<?php

class AdminHelper {
    /**
     * Redirect with success or error message
     */
    public static function redirectWithMessage($location, $success = null, $error = null) {
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
    public static function parseQuestionsFromPost($numQuestions) {
        $questions = [];
        for ($i = 1; $i <= $numQuestions; $i++) {
            $question = $_POST["question_$i"] ?? '';
            if (!$question) continue;
            
            $questions[] = [
                'question' => $question,
                'type' => $_POST["type_$i"] ?? 'multiple',
                'timer' => intval($_POST["timer_$i"] ?? 30),
                'option1' => $_POST["option_{$i}_1"] ?? '',
                'option2' => $_POST["option_{$i}_2"] ?? '',
                'option3' => $_POST["option_{$i}_3"] ?? '',
                'option4' => $_POST["option_{$i}_4"] ?? '',
                'correct' => intval($_POST["correct_$i"] ?? 1)
            ];
        }
        return $questions;
    }
    
    /**
     * Handle POST action and redirect
     */
    public static function handleAction($action, $admin, $game) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        switch ($action) {
            case 'change_credentials':
            case 'update_credentials':
            case 'update_admin_credentials':
                return self::handleUpdateCredentials($admin);
                
            case 'save_settings':
            case 'update_general_settings':
                return self::handleSaveSettings($admin);
                
            case 'create_set_with_questions':
                return self::handleCreateSet($admin);
                
            case 'update_set_with_questions':
                return self::handleUpdateSet($admin);
                
            case 'delete_question_set':
                return self::handleDeleteSet($admin);
                
            case 'start_round':
                return self::handleStartRound($game);
                
            case 'close_round':
                return self::handleCloseRound($game);
                
            default:
                self::redirectWithMessage('admin.php', null, 'invalid_action');
        }
    }
    
    /**
     * Handle update credentials
     */
    private static function handleUpdateCredentials($admin) {
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
            self::redirectWithMessage('admin.php?tab=' . $tab, 'credentials_updated');
        } else {
            $tab = isset($_POST['admin_new_password']) ? 'general' : 'settings';
            self::redirectWithMessage('admin.php?tab=' . $tab, null, $result['error']);
        }
    }
    
    /**
     * Handle save settings
     */
    private static function handleSaveSettings($admin) {
        $result = $admin->saveSettings($_POST);
        // Determina il tab da cui viene la richiesta
        $tab = isset($_POST['max_players']) ? 'general' : 'settings';
        self::redirectWithMessage(
            'admin.php?tab=' . $tab,
            $result['success'] ? 'settings_saved' : null,
            $result['success'] ? null : $result['error']
        );
    }
    
    /**
     * Handle create set
     */
    private static function handleCreateSet($admin) {
        $questions = self::parseQuestionsFromPost(intval($_POST['num_questions'] ?? 0));
        $result = $admin->createSetWithQuestions(
            $_POST['set_name'] ?? '',
            $_POST['set_description'] ?? '',
            $questions
        );
        self::redirectWithMessage(
            'admin.php?tab=sets',
            $result['success'] ? 'created' : null,
            $result['success'] ? null : $result['error']
        );
    }
    
    /**
     * Handle update set
     */
    private static function handleUpdateSet($admin) {
        error_log("AdminHelper::handleUpdateSet - POST data: " . json_encode($_POST));
        $setId = intval($_POST['set_id'] ?? 0);
        error_log("AdminHelper::handleUpdateSet - set_id: $setId");
        
        $questions = self::parseQuestionsFromPost(intval($_POST['num_questions'] ?? 0));
        $result = $admin->updateSetWithQuestions(
            $setId,
            $_POST['set_name'] ?? '',
            $_POST['set_description'] ?? '',
            $questions
        );
        error_log("AdminHelper::handleUpdateSet - result: " . json_encode($result));
        self::redirectWithMessage(
            'admin.php?tab=sets',
            $result['success'] ? 'updated' : null,
            $result['success'] ? null : $result['error']
        );
    }
    
    /**
     * Handle delete set
     */
    private static function handleDeleteSet($admin) {
        $result = $admin->deleteQuestionSet(intval($_POST['set_id'] ?? 0));
        self::redirectWithMessage(
            'admin.php?tab=sets',
            $result['success'] ? 'deleted' : null,
            $result['success'] ? null : $result['error']
        );
    }
    
    /**
     * Handle start round
     */
    private static function handleStartRound($game) {
        $result = $game->startRound(intval($_POST['round_id'] ?? 0));
        self::redirectWithMessage(
            'admin.php',
            null,
            $result['success'] ? null : $result['error']
        );
    }
    
    /**
     * Handle close round
     */
    private static function handleCloseRound($game) {
        $result = $game->closeRound(intval($_POST['round_id'] ?? 0));
        self::redirectWithMessage(
            'admin.php',
            null,
            $result['success'] ? null : $result['error']
        );
    }
    
    /**
     * Handle AJAX request for set questions
     */
    public static function handleAjaxRequest($admin) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            if ($_GET['action'] === 'get_set_questions' && ($setId = intval($_GET['set_id'] ?? 0))) {
                header('Content-Type: application/json');
                $result = $admin->getSetQuestions($setId);
                error_log("AdminHelper::handleAjaxRequest - get_set_questions for setId: $setId, result: " . json_encode($result));
                echo json_encode($result);
                exit;
            }
        }
    }
    
    /**
     * Get question sets with optional search
     */
    public static function getQuestionSets($admin, $searchQuery = '', $page = 1) {
        if ($searchQuery) {
            $searchResult = $admin->searchQuestionSets($searchQuery);
            return [
                'sets' => $searchResult['success'] ? $searchResult['sets'] : [],
                'pagination' => ['total' => 0, 'page' => 1, 'perPage' => 10, 'totalPages' => 1]
            ];
        }
        
        $questionSetsResult = $admin->getAllQuestionSets($page, 10);
        if ($questionSetsResult['success']) {
            return [
                'sets' => $questionSetsResult['sets'],
                'pagination' => $questionSetsResult['pagination']
            ];
        }
        
        return [
            'sets' => [],
            'pagination' => ['total' => 0, 'page' => 1, 'perPage' => 10, 'totalPages' => 1]
        ];
    }
    
    /**
     * Get selected set with rounds
     */
    public static function getSelectedSet($admin, $selectedSetId) {
        if (!$selectedSetId) {
            return [null, []];
        }
        
        $setResult = $admin->getQuestionSetById($selectedSetId);
        $selectedSet = $setResult['success'] ? $setResult['set'] : null;
        
        $roundsResult = $admin->getQuestionSetRounds($selectedSetId);
        $setRounds = $roundsResult['success'] ? $roundsResult['rounds'] : [];
        
        return [$selectedSet, $setRounds];
    }
}
