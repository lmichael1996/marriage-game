<?php
require_once __DIR__ . '/../config/database.php';

class AnswerRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function hasAnswered($round_id, $player_id) {
        try {
            // Get session username
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $username = $_SESSION['username'] ?? 'unknown';

            // Get room_id and round_number from rounds table
            $stmtRound = $this->conn->prepare("SELECT room_id, round_number FROM rounds WHERE id = ?");
            $stmtRound->bind_param("i", $round_id);
            $stmtRound->execute();
            $resultRound = $stmtRound->get_result();
            $round = $resultRound->fetch_assoc();
            $stmtRound->close();

            if (!$round) {
                return false;
            }

            // Check if user already answered this round in this room
            $stmt = $this->conn->prepare("
                SELECT id FROM player_answers
                WHERE room_id = ? AND round_number = ? AND username = ?
            ");
            $stmt->bind_param("iis", $round['room_id'], $round['round_number'], $username);
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

    public function submitAnswer($round_id, $player_id, $time_taken) {
        try {
            // Get player username from session
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $username = $_SESSION['username'] ?? 'unknown';

            // Get room_id and round_number from rounds table
            $stmtRound = $this->conn->prepare("SELECT room_id, round_number FROM rounds WHERE id = ?");
            $stmtRound->bind_param("i", $round_id);
            $stmtRound->execute();
            $resultRound = $stmtRound->get_result();
            $round = $resultRound->fetch_assoc();
            $stmtRound->close();

            if (!$round) {
                return false;
            }

            $room_id = $round['room_id'];
            $round_number = $round['round_number'];
            $answer_time = date('Y-m-d H:i:s', time() - intval($time_taken));

            // Insert into player_answers table
            // Schema: (id, room_id, round_number, username, answer_time)
            // NO is_correct column!
            $stmt = $this->conn->prepare("
                INSERT INTO player_answers (room_id, round_number, username, answer_time)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiss", $room_id, $round_number, $username, $answer_time);
            $success = $stmt->execute();

            if (!$success) {
                error_log("AnswerRepo submitAnswer ERROR: " . $stmt->error);
            } else {
                error_log("AnswerRepo submitAnswer SUCCESS: Saved answer for user=$username, room_id=$room_id, round=$round_number");
            }

            $stmt->close();
            return $success;
        } catch (Exception $e) {
            error_log("AnswerRepo submitAnswer EXCEPTION: " . $e->getMessage());
            return false;
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
