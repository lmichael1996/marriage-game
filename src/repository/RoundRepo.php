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

        $stmt->bind_param("ii", $room_id, $question_id);

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
            SELECT r.id, r.room_id,
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
            SELECT r.id, r.room_id, r.question_id,
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
                   q.id as question_id, q.round_type,
                   q.question, q.option1, q.option2, q.option3, q.option4,
                   q.correct_answer, q.timer
            FROM rounds r
            JOIN questions q ON r.question_id = q.id
            WHERE r.room_id = ?
            ORDER BY r.id DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();
        return $round;
    }

    /**
     * Delete all rounds for a room (used when advancing to next question)
     */
    public function deleteRoundByRoom($room_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM rounds WHERE room_id = ?
        ");
        $stmt->bind_param("i", $room_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
