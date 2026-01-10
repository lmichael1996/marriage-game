<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'update_timer') {
        $seconds = intval($data['seconds'] ?? 10);
        
        // Validate timer value (between 5 and 60 seconds)
        if ($seconds < 5) $seconds = 5;
        if ($seconds > 60) $seconds = 60;
        
        $stmt = $conn->prepare("UPDATE game_state SET timer_seconds = ? WHERE id = 1");
        $stmt->bind_param("i", $seconds);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            echo json_encode(['success' => true, 'timer_seconds' => $seconds]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update timer']);
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get current timer value
    $result = $conn->query("SELECT timer_seconds FROM game_state WHERE id = 1");
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'timer_seconds' => $row['timer_seconds'] ?? 10
    ]);
}

$conn->close();
?>
