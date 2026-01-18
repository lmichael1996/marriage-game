<?php
require_once __DIR__ . '/../services/GameService.php';

class GameController {
    private $gameService;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->gameService = new GameService();
    }
    
    /**
     * Get all question sets
     */
    public function getAllQuestionSets() {
        $sets = $this->questionSetModel->getAllSets();
        
        return [
            'success' => true,
            'sets' => $sets
        ];
    }
    
    /**
     * Get a specific question set with rounds
     */
    public function getQuestionSet($setId) {
        $set = $this->questionSetModel->getSetById($setId);
        
        if ($set) {
            return [
                'success' => true,
                'set' => $set
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Set di domande non trovato'
        ];
    }
    
    /**
     * Get current game state
     */
    public function getGameState() {
        try {
            $activeRound = $this->gameService->getActiveRound();
            return [
                'success' => true,
                'active_round' => $activeRound
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Start a new round
     */
    public function startRound($roundId) {
        try {
            $this->gameService->startRound($roundId);
            return [
                'success' => true,
                'message' => 'Round avviato'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Close a round
     */
    public function closeRound($roundId) {
        try {
            $this->gameService->closeRound($roundId);
            return [
                'success' => true,
                'message' => 'Round chiuso e punteggi calcolati'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Submit player answer
     */
    public function submitAnswer($userId, $roundId, $answer, $timeTaken) {
        try {
            $result = $this->gameService->submitAnswer($userId, $roundId, $answer, $timeTaken);
            return [
                'success' => true,
                'is_correct' => $result['is_correct']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get leaderboard
     */
    public function getLeaderboard($roomCode = null) {
        try {
            $leaderboard = $this->gameService->getLeaderboard($roomCode);
            return [
                'success' => true,
                'leaderboard' => $leaderboard
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
