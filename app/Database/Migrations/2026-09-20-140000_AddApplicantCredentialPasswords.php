<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApplicantCredentialPasswords extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'credential_password' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'password_hash',
                'comment'    => 'Encrypted copy used only for administrator credential handouts.',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'credential_password');
    }
}
