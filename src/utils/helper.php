<?php
require_once __DIR__ . '/../services/ServiceLoader.php';

// ── Helpers ──────────────────────────────────────────────────────────────

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect(string $tab, ?string $success = null, ?string $error = null): never {
    $params = ['tab' => $tab];
    if ($success) $params['success'] = $success;
    if ($error)   $params['error']   = $error;
    header('Location: admin.php?' . http_build_query($params));
    exit;
}

// ── Action Handlers ──────────────────────────────────────────────────────

function handleCredentials(): void {
    $tab = isset($_POST['admin_new_password']) ? 'general' : 'settings';
    try {
        $result = svc('admin')->updateCredentials(
            authUserId(),
            $_POST['new_username'] ?? $_POST['admin_username'] ?? '',
            $_POST['new_password'] ?? $_POST['admin_new_password'] ?? null,
            $_POST['confirm_password'] ?? $_POST['admin_confirm_password'] ?? null
        );
        // Aggiorna la sessione admin con il nuovo username
        svc('auth')->updateAdminSession(authUserId(), $result['username']);
        redirect($tab, 'credentials_updated');
    } catch (Exception $e) {
        redirect($tab, null, $e->getMessage());
    }
}

function handleSettings(): void {
    try {
        svc('admin')->saveSettings($_POST);
        if (isAjax()) {
            $settings = svc('admin')->getAllSettings();
            jsonResponse(['success' => true, 'message' => 'Impostazioni salvate', 'settings' => $settings['settings'] ?? []]);
        }
        redirect('settings', 'settings_saved');
    } catch (Exception $e) {
        if (isAjax()) jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        redirect('settings', null, $e->getMessage());
    }
}

function handleRound(string $action): void {
    $method = $action === 'start_round' ? 'startRound' : 'closeRound';
    $result = svc('game')->$method((int)($_POST['round_id'] ?? 0));
    $ok     = $result['success'] ?? false;
    redirect('sets', $ok ? 'round_updated' : null, $ok ? null : ($result['error'] ?? 'Errore operazione round'));
}

function handleQuestion(bool $isUpdate): void {
    try {
        $data   = buildQuestionData();
        $result = $isUpdate
            ? svc('question')->updateQuestion($data)
            : svc('question')->addQuestion($data);

        if (!($result['success'] ?? false)) {
            throw new Exception($result['error'] ?? 'Errore operazione');
        }

        $msg = $isUpdate ? 'Domanda aggiornata' : 'Domanda aggiunta';
        if (isAjax()) jsonResponse(['success' => true, 'message' => $msg, 'question_id' => $result['question_id'] ?? null]);
        redirect('sets', $isUpdate ? 'question_updated' : 'question_added');
    } catch (Exception $e) {
        if (isAjax()) jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        redirect('sets', null, $e->getMessage());
    }
}

function handleDeleteQuestion(): void {
    $qId = (int)($_POST['question_id'] ?? $_GET['q_id'] ?? 0);
    if (!$qId) {
        if (isAjax()) jsonResponse(['success' => false, 'error' => 'ID invalido'], 400);
        redirect('sets', null, 'ID domanda invalido');
    }

    $result = svc('question')->deleteQuestion($qId);
    $ok     = $result['success'] ?? false;
    if (isAjax()) jsonResponse($result);
    redirect('sets', $ok ? 'question_deleted' : null, $ok ? null : ($result['error'] ?? null));
}

function handleQuestionSet(string $action): void {
    try {
        $qs   = svc('question');
        $id   = (int)($_POST['set_id'] ?? 0);
        $name = trim($_POST['set_name'] ?? '');
        $desc = $_POST['set_description'] ?? '';

        match ($action) {
            'add_questionset' => (function () use ($qs, $name, $desc) {
                if (!$name) throw new Exception('Nome set obbligatorio');
                $setId = $qs->add($name, $desc);
                jsonResponse(['success' => true, 'setId' => $setId, 'message' => 'Set creato']);
            })(),
            'update_questionset' => (function () use ($qs, $id, $name, $desc) {
                if (!$id || !$name) throw new Exception('Set ID e nome obbligatori');
                $qs->update($id, $name, $desc);
                jsonResponse(['success' => true, 'message' => 'Set aggiornato']);
            })(),
            'delete_questionset' => (function () use ($qs, $id) {
                if (!$id) throw new Exception('Set ID obbligatorio');
                $qs->delete($id);
                redirect('settings', 'set_deleted');
            })(),
        };
    } catch (Exception $e) {
        if ($action === 'delete_questionset') redirect('settings', null, $e->getMessage());
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

// ── Question Data Builder ────────────────────────────────────────────────

function buildQuestionData(): array {
    $data = [
        'question'    => trim($_POST['question'] ?? ''),
        'round_type'  => $_POST['round_type'] ?? '',
        'category_id' => (int)($_POST['category_id'] ?? 1),
        'timer'       => (int)($_POST['timer'] ?? 30),
    ];
    if (!$data['question'])   throw new Exception('Domanda obbligatoria');
    if (!$data['round_type']) throw new Exception('Tipo domanda obbligatorio');

    if (isset($_POST['question_id'])) {
        $data['id'] = (int)$_POST['question_id'];
        if (!$data['id']) throw new Exception('ID domanda mancante');
    }

    if (in_array($data['round_type'], ['truefalse', 'multiple'], true)) {
        $data['correct_answer'] = (int)($_POST['correct_answer'] ?? 1);
        $isTF     = $data['round_type'] === 'truefalse';
        $max      = $isTF ? 2 : 4;
        for ($i = 1; $i <= $max; $i++) {
            $data["answer$i"] = $_POST["answer$i"] ?? '';
        }
    }
    return $data;
}

// ── Data Loaders ─────────────────────────────────────────────────────────

function loadQuestionsTab(): array {
    if (!empty($_POST['questions_page'])) {
        $_SESSION['questions_page'] = (int)$_POST['questions_page'];
    }

    $all    = svc('question')->getAllQuestions(1, 1000)['questions'] ?? [];
    $cat    = $_POST['category'] ?? '';
    $search = strtolower(trim($_POST['search_query'] ?? ''));
    $type   = $_POST['search_type'] ?? 'contains';

    if ($cat || $search) {
        $catId = $cat ? (int)$cat : null;
        $all   = array_values(array_filter($all, fn($q) => matchesFilter($q, $catId, $search, $type)));
    }

    $total   = count($all);
    $perPage = 10;
    $page    = $_SESSION['questions_page'];

    return [
        'questions'  => array_slice($all, ($page - 1) * $perPage, $perPage),
        'categories' => svc('question')->getAllCategories(),
        'pagination' => compact('total', 'page', 'perPage') + ['totalPages' => (int)ceil($total / $perPage)],
    ];
}

function matchesFilter(array $q, ?int $catId, string $search, string $type): bool {
    if ($catId && (int)($q['category_id'] ?? 1) !== $catId) return false;
    if (!$search) return true;

    $text = strtolower($q['question'] ?? '');
    return match ($type) {
        'exact'       => $text === $search,
        'starts_with' => str_starts_with($text, $search),
        'ends_with'   => str_ends_with($text, $search),
        default       => str_contains($text, $search),
    };
}

function loadSetsTab(): array {
    if (!empty($_POST['sets_page'])) {
        $_SESSION['sets_page'] = (int)$_POST['sets_page'];
    }

    $search   = $_POST['set_search_query'] ?? '';
    $setsData = $search
        ? svc('question')->search($search, $_POST['set_search_type'] ?? 'contains', $_SESSION['sets_page'])
        : svc('question')->getAll($_SESSION['sets_page']);

    return [
        'questionSets' => $setsData['sets'] ?? [],
        'pagination'   => $setsData,
    ];
}

function loadGeneralTab(): array {
    $result = svc('admin')->getAllSettings();
    return ['gameSettings' => $result['success'] ? $result['settings'] : []];
}

function loadGameRoom(): array {
    $settings     = svc('admin')->getAllSettings();
    $gameSettings = $settings['settings'] ?? [];

    $setsResult   = svc('question')->getAllQuestionSets(1, 100);
    $questionSets = $setsResult['sets'] ?? [];

    $selectedSetId = isset($_GET['set_id']) ? (int)$_GET['set_id'] : null;
    $selectedSet   = $selectedSetId
        ? svc('question')->getQuestionSetById($selectedSetId)
        : null;

    return compact('gameSettings', 'questionSets', 'selectedSetId', 'selectedSet');
}

function loadRoomAdmin(): array {
    $roomId = (int)($_GET['room_id'] ?? $_SESSION['room_id'] ?? 0);
    if (!$roomId) {
        header('Location: admin.php');
        exit;
    }

    $room = svc('room')->getRoomById($roomId);
    if (!$room) {
        header('Location: admin.php');
        exit;
    }

    $_SESSION['room_id'] = $roomId;

    // Counter: incrementa ogni volta che l'admin clicca "Prossima Domanda"
    $counterKey = 'round_counter_' . $roomId;
    $counter    = $_SESSION[$counterKey] ?? 1;

    // Domanda corrente e round attivo
    $question    = svc('question')->getQuestionByCounter($room['qset_id'], $counter);
    $activeRound = null;
    if ($question) {
        $round = svc('room')->getActiveRound($room['id']);
        if ($round && $round['question_id'] == $question['id']) {
            $activeRound = $round;
        }
    }

    $gameOver = !$question;

    // Se non ci sono più domande e la stanza è ancora 'running', chiudila
    if ($gameOver && ($room['status_room'] ?? '') === 'running') {
        svc('room')->finishGame($roomId);
        // Aggiorna il dato locale per coerenza
        $room['status_room'] = 'closed';
    }

    // Info stanza (cached in sessione)
    $roomInfoKey = 'room_info_' . $roomId;
    if (!isset($_SESSION[$roomInfoKey])) {
        $_SESSION[$roomInfoKey] = [
            'num_players'     => svc('room')->getPlayerCount($roomId),
            'total_questions' => svc('question')->getQuestionCountByQset($room['qset_id']),
        ];
    }
    $roomInfo = $_SESSION[$roomInfoKey];

    $judgeConnected = svc('room')->isJudgeConnected($roomId);

    return compact('roomId', 'room', 'counter', 'question', 'activeRound', 'gameOver', 'roomInfo', 'judgeConnected');
}
