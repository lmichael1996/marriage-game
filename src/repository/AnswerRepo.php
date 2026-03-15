<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for player_answers table.
 *
 * Handles recording, retrieving, and ranking player answers per round.
 * Each row in player_answers stores (round_id, player_id, selected_answer, answer_time)
 * where selected_answer is the option number chosen (1-4, NULL for clickfirst)
 * and answer_time is a DECIMAL(10,4) representing seconds taken to respond.
 */
class AnswerRepo {
    private mysqli $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Record a player's answer for a given round.
     *
     * @param int      $round_id        The active round ID
     * @param int      $player_id       The answering player's ID
     * @param float    $time_taken       Response time in seconds
     * @param int|null $selected_answer  The answer option chosen (1-4), null for clickfirst
     * @return bool True on success, false on failure
     */
    public function submitAnswer(int $round_id, int $player_id, float $time_taken, ?int $selected_answer = null): bool {
        try {
            $answer_time = floatval($time_taken);

            $stmt = $this->conn->prepare("
                INSERT INTO player_answers (round_id, player_id, selected_answer, answer_time)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiid", $round_id, $player_id, $selected_answer, $answer_time);
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
     * For multiple-choice and true/false rounds, only answers matching the
     * correct_answer are returned. For clickfirst rounds all answers are returned.
     * Uses ROW_NUMBER() to assign a 1-based position to each player
     * ordered by answer_time ASC (fastest first).
     *
     * @param int $round_id The round ID
     * @param int $limit    Max number of results (default 10)
     * @return array List of answers with username, player_id, answer_time, selected_answer, position
     */
    public function getTopFastestAnswers(int $round_id, int $limit = 10): array {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    p.username,
                    pa.player_id,
                    pa.selected_answer,
                    pa.answer_time,
                    ROW_NUMBER() OVER (ORDER BY pa.answer_time ASC) as position
                FROM player_answers pa
                INNER JOIN players p ON p.id = pa.player_id
                INNER JOIN rounds r ON r.id = pa.round_id
                INNER JOIN questions q ON q.id = r.question_id
                WHERE pa.round_id = ?
                  AND (q.question_type = 'clickfirst' OR pa.selected_answer = q.correct_answer)
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

    /**
     * Check if a player has already answered a specific round.
     *
     * @param int $playerId Player ID
     * @param int $roundId  Round ID
     * @return bool True if already answered
     */
    public function hasAnswered(int $playerId, int $roundId): bool {
        $stmt = $this->conn->prepare("
            SELECT 1 FROM player_answers
            WHERE player_id = ? AND round_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $playerId, $roundId);
        $stmt->execute();
        $found = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $found;
    }
}
