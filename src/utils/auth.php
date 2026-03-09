<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(['lifetime' => 0, 'path' => '/']);
    session_start();
}

require_once __DIR__ . '/../services/ServiceLoader.php';

/**
 * Auth guards and helpers — PHP session authentication.
 * This file starts the session automatically on require.
 */

// ── Guards (redirect if not authenticated) ───────────────────────────────

function requireAdmin(): void {
    if (!svc('auth')->getAdmin()) {
        header('Location: login-admin.php');
        exit();
    }
}

function requirePlayer(): void {
    if (!svc('auth')->getPlayer()) {
        header('Location: login-player.php');
        exit();
    }
}

function requireJudge(): void {
    if (!svc('auth')->getJudge()) {
        header('Location: login-judge.php');
        exit();
    }
}

// ── API guards (JSON error if not authenticated) ─────────────────────────

function requireLoginJson(): void {
    if (!svc('auth')->getAnyUser()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Autenticazione richiesta'
        ]);
        exit();
    }
}

function requireAdminJson(): void {
    if (!svc('auth')->getAdmin()) {
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
    return svc('auth')->getJudge();
}

function authRoomId(): ?int {
    $auth = svc('auth');
    $session = $auth->getPlayer() ?? $auth->getJudge();
    return $session
        ? (int)$session['room_id']
        : (isset($_SESSION['room_id']) ? (int)$_SESSION['room_id'] : null);
}

function authUsername(): ?string {
    return svc('auth')->getAnyUser()['username'] ?? null;
}

function authPlayerId(): ?int {
    return ($p = svc('auth')->getPlayer()) ? (int)$p['player_id'] : null;
}

function authUserId(): ?int {
    return ($a = svc('auth')->getAdmin()) ? (int)$a['user_id'] : null;
}
