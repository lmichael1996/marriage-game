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
    $result = svc('auth')->updateCredentials(
        authUserId(),
        $_POST['admin_username'] ?? '',
        $_POST['admin_new_password'] ?? null,
        $_POST['admin_confirm_password'] ?? null
    );
    if (!($result['success'] ?? false)) {
        redirect($tab, null, $result['error'] ?? 'Failed to update credentials');
        return;
    }
    svc('auth')->updateAdminSession(authUserId(), $result['username']);
    redirect($tab, 'credentials_updated');
}

function handleSettings(): void {
    $result = svc('settings')->saveSettings($_POST);
    if (!($result['success'] ?? false)) {
        $error = $result['error'] ?? 'Failed to save settings';
        if (isAjax()) jsonResponse(['success' => false, 'error' => $error], 400);
        redirect('settings', null, $error);
        return;
    }
    if (isAjax()) {
        $settings = svc('settings')->getAllSettings();
        jsonResponse([
            'success' => true,
            'message' => 'Settings saved',
            'settings' => $settings['settings']
        ]);
    }
    redirect('settings', 'settings_saved');
}

function handleRound(string $action): void {
    $method = $action === 'start_round' ? 'startRound' : 'closeRound';
    $result = svc('game')->$method((int)($_POST['round_id'] ?? 0));
    $ok     = $result['success'] ?? false;
    redirect('sets', $ok ? 'round_updated' : null, $ok ? null : ($result['error'] ?? 'Round operation error'));
}

function handleQuestion(bool $isUpdate): void {
    $data = buildQuestionData();
    if (!($data['success'] ?? true)) {
        $error = $data['error'] ?? 'Invalid question data';
        if (isAjax()) jsonResponse(['success' => false, 'error' => $error], 400);
        redirect('sets', null, $error);
        return;
    }

    $result = $isUpdate
        ? svc('question')->updateQuestion($data)
        : svc('question')->addQuestion($data);

    if (!($result['success'] ?? false)) {
        $error = $result['error'] ?? 'Operation error';
        if (isAjax()) jsonResponse(['success' => false, 'error' => $error], 400);
        redirect('sets', null, $error);
        return;
    }

    $msg = $isUpdate ? 'Question updated' : 'Question added';
    if (isAjax()) jsonResponse(['success' => true, 'message' => $msg, 'question_id' => $result['question_id'] ?? null]);
    redirect('sets', $isUpdate ? 'question_updated' : 'question_added');
}

function handleDeleteQuestion(): void {
    $qId = (int)($_POST['question_id'] ?? $_GET['q_id'] ?? 0);
    if (!$qId) {
        if (isAjax()) jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400);
        redirect('sets', null, 'Invalid question ID');
    }

    $result = svc('question')->deleteQuestion($qId);
    $ok     = $result['success'] ?? false;
    if (isAjax()) jsonResponse($result);
    redirect('sets', $ok ? 'question_deleted' : null, $ok ? null : ($result['error'] ?? null));
}

function handleQuestionSet(string $action): void {
    $qs   = svc('set');
    $id   = (int)($_POST['set_id'] ?? 0);
    $name = trim($_POST['set_name'] ?? '');
    $desc = $_POST['set_description'] ?? '';

    match ($action) {
        'add_questionset' => (function () use ($qs, $name, $desc) {
            if (!$name) {
                jsonResponse(['success' => false, 'error' => 'Set name is required'], 400);
                return;
            }
            $result = $qs->addSet($name, $desc);
            if (is_array($result)) {
                jsonResponse($result, 400);
                return;
            }
            jsonResponse(['success' => true, 'setId' => $result, 'message' => 'Set created']);
        })(),
        'update_questionset' => (function () use ($qs, $id, $name, $desc) {
            if (!$id || !$name) {
                jsonResponse(['success' => false, 'error' => 'Set ID and name are required'], 400);
                return;
            }
            $result = $qs->updateSet($id, $name, $desc);
            if (!($result['success'] ?? false)) {
                jsonResponse($result, 400);
                return;
            }
            jsonResponse(['success' => true, 'message' => 'Set updated']);
        })(),
        'delete_questionset' => (function () use ($qs, $id) {
            if (!$id) {
                redirect('settings', null, 'Set ID is required');
                return;
            }
            $result = $qs->deleteSet($id);
            if (!($result['success'] ?? false)) {
                redirect('settings', null, $result['error'] ?? 'Failed to delete set');
                return;
            }
            redirect('settings', 'set_deleted');
        })(),
    };
}

// ── Question Data Builder ────────────────────────────────────────────────

function buildQuestionData(): array {
    $data = [
        'question'    => trim($_POST['question'] ?? ''),
        'question_type'  => $_POST['question_type'] ?? '',
        'category_id' => (int)($_POST['category_id'] ?? 1),
        'timer'       => (int)($_POST['timer'] ?? 30),
    ];
    if (!$data['question'])   return ['success' => false, 'error' => 'Question is required'];
    if (!$data['question_type']) return ['success' => false, 'error' => 'Question type is required'];

    if (isset($_POST['question_id'])) {
        $data['id'] = (int)$_POST['question_id'];
        if (!$data['id']) return ['success' => false, 'error' => 'Question ID is missing'];
    }

    if (in_array($data['question_type'], ['truefalse', 'multiple'], true)) {
        $data['correct_answer'] = (int)($_POST['correct_answer'] ?? 1);
        $isTF     = $data['question_type'] === 'truefalse';
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

    $all    = svc('question')->getAllQuestions(1, 1000)['questions'];
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
        ? svc('set')->searchSets($search, $_POST['set_search_type'] ?? 'contains', $_SESSION['sets_page'])
        : svc('set')->getAllQuestionSets($_SESSION['sets_page']);

    return [
        'questionSets' => $setsData['sets'],
        'pagination'   => $setsData,
    ];
}

function loadGeneralTab(): array {
    return ['gameSettings' => svc('settings')->getAllSettings()['settings']];
}

function loadGameRoom(): array {
    $gameSettings = svc('settings')->getAllSettings()['settings'];
    $questionSets = svc('set')->getAllQuestionSets(1, 100)['sets'];

    $selectedSetId = isset($_GET['set_id']) ? (int)$_GET['set_id'] : null;
    $selectedSet   = $selectedSetId
        ? svc('set')->getQuestionSetById($selectedSetId)
        : null;

    return compact('gameSettings', 'questionSets', 'selectedSetId', 'selectedSet');
}

function loadRoomAdmin(): array {
    $roomId = (int)($_GET['room_id'] ?? $_SESSION['room_id'] ?? 0);
    $room   = $roomId ? svc('room')->getRoomById($roomId) : null;
    if (!$room) {
        header('Location: admin.php');
        exit;
    }

    $_SESSION['room_id'] = $roomId;
    $_SESSION['active_room_code'] = $room['code_player'];

    // Counter: increments every time the admin clicks "Next Question"
    $counterKey = 'round_counter_' . $roomId;
    $counter    = $_SESSION[$counterKey] ?? 1;

    // Current question and active round
    $question    = svc('set')->getQuestionByCounter($room['qset_id'], $counter);
    $activeRound = null;
    if ($question) {
        $round = svc('room')->getActiveRound($room['id']);
        if ($round && $round['question_id'] == $question['id']) {
            $activeRound = $round;
        }
    }

    $gameOver = !$question;

    // If no more questions and the room is still 'running', close it
    if ($gameOver && $room['status_room'] === 'running') {
        svc('room')->finishGame($roomId);
        // Update local data for consistency
        $room['status_room'] = 'closed';
    }

    // Room info (cached in session)
    $roomInfoKey = 'room_info_' . $roomId;
    if (!isset($_SESSION[$roomInfoKey])) {
        $_SESSION[$roomInfoKey] = [
            'num_players'     => svc('room')->getPlayerCount($roomId),
            'total_questions' => svc('set')->getQuestionCountByQset($room['qset_id']),
        ];
    }
    $roomInfo = $_SESSION[$roomInfoKey];

    $judgeConnected = svc('room')->isJudgeConnected($roomId);

    return compact('roomId', 'room', 'counter', 'question', 'activeRound', 'gameOver', 'roomInfo', 'judgeConnected');
}

function getTableData(): array {
    return svc('table')->getAllTablesData();
}
