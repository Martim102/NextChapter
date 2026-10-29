<?php
/** Ligação à base de dados e sessões guardadas em MySQL (necessário na Vercel). */

function config(string $section): array
{
    return $GLOBALS['nc_config'][$section] ?? [];
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = config('db');
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (($c['dsn'] ?? '') !== '') {
        $pdo = new PDO($c['dsn'], null, null, $opts);
        return $pdo;
    }
    $opts[PDO::ATTR_EMULATE_PREPARES] = false;
    if (!empty($c['ssl'])) {
        $opts[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
    }
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
    $pdo = new PDO($dsn, $c['user'], $c['pass'], $opts);
    return $pdo;
}

class NcSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $st = db()->prepare('SELECT data FROM sessions WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetchColumn();
        return $r === false ? '' : (string) $r;
    }

    public function write(string $id, string $data): bool
    {
        try {
            db()->prepare('REPLACE INTO sessions (id, data, updated_at) VALUES (?, ?, ?)')
                ->execute([$id, $data, time()]);
            return true;
        } catch (Throwable $e) {
            error_log('NextChapter session write: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            db()->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $st = db()->prepare('DELETE FROM sessions WHERE updated_at < ?');
            $st->execute([time() - $max_lifetime]);
            return $st->rowCount();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function validateId(string $id): bool
    {
        $st = db()->prepare('SELECT 1 FROM sessions WHERE id = ?');
        $st->execute([$id]);
        return $st->fetchColumn() !== false;
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        try {
            db()->prepare('UPDATE sessions SET updated_at = ? WHERE id = ?')->execute([time(), $id]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
