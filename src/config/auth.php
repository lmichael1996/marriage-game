<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
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
