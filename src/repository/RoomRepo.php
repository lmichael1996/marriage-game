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
            INSERT INTO rooms (room_code, question_set_id)
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
     * Update room status
     * Note: status_room column was removed from database
     * Kept for backward compatibility but does nothing
     */
    public function updateStatus($roomCode, $status) {
        // status_room column no longer exists
        return true;
    }

    /**
     * Start room (set status to active)
     */
    public function startRoom($roomCode) {
        return $this->updateStatus($roomCode, 'active');
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
     * Update question set for a room
     */
    public function setQuestionSet($roomCode, $questionSetId) {
        $stmt = $this->conn->prepare("UPDATE rooms SET question_set_id = ? WHERE room_code = ?");
        $stmt->bind_param("is", $questionSetId, strtoupper($roomCode));
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get all rooms by user
     */
    public function getRoomsByUser($userId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM rooms
            WHERE user_id = ?
            ORDER BY id DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rooms = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rooms;
    }

    /**
     * Get question set ID for a room by room code
     */
    public function getQuestionSetIdByRoomCode($roomCode) {
        $stmt = $this->conn->prepare("
            SELECT question_set_id FROM rooms
            WHERE room_code = ?
        ");
        $roomCodeUpper = strtoupper($roomCode);
        $stmt->bind_param("s", $roomCodeUpper);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room ? $room['question_set_id'] : null;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
