<?php
require_once __DIR__ . '/../config/database.php';

class AnswerRepo {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    public function hasAnswered($round_id, $player_id) {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM player_answers WHERE question_id = ? AND player_id = ?");
            $stmt->bind_param("ii", $round_id, $player_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->fetch_assoc() !== null;
            $stmt->close();
            return $exists;
        } catch (Exception $e) {
            // Table doesn't exist yet - return false
            return false;
        }
    }
    
    public function submitAnswer($round_id, $player_id, $time_taken, $is_correct) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO player_answers (question_id, player_id, time_taken, is_correct) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iidi", $round_id, $player_id, $time_taken, $is_correct);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        } catch (Exception $e) {
            // Table doesn't exist yet - return true to continue game
            return true;
        }
    }
    
    public function getRoundAnswers($round_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    pa.*,
                    p.username
                FROM player_answers pa
                JOIN players p ON p.id = pa.player_id
                WHERE pa.question_id = ?
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
        } catch (Exception $e) {
            // Table doesn't exist yet - return empty array
            return [];
        }
    }
    
    public function getLeaderboard($roomCode = null) {
        try {
            if ($roomCode) {
                // Get room ID from room_code
                $stmtRoom = $this->conn->prepare("SELECT id FROM rooms WHERE room_code = ?");
                $stmtRoom->bind_param("s", strtoupper($roomCode));
                $stmtRoom->execute();
                $result = $stmtRoom->get_result();
                $room = $result->fetch_assoc();
                $stmtRoom->close();
                
                if (!$room) {
                    return [
                        'success' => false,
                        'leaderboard' => []
                    ];
                }
                
                $roomId = $room['id'];
                
                // Classifica semplice: mostra i giocatori che hanno risposto correttamente, ordinati per velocità media
                $stmt = $this->conn->prepare("
                    SELECT 
                        p.username,
                        COUNT(pa.id) as total_answers,
                        SUM(CASE WHEN pa.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                        AVG(CASE WHEN pa.is_correct = 1 THEN pa.time_taken ELSE NULL END) as avg_time
                    FROM players p
                    LEFT JOIN player_answers pa ON pa.player_id = p.id
                    WHERE p.room_id = ?
                    GROUP BY p.id, p.username
                    HAVING correct_answers > 0
                    ORDER BY correct_answers DESC, avg_time ASC
                    LIMIT 10
                ");
                $stmt->bind_param("i", $roomId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $leaderboard = [];
                while ($row = $result->fetch_assoc()) {
                    $leaderboard[] = $row;
                }
                
                $stmt->close();
                
                return [
                    'success' => true,
                    'leaderboard' => $leaderboard
                ];
            }
            
            $result = $this->conn->query("
                SELECT 
                    p.username,
                    COUNT(pa.id) as total_answers,
                    SUM(pa.is_correct) as correct_answers,
                    AVG(pa.time_taken) as avg_time
                FROM players p
                LEFT JOIN player_answers pa ON pa.player_id = p.id
                GROUP BY p.id, p.username
                HAVING correct_answers > 0
                ORDER BY correct_answers DESC, avg_time ASC
                LIMIT 10
            ");
            
            $leaderboard = [];
            while ($row = $result->fetch_assoc()) {
                $leaderboard[] = $row;
            }
            
            return [
                'success' => true,
                'leaderboard' => $leaderboard
            ];
        } catch (Exception $e) {
            // Table doesn't exist yet - return empty leaderboard
            return [
                'success' => true,
                'leaderboard' => []
            ];
        }
    }
    
    public function getRoundLeaderboard($roomCode, $roundId) {
        try {
            // Get room ID from room_code
            $stmtRoom = $this->conn->prepare("SELECT id FROM rooms WHERE room_code = ?");
            $stmtRoom->bind_param("s", strtoupper($roomCode));
            $stmtRoom->execute();
            $result = $stmtRoom->get_result();
            $room = $result->fetch_assoc();
            $stmtRoom->close();
            
            if (!$room) {
                return [
                    'success' => false,
                    'leaderboard' => []
                ];
            }
            
            $roomId = $room['id'];
            
            $stmt = $this->conn->prepare("
            SELECT 
                p.username,
                pa.time_taken,
                r.round_type,
                CASE r.round_type
                    WHEN 'multiple' THEN
                        CASE ROW_NUMBER() OVER (ORDER BY pa.time_taken ASC)
                            WHEN 1 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_1st')
                            WHEN 2 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_2nd')
                            WHEN 3 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_3rd')
                            WHEN 4 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_4th')
                            WHEN 5 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_5th')
                            WHEN 6 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_6th')
                            WHEN 7 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_7th')
                            WHEN 8 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_8th')
                            WHEN 9 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_9th')
                            WHEN 10 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_10th')
                            ELSE 0
                        END
                    WHEN 'truefalse' THEN
                        CASE ROW_NUMBER() OVER (ORDER BY pa.time_taken ASC)
                            WHEN 1 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_1st')
                            WHEN 2 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_2nd')
                            WHEN 3 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_3rd')
                            WHEN 4 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_4th')
                            WHEN 5 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_5th')
                            WHEN 6 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_6th')
                            WHEN 7 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_7th')
                            WHEN 8 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_8th')
                            WHEN 9 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_9th')
                            WHEN 10 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_10th')
                            ELSE 0
                        END
                    WHEN 'clickfirst' THEN
                        CASE ROW_NUMBER() OVER (ORDER BY pa.time_taken ASC)
                            WHEN 1 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_clickfirst')
                            ELSE 0
                        END
                    ELSE 0
                END as points
            FROM player_answers pa
            JOIN players p ON p.id = pa.player_id
            JOIN questions r ON r.id = pa.question_id
            WHERE pa.question_id = ? 
                AND p.room_id = ?
                AND pa.is_correct = 1
            ORDER BY pa.time_taken ASC
            LIMIT 10
        ");
        $stmt->bind_param("ii", $roundId, $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $leaderboard = [];
        while ($row = $result->fetch_assoc()) {
            $leaderboard[] = $row;
        }
        
        $stmt->close();
        
        return [
            'success' => true,
            'leaderboard' => $leaderboard
        ];
        } catch (Exception $e) {
            // Table doesn't exist yet - return empty leaderboard
            return [
                'success' => true,
                'leaderboard' => []
            ];
        }
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
