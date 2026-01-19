<?php
require_once __DIR__ . '/../config/database.php';

class RoundRepo {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    /**
     * Create a new round
     */
    public function create($questionSetId, $round_number, $round_type, $question, $option1, $option2, $option3, $option4, $correct_answer, $timer = 10) {
        $stmt = $this->conn->prepare("
            INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer, status_round) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iissssssii", $questionSetId, $round_number, $round_type, $question, $option1, $option2, $option3, $option4, $correct_answer, $timer);
        
        if ($stmt->execute()) {
            $roundId = $this->conn->insert_id;
            $stmt->close();
            return $roundId;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Get all rounds
     */
    public function getAllRounds() {
        $result = $this->conn->query("SELECT * FROM rounds ORDER BY round_number DESC, id DESC");
        $rounds = [];
        while ($row = $result->fetch_assoc()) {
            $rounds[] = $row;
        }
        return $rounds;
    }
    
    /**
     * Get rounds by question set
     */
    public function getRoundsByQuestionSet($questionSetId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM rounds 
            WHERE question_set_id = ?
            ORDER BY round_number ASC
        ");
        $stmt->bind_param("i", $questionSetId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rounds = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $rounds;
    }
    
    /**
     * Get active round
     */
    public function getActiveRound($questionSetId = null) {
        if ($questionSetId !== null) {
            // Get active round for specific question set (room)
            $stmt = $this->conn->prepare("
                SELECT * FROM rounds 
                WHERE status_round = 'active' 
                AND question_set_id = ? 
                LIMIT 1
            ");
            $stmt->bind_param("i", $questionSetId);
        } else {
            // Get any active round (for backward compatibility)
            $stmt = $this->conn->prepare("SELECT * FROM rounds WHERE status_round = 'active' LIMIT 1");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();
        return $round;
    }
    
    /**
     * Start a round (set to active)
     */
    public function startRound($round_id) {
        // Set all rounds to pending first
        $this->conn->query("UPDATE rounds SET status_round = 'pending' WHERE status_round = 'active'");
        
        // Set this round to active
        $stmt = $this->conn->prepare("UPDATE rounds SET status_round = 'active' WHERE id = ?");
        $stmt->bind_param("i", $round_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Close a round
     */
    public function closeRound($round_id) {
        $stmt = $this->conn->prepare("UPDATE rounds SET status_round = 'closed' WHERE id = ?");
        $stmt->bind_param("i", $round_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Update correct answer for a round
     */
    public function updateAnswer($round_id, $correct_answer) {
        $stmt = $this->conn->prepare("UPDATE rounds SET correct_answer = ? WHERE id = ?");
        $stmt->bind_param("ii", $correct_answer, $round_id);
        $stmt->execute();
        $stmt->close();
        
        // Update correctness of all answers for this round
        $stmt = $this->conn->prepare("UPDATE player_answers SET is_correct = (answer = ?) WHERE round_id = ?");
        $stmt->bind_param("ii", $correct_answer, $round_id);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
    /**
     * Get round by ID
     */
    public function getRoundById($round_id) {
        $stmt = $this->conn->prepare("SELECT * FROM rounds WHERE id = ?");
        $stmt->bind_param("i", $round_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();
        return $round;
    }
    
    /**
     * Get round statistics from player_answers
     */
    public function getRoundStats($round_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_players,
                SUM(is_correct) as correct_answers,
                MIN(time_taken) as fastest_time,
                AVG(time_taken) as avg_time
            FROM player_answers
            WHERE round_id = ?
        ");
        $stmt->bind_param("i", $round_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return $stats ?: [
            'total_players' => 0,
            'correct_answers' => 0,
            'fastest_time' => null,
            'avg_time' => null
        ];
    }
    
    /**
     * Get all rounds with statistics
     */
    public function getAllRoundsWithStats() {
        $rounds = $this->getAllRounds();
        foreach ($rounds as &$round) {
            $round['stats'] = $this->getRoundStats($round['id']);
            $round['winners'] = $this->getWinningPlayers($round['id'], $round['round_type']);
        }
        return $rounds;
    }
    
    /**
     * Get winning players for a round based on round type
     */
    public function getWinningPlayers($round_id, $round_type) {
        if ($round_type === 'clickfirst') {
            // For click first, only the fastest correct answer wins
            $stmt = $this->conn->prepare("
                SELECT p.username, pa.time_taken
                FROM player_answers pa
                JOIN players p ON pa.player_id = p.id
                WHERE pa.round_id = ? AND pa.is_correct = 1
                ORDER BY pa.time_taken ASC
                LIMIT 1
            ");
        } else {
            // For multiple choice and true/false, top 10 by speed
            $stmt = $this->conn->prepare("
                SELECT p.username, pa.time_taken
                FROM player_answers pa
                JOIN players p ON pa.player_id = p.id
                WHERE pa.round_id = ? AND pa.is_correct = 1
                ORDER BY pa.time_taken ASC
                LIMIT 10
            ");
        }
        
        $stmt->bind_param("i", $round_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $winners = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $winners;
    }
    
    /**
     * Add a round to a question set
     */
    public function addRoundToSet($setId, $roundType, $question, $option1, $option2, $option3, $option4, $correctAnswer) {
        // Get next round number
        $stmt = $this->conn->prepare("SELECT MAX(round_number) as max_num FROM rounds WHERE question_set_id = ?");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $nextNumber = ($row['max_num'] ?? 0) + 1;
        $stmt->close();
        
        // Insert round
        $stmt = $this->conn->prepare("
            INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, status_round) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iissssssi", $setId, $nextNumber, $roundType, $question, $option1, $option2, $option3, $option4, $correctAnswer);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Delete round by ID
     */
    public function deleteRound($round_id) {
        $stmt = $this->conn->prepare("DELETE FROM rounds WHERE id = ?");
        $stmt->bind_param("i", $round_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
