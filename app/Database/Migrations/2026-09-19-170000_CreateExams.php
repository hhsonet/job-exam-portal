<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExams extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 180],
            'description' => ['type' => 'TEXT', 'null' => true],
            'duration_seconds' => ['type' => 'INT', 'unsigned' => true, 'default' => 2700],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('exams', true);

        $this->forge->addColumn('questions', [
            'exam_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'id'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('questions', 'exam_id');
        $this->forge->dropTable('exams', true);
    }
}
