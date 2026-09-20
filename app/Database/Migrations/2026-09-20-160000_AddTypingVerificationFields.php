<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTypingVerificationFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('submission_question_marks', [
            'similarity_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true, 'after' => 'marks'],
            'verification_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'similarity_percent'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('submission_question_marks', ['similarity_percent', 'verification_status']);
    }
}
