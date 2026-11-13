<?php

namespace App\Libraries;

/**
 * Own-pace seasons: every resort's season number and game day (table player_clocks).
 *
 * With the `own_pace` flag on for a player, their row is the source of truth: the season helpers
 * (getGameDay(), getSeasonNumber(), ...) read it, Next day moves it forward (up to a daily cap) and
 * the nightly autopilot adds one day for active players who didn't press Next day the day before.
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

    public static function dailyCap(): int
    {
        $v = function_exists('setting') ? setting('OwnPace.dailyCap') : null;
        return max(0, (int) ($v ?? config('OwnPace')->dailyCap));
    }

    public static function idleHours(): int
    {
        $v = function_exists('setting') ? setting('OwnPace.idleHours') : null;
        return max(1, (int) ($v ?? config('OwnPace')->idleHours));
    }

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

    /** A player's clock row, created at today's shared day the first time it's needed */
    public static function row(int $userId): array
    {
        if (isset(self::$rows[$userId])) return self::$rows[$userId];
        $db = db_connect();
        $row = $db->table('player_clocks')->where('user_id', $userId)->get()->getRowArray();
        if (!$row) {
            $season = getGlobalSeason();
            $now = date('Y-m-d H:i:s');
            $db->query(
                'INSERT IGNORE INTO player_clocks (user_id, season_number, game_day, autopilot, last_advanced_date, season_started_at, created_at, updated_at)
                 VALUES (?, ?, ?, 1, ?, ?, ?, ?)',
                [$userId, (int) $season['season_number'], getGlobalGameDay(), date('Y-m-d'), $season['start_date'] . ' 00:00:00', $now, $now]
            );
            $row = $db->table('player_clocks')->where('user_id', $userId)->get()->getRowArray();
        }
        return self::$rows[$userId] = $row;
    }

    public static function forget(int $userId): void
    {
        unset(self::$rows[$userId]);
    }

    /** What the top bar, Settings and the apps show */
    public static function state(int $userId): array
    {
        $enabled = self::enabledFor($userId);
        if (!$enabled) return ['enabled' => false];

        $row = self::row($userId);
        $season = getSeasonByNumber((int) $row['season_number']) ?? getGlobalSeason();
        $length = max(1, (int) $season['duration_days']);
        $today = date('Y-m-d');
        $usedToday = ($row['manual_count_date'] ?? null) === $today ? (int) $row['manual_count'] : 0;
        $cap = self::dailyCap();
        $complete = (int) $row['game_day'] >= $length && self::nextSeason((int) $row['season_number']) === null;
        $autopilot = (int) $row['autopilot'] === 1;

        return [
            'enabled' => true,
            'seasonNumber' => (int) $row['season_number'],
            'day' => (int) $row['game_day'],
            'length' => $length,
            'autopilot' => $autopilot,
            'cap' => $cap,
            'left' => max(0, $cap - $usedToday),
            'seasonComplete' => $complete,
            // Pressing Next day today means autopilot leaves tonight alone
            'autopilotTonight' => $autopilot && !$complete && $usedToday === 0,
            'nextAutoAt' => self::nextAutoAt(),
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

    /** The next season a player can move into after finishing $seasonNumber, or null */
    public static function nextSeason(int $seasonNumber): ?array
    {
        $db = db_connect();
        $row = $db->table('seasons')->where('season_number >', $seasonNumber)->orderBy('season_number', 'ASC')->get()->getRowArray();
        return $row ?: null;
    }

    /**
     * Move a resort one day forward and run that day's tick, in one transaction.
     * $source: 'manual' (Next day: cap and gap apply) or 'autopilot' (nightly: once per real day).
     * @return array{ok: bool, message: string, report?: array, state?: array}
     */
    public static function advance(int $userId, string $source): array
    {
        $db = db_connect();
        $manual = $source === 'manual';
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $cfg = config('OwnPace');

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

            $usedToday = ($row['manual_count_date'] ?? null) === $today ? (int) $row['manual_count'] : 0;
            if ($manual) {
                if ($usedToday >= self::dailyCap()) {
                    $db->transRollback();
                    return ['ok' => false, 'message' => "You've used today's " . self::dailyCap() . ' days. More are available after midnight.'];
                }
                if (!empty($row['last_advanced_at']) && time() - strtotime($row['last_advanced_at']) < $cfg->minGapSeconds) {
                    $db->transRollback();
                    return ['ok' => false, 'message' => 'That day is still running. Try again in a few seconds.'];
                }
            } elseif (($row['last_advanced_date'] ?? null) === $today) {
                $db->transRollback();
                return ['ok' => false, 'message' => 'Already moved forward today.'];
            }

            $seasonNumber = (int) $row['season_number'];
            $season = getSeasonByNumber($seasonNumber) ?? getGlobalSeason();
            $day = (int) $row['game_day'];
            $update = ['last_advanced_at' => $now, 'last_advanced_date' => $today, 'updated_at' => $now];
            if ($manual) {
                $update['manual_count_date'] = $today;
                $update['manual_count'] = $usedToday + 1;
            }

            $rolled = null;
            if ($day >= (int) $season['duration_days']) {
                $next = self::nextSeason($seasonNumber);
                if ($next === null) {
                    $db->transRollback();
                    return ['ok' => false, 'message' => 'Your season is complete. The next season opens when it is announced.'];
                }
                SeasonEnd::finish($userId, $season, $next);
                $update += ['season_number' => (int) $next['season_number'], 'game_day' => 1, 'season_started_at' => $now];
                $rolled = $next;
            } else {
                $update['game_day'] = $day + 1;
            }

            $db->table('player_clocks')->where('user_id', $userId)->update($update);
            self::$rows[$userId] = array_merge($row, $update);

            $report = self::as($userId, fn() => DayTick::run($userId));

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
        $message = $rolled
            ? ($rolled['name'] ?? 'The next season') . ' has started. Day 1.'
            : 'Day ' . $state['day'] . ': +' . currency((int) $report['income']) . ' income, -' . currency((int) $report['expenses']) . ' costs, ' . number_format((int) $report['visitors']) . ' visitors.';
        return ['ok' => true, 'message' => $message, 'report' => $report, 'state' => $state];
    }

    /** Flag off: copy the shared season and day into the row after the nightly tick */
    public static function syncShared(int $userId): void
    {
        $row = self::row($userId);
        $season = getGlobalSeason();
        $update = ['game_day' => getGlobalGameDay(), 'last_advanced_date' => date('Y-m-d'), 'updated_at' => date('Y-m-d H:i:s')];
        if ((int) $row['season_number'] !== (int) $season['season_number']) {
            $update += ['season_number' => (int) $season['season_number'], 'season_started_at' => $season['start_date'] . ' 00:00:00'];
        }
        db_connect()->table('player_clocks')->where('user_id', $userId)->update($update);
        self::forget($userId);
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
               AND NOT (pc.manual_count_date = ? AND pc.manual_count > 0)
               AND pf.last_active >= ?',
            [$today, $yesterday, date('Y-m-d H:i:s', time() - self::idleHours() * 3600)]
        )->getResultArray(), 'user_id'));
    }
}
