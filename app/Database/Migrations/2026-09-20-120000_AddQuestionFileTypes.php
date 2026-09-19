<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuestionFileTypes extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('questions', [
            'allowed_file_types' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'type',
            ],
        ]);

        $this->db->query("UPDATE questions SET allowed_file_types = '[\"pdf\"]' WHERE type = 'upload'");
    }

    public function down(): void
    {
        $this->forge->dropColumn('questions', 'allowed_file_types');
    }
}
