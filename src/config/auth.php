<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['player_id']);
}

// Check if user is admin
function isAdmin() {
    // Admin = logged in as user WITHOUT player_id
    return isset($_SESSION['user_id']) && !isset($_SESSION['player_id']);
}

// Check if user is player
function isPlayer() {
    // Player = logged in with player_id and room_code
    return isset($_SESSION['player_id']) && isset($_SESSION['room_code']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        header('Location: ' . $scriptDir . '/login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        header('Location: ' . $scriptDir . '/player.php');
        exit();
    }
}
?>
