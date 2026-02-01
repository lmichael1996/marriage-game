<?php

/**
 * Redirect with success or error message
 */
function redirectWithMessage($location, $success = null, $error = null) {
    $params = [];
    if ($success) $params['success'] = $success;
    if ($error) $params['error'] = urlencode($error);
    $query = $params ? '?' . http_build_query($params) : '';
    header("Location: $location$query");
    exit;
}

/**
 * Parse questions from POST data
 */
function parseQuestionsFromPost($numQuestions) {
    $questions = [];
    error_log("parseQuestionsFromPost - numQuestions: $numQuestions");
    for ($i = 1; $i <= $numQuestions; $i++) {
        $question = $_POST["question_$i"] ?? '';
        if (!$question) {
            error_log("parseQuestionsFromPost - Question $i is empty, skipping");
            continue;
        }

        $questionData = [
            'question' => $question,
            'type' => $_POST["type_$i"] ?? 'multiple',
            'timer' => intval($_POST["timer_$i"] ?? 30),
            'option1' => $_POST["option_{$i}_1"] ?? '',
            'option2' => $_POST["option_{$i}_2"] ?? '',
            'option3' => $_POST["option_{$i}_3"] ?? '',
            'option4' => $_POST["option_{$i}_4"] ?? '',
            'correct' => intval($_POST["correct_$i"] ?? 1)
        ];
        error_log("parseQuestionsFromPost - Question $i: " . json_encode($questionData));
        $questions[] = $questionData;
    }
    error_log("parseQuestionsFromPost - Total questions parsed: " . count($questions));
    return $questions;
}

/**
 * Handle update credentials
 */
function handleUpdateCredentials($admin) {
    // Supporta sia i nomi vecchi che quelli nuovi dei campi
    $newPassword = $_POST['new_password'] ?? $_POST['admin_new_password'] ?? null;
    $confirmPassword = $_POST['confirm_password'] ?? $_POST['admin_confirm_password'] ?? null;
    $newUsername = $_POST['new_username'] ?? '';

    $result = $admin->updateCredentials(
        $_SESSION['user_id'],
        $newUsername,
        $newPassword,
        $confirmPassword
    );

    if ($result['success']) {
        $_SESSION['username'] = $result['new_username'];
        // Determina il tab da cui viene la richiesta
        $tab = isset($_POST['admin_new_password']) ? 'general' : 'settings';
        redirectWithMessage('admin.php?tab=' . $tab, 'credentials_updated');
    } else {
        $tab = isset($_POST['admin_new_password']) ? 'general' : 'settings';
        redirectWithMessage('admin.php?tab=' . $tab, null, $result['error']);
    }
}

/**
 * Handle save settings
 */
function handleSaveSettings($admin) {
    // Salva le impostazioni
    $result = $admin->saveSettings($_POST);

    // Se è una richiesta AJAX, restituisci JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');

        // Ricupera le nuove impostazioni
        $settingsResult = $admin->getAllSettings();
        $settings = $settingsResult['success'] ? $settingsResult['settings'] : [];

        echo json_encode([
            'success' => true,
            'message' => 'Impostazioni salvate con successo',
            'settings' => $settings
        ]);
        exit;
    }

    // Altrimenti fai il redirect classico
    // Determina il tab da cui viene la richiesta
    $tab = isset($_POST['max_players']) ? 'general' : 'settings';
    redirectWithMessage(
        'admin.php?tab=' . $tab,
        'settings_saved',
        null
    );
}

/**
 * Handle create set
 */
function handleCreateSet($questionService) {
    $questions = parseQuestionsFromPost(intval($_POST['num_questions'] ?? 0));
    $setId = $questionService->createQuestionSet(
        $_POST['set_name'] ?? '',
        $_POST['set_description'] ?? '',
        $questions
    );
    redirectWithMessage(
        'admin.php?tab=sets',
        'created',
        null
    );
}

/**
 * Handle update set
 */
function handleUpdateSet($questionService) {
    error_log("handleUpdateSet - POST data: " . json_encode($_POST));
    $setId = intval($_POST['set_id'] ?? 0);
    error_log("handleUpdateSet - set_id: $setId");

    $questions = parseQuestionsFromPost(intval($_POST['num_questions'] ?? 0));
    $questionService->updateQuestionSet(
        $setId,
        $_POST['set_name'] ?? '',
        $_POST['set_description'] ?? '',
        $questions
    );
    redirectWithMessage(
        'admin.php?tab=sets',
        'updated',
        null
    );
}

/**
 * Handle delete set
 */
function handleDeleteSet($questionService) {
    $questionService->deleteQuestionSet(intval($_POST['set_id'] ?? 0));
    redirectWithMessage(
        'admin.php?tab=sets',
        'deleted',
        null
    );
}

/**
 * Handle start round
 */
function handleStartRound($game) {
    $result = $game->startRound(intval($_POST['round_id'] ?? 0));
    redirectWithMessage(
        'admin.php',
        null,
        $result['success'] ? null : $result['error']
    );
}

/**
 * Handle close round
 */
function handleCloseRound($game) {
    $result = $game->closeRound(intval($_POST['round_id'] ?? 0));
    redirectWithMessage(
        'admin.php',
        null,
        $result['success'] ? null : $result['error']
    );
}

/**
 * Handle POST action and redirect
 */
function handleAction($action, $admin, $game, $question = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    switch ($action) {
        case 'change_credentials':
        case 'update_credentials':
        case 'update_admin_credentials':
            return handleUpdateCredentials($admin);

        case 'save_settings':
        case 'update_general_settings':
            return handleSaveSettings($admin);

        case 'create_set_with_questions':
            return handleCreateSet($question);

        case 'update_set_with_questions':
            return handleUpdateSet($question);

        case 'delete_question_set':
            return handleDeleteSet($question);

        case 'start_round':
            return handleStartRound($game);

        case 'close_round':
            return handleCloseRound($game);

        default:
            redirectWithMessage('admin.php', null, 'invalid_action');
    }
}

/**
 * Handle AJAX request for set questions
 */
function handleAjaxRequest($questionService) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        if ($_GET['action'] === 'get_set_questions' && ($setId = intval($_GET['set_id'] ?? 0))) {
            header('Content-Type: application/json');
            $result = $questionService->getQuestionSetWithQuestions($setId);
            error_log("handleAjaxRequest - get_set_questions for setId: $setId, result: " . json_encode($result));

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'questions' => $result['questions'] ?? []
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Set di domande non trovato'
                ]);
            }
            exit;
        }
    }
}

/**
 * Get question sets with optional search
 */
function getQuestionSets($questionService, $searchQuery = '', $page = 1, $searchType = 'contains') {
    $questionSetsResult = $questionService->getAllQuestionSets($page, 10);

    // QuestionService returns direct array, not wrapped in 'success'
    if (!is_array($questionSetsResult) || !isset($questionSetsResult['sets'])) {
        return [
            'sets' => [],
            'pagination' => ['total' => 0, 'page' => 1, 'perPage' => 10, 'totalPages' => 1]
        ];
    }

    $allSets = $questionSetsResult['sets'];

    // Apply search filter if query is provided
    if ($searchQuery) {
        $filtered = [];
        $searchQuery = trim($searchQuery);
        $searchLower = strtolower($searchQuery);

        foreach ($allSets as $set) {
            $setName = strtolower($set['set_name'] ?? '');
            $match = false;

            switch ($searchType) {
                case 'exact':
                    $match = ($setName === $searchLower);
                    break;
                case 'starts_with':
                    $match = strpos($setName, $searchLower) === 0;
                    break;
                case 'ends_with':
                    $match = (strlen($searchLower) <= strlen($setName)) &&
                             substr($setName, -strlen($searchLower)) === $searchLower;
                    break;
                case 'contains':
                default:
                    $match = strpos($setName, $searchLower) !== false;
                    break;
            }

            if ($match) {
                $filtered[] = $set;
            }
        }

        $total = count($filtered);
        $totalPages = ceil($total / 10);

        return [
            'sets' => $filtered,
            'pagination' => [
                'total' => $total,
                'page' => 1,
                'perPage' => 10,
                'totalPages' => $totalPages
            ]
        ];
    }

    return [
        'sets' => $allSets,
        'pagination' => [
            'total' => $questionSetsResult['total'] ?? 0,
            'page' => $questionSetsResult['page'] ?? $page,
            'perPage' => $questionSetsResult['perPage'] ?? 10,
            'totalPages' => $questionSetsResult['totalPages'] ?? 1
        ]
    ];
}

/**
 * Get selected set with rounds
 */
function getSelectedSet($questionService, $selectedSetId) {
    if (!$selectedSetId) {
        return [null, []];
    }

    $setResult = $questionService->getQuestionSetById($selectedSetId);
    $selectedSet = $setResult['success'] ? $setResult['set'] : null;

    $roundsResult = $questionService->getQuestionSetWithQuestions($selectedSetId);
    $setRounds = $roundsResult['success'] ? $roundsResult['rounds'] : [];

    return [$selectedSet, $setRounds];
}
