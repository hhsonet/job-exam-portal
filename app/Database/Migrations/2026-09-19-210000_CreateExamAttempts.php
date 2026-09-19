<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExamAttempts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'assigned_exam_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'after' => 'position',
            ],
        ]);
        $this->db->query('ALTER TABLE users ADD INDEX users_assigned_exam_id_index (assigned_exam_id)');
        $this->db->query(
            "UPDATE users u INNER JOIN exams e ON e.title = u.position "
            . "SET u.assigned_exam_id = e.id WHERE u.usertype = 'applicant'"
        );

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'exam_id' => ['type' => 'INT', 'unsigned' => true],
            'applicant_id' => ['type' => 'VARCHAR', 'constraint' => 50],
            'answers' => ['type' => 'LONGTEXT', 'null' => true],
            'marked' => ['type' => 'LONGTEXT', 'null' => true],
            'started_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('exam_id');
        $this->forge->addKey('applicant_id');
        $this->forge->addUniqueKey(['exam_id', 'applicant_id']);
        $this->forge->createTable('exam_attempts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('exam_attempts', true);
        $this->db->query('ALTER TABLE users DROP INDEX users_assigned_exam_id_index');
        $this->forge->dropColumn('users', 'assigned_exam_id');
    }
}
