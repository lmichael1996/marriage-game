<?php
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/GameService.php';
require_once __DIR__ . '/RoomService.php';
require_once __DIR__ . '/QuestionService.php';
require_once __DIR__ . '/SetService.php';
require_once __DIR__ . '/TableService.php';
require_once __DIR__ . '/SettingsService.php';

$services = [];

function svc(string $name) {
    global $services;
    return $services[$name] ??= match ($name) {
        'auth'     => new AuthService(),
        'game'     => new GameService(),
        'room'     => new RoomService(),
        'question' => new QuestionService(),
        'set'      => new SetService(),
        'table'    => new TableService(),
        'settings' => new SettingsService(),
        default    => throw new Exception("Service '$name' not found"),
    };
}
