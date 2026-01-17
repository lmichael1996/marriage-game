<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

header('Content-Type: application/json');

try {
    $flagFile = sys_get_temp_dir() . '/marriage_game_room_closed.flag';
    
    // Check if room was closed in the last 5 seconds
    if (file_exists($flagFile)) {
        $closedTime = (int)file_get_contents($flagFile);
        $currentTime = time();
        
        if (($currentTime - $closedTime) < 5) {
            // Room was just closed, signal logout
            echo json_encode([
                'room_open' => false,
                'message' => 'La stanza è stata chiusa dall\'admin'
            ]);
            return;
        } else {
            // Old flag, remove it
            unlink($flagFile);
        }
    }
    
    echo json_encode([
        'room_open' => true
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
