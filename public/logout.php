<?php
require_once __DIR__ . '/../src/utils/auth.php';

$role = $_GET['role'] ?? null;

// If a specific role is provided and valid, log out only that role. Otherwise, log out all roles.
if ($role && ($role === 'admin' || $role === 'player' || $role === 'judge')) {
    // Logout specific role
    Container::auth()->logout($role);

    $redirect = match ($role) {
        'admin'  => BASE_URL . 'public/login-admin.php',
        'player' => BASE_URL . 'public/login-player.php',
        'judge'  => BASE_URL . 'public/login-judge.php',
    };
} else {
    // Total logout (all roles)
    Container::auth()->logout();
    $redirect = BASE_URL . 'index.html';
}

header('Location: ' . $redirect);
exit();
