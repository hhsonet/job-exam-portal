<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'usertype'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users', true);

        $this->db->table('users')->insert([
            'username'      => 'sonet',
            'password_hash' => password_hash('Mango@000', PASSWORD_DEFAULT),
            'usertype'      => 'SuperAdmin',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
