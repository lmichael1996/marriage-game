<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Room.php';

requireLogin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $roomCode = $input['room_code'] ?? '';
    
    if (!$roomCode) {
        throw new Exception('Codice stanza mancante');
    }
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Admin non autenticato');
    }
    
    $adminId = $_SESSION['user_id'];
    Room::createRoom($roomCode, $adminId);
    
    echo json_encode([
        'success' => true,
        'room_code' => strtoupper($roomCode)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
