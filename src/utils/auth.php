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
        header('Location: login-admin.php');
        exit();
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
        header('Location: login-player.php');
        exit();
    }
}
