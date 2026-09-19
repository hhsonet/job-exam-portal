<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicantFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'full_name'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'username'],
            'applicant_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'full_name'],
            'position'      => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true, 'after' => 'applicant_code'],
        ]);
        $this->db->query('ALTER TABLE users ADD UNIQUE KEY users_applicant_code_unique (applicant_code)');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE users DROP INDEX users_applicant_code_unique');
        $this->forge->dropColumn('users', ['full_name', 'applicant_code', 'position']);
    }
}
