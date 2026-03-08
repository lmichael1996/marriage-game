<?php
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/PDFGenerator.php';
require_once __DIR__ . '/../services/ServiceLoader.php';

header('Content-Type: application/json');

// ── Helpers ──────────────────────────────────────────────────────────────

function respond($data)
{
    echo json_encode($data);
    exit;
}

function respondError($msg, $code = 400)
{
    http_response_code($code);
    respond([
        'success' => false,
        'error' => $msg
    ]);
}

function requirePost()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        respondError('Method not allowed', 405);
}

function input()
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// ── Router ───────────────────────────────────────────────────────────────

$endpoint = $_GET['endpoint'] ?? '';

if (!$endpoint && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $endpoint = input()['endpoint'] ?? '';
}

$action = $_GET['action'] ?? '';

switch ($endpoint) {
    case 'player_login':
        handlePlayerLogin();
        break;

    case 'admin_login':
        handleAdminLogin();
        break;

    case 'judge_login':
        handleJudgeLogin();
        break;

    case 'answer':
        handleAnswer();
        break;

    case 'create_room':
        handleCreateRoom();
        break;

    case 'start_room':
        handleStartRoom();
        break;

    case 'check_room_status':
        handleCheckRoomStatus();
        break;

    case 'delete_room':
        handleDeleteRoom();
        break;

    case 'generate_pdf':
        handleGeneratePDF();
        break;

    case 'connected_devices':
        handleConnectedDevices();
        break;

    case 'game':
        handleGame($action);
        break;

    case 'round_answers':
        handleRoundAnswers();
        break;

    case 'final_leaderboard':
        handleFinalLeaderboard();
        break;

    case 'player_progress':
        handlePlayerProgress();
        break;

    case 'get_question':
        handleGetQuestion();
        break;

    case 'get_questions':
        handleGetQuestions();
        break;

    case 'get_categories':
        handleGetCategories();
        break;

    case 'save_categories':
        handleSaveCategories();
        break;

    case 'get_questionset':
        handleGetQuestionSet();
        break;

    case 'add_questionset':
        handleAddQuestionSet();
        break;

    case 'update_questionset':
        handleUpdateQuestionSet();
        break;

    case 'delete_questionset':
        handleDeleteQuestionSet();
        break;

    case 'get_set_questions':
        handleGetSetQuestions();
        break;

    case 'add_question_to_set':
        handleAddQuestionToSet();
        break;

    case 'add_question_to_set_at_position':
        handleAddQuestionToSetAtPosition();
        break;

    case 'remove_question_from_set':
        handleRemoveQuestionFromSet();
        break;

    case 'update_question_order':
        handleUpdateQuestionOrder();
        break;

    default:
        respondError('Endpoint not found', 404);
}

// ── Auth ─────────────────────────────────────────────────────────────────

function handlePlayerLogin() {
    requirePost();
    $data = input();
    try {
        svc('auth')->playerLogin($data['username'] ?? '', $data['room_code'] ?? '');
        respond([
            'success' => true,
            'redirect' => '../public/player.php'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 401);
    }
}

function handleAdminLogin() {
    requirePost();
    $data = input();
    try {
        svc('auth')->adminLogin($data['username'] ?? '', $data['password'] ?? '');
        respond([
            'success' => true,
            'redirect' => '../public/admin.php'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 401);
    }
}

function handleJudgeLogin() {
    requirePost();
    $data = input();
    try {
        svc('auth')->judgeLogin($data['room_code'] ?? '');
        respond([
            'success' => true,
            'redirect' => '../public/judge.php'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 401);
    }
}

// ── Game ─────────────────────────────────────────────────────────────────

function handleAnswer() {
    requireLoginJson();
    requirePost();
    $data = input();

    if (!($data['round_id'] ?? 0))      respondError('Round ID required');
    if (($data['answer'] ?? '') === '')  respondError('Answer required');

    try {
        respond(svc('game')->submitAnswerByRoundId($data['round_id'], $data['answer'], $data['time_taken'] ?? 0));
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

// ── Room ─────────────────────────────────────────────────────────────────

function handleCreateRoom() {
    try {
        $room   = svc('room');
        $result = $room->createRoom((int)(input()['question_set_id'] ?? 0));

        if ($result['success']) {
            $_SESSION['active_room_code']  = $result['code_player'];
            $_SESSION['active_judge_code'] = $result['code_judge'];
            $_SESSION['room_id']           = $result['room_id'];

            $roomData = $room->getRoomDetails($result['room_id']);
            if ($roomData) {
                if (!empty($roomData['qr_uri_player'])) $result['qr_uri_player'] = 'data:image/svg+xml;base64,' . $roomData['qr_uri_player'];
                if (!empty($roomData['qr_uri_judge']))  $result['qr_uri_judge']  = 'data:image/svg+xml;base64,' . $roomData['qr_uri_judge'];
            }
        }
        respond($result);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleStartRoom() {
    requireLoginJson();
    try {
        $roomId = authRoomId();
        if (!$roomId) throw new Exception('No active room in session');
        respond(svc('room')->startRoom($roomId));
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleCheckRoomStatus() {
    requireLoginJson();
    try {
        $roomId = authRoomId();
        if (!$roomId) respond([
            'room_open' => false,
            'status' => 'unknown',
            'message' => 'No room associated'
        ]);

        $roomData = svc('room')->getRoomDetails($roomId);
        if (!$roomData) respond([
            'room_open' => false,
            'status' => 'unknown',
            'message' => 'Room not found'
        ]);

        $status = $roomData['status_room'] ?? 'unknown';
        respond([
            'room_open'  => ($status === 'open' || $status === 'running'),
            'status'     => $status,
            'has_winner' => $roomData['has_winner'] ?? false,
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleDeleteRoom() {
    requireAdminJson();
    try {
        $roomId = authRoomId();
        if (!$roomId) throw new Exception('No active room');

        $result = svc('room')->cancelRoom($roomId);
        if ($result['success']) {
            unset($_SESSION['active_room_code'], $_SESSION['active_judge_code'], $_SESSION['room_id']);
         }
        respond($result);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleConnectedDevices() {
    try {
        $roomId = authRoomId();
        if (!$roomId) respond(['success' => true, 'devices' => [], 'count' => 0]);

        $result = svc('room')->getConnectedDevices($roomId);
        respond([
            'success' => true,
            'devices' => $result['devices'],
            'count' => $result['count'],
            'judge_connected' => $result['judge_connected'] ?? false
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

// ── Game Actions ─────────────────────────────────────────────────────────

function handleGame($action) {
    requireLoginJson();
    match ($action) {
        'get_game_state' => handleGetGameState(),
        'start_round'    => handleStartRound(),
        'close_round'    => handleCloseRound(),
        'set_clickfirst_winner' => handleSetClickfirstWinner(),
        'judge_advance'  => handleJudgeAdvance(),
        'check_judge_decision' => handleCheckJudgeDecision(),
        'check_winner'   => handleCheckWinner(),
        default          => respondError('Invalid action'),
    };
}

function handleGetGameState() {
    try {
        $roomId = authRoomId();
        if (!$roomId) respond(['success' => false]);

        $room = svc('room');
        $roomData = $room->getRoomDetails($roomId);
        if (!$roomData) respond(['success' => false]);

        $statusRoom = $roomData['status_room'] ?? null;

        // If the room is closed or has a winner, notify the player immediately
        if ($statusRoom === 'closed' || ($roomData['has_winner'] ?? false)) {
            // If there's no winner yet, mark one now
            if (!($roomData['has_winner'] ?? false)) {
                $room->markWinner($roomData['id']);
                $roomData = $room->getRoomDetails($roomId);
            }

            $isWinner = false;
            $playerId = authPlayerId();
            if ($playerId) {
                $isWinner = $room->isWinner($roomData['id'], $playerId);
            }

            respond([
                'success'       => false,
                'game_finished' => true,
                'status_room'   => $statusRoom,
                'is_winner'     => $isWinner,
                'winner_id'     => $roomData['winner_id'] ?? null,
            ]);
        }

        if ($statusRoom === 'cancelled') {
            respond([
                'success'     => false,
                'status_room' => 'cancelled',
            ]);
        }

        $counter = max(1, intval($_GET['counter'] ?? 1));
        $result  = $room->getRoundByPosition($roomData['id'], $counter);
        $response = $result
            ? array_merge($result, ['success' => true, 'status_room' => $statusRoom])
            : [
                'success' => false,
                'status_room' => $statusRoom
            ];
        respond($response);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleStartRound() {
    $questionId = $_GET['question_id'] ?? 0;
    if (!$questionId && $_SERVER['REQUEST_METHOD'] === 'POST') $questionId = input()['question_id'] ?? 0;
    if (!$questionId) respondError('Question ID required');

    $roomId = authRoomId();
    if (!$roomId) respondError('Room ID not found');

    try {
        respond(svc('game')->startRound($questionId, $roomId));
    } catch (Exception $e) {
        respondError('Server error: ' . $e->getMessage(), 500);
    }
}

function handleCloseRound() {
    $roundId = $_GET['round_id'] ?? 0;
    if (!$roundId && $_SERVER['REQUEST_METHOD'] === 'POST') $roundId = input()['round_id'] ?? 0;
    if (!$roundId) respondError('Round ID required');

    try {
        respond(svc('game')->closeRound($roundId));
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleSetClickfirstWinner() {
    $data = input();
    $roundId = $data['round_id'] ?? 0;
    $winnerIndex = $data['winner_index'] ?? null;
    if (!$roundId) respondError('Round ID required');
    if ($winnerIndex === null) respondError('Winner index required');

    try {
        respond(svc('game')->setClickfirstWinner($roundId, (int)$winnerIndex));
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleJudgeAdvance() {
    $data = input();
    $roundId = $data['round_id'] ?? 0;
    if (!$roundId) respondError('Round ID required');

    try {
        svc('game')->markJudgeDecided($roundId);
        respond([
            'success' => true,
            'message' => 'Judge advance signaled'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleCheckJudgeDecision() {
    $roundId = $_GET['round_id'] ?? 0;
    if (!$roundId) respondError('Round ID required');

    try {
        $decided = svc('game')->isJudgeDecided($roundId);
        respond([
            'success' => true,
            'judge_decided' => $decided
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleCheckWinner() {
    try {
        $roomId = authRoomId();
        $fail = [
            'success' => false,
            'is_winner' => false
        ];
        if (!$roomId) respond($fail);

        $room = svc('room');
        $roomData = $room->getRoomDetails($roomId);
        if (!$roomData) respond($fail);

        // If the room is closed but there's no winner yet, mark it now
        if (!($roomData['has_winner'] ?? false) && ($roomData['status_room'] ?? '') === 'closed') {
            $room->markWinner($roomData['id']);
            // Reload to get the updated winner
            $roomData = $room->getRoomDetails($roomId);
        }

        $playerId = authPlayerId();
        if (!$playerId) respond($fail);

        $isWinner = $room->isWinner($roomData['id'], $playerId);

        if ($roomData['has_winner'] ?? false) {
            respond([
                'success' => true,
                'game_finished' => true,
                'is_winner' => $isWinner,
                'winner_id' => $roomData['winner_id'] ?? null
            ]);
        }
        respond([
            'success' => true,
            'is_winner' => $isWinner
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleRoundAnswers() {
    requireLoginJson();
    $roundId = $_GET['round_id'] ?? 0;
    if (!$roundId)
    {
        respond([
            'success' => false,
            'top_answers' => [],
            'message' => 'Round ID required'
        ]);
    }
    respond([
        'success' => true,
        'top_answers' => svc('game')->getTopAnswers($roundId, 10)
    ]);
}

function handleFinalLeaderboard() {
    requireLoginJson();
    $roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : authRoomId();
    if (!$roomId) {
        respond([
            'success' => false,
            'leaderboard' => []
        ]);
    }
    respond(svc('game')->getFinalLeaderboard($roomId));
}

function handlePlayerProgress() {
    requireLoginJson();
    try {
        $answered = svc('game')->getPlayerProgress();
        respond([
            'success' => true,
            'answered' => $answered
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

// ── Questions ────────────────────────────────────────────────────────────

function handleGetQuestion() {
    $id = $_GET['id'] ?? null;
    if (!$id) respondError('Question ID required');
    try {
        $result = svc('question')->getQuestionById($id);
        if (!$result) respondError('Question not found', 404);
        respond([
            'success' => true,
            'question' => $result
        ]);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleGetQuestions() {
    try {
        respond([
            'success' => true,
            'questions' => svc('question')->getAllQuestions(1, 1000)['questions']
        ]);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

// ── Categories ───────────────────────────────────────────────────────────

function handleGetCategories() {
    try {
        respond([
            'success' => true,
            'categories' => svc('question')->getAllCategories()
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleSaveCategories() {
    requirePost();
    $data = input();
    $q = svc('question');
    try {
        foreach ($data['deleted'] ?? [] as $id) {
            $q->deleteCategory($id);
        }
        foreach ($data['updated'] ?? [] as $cat) {
            if (isset($cat['id'], $cat['name'], $cat['color'])) {
                $q->updateCategory($cat['id'], $cat['name'], $cat['color']);
            }
        }
        foreach ($data['added'] ?? [] as $cat) {
            if (isset($cat['name'], $cat['color'])) {
                $q->addCategory($cat['name'], $cat['color']);
            }
        }
        respond([
            'success' => true,
            'message' => 'All changes saved successfully'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

// ── Question Sets ────────────────────────────────────────────────────────

function handleGetQuestionSet() {
    $setId = $_GET['id'] ?? null;
    if (!$setId) respondError('Set ID is required');
    try {
        $set = svc('set')->getQuestionSetById($setId);
        if (!$set) respondError('Question set not found', 404);
        respond([
            'success' => true,
            'set' => $set
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleAddQuestionSet() {
    requirePost();
    $data    = input();
    $setName = $data['set_name'] ?? '';
    if (!$setName) respondError('Set name is required');
    try {
        $setId = svc('set')->addSet($setName, $data['set_description'] ?? '');
        respond([
            'success' => true,
            'set_id' => $setId,
            'message' => 'Question set created successfully'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage());
    }
}

function handleUpdateQuestionSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['set_name'] ?? '')) respondError('Set ID and name are required');
    try {
        svc('set')->updateSet($data['set_id'], $data['set_name'], $data['set_description'] ?? '');
        respond([
            'success' => true,
            'message' => 'Question set updated successfully'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage());
    }
}

function handleDeleteQuestionSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null)) respondError('Set ID is required');
    try {
        svc('set')->deleteSet($data['set_id']);
        respond([
            'success' => true,
            'message' => 'Question set deleted successfully'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleGetSetQuestions() {
    $setId = $_GET['set_id'] ?? null;
    if (!$setId) respondError('Set ID is required');
    try {
        respond([
            'success' => true,
            'questions' => svc('set')->getSetQuestions($setId)
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleAddQuestionToSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) respondError('Set ID and Question ID are required');
    try {
        $q = svc('set');
        $questions = $q->getSetQuestions($data['set_id']);
        $maxOrder = 0;
        foreach ($questions as $qItem) {
            if ($qItem['order_in_set'] > $maxOrder) $maxOrder = $qItem['order_in_set'];
            if ($qItem['id'] == $data['question_id']) respondError('Question already in set');
        }
        if (!$q->addQuestionToSet($data['set_id'], $data['question_id'], $maxOrder + 1)) respondError('Question already in set or unable to add');
        respond([
            'success' => true,
            'message' => 'Question added successfully'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleAddQuestionToSetAtPosition() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null) || ($data['position'] ?? null) === null) {
        respondError('Set ID, Question ID and position are required');
    }
    try {
        if (!svc('set')->addQuestionAtPosition($data['set_id'], $data['question_id'], $data['position'])) respondError('Question already in set or unable to add');
        respond([
            'success' => true,
            'message' => 'Question added successfully'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleRemoveQuestionFromSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) respondError('Set ID and Question ID are required');
    try {
        svc('set')->removeQuestion($data['set_id'], $data['question_id']);
        respond([
            'success' => true,
            'message' => 'Question removed successfully'
        ]);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleUpdateQuestionOrder() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || empty($data['questions'])) respondError('Set ID and questions are required');
    try {
        if (!svc('set')->updateQuestionsOrder($data['set_id'], $data['questions'])) respondError('Unable to update order');
        respond([
            'success' => true,
            'message' => 'Order updated successfully'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

// ── Misc ─────────────────────────────────────────────────────────────────

function handleGeneratePDF() {
    $roomId = authRoomId();
    if (!$roomId) respondError('Room ID not found');

    try {
        $room = svc('room');
        $roomResult = $room->getRoomDetails($roomId);
        if (!$roomResult) respondError('Room not found', 404);

        $codePlayer = $roomResult['code_player'] ?? null;
        $codeJudge  = $roomResult['code_judge'] ?? null;
        if (!$codePlayer || !$codeJudge) respondError('Both room codes are required');

        // Retrieve base64-encoded QR SVGs from DB and decode
        $svgPlayer = !empty($roomResult['qr_uri_player']) ? base64_decode($roomResult['qr_uri_player']) : null;
        $svgJudge  = !empty($roomResult['qr_uri_judge'])  ? base64_decode($roomResult['qr_uri_judge'])  : null;
        if (!$svgPlayer || !$svgJudge) respondError('QR codes not found for this room', 500);

        $pdf = new PDFGenerator($codePlayer, $codeJudge, $svgPlayer, $svgJudge);
        $pdf->generate();
        respond([
            'success'  => true,
            'pdf_data' => 'data:application/pdf;base64,' . base64_encode($pdf->getPDF()),
            'filename' => 'MVquiz-room-' . $codePlayer . '-' . $codeJudge . '.pdf'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}
