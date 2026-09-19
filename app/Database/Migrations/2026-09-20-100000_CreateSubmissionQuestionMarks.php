<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubmissionQuestionMarks extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'submission_id' => ['type' => 'INT', 'unsigned' => true],
            'question_id' => ['type' => 'INT', 'unsigned' => true],
            'marks' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('submission_id');
        $this->forge->addKey('question_id');
        $this->forge->addUniqueKey(['submission_id', 'question_id']);
        $this->forge->createTable('submission_question_marks', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('submission_question_marks', true);
    }
}
