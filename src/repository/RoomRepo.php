<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for the `rooms` table.
 *
 * Handles CRUD operations on game rooms: creation, lookup,
 * status transitions (running → closed / cancelled), and winner assignment.
 */
class RoomRepo {
    private mysqli $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Create a new room with separate codes and QR URIs for player and judge.
     *
     * @param string $codePlayer     Room code for players (auto-uppercased)
     * @param string $codeJudge      Room code for the judge (auto-uppercased)
     * @param int    $questionSetId  ID of the question set to use
     * @param string $qrUriPlayer    QR image URI for the player code
     * @param string $qrUriJudge     QR image URI for the judge code
     * @return int|false             The new room ID on success, false on failure
     */
    public function createRoom(string $codePlayer, string $codeJudge, int $questionSetId, string $qrUriPlayer, string $qrUriJudge): int|false {
        $codePlayerUpper = strtoupper($codePlayer);
        $codeJudgeUpper = strtoupper($codeJudge);

        $stmt = $this->conn->prepare(
            "INSERT INTO rooms (code_player, code_judge, qset_id, qr_uri_player, qr_uri_judge)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssiss",
            $codePlayerUpper,
            $codeJudgeUpper,
            $questionSetId,
            $qrUriPlayer,
            $qrUriJudge
        );

        if ($stmt->execute()) {
            $roomId = $this->conn->insert_id;
            $stmt->close();
            return $roomId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Find a room by access code (searches both player and judge codes).
     *
     * @param string $code  Room code to look up (case-insensitive)
     * @return array|null   Room row or null if not found
     */
    public function getRoomByCode(string $code): ?array {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM rooms
            WHERE code_player = ? OR code_judge = ?
        ");
        $codeUpper = strtoupper($code);
        $stmt->bind_param("ss", $codeUpper, $codeUpper);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room;
    }

    /**
     * Find a room by its primary key.
     *
     * @param int $roomId  Room ID
     * @return array|null  Room row or null if not found
     */
    public function getRoomById(int $roomId): ?array {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM rooms
            WHERE id = ?
        ");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room;
    }

    /**
     * Start room and optionally clear judge data if no judge is connected.
     *
     * @param int  $roomId     Room ID
     * @param bool $clearJudge If true, also sets code_judge and qr_uri_judge to NULL
     * @return bool True on success
     */
    public function startRoom(int $roomId, bool $clearJudge = false): bool {
        $sql = $clearJudge
            ? "UPDATE rooms SET status_room = 'running', code_judge = NULL, qr_uri_judge = NULL WHERE id = ?"
            : "UPDATE rooms SET status_room = 'running' WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $roomId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Close a room when the game finishes (status → 'closed').
     *
     * @param int $roomId  Room ID
     * @return array{success: bool, message: string}
     */
    public function closeRoom(int $roomId): array {
        $stmt = $this->conn->prepare("
            UPDATE rooms
            SET status_room = 'closed'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $roomId);
        $success = $stmt->execute();
        $stmt->close();

        return [
            'success' => $success,
            'message' => $success ? 'Game finished' : 'Error closing the game'
        ];
    }

    /**
     * Cancel a room manually by the admin (status → 'cancelled').
     *
     * @param int $roomId  Room ID
     * @return array{success: bool, message: string}
     */
    public function cancelRoom(int $roomId): array {
        $stmt = $this->conn->prepare("
            UPDATE rooms
            SET status_room = 'cancelled'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $roomId);
        $success = $stmt->execute();
        $stmt->close();

        return [
            'success' => $success,
            'message' => $success ? 'Room cancelled' : 'Error cancelling the room'
        ];
    }

    /**
     * Get the winner player ID for a room.
     *
     * @param int $roomId  Room ID
     * @return int|null    Winner's player ID, or null if no winner set
     */
    public function getWinnerId(int $roomId): ?int {
        $stmt = $this->conn->prepare("
            SELECT winner_id FROM rooms WHERE id = ?
        ");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room ? $room['winner_id'] : null;
    }

    /**
     * Set the winner of a room.
     *
     * @param int $roomId    Room ID
     * @param int $playerId  Winning player's ID
     * @return bool          True on success
     */
    public function setWinner(int $roomId, int $playerId): bool {
        $stmt = $this->conn->prepare("
            UPDATE rooms SET winner_id = ? WHERE id = ?
        ");
        $stmt->bind_param("ii", $playerId, $roomId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
