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
     * @param string      $codePlayer     Room code for players (auto-uppercased)
     * @param string      $codeJudge      Room code for the judge (auto-uppercased)
     * @param int         $questionSetId  ID of the question set to use
     * @param string      $qrUriPlayer    QR image URI for the player code
     * @param string      $qrUriJudge     QR image URI for the judge code
     * @param string      $baseUrl        Base URL used to generate QR codes
     * @return int|false                  The new room ID on success, false on failure
     */
    public function createRoom(string $codePlayer, string $codeJudge, int $questionSetId, string $qrUriPlayer, string $qrUriJudge, string $baseUrl): int|false {
        $codePlayerUpper = strtoupper($codePlayer);
        $codeJudgeUpper = strtoupper($codeJudge);

        $stmt = $this->conn->prepare(
            "INSERT INTO rooms (code_player, code_judge, qset_id, qr_uri_player, qr_uri_judge, base_url)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssisss",
            $codePlayerUpper,
            $codeJudgeUpper,
            $questionSetId,
            $qrUriPlayer,
            $qrUriJudge,
            $baseUrl
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
     * Find a room by player code (any status).
     *
     * @param string $code  Player room code (case-insensitive)
     * @return array|null   Room row or null if not found
     */
    public function getRoomByPlayerCode(string $code): ?array {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM rooms
            WHERE code_player = ?
        ");
        $codeUpper = strtoupper($code);
        $stmt->bind_param("s", $codeUpper);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room;
    }

    /**
     * Find a room by judge code (any status).
     *
     * @param string $code  Judge room code (case-insensitive)
     * @return array|null   Room row or null if not found
     */
    public function getRoomByJudgeCode(string $code): ?array {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM rooms
            WHERE code_judge = ?
        ");
        $codeUpper = strtoupper($code);
        $stmt->bind_param("s", $codeUpper);
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
     * Start a room: set status to 'running'.
     * If no judge is connected, clear judge access codes.
     *
     * @param int  $roomId      Room ID
     * @param bool $clearJudge  Whether to clear judge code and QR
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
            'message' => $success ? 'Gioco terminato' : 'Errore durante la chiusura del gioco'
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
            'message' => $success ? 'Stanza annullata' : 'Errore durante l\'annullamento della stanza'
        ];
    }

    /**
     * Get the final ranking JSON for a room.
     *
     * @param int $roomId  Room ID
     * @return array       Decoded ranking array, empty if not set
     */
    public function getFinalRanking(int $roomId): array {
        $stmt = $this->conn->prepare("
            SELECT final_ranking FROM rooms WHERE id = ?
        ");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room ? json_decode($room['final_ranking'] ?? '[]', true) : [];
    }

    /**
     * Save the final ranking JSON for a room.
     *
     * @param int   $roomId   Room ID
     * @param array $ranking  Array of [{player_id, username, score}, ...]
     * @return bool           True on success
     */
    public function setFinalRanking(int $roomId, array $ranking): bool {
        $json = json_encode($ranking);
        $stmt = $this->conn->prepare("
            UPDATE rooms SET final_ranking = ? WHERE id = ?
        ");
        $stmt->bind_param("si", $json, $roomId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
