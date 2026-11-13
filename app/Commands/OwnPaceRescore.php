<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/** php spark ownpace:rescore: recompute every resort's leaderboard score (App\Libraries\ResortScore) */
class OwnPaceRescore extends BaseCommand
{
    protected $group = 'Game';
    protected $name = 'ownpace:rescore';
    protected $description = "Recompute every resort's own-pace leaderboard score from its current state";

    public function run(array $params)
    {
        helper(['rating']);
        $n = \App\Libraries\ResortScore::rescoreAll();
        CLI::write("Scored {$n} resorts.", 'green');
    }
}
