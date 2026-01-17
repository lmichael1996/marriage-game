<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Room.php';

requireAdmin();

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Get active room code
    $room = Room::getActiveRoom();
    
    if (!$room) {
        echo json_encode([
            'success' => true,
            'devices' => [],
            'count' => 0
        ]);
        exit();
    }
    
    $roomCode = $room['code'];
    
    // Get all players for this room
    $stmt = $db->prepare("
        SELECT 
            id,
            username,
            'online' as status,
            last_seen
        FROM players 
        WHERE room_code = ?
        ORDER BY connected_at ASC
    ");
    
    $stmt->bind_param("s", $roomCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $devices = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'devices' => $devices,
        'count' => count($devices)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
