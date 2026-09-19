<?php

namespace App\Services;

use Throwable;

class AuditLogService
{
    private const SENSITIVE_KEYS = [
        'password', 'password_hash', 'pw', 'token', 'access_token',
        'refresh_token', 'otp', 'secret', 'cookie', 'authorization',
    ];

    public function log(
        string $event,
        ?int $userId = null,
        array $details = [],
        bool $required = false,
        $db = null
    ): bool {
        try {
            $db ??= db_connect();
            $context = $this->context();
            $db->table('audit_logs')->insert([
                'user_id' => $userId,
                'actor_type' => $details['actorType'] ?? ($userId ? 'user' : 'system'),
                'event' => $this->limit($event, 80),
                'entity_type' => isset($details['entityType']) ? $this->limit((string) $details['entityType'], 50) : null,
                'entity_id' => isset($details['entityId']) ? $this->limit((string) $details['entityId'], 64) : null,
                'description' => isset($details['description']) ? $this->limit((string) $details['description'], 500) : null,
                'old_values' => $this->encode($details['oldValues'] ?? null),
                'new_values' => $this->encode($details['newValues'] ?? null),
                'metadata' => $this->encode($details['metadata'] ?? null),
                'ip_address' => $context['ip_address'],
                'user_agent' => $context['user_agent'],
                'browser' => $context['browser'],
                'browser_version' => $context['browser_version'],
                'operating_system' => $context['operating_system'],
                'device_type' => $context['device_type'],
                'session_identifier' => $context['session_identifier'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (Throwable $exception) {
            log_message('error', 'Audit logging failed for {event}: {message}', [
                'event' => $event,
                'message' => $exception->getMessage(),
            ]);
            if ($required) {
                throw $exception;
            }

            return false;
        }
    }

    public function recordLogin(
        ?int $userId,
        ?string $identifier,
        string $status,
        ?string $failureReason = null
    ): ?int {
        try {
            $db = db_connect();
            $context = $this->context();
            $now = date('Y-m-d H:i:s');
            $db->table('login_logs')->insert([
                'user_id' => $userId,
                'login_identifier' => $this->limit((string) $identifier, 190) ?: null,
                'status' => $this->limit($status, 20),
                'failure_reason' => $failureReason ? $this->limit($failureReason, 50) : null,
                'ip_address' => $context['ip_address'],
                'user_agent' => $context['user_agent'],
                'browser' => $context['browser'],
                'browser_version' => $context['browser_version'],
                'operating_system' => $context['operating_system'],
                'device_type' => $context['device_type'],
                'session_identifier' => $context['session_identifier'],
                'logged_in_at' => $status === 'SUCCESS' ? $now : null,
                'created_at' => $now,
            ]);
            $logId = (int) $db->insertID();
            $this->log($status === 'SUCCESS' ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED', $userId, [
                'entityType' => 'login',
                'entityId' => (string) $logId,
                'description' => $status === 'SUCCESS' ? 'User login succeeded.' : 'User login failed.',
                'metadata' => ['failure_reason' => $failureReason],
            ]);

            return $logId;
        } catch (Throwable $exception) {
            log_message('error', 'Login logging failed: {message}', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    public function recordLogout(?int $userId, string $event = 'LOGOUT'): void
    {
        try {
            $db = db_connect();
            $sessionIdentifier = $this->context()['session_identifier'];
            if ($userId && $sessionIdentifier) {
                $login = $db->table('login_logs')
                    ->where(['user_id' => $userId, 'session_identifier' => $sessionIdentifier, 'status' => 'SUCCESS'])
                    ->where('logged_out_at IS NULL', null, false)
                    ->orderBy('id', 'DESC')
                    ->get()->getRowArray();
                if ($login) {
                    $db->table('login_logs')->where('id', $login['id'])->update(['logged_out_at' => date('Y-m-d H:i:s')]);
                }
            }
            $this->log($event, $userId, [
                'entityType' => 'session',
                'description' => 'User logged out.',
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Logout logging failed: {message}', ['message' => $exception->getMessage()]);
        }
    }

    public function logSubmission(array $data, bool $required = true, $db = null): bool
    {
        try {
            $db ??= db_connect();
            $context = $this->context();
            $db->table('submission_logs')->insert([
                'submission_id' => $data['submission_id'] ?? null,
                'exam_id' => $data['exam_id'] ?? null,
                'application_reference' => isset($data['application_reference']) ? $this->limit((string) $data['application_reference'], 40) : null,
                'application_version' => (int) ($data['application_version'] ?? 1),
                'user_id' => $data['user_id'] ?? null,
                'applicant_id' => isset($data['applicant_id']) ? $this->limit((string) $data['applicant_id'], 100) : null,
                'action' => $this->limit((string) ($data['action'] ?? 'FINAL_SUBMIT'), 50),
                'previous_status' => isset($data['previous_status']) ? $this->limit((string) $data['previous_status'], 30) : null,
                'new_status' => isset($data['new_status']) ? $this->limit((string) $data['new_status'], 30) : null,
                'submitted_at' => $data['submitted_at'] ?? null,
                'deadline_at' => $data['deadline_at'] ?? null,
                'ip_address' => $context['ip_address'],
                'user_agent' => $context['user_agent'],
                'browser' => $context['browser'],
                'browser_version' => $context['browser_version'],
                'operating_system' => $context['operating_system'],
                'device_type' => $context['device_type'],
                'session_identifier' => $context['session_identifier'],
                'snapshot_hash' => isset($data['snapshot_hash']) ? $this->limit((string) $data['snapshot_hash'], 64) : null,
                'remarks' => isset($data['remarks']) ? $this->limit((string) $data['remarks'], 500) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (Throwable $exception) {
            log_message('error', 'Submission audit logging failed: {message}', ['message' => $exception->getMessage()]);
            if ($required) {
                throw $exception;
            }

            return false;
        }
    }

    private function context(): array
    {
        $request = service('request');
        $agent = $request->getUserAgent();
        $raw = $this->limit($agent->getAgentString(), 1000);
        $lower = strtolower($raw);
        $device = $agent->isRobot()
            ? 'Bot'
            : (preg_match('/ipad|tablet|android(?!.*mobile)/i', $lower) ? 'Tablet' : ($agent->isMobile() ? 'Mobile' : ($agent->isBrowser() ? 'Desktop' : 'Unknown')));

        return [
            'ip_address' => $this->limit($request->getIPAddress(), 45) ?: null,
            'user_agent' => $raw ?: null,
            'browser' => $this->limit($agent->getBrowser(), 80) ?: null,
            'browser_version' => $this->limit($agent->getVersion(), 80) ?: null,
            'operating_system' => $this->limit($agent->getPlatform(), 100) ?: null,
            'device_type' => $device,
            'session_identifier' => $this->sessionIdentifier(),
        ];
    }

    private function sessionIdentifier(): ?string
    {
        $sessionId = session_id();

        return $sessionId !== '' ? hash('sha256', $sessionId) : null;
    }

    private function encode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $safe = $this->sanitize($value);
        $encoded = json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $this->limit($encoded, 60000);
    }

    private function sanitize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return is_string($value) ? $this->limit($value, 2000) : $value;
        }

        $safe = [];
        foreach ($value as $key => $item) {
            $keyString = strtolower((string) $key);
            if (in_array($keyString, self::SENSITIVE_KEYS, true)) {
                continue;
            }
            $safe[$this->limit((string) $key, 100)] = $this->sanitize($item);
        }

        return $safe;
    }

    private function limit(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }
}
