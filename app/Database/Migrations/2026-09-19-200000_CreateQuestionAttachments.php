<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuestionAttachments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'question_id' => ['type' => 'INT', 'unsigned' => true],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('question_id');
        $this->forge->createTable('question_attachments', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('question_attachments', true);
    }
}
