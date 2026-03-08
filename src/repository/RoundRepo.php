<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for the `rounds` table.
 *
 * Handles round creation, lookup (by ID / room / position),
 * ranking persistence, and judge decision tracking.
 */
class RoundRepo {
    private mysqli $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Create a new round for a room.
     *
     * @param int $room_id      Room ID
     * @param int $question_id  Question ID
     * @return int|false        New round ID on success, false on failure
     */
    public function createRound(int $room_id, int $question_id): int|false {
        $stmt = $this->conn->prepare("INSERT INTO rounds (room_id, question_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $room_id, $question_id);
        $success = $stmt->execute();
        $id = $success ? $this->conn->insert_id : false;
        $stmt->close();

        return $id;
    }

    /**
     * Get a round by its primary key (with question details).
     *
     * @param int $round_id  Round ID
     * @return array|null    Round row or null if not found
     */
    public function getRoundById(int $round_id): ?array {
        $stmt = $this->conn->prepare("
            SELECT r.*, q.round_type, q.question,
                   q.option1, q.option2, q.option3, q.option4,
                   q.correct_answer, q.timer
            FROM rounds r
            JOIN questions q ON r.question_id = q.id
            WHERE r.id = ?
        ");
        $stmt->bind_param("i", $round_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();
        return $round;
    }

    /**
     * Get all rounds for a room with question details.
     *
     * @param int $room_id  Room ID
     * @return array        List of round rows ordered by creation
     */
    public function getRoundsByRoom(int $room_id): array {
        $stmt = $this->conn->prepare("
            SELECT r.*, q.round_type, q.question,
                   q.option1, q.option2, q.option3, q.option4,
                   q.correct_answer, q.timer
            FROM rounds r
            JOIN questions q ON r.question_id = q.id
            WHERE r.room_id = ?
            ORDER BY r.id ASC
        ");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rounds = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rounds;
    }

    /**
     * Get the active (most recent) round for a room.
     * Includes question details, category info, and computed round_number.
     *
     * @param int $room_id  Room ID
     * @return array|null   Active round row or null if no rounds exist
     */
    public function getActiveRound(int $room_id): ?array {
        $stmt = $this->conn->prepare("
            SELECT r.id, r.room_id, r.question_id,
                   q.id as q_id, q.round_type,
                   q.question, q.option1, q.option2, q.option3, q.option4,
                   q.correct_answer, q.timer,
                   qc.category_name, qc.color as category_color,
                   (SELECT COUNT(*) FROM rounds r2 WHERE r2.room_id = r.room_id AND r2.id <= r.id) as round_number
            FROM rounds r
            LEFT JOIN questions q ON r.question_id = q.id
            LEFT JOIN question_categories qc ON q.category_id = qc.id
            WHERE r.room_id = ?
            ORDER BY r.id DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();

        if ($round) {
            // Convert to int
            $round['round_number'] = (int)$round['round_number'];
        }
        return $round;
    }

    /**
     * Get a round by its position in a room (for player polling).
     * Position 1 = first round, 2 = second round, etc.
     * Also fills in truefalse options and includes room status.
     *
     * @param int $room_id   Room ID
     * @param int $position  1-based round position
     * @return array|null    Round row or null if position is out of range
     */
    public function getRoundByPosition(int $room_id, int $position): ?array {
        $offset = max(0, $position - 1);
        $stmt = $this->conn->prepare("
            SELECT r.id, r.room_id, r.question_id,
                   q.id as q_id, q.round_type,
                   q.question, q.option1, q.option2, q.option3, q.option4,
                   q.correct_answer, q.timer,
                   qc.category_name, qc.color as category_color,
                   rm.status_room
            FROM rounds r
            LEFT JOIN questions q ON r.question_id = q.id
            LEFT JOIN question_categories qc ON q.category_id = qc.id
            LEFT JOIN rooms rm ON r.room_id = rm.id
            WHERE r.room_id = ?
            ORDER BY r.id ASC
            LIMIT 1 OFFSET ?
        ");
        $stmt->bind_param("ii", $room_id, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();

        if ($round) {
            // Add round_number (position)
            $round['round_number'] = $position;

            // Truefalse: fixed options "Vero"/"Falso" (empty in DB)
            if (($round['round_type'] ?? '') === 'truefalse') {
                $round['option1'] = 'Vero';
                $round['option2'] = 'Falso';
            }
        }

        return $round;
    }

    /**
     * Save the ranking JSON for a round.
     *
     * @param int   $roundId  Round ID
     * @param array $ranking  Ranking data to encode as JSON
     * @return bool           True on success
     */
    public function saveRanking(int $roundId, array $ranking): bool {
        $json = json_encode($ranking, JSON_UNESCAPED_UNICODE);
        $stmt = $this->conn->prepare("UPDATE rounds SET ranking = ? WHERE id = ?");
        $stmt->bind_param("si", $json, $roundId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Mark a round as decided by the judge.
     *
     * @param int $roundId  Round ID
     */
    public function setJudgeDecided(int $roundId): void {
        $stmt = $this->conn->prepare("UPDATE rounds SET judge_decided = 1 WHERE id = ?");
        $stmt->bind_param("i", $roundId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Check whether the judge has decided for a round.
     *
     * @param int $roundId  Round ID
     * @return bool         True if the judge has decided
     */
    public function isJudgeDecided(int $roundId): bool {
        $stmt = $this->conn->prepare("SELECT judge_decided FROM rounds WHERE id = ?");
        $stmt->bind_param("i", $roundId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? (bool)$result['judge_decided'] : false;
    }
}
?>
