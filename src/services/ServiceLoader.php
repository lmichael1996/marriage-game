<?php
require_once __DIR__ . '/TokenService.php';
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/GameService.php';
require_once __DIR__ . '/RoomService.php';
require_once __DIR__ . '/AdminService.php';
require_once __DIR__ . '/QuestionService.php';

$services = [];

function svc(string $name) {
    global $services;
    return $services[$name] ??= match ($name) {
        'auth'     => new AuthService(),
        'game'     => new GameService(),
        'room'     => new RoomService(),
        'admin'    => new AdminService(),
        'question' => new QuestionService(),
        default    => throw new Exception("Servizio '$name' non trovato"),
    };
}
