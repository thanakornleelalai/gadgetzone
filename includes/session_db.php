<?php
/**
 * includes/session_db.php
 * Database-backed PHP session handler.
 *
 * Vercel (and any serverless host) has per-invocation /tmp, so the default
 * file session handler loses state between requests — login, cart, and the
 * language preference all reset every page load. This handler persists
 * sessions in the `sessions` table on the configured MySQL/TiDB instance.
 *
 * Activated from includes/db.php when GZ_DB_SSL=1 (i.e. cloud DB in use).
 */

class GZ_DbSessionHandler implements SessionHandlerInterface
{
    private mysqli $conn;
    private bool   $ready = false;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /** Ensure the sessions table exists. Idempotent. */
    private function ensureTable(): void
    {
        if ($this->ready) { return; }
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS sessions (
                id      VARCHAR(128) PRIMARY KEY,
                data    MEDIUMTEXT   NOT NULL,
                expires INT          NOT NULL,
                INDEX idx_expires (expires)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $this->ready = true;
    }

    public function open(string $path, string $name): bool
    {
        $this->ensureTable();
        return true;
    }

    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $this->ensureTable();
        $now  = time();
        $stmt = $this->conn->prepare("SELECT data FROM sessions WHERE id=? AND expires>? LIMIT 1");
        $stmt->bind_param('si', $id, $now);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['data'] ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $this->ensureTable();
        $lifetime = (int)ini_get('session.gc_maxlifetime');
        if ($lifetime <= 0) { $lifetime = 1440; }
        $expires = time() + $lifetime;
        $stmt = $this->conn->prepare(
            "INSERT INTO sessions (id, data, expires) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE data=VALUES(data), expires=VALUES(expires)"
        );
        $stmt->bind_param('ssi', $id, $data, $expires);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE id=?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $now = time();
        $this->conn->query("DELETE FROM sessions WHERE expires<$now");
        return $this->conn->affected_rows;
    }
}
