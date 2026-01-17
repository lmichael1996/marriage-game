<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repository/Room.php';
require_once __DIR__ . '/../repository/Round.php';
require_once __DIR__ . '/../repository/PlayerAnswer.php';
require_once __DIR__ . '/../repository/QuestionSet.php';

header('Content-Type: application/json');

// Get endpoint from URL path
$endpoint = $_GET['endpoint'] ?? '';
$action = $_GET['action'] ?? '';

// Router
switch ($endpoint) {
    case 'answer':
        handleAnswer($action);
        break;
    
    case 'create_room':
        handleCreateRoom();
        break;
    
    case 'check_room_status':
        handleCheckRoomStatus();
        break;
    
    case 'close_room':
        handleCloseRoom();
        break;
    
    case 'connected_devices':
        handleConnectedDevices();
        break;
    
    case 'game':
        handleGame($action);
        break;
    
    case 'leaderboard':
        handleLeaderboard();
        break;
    
    case 'timer':
        handleTimer();
        break;
    
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint non trovato'
        ]);
        break;
}

// ============================================================================
// ENDPOINT HANDLERS
// ============================================================================

function handleAnswer($action) {
    requireLogin();
    
    if ($action === 'submit') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $round_id = $data['round_id'] ?? 0;
        $answer = $data['answer'] ?? 0;
        $time_taken = $data['time_taken'] ?? 0;
        $user_id = $_SESSION['user_id'];
        
        $playerAnswerModel = new PlayerAnswer();
        $roundModel = new Round();
        
        // Check if user already answered this round
        if ($playerAnswerModel->hasAnswered($round_id, $user_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Hai già risposto a questo round'
            ]);
            exit();
        }
        
        // Get correct answer
        $round = $roundModel->getRoundById($round_id);
        
        if (!$round) {
            echo json_encode([
                'success' => false,
                'message' => 'Round non trovato'
            ]);
            exit();
        }
        
        $is_correct = ($answer == $round['correct_answer']) ? 1 : 0;
        
        // Save answer
        $playerAnswerModel->submitAnswer($round_id, $user_id, $answer, $time_taken, $is_correct);
        
        echo json_encode([
            'success' => true,
            'is_correct' => $is_correct
        ]);
        exit();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Azione non valida'
    ]);
}

function handleCreateRoom() {
    requireLogin();
    
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
}

function handleCheckRoomStatus() {
    requireLogin();
    
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
}

function handleCloseRoom() {
    requireAdmin();
    
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
}

function handleConnectedDevices() {
    requireAdmin();
    
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
}

function handleGame($action) {
    requireLogin();
    
    $roundModel = new Round();
    
    if ($action === 'get_game_state') {
        $activeRound = $roundModel->getActiveRound();
        
        echo json_encode([
            'success' => true,
            'active_round' => $activeRound
        ]);
        exit();
    }
    
    if ($action === 'get_set') {
        $setId = $_GET['set_id'] ?? 0;
        
        if (!$setId) {
            echo json_encode([
                'success' => false,
                'message' => 'Set ID mancante'
            ]);
            exit();
        }
        
        $questionSetModel = new QuestionSet();
        $set = $questionSetModel->getSetById($setId);
        
        if ($set) {
            echo json_encode([
                'success' => true,
                'set' => $set,
                'rounds' => $set['rounds'] ?? []
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Set non trovato'
            ]);
        }
        exit();
    }
    
    if ($action === 'start_round') {
        $roundId = $_GET['round_id'] ?? 0;
        
        if (!$roundId) {
            echo json_encode([
                'success' => false,
                'message' => 'Round ID mancante'
            ]);
            exit();
        }
        
        $success = $roundModel->startRound($roundId);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Round avviato' : 'Errore nell\'avvio del round'
        ]);
        exit();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Azione non valida'
    ]);
}

function handleLeaderboard() {
    requireLogin();
    
    $playerAnswerModel = new PlayerAnswer();
    $leaderboard = $playerAnswerModel->getLeaderboard();
    
    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard
    ]);
}

function handleTimer() {
    requireAdmin();
    
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
}
?>
