<?php
require_once __DIR__ . '/../services/ServiceLoader.php';

/**
 * Auth guard functions — Autenticazione via sessione PHP.
 *
 * Le chiavi auth_admin / auth_player / auth_judge sono indipendenti in sessione,
 * quindi admin, player e judge possono coesistere nello stesso browser.
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

function authAdmin(): ?array {
    return svc('auth')->getAdmin();
}

function authPlayer(): ?array {
    return svc('auth')->getPlayer();
}

function authJudge(): ?array {
    return svc('auth')->getJudge();
}

function authUser(): ?array {
    return svc('auth')->getAnyUser();
}

function authRoomCode(): ?string {
    $player = svc('auth')->getPlayer();
    if ($player) return $player['room_code'];
    return $_SESSION['code_player'] ?? null;
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
