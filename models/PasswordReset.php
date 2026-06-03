<?php
require_once __DIR__ . '/../bootstrap.php';

class PasswordResetManager
{
    private string $resetsFile;
    private int $ttlSeconds;

    public function __construct(?string $dataDirectory = null, int $ttlSeconds = 3600)
    {
        $dataDirectory = $dataDirectory ?: __DIR__ . '/../data';
        $this->resetsFile = $dataDirectory . '/password_resets.json';
        $this->ttlSeconds = $ttlSeconds;

        if (!is_dir($dataDirectory)) {
            mkdir($dataDirectory, 0775, true);
        }

        if (!file_exists($this->resetsFile)) {
            file_put_contents($this->resetsFile, json_encode([]), LOCK_EX);
        }
    }

    public function createToken(int $userId): array
    {
        $this->purgeExpired();

        $rawToken = bin2hex(random_bytes(32));
        $record = [
            'id' => uniqid('pwd_', true),
            'user_id' => $userId,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->ttlSeconds),
            'used_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $records = array_values(array_filter($this->load(), static function (array $candidate) use ($userId): bool {
            return (int) ($candidate['user_id'] ?? 0) !== $userId || !empty($candidate['used_at']);
        }));

        $records[] = $record;
        $this->save($records);

        return [
            'token' => $rawToken,
            'record' => $record,
        ];
    }

    public function getValidReset(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $hash = hash('sha256', $token);
        $now = time();

        foreach ($this->load() as $record) {
            if ((string) ($record['token_hash'] ?? '') !== $hash) {
                continue;
            }

            if (!empty($record['used_at'])) {
                return null;
            }

            $expiresAt = strtotime((string) ($record['expires_at'] ?? ''));
            if ($expiresAt === false || $expiresAt < $now) {
                return null;
            }

            return $record;
        }

        return null;
    }

    public function markUsed(string $token): bool
    {
        $hash = hash('sha256', trim($token));
        $records = $this->load();
        $changed = false;

        foreach ($records as &$record) {
            if ((string) ($record['token_hash'] ?? '') === $hash) {
                $record['used_at'] = date('Y-m-d H:i:s');
                $changed = true;
                break;
            }
        }
        unset($record);

        if ($changed) {
            $this->save($records);
        }

        return $changed;
    }

    private function purgeExpired(): void
    {
        $now = time();
        $records = array_values(array_filter($this->load(), static function (array $record) use ($now): bool {
            $expiresAt = strtotime((string) ($record['expires_at'] ?? ''));

            return !empty($record['used_at']) || ($expiresAt !== false && $expiresAt >= $now);
        }));

        $this->save($records);
    }

    private function load(): array
    {
        $decoded = json_decode((string) @file_get_contents($this->resetsFile), true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    private function save(array $records): bool
    {
        return (bool) file_put_contents(
            $this->resetsFile,
            json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}
