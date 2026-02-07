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

            // Check if user already answered this round
            $stmt = $this->conn->prepare("
                SELECT id FROM player_answers
                WHERE round_id = ? AND username = ?
            ");
            $stmt->bind_param("is", $round_id, $username);
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
            // answer_time: tempo in secondi impiegato dal player per rispondere
            $answer_time = floatval($time_taken);

            error_log("submitAnswer DEBUG: time_taken type=" . gettype($time_taken) . ", value=$time_taken, answer_time=$answer_time");

            // Insert into player_answers table
            // Schema: (id, round_id, username, answer_time)
            // answer_time: DECIMAL(10,4) tempo in secondi
            $stmt = $this->conn->prepare("
                INSERT INTO player_answers (round_id, username, answer_time)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("isd", $round_id, $username, $answer_time);
            $success = $stmt->execute();

            if (!$success) {
                error_log("AnswerRepo submitAnswer ERROR: " . $stmt->error);
            } else {
                error_log("AnswerRepo submitAnswer SUCCESS: Saved answer for user=$username, round_id=$round_id");
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
                    pa.id,
                    pa.round_id,
                    pa.username,
                    pa.answer_time
                FROM player_answers pa
                WHERE pa.round_id = ?
                ORDER BY pa.answer_time ASC
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

    /**
     * Get top 10 fastest answers for a specific round
     */
    public function getTopFastestAnswers($round_id, $limit = 10) {
        try {
            error_log("getTopFastestAnswers: Fetching top $limit answers for round_id=$round_id");

            $stmt = $this->conn->prepare("
                SELECT
                    username,
                    answer_time,
                    ROW_NUMBER() OVER (ORDER BY answer_time ASC) as position
                FROM player_answers
                WHERE round_id = ?
                ORDER BY answer_time ASC
                LIMIT ?
            ");
            $stmt->bind_param("ii", $round_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $answers = [];
            while ($row = $result->fetch_assoc()) {
                $answers[] = $row;
            }

            error_log("getTopFastestAnswers: Found " . count($answers) . " answers");

            $stmt->close();
            return $answers;
        } catch (Exception $e) {
            error_log("getTopFastestAnswers ERROR: " . $e->getMessage());
            // Table doesn't exist or query error - return empty array
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

                // Get all players in this room with their answer count
                $stmt = $this->conn->prepare("
                    SELECT
                        p.username,
                        COUNT(pa.id) as total_answers
                    FROM players p
                    LEFT JOIN player_answers pa ON pa.username = p.username
                    WHERE p.room_id = ?
                    GROUP BY p.username
                    ORDER BY total_answers DESC
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

            // If no room code, get leaderboard for current session room
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $roomCode = $_SESSION['room_code'] ?? null;

            if (!$roomCode) {
                return [
                    'success' => false,
                    'leaderboard' => []
                ];
            }

            // Recursive call with room code
            return $this->getLeaderboard($roomCode);
        } catch (Exception $e) {
            error_log("Leaderboard error: " . $e->getMessage());
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
