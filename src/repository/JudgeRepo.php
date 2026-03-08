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

    /**
     * Create a new judge record for a room.
     *
     * @param int $roomId The room to assign the judge to
     * @return int The newly created judge ID
     */
    public function createJudge(int $roomId): int {
        $stmt = $this->conn->prepare("INSERT INTO judges (room_id) VALUES (?)");
        $stmt->bind_param('i', $roomId);
        $stmt->execute();
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
