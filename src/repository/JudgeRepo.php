<?php
require_once __DIR__ . '/../config/database.php';

class JudgeRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Inserisce un giudice associato a una stanza
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
     * Controlla se esiste almeno un giudice connesso alla stanza
     */
    public function isJudgeConnected(int $roomId): bool {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM judges WHERE room_id = ?");
        $stmt->bind_param("i", $roomId);
        $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();
        return $count > 0;
    }
}
