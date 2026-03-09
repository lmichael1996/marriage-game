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
     * @return int|array The newly created player ID, or error array on failure
     */
    public function createPlayer(string $username, int $roomId): int|array {
        $stmt = $this->conn->prepare("INSERT INTO players (username, room_id) VALUES (?, ?)");
        $stmt->bind_param("si", $username, $roomId);

        if (!$stmt->execute()) {
            $errno = $stmt->errno;
            $stmt->close();

            if ($errno === self::DUPLICATE_ENTRY_ERROR_CODE) {
                return [
                    'success' => false,
                    'error' => 'Username già utilizzato in questa stanza. Scegli un altro nome.'
                ];
            }
            return [
                'success' => false,
                'error' => 'Errore durante la creazione del giocatore.'
            ];
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
     * Check if a player (by username + room) was already logged out.
     *
     * @param string $username Player display name
     * @param int    $roomId   Room ID
     * @return bool True if the player exists and has logged_out = 1
     */
    public function isPlayerLoggedOut(string $username, int $roomId): bool {
        $stmt = $this->conn->prepare(
            "SELECT logged_out FROM players WHERE username = ? AND room_id = ? LIMIT 1"
        );
        $stmt->bind_param("si", $username, $roomId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row && (int)$row['logged_out'] === 1;
    }

    /**
     * Mark a player as logged out (one-time session).
     *
     * @param int $playerId Player ID
     */
    public function markLoggedOut(int $playerId): void {
        $stmt = $this->conn->prepare("UPDATE players SET logged_out = 1 WHERE id = ?");
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
        $stmt->close();
    }
}
