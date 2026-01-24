<?php
require_once __DIR__ . '/../services/AdminService.php';
require_once __DIR__ . '/../services/QuestionService.php';

class AdminController {
    private $admin;
    private $question;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->adminService = new AdminService();
        $this->questionService = new QuestionService();
    }
    
    /**
     * Update admin credentials (username and/or password)
     */
    public function updateCredentials($userId, $newUsername, $newPassword = null, $confirmPassword = null) {
        try {
            $result = $this->adminService->updateCredentials($userId, $newUsername, $newPassword, $confirmPassword);
            return [
                'success' => true,
                'message' => 'Credenziali aggiornate con successo',
                'new_username' => $result['username']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Save game settings
     */
    public function saveSettings($settingsData) {
        try {
            $this->adminService->saveSettings($settingsData);
            return [
                'success' => true,
                'message' => 'Impostazioni salvate con successo'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all game settings
     */
    public function getSettings() {
        try {
            $settings = $this->adminService->getAllSettings();
            return [
                'success' => true,
                'settings' => $settings
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create question set with questions
     */
    public function createSetWithQuestions($setName, $setDescription, $questions) {
        try {
            $setId = $this->questionService->createQuestionSet($setName, $setDescription, $questions);
            return [
                'success' => true,
                'message' => 'Set creato con successo',
                'set_id' => $setId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update question set with questions
     */
    public function updateSetWithQuestions($setId, $setName, $setDescription, $questions) {
        try {
            $this->questionService->updateQuestionSet($setId, $setName, $setDescription, $questions);
            return [
                'success' => true,
                'message' => 'Set aggiornato con successo'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get questions for a specific set
     */
    public function getSetQuestions($setId) {
        try {
            error_log("AdminController::getSetQuestions called with setId: $setId");
            $set = $this->questionService->getQuestionSetWithQuestions($setId);
            error_log("AdminController::getSetQuestions - set: " . json_encode($set));
            
            if (!$set) {
                return [
                    'success' => false,
                    'error' => 'Set non trovato'
                ];
            }
            
            $result = [
                'success' => true,
                'questions' => $set['questions'] ?? []
            ];
            error_log("AdminController::getSetQuestions - returning: " . json_encode($result));
            return $result;
        } catch (Exception $e) {
            error_log("AdminController::getSetQuestions - exception: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Add a single question to a set
     */
    public function addQuestion($setId, $questionData) {
        try {
            $this->questionService->addQuestion($setId, $questionData);
            return [
                'success' => true,
                'message' => 'Domanda aggiunta con successo'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update timer
     */
    public function updateTimer($seconds) {
        try {
            // Validate timer value (between 5 and 60 seconds)
            if ($seconds < 5) $seconds = 5;
            if ($seconds > 60) $seconds = 60;
            
            $this->adminService->updateTimer($seconds);
            return [
                'success' => true,
                'timer_seconds' => $seconds
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get timer
     */
    public function getTimer() {
        try {
            $seconds = $this->adminService->getTimer();
            return [
                'success' => true,
                'timer_seconds' => $seconds
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all question sets
     */
    public function getAllQuestionSets($page = 1, $perPage = 10) {
        try {
            $result = $this->questionService->getAllQuestionSets($page, $perPage);
            return [
                'success' => true,
                'sets' => $result['sets'],
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'perPage' => $result['perPage'],
                    'totalPages' => $result['totalPages']
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Search question sets
     */
    public function searchQuestionSets($query) {
        try {
            $sets = $this->questionService->searchQuestionSets($query);
            return [
                'success' => true,
                'sets' => $sets
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get question set by ID
     */
    public function getQuestionSetById($id) {
        try {
            $set = $this->questionService->getQuestionSetById($id);
            return [
                'success' => true,
                'set' => $set
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get rounds for a question set
     */
    public function getQuestionSetRounds($setId) {
        try {
            $rounds = $this->questionService->getQuestionSetRounds($setId);
            return [
                'success' => true,
                'rounds' => $rounds
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a question set
     */
    public function deleteQuestionSet($setId) {
        try {
            $this->questionService->deleteQuestionSet($setId);
            return [
                'success' => true
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
