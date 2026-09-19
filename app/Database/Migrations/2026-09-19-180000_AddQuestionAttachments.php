<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuestionAttachments extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('questions', [
            'attachment_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'options'],
            'attachment_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'attachment_name'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('questions', ['attachment_name', 'attachment_path']);
    }
}
