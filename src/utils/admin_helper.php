<?php

function redirectWithMessage($location, $success = null, $error = null) {
    $params = [];
    if ($success) $params['success'] = $success;
    if ($error) $params['error'] = urlencode($error);
    $query = $params ? '?' . http_build_query($params) : '';
    header("Location: $location$query");
    exit;
}

function handleUpdateCredentials($admin) {
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
        $tab = isset($_POST['admin_new_password']) ? 'general' : 'settings';
        redirectWithMessage('admin.php?tab=' . $tab, 'credentials_updated');
    } else {
        $tab = isset($_POST['admin_new_password']) ? 'general' : 'settings';
        redirectWithMessage('admin.php?tab=' . $tab, null, $result['error']);
    }
}

function handleSaveSettings($admin) {
    $result = $admin->saveSettings($_POST);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        $settingsResult = $admin->getAllSettings();
        $settings = $settingsResult['success'] ? $settingsResult['settings'] : [];

        echo json_encode([
            'success' => true,
            'message' => 'Impostazioni salvate con successo',
            'settings' => $settings
        ]);
        exit;
    }

    $tab = 'settings';
    redirectWithMessage('admin.php?tab=' . $tab, 'settings_saved', null);
}

function handleStartRound($game) {
    $result = $game->startRound(intval($_POST['round_id'] ?? 0));
    redirectWithMessage('admin.php', null, $result['success'] ? null : $result['error']);
}

function handleCloseRound($game) {
    $result = $game->closeRound(intval($_POST['round_id'] ?? 0));
    redirectWithMessage('admin.php', null, $result['success'] ? null : $result['error']);
}

function handleAddQuestion($question) {
    if (!$question) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Question service not available']);
        exit;
    }

    try {
        $questionText = $_POST['question'] ?? '';
        $type = $_POST['round_type'] ?? '';
        $categoryId = intval($_POST['category_id'] ?? 1);
        $timer = intval($_POST['timer'] ?? 30);

        if (empty($questionText)) {
            throw new Exception('Domanda obbligatoria');
        }
        if (empty($type)) {
            throw new Exception('Tipo domanda obbligatorio');
        }

        // Inserisci domanda con answers in base al tipo
        $result = null;

        if ($type === 'truefalse') {
            $answer1 = $_POST['answer1'] ?? 'Vero';
            $answer2 = $_POST['answer2'] ?? 'Falso';
            $correctAnswer = intval($_POST['correct_answer'] ?? 1);

            $result = $question->addQuestion([
                'question' => $questionText,
                'round_type' => $type,
                'category_id' => $categoryId,
                'timer' => $timer,
                'answer1' => $answer1,
                'answer2' => $answer2,
                'correct_answer' => $correctAnswer
            ]);
        } else if ($type === 'multiple') {
            $answer1 = $_POST['answer1'] ?? '';
            $answer2 = $_POST['answer2'] ?? '';
            $answer3 = $_POST['answer3'] ?? '';
            $answer4 = $_POST['answer4'] ?? '';
            $correctAnswer = intval($_POST['correct_answer'] ?? 1);

            if (empty($answer1) || empty($answer2) || empty($answer3) || empty($answer4)) {
                throw new Exception('Tutte le risposte sono obbligatorie per Multiple Choice');
            }

            $result = $question->addQuestion([
                'question' => $questionText,
                'round_type' => $type,
                'category_id' => $categoryId,
                'timer' => $timer,
                'answer1' => $answer1,
                'answer2' => $answer2,
                'answer3' => $answer3,
                'answer4' => $answer4,
                'correct_answer' => $correctAnswer
            ]);
        } else if ($type === 'clickfirst') {
            $result = $question->addQuestion([
                'question' => $questionText,
                'round_type' => $type,
                'category_id' => $categoryId,
                'timer' => $timer
            ]);
        }

        if (is_array($result) && isset($result['success'])) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Domanda aggiunta con successo', 'question_id' => $result['question_id'] ?? null]);
                exit;
            }
            redirectWithMessage('admin.php?tab=questions', 'question_added');
        } else {
            throw new Exception('Errore nell\'aggiunta della domanda');
        }
    } catch (Exception $e) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        redirectWithMessage('admin.php?tab=questions', null, $e->getMessage());
    }
}

function handleUpdateQuestion($question) {
    if (!$question) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Question service not available']);
        exit;
    }

    try {
        $questionId = intval($_POST['question_id'] ?? 0);
        $questionText = $_POST['question'] ?? '';
        $type = $_POST['round_type'] ?? '';
        $categoryId = intval($_POST['category_id'] ?? 1);
        $timer = intval($_POST['timer'] ?? 30);

        if (empty($questionId)) {
            throw new Exception('ID domanda mancante');
        }
        if (empty($questionText)) {
            throw new Exception('Domanda obbligatoria');
        }
        if (empty($type)) {
            throw new Exception('Tipo domanda obbligatorio');
        }

        // Prepara dati per aggiornamento
        $updateData = [
            'id' => $questionId,
            'question' => $questionText,
            'round_type' => $type,
            'category_id' => $categoryId,
            'timer' => $timer
        ];

        if ($type === 'truefalse') {
            $answer1 = $_POST['answer1'] ?? 'Vero';
            $answer2 = $_POST['answer2'] ?? 'Falso';
            $correctAnswer = intval($_POST['correct_answer'] ?? 1);

            $updateData['answer1'] = $answer1;
            $updateData['answer2'] = $answer2;
            $updateData['correct_answer'] = $correctAnswer;
        } else if ($type === 'multiple') {
            $answer1 = $_POST['answer1'] ?? '';
            $answer2 = $_POST['answer2'] ?? '';
            $answer3 = $_POST['answer3'] ?? '';
            $answer4 = $_POST['answer4'] ?? '';
            $correctAnswer = intval($_POST['correct_answer'] ?? 1);

            if (empty($answer1) || empty($answer2) || empty($answer3) || empty($answer4)) {
                throw new Exception('Tutte le risposte sono obbligatorie per Multiple Choice');
            }

            $updateData['answer1'] = $answer1;
            $updateData['answer2'] = $answer2;
            $updateData['answer3'] = $answer3;
            $updateData['answer4'] = $answer4;
            $updateData['correct_answer'] = $correctAnswer;
        } else if ($type === 'clickfirst') {
            // No answers needed for clickfirst
        }

        // Chiama metodo di aggiornamento
        if ($question && method_exists($question, 'updateQuestion')) {
            $result = $question->updateQuestion($updateData);

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                if ($result['success'] ?? false) {
                    echo json_encode(['success' => true, 'message' => 'Domanda aggiornata con successo']);
                } else {
                    echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Errore nell\'aggiornamento']);
                }
                exit;
            }

            if ($result['success'] ?? false) {
                redirectWithMessage('admin.php?tab=questions', 'question_updated');
            } else {
                throw new Exception($result['error'] ?? 'Errore nell\'aggiornamento della domanda');
            }
        } else {
            throw new Exception('Metodo updateQuestion non disponibile');
        }
    } catch (Exception $e) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        redirectWithMessage('admin.php?tab=questions', null, $e->getMessage());
    }
}

function handleAction($action, $admin, $game, $question = null, $questionSet = null) {
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
        case 'start_round':
            return handleStartRound($game);
        case 'close_round':
            return handleCloseRound($game);
        case 'add_question':
        case 'save_question':
            return handleAddQuestion($question);
        case 'update_question':
            return handleUpdateQuestion($question);
        case 'delete_question':
            // Gestione eliminazione domanda
            if (isset($_POST['question_id'])) {
                $qId = intval($_POST['question_id']);
                if ($question && method_exists($question, 'deleteQuestion')) {
                    $result = $question->deleteQuestion($qId);
                    redirectWithMessage('admin.php?tab=questions', $result['success'] ? 'question_deleted' : null, $result['error'] ?? null);
                }
            }
            redirectWithMessage('admin.php?tab=questions', null, 'Errore nell\'eliminazione');
        case 'add_questionset':
            return handleAddQuestionSet($questionSet);
        case 'update_questionset':
            return handleEditQuestionSet($questionSet);
        case 'delete_questionset':
            return handleDeleteQuestionSet($questionSet);
        default:
            redirectWithMessage('admin.php', null, 'invalid_action');
    }
}

function handleAjaxRequest($questionService) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        if ($_GET['action'] === 'delete_question' && ($qId = intval($_GET['q_id'] ?? 0))) {
            header('Content-Type: application/json');
            $result = $questionService->deleteQuestion($qId);
            echo json_encode($result);
            exit;
        }
    }
}

function getAllQuestions($questionService, $searchQuery = '', $page = 1, $searchType = 'contains', $category = '') {
    // Recupera TUTTE le domande (non paginate dal DB)
    $questionsResult = $questionService->getAllQuestions(1, 1000);

    if (!is_array($questionsResult) || !isset($questionsResult['questions'])) {
        return [
            'questions' => [],
            'pagination' => ['total' => 0, 'page' => 1, 'perPage' => 10, 'totalPages' => 1]
        ];
    }

    $allQuestions = $questionsResult['questions'];

    // Filtra per categoria
    if ($category) {
        $filtered = [];
        foreach ($allQuestions as $question) {
            $categoryId = (int)$question['category_id'] ?? 1;
            // Mappa categoria_id a nome
            $categoryMap = [
                1 => 'general',
                2 => 'science',
                3 => 'history',
                4 => 'sports',
                5 => 'entertainment'
            ];
            if (($categoryMap[$categoryId] ?? 'general') === $category) {
                $filtered[] = $question;
            }
        }
        $allQuestions = $filtered;
    }

    if ($searchQuery) {
        $filtered = [];
        $searchQuery = trim($searchQuery);
        $searchLower = strtolower($searchQuery);

        foreach ($allQuestions as $question) {
            $questionText = strtolower($question['question'] ?? '');
            $match = false;

            switch ($searchType) {
                case 'exact':
                    $match = ($questionText === $searchLower);
                    break;
                case 'starts_with':
                    $match = strpos($questionText, $searchLower) === 0;
                    break;
                case 'ends_with':
                    $match = (strlen($searchLower) <= strlen($questionText)) &&
                             substr($questionText, -strlen($searchLower)) === $searchLower;
                    break;
                case 'contains':
                default:
                    $match = strpos($questionText, $searchLower) !== false;
                    break;
            }

            if ($match) {
                $filtered[] = $question;
            }
        }

        $allQuestions = $filtered;
    }

    $total = count($allQuestions);
    $perPage = 10;
    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    $paginatedQuestions = array_slice($allQuestions, $offset, $perPage);

    return [
        'questions' => $paginatedQuestions,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages
        ]
    ];
}

function handleAddQuestionSet($questionSet) {
    if (!$questionSet) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Question Set service not available']);
        exit;
    }

    try {
        $setName = $_POST['set_name'] ?? '';
        $setDescription = $_POST['set_description'] ?? '';

        if (empty($setName)) {
            throw new Exception('Nome del set obbligatorio');
        }

        $setId = $questionSet->add($setName, $setDescription);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'setId' => $setId,
            'message' => 'Set creato con successo'
        ]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

function handleEditQuestionSet($questionSet) {
    if (!$questionSet) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Question Set service not available']);
        exit;
    }

    try {
        $setId = intval($_POST['set_id'] ?? 0);
        $setName = $_POST['set_name'] ?? '';
        $setDescription = $_POST['set_description'] ?? '';

        if ($setId <= 0 || empty($setName)) {
            throw new Exception('Set ID e nome obbligatori');
        }

        $questionSet->update($setId, $setName, $setDescription);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Set aggiornato con successo'
        ]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

function handleDeleteQuestionSet($questionSet) {
    if (!$questionSet) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Question Set service not available']);
        exit;
    }

    try {
        $setId = intval($_POST['set_id'] ?? 0);

        if ($setId <= 0) {
            throw new Exception('Set ID obbligatorio');
        }

        $questionSet->delete($setId);

        redirectWithMessage('admin.php?tab=game', 'set_deleted');
    } catch (Exception $e) {
        redirectWithMessage('admin.php?tab=game', null, $e->getMessage());
    }
}

?>
