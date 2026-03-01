<?php
require_once __DIR__ . '/../config/database.php';

class RoomRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Create a new room with separate codes and QR URIs for player and judge
     */
    public function createRoom($codePlayer, $codeJudge, $questionSetId = null, $qrUriPlayer = null, $qrUriJudge = null) {
        $codePlayerUpper = strtoupper($codePlayer);
        $codeJudgeUpper = strtoupper($codeJudge);

        $stmt = $this->conn->prepare(
            "INSERT INTO rooms (code_player, code_judge, qset_id, qr_uri_player, qr_uri_judge)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssiss", $codePlayerUpper, $codeJudgeUpper, $questionSetId, $qrUriPlayer, $qrUriJudge);

        if ($stmt->execute()) {
            $roomId = $this->conn->insert_id;
            $stmt->close();
            return $roomId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Get room by code (searches both code_player and code_judge)
     */
    public function getRoomByCode($code) {
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
     * Get room by ID
     */
    public function getRoomById($roomId) {
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
     * Get active rooms (waiting or active status)
     */
    public function getActiveRooms($userId = null) {
        $stmt = $this->conn->prepare("
            SELECT * FROM rooms
            ORDER BY id DESC
        ");

        $stmt->execute();
        $result = $stmt->get_result();
        $rooms = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rooms;
    }

    /**
     * Start room by updating its status to 'running'
     */
    public function startRoom($roomId) {
        $stmt = $this->conn->prepare("
            UPDATE rooms
            SET status_room = 'running'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $roomId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Close room when game finishes (update status to 'closed')
     */
    public function closeRoom($roomId) {
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
            'message' => $success ? 'Partita terminata' : 'Errore nella chiusura della partita'
        ];
    }

    /**
     * Cancel room manually (admin closes room, update status to 'cancelled')
     */
    public function cancelRoom($roomId) {
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
            'message' => $success ? 'Stanza cancellata' : 'Errore nella cancellazione della stanza'
        ];
    }

    /**
     * Verify if room code exists and is open (can accept new players)
     */
    public function verifyRoomCode($code) {
        $stmt = $this->conn->prepare("
            SELECT id FROM rooms
            WHERE (code_player = ? OR code_judge = ?)
            AND status_room = 'open'
        ");
        $codeUpper = strtoupper($code);
        $stmt->bind_param("ss", $codeUpper, $codeUpper);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room ? $room['id'] : null;
    }

    /**
     * Get question set ID for a room by room ID
     */
    public function getQuestionSetIdByRoomId($roomId) {
        $stmt = $this->conn->prepare("
            SELECT qset_id FROM rooms
            WHERE id = ?
        ");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();

        return $room ? $room['qset_id'] : null;
    }

    /**
     * Get players/devices connected to a room
     */
    public function getRoomPlayers($roomId) {
        require_once __DIR__ . '/PlayerRepo.php';
        $playerRepo = new PlayerRepo();
        return $playerRepo->getPlayersByRoomId($roomId);
    }

    /**
     * Count correct answers for a player in a room
     */
    public function countCorrectAnswersByPlayer($roomId, $playerId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as correct_count
            FROM player_answers pa
            JOIN rounds r ON pa.round_id = r.id
            WHERE r.room_id = ? AND pa.player_id = ?
        ");
        $stmt->bind_param("ii", $roomId, $playerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['correct_count'] : 0;
    }

    /**
     * Insert winner record
     */
    public function insertWinner($roomId, $playerId) {
        $stmt = $this->conn->prepare("
            INSERT INTO winners (room_id, user_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
        ");

        // Support both integer and null for playerId
        if ($playerId === null) {
            $stmt->bind_param("is", $roomId, $playerId);
        } else {
            $stmt->bind_param("ii", $roomId, $playerId);
        }

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get winner for a room
     */
    public function getWinner($roomId) {
        $stmt = $this->conn->prepare("
            SELECT id, room_id, user_id
            FROM winners
            WHERE room_id = ?
        ");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $result = $stmt->get_result();
        $winner = $result->fetch_assoc();
        $stmt->close();

        return $winner;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
