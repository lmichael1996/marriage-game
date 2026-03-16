<?php
if (session_status() === PHP_SESSION_NONE) {
    // Dedicated save path so other apps' GC won't purge our sessions
    $savePath = dirname(__DIR__, 2) . '/storage/sessions';
    if (!is_dir($savePath)) {
        mkdir($savePath, 0700, true);
    }
    ini_set('session.save_path', $savePath);
    ini_set('session.gc_maxlifetime', 86400);   // 24 h server-side
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);

    session_set_cookie_params([
        'lifetime' => 86400,  // 24 h cookie (survives browser close)
        'path'     => '/',
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Refresh cookie expiry on every request so it never goes stale
    setcookie(session_name(), session_id(), [
        'expires'  => time() + 86400,
        'path'     => '/',
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/../services/ServiceLoader.php';

/**
 * Auth guards and helpers — PHP session authentication.
 * This file starts the session automatically on require.
 */

// ── Guards (redirect if not authenticated) ───────────────────────────────

function requireAdmin(): void {
    if (!Container::auth()->getAdmin()) {
        header('Location: login-admin.php');
        exit();
    }
}

function requirePlayer(): void {
    if (!Container::auth()->getPlayer()) {
        header('Location: login-player.php');
        exit();
    }
}

function requireJudge(): void {
    if (!Container::auth()->getJudge()) {
        header('Location: login-judge.php');
        exit();
    }
}

// ── API guards (JSON error if not authenticated) ─────────────────────────

function requireLoginJson(): void {
    if (!Container::auth()->getAnyUser()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Autenticazione richiesta'
        ]);
        exit();
    }
}

function requireAdminJson(): void {
    if (!Container::auth()->getAdmin()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Accesso admin richiesto'
        ]);
        exit();
    }
}

// ── Convenience getters ──────────────────────────────────────────────────

function authJudge(): ?array {
    return Container::auth()->getJudge();
}

function authRoomId(): ?int {
    $auth = Container::auth();

    // Admin: use session room_id (set when room is created)
    if ($auth->getAdmin() && isset($_SESSION['room_id'])) {
        return (int)$_SESSION['room_id'];
    }

    // Player or judge: room_id is in their auth session
    $session = $auth->getPlayer() ?? $auth->getJudge();
    return $session ? (int)$session['room_id'] : null;
}

function authUsername(): ?string {
    return Container::auth()->getAnyUser()['username'] ?? null;
}

function authPlayerId(): ?int {
    return ($p = Container::auth()->getPlayer()) ? (int)$p['player_id'] : null;
}

function authUserId(): ?int {
    return ($a = Container::auth()->getAdmin()) ? (int)$a['user_id'] : null;
}
