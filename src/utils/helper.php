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
    $result = Container::auth()->updateCredentials(
        authUserId(),
        $_POST['admin_username'] ?? '',
        $_POST['admin_new_password'] ?? null,
        $_POST['admin_confirm_password'] ?? null
    );
    if (!($result['success'] ?? false)) {
        redirect($tab, null, $result['error'] ?? 'Impossibile aggiornare le credenziali');
        return;
    }
    Container::auth()->updateAdminSession(authUserId(), $result['username']);
    redirect($tab, 'credentials_updated');
}

function handleSettings(): void {
    $result = Container::settings()->saveSettings($_POST);
    if (!($result['success'] ?? false)) {
        $error = $result['error'] ?? 'Impossibile salvare le impostazioni';
        if (isAjax()) jsonResponse(['success' => false, 'error' => $error], 400);
        redirect('settings', null, $error);
        return;
    }
    if (isAjax()) {
        $settings = Container::settings()->getAllSettings();
        jsonResponse([
            'success' => true,
            'message' => 'Impostazioni salvate',
            'settings' => $settings['settings']
        ]);
    }
    redirect('settings', 'settings_saved');
}

function handleQuestion(bool $isUpdate): void {
    $data = buildQuestionData();
    if (!($data['success'] ?? true)) {
        $error = $data['error'] ?? 'Dati domanda non validi';
        if (isAjax()) jsonResponse(['success' => false, 'error' => $error], 400);
        redirect('sets', null, $error);
        return;
    }

    $result = $isUpdate
        ? Container::question()->updateQuestion($data)
        : Container::question()->addQuestion($data);

    if (!($result['success'] ?? false)) {
        $error = $result['error'] ?? 'Errore durante l\'operazione';
        if (isAjax()) jsonResponse(['success' => false, 'error' => $error], 400);
        redirect('sets', null, $error);
        return;
    }

    $msg = $isUpdate ? 'Domanda aggiornata' : 'Domanda aggiunta';
    if (isAjax()) jsonResponse(['success' => true, 'message' => $msg, 'question_id' => $result['question_id'] ?? null]);
    redirect('sets', $isUpdate ? 'question_updated' : 'question_added');
}

function handleDeleteQuestion(): void {
    $qId = (int)($_POST['question_id'] ?? $_GET['q_id'] ?? 0);
    if (!$qId) {
        if (isAjax()) jsonResponse(['success' => false, 'error' => 'ID non valido'], 400);
        redirect('sets', null, 'ID domanda non valido');
    }

    $result = Container::question()->deleteQuestion($qId);
    $ok     = $result['success'] ?? false;
    if (isAjax()) jsonResponse($result);
    redirect('sets', $ok ? 'question_deleted' : null, $ok ? null : ($result['error'] ?? null));
}

function handleQuestionSet(): void {
    $qs = Container::set();
    $id = (int)($_POST['set_id'] ?? 0);

    if (!$id) {
        redirect('settings', null, 'ID set obbligatorio');
        return;
    }
    $result = $qs->deleteSet($id);
    if (!($result['success'] ?? false)) {
        redirect('settings', null, $result['error'] ?? 'Impossibile eliminare il set');
        return;
    }
    redirect('settings', 'set_deleted');
}

// ── Question Data Builder ────────────────────────────────────────────────

function buildQuestionData(): array {
    $data = [
        'question'    => trim($_POST['question'] ?? ''),
        'question_type'  => $_POST['question_type'] ?? '',
        'category_id' => (int)($_POST['category_id'] ?? 1),
        'timer'       => (int)($_POST['timer'] ?? 30),
    ];
    if (!$data['question'])   return ['success' => false, 'error' => 'Domanda obbligatoria'];
    if (!$data['question_type']) return ['success' => false, 'error' => 'Tipo domanda obbligatorio'];

    if (isset($_POST['question_id'])) {
        $data['id'] = (int)$_POST['question_id'];
        if (!$data['id']) return ['success' => false, 'error' => 'ID domanda mancante'];
    }

    if (in_array($data['question_type'], ['truefalse', 'multiple'], true)) {
        $data['correct_answer'] = (int)($_POST['correct_answer'] ?? 1);
        $isTF     = $data['question_type'] === 'truefalse';
        $max      = $isTF ? 2 : 4;
        for ($i = 1; $i <= $max; $i++) {
            $data["answer$i"] = $_POST["answer$i"] ?? '';
        }
    }

    // Handle image upload
    $imageUrl = $_POST['existing_image_url'] ?? null;
    if (!empty($_POST['remove_image'])) {
        if ($imageUrl) {
            $old = dirname(__DIR__, 2) . '/assets/image/questions/' . $imageUrl;
            if (file_exists($old)) unlink($old);
        }
        $imageUrl = null;
    } elseif (!empty($_FILES['question_image']['name'])) {
        $file    = $_FILES['question_image'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed, true)) {
            return ['success' => false, 'error' => 'Formato immagine non supportato (jpeg/png/gif/webp)'];
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Immagine troppo grande (max 5 MB)'];
        }
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('q_', true) . '.' . $ext;
        $uploadDir = dirname(__DIR__, 2) . '/assets/image/questions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            if ($imageUrl) {
                $old = $uploadDir . $imageUrl;
                if (file_exists($old)) unlink($old);
            }
            $imageUrl = $filename;
        }
    }
    $data['image_url'] = $imageUrl ?: null;

    return $data;
}

// ── Data Loaders ─────────────────────────────────────────────────────────

function loadQuestionsTab(): array {
    if (!empty($_POST['questions_page'])) {
        $_SESSION['questions_page'] = (int)$_POST['questions_page'];
    }

    $all    = Container::question()->getAllQuestions(1, 1000)['questions'];
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
        'categories' => Container::question()->getAllCategories(),
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
        ? Container::set()->searchSets($search, $_POST['set_search_type'] ?? 'contains', $_SESSION['sets_page'])
        : Container::set()->getAllQuestionSets($_SESSION['sets_page']);

    return [
        'questionSets' => $setsData['sets'],
        'pagination'   => $setsData,
    ];
}

function loadGeneralTab(): array {
    return ['gameSettings' => Container::settings()->getAllSettings()['settings']];
}

function loadGameRoom(): array {
    $gameSettings = Container::settings()->getAllSettings()['settings'];
    $questionSets = Container::set()->getAllQuestionSets(1, 100)['sets'];

    $selectedSetId = isset($_GET['set_id']) ? (int)$_GET['set_id'] : null;
    $selectedSet   = $selectedSetId
        ? Container::set()->getQuestionSetById($selectedSetId)
        : null;

    return compact('gameSettings', 'questionSets', 'selectedSetId', 'selectedSet');
}

function loadRoomAdmin(): array {
    $roomId = (int)($_GET['room_id'] ?? $_SESSION['room_id'] ?? 0);
    $room   = $roomId ? Container::room()->getRoomById($roomId) : null;
    if (!$room) {
        header('Location: admin.php');
        exit;
    }

    $_SESSION['room_id'] = $roomId;
    $_SESSION['active_room_code'] = $room['code_player'];

    $roundCount  = Container::room()->getRoundCount($roomId);
    $activeRound = Container::room()->getActiveRound($roomId);
    $counter     = $activeRound ? $roundCount : $roundCount + 1;
    $question    = Container::set()->getQuestionByCounter($room['qset_id'], $counter);

    // Discard activeRound if it doesn't match the current question
    if ($activeRound && (!$question || $activeRound['question_id'] != $question['id'])) {
        $activeRound = null;
    }

    $gameOver = !$question;

    if ($gameOver && $room['status_room'] === 'running') {
        Container::room()->finishGame($roomId);
        $room['status_room'] = 'closed';
    }

    $roomInfo = [
        'num_players'     => Container::room()->getPlayerCount($roomId),
        'total_questions' => Container::set()->getQuestionCountByQset($room['qset_id']),
    ];

    $judgeConnected = Container::room()->isJudgeConnected($roomId);

    return compact('roomId', 'room', 'counter', 'question', 'activeRound', 'gameOver', 'roomInfo', 'judgeConnected');
}

function getTableData(): array {
    return Container::table()->getAllTablesData();
}
