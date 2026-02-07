<?php
session_start();

require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/GameService.php';
require_once __DIR__ . '/../services/RoomService.php';
require_once __DIR__ . '/../services/AdminService.php';
require_once __DIR__ . '/../services/QuestionService.php';

header('Content-Type: application/json');

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
        handleGame($action, $game, $room, $question);
        break;

    case 'leaderboard':
        handleLeaderboard($game);
        break;

    case 'round_answers':
        handleRoundAnswers($game);
        break;

    case 'final_leaderboard':
        handleFinalLeaderboard($game);
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

    try {
        $result = $auth->playerLogin($username, $roomCode);

        echo json_encode([
            'success' => true,
            'redirect' => '../public/player.php'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
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

    try {
        $result = $auth->adminLogin($username, $password);

        echo json_encode([
            'success' => true,
            'redirect' => '../public/admin.php'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleAnswer($action, $game) {
    requireLoginJson();

    if ($action === 'submit' || $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $round_number = $data['round_number'] ?? 0;
        $answer = $data['answer'] ?? 0;
        $time_taken = $data['time_taken'] ?? 0;

        $user_id = $_SESSION['player_id'] ?? $_SESSION['user_id'] ?? 0;

        if (!$user_id) {
            echo json_encode([
                'success' => false,
                'error' => 'User ID non trovato nella sessione'
            ]);
            exit();
        }

        if (!$round_number) {
            echo json_encode([
                'success' => false,
                'error' => 'Round number mancante'
            ]);
            exit();
        }

        $result = $game->submitAnswer($user_id, $round_number, $answer, $time_taken);
        echo json_encode($result);
        exit();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Azione non valida'
    ]);
}

function handleCreateRoom($room) {
    requireLoginJson();

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
    requireLoginJson();

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
    requireLoginJson();

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
    requireAdminJson();

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
    requireAdminJson();

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

        try {
            $result = $room->getRoomPlayers($roomCode);
        } catch (Exception $e) {
            // If there's an error (like missing column), return empty list
            $result = [];
        }

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
}function handleGame($action, $game, $room, $question) {
    requireLoginJson();

    if ($action === 'get_game_state') {
        $questionSetId = null;

        // If player is logged in, get their room's question set
        if (isset($_SESSION['room_code'])) {
            $questionSetId = $room->getQuestionSetIdByRoomCode($_SESSION['room_code']);
        }

        $result = $game->getActiveRound($questionSetId);

        if ($result) {
            // Active round found
            echo json_encode($result);
        } else {
            // No active round
            echo json_encode(['success' => false]);
        }
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

        $result = $question->getSetQuestions($setId);

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
        requireAdminJson();

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
    requireLoginJson();

    $roomCode = $_SESSION['room_code'] ?? $_GET['room_code'] ?? null;
    $result = $game->getLeaderboard($roomCode);

    echo json_encode($result);
}

function handleRoundAnswers($game) {
    requireLoginJson();

    $roundId = $_GET['round_id'] ?? 0;

    if (!$roundId) {
        echo json_encode([
            'success' => false,
            'top_answers' => [],
            'message' => 'Round ID mancante'
        ]);
        return;
    }

    // Get answers from AnswerRepo
    require_once __DIR__ . '/../repository/AnswerRepo.php';
    $answerRepo = new AnswerRepo();
    $answers = $answerRepo->getTopFastestAnswers($roundId, 10);

    echo json_encode([
        'success' => true,
        'top_answers' => $answers
    ]);
}

function handleFinalLeaderboard($game) {
    requireLoginJson();

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    $roomCode = $_SESSION['room_code'] ?? null;

    if (!$roomCode) {
        echo json_encode([
            'success' => false,
            'leaderboard' => [],
            'message' => 'Room code mancante'
        ]);
        return;
    }

    // Get room and all rounds
    require_once __DIR__ . '/../repository/RoomRepo.php';
    require_once __DIR__ . '/../repository/RoundRepo.php';
    require_once __DIR__ . '/../repository/AnswerRepo.php';

    $roomRepo = new RoomRepo();
    $roundRepo = new RoundRepo();
    $answerRepo = new AnswerRepo();

    $room = $roomRepo->getRoomByCode($roomCode);
    if (!$room) {
        echo json_encode([
            'success' => false,
            'leaderboard' => [],
            'message' => 'Room non trovata'
        ]);
        return;
    }

    // Get all rounds for this room
    $allRounds = $roundRepo->getRoundsByRoom($room['id']);

    // Get scoring system from game_settings database
    require_once __DIR__ . '/../config/database.php';
    $settingsConn = getDBConnection();

    // Default scoring system
    $defaultScores = [
        'clickfirst' => [1 => 50],
        'multiple' => [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1],
        'truefalse' => [1 => 20, 2 => 15, 3 => 12, 4 => 10, 5 => 8, 6 => 6, 7 => 5, 8 => 3, 9 => 2, 10 => 1]
    ];

    // Load points from game_settings table if available
    $scoreMap = $defaultScores;

    try {
        $settingKeys = [
            'points_clickfirst',
            'points_mult_1st', 'points_mult_2nd', 'points_mult_3rd', 'points_mult_4th', 'points_mult_5th',
            'points_mult_6th', 'points_mult_7th', 'points_mult_8th', 'points_mult_9th', 'points_mult_10th',
            'points_tf_1st', 'points_tf_2nd', 'points_tf_3rd', 'points_tf_4th', 'points_tf_5th',
            'points_tf_6th', 'points_tf_7th', 'points_tf_8th', 'points_tf_9th', 'points_tf_10th'
        ];

        $placeholders = implode(',', array_fill(0, count($settingKeys), '?'));
        $stmt = $settingsConn->prepare("SELECT setting_key, setting_value FROM game_settings WHERE setting_key IN ($placeholders)");

        // Bind parameters dynamically
        $types = str_repeat('s', count($settingKeys));
        $stmt->bind_param($types, ...$settingKeys);
        $stmt->execute();
        $result = $stmt->get_result();

        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = (int)$row['setting_value'];
        }
        $stmt->close();

        // Override default scores with database values if available
        if (isset($settings['points_clickfirst'])) {
            $scoreMap['clickfirst'][1] = $settings['points_clickfirst'];
        }

        for ($i = 1; $i <= 10; $i++) {
            $posStr = $i === 1 ? '1st' : ($i === 2 ? '2nd' : ($i === 3 ? '3rd' : ($i === 4 ? '4th' : ($i === 5 ? '5th' : ($i === 6 ? '6th' : ($i === 7 ? '7th' : ($i === 8 ? '8th' : ($i === 9 ? '9th' : '10th'))))))));
            $multKey = 'points_mult_' . $posStr;
            $tfKey = 'points_tf_' . $posStr;

            if (isset($settings[$multKey])) {
                $scoreMap['multiple'][$i] = $settings[$multKey];
            }
            if (isset($settings[$tfKey])) {
                $scoreMap['truefalse'][$i] = $settings[$tfKey];
            }
        }
    } catch (Exception $e) {
        error_log("Failed to load game settings, using defaults: " . $e->getMessage());
    }

    // Calculate total scores per player
    $playerScores = [];

    foreach ($allRounds as $round) {
        $roundId = $round['id'];
        // Get game type from the associated question through qset_questions
        // For now, default to 'multiple' as all rounds track answers the same way
        $gameType = 'multiple';

        // Get top 10 answers for this round
        $topAnswers = $answerRepo->getTopFastestAnswers($roundId, 10);

        foreach ($topAnswers as $index => $answer) {
            $username = $answer['username'];
            $position = $index + 1;

            // Get points for this position and game type
            $points = $scoreMap[$gameType][$position] ?? 0;

            if (!isset($playerScores[$username])) {
                $playerScores[$username] = 0;
            }

            $playerScores[$username] += $points;
        }
    }

    // Sort by score descending
    arsort($playerScores);

    // Format for output
    $leaderboard = [];
    $position = 1;
    foreach ($playerScores as $username => $score) {
        $medal = $position === 1 ? '🥇' : ($position === 2 ? '🥈' : ($position === 3 ? '🥉' : $position . '.'));
        $leaderboard[] = [
            'position' => $position,
            'username' => $username,
            'score' => $score,
            'medal' => $medal
        ];
        $position++;
    }

    echo json_encode([
        'success' => true,
        'leaderboard' => $leaderboard
    ]);
}
?>
