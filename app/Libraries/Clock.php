<?php

namespace App\Libraries;

/**
 * Own-pace seasons: every resort's season number and game day (table player_clocks).
 *
 * With the `own_pace` flag on for a player, their row is the source of truth: the season helpers
 * (getGameDay(), getSeasonNumber(), ...) read it. Next day spends a saved day from the player's day
 * bank, which refills by one every few real hours up to a small maximum, so nobody can race through
 * a season. The nightly autopilot adds one day for active players who didn't press Next day the day
 * before. On the season's last day the result goes to the final board (SeasonEnd::record); the
 * resort then keeps playing in free play until the next season is released, and the next day after
 * that starts it over in the new season (SeasonEnd::rollover).
 *
 * With the flag off, the shared calendar decides the day and the nightly tick copies it into the
 * row, so switching the flag on later starts the player where everyone else is.
 */
class Clock
{
    public const FLAG = 'own_pace';

    /** Whose clock the helpers read while the tick works on someone other than the signed-in player */
    private static ?int $subject = null;
    private static array $rows = [];
    private static array $enabled = [];

    private static function cfg(string $name): int
    {
        $v = function_exists('setting') ? setting('OwnPace.' . $name) : null;
        return (int) ($v ?? config('OwnPace')->{$name});
    }

    public static function bankMax(): int { return max(1, self::cfg('bankMax')); }
    public static function bankHours(): int { return max(1, self::cfg('bankHours')); }
    public static function idleHours(): int { return max(1, self::cfg('idleHours')); }

    public static function enabledFor(int $userId): bool
    {
        if ($userId <= 0) return false;
        return self::$enabled[$userId] ??= function_exists('featureEnabledFor') && featureEnabledFor(self::FLAG, $userId);
    }

    /** The player the season helpers answer for: the tick's current player, else the signed-in one */
    public static function subject(): ?int
    {
        if (self::$subject !== null) return self::$subject;
        if (is_cli() || !function_exists('auth')) return null;
        try {
            return auth()->loggedIn() ? (int) auth()->id() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Run $fn with the season helpers answering for $userId */
    public static function as(int $userId, callable $fn): mixed
    {
        $previous = self::$subject;
        self::$subject = $userId;
        try {
            return $fn();
        } finally {
            self::$subject = $previous;
        }
    }

    /** The subject's own clock when own pace is on for them, else null (the shared calendar applies) */
    public static function current(): ?array
    {
        $userId = self::subject();
        return $userId && self::enabledFor($userId) ? self::row($userId) : null;
    }

    /**
     * A player's clock row, created the first time it's needed: day 1 of the newest season for an
     * own-pace player, today's shared day otherwise.
     */
    public static function row(int $userId): array
    {
        if (isset(self::$rows[$userId])) return self::$rows[$userId];
        $db = db_connect();
        $row = $db->table('player_clocks')->where('user_id', $userId)->get()->getRowArray();
        if (!$row) {
            $season = getGlobalSeason();
            $now = date('Y-m-d H:i:s');
            $own = self::enabledFor($userId);
            $db->query(
                'INSERT IGNORE INTO player_clocks (user_id, season_number, game_day, autopilot, last_advanced_date, season_started_at, bank_days, bank_updated_at, created_at, updated_at)
                 VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?)',
                [$userId, (int) $season['season_number'], $own ? 1 : getGlobalGameDay(), date('Y-m-d'),
                 $own ? $now : $season['start_date'] . ' 00:00:00', self::bankMax(), $now, $now, $now]
            );
            $row = $db->table('player_clocks')->where('user_id', $userId)->get()->getRowArray();
        }
        return self::$rows[$userId] = $row;
    }

    public static function forget(int $userId): void
    {
        unset(self::$rows[$userId]);
    }

    /**
     * Saved days right now, refilled from the last update: [days, refill clock start (unix time)].
     * A full bank doesn't refill, so its clock restarts from now.
     */
    public static function bank(array $row): array
    {
        $max = self::bankMax();
        $step = self::bankHours() * 3600;
        $days = (int) ($row['bank_days'] ?? 0);
        $since = !empty($row['bank_updated_at']) ? strtotime($row['bank_updated_at']) : time();
        if ($days >= $max) return [$max, time()];
        $earned = intdiv(max(0, time() - $since), $step);
        $days = min($max, $days + $earned);
        return [$days, $days >= $max ? time() : $since + $earned * $step];
    }

    /** The next season a player can move into after $seasonNumber: it must exist and be released (start date reached) */
    public static function nextSeason(int $seasonNumber): ?array
    {
        $row = db_connect()->table('seasons')->where('season_number >', $seasonNumber)
            ->groupStart()->where('active', 1)->orWhere('start_date <=', date('Y-m-d'))->groupEnd()
            ->orderBy('season_number', 'ASC')->get()->getRowArray();
        return $row ?: null;
    }

    /** What the top bar, Settings and the apps show */
    public static function state(int $userId): array
    {
        if (!self::enabledFor($userId)) return ['enabled' => false];

        $row = self::row($userId);
        $season = getSeasonByNumber((int) $row['season_number']) ?? getGlobalSeason();
        $length = max(1, (int) $season['duration_days']);
        $day = (int) $row['game_day'];
        [$bank, $since] = self::bank($row);
        $autopilot = (int) $row['autopilot'] === 1;
        $usedToday = ($row['manual_count_date'] ?? null) === date('Y-m-d') && (int) $row['manual_count'] > 0;
        $next = $day >= $length ? self::nextSeason((int) $row['season_number']) : null;

        return [
            'enabled' => true,
            'seasonNumber' => (int) $row['season_number'],
            'day' => $day,
            'length' => $length,
            // Past the last day: the season is over and the resort plays on until the next one opens
            'freePlay' => $day > $length,
            'nextSeasonReady' => $next !== null,
            'nextSeasonName' => $next['name'] ?? null,
            'autopilot' => $autopilot,
            'bank' => $bank,
            'bankMax' => self::bankMax(),
            'bankHours' => self::bankHours(),
            'nextBankAt' => $bank < self::bankMax() ? $since + self::bankHours() * 3600 : null,
            // Pressing Next day today means autopilot leaves tonight alone
            'autopilotTonight' => $autopilot && !$usedToday,
            'nextAutoAt' => self::nextAutoAt(),
            'score' => (int) ($row['score'] ?? 0),
        ];
    }

    /** Unix time of the next 00:05 in the app timezone, when the nightly tick runs */
    public static function nextAutoAt(): int
    {
        $today = strtotime(date('Y-m-d') . ' 00:05:00');
        return time() < $today ? $today : strtotime(date('Y-m-d', strtotime('+1 day')) . ' 00:05:00');
    }

    public static function setAutopilot(int $userId, bool $on): void
    {
        self::row($userId);
        db_connect()->table('player_clocks')->where('user_id', $userId)->update(['autopilot' => $on ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')]);
        self::forget($userId);
    }

    /**
     * Move a resort one day forward and run that day's tick, in one transaction.
     * $source: 'manual' (Next day: spends a saved day) or 'autopilot' (nightly: once per real day).
     * @return array{ok: bool, message: string, report?: array, state?: array}
     */
    public static function advance(int $userId, string $source): array
    {
        $db = db_connect();
        $manual = $source === 'manual';
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $db->resetTransStatus();
        $db->transBegin();
        try {
            // Locks the row: a second click, or the timer, waits here and then sees the new day
            $row = $db->query('SELECT * FROM player_clocks WHERE user_id = ? FOR UPDATE', [$userId])->getRowArray();
            if (!$row) {
                $db->transRollback();
                self::row($userId);
                return self::advance($userId, $source);
            }

            $update = ['last_advanced_at' => $now, 'last_advanced_date' => $today, 'updated_at' => $now];
            if ($manual) {
                [$bank, $since] = self::bank($row);
                if ($bank < 1) {
                    $db->transRollback();
                    $wait = max(60, $since + self::bankHours() * 3600 - time());
                    return ['ok' => false, 'message' => 'No saved days left. The next one is ready in ' . self::duration($wait) . '.'];
                }
                if (!empty($row['last_advanced_at']) && time() - strtotime($row['last_advanced_at']) < self::cfg('minGapSeconds')) {
                    $db->transRollback();
                    return ['ok' => false, 'message' => 'That day is still running. Try again in a few seconds.'];
                }
                $usedToday = ($row['manual_count_date'] ?? null) === $today ? (int) $row['manual_count'] : 0;
                $update += [
                    'bank_days' => $bank - 1, 'bank_updated_at' => date('Y-m-d H:i:s', $since),
                    'manual_count_date' => $today, 'manual_count' => $usedToday + 1,
                ];
            } elseif (($row['last_advanced_date'] ?? null) === $today) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Already moved forward today.'];
            }

            $seasonNumber = (int) $row['season_number'];
            $season = getSeasonByNumber($seasonNumber) ?? getGlobalSeason();
            $length = max(1, (int) $season['duration_days']);
            $day = (int) $row['game_day'];

            $rolled = null;
            $next = $day >= $length ? self::nextSeason($seasonNumber) : null;
            if ($next) {
                $update += SeasonEnd::rollover($userId, $season, $next);
                $rolled = $next;
            } else {
                // Includes free play past the last day: the season's weather and phases repeat
                $update['game_day'] = $day + 1;
            }

            $db->table('player_clocks')->where('user_id', $userId)->update($update);
            self::$rows[$userId] = array_merge($row, $update);

            $report = self::as($userId, fn() => DayTick::run($userId));
            ResortScore::store($userId, (int) $report['visitors']);

            $finished = null;
            if (!$rolled && (int) $update['game_day'] === $length) {
                $finished = SeasonEnd::record($userId, $season);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                self::forget($userId);
                return ['ok' => false, 'message' => "The day didn't run. Nothing changed. Try again."];
            }
            $db->transCommit();
            clearResortRatingCache($userId);
        } catch (\Throwable $e) {
            $db->transRollback();
            $db->resetTransStatus();
            self::forget($userId);
            log_message('error', "Clock::advance user {$userId} ({$source}) failed: " . $e->getMessage());
            if (class_exists('\\Sentry\\SentrySdk')) \Sentry\captureException($e);
            return ['ok' => false, 'message' => "The day didn't run. Nothing changed. Try again."];
        }

        self::forget($userId);
        $state = self::state($userId);
        $message = match (true) {
            $rolled !== null => ($rolled['name'] ?? 'The next season') . ' has started. Day 1.',
            $finished !== null => "Season complete. Final resort score {$finished['score']}: {$finished['badge']}, +{$finished['genepis']} Génépis.",
            default => 'Day ' . $state['day'] . ': +' . currency((int) $report['income']) . ' income, -' . currency((int) $report['expenses']) . ' costs, ' . number_format((int) $report['visitors']) . ' visitors.',
        };
        return ['ok' => true, 'message' => $message, 'report' => $report, 'state' => $state];
    }

    private static function duration(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = (int) ceil(($seconds % 3600) / 60);
        if ($m === 60) { $h++; $m = 0; }
        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }

    /** Flag off: copy the shared season and day into the row after the nightly tick */
    public static function syncShared(int $userId, int $visitorsToday = 0): void
    {
        $row = self::row($userId);
        $season = getGlobalSeason();
        $update = ['game_day' => getGlobalGameDay(), 'last_advanced_date' => date('Y-m-d'), 'updated_at' => date('Y-m-d H:i:s')];
        if ((int) $row['season_number'] !== (int) $season['season_number']) {
            $update += ['season_number' => (int) $season['season_number'], 'season_started_at' => $season['start_date'] . ' 00:00:00', 'season_visitors' => 0];
        }
        db_connect()->table('player_clocks')->where('user_id', $userId)->update($update);
        self::forget($userId);
        ResortScore::store($userId, $visitorsToday);
    }

    /** Own-pace players the nightly autopilot moves forward: autopilot on, seen recently, not yet moved today, no Next day yesterday */
    public static function autopilotDue(): array
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        return array_map('intval', array_column(db_connect()->query(
            'SELECT pc.user_id FROM player_clocks pc JOIN player_finances pf ON pf.user_id = pc.user_id
             WHERE pc.autopilot = 1
               AND (pc.last_advanced_date IS NULL OR pc.last_advanced_date < ?)
               AND NOT (pc.manual_count_date <=> ? AND pc.manual_count > 0)
               AND pf.last_active >= ?',
            [$today, $yesterday, date('Y-m-d H:i:s', time() - self::idleHours() * 3600)]
        )->getResultArray(), 'user_id'));
    }
}
