<?php
session_start();

require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/PDFGenerator.php';
require_once __DIR__ . '/../utils/QRGenerator.php';
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
    respond(['success' => false, 'error' => $msg]);
}

function requirePost()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        respondError('Metodo non consentito', 405);
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

    case 'leaderboard':
        handleLeaderboard();
        break;

    case 'round_answers':
        handleRoundAnswers();
        break;

    case 'final_leaderboard':
        handleFinalLeaderboard();
        break;

    case 'get_question':
        handleGetQuestion();
        break;

    case 'get_questions':
        handleGetQuestions();
        break;

    case 'add_category':
        handleAddCategory();
        break;

    case 'update_category':
        handleUpdateCategory();
        break;

    case 'delete_category':
        handleDeleteCategory();
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

    case 'update_questionset_metadata':
        handleUpdateQuestionSetMetadata();
        break;

    case 'delete_questionset':
        handleDeleteQuestionSet();
        break;

    case 'get_questionsets':
        handleGetQuestionSets();
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

    case 'move_question_up':
        handleMoveQuestionUp();
        break;

    case 'move_question_down':
        handleMoveQuestionDown();
        break;

    default:
        respondError('Endpoint non trovato', 404);
}

// ── Auth ─────────────────────────────────────────────────────────────────

function handlePlayerLogin() {
    requirePost();
    $data = input();
    try {
        svc('auth')->playerLogin($data['username'] ?? '', $data['room_code'] ?? '');
        respond(['success' => true, 'redirect' => '../public/player.php']);
    } catch (Exception $e) {
        respondError($e->getMessage(), 401);
    }
}

function handleAdminLogin() {
    requirePost();
    $data = input();
    try {
        svc('auth')->adminLogin($data['username'] ?? '', $data['password'] ?? '');
        respond(['success' => true, 'redirect' => '../public/admin.php']);
    } catch (Exception $e) {
        respondError($e->getMessage(), 401);
    }
}

function handleJudgeLogin() {
    requirePost();
    $data = input();
    try {
        svc('auth')->judgeLogin($data['room_code'] ?? '');
        respond(['success' => true, 'redirect' => '../public/game.php']);
    } catch (Exception $e) {
        respondError($e->getMessage(), 401);
    }
}

// ── Game ─────────────────────────────────────────────────────────────────

function handleAnswer() {
    requireLoginJson();
    requirePost();
    $data = input();

    if (!($data['round_id'] ?? 0))      respondError('Round ID mancante');
    if (($data['answer'] ?? '') === '')  respondError('Risposta mancante');

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
        $result = $room->createRoom(input()['question_set_id'] ?? null);

        if ($result['success']) {
            $_SESSION['code_player'] = $result['code_player'];
            $_SESSION['code_judge']  = $result['code_judge'];

            $roomData = $room->getRoomDetails($result['code_player']);
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
        $roomCode = input()['code_player'] ?? $_SESSION['code_player'] ?? null;
        if (!$roomCode) throw new Exception('Nessuna stanza attiva nella sessione');
        respond(svc('room')->startRoom($roomCode));
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleCheckRoomStatus() {
    requireLoginJson();
    try {
        $roomCode = authRoomCode();
        if (!$roomCode) respond(['room_open' => false, 'status' => 'unknown', 'message' => 'Nessuna stanza associata']);

        $roomData = svc('room')->getRoomDetails($roomCode);
        if (!$roomData) respond(['room_open' => false, 'status' => 'unknown', 'message' => 'Stanza non trovata']);

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
        $codePlayer = $_SESSION['code_player'] ?? $_GET['code_player'] ?? null;
        if (!$codePlayer) throw new Exception('Codice stanza mancante');

        $result = svc('room')->cancelRoom($codePlayer);
        if ($result['success']) { unset($_SESSION['code_player'], $_SESSION['code_judge']); }
        respond($result);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleConnectedDevices() {
    try {
        $codePlayer = $_SESSION['code_player'] ?? $_GET['code_player'] ?? null;
        if (!$codePlayer) respond(['success' => true, 'devices' => [], 'count' => 0]);

        $result = svc('room')->getConnectedDevices($codePlayer);
        respond(['success' => true, 'devices' => $result['devices'], 'count' => $result['count']]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

// ── Game Actions ─────────────────────────────────────────────────────────

function handleGame($action) {
    requireLoginJson();
    match ($action) {
        'get_game_state' => handleGetGameState(),
        'get_set'        => handleGetSet(),
        'start_round'    => handleStartRound(),
        'close_round'    => handleCloseRound(),
        'check_winner'   => handleCheckWinner(),
        'reset_game'     => handleResetGame(),
        default          => respondError('Azione non valida'),
    };
}

function handleGetGameState() {
    try {
        $roomCode = authRoomCode();
        if (!$roomCode) respond(['success' => false]);

        $room = svc('room');
        $roomData = $room->getRoomDetails($roomCode);
        if (!$roomData) respond(['success' => false]);

        $statusRoom = $roomData['status_room'] ?? null;

        // Se la room è chiusa o cancellata, comunicalo subito al player con is_winner
        if ($statusRoom === 'closed' || ($roomData['has_winner'] ?? false)) {
            // Se non c'è ancora un vincitore, marcalo ora
            if (!($roomData['has_winner'] ?? false)) {
                $room->markWinner($roomData['id']);
                $roomData = $room->getRoomDetails($roomCode);
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
            : ['success' => false, 'status_room' => $statusRoom];
        respond($response);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleGetSet() {
    $setId = $_GET['set_id'] ?? 0;
    if (!$setId) respondError('Set ID mancante');

    $result = svc('question')->getSetQuestions($setId);
    respond($result['success']
        ? ['success' => true, 'set' => $result['questions'], 'questions' => $result['questions']['questions'] ?? []]
        : ['success' => false, 'message' => $result['error'] ?? 'Set non trovato']
    );
}

function handleStartRound() {
    $questionId = $_GET['question_id'] ?? 0;
    if (!$questionId && $_SERVER['REQUEST_METHOD'] === 'POST') $questionId = input()['question_id'] ?? 0;
    if (!$questionId) respondError('Question ID required');

    $roomCode = authRoomCode();
    if (!$roomCode) respondError('Room code not found');

    try { respond(svc('game')->startRound($questionId, $roomCode)); }
    catch (Exception $e) { respondError('Server error: ' . $e->getMessage(), 500); }
}

function handleCloseRound() {
    $roundId = $_GET['round_id'] ?? 0;
    if (!$roundId && $_SERVER['REQUEST_METHOD'] === 'POST') $roundId = input()['round_id'] ?? 0;
    if (!$roundId) respondError('Round ID mancante');

    try { respond(svc('game')->closeRound($roundId)); }
    catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleCheckWinner() {
    try {
        $roomCode = authRoomCode();
        $fail = ['success' => false, 'is_winner' => false];
        if (!$roomCode) respond($fail);

        $room = svc('room');
        $roomData = $room->getRoomDetails($roomCode);
        if (!$roomData) respond($fail);

        // Se la room è chiusa/closed ma non c'è ancora un vincitore, marcalo ora
        if (!($roomData['has_winner'] ?? false) && ($roomData['status_room'] ?? '') === 'closed') {
            $room->markWinner($roomData['id']);
            // Ricarica per avere il vincitore aggiornato
            $roomData = $room->getRoomDetails($roomCode);
        }

        $playerId = authPlayerId();
        if (!$playerId) respond($fail);

        $isWinner = $room->isWinner($roomData['id'], $playerId);

        if ($roomData['has_winner'] ?? false) {
            respond(['success' => true, 'game_finished' => true, 'is_winner' => $isWinner, 'winner_id' => $roomData['winner_id'] ?? null]);
        }
        respond(['success' => true, 'is_winner' => $isWinner]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleResetGame() {
    requireAdminJson();
    $questionSetId = input()['question_set_id'] ?? 0;
    if (!$questionSetId) respondError('Question Set ID required');

    try {
        svc('game')->resetGameByQuestionSet($questionSetId);
        respond(['success' => true, 'message' => 'Game reset']);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}

function handleLeaderboard() {
    requireLoginJson();
    respond(svc('game')->getLeaderboard(authRoomCode() ?? $_GET['room_code'] ?? null));
}

function handleRoundAnswers() {
    requireLoginJson();
    $roundId = $_GET['round_id'] ?? 0;
    if (!$roundId) respond(['success' => false, 'top_answers' => [], 'message' => 'Round ID required']);
    respond(['success' => true, 'top_answers' => svc('game')->getTopAnswers($roundId, 10)]);
}

function handleFinalLeaderboard() {
    requireLoginJson();
    $roomId   = $_GET['room_id'] ?? null;
    $roomCode = $_GET['room_code'] ?? authRoomCode();
    if (!$roomId && !$roomCode) respond(['success' => false, 'leaderboard' => []]);
    respond(svc('game')->getFinalLeaderboard($roomCode, $roomId));
}

// ── Questions ────────────────────────────────────────────────────────────

function handleGetQuestion() {
    $id = $_GET['id'] ?? null;
    if (!$id) respondError('Question ID required');
    try {
        $result = svc('question')->getQuestionById($id);
        if (!$result) respondError('Question not found', 404);
        respond(['success' => true, 'question' => $result]);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleGetQuestions() {
    try {
        respond(['success' => true, 'questions' => svc('question')->getAllQuestions(1, 1000)['questions']]);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

// ── Categories ───────────────────────────────────────────────────────────

function handleAddCategory() {
    requirePost();
    $data = input();
    if (!($data['name'] ?? null) || !($data['color'] ?? null)) respondError('Nome e colore obbligatori');
    try {
        $result = svc('question')->addCategory($data['name'], $data['color']);
        if (!$result) respondError('Errore nell\'aggiunta della categoria', 500);
        respond(['success' => true, 'message' => 'Categoria aggiunta con successo', 'categoryId' => $result]);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleUpdateCategory() {
    requirePost();
    $data = input();
    if (!($data['id'] ?? null) || !($data['name'] ?? null) || !($data['color'] ?? null)) respondError('ID, nome e colore obbligatori');
    try {
        $result = svc('question')->updateCategory($data['id'], $data['name'], $data['color']);
        if (!$result) respondError('Errore nell\'aggiornamento della categoria', 500);
        respond(['success' => true, 'message' => 'Categoria aggiornata con successo']);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleDeleteCategory() {
    requirePost();
    $data = input();
    if (!($data['id'] ?? null)) respondError('ID obbligatorio');
    try {
        $result = svc('question')->deleteCategory($data['id']);
        respond($result
            ? ['success' => true, 'message' => 'Categoria eliminata con successo. Le domande associate sono state spostate alla categoria Generale.']
            : ['success' => false, 'message' => 'Non puoi eliminare la categoria Generale.']
        );
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleGetCategories() {
    try { respond(['success' => true, 'categories' => svc('question')->getAllCategories()]); }
    catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleSaveCategories() {
    requirePost();
    $data = input();
    $q = svc('question');
    try {
        foreach ($data['deleted'] ?? [] as $id)  $q->deleteCategory($id);
        foreach ($data['updated'] ?? [] as $cat) { if (isset($cat['id'], $cat['name'], $cat['color'])) $q->updateCategory($cat['id'], $cat['name'], $cat['color']); }
        foreach ($data['added']   ?? [] as $cat) { if (isset($cat['name'], $cat['color'])) $q->addCategory($cat['name'], $cat['color']); }
        respond(['success' => true, 'message' => 'Tutte le modifiche sono state salvate con successo']);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

// ── Question Sets ────────────────────────────────────────────────────────

function handleGetQuestionSet() {
    $setId = $_GET['id'] ?? null;
    if (!$setId) respondError('Set ID is required');
    try {
        $set = svc('question')->getById($setId);
        if (!$set) respondError('Question set not found', 404);
        respond(['success' => true, 'set' => $set]);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleAddQuestionSet() {
    requirePost();
    $data    = input();
    $setName = $data['set_name'] ?? $_POST['set_name'] ?? '';
    if (!$setName) respondError('Set name is required');
    try {
        $setId = svc('question')->add($setName, $data['set_description'] ?? $_POST['set_description'] ?? '');
        respond(['success' => true, 'set_id' => $setId, 'message' => 'Question set created successfully']);
    } catch (Exception $e) { respondError($e->getMessage()); }
}

function handleUpdateQuestionSet() {
    requirePost();
    $setId   = $_POST['set_id'] ?? null;
    $setName = $_POST['set_name'] ?? '';
    if (!$setId || !$setName) respondError('Set ID and name are required');
    try {
        svc('question')->update($setId, $setName, $_POST['set_description'] ?? '');
        respond(['success' => true, 'message' => 'Question set updated successfully']);
    } catch (Exception $e) { respondError($e->getMessage()); }
}

function handleUpdateQuestionSetMetadata() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['set_name'] ?? '')) respondError('Set ID and name are required');
    try {
        $q = svc('question');
        $q->update($data['set_id'], $data['set_name'], $data['set_description'] ?? '');
        if (($data['is_saved'] ?? null) !== null) $q->setSaved($data['set_id'], $data['is_saved']);
        respond(['success' => true, 'message' => 'Question set metadata updated successfully']);
    } catch (Exception $e) { respondError($e->getMessage()); }
}

function handleDeleteQuestionSet() {
    requirePost();
    $setId = $_POST['set_id'] ?? null;
    if (!$setId) respondError('Set ID is required');
    try {
        svc('question')->delete($setId);
        respond(['success' => true, 'message' => 'Question set deleted successfully']);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleGetQuestionSets() {
    $page   = $_GET['page'] ?? 1;
    $search = $_GET['search'] ?? '';
    try {
        $data = $search
            ? svc('question')->search($search, $_GET['search_type'] ?? 'contains', $page)
            : svc('question')->getAll($page);
        respond(['success' => true, 'sets' => $data['sets'], 'total' => $data['total'], 'page' => $data['page'], 'totalPages' => $data['totalPages']]);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleGetSetQuestions() {
    $setId = $_GET['set_id'] ?? null;
    if (!$setId) respondError('Set ID is required');
    try {
        respond(['success' => true, 'questions' => svc('question')->getQuestions($setId)]);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleAddQuestionToSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) respondError('Set ID and Question ID are required');
    try {
        $q = svc('question');
        $questions = $q->getQuestions($data['set_id']);
        $maxOrder = 0;
        foreach ($questions as $qItem) {
            if ($qItem['order_in_set'] > $maxOrder) $maxOrder = $qItem['order_in_set'];
            if ($qItem['id'] == $data['question_id']) respondError('Question already in set');
        }
        if (!$q->addQuestionToSet($data['set_id'], $data['question_id'], $maxOrder + 1)) respondError('Question already in set or unable to add');
        respond(['success' => true, 'message' => 'Question added successfully']);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleAddQuestionToSetAtPosition() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null) || ($data['position'] ?? null) === null) {
        respondError('Set ID, Question ID and position are required');
    }
    try {
        if (!svc('question')->addQuestionAtPosition($data['set_id'], $data['question_id'], $data['position'])) respondError('Question already in set or unable to add');
        respond(['success' => true, 'message' => 'Question added successfully']);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleRemoveQuestionFromSet() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) respondError('Set ID and Question ID are required');
    try {
        svc('question')->removeQuestion($data['set_id'], $data['question_id']);
        respond(['success' => true, 'message' => 'Question removed successfully']);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleUpdateQuestionOrder() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || empty($data['questions'])) respondError('Set ID and questions are required');
    try {
        if (!svc('question')->updateQuestionsOrder($data['set_id'], $data['questions'])) respondError('Unable to update order');
        respond(['success' => true, 'message' => 'Order updated successfully']);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleMoveQuestionUp() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) respondError('Set ID and Question ID are required');
    try {
        svc('question')->moveQuestionUp($data['set_id'], $data['question_id']);
        respond(['success' => true, 'message' => 'Question moved up successfully']);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

function handleMoveQuestionDown() {
    requirePost();
    $data = input();
    if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) respondError('Set ID and Question ID are required');
    try {
        svc('question')->moveQuestionDown($data['set_id'], $data['question_id']);
        respond(['success' => true, 'message' => 'Question moved down successfully']);
    } catch (Exception $e) { respondError($e->getMessage(), 500); }
}

// ── Misc ─────────────────────────────────────────────────────────────────

function handleGeneratePDF() {
    $data     = input();
    $roomCode = $data['room_code'] ?? $_POST['room_code'] ?? '';
    if (!$roomCode) respondError('Room code required');

    try {
        $room = svc('room');
        $roomResult = $room->getRoomDetails($roomCode);
        if (!$roomResult) respondError('Room not found', 404);

        $codePlayer = $roomResult['code_player'] ?? null;
        $codeJudge  = $roomResult['code_judge'] ?? null;
        if (!$codePlayer || !$codeJudge) respondError('Both room codes are required');

        // Recupera SVG QR dal DB (base64) e decodifica
        $svgPlayer = !empty($roomResult['qr_uri_player']) ? base64_decode($roomResult['qr_uri_player']) : null;
        $svgJudge  = !empty($roomResult['qr_uri_judge'])  ? base64_decode($roomResult['qr_uri_judge'])  : null;
        if (!$svgPlayer || !$svgJudge) respondError('QR codes not found for this room', 500);

        $pdf = new PDFGenerator($codePlayer, $codeJudge, $svgPlayer, $svgJudge);
        $pdf->generate();
        respond([
            'success'  => true,
            'pdf_data' => 'data:application/pdf;base64,' . base64_encode($pdf->getPDF()),
            'filename' => 'Mvquiz-stanza-' . $codePlayer . '-' . $codeJudge . '.pdf'
        ]);
    } catch (Exception $e) {
        respondError($e->getMessage(), 500);
    }
}
