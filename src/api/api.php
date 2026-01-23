<?php
session_start();

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/GameController.php';
require_once __DIR__ . '/../controllers/RoomController.php';
require_once __DIR__ . '/../controllers/AdminController.php';

header('Content-Type: application/json');

// Helper function to check if user is logged in
function requireLogin() {
    // Accept both admin (user_id) and player (player_id)
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['player_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Autenticazione richiesta'
        ]);
        exit();
    }
}

// Helper function to check if user is admin
function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Accesso riservato agli amministratori'
        ]);
        exit();
    }
}

// Initialize controllers
$auth = new AuthController();
$game = new GameController();
$room = new RoomController();
$admin = new AdminController();

// Get endpoint from URL path
$endpoint = $_GET['endpoint'] ?? '';
$action = $_GET['action'] ?? '';

// Router
switch ($endpoint) {
    case 'login':
        handleLogin($auth);
        break;
    
    case 'player_login':
        handlePlayerLogin($auth);
        break;
    
    case 'admin_login':
        handleAdminLogin($auth);
        break;
    
    case 'answer':
        handleAnswer($action, $game);
        break;
    
    case 'create_room':
        handleCreateRoom($room);
        break;
    
    case 'check_room_status':
        handleCheckRoomStatus($room);
        break;
    
    case 'close_room':
        handleCloseRoom($room);
        break;
    
    case 'connected_devices':
        handleConnectedDevices($room);
        break;
    
    case 'game':
        handleGame($action, $game, $room, $admin);
        break;
    
    case 'leaderboard':
        handleLeaderboard($game);
        break;
    
    case 'timer':
        handleTimer($admin);
        break;
    
    case 'admin':
        handleAdmin($action, $admin);
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

function handleLogin($auth) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Metodo non consentito'
        ]);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $roomCode = $data['room_code'] ?? '';
    
    $result = $auth->login($username, $password, $roomCode);
    
    echo json_encode($result);
}

function handlePlayerLogin($auth) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Metodo non consentito'
        ]);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = $data['username'] ?? '';
    $roomCode = $data['room_code'] ?? '';
    
    $result = $auth->playerLogin($username, $roomCode);
    
    echo json_encode($result);
}

function handleAdminLogin($auth) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Metodo non consentito'
        ]);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    $result = $auth->adminLogin($username, $password);
    
    echo json_encode($result);
}

function handleAnswer($action, $game) {
    requireLogin();
    
    // Accept both with and without action parameter for backward compatibility
    if ($action === 'submit' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $round_id = $data['round_id'] ?? 0;
        $answer = $data['answer'] ?? 0;
        $time_taken = $data['time_taken'] ?? 0;
        
        // Use player_id if available (for players), otherwise user_id (for admins in testing)
        $user_id = $_SESSION['player_id'] ?? $_SESSION['user_id'] ?? 0;
        
        if (!$user_id) {
            echo json_encode([
                'success' => false,
                'error' => 'User ID non trovato nella sessione'
            ]);
            exit();
        }
        
        $result = $game->submitAnswer($user_id, $round_id, $answer, $time_taken);
        echo json_encode($result);
        exit();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Azione non valida'
    ]);
}

function handleCreateRoom($room) {
    requireLogin();
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $questionSetId = $input['question_set_id'] ?? null;
        
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Admin non autenticato');
        }
        
        $userId = $_SESSION['user_id'];
        $result = $room->createRoom($userId, $questionSetId);
        
        // Save room_code in session for admin panel
        if ($result['success'] && isset($result['room_code'])) {
            $_SESSION['room_code'] = $result['room_code'];
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleCheckRoomStatus($room) {
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

function handleCloseRoom($room) {
    requireAdmin();
    
    try {
        // Get active room from session or request
        $roomCode = $_SESSION['room_code'] ?? $_GET['room_code'] ?? null;
        
        if (!$roomCode) {
            throw new Exception('Codice stanza mancante');
        }
        
        $result = $room->closeRoom($roomCode);
        
        // Set a flag to signal room closure to connected players
        $flagFile = sys_get_temp_dir() . '/marriage_game_room_closed.flag';
        file_put_contents($flagFile, time());
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleConnectedDevices($room) {
    requireAdmin();
    
    try {
        // Get room code from session or request
        $roomCode = $_SESSION['room_code'] ?? $_GET['room_code'] ?? null;
        
        if (!$roomCode) {
            echo json_encode([
                'success' => true,
                'devices' => [],
                'count' => 0
            ]);
            exit();
        }
        
        $result = $room->getRoomPlayers($roomCode);
        echo json_encode($result);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleGame($action, $game, $room, $admin) {
    requireLogin();
    
    if ($action === 'get_game_state') {
        $questionSetId = null;
        
        // If player is logged in, get their room's question set
        if (isset($_SESSION['room_code'])) {
            $questionSetId = $room->getQuestionSetIdByRoomCode($_SESSION['room_code']);
        }
        
        $result = $game->getGameState($questionSetId);
        echo json_encode($result);
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
        
        $result = $admin->getSetQuestions($setId);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'set' => $result['questions'],
                'questions' => $result['questions']['questions'] ?? []
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['error'] ?? 'Set non trovato'
            ]);
        }
        exit();
    }
    
    if ($action === 'start_round') {
        // Get round_id from either GET or POST body
        $roundId = $_GET['round_id'] ?? 0;
        
        if (!$roundId && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = json_decode(file_get_contents('php://input'), true);
            $roundId = $postData['round_id'] ?? 0;
        }
        
        if (!$roundId) {
            echo json_encode([
                'success' => false,
                'message' => 'Round ID mancante'
            ]);
            exit();
        }
        
        $result = $game->startRound($roundId);
        echo json_encode($result);
        exit();
    }
    
    if ($action === 'close_round') {
        $roundId = $_GET['round_id'] ?? 0;
        
        if (!$roundId) {
            echo json_encode([
                'success' => false,
                'message' => 'Round ID mancante'
            ]);
            exit();
        }
        
        $result = $game->closeRound($roundId);
        echo json_encode($result);
        exit();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Azione non valida'
    ]);
}

function handleLeaderboard($game) {
    requireLogin();
    
    $roomCode = $_SESSION['room_code'] ?? $_GET['room_code'] ?? null;
    $result = $game->getLeaderboard($roomCode);
    
    echo json_encode($result);
}

function handleTimer($admin) {
    requireAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if ($action === 'update_timer') {
            $seconds = intval($data['seconds'] ?? 10);
            $result = $admin->updateTimer($seconds);
            echo json_encode($result);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $admin->getTimer();
        echo json_encode($result);
    }
}

function handleAdmin($action, $admin) {
    requireAdmin();
    
    switch ($action) {
        case 'get_question_sets':
            $result = $admin->getAllQuestionSets();
            echo json_encode($result);
            break;
            
        case 'search_question_sets':
            $query = $_GET['query'] ?? '';
            $result = $admin->searchQuestionSets($query);
            echo json_encode($result);
            break;
            
        case 'get_question_set':
            $setId = intval($_GET['set_id'] ?? 0);
            $result = $admin->getQuestionSetById($setId);
            echo json_encode($result);
            break;
            
        case 'get_question_set_rounds':
            $setId = intval($_GET['set_id'] ?? 0);
            $result = $admin->getQuestionSetRounds($setId);
            echo json_encode($result);
            break;
            
        case 'create_set_with_questions':
            $data = json_decode(file_get_contents('php://input'), true);
            $setName = $data['set_name'] ?? '';
            $setDescription = $data['set_description'] ?? '';
            $questions = $data['questions'] ?? [];
            $result = $admin->createSetWithQuestions($setName, $setDescription, $questions);
            echo json_encode($result);
            break;
            
        case 'update_set_with_questions':
            $data = json_decode(file_get_contents('php://input'), true);
            $setId = intval($data['set_id'] ?? 0);
            $setName = $data['set_name'] ?? '';
            $setDescription = $data['set_description'] ?? '';
            $questions = $data['questions'] ?? [];
            $result = $admin->updateSetWithQuestions($setId, $setName, $setDescription, $questions);
            echo json_encode($result);
            break;
            
        case 'delete_question_set':
            $data = json_decode(file_get_contents('php://input'), true);
            $setId = intval($data['set_id'] ?? 0);
            $result = $admin->deleteQuestionSet($setId);
            echo json_encode($result);
            break;
            
        case 'save_settings':
            $data = json_decode(file_get_contents('php://input'), true);
            $settingsData = $data['settings'] ?? [];
            $result = $admin->saveSettings($settingsData);
            echo json_encode($result);
            break;
            
        case 'get_settings':
            $result = $admin->getSettings();
            echo json_encode($result);
            break;
            
        case 'update_credentials':
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = intval($data['user_id'] ?? 0);
            $newUsername = $data['new_username'] ?? '';
            $newPassword = $data['new_password'] ?? null;
            $confirmPassword = $data['confirm_password'] ?? null;
            $result = $admin->updateCredentials($userId, $newUsername, $newPassword, $confirmPassword);
            echo json_encode($result);
            break;
            
        case 'start_round':
            $data = json_decode(file_get_contents('php://input'), true);
            $roundId = intval($data['round_id'] ?? 0);
            $result = $game->startRound($roundId);
            echo json_encode($result);
            break;
            
        case 'close_round':
            $data = json_decode(file_get_contents('php://input'), true);
            $roundId = intval($data['round_id'] ?? 0);
            $result = $game->closeRound($roundId);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Admin action not found'
            ]);
            break;
    }
}
?>
