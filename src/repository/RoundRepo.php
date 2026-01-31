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
            INSERT INTO questions (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $result = $this->conn->query("SELECT * FROM questions ORDER BY round_number DESC, id DESC");
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
            SELECT * FROM questions 
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
     * Get active round - This method is no longer used since status_round column was removed
     * Kept for backward compatibility but returns null
     */
    public function getActiveRound($questionSetId = null) {
        // status_round column no longer exists in database
        // Active round tracking should be done in session or a separate status table
        return null;
    }
    
    /**
     * Start a round (set to active)
     * Note: status_round column was removed from database
     * Kept for backward compatibility but does nothing
     */
    public function startRound($round_id) {
        // status_round column no longer exists
        return true;
    }
    
    /**
     * Close a round
     * Note: status_round column was removed from database
     * Kept for backward compatibility but does nothing
     */
    public function closeRound($round_id) {
        // status_round column no longer exists
        return true;
    }
    
    /**
     * Update correct answer for a round
     */
    public function updateAnswer($round_id, $correct_answer) {
        $stmt = $this->conn->prepare("UPDATE questions SET correct_answer = ? WHERE id = ?");
        $stmt->bind_param("ii", $correct_answer, $round_id);
        $stmt->execute();
        $stmt->close();
        
        // Update correctness of all answers for this round
        $stmt = $this->conn->prepare("UPDATE player_answers SET is_correct = (answer = ?) WHERE question_id = ?");
        $stmt->bind_param("ii", $correct_answer, $round_id);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
    /**
     * Get round by ID
     */
    public function getRoundById($round_id) {
        $stmt = $this->conn->prepare("SELECT * FROM questions WHERE id = ?");
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
            WHERE question_id = ?
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
                WHERE pa.question_id = ? AND pa.is_correct = 1
                ORDER BY pa.time_taken ASC
                LIMIT 1
            ");
        } else {
            // For multiple choice and true/false, top 10 by speed
            $stmt = $this->conn->prepare("
                SELECT p.username, pa.time_taken
                FROM player_answers pa
                JOIN players p ON pa.player_id = p.id
                WHERE pa.question_id = ? AND pa.is_correct = 1
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
        $stmt = $this->conn->prepare("SELECT MAX(round_number) as max_num FROM questions WHERE question_set_id = ?");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $nextNumber = ($row['max_num'] ?? 0) + 1;
        $stmt->close();
        
        // Insert round
        $stmt = $this->conn->prepare("
            INSERT INTO questions (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $stmt = $this->conn->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->bind_param("i", $round_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Reset all rounds of a question set to pending status
     * Note: status_round column was removed from database
     * Kept for backward compatibility - just clears player answers
     */
    public function resetRoundsBySetId($question_set_id) {
        // Delete all player answers for these rounds
        $stmt = $this->conn->prepare("
            DELETE FROM player_answers 
            WHERE question_id IN (SELECT id FROM questions WHERE question_set_id = ?)
        ");
        $stmt->bind_param("i", $question_set_id);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
