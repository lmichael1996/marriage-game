<?php
require_once __DIR__ . '/../config/database.php';

class AnswerRepo {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    public function hasAnswered($round_id, $player_id) {
        $stmt = $this->conn->prepare("SELECT id FROM player_answers WHERE round_id = ? AND player_id = ?");
        $stmt->bind_param("ii", $round_id, $player_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc() !== null;
        $stmt->close();
        return $exists;
    }
    
    public function submitAnswer($round_id, $player_id, $answer, $time_taken, $is_correct) {
        $stmt = $this->conn->prepare("INSERT INTO player_answers (round_id, player_id, answer, time_taken, is_correct) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidi", $round_id, $player_id, $answer, $time_taken, $is_correct);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    public function getRoundAnswers($round_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                pa.*,
                p.username
            FROM player_answers pa
            JOIN players p ON p.id = pa.player_id
            WHERE pa.round_id = ?
            ORDER BY pa.time_taken ASC
        ");
        $stmt->bind_param("i", $round_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $answers = [];
        while ($row = $result->fetch_assoc()) {
            $answers[] = $row;
        }
        
        $stmt->close();
        return $answers;
    }
    
    public function getLeaderboard() {
        $result = $this->conn->query("
            SELECT 
                p.username,
                p.total_score,
                COUNT(pa.id) as total_answers,
                SUM(pa.is_correct) as correct_answers,
                AVG(pa.time_taken) as avg_time
            FROM players p
            LEFT JOIN player_answers pa ON pa.player_id = p.id
            GROUP BY p.id, p.username
            ORDER BY p.total_score DESC, correct_answers DESC, avg_time ASC
        ");
        
        $leaderboard = [];
        while ($row = $result->fetch_assoc()) {
            $leaderboard[] = $row;
        }
        return $leaderboard;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
