<?php
session_start();

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/GameService.php';
require_once __DIR__ . '/../services/RoomService.php';
require_once __DIR__ . '/../services/AdminService.php';
require_once __DIR__ . '/../services/QuestionService.php';

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

// Initialize services
$auth = new AuthService();
$game = new GameService();
$room = new RoomService();
$admin = new AdminService();
$question = new QuestionService();

// Get endpoint from URL path
$endpoint = $_GET['endpoint'] ?? '';
$action = $_GET['action'] ?? '';

// Router
switch ($endpoint) {
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

    case 'start_room':
        handleStartRoom($room);
        break;

    case 'check_room_status':
        handleCheckRoomStatus($room);
        break;

    case 'delete_room':
        handleDeleteRoom($room);
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

        $result = $room->createRoom($questionSetId);

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

function handleStartRoom($room) {
    requireLogin();

    try {
        $roomCode = $_SESSION['room_code'] ?? null;

        if (!$roomCode) {
            throw new Exception('Nessuna stanza attiva nella sessione');
        }

        $result = $room->startRoom($roomCode);

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

function handleDeleteRoom($room) {
    requireAdmin();

    try {
        // Get active room from session or request
        $roomCode = $_SESSION['room_code'] ?? $_GET['room_code'] ?? null;

        if (!$roomCode) {
            throw new Exception('Codice stanza mancante');
        }

        // Delete the room completely
        $result = $room->deleteRoom($roomCode);

        // Clear room from session
        if ($result['success']) {
            unset($_SESSION['room_code']);
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
        echo json_encode([
            'success' => true,
            'devices' => is_array($result) ? $result : [],
            'count' => is_array($result) ? count($result) : 0
        ]);

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

        $result = $game->getActiveRound($questionSetId);
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
        // Get round_id from either GET or POST body
        $roundId = $_GET['round_id'] ?? 0;

        if (!$roundId && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = json_decode(file_get_contents('php://input'), true);
            $roundId = $postData['round_id'] ?? 0;
        }

        error_log("close_round called with round_id: $roundId");

        if (!$roundId) {
            error_log("close_round: round_id missing");
            echo json_encode([
                'success' => false,
                'message' => 'Round ID mancante'
            ]);
            exit();
        }

        try {
            $result = $game->closeRound($roundId);
            error_log("close_round result: " . json_encode($result));
            echo json_encode($result);
        } catch (Exception $e) {
            error_log("close_round exception: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit();
    }

    if ($action === 'reset_game') {
        requireAdmin();

        $postData = json_decode(file_get_contents('php://input'), true);
        $questionSetId = $postData['question_set_id'] ?? 0;
        $roomCode = $postData['room_code'] ?? '';

        if (!$questionSetId) {
            echo json_encode([
                'success' => false,
                'message' => 'Question Set ID mancante'
            ]);
            exit();
        }

        try {
            // Reset all rounds to pending and delete all player answers
            require_once __DIR__ . '/../repository/RoundRepo.php';
            $roundRepo = new RoundRepo();
            $resetResult = $roundRepo->resetRoundsBySetId($questionSetId);

            echo json_encode([
                'success' => true,
                'message' => 'Partita resettata con successo'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
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
?>
