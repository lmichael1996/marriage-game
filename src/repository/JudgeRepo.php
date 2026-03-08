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
     * @return int The newly created judge ID
     * @throws Exception If a judge already exists for this room or DB error
     */
    public function createJudge(int $roomId): int {
        $stmt = $this->conn->prepare("INSERT INTO judges (room_id) VALUES (?)");
        $stmt->bind_param('i', $roomId);

        if (!$stmt->execute()) {
            $errno = $stmt->errno;
            $stmt->close();

            if ($errno === self::DUPLICATE_ENTRY_ERROR_CODE) {
                throw new Exception('A judge is already connected to this room.');
            }
            throw new Exception('Error creating the judge.');
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
}
