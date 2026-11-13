<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Own-pace seasons (App\Libraries\Clock). Values can be overridden with setting('OwnPace.<name>')
 * without a deploy.
 */
class OwnPace extends BaseConfig
{
    /** Next day spends a saved day; one more is saved every this many real hours */
    public int $bankHours = 4;

    /** Most days a player can have saved */
    public int $bankMax = 6;

    /** Autopilot skips a resort whose player hasn't visited for this many hours */
    public int $idleHours = 48;

    /** Shortest gap between two Next day presses, so a double click can't run two days */
    public int $minGapSeconds = 5;

    /** Days a resort must have played this season before the leaderboard ranks it */
    public int $minRankedDays = 10;
}
