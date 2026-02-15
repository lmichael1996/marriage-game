<?php
require_once __DIR__ . '/../config/database.php';

class PlayerRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Create a new player associated with a room
     */
    public function createPlayer($username, $roomCode) {
        // First, get the room ID from room_code
        $stmtRoom = $this->conn->prepare("SELECT id FROM rooms WHERE room_code = ?");
        $roomCodeUpper = strtoupper($roomCode);
        $stmtRoom->bind_param("s", $roomCodeUpper);
        $stmtRoom->execute();
        $result = $stmtRoom->get_result();
        $room = $result->fetch_assoc();
        $stmtRoom->close();

        if (!$room) {
            return false; // Room doesn't exist
        }

        $roomId = $room['id'];
        $stmt = $this->conn->prepare("INSERT INTO players (username, room_id) VALUES (?, ?)");
        $stmt->bind_param("si", $username, $roomId);

        if ($stmt->execute()) {
            $playerId = $this->conn->insert_id;
            $stmt->close();
            return $playerId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Get all players for a specific room
     */
    public function getPlayersByRoom($roomCode) {
        // First, get the room ID from room_code
        $stmtRoom = $this->conn->prepare("SELECT id FROM rooms WHERE room_code = ?");
        $roomCodeUpper = strtoupper($roomCode);
        $stmtRoom->bind_param("s", $roomCodeUpper);
        $stmtRoom->execute();
        $result = $stmtRoom->get_result();
        $room = $result->fetch_assoc();
        $stmtRoom->close();

        if (!$room) {
            return []; // Room doesn't exist
        }

        $roomId = $room['id'];

        // Select with connected_at from database
        $stmt = $this->conn->prepare("
            SELECT
                id,
                username,
                connected_at
            FROM players
            WHERE room_id = ?
            ORDER BY id ASC
        ");

        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $players = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return $players;
    }

    /**
     * Delete all players for a specific room
     */
    public function deletePlayersByRoom($roomCode) {
        // First, get the room ID from room_code
        $stmtRoom = $this->conn->prepare("SELECT id FROM rooms WHERE room_code = ?");
        $roomCodeUpper = strtoupper($roomCode);
        $stmtRoom->bind_param("s", $roomCodeUpper);
        $stmtRoom->execute();
        $result = $stmtRoom->get_result();
        $room = $result->fetch_assoc();
        $stmtRoom->close();

        if (!$room) {
            return false; // Room doesn't exist
        }

        $roomId = $room['id'];
        $stmt = $this->conn->prepare("DELETE FROM players WHERE room_id = ?");
        $stmt->bind_param("i", $roomId);
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
