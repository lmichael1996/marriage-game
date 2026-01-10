<?php
require_once __DIR__ . '/../config/database.php';

class Round {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    public function create($round_number, $round_type, $question, $option1, $option2, $option3, $option4, $correct_answer) {
        $stmt = $this->conn->prepare("INSERT INTO rounds (round_number, round_type, question, option1, option2, option3, option4, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssi", $round_number, $round_type, $question, $option1, $option2, $option3, $option4, $correct_answer);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    public function getAllRounds() {
        $result = $this->conn->query("SELECT * FROM rounds ORDER BY round_number DESC, id DESC");
        $rounds = [];
        while ($row = $result->fetch_assoc()) {
            $rounds[] = $row;
        }
        return $rounds;
    }
    
    public function getActiveRound() {
        // No active round management without status field
        return null;
    }
    
    public function startRound($round_id) {
        // Simplified without status field
        return true;
    }
    
    public function closeRound($round_id) {
        // Simplified without status field
        return true;
    }
    
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
    
    public function getRoundById($round_id) {
        $stmt = $this->conn->prepare("SELECT * FROM rounds WHERE id = ?");
        $stmt->bind_param("i", $round_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();
        return $round;
    }
    
    public function getRoundStats($round_id) {
        // Simplified without player_answers table
        return [
            'total_players' => 0,
            'correct_answers' => 0,
            'fastest_time' => null,
            'avg_time' => null
        ];
    }
    
    public function getAllRoundsWithStats() {
        $rounds = $this->getAllRounds();
        foreach ($rounds as &$round) {
            // Simplified without status field and player_answers
            $round['stats'] = $this->getRoundStats($round['id']);
            $round['winners'] = [];
        }
        return $rounds;
    }
    
    public function getWinningPlayers($round_id, $round_type) {
        // Simplified without player_answers table
        return [];
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
