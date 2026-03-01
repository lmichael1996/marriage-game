<?php

require_once __DIR__ . '/../config/database.php';

/**
 * TokenRepo — Repository per i token di autenticazione salvati nel DB.
 *
 * Gestisce la tabella auth_tokens con JOIN su users/players/rooms/judges.
 */
class TokenRepo {

    private const TTL = 86400; // 24 ore

    public const COOKIE_ADMIN  = 'admin_token';
    public const COOKIE_PLAYER = 'player_token';
    public const COOKIE_JUDGE  = 'judge_token';

    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    // ── Admin ────────────────────────────────────────────────────────────

    /**
     * Crea un token admin nel DB e setta il cookie.
     */
    public function setAdminCookie(int $userId, string $username): void {
        $stmt = $this->conn->prepare("DELETE FROM auth_tokens WHERE auth_role = 'admin' AND user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        $token = $this->generateToken();
        $hash  = hash('sha256', $token);
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt  = $this->conn->prepare(
            "INSERT INTO auth_tokens (token_hash, auth_role, ip_address, user_id) VALUES (?, 'admin', ?, ?)"
        );
        $stmt->bind_param('ssi', $hash, $ip, $userId);
        $stmt->execute();
        $stmt->close();

        $this->setCookie(self::COOKIE_ADMIN, $token);
    }

    /**
     * Legge e verifica il cookie admin. Ritorna payload o null.
     */
    public function getAdmin(): ?array {
        $token = $_COOKIE[self::COOKIE_ADMIN] ?? null;
        if (!$token) return null;

        $hash = hash('sha256', $token);
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $this->conn->prepare(
            "SELECT t.user_id, u.username
             FROM auth_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.ip_address = ? AND t.auth_role = 'admin'
               AND t.created_at > NOW() - INTERVAL " . self::TTL . " SECOND"
        );
        $stmt->bind_param('ss', $hash, $ip);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        return [
            'role'     => 'admin',
            'user_id'  => (int) $row['user_id'],
            'username' => $row['username'],
            'is_admin' => 1,
        ];
    }

    /**
     * Cancella il cookie admin e il token dal DB.
     */
    public function clearAdminCookie(): void {
        $this->clearTokenByCookie(self::COOKIE_ADMIN);
    }

    // ── Player ───────────────────────────────────────────────────────────

    /**
     * Crea un token player nel DB e setta il cookie.
     */
    public function setPlayerCookie(int $playerId, string $username, string $roomCode): void {
        $stmt = $this->conn->prepare("DELETE FROM auth_tokens WHERE auth_role = 'player' AND player_id = ?");
        $stmt->bind_param('i', $playerId);
        $stmt->execute();
        $stmt->close();

        $token = $this->generateToken();
        $hash  = hash('sha256', $token);
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt  = $this->conn->prepare(
            "INSERT INTO auth_tokens (token_hash, auth_role, ip_address, player_id) VALUES (?, 'player', ?, ?)"
        );
        $stmt->bind_param('ssi', $hash, $ip, $playerId);
        $stmt->execute();
        $stmt->close();

        $this->setCookie(self::COOKIE_PLAYER, $token);
    }

    /**
     * Legge e verifica il cookie player. Ritorna payload o null.
     */
    public function getPlayer(): ?array {
        $token = $_COOKIE[self::COOKIE_PLAYER] ?? null;
        if (!$token) return null;

        $hash = hash('sha256', $token);
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $this->conn->prepare(
            "SELECT t.player_id, p.username, r.code_player AS room_code
             FROM auth_tokens t
             JOIN players p ON p.id = t.player_id
             JOIN rooms r ON r.id = p.room_id
             WHERE t.token_hash = ? AND t.ip_address = ? AND t.auth_role = 'player'
               AND t.created_at > NOW() - INTERVAL " . self::TTL . " SECOND"
        );
        $stmt->bind_param('ss', $hash, $ip);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        return [
            'role'      => 'player',
            'player_id' => (int) $row['player_id'],
            'username'  => $row['username'],
            'room_code' => $row['room_code'],
        ];
    }

    /**
     * Cancella il cookie player e il token dal DB.
     */
    public function clearPlayerCookie(): void {
        $this->clearTokenByCookie(self::COOKIE_PLAYER);
    }

    // ── Judge ────────────────────────────────────────────────────────────

    /**
     * Crea un token judge nel DB e setta il cookie.
     */
    public function setJudgeCookie(int $judgeId, string $roomCode): void {
        $stmt = $this->conn->prepare("DELETE FROM auth_tokens WHERE auth_role = 'judge' AND judge_id = ?");
        $stmt->bind_param('i', $judgeId);
        $stmt->execute();
        $stmt->close();

        $token = $this->generateToken();
        $hash  = hash('sha256', $token);
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt  = $this->conn->prepare(
            "INSERT INTO auth_tokens (token_hash, auth_role, ip_address, judge_id) VALUES (?, 'judge', ?, ?)"
        );
        $stmt->bind_param('ssi', $hash, $ip, $judgeId);
        $stmt->execute();
        $stmt->close();

        $this->setCookie(self::COOKIE_JUDGE, $token);
    }

    /**
     * Legge e verifica il cookie judge. Ritorna payload o null.
     */
    public function getJudge(): ?array {
        $token = $_COOKIE[self::COOKIE_JUDGE] ?? null;
        if (!$token) return null;

        $hash = hash('sha256', $token);
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $this->conn->prepare(
            "SELECT t.judge_id, r.code_judge AS room_code, r.id AS room_id
             FROM auth_tokens t
             JOIN judges j ON j.id = t.judge_id
             JOIN rooms r ON r.id = j.room_id
             WHERE t.token_hash = ? AND t.ip_address = ? AND t.auth_role = 'judge'
               AND t.created_at > NOW() - INTERVAL " . self::TTL . " SECOND"
        );
        $stmt->bind_param('ss', $hash, $ip);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        return [
            'role'      => 'judge',
            'judge_id'  => (int) $row['judge_id'],
            'room_code' => $row['room_code'],
            'room_id'   => (int) $row['room_id'],
        ];
    }

    /**
     * Cancella il cookie judge e il token dal DB.
     */
    public function clearJudgeCookie(): void {
        $this->clearTokenByCookie(self::COOKIE_JUDGE);
    }

    // ── Any user ─────────────────────────────────────────────────────────

    /**
     * Ritorna i dati di qualsiasi utente autenticato (admin, player o judge).
     */
    public function getAnyUser(): ?array {
        return $this->getAdmin() ?? $this->getPlayer() ?? $this->getJudge();
    }

    /**
     * Cancella tutti i cookie auth e i token dal DB.
     */
    public function clearAll(): void {
        $this->clearAdminCookie();
        $this->clearPlayerCookie();
        $this->clearJudgeCookie();
    }

    // ── Internal helpers ─────────────────────────────────────────────────

    /**
     * Cancella un token dal DB cercandolo nel cookie, poi rimuove il cookie.
     */
    private function clearTokenByCookie(string $cookieName): void {
        $token = $_COOKIE[$cookieName] ?? null;
        if ($token) {
            $hash = hash('sha256', $token);
            $stmt = $this->conn->prepare("DELETE FROM auth_tokens WHERE token_hash = ?");
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            $stmt->close();
        }
        $this->clearCookie($cookieName);
    }

    /**
     * Elimina dal DB tutti i token scaduti (più vecchi di TTL secondi).
     */
    public function cleanExpired(): int {
        $stmt = $this->conn->prepare(
            "DELETE FROM auth_tokens WHERE created_at <= NOW() - INTERVAL " . self::TTL . " SECOND"
        );
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    private function generateToken(): string {
        return bin2hex(random_bytes(32));
    }

    private function setCookie(string $name, string $value): void {
        setcookie($name, $value, [
            'expires'  => time() + self::TTL,
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
            'secure'   => false,
        ]);
        $_COOKIE[$name] = $value;
    }

    private function clearCookie(string $name): void {
        setcookie($name, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$name]);
    }
}
