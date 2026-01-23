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
        $stmt = $this->conn->prepare("INSERT INTO players (username, room_code) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, strtoupper($roomCode));
        
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
        $stmt = $this->conn->prepare("
            SELECT 
                id,
                username,
                connected_at,
                last_seen,
                'online' as status
            FROM players 
            WHERE room_code = ?
            ORDER BY connected_at ASC
        ");
        
        $stmt->bind_param("s", strtoupper($roomCode));
        $stmt->execute();
        $result = $stmt->get_result();
        $players = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $players;
    }
    
    /**
     * Get a player by ID
     */
    public function getPlayerById($playerId) {
        $stmt = $this->conn->prepare("SELECT * FROM players WHERE id = ?");
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $player = $result->fetch_assoc();
        $stmt->close();
        
        return $player;
    }
    
    /**
     * Delete all players for a specific room
     */
    public function deletePlayersByRoom($roomCode) {
        $stmt = $this->conn->prepare("DELETE FROM players WHERE room_code = ?");
        $stmt->bind_param("s", strtoupper($roomCode));
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Update player's last seen timestamp
     */
    public function updateLastSeen($playerId) {
        $stmt = $this->conn->prepare("UPDATE players SET last_seen = NOW() WHERE id = ?");
        $stmt->bind_param("i", $playerId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Reset all player scores in a room
     */
    public function resetPlayerScoresByRoom($roomCode) {
        $stmt = $this->conn->prepare("UPDATE players SET total_score = 0 WHERE room_code = ?");
        $stmt->bind_param("s", strtoupper($roomCode));
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
