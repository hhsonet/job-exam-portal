<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class Admin extends BaseController
{
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

        return redirect()->to('/admin');
    }

    public function logout(): ResponseInterface
    {
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

    public function deleteExam(int $id): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
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

        db_connect()->table('exams')->insert([
            'title' => $data['title'], 'description' => $data['description'] ?: null,
            'duration_seconds' => $data['duration_minutes'] * 60, 'status' => 'draft',
            'start_at' => $data['start_at'] ? date('Y-m-d H:i:s', strtotime($data['start_at'])) : null,
            'end_at' => $data['end_at'] ? date('Y-m-d H:i:s', strtotime($data['end_at'])) : null,
            'allow_multiple_submissions' => $data['allow_multiple_submissions'],
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/exams');
    }

    public function updateExam(int $id): ResponseInterface|string
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
            'hint' => trim((string) $this->request->getPost('hint')),
            'points' => max(1, (int) $this->request->getPost('points')),
            'options' => $this->request->getPost('options'),
        ];

        $validTypes = ['single', 'multi', 'bool', 'written', 'upload'];
        if (! in_array($data['type'], $validTypes, true) || $data['prompt'] === '') {
            return view('admin/question_form', ['error' => 'Choose a valid type and enter a question prompt.', 'data' => $data, 'exams' => $exams]);
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
            'prompt' => $data['prompt'],
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

        return redirect()->to('/admin/questions');
    }

    public function newQuestion(?int $examId = null): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $examId = $examId ?: (int) $this->request->getGet('exam_id');
        $exams = db_connect()->table('exams')->orderBy('id', 'DESC')->get()->getResultArray();
        return view('admin/question_form', ['error' => null, 'data' => ['exam_id' => $examId], 'exams' => $exams]);
    }

    public function deleteQuestion(int $id): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        db_connect()->table('questions')->where('id', $id)->delete();

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

        db_connect()->table('users')->insert([
            'username' => $data['applicant_code'], 'full_name' => $data['full_name'],
            'applicant_code' => $data['applicant_code'], 'position' => $assignedExam['title'], 'assigned_exam_id' => (int) $assignedExam['id'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT), 'usertype' => 'applicant',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
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
        }
        $db->transStart();
        $db->table('users')->where(['id' => $id, 'usertype' => 'applicant'])->update($update);
        if ($currentApplicant['applicant_code'] !== $data['applicant_code']) {
            $db->table('submissions')->where('applicant_id', $currentApplicant['applicant_code'])->update(['applicant_id' => $data['applicant_code']]);
            $db->table('exam_attempts')->where('applicant_id', $currentApplicant['applicant_code'])->update(['applicant_id' => $data['applicant_code']]);
        }
        $db->transComplete();
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
            $rows[$applicantId] = ['username' => $applicantId, 'full_name' => $name, 'applicant_code' => $applicantId, 'position' => $exam['title'], 'assigned_exam_id' => (int) $exam['id'], 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'usertype' => 'applicant', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
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

    public function markSubmission(int $id): ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $score = max(0, (float) $this->request->getPost('score'));
        $maxScore = max(0, (float) $this->request->getPost('max_score'));
        $notes = trim((string) $this->request->getPost('marker_notes'));

        $db = db_connect();
        $submission = $db->table('submissions')->where('id', $id)->get()->getRowArray();
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

        $db->table('submissions')->where('id', $id)->update([
            'score' => $score,
            'max_score' => $maxScore,
            'marker_notes' => $notes ?: null,
            'status' => 'marked',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/submissions');
    }

    public function submissionDetail(int $id): string|ResponseInterface
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $db = db_connect();
        $submission = $db->table('submissions s')
            ->select('s.*, e.title AS exam_title, u.full_name AS applicant_name, u.position AS applicant_position')
            ->join('users u', "u.applicant_code = s.applicant_id AND u.usertype = 'applicant'", 'left')
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

        return view('admin/submission_detail', ['submission' => $submission, 'questions' => $questions]);
    }
}
