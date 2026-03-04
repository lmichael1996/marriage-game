<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 86400);   // 24 ore
    session_set_cookie_params(86400);            // cookie dura 24 ore
    session_start();
}

require_once __DIR__ . '/../services/ServiceLoader.php';

/**
 * Auth guard e helper — Autenticazione via sessione PHP.
 * Questo file avvia la sessione automaticamente al require.
 */

// ── Guards (redirect se non autenticato) ─────────────────────────────────

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

// ── API guards (JSON error se non autenticato) ──────────────────────────

function requireLoginJson(): void {
    if (!svc('auth')->getAnyUser()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Autenticazione richiesta']);
        exit();
    }
}

function requireAdminJson(): void {
    if (!svc('auth')->getAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accesso riservato agli amministratori']);
        exit();
    }
}

// ── Convenience getters ──────────────────────────────────────────────────

function authJudge(): ?array {
    return svc('auth')->getJudge();
}

function authRoomCode(): ?string {
    $player = svc('auth')->getPlayer();
    if ($player) return $player['room_code'];
    $judge = svc('auth')->getJudge();
    if ($judge) return $judge['room_code'];
    return null;
}

function authUsername(): ?string {
    $user = svc('auth')->getAnyUser();
    return $user['username'] ?? null;
}

function authPlayerId(): ?int {
    $player = svc('auth')->getPlayer();
    return $player ? (int)$player['player_id'] : null;
}

function authUserId(): ?int {
    $admin = svc('auth')->getAdmin();
    return $admin ? (int)$admin['user_id'] : null;
}

function authJudgeId(): ?int {
    $judge = svc('auth')->getJudge();
    return $judge ? (int)$judge['judge_id'] : null;
}
