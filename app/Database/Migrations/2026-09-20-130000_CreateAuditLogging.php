<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogging extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'actor_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'user'],
            'event' => ['type' => 'VARCHAR', 'constraint' => 80],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'old_values' => ['type' => 'TEXT', 'null' => true],
            'new_values' => ['type' => 'TEXT', 'null' => true],
            'metadata' => ['type' => 'TEXT', 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'browser' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'browser_version' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'operating_system' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'device_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Unknown'],
            'session_identifier' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('event');
        $this->forge->addKey('entity_type');
        $this->forge->addKey('ip_address');
        $this->forge->addKey('created_at');
        $this->forge->createTable('audit_logs', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'login_identifier' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'failure_reason' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'browser' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'browser_version' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'operating_system' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'device_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Unknown'],
            'session_identifier' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'logged_in_at' => ['type' => 'DATETIME', 'null' => true],
            'logged_out_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('login_identifier');
        $this->forge->addKey('status');
        $this->forge->addKey('ip_address');
        $this->forge->addKey('created_at');
        $this->forge->createTable('login_logs', true);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'submission_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'exam_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'application_reference' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'application_version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'applicant_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 50],
            'previous_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'new_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'deadline_at' => ['type' => 'DATETIME', 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'browser' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'browser_version' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'operating_system' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'device_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Unknown'],
            'session_identifier' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'snapshot_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'remarks' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('submission_id');
        $this->forge->addKey('exam_id');
        $this->forge->addKey('application_reference');
        $this->forge->addKey('user_id');
        $this->forge->addKey('applicant_id');
        $this->forge->addKey('action');
        $this->forge->addKey('submitted_at');
        $this->forge->addKey('ip_address');
        $this->forge->createTable('submission_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('submission_logs', true);
        $this->forge->dropTable('login_logs', true);
        $this->forge->dropTable('audit_logs', true);
    }
}
