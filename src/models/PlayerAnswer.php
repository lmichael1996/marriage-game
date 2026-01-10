<?php
require_once __DIR__ . '/../config/database.php';

class PlayerAnswer {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    public function hasAnswered($round_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT id FROM player_answers WHERE round_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $round_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc() !== null;
        $stmt->close();
        return $exists;
    }
    
    public function submitAnswer($round_id, $user_id, $answer, $time_taken, $is_correct) {
        $stmt = $this->conn->prepare("INSERT INTO player_answers (round_id, user_id, answer, time_taken, is_correct) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidi", $round_id, $user_id, $answer, $time_taken, $is_correct);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    public function getLeaderboard() {
        $result = $this->conn->query("
            SELECT 
                u.username,
                COUNT(pa.id) as total_answers,
                SUM(pa.is_correct) as correct_answers,
                AVG(pa.time_taken) as avg_time
            FROM users u
            LEFT JOIN player_answers pa ON pa.user_id = u.id
            WHERE u.role = 'player'
            GROUP BY u.id, u.username
            ORDER BY correct_answers DESC, avg_time ASC
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
