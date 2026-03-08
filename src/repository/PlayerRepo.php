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
                throw new Exception('Un giocatore con questo nome è già nella stanza. Usa un nome diverso.');
            }
            throw new Exception('Errore durante la creazione del giocatore.');
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

    /**
     * Find a player by room and username.
     *
     * Used by markWinner and player reconnection to resolve username → player ID.
     *
     * @param int    $roomId   The room ID
     * @param string $username The player's display name
     * @return array|null Player row with 'id', or null if not found
     */
    public function getPlayerByRoomAndUsername(int $roomId, string $username): ?array {
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
}
