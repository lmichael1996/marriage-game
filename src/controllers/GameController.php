<?php
require_once __DIR__ . '/../repository/QuestionSet.php';
require_once __DIR__ . '/../config/database.php';

class GameController {
    private $db;
    private $questionSetModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = getDBConnection();
        $this->questionSetModel = new QuestionSet();
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
        // Get active round
        $stmt = $this->db->prepare("
            SELECT * FROM rounds 
            WHERE status = 'active' 
            LIMIT 1
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        $activeRound = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'success' => true,
            'active_round' => $activeRound
        ];
    }
    
    /**
     * Start a new round
     */
    public function startRound($roundId) {
        // Set all rounds to pending
        $this->db->query("UPDATE rounds SET status = 'pending'");
        
        // Set this round to active
        $stmt = $this->db->prepare("UPDATE rounds SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $roundId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            return [
                'success' => true,
                'message' => 'Round avviato'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Errore nell\'avvio del round'
        ];
    }
    
    /**
     * Close a round
     */
    public function closeRound($roundId) {
        $stmt = $this->db->prepare("UPDATE rounds SET status = 'closed' WHERE id = ?");
        $stmt->bind_param("i", $roundId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            return [
                'success' => true,
                'message' => 'Round chiuso'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Errore nella chiusura del round'
        ];
    }
    
    /**
     * Submit player answer
     */
    public function submitAnswer($userId, $roundId, $answer, $timeTaken) {
        // Check if round is still active
        $stmt = $this->db->prepare("SELECT status FROM rounds WHERE id = ?");
        $stmt->bind_param("i", $roundId);
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();
        
        if (!$round || $round['status'] !== 'active') {
            return [
                'success' => false,
                'error' => 'Round non più attivo'
            ];
        }
        
        // Save answer (you would need a player_answers table)
        // For now, just return success
        
        return [
            'success' => true,
            'message' => 'Risposta registrata'
        ];
    }
    
    /**
     * Get leaderboard
     */
    public function getLeaderboard() {
        // This would query the player_answers table
        // For now, return empty array
        
        return [
            'success' => true,
            'leaderboard' => []
        ];
    }
}
