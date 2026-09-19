<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAssessmentSettings extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('exams', [
            'start_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'duration_seconds'],
            'end_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'start_at'],
            'allow_multiple_submissions' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'end_at'],
        ]);
        $this->forge->addColumn('submissions', [
            'exam_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'id'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('submissions', 'exam_id');
        $this->forge->dropColumn('exams', ['start_at', 'end_at', 'allow_multiple_submissions']);
    }
}
