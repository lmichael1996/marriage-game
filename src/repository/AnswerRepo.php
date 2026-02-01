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
                $roomCodeUpper = strtoupper($roomCode);
                $stmtRoom->bind_param("s", $roomCodeUpper);
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

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
