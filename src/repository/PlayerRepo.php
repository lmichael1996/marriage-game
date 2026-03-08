<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for the players table.
 *
 * Each player belongs to a room (room_id) and has a unique username within that room.
 * UNIQUE constraint on (username, room_id) enforced at DB level.
 */
class PlayerRepo {
    private mysqli $conn;
    // 1062 = MySQL duplicate entry error
    private const int DUPLICATE_ENTRY_ERROR_CODE = 1062;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Create a new player associated with a room.
     * Relies on UNIQUE(username, room_id) constraint to prevent duplicates.
     *
     * @param string $username Player display name
     * @param int    $roomId   The room ID
     * @return int The newly created player ID
     * @throws Exception If username already taken in this room or DB error
     */
    public function createPlayer(string $username, int $roomId): int {
        $stmt = $this->conn->prepare("INSERT INTO players (username, room_id) VALUES (?, ?)");
        $stmt->bind_param("si", $username, $roomId);

        if (!$stmt->execute()) {
            $errno = $stmt->errno;
            $stmt->close();

            if ($errno === self::DUPLICATE_ENTRY_ERROR_CODE) {
                throw new Exception('A player with this name is already in the room. Use a different name.');
            }
            throw new Exception('Error creating the player.');
        }

        $playerId = $this->conn->insert_id;
        $stmt->close();
        return (int)$playerId;
    }

    /**
     * Get all players in a room, ordered by join order.
     *
     * @param int $roomId The room ID
     * @return array List of players with id, username, connected_at
     */
    public function getPlayersByRoomId(int $roomId): array {
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
}
