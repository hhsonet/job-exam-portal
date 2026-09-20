<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTypingAnswerText extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('questions', [
            'typing_answer' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'prompt',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('questions', 'typing_answer');
    }
}
