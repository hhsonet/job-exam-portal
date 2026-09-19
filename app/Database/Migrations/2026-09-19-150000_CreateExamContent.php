<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExamContent extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'prompt'     => ['type' => 'TEXT'],
            'hint'       => ['type' => 'TEXT', 'null' => true],
            'points'     => ['type' => 'INT', 'unsigned' => true],
            'options'    => ['type' => 'TEXT', 'null' => true],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('questions', true);

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reference'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'applicant_id'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'answered_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'total_count'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'marked_count'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'time_used'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'answers'        => ['type' => 'LONGTEXT', 'null' => true],
            'score'          => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'max_score'      => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'submitted'],
            'marker_notes'   => ['type' => 'TEXT', 'null' => true],
            'submitted_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('reference');
        $this->forge->createTable('submissions', true);

        $now = date('Y-m-d H:i:s');
        $questions = [
            ['type' => 'single', 'points' => 2, 'prompt' => 'What is the worst-case time complexity of a binary search over a sorted array of n elements?', 'hint' => 'Select one answer.', 'options' => [['key' => 'A', 'text' => 'O(1)'], ['key' => 'B', 'text' => 'O(log n)'], ['key' => 'C', 'text' => 'O(n)'], ['key' => 'D', 'text' => 'O(n log n)']]],
            ['type' => 'multi', 'points' => 3, 'prompt' => 'Which of the following HTTP methods are idempotent?', 'hint' => 'Select all that apply.', 'options' => [['key' => 'A', 'text' => 'GET'], ['key' => 'B', 'text' => 'POST'], ['key' => 'C', 'text' => 'PUT'], ['key' => 'D', 'text' => 'DELETE']]],
            ['type' => 'bool', 'points' => 1, 'prompt' => 'Adding an index to a database table always improves write performance.', 'hint' => 'Select true or false.', 'options' => [['key' => 'A', 'text' => 'True'], ['key' => 'B', 'text' => 'False']]],
            ['type' => 'written', 'points' => 6, 'prompt' => 'An API endpoint fails for roughly 2% of requests, with no clear pattern in the error logs. Describe how you would investigate.', 'hint' => 'Written answer. Describe your steps and what you would look at first.', 'options' => null],
            ['type' => 'single', 'points' => 2, 'prompt' => 'You committed work to the wrong branch and have not pushed yet. Which approach preserves your commit history most safely?', 'hint' => 'Select one answer.', 'options' => [['key' => 'A', 'text' => 'Delete the branch and rewrite the changes by hand'], ['key' => 'B', 'text' => 'Create the correct branch from your current HEAD, then reset the original branch'], ['key' => 'C', 'text' => 'Force-push the wrong branch over main'], ['key' => 'D', 'text' => 'Amend the previous commit with an empty message']]],
            ['type' => 'multi', 'points' => 3, 'prompt' => 'Which of these are genuine benefits of normalising a relational schema?', 'hint' => 'Select all that apply.', 'options' => [['key' => 'A', 'text' => 'Reduced data duplication'], ['key' => 'B', 'text' => 'Faster reads for every query'], ['key' => 'C', 'text' => 'Fewer update anomalies'], ['key' => 'D', 'text' => 'Clearer ownership of each fact in the data model']]],
            ['type' => 'bool', 'points' => 1, 'prompt' => 'A unit test that depends on the system clock and the network can still be considered a unit test.', 'hint' => 'Select true or false.', 'options' => [['key' => 'A', 'text' => 'True'], ['key' => 'B', 'text' => 'False']]],
            ['type' => 'written', 'points' => 6, 'prompt' => 'Your team lead asks for a feature by Friday. Two days in, you find the estimate was wrong and it will take a week. What do you do?', 'hint' => 'Written answer. We are interested in how you communicate, not in a perfect answer.', 'options' => null],
            ['type' => 'upload', 'points' => 8, 'prompt' => 'Upload your take-home solution: a short design document for a URL-shortening service.', 'hint' => 'PDF only, up to 10 MB. You may replace the file any time before you submit.', 'options' => null],
        ];

        foreach ($questions as &$question) {
            $question['options'] = $question['options'] === null ? null : json_encode($question['options']);
            $question['created_at'] = $now;
            $question['updated_at'] = $now;
        }
        unset($question);

        $this->db->table('questions')->insertBatch($questions);
    }

    public function down(): void
    {
        $this->forge->dropTable('submissions', true);
        $this->forge->dropTable('questions', true);
    }
}
