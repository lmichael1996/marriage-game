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
    public function createPlayer($username, $roomId) {
        // Check if player with this username already exists in this room
        $stmtCheck = $this->conn->prepare("SELECT id FROM players WHERE username = ? AND room_id = ?");
        $stmtCheck->bind_param("si", $username, $roomId);
        $stmtCheck->execute();
        $existingResult = $stmtCheck->get_result();
        $existingPlayer = $existingResult->fetch_assoc();
        $stmtCheck->close();

        if ($existingPlayer) {
            throw new Exception('Un giocatore con questo nome è già nella stanza. Usa un nome diverso.');
        }

        $stmt = $this->conn->prepare("INSERT INTO players (username, room_id) VALUES (?, ?)");
        $stmt->bind_param("si", $username, $roomId);

        if ($stmt->execute()) {
            $playerId = $this->conn->insert_id;
            $stmt->close();
            return $playerId;
        } else {
            // Catch other database errors
            $errorMsg = $stmt->error;
            $stmt->close();
            throw new Exception('Errore durante la creazione del giocatore: ' . $errorMsg);
        }
    }

    /**
     * Get all players for a specific room by room ID
     */
    public function getPlayersByRoomId($roomId) {
        $stmt = $this->conn->prepare("
            SELECT id, username, connected_at
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
     * Get player by room ID and username
     */
    public function getPlayerByRoomAndUsername($roomId, $username) {
        $stmt = $this->conn->prepare("
            SELECT id FROM players
            WHERE room_id = ? AND username = ?
        ");
        $stmt->bind_param("is", $roomId, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $player = $result->fetch_assoc();
        $stmt->close();

        return $player;
    }

    /**
     * Delete all players from a room by room code
     */
    public function deletePlayersByRoom($roomCode) {
        $stmt = $this->conn->prepare("
            DELETE p FROM players p
            INNER JOIN rooms r ON p.room_id = r.id
            WHERE r.code_player = ? OR r.code_judge = ?
        ");
        $stmt->bind_param("ss", $roomCode, $roomCode);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
