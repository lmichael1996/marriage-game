<?php
require_once __DIR__ . '/../config/database.php';

class AnswerRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function hasAnswered($round_id, $player_id) {
        try {
            // Check if player already answered this round
            $stmt = $this->conn->prepare("
                SELECT id FROM player_answers
                WHERE round_id = ? AND player_id = ?
            ");
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

    public function submitAnswer($round_id, $player_id, $time_taken) {
        try {
            // answer_time: tempo in secondi impiegato dal player per rispondere
            $answer_time = floatval($time_taken);

            // Insert into player_answers table
            // Schema: (id, round_id, player_id, answer_time)
            // answer_time: DECIMAL(10,4) tempo in secondi
            $stmt = $this->conn->prepare("
                INSERT INTO player_answers (round_id, player_id, answer_time)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iid", $round_id, $player_id, $answer_time);
            $success = $stmt->execute();

            $stmt->close();
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getRoundAnswers($round_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    pa.id,
                    pa.round_id,
                    pa.player_id,
                    p.username,
                    pa.answer_time
                FROM player_answers pa
                INNER JOIN players p ON p.id = pa.player_id
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
            $stmt = $this->conn->prepare("
                SELECT
                    p.username,
                    pa.player_id,
                    pa.answer_time,
                    ROW_NUMBER() OVER (ORDER BY pa.answer_time ASC) as position
                FROM player_answers pa
                INNER JOIN players p ON p.id = pa.player_id
                WHERE pa.round_id = ?
                ORDER BY pa.answer_time ASC
                LIMIT ?
            ");
            $stmt->bind_param("ii", $round_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $answers = [];
            while ($row = $result->fetch_assoc()) {
                $answers[] = $row;
            }

            $stmt->close();
            return $answers;
        } catch (Exception $e) {
            // Table doesn't exist or query error - return empty array
            return [];
        }
    }

    public function getLeaderboard($roomCode = null) {
        try {
            if ($roomCode) {
                // Get room ID from code_player
                $stmtRoom = $this->conn->prepare("SELECT id FROM rooms WHERE code_player = ?");
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
                    LEFT JOIN player_answers pa ON pa.player_id = p.id
                    WHERE p.room_id = ?
                    GROUP BY p.id
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

            // If no room code, get leaderboard for current session/cookie room
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            require_once __DIR__ . '/../services/ServiceLoader.php';
            $player = svc('auth')->getPlayer();
            $roomCode = $player['room_code'] ?? ($_SESSION['code_player'] ?? null);

            if (!$roomCode) {
                return [
                    'success' => false,
                    'leaderboard' => []
                ];
            }

            // Recursive call with room code
            return $this->getLeaderboard($roomCode);
        } catch (Exception $e) {
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
