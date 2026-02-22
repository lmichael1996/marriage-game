<?php
require_once __DIR__ . '/../config/database.php';

class RoomRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Create a new room
     */
    public function createRoom($code, $questionSetId = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO rooms (room_code, qset_id)
            VALUES (?, ?)
        ");
        $codeUpper = strtoupper($code);
        $stmt->bind_param("si", $codeUpper, $questionSetId);

        if ($stmt->execute()) {
            $roomId = $this->conn->insert_id;
            $stmt->close();
            return $roomId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Get room by code
     */
    public function getRoomByCode($code) {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM rooms
            WHERE room_code = ?
        ");
        $codeUpper = strtoupper($code);
        $stmt->bind_param("s", $codeUpper);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room;
    }

    /**
     * Get active rooms (waiting or active status)
     */
    public function getActiveRooms($userId = null) {
        $stmt = $this->conn->prepare("
            SELECT * FROM rooms
            ORDER BY id DESC
        ");

        $stmt->execute();
        $result = $stmt->get_result();
        $rooms = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rooms;
    }

    /**
     * Start room (just returns true, no status tracking needed)
     */
    public function startRoom($roomCode) {
        return true;
    }

    /**
     * Delete room completely
     */
    public function deleteRoom($roomCode) {
        $stmt = $this->conn->prepare("
            DELETE FROM rooms
            WHERE room_code = ?
        ");
        $roomCodeUpper = strtoupper($roomCode);
        $stmt->bind_param("s", $roomCodeUpper);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Verify if room code exists and is active
     */
    public function verifyRoomCode($code) {
        $stmt = $this->conn->prepare("
            SELECT id FROM rooms
            WHERE room_code = ?
        ");
        $codeUpper = strtoupper($code);
        $stmt->bind_param("s", $codeUpper);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * Get question set ID for a room by room code
     */
    public function getQuestionSetIdByRoomCode($roomCode) {
        $stmt = $this->conn->prepare("
            SELECT qset_id FROM rooms
            WHERE room_code = ?
        ");
        $roomCodeUpper = strtoupper($roomCode);
        $stmt->bind_param("s", $roomCodeUpper);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room ? $room['qset_id'] : null;
    }

    /**
     * Get players/devices connected to a room
     */
    public function getRoomPlayers($roomCode) {
        require_once __DIR__ . '/PlayerRepo.php';
        $playerRepo = new PlayerRepo();
        return $playerRepo->getPlayersByRoom($roomCode);
    }

    /**
     * Count correct answers for a player in a room
     */
    public function countCorrectAnswersByPlayer($roomId, $username) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as correct_count
            FROM player_answers pa
            JOIN rounds r ON pa.round_id = r.id
            JOIN questions q ON r.question_id = q.id
            WHERE r.room_id = ? AND pa.username = ? AND pa.answer = q.correct_answer
        ");
        $stmt->bind_param("is", $roomId, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['correct_count'] : 0;
    }

    /**
     * Insert winner record
     */
    public function insertWinner($roomId, $playerId) {
        $stmt = $this->conn->prepare("
            INSERT INTO winners (room_id, user_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
        ");
        $stmt->bind_param("ii", $roomId, $playerId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get winner for a room
     */
    public function getWinner($roomId) {
        $stmt = $this->conn->prepare("
            SELECT id, room_id, user_id
            FROM winners
            WHERE room_id = ?
        ");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $winner = $result->fetch_assoc();
        $stmt->close();

        return $winner;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
