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
    
    public function getLeaderboard($roomCode = null) {
        if ($roomCode) {
            // Calcola i punteggi F1-style: solo i primi 10 che rispondono correttamente per ogni round ricevono punti
            $stmt = $this->conn->prepare("
                SELECT 
                    p.username,
                    COALESCE(SUM(ranked_answers.points), 0) as total_score,
                    COUNT(pa.id) as total_answers,
                    SUM(pa.is_correct) as correct_answers,
                    AVG(pa.time_taken) as avg_time
                FROM players p
                LEFT JOIN player_answers pa ON pa.player_id = p.id
                LEFT JOIN (
                    -- Per ogni round, assegna i punti ai primi 10 che hanno risposto correttamente
                    SELECT 
                        pa2.id as answer_id,
                        pa2.player_id,
                        r2.round_type,
                        CASE 
                            WHEN @prev_round != r2.id THEN @rank := 1
                            ELSE @rank := @rank + 1
                        END as position,
                        @prev_round := r2.id as round_tracker,
                        CASE r2.round_type
                            WHEN 'multiple' THEN
                                CASE 
                                    WHEN @rank = 1 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_1st')
                                    WHEN @rank = 2 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_2nd')
                                    WHEN @rank = 3 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_3rd')
                                    WHEN @rank = 4 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_4th')
                                    WHEN @rank = 5 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_5th')
                                    WHEN @rank = 6 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_6th')
                                    WHEN @rank = 7 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_7th')
                                    WHEN @rank = 8 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_8th')
                                    WHEN @rank = 9 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_9th')
                                    WHEN @rank = 10 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_mult_10th')
                                    ELSE 0
                                END
                            WHEN 'truefalse' THEN
                                CASE 
                                    WHEN @rank = 1 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_1st')
                                    WHEN @rank = 2 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_2nd')
                                    WHEN @rank = 3 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_3rd')
                                    WHEN @rank = 4 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_4th')
                                    WHEN @rank = 5 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_5th')
                                    WHEN @rank = 6 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_6th')
                                    WHEN @rank = 7 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_7th')
                                    WHEN @rank = 8 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_8th')
                                    WHEN @rank = 9 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_9th')
                                    WHEN @rank = 10 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_tf_10th')
                                    ELSE 0
                                END
                            WHEN 'clickfirst' THEN
                                CASE 
                                    WHEN @rank = 1 THEN (SELECT setting_value FROM game_settings WHERE setting_key = 'points_clickfirst')
                                    ELSE 0
                                END
                            ELSE 0
                        END as points
                    FROM player_answers pa2
                    INNER JOIN rounds r2 ON r2.id = pa2.round_id
                    INNER JOIN players p2 ON p2.id = pa2.player_id
                    CROSS JOIN (SELECT @rank := 0, @prev_round := 0) vars
                    WHERE pa2.is_correct = 1 AND p2.room_code = ?
                    ORDER BY r2.id ASC, pa2.time_taken ASC
                ) ranked_answers ON ranked_answers.answer_id = pa.id
                WHERE p.room_code = ?
                GROUP BY p.id, p.username
                HAVING correct_answers > 0
                ORDER BY total_score DESC, correct_answers DESC, avg_time ASC
                LIMIT 10
            ");
            $stmt->bind_param("ss", $roomCode, $roomCode);
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
                p.total_score,
                COUNT(pa.id) as total_answers,
                SUM(pa.is_correct) as correct_answers,
                AVG(pa.time_taken) as avg_time
            FROM players p
            LEFT JOIN player_answers pa ON pa.player_id = p.id
            GROUP BY p.id, p.username
            HAVING correct_answers > 0
            ORDER BY p.total_score DESC, correct_answers DESC, avg_time ASC
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
    }
    
    public function getRoundLeaderboard($roomCode, $roundId) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.username,
                pa.time_taken,
                pa.answer,
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
            JOIN rounds r ON r.id = pa.round_id
            WHERE pa.round_id = ? 
                AND p.room_code = ?
                AND pa.is_correct = 1
            ORDER BY pa.time_taken ASC
            LIMIT 10
        ");
        $stmt->bind_param("is", $roundId, $roomCode);
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
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
