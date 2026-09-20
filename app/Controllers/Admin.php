<?php

namespace App\Controllers;

use App\Libraries\CredentialPdf;
use App\Services\TypingVerificationService;
use CodeIgniter\HTTP\ResponseInterface;

class Admin extends BaseController
{
    private function auditAdmin(string $event, string $entityType, string|int|null $entityId, array $details = []): void
    {
        (new \App\Services\AuditLogService())->log($event, (int) session()->get('admin_id'), [
            'actorType' => 'admin',
            'entityType' => $entityType,
            'entityId' => $entityId === null ? null : (string) $entityId,
        ] + $details);
    }

    private function requireAdmin(): ?ResponseInterface
    {
        if (! session()->get('admin_authenticated')) {
            return redirect()->to('/admin/login');
        }

        return null;
    }

    public function loginForm(): string|ResponseInterface
    {
        if (session()->get('admin_authenticated')) {
            return redirect()->to('/admin');
        }

        return view('admin/login', ['error' => null]);
    }

    public function login(): ResponseInterface|string
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        $user = db_connect()->table('users')
            ->where('username', $username)
            ->whereIn('usertype', ['SuperAdmin', 'admin'])
            ->get()
            ->getRowArray();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            (new \App\Services\AuditLogService())->recordLogin(
                $user ? (int) $user['id'] : null,
                $username,
                'FAILED',
                $user ? 'INVALID_CREDENTIALS' : 'USER_NOT_FOUND'
            );
            return view('admin/login', [
                'error' => 'Invalid admin username or password.',
            ]);
        }

        session()->regenerate(true);
        session()->set([
            'admin_authenticated' => true,
            'admin_id'            => $user['id'],
            'admin_username'      => $user['username'],
            'admin_usertype'      => $user['usertype'],
        ]);
        (new \App\Services\AuditLogService())->recordLogin((int) $user['id'], $user['username'], 'SUCCESS');

        return redirect()->to('/admin');
    }

    public function logout(): ResponseInterface
    {
        (new \App\Services\AuditLogService())->recordLogout((int) session()->get('admin_id'), 'LOGOUT');
        session()->remove([
            'admin_authenticated',
            'admin_id',
            'admin_username',
            'admin_usertype',
        ]);

        return redirect()->to('/admin/login');
    }

    public function index(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();

        $latestSubmissionStats = $db->query(
            "SELECT COUNT(*) AS total_count,
                    SUM(CASE WHEN s.status = 'submitted' THEN 1 ELSE 0 END) AS pending_count
             FROM submissions s
             INNER JOIN (
                 SELECT MAX(id) AS id
                 FROM submissions
                 GROUP BY exam_id, applicant_id
             ) latest ON latest.id = s.id"
        )->getRowArray() ?: ['total_count' => 0, 'pending_count' => 0];

        return view('admin/index', [
            'username' => session()->get('admin_username'),
            'usertype' => session()->get('admin_usertype'),
            'questionCount' => $db->table('questions')->where('is_active', 1)->countAllResults(),
            'submissionCount' => (int) ($latestSubmissionStats['total_count'] ?? 0),
            'pendingCount' => (int) ($latestSubmissionStats['pending_count'] ?? 0),
        ]);
    }

    public function questions(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $questions = db_connect()->table('questions')->orderBy('id', 'ASC')->get()->getResultArray();
        foreach ($questions as &$question) {
            $question['options'] = $question['options'] ? json_decode($question['options'], true) : [];
        }

        return view('admin/questions', ['questions' => $questions, 'error' => null]);
    }

    public function exams(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $exams = db_connect()->query(
            "SELECT e.*,
                    COUNT(DISTINCT CASE WHEN q.is_active = 1 THEN q.id END) AS question_count,
                    COUNT(DISTINCT u.id) AS applicant_count,
                    COUNT(DISTINCT a.id) AS attempt_count,
                    COUNT(DISTINCT s.id) AS submission_count,
                    MAX(s.submitted_at) AS latest_submission_at
             FROM exams e
             LEFT JOIN questions q ON q.exam_id = e.id
             LEFT JOIN users u ON u.assigned_exam_id = e.id AND u.usertype = 'applicant'
             LEFT JOIN exam_attempts a ON a.exam_id = e.id
             LEFT JOIN (
                 SELECT s1.*
                 FROM submissions s1
                 INNER JOIN (
                     SELECT MAX(id) AS id
                     FROM submissions
                     GROUP BY exam_id, applicant_id
                 ) latest ON latest.id = s1.id
             ) s ON s.exam_id = e.id
             GROUP BY e.id
             ORDER BY e.id DESC"
        )->getResultArray();
        $now = date('Y-m-d H:i:s');
        foreach ($exams as &$exam) {
            if ($exam['status'] !== 'active') {
                $exam['display_status'] = ucfirst((string) $exam['status']);
            } elseif ($exam['start_at'] && $now < $exam['start_at']) {
                $exam['display_status'] = 'Scheduled';
            } elseif ($exam['end_at'] && $now > $exam['end_at']) {
                $exam['display_status'] = 'Closed';
            } else {
                $exam['display_status'] = 'Open';
            }
            $exam['submission_rate'] = (int) $exam['applicant_count'] > 0
                ? min(100, round(((int) $exam['submission_count'] / (int) $exam['applicant_count']) * 100))
                : 0;
        }
        unset($exam);

        return view('admin/exams', ['exams' => $exams, 'error' => $this->request->getGet('error'), 'success' => $this->request->getGet('success')]);
    }

    public function examMonitor(int $id): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $monitorData = $this->buildExamMonitorData($id);
        if (! $monitorData) {
            return redirect()->to('/admin/exams?error=' . rawurlencode('Exam not found.'));
        }

        return view('admin/exam_monitor', ['monitorData' => $monitorData]);
    }

    public function examMonitorData(int $id): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $monitorData = $this->buildExamMonitorData($id);
        if (! $monitorData) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Exam not found.']);
        }

        return $this->response->setJSON($monitorData);
    }

    private function buildExamMonitorData(int $examId): ?array
    {
        $db = db_connect();
        $exam = $db->table('exams')->where('id', $examId)->get()->getRowArray();
        if (! $exam) {
            return null;
        }

        $nowString = date('Y-m-d H:i:s');
        if ($exam['status'] !== 'active') {
            $displayStatus = ucfirst((string) $exam['status']);
        } elseif ($exam['start_at'] && $nowString < $exam['start_at']) {
            $displayStatus = 'Scheduled';
        } elseif ($exam['end_at'] && $nowString > $exam['end_at']) {
            $displayStatus = 'Closed';
        } else {
            $displayStatus = 'Open';
        }

        $questionRows = $db->table('questions')
            ->select('id, type')
            ->where(['exam_id' => $examId, 'is_active' => 1])
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        $questionCount = count($questionRows);

        $applicantRows = $db->query(
            "SELECT u.id AS user_id, u.full_name, u.applicant_code, u.email,
                    s.id AS submission_id, s.status AS submission_status,
                    s.answered_count, s.total_count, s.submitted_at, s.time_used,
                    s.answers AS submission_answers,
                    a.id AS attempt_id, a.started_at AS attempt_started_at,
                    a.updated_at AS attempt_updated_at, a.answers AS attempt_answers
             FROM users u
             LEFT JOIN exam_attempts a
                    ON a.exam_id = ? AND a.applicant_id = u.applicant_code
             LEFT JOIN (
                 SELECT s1.*
                 FROM submissions s1
                 INNER JOIN (
                     SELECT MAX(id) AS id
                     FROM submissions
                     WHERE exam_id = ?
                     GROUP BY exam_id, applicant_id
                 ) latest ON latest.id = s1.id
             ) s ON s.exam_id = ? AND s.applicant_id = u.applicant_code
             WHERE u.usertype = 'applicant' AND u.assigned_exam_id = ?
             ORDER BY u.full_name ASC",
            [$examId, $examId, $examId, $examId]
        )->getResultArray();

        $applicantCodes = array_values(array_filter(array_map(static fn (array $row): string => (string) $row['applicant_code'], $applicantRows)));
        $activityRows = $db->table('audit_logs')
            ->where(['entity_type' => 'exam_activity', 'entity_id' => (string) $examId])
            ->orderBy('id', 'DESC')->limit(300)->get()->getResultArray();
        $activityByApplicant = [];
        $globalEvents = [];
        foreach ($activityRows as $activity) {
            $metadata = $activity['metadata'] ? json_decode($activity['metadata'], true) : [];
            $applicantCode = is_array($metadata) ? (string) ($metadata['applicant_id'] ?? '') : '';
            if ($applicantCode === '' || ! in_array($applicantCode, $applicantCodes, true)) {
                continue;
            }
            $event = preg_replace('/^APPLICANT_/', '', (string) $activity['event']) ?: (string) $activity['event'];
            $eventLabel = ucwords(strtolower(str_replace('_', ' ', $event)));
            $entry = [
                'event' => $event,
                'label' => $eventLabel,
                'created_at' => $activity['created_at'],
                'description' => $activity['description'],
            ];
            $activityByApplicant[$applicantCode][] = $entry;
            $globalEvents[] = [
                'applicant_id' => $applicantCode,
                'event' => $event,
                'label' => $eventLabel,
                'created_at' => $activity['created_at'],
            ];
        }

        $submissionRows = $db->table('submission_logs sl')
            ->select('sl.applicant_id, sl.action, sl.created_at, u.full_name')
            ->join('users u', 'u.applicant_code = sl.applicant_id', 'left')
            ->where('sl.exam_id', $examId)
            ->orderBy('sl.id', 'DESC')->limit(100)->get()->getResultArray();
        foreach ($submissionRows as $submission) {
            $actionLabel = $submission['action'] === 'RESUBMIT' ? 'Exam resubmitted' : 'Exam submitted';
            $globalEvents[] = [
                'applicant_id' => $submission['applicant_id'],
                'applicant_name' => $submission['full_name'] ?: 'Applicant',
                'event' => $submission['action'],
                'label' => $actionLabel,
                'created_at' => $submission['created_at'],
            ];
            $activityByApplicant[$submission['applicant_id']][] = [
                'event' => $submission['action'],
                'label' => $actionLabel,
                'created_at' => $submission['created_at'],
                'description' => 'Submission activity',
            ];
        }
        foreach ($activityByApplicant as &$events) {
            usort($events, static fn (array $left, array $right): int => strcmp((string) $right['created_at'], (string) $left['created_at']));
        }
        unset($events);

        $loginRows = $db->table('login_logs l')
            ->select('l.user_id, l.ip_address, l.browser, l.browser_version, l.operating_system, l.device_type, l.logged_in_at')
            ->join('users u', 'u.id = l.user_id', 'inner')
            ->where(['u.assigned_exam_id' => $examId, 'u.usertype' => 'applicant', 'l.status' => 'SUCCESS'])
            ->orderBy('l.id', 'DESC')->get()->getResultArray();
        $loginByUser = [];
        foreach ($loginRows as $login) {
            $loginByUser[(int) $login['user_id']] ??= $login;
        }

        $taskLabels = [
            'upload' => 'File upload',
            'written' => 'Written response',
            'typing' => 'Typing test',
            'single' => 'Objective questions',
            'multi' => 'Objective questions',
            'bool' => 'Objective questions',
        ];
        $taskDefinitions = [];
        foreach ($questionRows as $question) {
            $taskKey = $taskLabels[$question['type']] ?? 'Questions';
            $taskDefinitions[$taskKey] ??= ['label' => $taskKey, 'question_ids' => []];
            $taskDefinitions[$taskKey]['question_ids'][] = (int) $question['id'];
        }
        $taskDefinitions = array_values($taskDefinitions);

        $countAnswered = static function (mixed $value): bool {
            if (is_array($value)) {
                return isset($value['file']) || count($value) > 0;
            }

            return trim((string) $value) !== '';
        };
        $serverNow = time();
        $applicants = [];
        $summary = ['total' => count($applicantRows), 'active' => 0, 'submitted' => 0, 'not_started' => 0, 'offline' => 0, 'attention' => 0];

        foreach ($applicantRows as $row) {
            $hasSubmission = ! empty($row['submission_id']);
            $answers = json_decode((string) ($hasSubmission ? $row['submission_answers'] : $row['attempt_answers']), true);
            $answers = is_array($answers) ? $answers : [];
            $answeredCount = $hasSubmission ? (int) $row['answered_count'] : count(array_filter($answers, $countAnswered));
            $progress = $questionCount > 0 ? min(100, (int) round(($answeredCount / $questionCount) * 100)) : 0;
            $remaining = null;
            $deadline = null;
            if (! empty($row['attempt_started_at'])) {
                $deadline = strtotime($row['attempt_started_at']) + (int) $exam['duration_seconds'];
                if ($exam['end_at']) {
                    $deadline = min($deadline, strtotime($exam['end_at']));
                }
                $remaining = max(0, $deadline - $serverNow);
            }

            $attemptFresh = ! empty($row['attempt_updated_at']) && (strtotime($row['attempt_updated_at']) >= ($serverNow - 45));
            if ($hasSubmission) {
                $status = 'submitted';
            } elseif (empty($row['attempt_id'])) {
                $status = 'not_started';
            } elseif ($remaining !== null && $remaining < 1) {
                $status = 'expired';
            } elseif ($attemptFresh) {
                $status = 'active';
            } else {
                $status = 'offline';
            }

            $latestEvent = $activityByApplicant[$row['applicant_code']][0] ?? null;
            $attentionReasons = [];
            if ($status === 'offline') {
                $attentionReasons[] = 'Connection may be lost';
            }
            if ($status === 'expired') {
                $attentionReasons[] = 'Exam time ended';
            }
            if ($remaining !== null && $remaining > 0 && $remaining <= 300 && ! $hasSubmission) {
                $attentionReasons[] = 'Low time remaining';
            }
            if ($latestEvent && in_array($latestEvent['event'], ['TAB_HIDDEN', 'FOCUS_LOST', 'CONNECTION_LOST'], true)) {
                $attentionReasons[] = $latestEvent['label'];
            }
            $attention = count($attentionReasons) > 0;

            $tasks = [];
            foreach ($taskDefinitions as $definition) {
                $taskAnswered = 0;
                foreach ($definition['question_ids'] as $questionId) {
                    if ($countAnswered($answers['q' . $questionId] ?? null)) {
                        $taskAnswered++;
                    }
                }
                $taskTotal = count($definition['question_ids']);
                $tasks[] = [
                    'label' => $definition['label'],
                    'answered' => $taskAnswered,
                    'total' => $taskTotal,
                    'status' => $taskAnswered >= $taskTotal ? 'Completed' : ($taskAnswered > 0 ? 'Working' : 'Not started'),
                ];
            }
            $currentTask = 'Questions';
            foreach ($tasks as $task) {
                if ($task['status'] === 'Working') {
                    $currentTask = $task['label'];
                    break;
                }
                if ($task['status'] === 'Not started') {
                    $currentTask = $task['label'];
                }
            }

            $login = $loginByUser[(int) $row['user_id']] ?? [];
            $applicantEvents = array_slice($activityByApplicant[$row['applicant_code']] ?? [], 0, 12);
            $lastActivity = $row['attempt_updated_at'] ?: ($row['submitted_at'] ?: null);
            $summary[$status === 'expired' ? 'offline' : $status] = ($summary[$status === 'expired' ? 'offline' : $status] ?? 0) + 1;
            if ($attention) {
                $summary['attention']++;
            }

            $applicants[] = [
                'user_id' => (int) $row['user_id'],
                'name' => $row['full_name'] ?: 'Applicant',
                'applicant_id' => $row['applicant_code'],
                'status' => $status,
                'progress' => $progress,
                'answered_count' => $answeredCount,
                'total_count' => $questionCount,
                'time_remaining' => $hasSubmission ? null : $remaining,
                'started_at' => $row['attempt_started_at'],
                'last_activity' => $lastActivity,
                'submitted_at' => $row['submitted_at'],
                'time_used' => (int) ($row['time_used'] ?? 0),
                'tasks' => $tasks,
                'current_task' => $currentTask,
                'attention' => $attention,
                'attention_reasons' => $attentionReasons,
                'events' => $applicantEvents,
                'session' => [
                    'ip' => $login['ip_address'] ?? null,
                    'browser' => trim(($login['browser'] ?? '') . ' ' . ($login['browser_version'] ?? '')) ?: null,
                    'os' => $login['operating_system'] ?? null,
                    'device' => $login['device_type'] ?? null,
                ],
            ];
        }

        usort($globalEvents, static fn (array $left, array $right): int => strcmp((string) $right['created_at'], (string) $left['created_at']));

        return [
            'server_now' => date('c'),
            'exam' => [
                'id' => $examId,
                'title' => $exam['title'],
                'description' => $exam['description'],
                'status' => $displayStatus,
                'start_at' => $exam['start_at'],
                'end_at' => $exam['end_at'],
                'duration_seconds' => (int) $exam['duration_seconds'],
                'question_count' => $questionCount,
            ],
            'summary' => $summary,
            'applicants' => $applicants,
            'events' => array_slice($globalEvents, 0, 20),
        ];
    }

    public function deleteExam(int $id): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $exam = $db->table('exams')->where('id', $id)->get()->getRowArray();
        if (! $exam) {
            return redirect()->to('/admin/exams?error=' . rawurlencode('Exam not found.'));
        }
        if ($db->table('submissions')->where('exam_id', $id)->countAllResults() > 0) {
            return redirect()->to('/admin/exams?error=' . rawurlencode('This exam cannot be deleted because it has submissions.'));
        }
        if ($db->table('users')->where(['assigned_exam_id' => $id, 'usertype' => 'applicant'])->countAllResults() > 0) {
            return redirect()->to('/admin/exams?error=' . rawurlencode('This exam cannot be deleted while applicants are assigned to it.'));
        }

        $db->transStart();
        $db->table('exam_attempts')->where('exam_id', $id)->delete();
        $db->table('questions')->where('exam_id', $id)->delete();
        $db->table('exams')->where('id', $id)->delete();
        $db->transComplete();
        if ($db->transStatus()) {
            $this->auditAdmin('EXAM_DELETED', 'exam', $id, [
                'description' => 'Assessment deleted.',
                'oldValues' => ['title' => $exam['title']],
            ]);
        }

        return redirect()->to('/admin/exams');
    }

    public function deleteAllSubmissions(int $id = 0): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $exam = $id > 0 ? $db->table('exams')->where('id', $id)->get()->getRowArray() : null;
        if ($id > 0 && ! $exam) {
            return redirect()->to('/admin/exams?error=' . rawurlencode('Exam not found.'));
        }

        $submissionQuery = $db->table('submissions')->select('answers');
        $attemptQuery = $db->table('exam_attempts')->select('answers');
        if ($id > 0) {
            $submissionQuery->where('exam_id', $id);
            $attemptQuery->where('exam_id', $id);
        }
        $submissions = $submissionQuery->get()->getResultArray();
        $attempts = $attemptQuery->get()->getResultArray();
        $storedAnswerRows = array_merge($submissions, $attempts);
        $count = count($submissions);
        $db->transStart();
        if ($id > 0) {
            $db->table('submissions')->where('exam_id', $id)->delete();
            $db->table('exam_attempts')->where('exam_id', $id)->delete();
        } else {
            $db->table('submissions')->where('id >', 0)->delete();
            $db->table('exam_attempts')->where('id >', 0)->delete();
        }
        $db->transComplete();

        if ($db->transStatus()) {
            $this->auditAdmin('ADMIN_DELETE_SUBMISSIONS', $id > 0 ? 'exam' : 'submissions', $id > 0 ? $id : null, [
                'description' => $id > 0 ? 'Submissions removed from an exam.' : 'All submissions removed.',
                'metadata' => ['count' => $count],
            ]);
            $uploadRoot = realpath(WRITEPATH . 'uploads/exam');
            foreach ($storedAnswerRows as $storedAnswerRow) {
                $answers = $storedAnswerRow['answers'] ? (json_decode($storedAnswerRow['answers'], true) ?: []) : [];
                foreach ($answers as $answer) {
                    $relativePath = is_array($answer) ? (string) ($answer['file'] ?? '') : '';
                    if (! $uploadRoot || $relativePath === '') {
                        continue;
                    }
                    $filePath = realpath($uploadRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));
                    if ($filePath && str_starts_with($filePath, $uploadRoot . DIRECTORY_SEPARATOR) && is_file($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            if ($id === 0 && $uploadRoot) {
                $fileIterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($uploadRoot, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($fileIterator as $fileInfo) {
                    if ($fileInfo->isFile()) {
                        unlink($fileInfo->getPathname());
                    }
                }
            }
        }

        $message = $id > 0
            ? $count . ' submission(s) removed from ' . $exam['title'] . '.'
            : $count . ' submission(s) removed.';

        return redirect()->to(site_url($id > 0
            ? 'admin/exams?success=' . rawurlencode($message)
            : 'admin/submissions?success=' . rawurlencode($message)));
    }

    public function newExam(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        return view('admin/exam_form', ['error' => null, 'data' => [], 'formAction' => site_url('admin/exams/create'), 'formTitle' => 'Create assessment', 'submitLabel' => 'Create assessment and add questions']);
    }

    public function editExam(int $id): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $exam = db_connect()->table('exams')->where('id', $id)->get()->getRowArray();
        if (! $exam) {
            return redirect()->to('/admin/exams');
        }

        return view('admin/exam_form', [
            'error' => null,
            'data' => [
                'title' => $exam['title'], 'description' => $exam['description'],
                'duration_minutes' => (int) ($exam['duration_seconds'] / 60),
                'start_at' => $exam['start_at'] ? date('Y-m-d\TH:i', strtotime($exam['start_at'])) : '',
                'end_at' => $exam['end_at'] ? date('Y-m-d\TH:i', strtotime($exam['end_at'])) : '',
                'allow_multiple_submissions' => (int) $exam['allow_multiple_submissions'],
                'status' => $exam['status'],
            ],
            'formAction' => site_url('admin/exams/' . $id . '/update'),
            'formTitle' => 'Edit assessment',
            'submitLabel' => 'Save assessment settings',
        ]);
    }

    public function createExam(): ResponseInterface|string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $data = [
            'title' => trim((string) $this->request->getPost('title')),
            'description' => trim((string) $this->request->getPost('description')),
            'duration_minutes' => max(1, (int) $this->request->getPost('duration_minutes')),
            'start_at' => trim((string) $this->request->getPost('start_at')),
            'end_at' => trim((string) $this->request->getPost('end_at')),
            'allow_multiple_submissions' => $this->request->getPost('allow_multiple_submissions') ? 1 : 0,
        ];

        if ($data['title'] === '') {
            return view('admin/exam_form', ['error' => 'Exam title is required.', 'data' => $data, 'formAction' => site_url('admin/exams/create'), 'formTitle' => 'Create assessment', 'submitLabel' => 'Create assessment and add questions']);
        }

        $db = db_connect();
        $db->table('exams')->insert([
            'title' => $data['title'], 'description' => $data['description'] ?: null,
            'duration_seconds' => $data['duration_minutes'] * 60, 'status' => 'draft',
            'start_at' => $data['start_at'] ? date('Y-m-d H:i:s', strtotime($data['start_at'])) : null,
            'end_at' => $data['end_at'] ? date('Y-m-d H:i:s', strtotime($data['end_at'])) : null,
            'allow_multiple_submissions' => $data['allow_multiple_submissions'],
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $examId = (int) $db->insertID();
        $this->auditAdmin('EXAM_CREATED', 'exam', $examId, [
            'description' => 'Assessment created.',
            'newValues' => ['title' => $data['title'], 'status' => 'draft', 'duration_minutes' => $data['duration_minutes']],
        ]);

        return redirect()->to('/admin/exams');
    }

    public function updateExam(int $id): ResponseInterface|string
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $currentExam = db_connect()->table('exams')->where('id', $id)->get()->getRowArray();
        $data = [
            'title' => trim((string) $this->request->getPost('title')),
            'description' => trim((string) $this->request->getPost('description')),
            'duration_minutes' => max(1, (int) $this->request->getPost('duration_minutes')),
            'start_at' => trim((string) $this->request->getPost('start_at')),
            'end_at' => trim((string) $this->request->getPost('end_at')),
            'allow_multiple_submissions' => $this->request->getPost('allow_multiple_submissions') ? 1 : 0,
            'status' => (string) $this->request->getPost('status'),
        ];

        if ($data['title'] === '' || ! in_array($data['status'], ['draft', 'active', 'archived'], true)) {
            return view('admin/exam_form', ['error' => 'Exam name is required.', 'data' => $data, 'formAction' => site_url('admin/exams/' . $id . '/update'), 'formTitle' => 'Edit assessment', 'submitLabel' => 'Save assessment settings']);
        }

        db_connect()->table('exams')->where('id', $id)->update([
            'title' => $data['title'], 'description' => $data['description'] ?: null,
            'duration_seconds' => $data['duration_minutes'] * 60,
            'start_at' => $data['start_at'] ? date('Y-m-d H:i:s', strtotime($data['start_at'])) : null,
            'end_at' => $data['end_at'] ? date('Y-m-d H:i:s', strtotime($data['end_at'])) : null,
            'allow_multiple_submissions' => $data['allow_multiple_submissions'],
            'status' => $data['status'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        db_connect()->table('users')
            ->where(['assigned_exam_id' => $id, 'usertype' => 'applicant'])
            ->update(['position' => $data['title'], 'updated_at' => date('Y-m-d H:i:s')]);
        $this->auditAdmin($currentExam && $currentExam['status'] !== $data['status'] ? 'ADMIN_STATUS_CHANGE' : 'EXAM_UPDATED', 'exam', $id, [
            'description' => 'Assessment settings updated.',
            'oldValues' => $currentExam ? ['title' => $currentExam['title'], 'status' => $currentExam['status'], 'duration_seconds' => $currentExam['duration_seconds']] : null,
            'newValues' => ['title' => $data['title'], 'status' => $data['status'], 'duration_seconds' => $data['duration_minutes'] * 60],
        ]);

        return redirect()->to('/admin/exams');
    }

    public function examQuestions(int $examId): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $exam = db_connect()->table('exams')->where('id', $examId)->get()->getRowArray();
        if (! $exam) {
            return redirect()->to('/admin/exams');
        }

        $questions = db_connect()->table('questions')->where('exam_id', $examId)->orderBy('id', 'ASC')->get()->getResultArray();
        foreach ($questions as &$question) {
            $question['options'] = $question['options'] ? json_decode($question['options'], true) : [];
            $question['attachments'] = db_connect()->table('question_attachments')->where('question_id', $question['id'])->orderBy('id', 'ASC')->get()->getResultArray();
        }

        return view('admin/exam_questions', ['exam' => $exam, 'questions' => $questions]);
    }

    private function normaliseAllowedFileTypes(mixed $value, string $questionType): array
    {
        if ($questionType !== 'upload') {
            return [];
        }

        $values = is_array($value) ? $value : [$value];
        $values = array_values(array_intersect(['pdf', 'excel', 'all'], array_map('strtolower', array_map('strval', $values))));
        if (in_array('all', $values, true)) {
            return ['all'];
        }

        return $values ?: ['pdf'];
    }

    public function createQuestion(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $exams = db_connect()->table('exams')->orderBy('id', 'DESC')->get()->getResultArray();

        $data = [
            'exam_id' => (int) $this->request->getPost('exam_id'),
            'type' => (string) $this->request->getPost('type'),
            'prompt' => trim((string) $this->request->getPost('prompt')),
            'typing_answer' => (string) $this->request->getPost('typing_answer'),
            'hint' => trim((string) $this->request->getPost('hint')),
            'points' => max(1, (int) $this->request->getPost('points')),
            'options' => $this->request->getPost('options'),
            'allowed_file_types' => $this->normaliseAllowedFileTypes($this->request->getPost('allowed_file_types'), (string) $this->request->getPost('type')),
        ];

        $validTypes = ['single', 'multi', 'bool', 'written', 'typing', 'upload'];
        if (! in_array($data['type'], $validTypes, true) || $data['prompt'] === '') {
            return view('admin/question_form', ['error' => 'Choose a valid type and enter a question prompt.', 'data' => $data, 'exams' => $exams]);
        }
        if ($data['type'] === 'typing' && trim($data['typing_answer']) === '') {
            return view('admin/question_form', ['error' => 'Enter the reference text for this typing test.', 'data' => $data, 'exams' => $exams]);
        }

        $options = null;
        if (in_array($data['type'], ['single', 'multi', 'bool'], true)) {
            $rows = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $data['options']))));
            if (count($rows) < 2) {
                return view('admin/question_form', ['error' => 'Choice-based questions need at least two options.', 'data' => $data, 'exams' => $exams]);
            }
            $options = [];
            foreach ($rows as $index => $text) {
                $options[] = ['key' => chr(65 + $index), 'text' => $text];
            }
        }

        $attachments = [];
        $attachmentNames = $this->request->getPost('attachment_names') ?? [];
        $attachmentNames = is_array($attachmentNames) ? $attachmentNames : [];
        foreach ($this->request->getFileMultiple('attachments') ?? [] as $attachment) {
            if ($attachment->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if (! $attachment->isValid()) {
                return view('admin/question_form', ['error' => 'One of the attachments could not be uploaded.', 'data' => $data, 'exams' => $exams]);
            }
            if ($attachment->getSize() > 10 * 1024 * 1024) {
                return view('admin/question_form', ['error' => 'Each attachment must be 10 MB or smaller.', 'data' => $data, 'exams' => $exams]);
            }
            $attachments[] = $attachment;
        }

        db_connect()->table('questions')->insert([
            'exam_id' => $data['exam_id'] ?: null,
            'type' => $data['type'],
            'allowed_file_types' => $data['type'] === 'upload' ? json_encode($data['allowed_file_types']) : null,
            'prompt' => $data['prompt'],
            'typing_answer' => $data['type'] === 'typing' ? $data['typing_answer'] : null,
            'hint' => $data['hint'] ?: null,
            'points' => $data['points'],
            'options' => $options ? json_encode($options) : null,
            'attachment_name' => null,
            'attachment_path' => null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $questionId = db_connect()->insertID();
        if ($attachments) {
            $targetDir = WRITEPATH . 'uploads/questions';
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            foreach ($attachments as $index => $attachment) {
                $storedName = $attachment->getRandomName();
                $attachment->move($targetDir, $storedName);
                $displayName = trim((string) ($attachmentNames[$index] ?? ''));
                if ($displayName !== '') {
                    $displayName = basename(str_replace('\\', '/', $displayName));
                    $displayName = preg_replace('/[\\x00-\\x1F\\x7F]/u', '', $displayName) ?: '';
                }
                db_connect()->table('question_attachments')->insert([
                    'question_id' => $questionId,
                    'original_name' => mb_substr($displayName ?: $attachment->getClientName(), 0, 255),
                    'stored_name' => $storedName,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->auditAdmin('QUESTION_CREATED', 'question', $questionId, [
            'description' => 'Question created.',
            'newValues' => ['exam_id' => $data['exam_id'], 'type' => $data['type'], 'points' => $data['points']],
        ]);

        return redirect()->to('/admin/questions');
    }

    public function newQuestion(?int $examId = null): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $examId = $examId ?: (int) $this->request->getGet('exam_id');
        $exams = db_connect()->table('exams')->orderBy('id', 'DESC')->get()->getResultArray();
        return view('admin/question_form', ['error' => null, 'data' => ['exam_id' => $examId, 'allowed_file_types' => ['pdf'], 'typing_answer' => ''], 'exams' => $exams]);
    }

    public function editQuestion(int $id): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $question = $db->table('questions')->where('id', $id)->get()->getRowArray();
        if (! $question) {
            return redirect()->to('/admin/questions');
        }

        $options = $question['options'] ? json_decode($question['options'], true) : [];
        $optionText = implode("\n", array_map(static fn (array $option): string => (string) ($option['text'] ?? ''), is_array($options) ? $options : []));
        $allowedFileTypes = $question['allowed_file_types'] ? json_decode($question['allowed_file_types'], true) : ['pdf'];
        $allowedFileTypes = is_array($allowedFileTypes) && $allowedFileTypes ? $allowedFileTypes : ['pdf'];
        return view('admin/question_form', [
            'error' => null,
            'data' => [
                'exam_id' => (int) $question['exam_id'],
                'type' => $question['type'],
                'allowed_file_types' => $allowedFileTypes,
                'prompt' => $question['prompt'],
                'typing_answer' => $question['typing_answer'] ?? '',
                'hint' => $question['hint'],
                'points' => $question['points'],
                'options' => $optionText,
            ],
            'exams' => $db->table('exams')->orderBy('id', 'DESC')->get()->getResultArray(),
            'questionId' => $id,
            'existingAttachments' => $db->table('question_attachments')->where('question_id', $id)->orderBy('id', 'ASC')->get()->getResultArray(),
            'formAction' => site_url('admin/questions/' . $id . '/update'),
            'formTitle' => 'Edit question',
            'submitLabel' => 'Save question',
        ]);
    }

    public function updateQuestion(int $id): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $question = $db->table('questions')->where('id', $id)->get()->getRowArray();
        $exams = $db->table('exams')->orderBy('id', 'DESC')->get()->getResultArray();
        $existingAttachments = $db->table('question_attachments')->where('question_id', $id)->orderBy('id', 'ASC')->get()->getResultArray();
        if (! $question) {
            return redirect()->to('/admin/questions');
        }

        $data = [
            'exam_id' => (int) $this->request->getPost('exam_id'),
            'type' => (string) $this->request->getPost('type'),
            'prompt' => trim((string) $this->request->getPost('prompt')),
            'typing_answer' => (string) $this->request->getPost('typing_answer'),
            'hint' => trim((string) $this->request->getPost('hint')),
            'points' => max(1, (int) $this->request->getPost('points')),
            'options' => (string) $this->request->getPost('options'),
            'allowed_file_types' => $this->normaliseAllowedFileTypes($this->request->getPost('allowed_file_types'), (string) $this->request->getPost('type')),
        ];
        $formData = [
            'error' => null,
            'data' => $data,
            'exams' => $exams,
            'questionId' => $id,
            'existingAttachments' => $existingAttachments,
            'formAction' => site_url('admin/questions/' . $id . '/update'),
            'formTitle' => 'Edit question',
            'submitLabel' => 'Save question',
        ];

        $validTypes = ['single', 'multi', 'bool', 'written', 'typing', 'upload'];
        if (! in_array($data['type'], $validTypes, true) || $data['prompt'] === '') {
            $formData['error'] = 'Choose a valid type and enter a question prompt.';
            return view('admin/question_form', $formData);
        }
        if ($data['type'] === 'typing' && trim($data['typing_answer']) === '') {
            $formData['error'] = 'Enter the reference text for this typing test.';
            return view('admin/question_form', $formData);
        }

        $options = null;
        if (in_array($data['type'], ['single', 'multi', 'bool'], true)) {
            $rows = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['options']))));
            if (count($rows) < 2) {
                $formData['error'] = 'Choice-based questions need at least two options.';
                return view('admin/question_form', $formData);
            }
            $options = [];
            foreach ($rows as $index => $text) {
                $options[] = ['key' => chr(65 + $index), 'text' => $text];
            }
        }

        $attachments = [];
        $attachmentNames = $this->request->getPost('attachment_names') ?? [];
        $attachmentNames = is_array($attachmentNames) ? $attachmentNames : [];
        foreach ($this->request->getFileMultiple('attachments') ?? [] as $attachment) {
            if ($attachment->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if (! $attachment->isValid()) {
                $formData['error'] = 'One of the attachments could not be uploaded.';
                return view('admin/question_form', $formData);
            }
            if ($attachment->getSize() > 10 * 1024 * 1024) {
                $formData['error'] = 'Each attachment must be 10 MB or smaller.';
                return view('admin/question_form', $formData);
            }
            $attachments[] = $attachment;
        }

        $now = date('Y-m-d H:i:s');
        $db->table('questions')->where('id', $id)->update([
            'exam_id' => $data['exam_id'] ?: null,
            'type' => $data['type'],
            'allowed_file_types' => $data['type'] === 'upload' ? json_encode($data['allowed_file_types']) : null,
            'prompt' => $data['prompt'],
            'typing_answer' => $data['type'] === 'typing' ? $data['typing_answer'] : null,
            'hint' => $data['hint'] ?: null,
            'points' => $data['points'],
            'options' => $options ? json_encode($options) : null,
            'updated_at' => $now,
        ]);

        if ($attachments) {
            $targetDir = WRITEPATH . 'uploads/questions';
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            foreach ($attachments as $index => $attachment) {
                $storedName = $attachment->getRandomName();
                $attachment->move($targetDir, $storedName);
                $displayName = trim((string) ($attachmentNames[$index] ?? ''));
                if ($displayName !== '') {
                    $displayName = basename(str_replace('\\', '/', $displayName));
                    $displayName = preg_replace('/[\x00-\x1F\x7F]/u', '', $displayName) ?: '';
                }
                $db->table('question_attachments')->insert([
                    'question_id' => $id,
                    'original_name' => mb_substr($displayName ?: $attachment->getClientName(), 0, 255),
                    'stored_name' => $storedName,
                    'created_at' => $now,
                ]);
            }
        }

        $this->auditAdmin('QUESTION_UPDATED', 'question', $id, [
            'description' => 'Question updated.',
            'newValues' => ['exam_id' => $data['exam_id'], 'type' => $data['type'], 'points' => $data['points']],
        ]);

        return redirect()->to('/admin/questions');
    }

    public function deleteQuestion(int $id): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $question = $db->table('questions')->where('id', $id)->get()->getRowArray();
        $db->table('questions')->where('id', $id)->delete();
        if ($question) {
            $this->auditAdmin('QUESTION_DELETED', 'question', $id, [
                'description' => 'Question deleted.',
                'oldValues' => ['exam_id' => $question['exam_id'], 'type' => $question['type'], 'points' => $question['points']],
            ]);
        }

        return redirect()->to('/admin/questions');
    }

    public function questionAttachment(int $id, ?int $attachmentId = null): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $question = $db->table('questions')->where('id', $id)->get()->getRowArray();
        $attachment = $attachmentId ? $db->table('question_attachments')->where(['id' => $attachmentId, 'question_id' => $id])->get()->getRowArray() : null;
        $storedName = $attachment['stored_name'] ?? ($question['attachment_path'] ?? null);
        $originalName = $attachment['original_name'] ?? ($question['attachment_name'] ?? null);
        $path = $storedName ? WRITEPATH . 'uploads/questions/' . $storedName : '';
        if (! $question || ! $storedName || ! is_file($path)) {
            return $this->response->setStatusCode(404)->setBody('Attachment not found.');
        }

        $originalName = trim((string) $originalName);
        $extension = pathinfo($storedName, PATHINFO_EXTENSION);
        if ($extension !== '' && pathinfo($originalName, PATHINFO_EXTENSION) === '') {
            $originalName = rtrim($originalName, '. ') . '.' . $extension;
        }

        return $this->response->download($path, null)->setFileName($originalName);
    }

    public function applicants(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $applicants = db_connect()->table('users')
            ->select('id, full_name, applicant_code, position, assigned_exam_id, username, created_at')
            ->where('usertype', 'applicant')
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        return view('admin/applicants', ['applicants' => $applicants]);
    }

    public function downloadAllApplicantCredentials(): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $applicants = $db->table('users')
            ->where('usertype', 'applicant')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        if (! $applicants) {
            return redirect()->to('/admin/applicants?error=' . rawurlencode('No applicants are available for credential download.'));
        }

        $records = [];
        $now = date('Y-m-d H:i:s');
        try {
            $db->transBegin();
            foreach ($applicants as $applicant) {
                $password = $this->generateApplicantPassword();
                $updated = $db->table('users')->where(['id' => $applicant['id'], 'usertype' => 'applicant'])->update([
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'credential_password' => base64_encode(service('encrypter')->encrypt($password)),
                    'updated_at' => $now,
                ]);
                if (! $updated) {
                    throw new \RuntimeException('An applicant credential could not be updated.');
                }
                $records[] = [
                    'name' => $applicant['full_name'],
                    'applicantId' => $applicant['applicant_code'],
                    'position' => $applicant['position'],
                    'siteUrl' => rtrim(site_url(), '/') . '/',
                    'username' => $applicant['username'] ?: $applicant['applicant_code'],
                    'password' => $password,
                ];
            }
            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Applicant credential regeneration failed: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to('/admin/applicants?error=' . rawurlencode('Applicant credentials could not be generated. No credentials were downloaded.'));
        }

        $this->auditAdmin('APPLICANT_CREDENTIALS_REGENERATED', 'applicants', null, [
            'description' => 'Regenerated credentials for all applicants.',
            'newValues' => ['applicant_count' => count($records)],
        ]);
        $pdf = CredentialPdf::makeBatch($records);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="applicant-credentials-all.pdf"')
            ->setHeader('Content-Length', (string) strlen($pdf))
            ->setBody($pdf);
    }

    private function generateApplicantPassword(): string
    {
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digits = '23456789';
        $alphabet = $letters . $digits;
        $characters = [
            $letters[random_int(0, strlen($letters) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];
        for ($index = 2; $index < 6; $index++) {
            $characters[] = $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        for ($index = count($characters) - 1; $index > 0; $index--) {
            $swapIndex = random_int(0, $index);
            [$characters[$index], $characters[$swapIndex]] = [$characters[$swapIndex], $characters[$index]];
        }

        return implode('', $characters);
    }

    public function newApplicant(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $exams = db_connect()->table('exams')->where('status !=', 'archived')->orderBy('title', 'ASC')->get()->getResultArray();
        return view('admin/applicant_form', ['error' => null, 'data' => [], 'exams' => $exams, 'formAction' => site_url('admin/applicants/create'), 'formTitle' => 'Add applicant', 'submitLabel' => 'Create applicant']);
    }

    public function editApplicant(int $id): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $applicant = db_connect()->table('users')->where(['id' => $id, 'usertype' => 'applicant'])->get()->getRowArray();
        if (! $applicant) {
            return redirect()->to('/admin/applicants');
        }

        $exams = db_connect()->table('exams')->where('status !=', 'archived')->orderBy('title', 'ASC')->get()->getResultArray();
        return view('admin/applicant_form', [
            'error' => null,
            'data' => ['full_name' => $applicant['full_name'], 'applicant_code' => $applicant['applicant_code'], 'position' => $applicant['position'], 'assigned_exam_id' => $applicant['assigned_exam_id']],
            'exams' => $exams,
            'formAction' => site_url('admin/applicants/' . $id . '/update'),
            'formTitle' => 'Edit applicant',
            'submitLabel' => 'Save applicant',
        ]);
    }

    public function createApplicant(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $exams = db_connect()->table('exams')->where('status !=', 'archived')->orderBy('title', 'ASC')->get()->getResultArray();

        $data = [
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'applicant_code' => trim((string) $this->request->getPost('applicant_code')),
            'password' => (string) $this->request->getPost('password'),
            'assigned_exam_id' => (int) $this->request->getPost('assigned_exam_id'),
        ];
        $assignedExam = db_connect()->table('exams')->where('id', $data['assigned_exam_id'])->where('status !=', 'archived')->get()->getRowArray();

        if ($data['full_name'] === '' || $data['applicant_code'] === '' || $data['password'] === '' || ! $assignedExam) {
            return view('admin/applicant_form', ['error' => 'Name, applicant ID, password, and position are required.', 'data' => $data, 'exams' => $exams, 'formAction' => site_url('admin/applicants/create'), 'formTitle' => 'Add applicant', 'submitLabel' => 'Create applicant']);
        }

        if (db_connect()->table('users')->where('applicant_code', $data['applicant_code'])->countAllResults()) {
            return view('admin/applicant_form', ['error' => 'That applicant ID is already in use.', 'data' => $data, 'exams' => $exams, 'formAction' => site_url('admin/applicants/create'), 'formTitle' => 'Add applicant', 'submitLabel' => 'Create applicant']);
        }

        $db = db_connect();
        $db->table('users')->insert([
            'username' => $data['applicant_code'], 'full_name' => $data['full_name'],
            'applicant_code' => $data['applicant_code'], 'position' => $assignedExam['title'], 'assigned_exam_id' => (int) $assignedExam['id'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT), 'credential_password' => base64_encode(service('encrypter')->encrypt($data['password'])), 'usertype' => 'applicant',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $applicantId = (int) $db->insertID();
        $this->auditAdmin('APPLICANT_CREATED', 'applicant', $applicantId, [
            'description' => 'Applicant account created.',
            'newValues' => ['applicant_code' => $data['applicant_code'], 'assigned_exam_id' => (int) $assignedExam['id']],
        ]);

        return redirect()->to('/admin/applicants');
    }

    public function updateApplicant(int $id): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $exams = $db->table('exams')->where('status !=', 'archived')->orderBy('title', 'ASC')->get()->getResultArray();
        $currentApplicant = $db->table('users')->where(['id' => $id, 'usertype' => 'applicant'])->get()->getRowArray();
        if (! $currentApplicant) {
            return redirect()->to('/admin/applicants');
        }

        $data = [
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'applicant_code' => trim((string) $this->request->getPost('applicant_code')),
            'password' => (string) $this->request->getPost('password'),
            'assigned_exam_id' => (int) $this->request->getPost('assigned_exam_id'),
        ];
        $assignedExam = $db->table('exams')->where('id', $data['assigned_exam_id'])->where('status !=', 'archived')->get()->getRowArray();
        $form = ['data' => $data, 'exams' => $exams, 'formAction' => site_url('admin/applicants/' . $id . '/update'), 'formTitle' => 'Edit applicant', 'submitLabel' => 'Save applicant'];
        if ($data['full_name'] === '' || $data['applicant_code'] === '' || ! $assignedExam) {
            return view('admin/applicant_form', ['error' => 'Name, applicant ID, and position are required.'] + $form);
        }
        $duplicate = $db->table('users')->where('applicant_code', $data['applicant_code'])->where('id !=', $id)->countAllResults();
        if ($duplicate) {
            return view('admin/applicant_form', ['error' => 'That applicant ID is already in use.'] + $form);
        }
        $update = ['full_name' => $data['full_name'], 'applicant_code' => $data['applicant_code'], 'position' => $assignedExam['title'], 'assigned_exam_id' => (int) $assignedExam['id'], 'username' => $data['applicant_code'], 'updated_at' => date('Y-m-d H:i:s')];
        if ($data['password'] !== '') {
            $update['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $update['credential_password'] = base64_encode(service('encrypter')->encrypt($data['password']));
        }
        $db->transStart();
        $db->table('users')->where(['id' => $id, 'usertype' => 'applicant'])->update($update);
        if ($currentApplicant['applicant_code'] !== $data['applicant_code']) {
            $db->table('submissions')->where('applicant_id', $currentApplicant['applicant_code'])->update(['applicant_id' => $data['applicant_code']]);
            $db->table('exam_attempts')->where('applicant_id', $currentApplicant['applicant_code'])->update(['applicant_id' => $data['applicant_code']]);
        }
        $db->transComplete();
        if ($db->transStatus()) {
            $this->auditAdmin('APPLICANT_UPDATED', 'applicant', $id, [
                'description' => 'Applicant account updated.',
                'oldValues' => ['applicant_code' => $currentApplicant['applicant_code'], 'assigned_exam_id' => $currentApplicant['assigned_exam_id']],
                'newValues' => ['applicant_code' => $data['applicant_code'], 'assigned_exam_id' => $data['assigned_exam_id']],
            ]);
        }
        return redirect()->to('/admin/applicants');
    }

    public function deleteApplicant(int $id): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }
        if (db_connect()->table('submissions')->where('applicant_id', db_connect()->table('users')->where('id', $id)->get()->getRow('applicant_code'))->countAllResults()) {
            return redirect()->to('/admin/applicants?error=' . rawurlencode('This applicant cannot be deleted because submissions exist.'));
        }
        $applicantCode = db_connect()->table('users')->where('id', $id)->get()->getRow('applicant_code');
        if ($applicantCode) {
            db_connect()->table('exam_attempts')->where('applicant_id', $applicantCode)->delete();
        }
        db_connect()->table('users')->where(['id' => $id, 'usertype' => 'applicant'])->delete();
        $this->auditAdmin('APPLICANT_DELETED', 'applicant', $id, [
            'description' => 'Applicant account deleted.',
            'oldValues' => ['applicant_code' => $applicantCode],
        ]);
        return redirect()->to('/admin/applicants');
    }

    public function bulkApplicantForm(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }
        $exams = db_connect()->table('exams')->where('status !=', 'archived')->orderBy('title', 'ASC')->get()->getResultArray();
        return view('admin/applicant_bulk_upload', ['exams' => $exams, 'error' => null, 'success' => null, 'imported' => 0]);
    }

    public function bulkApplicantUpload(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }
        $exams = db_connect()->table('exams')->where('status !=', 'archived')->orderBy('title', 'ASC')->get()->getResultArray();
        $examId = (int) $this->request->getPost('exam_id');
        $exam = db_connect()->table('exams')->where('id', $examId)->get()->getRowArray();
        $file = $this->request->getFile('applicant_file');
        if (! $exam || ! $file || ! $file->isValid()) {
            return view('admin/applicant_bulk_upload', ['exams' => $exams, 'error' => 'Select an available exam and a valid CSV file.', 'success' => null, 'imported' => 0]);
        }
        $handle = fopen($file->getTempName(), 'rb');
        $headers = $handle ? array_map(static fn ($header) => strtolower(trim((string) $header)), fgetcsv($handle) ?: []) : [];
        $required = ['name', 'applicant_id', 'password'];
        if (array_diff($required, $headers)) {
            if (is_resource($handle)) fclose($handle);
            return view('admin/applicant_bulk_upload', ['exams' => $exams, 'error' => 'The CSV must contain: name, applicant_id, password.', 'success' => null, 'imported' => 0]);
        }
        $db = db_connect();
        $rows = [];
        $errors = [];
        $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($values, static fn ($value) => trim((string) $value) !== '')) === 0) continue;
            $row = array_combine($headers, array_pad($values, count($headers), ''));
            $name = trim((string) ($row['name'] ?? ''));
            $applicantId = trim((string) ($row['applicant_id'] ?? ''));
            $password = (string) ($row['password'] ?? '');
            if ($name === '' || $applicantId === '' || $password === '') {
                $errors[] = "Row {$line}: name, applicant_id, and password are required.";
                continue;
            }
            if (isset($rows[$applicantId]) || $db->table('users')->where('applicant_code', $applicantId)->countAllResults()) {
                $errors[] = "Row {$line}: applicant ID {$applicantId} already exists.";
                continue;
            }
            $rows[$applicantId] = ['username' => $applicantId, 'full_name' => $name, 'applicant_code' => $applicantId, 'position' => $exam['title'], 'assigned_exam_id' => (int) $exam['id'], 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'credential_password' => base64_encode(service('encrypter')->encrypt($password)), 'usertype' => 'applicant', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        }
        if (is_resource($handle)) fclose($handle);
        if ($errors) {
            return view('admin/applicant_bulk_upload', ['exams' => $exams, 'error' => implode(' ', $errors), 'success' => null, 'imported' => 0]);
        }
        if ($rows) $db->table('users')->insertBatch(array_values($rows));
        return view('admin/applicant_bulk_upload', ['exams' => $exams, 'error' => null, 'success' => count($rows) . ' applicant(s) imported and assigned to ' . $exam['title'] . '.', 'imported' => count($rows)]);
    }

    public function applicantTemplate(): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) return $redirect;
        $path = FCPATH . 'downloads/applicant_import_template.csv';
        return $this->response->download($path, null)->setFileName('applicant_import_template.csv');
    }

    private function auditQuery(array $filters)
    {
        $query = db_connect()->table('audit_logs a')
            ->select('a.*, u.full_name AS actor_name, u.username AS actor_username')
            ->join('users u', 'u.id = a.user_id', 'left');
        if ($filters['date_from'] !== '') {
            $query->where('a.created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        if ($filters['date_to'] !== '') {
            $query->where('a.created_at <=', $filters['date_to'] . ' 23:59:59');
        }
        if ($filters['event'] !== '') {
            $query->where('a.event', $filters['event']);
        }
        if ($filters['user'] !== '') {
            $query->groupStart()
                ->like('a.user_id', $filters['user'])
                ->orLike('u.full_name', $filters['user'])
                ->orLike('u.username', $filters['user'])
                ->groupEnd();
        }
        if ($filters['reference'] !== '') {
            $query->groupStart()
                ->like('a.entity_id', $filters['reference'])
                ->orLike('a.metadata', $filters['reference'])
                ->groupEnd();
        }
        if ($filters['ip'] !== '') {
            $query->like('a.ip_address', $filters['ip']);
        }
        if ($filters['device'] !== '') {
            $query->where('a.device_type', $filters['device']);
        }
        if ($filters['browser'] !== '') {
            $query->like('a.browser', $filters['browser']);
        }

        return $query;
    }

    public function auditLogs(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $filters = [
            'date_from' => trim((string) $this->request->getGet('date_from')),
            'date_to' => trim((string) $this->request->getGet('date_to')),
            'event' => trim((string) $this->request->getGet('event')),
            'user' => trim((string) $this->request->getGet('user')),
            'reference' => trim((string) $this->request->getGet('reference')),
            'ip' => trim((string) $this->request->getGet('ip')),
            'device' => trim((string) $this->request->getGet('device')),
            'browser' => trim((string) $this->request->getGet('browser')),
        ];
        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = 25;
        $total = $this->auditQuery($filters)->countAllResults();
        $logs = $this->auditQuery($filters)->orderBy('a.id', 'DESC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        foreach ($logs as &$log) {
            $metadata = $log['metadata'] ? (json_decode($log['metadata'], true) ?: []) : [];
            $log['reference'] = $metadata['reference'] ?? $log['entity_id'];
        }
        unset($log);

        $events = db_connect()->table('audit_logs')->select('event')->distinct()->orderBy('event', 'ASC')->get()->getResultArray();

        return view('admin/audit_logs', [
            'logs' => $logs,
            'filters' => $filters,
            'events' => $events,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function loginHistory(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));
        $user = trim((string) $this->request->getGet('user'));
        $status = trim((string) $this->request->getGet('status'));
        $ip = trim((string) $this->request->getGet('ip'));
        $device = trim((string) $this->request->getGet('device'));
        $browser = trim((string) $this->request->getGet('browser'));
        $makeQuery = static function () use ($dateFrom, $dateTo, $user, $status, $ip, $device, $browser) {
            $query = db_connect()->table('login_logs l')
                ->select('l.*, u.full_name AS user_name, u.username')
                ->join('users u', 'u.id = l.user_id', 'left');
            if ($dateFrom !== '') $query->where('l.created_at >=', $dateFrom . ' 00:00:00');
            if ($dateTo !== '') $query->where('l.created_at <=', $dateTo . ' 23:59:59');
            if ($user !== '') $query->groupStart()->like('l.login_identifier', $user)->orLike('u.full_name', $user)->orLike('u.username', $user)->groupEnd();
            if ($status !== '') $query->where('l.status', $status);
            if ($ip !== '') $query->like('l.ip_address', $ip);
            if ($device !== '') $query->where('l.device_type', $device);
            if ($browser !== '') $query->like('l.browser', $browser);
            return $query;
        };
        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = 25;
        $total = $makeQuery()->countAllResults();
        $logs = $makeQuery()->orderBy('l.id', 'DESC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return view('admin/login_history', [
            'logs' => $logs,
            'filters' => compact('dateFrom', 'dateTo', 'user', 'status', 'ip', 'device', 'browser'),
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function submissions(): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $applicantFilter = trim((string) ($this->request->getGet('applicant') ?: $this->request->getGet('applicant_filter')));
        $examFilter = (int) $this->request->getGet('exam_id');
        $submissionQuery = $db->table('submissions s')
            ->select('s.*, u.full_name AS applicant_name, u.position AS applicant_position, e.title AS exam_title')
            ->join(
                '(SELECT MAX(id) AS id FROM submissions GROUP BY exam_id, applicant_id) latest_submission',
                'latest_submission.id = s.id',
                'inner',
                false
            )
            ->join('users u', "u.applicant_code = s.applicant_id AND u.usertype = 'applicant'", 'left')
            ->join('exams e', 'e.id = s.exam_id', 'left')
            ->orderBy('s.id', 'DESC');
        if ($applicantFilter !== '') {
            $submissionQuery->groupStart()
                ->like('s.applicant_id', $applicantFilter)
                ->orLike('u.full_name', $applicantFilter)
                ->groupEnd();
        }
        if ($examFilter > 0) {
            $submissionQuery->where('s.exam_id', $examFilter);
        }
        $submissions = $submissionQuery->get()->getResultArray();
        foreach ($submissions as &$submission) {
            $submission['answers'] = $submission['answers'] ? json_decode($submission['answers'], true) : [];
        }
        unset($submission);

        $applicants = $db->table('users')->select('applicant_code, full_name')->where('usertype', 'applicant')->orderBy('full_name', 'ASC')->get()->getResultArray();

        $exams = $db->table('exams')->orderBy('title', 'ASC')->get()->getResultArray();

        return view('admin/submissions', ['submissions' => $submissions, 'applicants' => $applicants, 'exams' => $exams, 'applicantFilter' => $applicantFilter, 'examFilter' => $examFilter, 'success' => $this->request->getGet('success')]);
    }

    public function exportSubmissionMarks(): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $applicantFilter = trim((string) ($this->request->getGet('applicant') ?: $this->request->getGet('applicant_filter')));
        $examFilter = (int) $this->request->getGet('exam_id');
        $submissionQuery = $db->table('submissions s')
            ->select('s.applicant_id, s.answered_count, s.time_used, s.score, u.full_name AS applicant_name')
            ->join(
                '(SELECT MAX(id) AS id FROM submissions GROUP BY exam_id, applicant_id) latest_submission',
                'latest_submission.id = s.id',
                'inner',
                false
            )
            ->join('users u', "u.applicant_code = s.applicant_id AND u.usertype = 'applicant'", 'left')
            ->orderBy('s.id', 'DESC');
        if ($applicantFilter !== '') {
            $submissionQuery->groupStart()
                ->like('s.applicant_id', $applicantFilter)
                ->orLike('u.full_name', $applicantFilter)
                ->groupEnd();
        }
        if ($examFilter > 0) {
            $submissionQuery->where('s.exam_id', $examFilter);
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Applicant ID', 'Applicant Name', 'Answered', 'Time used', 'Marks']);
        foreach ($submissionQuery->get()->getResultArray() as $submission) {
            fputcsv($handle, [
                $submission['applicant_id'],
                $submission['applicant_name'] ?: 'Unknown applicant',
                $submission['answered_count'],
                sprintf('%02d:%02d', intdiv((int) $submission['time_used'], 60), (int) $submission['time_used'] % 60),
                $submission['score'] !== null ? $submission['score'] : 'Not marked',
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        $fileName = 'submission_marks_' . date('Y-m-d_H-i-s') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    public function markSubmission(int $id): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $rawMarks = $this->request->getPost('marks') ?? [];
        $rawMarks = is_array($rawMarks) ? $rawMarks : [];
        $notes = trim((string) $this->request->getPost('marker_notes'));

        $submission = $db->table('submissions')->where('id', $id)->get()->getRowArray();
        if (! $submission) {
            return redirect()->to(site_url('admin/submissions'));
        }
        if ($submission && $submission['exam_id'] !== null && $submission['applicant_id'] !== null) {
            $latestId = $db->table('submissions')
                ->select('id')
                ->where(['exam_id' => $submission['exam_id'], 'applicant_id' => $submission['applicant_id']])
                ->orderBy('id', 'DESC')
                ->get()->getRow('id');
            if ($latestId) {
                $id = (int) $latestId;
            }
        }

        $submission = $db->table('submissions')->where('id', $id)->get()->getRowArray() ?: $submission;
        $questions = $db->table('questions')
            ->where(['exam_id' => $submission['exam_id'], 'is_active' => 1])
            ->orderBy('id', 'ASC')->get()->getResultArray();
        $verificationByQuestion = [];
        foreach ($db->table('submission_question_marks')->where('submission_id', $id)->get()->getResultArray() as $verification) {
            $verificationByQuestion[(int) $verification['question_id']] = $verification;
        }
        $marks = [];
        $score = 0.0;
        $maxScore = 0.0;
        foreach ($questions as $question) {
            $questionId = (string) $question['id'];
            $rawValue = array_key_exists($questionId, $rawMarks) ? $rawMarks[$questionId] : 0;
            $rawValue = trim((string) $rawValue);
            $maxMarks = (float) $question['points'];
            $maxScore += $maxMarks;
            if ($rawValue === '') {
                $value = 0.0;
            } elseif (! is_numeric($rawValue)) {
                session()->setFlashdata('mark_values', $rawMarks);
                return redirect()->to(site_url('admin/submissions/' . $id . '?error=' . rawurlencode('Each obtained mark must be a valid number.')));
            } else {
                $value = round((float) $rawValue, 2);
            }
            if ($value < 0 || $value > $maxMarks) {
                session()->setFlashdata('mark_values', $rawMarks);
                return redirect()->to(site_url('admin/submissions/' . $id . '?error=' . rawurlencode('Obtained marks must be between 0 and the question maximum.')));
            }
            $marks[(int) $question['id']] = $value;
            $score += $value;
        }

        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('submission_question_marks')->where('submission_id', $id)->delete();
        if ($marks) {
            $markRows = [];
            foreach ($marks as $questionId => $value) {
                $markRows[] = [
                    'submission_id' => $id,
                    'question_id' => $questionId,
                    'marks' => $value,
                    'similarity_percent' => $verificationByQuestion[$questionId]['similarity_percent'] ?? null,
                    'verification_status' => $verificationByQuestion[$questionId]['verification_status'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $db->table('submission_question_marks')->insertBatch($markRows);
        }
        $db->table('submissions')->where('id', $id)->update([
            'score' => $score,
            'max_score' => $maxScore,
            'marker_notes' => $notes ?: null,
            'status' => 'marked',
            'updated_at' => $now,
        ]);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to(site_url('admin/submissions/' . $id . '?error=' . rawurlencode('Marks could not be saved. Please try again.')));
        }

        $this->auditAdmin('REVIEW_SUBMITTED', 'submission', $id, [
            'description' => 'Reviewer marks saved.',
            'newValues' => ['score' => $score, 'max_score' => $maxScore, 'status' => 'marked'],
        ]);

        return redirect()->to(site_url('admin/submissions/' . $id . '?success=' . rawurlencode('Question marks saved. Overall score: ' . rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.') . ' / ' . rtrim(rtrim(number_format($maxScore, 2, '.', ''), '0'), '.') . '.')));
    }

    public function submissionDetail(int $id): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $submission = $db->table('submissions s')
            ->select('s.*, e.title AS exam_title')
            ->join('exams e', 'e.id = s.exam_id', 'left')
            ->where('s.id', $id)->get()->getRowArray();
        if (! $submission) {
            return redirect()->to('/admin/submissions');
        }

        if ($submission['exam_id'] !== null && $submission['applicant_id'] !== null) {
            $latestId = $db->table('submissions')
                ->select('id')
                ->where(['exam_id' => $submission['exam_id'], 'applicant_id' => $submission['applicant_id']])
                ->orderBy('id', 'DESC')
                ->get()->getRow('id');
            if ($latestId && (int) $latestId !== $id) {
                return redirect()->to(site_url('admin/submissions/' . $latestId));
            }
        }

        $answers = $submission['answers'] ? json_decode($submission['answers'], true) : [];
        $questions = $db->table('questions')->where(['exam_id' => $submission['exam_id'], 'is_active' => 1])->orderBy('id', 'ASC')->get()->getResultArray();
        foreach ($questions as &$question) {
            $question['answer_key'] = 'q' . $question['id'];
            $question['answer'] = $answers[$question['answer_key']] ?? null;
        }
        unset($question);

        $savedMarks = [];
        $savedVerification = [];
        foreach ($db->table('submission_question_marks')->where('submission_id', $id)->get()->getResultArray() as $mark) {
            $savedMarks[(int) $mark['question_id']] = (float) $mark['marks'];
            if ($mark['similarity_percent'] !== null || $mark['verification_status'] !== null) {
                $savedVerification[(int) $mark['question_id']] = [
                    'similarity' => (float) ($mark['similarity_percent'] ?? 0),
                    'status' => $mark['verification_status'] ?: 'Not evaluated',
                ];
            }
        }
        $postedMarks = session()->getFlashdata('mark_values');
        $postedMarks = is_array($postedMarks) ? $postedMarks : [];
        foreach ($questions as &$question) {
            $questionId = (int) $question['id'];
            $question['obtain_mark'] = array_key_exists((string) $questionId, $postedMarks)
                ? $postedMarks[(string) $questionId]
                : ($savedMarks[$questionId] ?? '');
            if ($question['type'] === 'typing') {
                $answer = is_scalar($question['answer'] ?? null) ? (string) $question['answer'] : '';
                $evaluation = $savedVerification[$questionId] ?? TypingVerificationService::evaluate((string) ($question['typing_answer'] ?? ''), $answer);
                $question['similarity_percent'] = (float) ($evaluation['similarity'] ?? 0);
                $question['verification_status'] = (string) ($evaluation['status'] ?? 'Not evaluated');
            }
        }
        unset($question);

        $maxScore = array_sum(array_map(static fn (array $question): float => (float) $question['points'], $questions));
        $activityLogs = $db->table('audit_logs a')
            ->select('a.*, u.full_name AS actor_name, u.username AS actor_username')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->where(['a.entity_type' => 'submission', 'a.entity_id' => (string) $id])
            ->orderBy('a.id', 'DESC')->get()->getResultArray();
        $submissionIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            $db->table('submissions s')
                ->select('s.id')
                ->join(
                    '(SELECT MAX(id) AS id FROM submissions GROUP BY exam_id, applicant_id) latest_submission',
                    'latest_submission.id = s.id',
                    'inner',
                    false
                )
                ->orderBy('s.id', 'DESC')
                ->get()->getResultArray()
        );
        $currentIndex = array_search($id, $submissionIds, true);
        $previousSubmissionId = $currentIndex !== false ? ($submissionIds[$currentIndex + 1] ?? null) : null;
        $nextSubmissionId = $currentIndex !== false && $currentIndex > 0 ? $submissionIds[$currentIndex - 1] : null;

        return view('admin/submission_detail', [
            'submission' => $submission,
            'questions' => $questions,
            'markError' => $this->request->getGet('error'),
            'markSuccess' => $this->request->getGet('success'),
            'savedMarksTotal' => array_sum($savedMarks),
            'maxScore' => $maxScore,
            'previousSubmissionId' => $previousSubmissionId,
            'nextSubmissionId' => $nextSubmissionId,
            'activityLogs' => $activityLogs,
        ]);
    }

    public function submissionFile(int $submissionId, int $questionId): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $submission = $db->table('submissions')->where('id', $submissionId)->get()->getRowArray();
        $question = $submission
            ? $db->table('questions')->where(['id' => $questionId, 'exam_id' => $submission['exam_id']])->get()->getRowArray()
            : null;
        $answers = $submission && $submission['answers'] ? (json_decode($submission['answers'], true) ?: []) : [];
        $answer = $answers['q' . $questionId] ?? null;
        $relativePath = is_array($answer) ? (string) ($answer['file'] ?? '') : '';
        $uploadRoot = realpath(WRITEPATH . 'uploads/exam');
        $filePath = $uploadRoot && $relativePath !== ''
            ? realpath($uploadRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath))
            : false;
        if (! $submission || ! $question || ! $uploadRoot || ! $filePath
            || ! str_starts_with($filePath, $uploadRoot . DIRECTORY_SEPARATOR)
            || ! is_file($filePath)) {
            return $this->response->setStatusCode(404)->setBody('Submission file not found.');
        }

        $downloadName = basename((string) ($answer['name'] ?? basename($relativePath)));
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);
        if ($extension !== '' && pathinfo($downloadName, PATHINFO_EXTENSION) === '') {
            $downloadName = rtrim($downloadName, '. ') . '.' . $extension;
        }

        return $this->response->download($filePath, null)->setFileName($downloadName);
    }

}
