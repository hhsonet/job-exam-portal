<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class Exam extends BaseController
{
    private const TOTAL_SECONDS = 45 * 60;
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 15 * 60;

    private function requireAuth(): ?ResponseInterface
    {
        if (! session()->get('exam_authenticated')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Not signed in.']);
        }

        return null;
    }

    private function currentApplicant(): ?array
    {
        $applicantCode = (string) session()->get('applicant_id');
        if ($applicantCode === '') {
            return null;
        }

        return db_connect()->table('users')
            ->where('applicant_code', $applicantCode)
            ->where('usertype', 'applicant')
            ->get()->getRowArray();
    }

    private function assignedExam(?array $applicant = null): ?array
    {
        $applicant ??= $this->currentApplicant();
        $examId = (int) ($applicant['assigned_exam_id'] ?? 0);
        if ($examId < 1) {
            return null;
        }

        return db_connect()->table('exams')->where('id', $examId)->get()->getRowArray();
    }

    private function displayStatus(array $exam, ?string $now = null): string
    {
        $now ??= date('Y-m-d H:i:s');
        if ($exam['status'] !== 'active') {
            return ucfirst((string) $exam['status']);
        }
        if ($exam['start_at'] && $now < $exam['start_at']) {
            return 'Scheduled';
        }
        if ($exam['end_at'] && $now > $exam['end_at']) {
            return 'Closed';
        }

        return 'Open';
    }

    private function isOpen(array $exam, ?string $now = null): bool
    {
        return $this->displayStatus($exam, $now) === 'Open';
    }

    private function scheduleLabel(?array $exam): string
    {
        if (! $exam) {
            return 'No assessment assigned';
        }

        $start = $exam['start_at'] ? date('d M Y, H:i', strtotime($exam['start_at'])) : 'Available now';
        $end = $exam['end_at'] ? date('d M Y, H:i', strtotime($exam['end_at'])) : 'No closing time';

        return $start . ' - ' . $end;
    }

    private function applicantViewData(array $applicant, ?array $assignedExam): array
    {
        return [
            'name' => $applicant['full_name'] ?: 'Applicant',
            'id' => $applicant['applicant_code'] ?: '',
            'scheduledWindow' => $this->scheduleLabel($assignedExam),
            'position' => $assignedExam['title'] ?? ($applicant['position'] ?: 'Applicant assessment'),
            'email' => $applicant['email'] ?? '',
        ];
    }

    private function questionCount(int $examId): int
    {
        return db_connect()->table('questions')
            ->where(['exam_id' => $examId, 'is_active' => 1])
            ->countAllResults();
    }

    private function submissionFor(int $examId, string $applicantCode): ?array
    {
        return db_connect()->table('submissions')
            ->where(['exam_id' => $examId, 'applicant_id' => $applicantCode])
            ->orderBy('id', 'DESC')->get()->getRowArray();
    }

    private function attemptFor(int $examId, string $applicantCode): ?array
    {
        return db_connect()->table('exam_attempts')
            ->where(['exam_id' => $examId, 'applicant_id' => $applicantCode])
            ->get()->getRowArray();
    }

    private function ensureAttempt(array $exam, string $applicantCode, array $answers = []): array
    {
        $attempt = $this->attemptFor((int) $exam['id'], $applicantCode);
        if ($attempt) {
            return $attempt;
        }

        $now = date('Y-m-d H:i:s');
        db_connect()->table('exam_attempts')->insert([
            'exam_id' => (int) $exam['id'],
            'applicant_id' => $applicantCode,
            'answers' => json_encode($answers),
            'marked' => json_encode([]),
            'started_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->attemptFor((int) $exam['id'], $applicantCode) ?? [
            'answers' => json_encode($answers),
            'marked' => json_encode([]),
            'started_at' => $now,
        ];
    }

    private function secondsRemaining(array $exam, array $attempt, bool $hasSubmission): int
    {
        $now = time();
        if ($hasSubmission && $exam['end_at']) {
            return max(0, strtotime($exam['end_at']) - $now);
        }

        return max(0, $this->attemptDeadline($exam, $attempt) - $now);
    }

    private function attemptDeadline(array $exam, array $attempt): int
    {
        $deadline = strtotime($attempt['started_at']) + (int) $exam['duration_seconds'];
        return $exam['end_at'] ? min($deadline, strtotime($exam['end_at'])) : $deadline;
    }

    private function cleanAnswers(int $examId, array $answers): array
    {
        $ids = db_connect()->table('questions')
            ->select('id')->where(['exam_id' => $examId, 'is_active' => 1])
            ->get()->getResultArray();
        $allowed = array_fill_keys(array_map(static fn (array $row): string => 'q' . $row['id'], $ids), true);

        return array_intersect_key($answers, $allowed);
    }

    public function loginForm(): string|ResponseInterface
    {
        if (session()->get('exam_authenticated')) {
            return redirect()->to('/dashboard');
        }

        return view('exam/login');
    }

    public function login(): ResponseInterface
    {
        $session = session();
        $lockedUntil = (int) $session->get('login_locked_until');
        if ($lockedUntil > time()) {
            return $this->response->setStatusCode(423)->setJSON(['error' => 'locked']);
        }

        $payload = $this->request->getJSON(true) ?? [];
        $id = trim((string) ($payload['id'] ?? ''));
        $password = (string) ($payload['pw'] ?? '');
        $applicant = db_connect()->table('users')
            ->where('applicant_code', $id)
            ->where('usertype', 'applicant')
            ->get()->getRowArray();

        if (! $applicant || ! password_verify($password, $applicant['password_hash'])) {
            $attempts = (int) $session->get('login_attempts') + 1;
            $session->set('login_attempts', $attempts);
            if ($attempts >= self::MAX_ATTEMPTS) {
                $session->set('login_locked_until', time() + self::LOCKOUT_SECONDS);
                $session->remove('login_attempts');
                return $this->response->setStatusCode(423)->setJSON(['error' => 'locked']);
            }

            return $this->response->setStatusCode(401)->setJSON([
                'error' => 'credentials',
                'attemptsRemaining' => max(0, self::MAX_ATTEMPTS - $attempts),
            ]);
        }

        $session->remove(['login_attempts', 'login_locked_until', 'selected_exam_id', 'submitted_exam_id']);
        $session->set([
            'exam_authenticated' => true,
            'applicant_user_id' => (int) $applicant['id'],
            'applicant_id' => $applicant['applicant_code'],
            'applicant_name' => $applicant['full_name'],
            'applicant_email' => $applicant['email'] ?? '',
        ]);

        return $this->response->setJSON(['redirect' => site_url('dashboard')]);
    }

    public function logout(): ResponseInterface
    {
        session()->destroy();
        return redirect()->to('/login');
    }

    public function index(): string|ResponseInterface
    {
        if (! session()->get('exam_authenticated')) {
            return redirect()->to('/login');
        }

        $applicant = $this->currentApplicant();
        if (! $applicant) {
            session()->destroy();
            return redirect()->to('/login');
        }

        $assignedExam = $this->assignedExam($applicant);
        $screen = (string) (session()->get('exam_initial_screen') ?: 'instructions');
        $selectedExamId = (int) session()->get('selected_exam_id');
        session()->remove(['exam_initial_screen', 'selected_exam_id']);
        $now = date('Y-m-d H:i:s');
        $availableExams = [];
        $dashboardExam = null;

        if ($assignedExam && $assignedExam['status'] !== 'archived') {
            $dashboardSubmission = $this->submissionFor((int) $assignedExam['id'], (string) $applicant['applicant_code']);
            $assignedExam['display_status'] = $this->displayStatus($assignedExam, $now);
            $assignedExam['question_count'] = $this->questionCount((int) $assignedExam['id']);
            $assignedExam['has_submission'] = (bool) $dashboardSubmission;
            $assignedExam['submission_status'] = $dashboardSubmission['status'] ?? 'Not submitted';
            $assignedExam['submitted_at'] = $dashboardSubmission['submitted_at'] ?? null;
            $assignedExam['time_used'] = $dashboardSubmission ? (int) $dashboardSubmission['time_used'] : null;
            $assignedExam['answered_count'] = $dashboardSubmission ? (int) $dashboardSubmission['answered_count'] : 0;
            $assignedExam['total_count'] = $dashboardSubmission ? (int) $dashboardSubmission['total_count'] : $assignedExam['question_count'];
            $assignedExam['marked_count'] = $dashboardSubmission ? (int) $dashboardSubmission['marked_count'] : 0;
            $assignedExam['can_edit'] = $assignedExam['has_submission'] && $assignedExam['display_status'] === 'Open' && $assignedExam['question_count'] > 0;
            $assignedExam['can_start'] = ! $assignedExam['has_submission'] && $assignedExam['display_status'] === 'Open' && $assignedExam['question_count'] > 0;
            $examTokens = session()->get('exam_tokens') ?: [];
            $examToken = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
            $examTokens[$examToken] = (int) $assignedExam['id'];
            session()->set('exam_tokens', $examTokens);
            $assignedExam['exam_token'] = $examToken;
            $dashboardExam = $assignedExam;
            $availableExams[] = $assignedExam;
        }

        $exam = null;
        $submission = null;
        $attempt = null;
        $questions = [];
        $savedAnswers = [];
        $savedMarked = [];
        $secondsRemaining = 0;

        if ($screen === 'exam') {
            $examId = $selectedExamId ?: (int) ($assignedExam['id'] ?? 0);
            if (! $assignedExam || $examId !== (int) $assignedExam['id']) {
                return redirect()->to('/dashboard?error=' . rawurlencode('That assessment is not assigned to you.'));
            }
            if (! $this->isOpen($assignedExam, $now)) {
                return redirect()->to('/dashboard?error=' . rawurlencode('That assessment is not currently open.'));
            }
            if ($this->questionCount($examId) < 1) {
                return redirect()->to('/dashboard?error=' . rawurlencode('This assessment has no questions yet.'));
            }

            $exam = $assignedExam;
            $submission = $this->submissionFor($examId, (string) $applicant['applicant_code']);
            $submissionAnswers = $submission && $submission['answers'] ? (json_decode($submission['answers'], true) ?: []) : [];
            $attempt = $this->ensureAttempt($exam, (string) $applicant['applicant_code'], $submissionAnswers);
            $savedAnswers = $attempt['answers'] ? (json_decode($attempt['answers'], true) ?: []) : $submissionAnswers;
            $savedMarked = $attempt['marked'] ? (json_decode($attempt['marked'], true) ?: []) : [];
            $secondsRemaining = $this->secondsRemaining($exam, $attempt, (bool) $submission);
            if (! $submission && $secondsRemaining < 1) {
                $screen = 'expired';
            }
        } elseif ($screen === 'success') {
            $examId = (int) (session()->get('submitted_exam_id') ?: ($assignedExam['id'] ?? 0));
            if ($assignedExam && $examId === (int) $assignedExam['id']) {
                $exam = $assignedExam;
                $submission = $this->submissionFor($examId, (string) $applicant['applicant_code']);
                $savedAnswers = $submission && $submission['answers'] ? (json_decode($submission['answers'], true) ?: []) : [];
                $attempt = $this->attemptFor($examId, (string) $applicant['applicant_code']);
                $savedMarked = $attempt && $attempt['marked'] ? (json_decode($attempt['marked'], true) ?: []) : [];
                $secondsRemaining = $attempt ? $this->secondsRemaining($exam, $attempt, (bool) $submission) : 0;
            }
            if (! $submission) {
                return redirect()->to('/dashboard?error=' . rawurlencode('No submission was found.'));
            }
        }

        if ($exam) {
            $questionRows = db_connect()->table('questions')
                ->where(['exam_id' => $exam['id'], 'is_active' => 1])
                ->orderBy('id', 'ASC')->get()->getResultArray();
            $questions = array_map(static function (array $question): array {
                return [
                    'id' => 'q' . $question['id'],
                    'type' => $question['type'],
                    'points' => (int) $question['points'],
                    'prompt' => $question['prompt'],
                    'hint' => $question['hint'],
                    'options' => $question['options'] ? json_decode($question['options'], true) : null,
                    'attachmentName' => $question['attachment_name'],
                    'attachmentUrl' => $question['attachment_path'] ? site_url('exam/questions/' . $question['id'] . '/attachment') : null,
                    'attachments' => array_map(static fn (array $attachment): array => [
                        'name' => $attachment['original_name'],
                        'url' => site_url('exam/questions/' . $question['id'] . '/attachment/' . $attachment['id']),
                    ], db_connect()->table('question_attachments')->where('question_id', $question['id'])->orderBy('id', 'ASC')->get()->getResultArray()),
                ];
            }, $questionRows);
        }

        $submissionSummary = $submission ? [
            'reference' => $submission['reference'],
            'submittedAt' => $submission['submitted_at'],
            'timeUsed' => (int) $submission['time_used'],
        ] : null;

        return view('exam/index', [
            'applicant' => $this->applicantViewData($applicant, $assignedExam),
            'dashboardExam' => $dashboardExam,
            'examId' => (int) ($exam['id'] ?? 0),
            'totalSeconds' => $exam ? (int) $exam['duration_seconds'] : self::TOTAL_SECONDS,
            'secondsRemaining' => $secondsRemaining,
            'savedAnswers' => $savedAnswers,
            'savedMarked' => $savedMarked,
            'canEditSubmission' => $submission && $exam && $this->isOpen($exam, $now),
            'initialScreen' => $screen,
            'submissionSummary' => $submissionSummary,
            'availableExams' => $availableExams,
            'workflowError' => trim((string) $this->request->getGet('error')),
            'questions' => $questions,
        ]);
    }

    public function autosave(): ResponseInterface
    {
        if ($denied = $this->requireAuth()) {
            return $denied;
        }

        $payload = $this->request->getJSON(true) ?? [];
        $examId = (int) ($payload['examId'] ?? 0);
        $applicant = $this->currentApplicant();
        $exam = $this->assignedExam($applicant);
        if (! $applicant || ! $exam || $examId !== (int) $exam['id']) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Assessment access denied.']);
        }
        if (! $this->isOpen($exam) || $this->questionCount($examId) < 1) {
            return $this->response->setStatusCode(423)->setJSON(['error' => 'This assessment is not open.']);
        }

        $submission = $this->submissionFor($examId, (string) $applicant['applicant_code']);
        $attempt = $this->ensureAttempt($exam, (string) $applicant['applicant_code']);
        if (! $submission && $this->secondsRemaining($exam, $attempt, false) < 1) {
            return $this->response->setStatusCode(423)->setJSON(['error' => 'Your assessment time has expired.']);
        }

        $answers = $this->cleanAnswers($examId, is_array($payload['answers'] ?? null) ? $payload['answers'] : []);
        $marked = $this->cleanAnswers($examId, is_array($payload['marked'] ?? null) ? $payload['marked'] : []);
        db_connect()->table('exam_attempts')
            ->where(['exam_id' => $examId, 'applicant_id' => $applicant['applicant_code']])
            ->update([
                'answers' => json_encode($answers),
                'marked' => json_encode($marked),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->response->setJSON(['savedAt' => date('c')]);
    }

    public function upload(): ResponseInterface
    {
        if ($denied = $this->requireAuth()) {
            return $denied;
        }

        $examId = (int) $this->request->getPost('exam_id');
        $exam = $this->assignedExam();
        if (! $exam || $examId !== (int) $exam['id'] || ! $this->isOpen($exam)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Assessment access denied.']);
        }
        $applicantCode = (string) session()->get('applicant_id');
        $submission = $this->submissionFor($examId, $applicantCode);
        $attempt = $this->ensureAttempt($exam, $applicantCode);
        if (! $submission && $this->secondsRemaining($exam, $attempt, false) < 1) {
            return $this->response->setStatusCode(423)->setJSON(['error' => 'Your assessment time has expired.']);
        }
        $file = $this->request->getFile('file');
        if ($file === null || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No valid file was received.']);
        }
        if ($file->getMimeType() !== 'application/pdf') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'That file is not a PDF. Please upload a PDF document.']);
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'That file is larger than 10 MB. Please upload a smaller PDF.']);
        }

        $targetDir = WRITEPATH . 'uploads/exam';
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $storedName = $file->getRandomName();
        $file->move($targetDir, $storedName);
        $bytes = $file->getSize();
        $sizeLabel = $bytes < 1024 * 1024 ? max(1, (int) round($bytes / 1024)) . ' KB' : round($bytes / 1048576, 1) . ' MB';

        return $this->response->setJSON([
            'name' => $file->getClientName(),
            'storedName' => $storedName,
            'size' => $sizeLabel,
        ]);
    }

    public function questionAttachment(int $id, ?int $attachmentId = null): ResponseInterface
    {
        if ($denied = $this->requireAuth()) {
            return $denied;
        }

        $db = db_connect();
        $exam = $this->assignedExam();
        $question = $db->table('questions')->where('id', $id)->get()->getRowArray();
        if (! $exam || ! $question || (int) $question['exam_id'] !== (int) $exam['id']) {
            return $this->response->setStatusCode(403)->setBody('Attachment access denied.');
        }
        $attachment = $attachmentId ? $db->table('question_attachments')->where(['id' => $attachmentId, 'question_id' => $id])->get()->getRowArray() : null;
        $storedName = $attachment['stored_name'] ?? ($question['attachment_path'] ?? null);
        $originalName = $attachment['original_name'] ?? ($question['attachment_name'] ?? null);
        $path = $storedName ? WRITEPATH . 'uploads/questions/' . $storedName : '';
        if (! $storedName || ! is_file($path)) {
            return $this->response->setStatusCode(404)->setBody('Attachment not found.');
        }

        return $this->response->download($path, null)->setFileName($originalName);
    }

    public function submit(): ResponseInterface
    {
        if ($denied = $this->requireAuth()) {
            return $denied;
        }

        $payload = $this->request->getJSON(true) ?? [];
        $examId = (int) ($payload['examId'] ?? 0);
        $applicant = $this->currentApplicant();
        $exam = $this->assignedExam($applicant);
        if (! $applicant || ! $exam || $examId !== (int) $exam['id']) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Assessment access denied.']);
        }
        $autoSubmit = ! empty($payload['autoSubmit']);
        $scheduleAllowsAutoSubmit = $exam['status'] === 'active'
            && (! $exam['start_at'] || time() >= strtotime($exam['start_at']))
            && (! $exam['end_at'] || time() <= strtotime($exam['end_at']) + 30);
        if (! $this->isOpen($exam) && ! ($autoSubmit && $scheduleAllowsAutoSubmit)) {
            return $this->response->setStatusCode(423)->setJSON(['error' => 'This assessment is closed for submissions.']);
        }
        $totalCount = $this->questionCount($examId);
        if ($totalCount < 1) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'This assessment has no questions.']);
        }

        $existing = $this->submissionFor($examId, (string) $applicant['applicant_code']);
        $attempt = $this->ensureAttempt($exam, (string) $applicant['applicant_code']);
        $personalTimeExpired = ! $existing && time() > $this->attemptDeadline($exam, $attempt);
        $answerSource = $personalTimeExpired
            ? (json_decode((string) $attempt['answers'], true) ?: [])
            : (is_array($payload['answers'] ?? null) ? $payload['answers'] : []);
        $markedSource = $personalTimeExpired
            ? (json_decode((string) $attempt['marked'], true) ?: [])
            : (is_array($payload['marked'] ?? null) ? $payload['marked'] : []);
        $answers = $this->cleanAnswers($examId, $answerSource);
        $marked = $this->cleanAnswers($examId, $markedSource);
        $reference = ($existing && ! $exam['allow_multiple_submissions'])
            ? $existing['reference']
            : 'NV-TS-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
        $submittedAtDb = date('Y-m-d H:i:s');
        $submittedAt = date('d M Y, H:i') . ' IST';
        $answeredCount = count(array_filter($answers, static function ($value): bool {
            if (is_array($value)) {
                return isset($value['file']) || count($value) > 0;
            }
            return trim((string) $value) !== '';
        }));
        $timeUsed = max(0, min((int) $exam['duration_seconds'], (int) $exam['duration_seconds'] - $this->secondsRemaining($exam, $attempt, false)));
        $submissionData = [
            'exam_id' => $examId,
            'reference' => $reference,
            'applicant_id' => $applicant['applicant_code'],
            'answered_count' => $answeredCount,
            'total_count' => $totalCount,
            'marked_count' => count($marked),
            'time_used' => $timeUsed,
            'answers' => json_encode($answers),
            'status' => 'submitted',
            'submitted_at' => $submittedAtDb,
            'updated_at' => $submittedAtDb,
        ];

        $submissions = db_connect()->table('submissions');
        if ($existing && ! $exam['allow_multiple_submissions']) {
            $submissions->where('id', $existing['id'])->update($submissionData);
        } else {
            $submissionData['created_at'] = $submittedAtDb;
            $submissions->insert($submissionData);
        }
        db_connect()->table('exam_attempts')
            ->where(['exam_id' => $examId, 'applicant_id' => $applicant['applicant_code']])
            ->update(['answers' => json_encode($answers), 'marked' => json_encode($marked), 'updated_at' => $submittedAtDb]);

        $record = [
            'reference' => $reference,
            'submittedAt' => $submittedAt,
            'applicantId' => $applicant['applicant_code'],
            'answeredCount' => $answeredCount,
            'totalCount' => $totalCount,
            'markedCount' => count($marked),
            'timeUsed' => $timeUsed,
            'answers' => $answers,
            'createdAt' => date('c'),
        ];
        $dir = WRITEPATH . 'exam_submissions';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . '/' . $reference . '.json', json_encode($record, JSON_PRETTY_PRINT));
        session()->set('submitted_exam_id', $examId);

        return $this->response->setJSON(['referenceNumber' => $reference, 'submittedAt' => $submittedAt]);
    }

    public function dashboard(): string|ResponseInterface
    {
        if (! session()->get('exam_authenticated')) {
            return redirect()->to('/login');
        }
        session()->set('exam_initial_screen', 'instructions');
        return $this->index();
    }

    public function examPage(): string|ResponseInterface
    {
        if ($this->request->getGet('exam_id')) {
            return redirect()->to('/dashboard?error=' . rawurlencode('Please open the assessment from your dashboard.'));
        }
        $token = trim((string) $this->request->getGet('exam_token'));
        if ($token !== '') {
            $examTokens = session()->get('exam_tokens') ?: [];
            $examId = (int) ($examTokens[$token] ?? 0);
            if ($examId < 1) {
                return redirect()->to('/dashboard?error=' . rawurlencode('That assessment link is no longer valid.'));
            }
            session()->set('selected_exam_id', $examId);
        }
        session()->set('exam_initial_screen', 'exam');
        return $this->index();
    }

    public function submittedPage(): string|ResponseInterface
    {
        session()->set('exam_initial_screen', 'success');
        return $this->index();
    }
}
