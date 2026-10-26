<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The current season's weather was started by copying last season's final (summer) day, which left
 * a 0 cm snow base and a missing day 2. Clear it; App\Libraries\Weather regenerates every day of
 * the season on the next page view or tick. Archived seasons (weather_season_N) are untouched.
 */
class RegenerateSeasonWeather extends Migration
{
    public function up()
    {
        $this->db->table('weather')->emptyTable();
    }

    public function down()
    {
        // Nothing to restore: the old rows were generated, and the new ones are regenerated on demand
    }
}
