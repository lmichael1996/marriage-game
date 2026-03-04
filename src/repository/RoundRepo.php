<?php
require_once __DIR__ . '/../config/database.php';

class RoundRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Create a new round for a room (insert into rounds table)
     * Stores: room_id, question_id
     * Rankings are computed on-demand from player_answers table
     */
    public function createRound($room_id, $question_id) {
        $stmt = $this->conn->prepare("
            INSERT INTO rounds (room_id, question_id)
            VALUES (?, ?)
        ");

        // Support both integer and null for question_id
        if ($question_id === null) {
            $stmt->bind_param("is", $room_id, $question_id);
        } else {
            $stmt->bind_param("ii", $room_id, $question_id);
        }

        if ($stmt->execute()) {
            $roundId = $this->conn->insert_id;
            $stmt->close();
            return $roundId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Get rounds by question set
     */
    public function getRoundsByQuestionSet($questionSetId) {
        $stmt = $this->conn->prepare("
            SELECT q.*, qsq.order_in_set as round_number FROM questions q
            JOIN qset_questions qsq ON q.id = qsq.question_id
            WHERE qsq.qset_id = ?
            ORDER BY qsq.order_in_set ASC
        ");
        $stmt->bind_param("i", $questionSetId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rounds = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rounds;
    }

    /**
     * Reset all rounds for a question set (removes associated rounds from rooms)
     */
    public function resetRoundsBySetId($question_set_id) {
        // Delete all rounds that are associated with rooms that use this question set
        $stmt = $this->conn->prepare("
            DELETE r FROM rounds r
            JOIN rooms ro ON r.room_id = ro.id
            WHERE ro.qset_id = ?
        ");
        $stmt->bind_param("i", $question_set_id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get round by ID
     */
    public function getRoundById($round_id) {
        $stmt = $this->conn->prepare("
            SELECT r.id, r.room_id, r.ranking,
                   q.id as question_id, q.round_type,
                   q.question, q.option1, q.option2, q.option3, q.option4,
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
     * Get all rounds for a specific room with full question details
     */
    public function getRoundsByRoom($room_id) {
        $stmt = $this->conn->prepare("
            SELECT r.id, r.room_id, r.question_id, r.ranking,
                   q.id as question_id, q.round_type,
                   q.question, q.option1, q.option2, q.option3, q.option4,
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
     * Get active round for a room (the most recent one created)
     * This is the round currently being played
     */
    public function getActiveRound($room_id) {
        $stmt = $this->conn->prepare("
            SELECT r.id, r.room_id, r.question_id,
                   q.id as q_id, q.round_type,
                   q.question, q.option1, q.option2, q.option3, q.option4,
                   q.correct_answer, q.timer,
                   (SELECT COUNT(*) FROM rounds r2 WHERE r2.room_id = r.room_id AND r2.id <= r.id) as round_number
            FROM rounds r
            LEFT JOIN questions q ON r.question_id = q.id
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
     * Get round by position for a room (for player polling)
     * Position 1 = first round, 2 = second round, etc.
     */
    public function getRoundByPosition($room_id, $position) {
        $offset = max(0, $position - 1);
        $stmt = $this->conn->prepare("
            SELECT r.id, r.room_id, r.question_id,
                   q.id as q_id, q.round_type,
                   q.question, q.option1, q.option2, q.option3, q.option4,
                   q.correct_answer, q.timer,
                   rm.status_room
            FROM rounds r
            LEFT JOIN questions q ON r.question_id = q.id
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

            // Truefalse: opzioni fisse "Vero"/"Falso" (nel DB sono vuote)
            if (($round['round_type'] ?? '') === 'truefalse') {
                $round['option1'] = 'Vero';
                $round['option2'] = 'Falso';
            }
        }

        return $round;
    }

    /**
     * Save the ranking JSON for a round
     */
    public function saveRanking($roundId, array $ranking) {
        $json = json_encode($ranking, JSON_UNESCAPED_UNICODE);
        $stmt = $this->conn->prepare("UPDATE rounds SET ranking = ? WHERE id = ?");
        $stmt->bind_param("si", $json, $roundId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function setJudgeDecided($roundId) {
        $stmt = $this->conn->prepare("UPDATE rounds SET judge_decided = 1 WHERE id = ?");
        $stmt->bind_param("i", $roundId);
        $stmt->execute();
        $stmt->close();
    }

    public function isJudgeDecided($roundId) {
        $stmt = $this->conn->prepare("SELECT judge_decided FROM rounds WHERE id = ?");
        $stmt->bind_param("i", $roundId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? (bool)$result['judge_decided'] : false;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
