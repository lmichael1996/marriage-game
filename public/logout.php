<?php
require_once __DIR__ . '/../src/utils/auth.php';

$role = $_GET['role'] ?? null;

// If a specific role is provided and valid, log out only that role. Otherwise, log out all roles.
if ($role && ($role === 'admin' || $role === 'player' || $role === 'judge')) {
    // Logout specific role
    Container::auth()->logout($role);

    $redirect = match ($role) {
        'admin'  => 'login-admin.php',
        'player' => 'login-player.php',
        'judge'  => 'login-judge.php',
    };
} else {
    // Total logout (all roles)
    Container::auth()->logout();
    $redirect = '../index.html';
}

header('Location: ' . $redirect);
exit();
