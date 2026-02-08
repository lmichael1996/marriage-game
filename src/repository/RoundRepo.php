<?php
require_once __DIR__ . '/../config/database.php';

class RoundRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Create a new round for a room (insert into rounds table)
     * Stores only: room_id, qset_question_id
     * Rankings are computed on-demand from player_answers table
     */
    public function createRound($room_id, $qset_question_id) {
        $stmt = $this->conn->prepare("
            INSERT INTO rounds (room_id, qset_question_id, created_at)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");

        $stmt->bind_param("ii", $room_id, $qset_question_id);

        if ($stmt->execute()) {
            $roundId = $this->conn->insert_id;
            $stmt->close();
            return $roundId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Create a new round
     */
    public function create($questionSetId, $round_number, $round_type, $question, $option1, $option2, $option3, $option4, $correct_answer, $timer = 10) {
        $stmt = $this->conn->prepare("
            INSERT INTO questions (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissssssii", $questionSetId, $round_number, $round_type, $question, $option1, $option2, $option3, $option4, $correct_answer, $timer);

        if ($stmt->execute()) {
            $roundId = $this->conn->insert_id;
            $stmt->close();
            return $roundId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Get all rounds
     */
    public function getAllRounds() {
        $result = $this->conn->query("SELECT * FROM questions ORDER BY round_number DESC, id DESC");
        $rounds = [];
        while ($row = $result->fetch_assoc()) {
            $rounds[] = $row;
        }
        return $rounds;
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
     * Get active round - This method is no longer used since status_round column was removed
     * Kept for backward compatibility but returns null
     */
    public function getActiveRound($questionSetId = null) {
        // status_round column no longer exists in database
        // Active round tracking should be done in session or a separate status table
        return null;
    }

    /**
     * Start a round (set to active)
     * Note: status_round column was removed from database
     * Kept for backward compatibility but does nothing
     */
    public function startRound($round_id) {
        // status_round column no longer exists
        return true;
    }

    /**
     * Close a round
     * Note: status_round column was removed from database
     * Kept for backward compatibility but does nothing
     */
    public function closeRound($round_id) {
        // status_round column no longer exists
        return true;
    }

    /**
     * Update correct answer for a round
     */
    public function updateAnswer($round_id, $correct_answer) {
        $stmt = $this->conn->prepare("UPDATE questions SET correct_answer = ? WHERE id = ?");
        $stmt->bind_param("ii", $correct_answer, $round_id);
        $stmt->execute();
        $stmt->close();

        // Note: player_answers table handling would go here if it existed
        // For now, just update the question's correct answer

        return true;
    }

    /**
     * Get round by ID
     */
    public function getRoundById($round_id) {
        $stmt = $this->conn->prepare("
            SELECT r.id, r.room_id, r.round_number,
                   q.id as question_id, q.question_set_id, q.round_number as question_round_number, q.round_type,
                   q.question, q.option1, q.option2, q.option3, q.option4,
                   q.correct_answer, q.timer
            FROM rounds r
            LEFT JOIN rooms ro ON r.room_id = ro.id
            LEFT JOIN questions q ON r.round_number = q.round_number AND q.question_set_id = ro.question_set_id
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
     * Get round by room_id and round_number
     */
    public function getRoundByRoomAndNumber($room_id, $round_number) {
        $stmt = $this->conn->prepare("
            SELECT r.id, r.room_id, r.round_number
            FROM rounds r
            WHERE r.room_id = ? AND r.round_number = ?
        ");
        $stmt->bind_param("ii", $room_id, $round_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $round = $result->fetch_assoc();
        $stmt->close();
        return $round;
    }

    /**
     * Get round statistics from player_answers
     * Note: player_answers table doesn't exist yet - return empty stats
     */
    public function getRoundStats($round_id) {
        // Table doesn't exist - return default empty stats
        return [
            'total_players' => 0,
            'correct_answers' => 0,
            'fastest_time' => null,
            'avg_time' => null
        ];
    }

    /**
     * Get all rounds with statistics
     */
    public function getAllRoundsWithStats() {
        $rounds = $this->getAllRounds();
        foreach ($rounds as &$round) {
            $round['stats'] = $this->getRoundStats($round['id']);
            $round['winners'] = $this->getWinningPlayers($round['id'], $round['round_type']);
        }
        return $rounds;
    }

    /**
     * Get winning players for a round based on round type
     */
    public function getWinningPlayers($round_id, $round_type) {
        // Get top 10 fastest answerers (by answer_time)
        $stmt = $this->conn->prepare("
            SELECT pa.username, pa.answer_time
            FROM player_answers pa
            WHERE pa.round_id = ?
            ORDER BY pa.answer_time ASC
            LIMIT 10
        ");

        $stmt->bind_param("i", $round_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $winners = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $winners;
    }

    /**
     * Add a round to a question set
     */
    public function addRoundToSet($setId, $roundType, $question, $option1, $option2, $option3, $option4, $correctAnswer) {
        // Get next round number
        $stmt = $this->conn->prepare("SELECT MAX(round_number) as max_num FROM questions WHERE question_set_id = ?");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $nextNumber = ($row['max_num'] ?? 0) + 1;
        $stmt->close();

        // Insert round
        $stmt = $this->conn->prepare("
            INSERT INTO questions (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissssssi", $setId, $nextNumber, $roundType, $question, $option1, $option2, $option3, $option4, $correctAnswer);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Delete round by ID
     */
    public function deleteRound($round_id) {
        $stmt = $this->conn->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->bind_param("i", $round_id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Reset all rounds of a question set to pending status
     * Note: status_round column was removed from database
     * This method now just returns true as no reset is needed
     */
    public function resetRoundsBySetId($question_set_id) {
        // No longer needs to do anything - player answers are handled separately
        // when game actually starts. This is just a placeholder for backward compatibility.
        return true;
    }

    /**
     * Get all rounds for a specific room
     */
    public function getRoundsByRoom($room_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, room_id, round_number, type_game
                FROM rounds
                WHERE room_id = ?
                ORDER BY round_number ASC
            ");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $rounds = [];
            while ($row = $result->fetch_assoc()) {
                $rounds[] = $row;
            }

            $stmt->close();
            return $rounds;
        } catch (Exception $e) {
            error_log("getRoundsByRoom ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
