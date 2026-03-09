<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for the judges table.
 *
 * Each judge row is linked to a room (room_id).
 * A room can have at most one active judge at a time.
 */
class JudgeRepo {
    private mysqli $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    // 1062 = MySQL duplicate entry error
    private const int DUPLICATE_ENTRY_ERROR_CODE = 1062;

    /**
     * Create a new judge record for a room.
     * Relies on UNIQUE(room_id) constraint to prevent duplicates.
     *
     * @param int $roomId The room to assign the judge to
     * @return int|array The newly created judge ID, or error array on failure
     */
    public function createJudge(int $roomId): int|array {
        $stmt = $this->conn->prepare("INSERT INTO judges (room_id) VALUES (?)");
        $stmt->bind_param('i', $roomId);

        if (!$stmt->execute()) {
            $errno = $stmt->errno;
            $stmt->close();

            if ($errno === self::DUPLICATE_ENTRY_ERROR_CODE) {
                return [
                    'success' => false,
                    'error' => 'Il giudice è già connesso a questa stanza.'
                ];
            }
            return [
                'success' => false,
                'error' => 'Errore durante la creazione del giudice.'
            ];
        }

        $judgeId = $this->conn->insert_id;
        $stmt->close();
        return (int)$judgeId;
    }

    /**
     * Check if at least one judge is connected to the room.
     *
     * @param int $roomId The room ID to check
     * @return bool True if a judge exists for this room
     */
    public function isJudgeConnected(int $roomId): bool {
        $stmt = $this->conn->prepare("SELECT 1 FROM judges WHERE room_id = ? LIMIT 1");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /**
     * Check if the judge for this room was already logged out.
     *
     * @param int $roomId Room ID
     * @return bool True if the judge exists and has logged_out = 1
     */
    public function isJudgeLoggedOut(int $roomId): bool {
        $stmt = $this->conn->prepare(
            "SELECT logged_out FROM judges WHERE room_id = ? LIMIT 1"
        );
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row && (int)$row['logged_out'] === 1;
    }

    /**
     * Mark the judge as logged out (one-time session).
     *
     * @param int $judgeId Judge ID
     */
    public function markLoggedOut(int $judgeId): void {
        $stmt = $this->conn->prepare("UPDATE judges SET logged_out = 1 WHERE id = ?");
        $stmt->bind_param("i", $judgeId);
        $stmt->execute();
        $stmt->close();
    }
}
