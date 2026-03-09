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
    $result = svc('auth')->playerLogin($data['username'] ?? '', $data['room_code'] ?? '');
    if (!($result['success'] ?? false)) {
        respondError($result['error'] ?? 'Login failed', 401);
    }
    respond([
        'success' => true,
        'redirect' => '../public/player.php'
    ]);
}

function handleAdminLogin() {
    requirePost();
    $data = input();
    $result = svc('auth')->adminLogin($data['username'] ?? '', $data['password'] ?? '');
    if (!($result['success'] ?? false)) {
        respondError($result['error'] ?? 'Login failed', 401);
    }
    respond([
        'success' => true,
        'redirect' => '../public/admin.php'
    ]);
}

function handleJudgeLogin() {
    requirePost();
    $data = input();
    $result = svc('auth')->judgeLogin($data['room_code'] ?? '');
    if (!($result['success'] ?? false)) {
        respondError($result['error'] ?? 'Login failed', 401);
    }
    respond([
        'success' => true,
        'redirect' => '../public/judge.php'
    ]);
}

// ── Game ─────────────────────────────────────────────────────────────────

function handleAnswer() {
    requireLoginJson();
    requirePost();
    $data = input();

    if (!($data['round_id'] ?? 0))      respondError('Round ID required');
    if (($data['answer'] ?? '') === '')  respondError('Answer required');

    $result = svc('game')->submitAnswerByRoundId($data['round_id'], $data['answer'], $data['time_taken'] ?? 0);
    if (!($result['success'] ?? true)) {
        respondError($result['error'] ?? 'Failed to submit answer', 500);
    }
    respond($result);
}

// ── Room ─────────────────────────────────────────────────────────────────

function handleCreateRoom() {
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
}

function handleStartRoom() {
    requireLoginJson();
    $roomId = authRoomId();
    if (!$roomId) respondError('No active room in session');
    respond(svc('room')->startRoom($roomId));
}

function handleCheckRoomStatus() {
    requireLoginJson();
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
        'room_open'    => ($status === 'open' || $status === 'running'),
        'status'       => $status,
        'has_ranking'  => $roomData['has_ranking'] ?? false,
    ]);
}

function handleDeleteRoom() {
    requireAdminJson();
    $roomId = authRoomId();
    if (!$roomId) respondError('No active room');

    $result = svc('room')->cancelRoom($roomId);
    if ($result['success']) {
        unset($_SESSION['active_room_code'], $_SESSION['active_judge_code'], $_SESSION['room_id']);
     }
    respond($result);
}

function handleConnectedDevices() {
    $roomId = authRoomId();
    if (!$roomId) respond(['success' => true, 'devices' => [], 'count' => 0]);

    $result = svc('room')->getConnectedDevices($roomId);
    respond([
        'success' => true,
        'devices' => $result['devices'],
        'count' => $result['count'],
        'judge_connected' => $result['judge_connected'] ?? false
    ]);
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
    $roomId = authRoomId();
    if (!$roomId) respond(['success' => false]);

    $room = svc('room');
    $roomData = $room->getRoomDetails($roomId);
    if (!$roomData) respond(['success' => false]);

    $statusRoom = $roomData['status_room'] ?? null;

    // If the room is closed or has a ranking, notify the player immediately
    if ($statusRoom === 'closed' || ($roomData['has_ranking'] ?? false)) {
        // If there's no ranking yet, compute one now
        if (!($roomData['has_ranking'] ?? false)) {
            $room->markFinalRanking($roomData['id']);
        }

        $placement = 0;
        $playerId = authPlayerId();
        if ($playerId) {
            $placement = $room->getPlacement($roomData['id'], $playerId);
        }

        respond([
            'success'       => false,
            'game_finished' => true,
            'status_room'   => $statusRoom,
            'placement'     => $placement,
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
}

function handleStartRound() {
    $questionId = $_GET['question_id'] ?? 0;
    if (!$questionId && $_SERVER['REQUEST_METHOD'] === 'POST') $questionId = input()['question_id'] ?? 0;
    if (!$questionId) respondError('Question ID required');

    $roomId = authRoomId();
    if (!$roomId) respondError('Room ID not found');

    $result = svc('game')->startRound($questionId, $roomId);
    if (!($result['success'] ?? true)) {
        respondError($result['error'] ?? 'Failed to start round', 500);
    }
    respond($result);
}

function handleCloseRound() {
    $roundId = $_GET['round_id'] ?? 0;
    if (!$roundId && $_SERVER['REQUEST_METHOD'] === 'POST') $roundId = input()['round_id'] ?? 0;
    if (!$roundId) respondError('Round ID required');

    $result = svc('game')->closeRound($roundId);
    if (!($result['success'] ?? true)) {
        respondError($result['error'] ?? 'Failed to close round', 500);
    }
    respond($result);
}

function handleSetClickfirstWinner() {
    $data = input();
    $roundId = $data['round_id'] ?? 0;
    $winnerIndex = $data['winner_index'] ?? null;
    if (!$roundId) respondError('Round ID required');
    if ($winnerIndex === null) respondError('Winner index required');

    $result = svc('game')->setClickfirstWinner($roundId, (int)$winnerIndex);
    if (!($result['success'] ?? true)) {
        respondError($result['error'] ?? 'Failed to set winner', 500);
    }
    respond($result);
}

function handleJudgeAdvance() {
    $data = input();
    $roundId = $data['round_id'] ?? 0;
    if (!$roundId) respondError('Round ID required');

    svc('game')->markJudgeDecided($roundId);
    respond([
        'success' => true,
        'message' => 'Judge advance signaled'
    ]);
}

function handleCheckJudgeDecision() {
    $roundId = $_GET['round_id'] ?? 0;
    if (!$roundId) respondError('Round ID required');

    $decided = svc('game')->isJudgeDecided($roundId);
    respond([
        'success' => true,
        'judge_decided' => $decided
    ]);
}

function handleCheckWinner() {
    $roomId = authRoomId();
    $fail = [
        'success' => false,
        'placement' => 0
    ];
    if (!$roomId) respond($fail);

    $room = svc('room');
    $roomData = $room->getRoomDetails($roomId);
    if (!$roomData) respond($fail);

    // If the room is closed but there's no ranking yet, compute it now
    if (!($roomData['has_ranking'] ?? false) && ($roomData['status_room'] ?? '') === 'closed') {
        $room->markFinalRanking($roomData['id']);
    }

    $playerId = authPlayerId();
    if (!$playerId) respond($fail);

    $placement = $room->getPlacement($roomData['id'], $playerId);

    if ($roomData['has_ranking'] ?? false) {
        respond([
            'success' => true,
            'game_finished' => true,
            'placement' => $placement,
        ]);
    }
    respond([
        'success' => true,
        'placement' => $placement
    ]);
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
    $result = svc('game')->getPlayerProgress();
    if (is_array($result)) {
        respondError($result['error'] ?? 'Failed to get progress', 500);
    }
    respond([
        'success' => true,
        'answered' => $result
    ]);
}

// ── Questions ────────────────────────────────────────────────────────────

function handleGetQuestion() {
    $id = $_GET['id'] ?? null;
    if (!$id) respondError('Question ID required');
    $result = svc('question')->getQuestionById($id);
    if (!$result) respondError('Question not found', 404);
    respond([
        'success' => true,
        'question' => $result
    ]);
}

function handleGetQuestions() {
    respond([
        'success' => true,
        'questions' => svc('question')->getAllQuestions(1, 1000)['questions']
    ]);
}

// ── Categories ───────────────────────────────────────────────────────────

function handleGetCategories() {
    respond([
        'success' => true,
        'categories' => svc('question')->getAllCategories()
    ]);
}

function handleSaveCategories() {
    requirePost();
    $data = input();
    $q = svc('question');

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
}

// ── Question Sets ────────────────────────────────────────────────────────

function handleGetQuestionSet() {
    $setId = $_GET['id'] ?? null;
    if (!$setId) respondError('Set ID is required');
    $set = svc('set')->getQuestionSetById($setId);
    if (!$set) respondError('Question set not found', 404);
    respond([
        'success' => true,
        'set' => $set
    ]);
}

function handleAddQuestionSet() {
    requirePost();
    $data    = input();
    $setName = $data['set_name'] ?? '';
    if (!$setName) respondError('Set name is required');
    $result = svc('set')->addSet($setName, $data['set_description'] ?? '');
    if (is_array($result)) {
        respondError($result['error'] ?? 'Failed to create set');
    }
    respond([
        'success' => true,
        'set_id' => $result,
        'message' => 'Question set created successfully'
    ]);
}

function handleUpdateQuestionSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['set_name'] ?? '')) respondError('Set ID and name are required');
    $result = svc('set')->updateSet($data['set_id'], $data['set_name'], $data['set_description'] ?? '');
    if (!($result['success'] ?? false)) {
        respondError($result['error'] ?? 'Failed to update set');
    }
    respond([
        'success' => true,
        'message' => 'Question set updated successfully'
    ]);
}

function handleDeleteQuestionSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null)) respondError('Set ID is required');
    $result = svc('set')->deleteSet($data['set_id']);
    if (!($result['success'] ?? false)) {
        respondError($result['error'] ?? 'Failed to delete set', 500);
    }
    respond([
        'success' => true,
        'message' => 'Question set deleted successfully'
    ]);
}

function handleGetSetQuestions() {
    $setId = $_GET['set_id'] ?? null;
    if (!$setId) respondError('Set ID is required');
    respond([
        'success' => true,
        'questions' => svc('set')->getSetQuestions($setId)
    ]);
}

function handleAddQuestionToSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) respondError('Set ID and Question ID are required');
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
}

function handleAddQuestionToSetAtPosition() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null) || ($data['position'] ?? null) === null) {
        respondError('Set ID, Question ID and position are required');
    }
    if (!svc('set')->addQuestionAtPosition($data['set_id'], $data['question_id'], $data['position'])) respondError('Question already in set or unable to add');
    respond([
        'success' => true,
        'message' => 'Question added successfully'
    ]);
}

function handleRemoveQuestionFromSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) respondError('Set ID and Question ID are required');
    svc('set')->removeQuestion($data['set_id'], $data['question_id']);
    respond([
        'success' => true,
        'message' => 'Question removed successfully'
    ]);
}

function handleUpdateQuestionOrder() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || empty($data['questions'])) respondError('Set ID and questions are required');
    if (!svc('set')->updateQuestionsOrder($data['set_id'], $data['questions'])) respondError('Unable to update order');
    respond([
        'success' => true,
        'message' => 'Order updated successfully'
    ]);
}

// ── Misc ─────────────────────────────────────────────────────────────────

function handleGeneratePDF() {
    $roomId = authRoomId();
    if (!$roomId) respondError('Room ID not found');

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

    $url = $roomResult['base_url'] ?? '';
    if (!$url) respondError('Base URL not found for this room', 500);

    // Generate PDF with the codes and QR SVGs
    $pdf = new PDFGenerator($url);
    $pdf->addPage('MVquiz - Giocatore', $codePlayer, $svgPlayer);
    $pdf->addPage('MVquiz - Giudice', $codeJudge, $svgJudge);
    respond([
        'success'  => true,
        'pdf_data' => 'data:application/pdf;base64,' . base64_encode($pdf->getPDF()),
        'filename' => 'MVquiz-room-' . $codePlayer . '-' . $codeJudge . '.pdf'
    ]);
}
