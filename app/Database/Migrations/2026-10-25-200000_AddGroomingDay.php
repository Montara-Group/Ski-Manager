<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Remember when each slope was last groomed, and whether that was a paid daytime run */
class AddGroomingDay extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('player_items');
        $add = [];
        if (!in_array('groomed_day', $fields, true)) {
            $add['groomed_day'] = ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'snow_depth'];
        }
        if (!in_array('groomed_manual', $fields, true)) {
            $add['groomed_manual'] = ['type' => 'TINYINT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'groomed_day'];
        }
        if ($add) $this->forge->addColumn('player_items', $add);
    }

    public function down()
    {
        $this->forge->dropColumn('player_items', ['groomed_day', 'groomed_manual']);
    }
}
