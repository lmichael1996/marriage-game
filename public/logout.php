<?php
require_once __DIR__ . '/../src/utils/auth.php';

$role = $_GET['role'] ?? null;

if ($role && in_array($role, ['admin', 'player', 'judge'])) {
    // Logout singolo ruolo
    Container::auth()->logout($role);

    $redirect = match ($role) {
        'admin'  => 'login-admin.php',
        'player' => 'login-player.php',
        'judge'  => 'login-judge.php',
    };
} else {
    // Logout totale (distrugge sessione)
    Container::auth()->logout();
    $redirect = '../index.html';
}

header('Location: ' . $redirect);
exit();
