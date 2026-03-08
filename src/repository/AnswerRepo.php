<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for player_answers table.
 *
 * Handles recording, retrieving, and ranking player answers per round.
 * Each row in player_answers stores (round_id, player_id, answer_time)
 * where answer_time is a DECIMAL(10,4) representing seconds taken to respond.
 */
class AnswerRepo {
    private mysqli $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Record a player's answer for a given round.
     *
     * @param int   $round_id  The active round ID
     * @param int   $player_id The answering player's ID
     * @param float $time_taken Response time in seconds
     * @return bool True on success, false on failure
     */
    public function submitAnswer(int $round_id, int $player_id, float $time_taken): bool {
        try {
            $answer_time = floatval($time_taken);

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

    /**
     * Get the top N fastest correct answers for a round, with ranking position.
     *
     * Uses ROW_NUMBER() to assign a 1-based position to each player
     * ordered by answer_time ASC (fastest first).
     *
     * @param int $round_id The round ID
     * @param int $limit    Max number of results (default 10)
     * @return array List of answers with username, player_id, answer_time, position
     */
    public function getTopFastestAnswers(int $round_id, int $limit = 10): array {
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
            return [];
        }
    }

    /**
     * Count how many rounds a player has answered in a given room.
     *
     * @param int $playerId Player ID
     * @param int $roomId   Room ID
     * @return int Number of answered rounds
     */
    public function countAnsweredRounds(int $playerId, int $roomId): int {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS cnt
            FROM player_answers pa
            INNER JOIN rounds r ON r.id = pa.round_id
            WHERE pa.player_id = ? AND r.room_id = ?
        ");
        $stmt->bind_param("ii", $playerId, $roomId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['cnt'] ?? 0);
    }
}
