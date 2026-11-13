<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Own-pace seasons (App\Libraries\Clock). Values can be overridden with setting('OwnPace.dailyCap')
 * and setting('OwnPace.idleHours') without a deploy.
 */
class OwnPace extends BaseConfig
{
    /** Days a player may move forward with the Next day button per real day */
    public int $dailyCap = 5;

    /** Autopilot skips a resort whose player hasn't visited for this many hours */
    public int $idleHours = 48;

    /** Shortest gap between two Next day presses, so a double click can't run two days */
    public int $minGapSeconds = 5;
}
