<?php
session_start();

require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/PDFGenerator.php';
require_once __DIR__ . '/../utils/QRGenerator.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/GameService.php';
require_once __DIR__ . '/../services/RoomService.php';
require_once __DIR__ . '/../services/AdminService.php';
require_once __DIR__ . '/../services/QuestionService.php';
require_once __DIR__ . '/../repository/RoundRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';

header('Content-Type: application/json');

// Initialize services
$auth = new AuthService();
$game = new GameService();
$room = new RoomService();
$admin = new AdminService();
$question = new QuestionService();
$questionSet = $question; // Alias for backward compatibility

// Get endpoint from URL path or from POST body
$endpoint = $_GET['endpoint'] ?? '';

// Se è una richiesta POST senza endpoint in GET, prova a leggerlo dal corpo JSON
if (!$endpoint && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $endpoint = $input['endpoint'] ?? '';
}

$action = $_GET['action'] ?? '';

// Router
switch ($endpoint) {
    case 'player_login':
        handlePlayerLogin($auth);
        break;

    case 'admin_login':
        handleAdminLogin($auth);
        break;

    case 'start_game_room':
        // Redirect a game-room.php mantenendo la sessione
        $set_id = $_GET['set_id'] ?? null;
        if ($set_id) {
            header('Location: ../../public/game-room.php?set_id=' . $set_id);
            exit();
        }
        echo json_encode(['success' => false, 'error' => 'set_id required']);
        exit();

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

    case 'close_room':
        handleCloseRoom($room);
        break;

    case 'finish_game':
        handleFinishGame($room);
        break;

    case 'generate_pdf':
        handleGeneratePDF($room);
        break;

    case 'connected_devices':
        handleConnectedDevices($room);
        break;

    case 'game':
        handleGame($action, $game, $room, $question);
        break;

    case 'increment_counter':
        handleIncrementCounter();
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

    case 'get_question':
        handleGetQuestion($question);
        break;

    case 'add_category':
        handleAddCategory($question);
        break;

    case 'update_category':
        handleUpdateCategory($question);
        break;

    case 'delete_category':
        handleDeleteCategory($question);
        break;

    case 'get_categories':
        handleGetCategories($question);
        break;

    case 'save_categories':
        handleSaveCategories($question);
        break;

    case 'get_questionset':
        handleGetQuestionSet($questionSet);
        break;

    case 'add_questionset':
        handleAddQuestionSet($questionSet);
        break;

    case 'update_questionset':
        handleUpdateQuestionSet($questionSet);
        break;

    case 'update_questionset_metadata':
        handleUpdateQuestionSetMetadata($questionSet);
        break;

    case 'delete_questionset':
        handleDeleteQuestionSet($questionSet);
        break;

    case 'get_questionsets':
        handleGetQuestionSets($questionSet);
        break;

    case 'get_set_questions':
        handleGetSetQuestions($questionSet);
        break;

    case 'get_questions':
        handleGetQuestions($question);
        break;

    case 'add_question_to_set':
        handleAddQuestionToSet($questionSet);
        break;

    case 'add_question_to_set_at_position':
        handleAddQuestionToSetAtPosition($questionSet);
        break;

    case 'remove_question_from_set':
        handleRemoveQuestionFromSet($questionSet);
        break;

    case 'update_question_order':
        handleUpdateQuestionOrder($questionSet);
        break;

    case 'move_question_up':
        handleMoveQuestionUp($questionSet);
        break;

    case 'move_question_down':
        handleMoveQuestionDown($questionSet);
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

        $round_id = $data['round_id'] ?? 0;
        $answer = $data['answer'] ?? null;
        $time_taken = $data['time_taken'] ?? 0;

        if (!$round_id) {
            echo json_encode([
                'success' => false,
                'error' => 'Round ID mancante'
            ]);
            exit();
        }

        if ($answer === null || $answer === '') {
            echo json_encode([
                'success' => false,
                'error' => 'Risposta mancante'
            ]);
            exit();
        }

        try {
            $result = $game->submitAnswerByRoundId($round_id, $answer, $time_taken);
            echo json_encode($result);
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

function handleCreateRoom($room) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $questionSetId = $input['question_set_id'] ?? null;

        error_log("Create room request: question_set_id=$questionSetId");
        $result = $room->createRoom($questionSetId);

        // Save codes in session for admin panel
        if ($result['success']) {
            $_SESSION['code_player'] = $result['code_player'];
            $_SESSION['code_judge'] = $result['code_judge'];

            // Recupera i dati della stanza (contiene i base64 QR)
            $roomData = $room->getRoomDetails($result['code_player']);

            if ($roomData) {
                // Aggiungi i prefissi data URI per i client
                if (!empty($roomData['qr_uri_player'])) {
                    $result['qr_uri_player'] = 'data:image/jpeg;base64,' . $roomData['qr_uri_player'];
                }
                if (!empty($roomData['qr_uri_judge'])) {
                    $result['qr_uri_judge'] = 'data:image/jpeg;base64,' . $roomData['qr_uri_judge'];
                }
            }
        }

        echo json_encode($result);    } catch (Exception $e) {
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
        $data = json_decode(file_get_contents('php://input'), true);
        $roomCode = $data['code_player'] ?? $_SESSION['code_player'] ?? null;

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
        $codePlayer = $_SESSION['code_player'] ?? $_GET['code_player'] ?? null;

        if (!$codePlayer) {
            throw new Exception('Codice stanza mancante');
        }

        // Cancel the room (mark as 'cancelled')
        $result = $room->cancelRoom($codePlayer);

        // Clear room from session
        if ($result['success']) {
            unset($_SESSION['code_player']);
            unset($_SESSION['code_judge']);
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

function handleCloseRoom($room) {
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is authenticated
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['player_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Autenticazione richiesta'
        ]);
        exit();
    }

    // Check if user is admin
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Accesso riservato agli amministratori'
        ]);
        exit();
    }

    try {
        // Get active room from session or request
        $roomCode = $_SESSION['room_code'] ?? $_GET['room_code'] ?? null;

        if (!$roomCode) {
            throw new Exception('Codice stanza mancante');
        }

        // Get room details
        $roomDetails = $room->getRoomDetails($roomCode);
        if (!$roomDetails) {
            throw new Exception('Stanza non trovata');
        }

        // Remove all players from the room
        $playerRepo = new PlayerRepo();
        $playerRepo->deletePlayersByRoom($roomCode);

        // Clear room from session
        unset($_SESSION['room_code']);

        echo json_encode([
            'success' => true,
            'message' => 'Stanza chiusa'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleFinishGame($room) {
    requireLoginJson();

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $roomCode = $data['code_player'] ?? $_SESSION['room_code'] ?? null;

        if (!$roomCode) {
            throw new Exception('Codice stanza mancante');
        }

        // Get room details via service
        $roomDetails = $room->getRoomDetails($roomCode);
        if (!$roomDetails) {
            throw new Exception('Stanza non trovata');
        }

        // Close the room via service (which calls repo)
        $result = $room->finishGame($roomDetails['id']);

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
    try {
        // Get room code from session (code_player) or request
        $codePlayer = $_SESSION['code_player'] ?? $_GET['code_player'] ?? null;

        if (!$codePlayer) {
            echo json_encode([
                'success' => true,
                'devices' => [],
                'count' => 0
            ]);
            exit();
        }

        try {
            // Get room by code
            $roomData = $room->getRoomDetails($codePlayer);
            if (!$roomData) {
                echo json_encode([
                    'success' => true,
                    'devices' => [],
                    'count' => 0
                ]);
                exit();
            }

            // Get players by room ID
            $playerRepo = new PlayerRepo();
            $result = $playerRepo->getPlayersByRoomId($roomData['id']);
        } catch (Exception $e) {
            // If there's an error, return empty list
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
        try {
            $roomCode = $_SESSION['room_code'] ?? null;
            if (!$roomCode) {
                echo json_encode(['success' => false]);
                exit();
            }

            $roundCounter = $_GET['counter'] ?? 1;
            $roundCounter = max(1, intval($roundCounter));

            // Get room details via service
            $roomData = $room->getRoomDetails($roomCode);
            if (!$roomData) {
                echo json_encode(['success' => false]);
                exit();
            }

            // Verifica se la stanza ha già un vincitore
            if ($roomData['has_winner'] ?? false) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Partita già terminata',
                    'game_finished' => true,
                    'winner' => $roomData['winner'] ?? null
                ]);
                exit();
            }

            // Get round at position counter via service
            $result = $room->getRoundByPosition($roomData['id'], $roundCounter);

            if ($result) {
                $result['success'] = true;
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false]);
            }
        } catch (Exception $e) {
            error_log('get_game_state error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
        requireLoginJson();

        try {
            $questionId = $_GET['question_id'] ?? 0;

            if (!$questionId && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $postData = json_decode(file_get_contents('php://input'), true);
                $questionId = $postData['question_id'] ?? 0;
            }

            if (!$questionId) {
                echo json_encode(['success' => false, 'message' => 'Question ID required']);
                exit();
            }

            $roomCode = $_SESSION['room_code'] ?? null;
            if (!$roomCode) {
                echo json_encode(['success' => false, 'message' => 'Room code not found']);
                exit();
            }

            // Use GameService to create round
            $result = $game->startRound($questionId, $roomCode);
            echo json_encode($result);
        } catch (Exception $e) {
            error_log('start_round error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'mark_winner') {
        requireLoginJson();

        try {
            $roomCode = $_SESSION['room_code'] ?? null;
            if (!$roomCode) {
                echo json_encode(['success' => false, 'message' => 'Room code not found']);
                exit();
            }

            // Get room details
            $roomData = $room->getRoomDetails($roomCode);
            if (!$roomData) {
                echo json_encode(['success' => false, 'message' => 'Room not found']);
                exit();
            }

            // Mark the winner
            $result = $room->markWinner($roomData['id']);
            echo json_encode(['success' => $result, 'message' => $result ? 'Winner marked' : 'Could not mark winner']);
        } catch (Exception $e) {
            error_log('mark_winner error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'check_winner') {
        requireLoginJson();

        try {
            $roomCode = $_SESSION['room_code'] ?? null;
            if (!$roomCode) {
                echo json_encode(['success' => false, 'is_winner' => false]);
                exit();
            }

            // Get room details
            $roomData = $room->getRoomDetails($roomCode);
            if (!$roomData) {
                echo json_encode(['success' => false, 'is_winner' => false]);
                exit();
            }

            // Get current player
            $username = $_SESSION['username'] ?? null;
            if (!$username) {
                echo json_encode(['success' => false, 'is_winner' => false]);
                exit();
            }

            // Get player info
            $playerRepo = new PlayerRepo();
            $player = $playerRepo->getPlayerByRoomAndUsername($roomData['id'], $username);
            if (!$player) {
                echo json_encode(['success' => false, 'is_winner' => false]);
                exit();
            }

            // Check if winner
            $isWinner = $room->isWinner($roomData['id'], $player['id']);

            // Verifica se la stanza ha già un vincitore
            if ($roomData['has_winner'] ?? false) {
                echo json_encode([
                    'success' => true,
                    'game_finished' => true,
                    'is_winner' => $isWinner,
                    'winner' => $roomData['winner'] ?? null
                ]);
                exit();
            }

            echo json_encode(['success' => true, 'is_winner' => $isWinner]);
        } catch (Exception $e) {
            error_log('check_winner error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'is_winner' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    if ($action === 'close_round') {
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

        try {
            $result = $game->closeRound($roundId);
            echo json_encode($result);
        } catch (Exception $e) {
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

        if (!$questionSetId) {
            echo json_encode(['success' => false, 'message' => 'Question Set ID required']);
            exit();
        }

        try {
            $resetResult = $game->resetGameByQuestionSet($questionSetId);
            echo json_encode(['success' => true, 'message' => 'Game reset']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
        echo json_encode(['success' => false, 'top_answers' => [], 'message' => 'Round ID required']);
        return;
    }

    $answers = $game->getTopAnswers($roundId, 10);

    echo json_encode(['success' => true, 'top_answers' => $answers]);
}

function handleFinalLeaderboard($game) {
    requireLoginJson();

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    $roomCode = $_SESSION['room_code'] ?? null;

    if (!$roomCode) {
        echo json_encode(['success' => false, 'leaderboard' => []]);
        return;
    }

    $result = $game->getFinalLeaderboard($roomCode);
    echo json_encode($result);
}

function handleGetQuestion($question) {
    try {
        $questionId = $_GET['id'] ?? null;

        if (!$questionId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Question ID required'
            ]);
            return;
        }

        $result = $question->getQuestionById($questionId);

        if (!$result) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Question not found'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'question' => $result
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleGetQuestions($question) {
    try {
        // Recupera tutte le domande (carica fino a 1000 domande)
        $result = $question->getAllQuestions(1, 1000);

        echo json_encode([
            'success' => true,
            'questions' => $result['questions']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleAddCategory($question) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Metodo non consentito'
            ]);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? null;
        $color = $data['color'] ?? null;

        if (!$name || !$color) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Nome e colore obbligatori'
            ]);
            return;
        }

        $result = $question->addCategory($name, $color);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Categoria aggiunta con successo',
                'categoryId' => $result
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Errore nell\'aggiunta della categoria'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleUpdateCategory($question) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Metodo non consentito'
            ]);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? null;
        $color = $data['color'] ?? null;

        if (!$id || !$name || !$color) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID, nome e colore obbligatori'
            ]);
            return;
        }

        $result = $question->updateCategory($id, $name, $color);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Categoria aggiornata con successo'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento della categoria'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleDeleteCategory($question) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Metodo non consentito'
            ]);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID obbligatorio'
            ]);
            return;
        }

        $result = $question->deleteCategory($id);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Categoria eliminata con successo. Le domande associate sono state spostate alla categoria Generale.'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Non puoi eliminare la categoria Generale. Le domande orfane verranno spostate in questa categoria.'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleSaveCategories($question) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Metodo non consentito'
            ]);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $deleted = $data['deleted'] ?? [];
        $updated = $data['updated'] ?? [];
        $added = $data['added'] ?? [];

        // Elimina categorie
        foreach ($deleted as $id) {
            $question->deleteCategory($id);
        }

        // Aggiorna categorie
        foreach ($updated as $cat) {
            if (isset($cat['id'], $cat['name'], $cat['color'])) {
                $question->updateCategory($cat['id'], $cat['name'], $cat['color']);
            }
        }

        // Aggiunge categorie nuove
        foreach ($added as $cat) {
            if (isset($cat['name'], $cat['color'])) {
                $question->addCategory($cat['name'], $cat['color']);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Tutte le modifiche sono state salvate con successo'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleGetCategories($question) {
    try {
        $categories = $question->getAllCategories();

        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleGetQuestionSet($questionSet) {
    $setId = $_GET['id'] ?? null;

    if (!$setId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID is required'
        ]);
        return;
    }

    try {
        $set = $questionSet->getById($setId);

        if (!$set) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Question set not found'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'set' => $set
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleAddQuestionSet($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }

    // Leggi da JSON body o da POST
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $setName = $input['set_name'] ?? $_POST['set_name'] ?? '';
    $setDescription = $input['set_description'] ?? $_POST['set_description'] ?? '';

    if (empty($setName)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set name is required'
        ]);
        return;
    }

    try {
        $setId = $questionSet->add($setName, $setDescription);

        echo json_encode([
            'success' => true,
            'set_id' => $setId,
            'message' => 'Question set created successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleUpdateQuestionSet($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }

    $setId = $_POST['set_id'] ?? null;
    $setName = $_POST['set_name'] ?? '';
    $setDescription = $_POST['set_description'] ?? '';

    if (!$setId || empty($setName)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID and name are required'
        ]);
        return;
    }

    try {
        $questionSet->update($setId, $setName, $setDescription);

        echo json_encode([
            'success' => true,
            'message' => 'Question set updated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleUpdateQuestionSetMetadata($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $setId = $input['set_id'] ?? null;
    $setName = $input['set_name'] ?? '';
    $setDescription = $input['set_description'] ?? '';
    $isSaved = $input['is_saved'] ?? null;

    if (!$setId || empty($setName)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID and name are required'
        ]);
        return;
    }

    try {
        $questionSet->update($setId, $setName, $setDescription);

        // If is_saved flag is provided, update it
        if ($isSaved !== null) {
            $questionSet->setSaved($setId, $isSaved);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Question set metadata updated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleDeleteQuestionSet($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        return;
    }

    $setId = $_POST['set_id'] ?? null;

    if (!$setId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID is required'
        ]);
        return;
    }

    try {
        $questionSet->delete($setId);

        echo json_encode([
            'success' => true,
            'message' => 'Question set deleted successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleGetQuestionSets($questionSet) {
    $page = $_GET['page'] ?? 1;
    $search = $_GET['search'] ?? '';
    $searchType = $_GET['search_type'] ?? 'contains';

    try {
        if (!empty($search)) {
            $data = $questionSet->search($search, $searchType, $page);
        } else {
            $data = $questionSet->getAll($page);
        }

        echo json_encode([
            'success' => true,
            'sets' => $data['sets'],
            'total' => $data['total'],
            'page' => $data['page'],
            'totalPages' => $data['totalPages']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleGetSetQuestions($questionSet) {
    $setId = $_GET['set_id'] ?? null;

    if (!$setId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID is required'
        ]);
        return;
    }

    try {
        $questions = $questionSet->getQuestions($setId);

        echo json_encode([
            'success' => true,
            'questions' => $questions
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleRemoveQuestionFromSet($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $setId = $data['set_id'] ?? null;
    $questionId = $data['question_id'] ?? null;

    if (!$setId || !$questionId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID and Question ID are required'
        ]);
        return;
    }

    try {
        $questionSet->removeQuestion($setId, $questionId);

        echo json_encode([
            'success' => true,
            'message' => 'Question removed successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleAddQuestionToSet($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $setId = $data['set_id'] ?? null;
    $questionId = $data['question_id'] ?? null;

    if (!$setId || !$questionId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID and Question ID are required'
        ]);
        return;
    }

    try {
        // Ottieni l'ordine massimo corrente e verifica se la domanda esiste già
        $questions = $questionSet->getQuestions($setId);
        $maxOrder = 0;
        $questionExists = false;

        foreach ($questions as $q) {
            if ($q['order_in_set'] > $maxOrder) {
                $maxOrder = $q['order_in_set'];
            }
            // Verifica se la domanda è già nel set
            if ($q['id'] == $questionId) {
                $questionExists = true;
            }
        }

        // Se la domanda esiste già nel set, ritorna errore
        if ($questionExists) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Question already in set or unable to add'
            ]);
            return;
        }

        // Aggiungi la domanda con il prossimo ordine
        $success = $questionSet->addQuestionToSet($setId, $questionId, $maxOrder + 1);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Question added successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Question already in set or unable to add'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleAddQuestionToSetAtPosition($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $setId = $data['set_id'] ?? null;
    $questionId = $data['question_id'] ?? null;
    $position = $data['position'] ?? null;

    if (!$setId || !$questionId || $position === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID, Question ID and position are required'
        ]);
        return;
    }

    try {
        // Aggiungi la domanda e sposta le altre se necessario
        $success = $questionSet->addQuestionAtPosition($setId, $questionId, $position);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Question added successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Question already in set or unable to add'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleUpdateQuestionOrder($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $setId = $data['set_id'] ?? null;
    $questions = $data['questions'] ?? [];

    if (!$setId || empty($questions)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID and questions are required'
        ]);
        return;
    }

    try {
        $success = $questionSet->updateQuestionsOrder($setId, $questions);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Order updated successfully'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Unable to update order'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleMoveQuestionUp($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $setId = $data['set_id'] ?? null;
    $questionId = $data['question_id'] ?? null;

    if (!$setId || !$questionId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID and Question ID are required'
        ]);
        return;
    }

    try {
        $questionSet->moveQuestionUp($setId, $questionId);

        echo json_encode([
            'success' => true,
            'message' => 'Question moved up successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleMoveQuestionDown($questionSet) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $setId = $data['set_id'] ?? null;
    $questionId = $data['question_id'] ?? null;

    if (!$setId || !$questionId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Set ID and Question ID are required'
        ]);
        return;
    }

    try {
        $questionSet->moveQuestionDown($setId, $questionId);

        echo json_encode([
            'success' => true,
            'message' => 'Question moved down successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleIncrementCounter() {
    requireLoginJson();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $roomCode = $_SESSION['room_code'] ?? null;
    if (!$roomCode) {
        echo json_encode([
            'success' => false,
            'error' => 'Nessuna stanza attiva'
        ]);
        exit;
    }

    $counterKey = 'round_counter_' . $roomCode;
    $currentCounter = $_SESSION[$counterKey] ?? 1;
    $_SESSION[$counterKey] = $currentCounter + 1;

    // Assicura che i dati vengono scritti nella session
    session_write_close();

    echo json_encode([
        'success' => true,
        'newCounter' => $currentCounter + 1
    ]);
}


/**
 * Genera il QR code come immagine binaria
 */
function generateQRImage($roomCode) {
    $baseUrl = 'http://151.64.86.229:9000/public/login-player.php';
    $qrUrl = $baseUrl . '?code=' . urlencode($roomCode);
    return QRGenerator::generateQRFromURL($qrUrl);
}function handleGeneratePDF($room) {
    // Leggi da JSON body (non da $_POST)
    $input = json_decode(file_get_contents('php://input'), true);
    $roomCode = $input['room_code'] ?? $_POST['room_code'] ?? '';

    if (!$roomCode) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Room code required']);
        exit();
    }

    try {
        // Verifica che la stanza esista e recupera i dati con i due codici e i due QR
        $roomResult = $room->getRoomDetails($roomCode);
        if (!$roomResult) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Room not found']);
            exit();
        }

        // Recupera i due codici
        $codePlayer = $roomResult['code_player'] ?? null;
        $codeJudge = $roomResult['code_judge'] ?? null;

        if (empty($codePlayer) || empty($codeJudge)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Both room codes are required']);
            exit();
        }

        // Recupera i base64 dei QR dal DB (sono già base64, senza prefisso)
        $qrBase64Player = $roomResult['qr_uri_player'] ?? null;
        $qrBase64Judge = $roomResult['qr_uri_judge'] ?? null;

        // Se mancano, generiamo i QR al volo
        if (empty($qrBase64Player)) {
            $generatedPlayer = generateQRImage($codePlayer);
            if (!empty($generatedPlayer)) {
                $qrBase64Player = base64_encode($generatedPlayer);
            }
        }

        if (empty($qrBase64Judge)) {
            $generatedJudge = generateQRImage($codeJudge);
            if (!empty($generatedJudge)) {
                $qrBase64Judge = base64_encode($generatedJudge);
            }
        }

        if (empty($qrBase64Player) || empty($qrBase64Judge)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to generate QR images']);
            exit();
        }

        // Genera PDF con due pagine (player e judge) con i rispettivi QR
        // PDFGenerator sa gestire sia base64 puro che data URI
        $pdfGenerator = new PDFGenerator($codePlayer, $codeJudge, $qrBase64Player, $qrBase64Judge);
        $pdfGenerator->generate();
        $pdfContent = $pdfGenerator->getPDF();

        // Converti a base64 per data URI
        $base64 = base64_encode($pdfContent);

        echo json_encode([
            'success' => true,
            'pdf_data' => 'data:application/pdf;base64,' . $base64,
            'filename' => 'marriage-game-stanza-' . $codePlayer . '.pdf'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
