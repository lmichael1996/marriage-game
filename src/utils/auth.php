<?php

/**
 * Check if user is admin
 * Redirect to admin login if not authenticated as admin
 */
function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in_via_login']) ||
        !isset($_SESSION['user_id']) ||
        isset($_SESSION['player_id'])) {
        error_log("requireAdmin REDIRECT - Session: " . json_encode($_SESSION));
        // header('Location: login-admin.php');
        // exit();
    }
}

/**
 * Check if user is player
 * Redirect to player login if not authenticated as player
 */
function requirePlayer() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in_via_login']) ||
        !isset($_SESSION['player_id']) ||
        !isset($_SESSION['room_code'])) {
        error_log("requirePlayer REDIRECT - Session: " . json_encode($_SESSION));
        // header('Location: login-player.php');
        // exit();
    }
}

/**
 * API: Check if user is logged in (admin or player)
 * Returns JSON error if not authenticated
 */
function requireLoginJson() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Accept both admin (user_id) and player (player_id)
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['player_id'])) {
        error_log("requireLoginJson FAILED - Session data: " . json_encode($_SESSION));
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Autenticazione richiesta'
        ]);
        exit();
    }
}

/**
 * API: Check if user is admin
 * Returns JSON error if not authenticated as admin
 */
function requireAdminJson() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    requireLoginJson();

    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Accesso riservato agli amministratori'
        ]);
        exit();
    }
}

