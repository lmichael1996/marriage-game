<?php

/**
 * TokenService — Cookie firmati HMAC-SHA256
 *
 * Genera e verifica token firmati per autenticazione stateless.
 * Ogni ruolo (admin/player) usa un cookie separato, eliminando
 * il conflitto di sessione quando si testa sullo stesso browser.
 */
class TokenService {

    private const ALGO      = 'sha256';
    private const SEPARATOR = '.';
    private const TTL       = 86400; // 24 ore

    private const COOKIE_ADMIN  = 'admin_token';
    private const COOKIE_PLAYER = 'player_token';

    private static ?string $secretKey = null;

    // ── Secret key ───────────────────────────────────────────────────────

    private static function secret(): string {
        if (self::$secretKey !== null) return self::$secretKey;

        $keyFile = __DIR__ . '/../../.secret_key';
        if (!file_exists($keyFile)) {
            $key = bin2hex(random_bytes(32));
            file_put_contents($keyFile, $key);
            chmod($keyFile, 0600);
        }
        self::$secretKey = trim(file_get_contents($keyFile));
        return self::$secretKey;
    }

    // ── Encode / Decode ──────────────────────────────────────────────────

    /**
     * Codifica un payload in un token firmato: base64(json).signature
     */
    public static function encode(array $payload): string {
        $payload['exp'] = $payload['exp'] ?? time() + self::TTL;
        $data = self::base64Encode(json_encode($payload));
        $sig  = hash_hmac(self::ALGO, $data, self::secret());
        return $data . self::SEPARATOR . $sig;
    }

    /**
     * Decodifica e verifica un token. Ritorna il payload o null se invalido/scaduto.
     */
    public static function decode(string $token): ?array {
        $parts = explode(self::SEPARATOR, $token, 2);
        if (count($parts) !== 2) return null;

        [$data, $sig] = $parts;

        // Verifica firma
        $expected = hash_hmac(self::ALGO, $data, self::secret());
        if (!hash_equals($expected, $sig)) return null;

        // Decodifica payload
        $payload = json_decode(self::base64Decode($data), true);
        if (!$payload) return null;

        // Verifica scadenza
        if (isset($payload['exp']) && $payload['exp'] < time()) return null;

        return $payload;
    }

    // ── Cookie helpers ───────────────────────────────────────────────────

    /**
     * Setta il cookie admin con i dati dell'utente.
     */
    public static function setAdminCookie(int $userId, string $username): void {
        $token = self::encode([
            'role'     => 'admin',
            'user_id'  => $userId,
            'username' => $username,
            'is_admin' => 1,
        ]);
        self::setCookie(self::COOKIE_ADMIN, $token);
    }

    /**
     * Setta il cookie player con i dati del giocatore.
     */
    public static function setPlayerCookie(int $playerId, string $username, string $roomCode): void {
        $token = self::encode([
            'role'      => 'player',
            'player_id' => $playerId,
            'username'  => $username,
            'room_code' => $roomCode,
        ]);
        self::setCookie(self::COOKIE_PLAYER, $token);
    }

    /**
     * Legge e verifica il cookie admin. Ritorna payload o null.
     */
    public static function getAdmin(): ?array {
        $token = $_COOKIE[self::COOKIE_ADMIN] ?? null;
        if (!$token) return null;
        $payload = self::decode($token);
        if (!$payload || ($payload['role'] ?? '') !== 'admin') return null;
        return $payload;
    }

    /**
     * Legge e verifica il cookie player. Ritorna payload o null.
     */
    public static function getPlayer(): ?array {
        $token = $_COOKIE[self::COOKIE_PLAYER] ?? null;
        if (!$token) return null;
        $payload = self::decode($token);
        if (!$payload || ($payload['role'] ?? '') !== 'player') return null;
        return $payload;
    }

    /**
     * Ritorna i dati di qualsiasi utente autenticato (admin o player).
     */
    public static function getAnyUser(): ?array {
        return self::getAdmin() ?? self::getPlayer();
    }

    /**
     * Cancella il cookie admin.
     */
    public static function clearAdminCookie(): void {
        self::clearCookie(self::COOKIE_ADMIN);
    }

    /**
     * Cancella il cookie player.
     */
    public static function clearPlayerCookie(): void {
        self::clearCookie(self::COOKIE_PLAYER);
    }

    /**
     * Cancella tutti i cookie auth.
     */
    public static function clearAll(): void {
        self::clearAdminCookie();
        self::clearPlayerCookie();
    }

    // ── Internal helpers ─────────────────────────────────────────────────

    private static function setCookie(string $name, string $value): void {
        setcookie($name, $value, [
            'expires'  => time() + self::TTL,
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
            'secure'   => false,  // true in produzione con HTTPS
        ]);
        // Rendi disponibile subito nella stessa richiesta
        $_COOKIE[$name] = $value;
    }

    private static function clearCookie(string $name): void {
        setcookie($name, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$name]);
    }

    private static function base64Encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64Decode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
