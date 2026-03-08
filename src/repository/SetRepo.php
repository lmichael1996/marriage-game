<?php
require_once __DIR__ . '/../config/database.php';

class SetRepo {
    private $conn;
    private const TEMPORARY_SET_PREFIX = '#Temporary set';

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Get all question sets (excludes temporary sets by default).
     *
     * @param bool $includeTemporary  Include temporary sets
     * @return array                  List of sets with question_count
     */
    public function getAllSets(bool $includeTemporary = false): array {
        $query = "
            SELECT qs.*, COUNT(qq.id) as question_count
            FROM qsets qs
            LEFT JOIN qset_questions qq ON qq.qset_id = qs.id
        ";

        if (!$includeTemporary) {
            $notLike = self::TEMPORARY_SET_PREFIX . '%';
            $query .= " WHERE qs.set_name NOT LIKE '$notLike'";
        }

        $query .= "
            GROUP BY qs.id
            ORDER BY qs.set_name ASC
        ";

        return $this->conn->query($query)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get question set by ID
     */
    public function getSetById(int $setId): ?array {
        $stmt = $this->conn->prepare("
            SELECT qs.*, COUNT(qq.id) as question_count
            FROM qsets qs
            LEFT JOIN qset_questions qq ON qq.qset_id = qs.id
            WHERE qs.id = ?
            GROUP BY qs.id
        ");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $result = $stmt->get_result();
        $set = $result->fetch_assoc();
        $stmt->close();

        return $set;
    }

    /**
     * Search question sets by name.
     *
     * @param string $searchTerm  Search term
     * @param string $searchType  'contains' or 'starts_with'
     * @return array              Matching sets with question_count
     */
    public function searchSets(string $searchTerm, string $searchType = 'contains'): array {
        $searchPattern = $searchType === 'starts_with' ? "$searchTerm%" : "%$searchTerm%";

        $stmt = $this->conn->prepare("
            SELECT qs.*, COUNT(qq.id) as question_count
            FROM qsets qs
            LEFT JOIN qset_questions qq ON qq.qset_id = qs.id
            WHERE qs.set_name LIKE ?
            GROUP BY qs.id
            ORDER BY qs.set_name ASC
        ");
        $stmt->bind_param("s", $searchPattern);
        $stmt->execute();
        $sets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $sets;
    }

    /**
     * Add new question set
     */
    public function createSet(string $setName, string $setDescription = ''): int|false {
        $stmt = $this->conn->prepare("
            INSERT INTO qsets (set_name, set_description)
            VALUES (?, ?)
        ");
        $stmt->bind_param("ss", $setName, $setDescription);

        if ($stmt->execute()) {
            $setId = $this->conn->insert_id;
            $stmt->close();
            return $setId;
        }

        $stmt->close();
        return false;
    }

    /**
     * Update question set
     */
    public function updateSet(int $setId, string $setName, string $setDescription): bool {
        $stmt = $this->conn->prepare("
            UPDATE qsets
            SET set_name = ?, set_description = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $setName, $setDescription, $setId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Delete question set
     */
    public function deleteSet(int $setId): bool {
        $stmt = $this->conn->prepare("
            DELETE FROM qsets
            WHERE id = ?
        ");
        $stmt->bind_param("i", $setId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get questions in a set
     */
    public function getQuestions(int $setId): array {
        $stmt = $this->conn->prepare("
            SELECT q.*, qq.order_in_set, qc.category_name, qc.color
            FROM qset_questions qq
            JOIN questions q ON q.id = qq.question_id
            LEFT JOIN question_categories qc ON qc.id = q.category_id
            WHERE qq.qset_id = ?
            ORDER BY qq.order_in_set ASC
        ");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $result = $stmt->get_result();
        $questions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $questions;
    }

    /**
     * Add question to set
     */
    public function addQuestionToSet(int $setId, int $questionId, int $orderInSet = 0): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO qset_questions (qset_id, question_id, order_in_set)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iii", $setId, $questionId, $orderInSet);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Add question to set at a specific position (sposta le altre avanti)
     */
    public function insertQuestionAt($setId, $questionId, $position) {
        // Incrementa l'ordine di tutte le domande dalla posizione specificata in poi
        $stmt = $this->conn->prepare("
            UPDATE qset_questions
            SET order_in_set = order_in_set + 1
            WHERE qset_id = ? AND order_in_set >= ?
        ");
        $stmt->bind_param("ii", $setId, $position);
        $stmt->execute();
        $stmt->close();

        // Aggiungi la nuova domanda alla posizione specificata
        $stmt = $this->conn->prepare("
            INSERT INTO qset_questions (qset_id, question_id, order_in_set)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iii", $setId, $questionId, $position);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Remove question from set
     */
    public function removeQuestionFromSet($setId, $questionId) {
        $stmt = $this->conn->prepare("
            DELETE FROM qset_questions
            WHERE qset_id = ? AND question_id = ?
        ");
        $stmt->bind_param("ii", $setId, $questionId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Update questions order (for drag & drop)
     */
    public function reorderQuestions($setId, $questions) {
        try {
            foreach ($questions as $item) {
                $questionId = $item['question_id'];
                $order = $item['order'];

                $stmt = $this->conn->prepare("
                    UPDATE qset_questions
                    SET order_in_set = ?
                    WHERE qset_id = ? AND question_id = ?
                ");
                $stmt->bind_param("iii", $order, $setId, $questionId);
                if (!$stmt->execute()) {
                    $stmt->close();
                    return false;
                }
                $stmt->close();
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function moveQuestionUp($setId, $questionId) {
        // Recupera l'ordine attuale della domanda
        $stmt = $this->conn->prepare("
            SELECT order_in_set FROM qset_questions
            WHERE qset_id = ? AND question_id = ?
        ");
        $stmt->bind_param("ii", $setId, $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row || $row['order_in_set'] <= 1) {
            return false; // È già la prima domanda
        }

        $currentOrder = $row['order_in_set'];
        $newOrder = $currentOrder - 1;

        // Sposta la domanda precedente giù
        $stmt = $this->conn->prepare("
            UPDATE qset_questions
            SET order_in_set = ?
            WHERE qset_id = ? AND order_in_set = ?
        ");
        $stmt->bind_param("iii", $currentOrder, $setId, $newOrder);
        $stmt->execute();
        $stmt->close();

        // Sposta la domanda attuale su
        $stmt = $this->conn->prepare("
            UPDATE qset_questions
            SET order_in_set = ?
            WHERE qset_id = ? AND question_id = ?
        ");
        $stmt->bind_param("iii", $newOrder, $setId, $questionId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function moveQuestionDown($setId, $questionId) {
        // Recupera l'ordine attuale della domanda e il totale
        $stmt = $this->conn->prepare("
            SELECT order_in_set FROM qset_questions
            WHERE qset_id = ? AND question_id = ?
        ");
        $stmt->bind_param("ii", $setId, $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return false;
        }

        $currentOrder = $row['order_in_set'];

        // Recupera il totale di domande nel set
        $stmt = $this->conn->prepare("
            SELECT MAX(order_in_set) as max_order FROM qset_questions
            WHERE qset_id = ?
        ");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $maxOrder = $row['max_order'] ?? 0;

        if ($currentOrder >= $maxOrder) {
            return false; // È già l'ultima domanda
        }

        $newOrder = $currentOrder + 1;

        // Sposta la domanda successiva su
        $stmt = $this->conn->prepare("
            UPDATE qset_questions
            SET order_in_set = ?
            WHERE qset_id = ? AND order_in_set = ?
        ");
        $stmt->bind_param("iii", $currentOrder, $setId, $newOrder);
        $stmt->execute();
        $stmt->close();

        // Sposta la domanda attuale giù
        $stmt = $this->conn->prepare("
            UPDATE qset_questions
            SET order_in_set = ?
            WHERE qset_id = ? AND question_id = ?
        ");
        $stmt->bind_param("iii", $newOrder, $setId, $questionId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get question by counter (order_in_set) for a specific question set
     */
    public function getQuestionAtPosition($qsetId, $counter) {
        $offset = max(0, $counter - 1);
        $stmt = $this->conn->prepare("
            SELECT qq.id as qset_question_id, qq.question_id, q.*,
                   qc.category_name, qc.color as category_color
            FROM qset_questions qq
            JOIN questions q ON qq.question_id = q.id
            LEFT JOIN question_categories qc ON q.category_id = qc.id
            WHERE qq.qset_id = ?
            ORDER BY qq.order_in_set ASC
            LIMIT 1 OFFSET ?
        ");
        $stmt->bind_param("ii", $qsetId, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $question = $result->fetch_assoc();
        $stmt->close();

        return $question;
    }

    /**
     * Get total count of questions in a specific question set
     */
    public function countQuestions($qsetId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total
            FROM qset_questions
            WHERE qset_id = ?
        ");
        $stmt->bind_param("i", $qsetId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['total'] ?? 0;
    }
}
