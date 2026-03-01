<?php
require_once __DIR__ . '/../services/TokenService.php';

/**
 * Auth guard functions — Autenticazione via cookie HMAC.
 *
 * I cookie admin_token / player_token sono indipendenti,
 * quindi admin e player possono coesistere nello stesso browser.
 * Le sessioni PHP restano attive solo per stato UI (tabs, counter, ecc.)
 */

/**
 * Assicura che la sessione PHP sia avviata (per stato UI).
 */
function ensureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is admin (cookie-based).
 * Redirect to admin login if not authenticated as admin.
 */
function requireAdmin(): void {
    ensureSession();
    if (!TokenService::getAdmin()) {
        header('Location: login-admin.php');
        exit();
    }
}

/**
 * Check if user is player (cookie-based).
 * Redirect to player login if not authenticated as player.
 */
function requirePlayer(): void {
    ensureSession();
    if (!TokenService::getPlayer()) {
        header('Location: login-player.php');
        exit();
    }
}

/**
 * API: Check if user is logged in (admin or player).
 * Returns JSON error if not authenticated.
 */
function requireLoginJson(): void {
    ensureSession();
    if (!TokenService::getAnyUser()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Autenticazione richiesta'
        ]);
        exit();
    }
}

/**
 * API: Check if user is admin.
 * Returns JSON error if not authenticated as admin.
 */
function requireAdminJson(): void {
    ensureSession();
    if (!TokenService::getAdmin()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Accesso riservato agli amministratori'
        ]);
        exit();
    }
}

// ── Convenience getters ──────────────────────────────────────────────────

/** Ritorna il payload admin dal cookie, o null. */
function authAdmin(): ?array {
    return TokenService::getAdmin();
}

/** Ritorna il payload player dal cookie, o null. */
function authPlayer(): ?array {
    return TokenService::getPlayer();
}

/** Ritorna il payload di qualsiasi utente autenticato, o null. */
function authUser(): ?array {
    return TokenService::getAnyUser();
}

/**
 * Ritorna il room_code dal cookie player, dalla sessione, o da GET/input.
 * Utile nei contesti API dove il room_code può venire da più fonti.
 */
function authRoomCode(): ?string {
    // 1. Cookie player (fonte primaria per i player)
    $player = TokenService::getPlayer();
    if ($player) return $player['room_code'];

    // 2. Sessione (code_player per admin che gestisce stanza)
    return $_SESSION['code_player'] ?? null;
}

/**
 * Ritorna lo username dal cookie (admin o player).
 */
function authUsername(): ?string {
    $user = TokenService::getAnyUser();
    return $user['username'] ?? null;
}

/**
 * Ritorna il player_id dal cookie player.
 */
function authPlayerId(): ?int {
    $player = TokenService::getPlayer();
    return $player ? (int)$player['player_id'] : null;
}

/**
 * Ritorna lo user_id dal cookie admin.
 */
function authUserId(): ?int {
    $admin = TokenService::getAdmin();
    return $admin ? (int)$admin['user_id'] : null;
}

