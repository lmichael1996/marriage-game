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
    public function createRoom($code, $userId, $questionSetId = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO rooms (room_code, user_id, question_set_id, status_room) 
            VALUES (?, ?, ?, 'waiting')
        ");
        $stmt->bind_param("sii", strtoupper($code), $userId, $questionSetId);
        
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
            SELECT r.*, u.username as admin_username 
            FROM rooms r
            JOIN users u ON r.user_id = u.id
            WHERE r.room_code = ?
        ");
        $stmt->bind_param("s", strtoupper($code));
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
        if ($userId) {
            $stmt = $this->conn->prepare("
                SELECT * FROM rooms 
                WHERE user_id = ? AND status_room IN ('waiting', 'active')
                ORDER BY created_at DESC
            ");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $this->conn->prepare("
                SELECT * FROM rooms 
                WHERE status_room IN ('waiting', 'active')
                ORDER BY created_at DESC
            ");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $rooms = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $rooms;
    }
    
    /**
     * Update room status
     */
    public function updateStatus($roomCode, $status) {
        $stmt = $this->conn->prepare("UPDATE rooms SET status_room = ? WHERE room_code = ?");
        $stmt->bind_param("ss", $status, strtoupper($roomCode));
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Start room (set status to active)
     */
    public function startRoom($roomCode) {
        return $this->updateStatus($roomCode, 'active');
    }
    
    /**
     * Close room and set closed_at timestamp
     */
    public function closeRoom($roomCode) {
        $stmt = $this->conn->prepare("
            UPDATE rooms 
            SET status_room = 'closed', closed_at = NOW() 
            WHERE room_code = ?
        ");
        $stmt->bind_param("s", strtoupper($roomCode));
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
            WHERE room_code = ? AND status_room IN ('waiting', 'active')
        ");
        $stmt->bind_param("s", strtoupper($code));
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
            ORDER BY created_at DESC
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
            WHERE room_code = ? AND status_room IN ('waiting', 'active')
        ");
        $stmt->bind_param("s", strtoupper($roomCode));
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
