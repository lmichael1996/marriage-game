<?php
session_start();
require_once '../config/database.php';
require_once '../models/Room.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Get active room
    $room = Room::getActiveRoom();
    
    if ($room) {
        $roomCode = $room['code'];
        
        // Delete all players for this room
        $stmt = $db->prepare("DELETE FROM players WHERE room_code = ?");
        $stmt->bind_param("s", $roomCode);
        $stmt->execute();
        $stmt->close();
    }
    
    // Close the active room
    Room::closeRoom();
    
    // Set a flag to signal room closure to connected players
    $flagFile = sys_get_temp_dir() . '/marriage_game_room_closed.flag';
    file_put_contents($flagFile, time());
    
    echo json_encode([
        'success' => true,
        'message' => 'Stanza chiusa. Tutti i giocatori sono stati rimossi.'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

