<?php
require_once __DIR__ . '/../config/database.php';

class QuestionSetRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Get all question sets
     */
    public function getAll($limit = null, $offset = 0) {
        $query = "
            SELECT qs.*, COUNT(qq.id) as question_count
            FROM qsets qs
            LEFT JOIN qset_questions qq ON qq.qset_id = qs.id
            WHERE qs.is_saved = 1
            GROUP BY qs.id
            ORDER BY qs.set_name ASC
        ";

        if ($limit) {
            $query .= " LIMIT $limit OFFSET $offset";
        }

        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get total count of question sets
     */
    public function getTotalCount() {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM qsets WHERE is_saved = 1");
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * Get question set by ID
     */
    public function getById($setId) {
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
     * Search question sets
     */
    public function search($searchTerm, $searchType = 'contains', $limit = null, $offset = 0) {
        if ($searchType === 'starts_with') {
            $searchPattern = $searchTerm . '%';
        } else {
            $searchPattern = '%' . $searchTerm . '%';
        }

        $query = "
            SELECT qs.*, COUNT(qq.id) as question_count
            FROM qsets qs
            LEFT JOIN qset_questions qq ON qq.qset_id = qs.id
            WHERE qs.set_name LIKE ? AND qs.is_saved = 1
            GROUP BY qs.id
            ORDER BY qs.set_name ASC
        ";

        if ($limit) {
            $query .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $sets = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $sets;
    }

    /**
     * Count search results
     */
    public function countSearch($searchTerm, $searchType = 'contains') {
        if ($searchType === 'starts_with') {
            $searchPattern = $searchTerm . '%';
        } else {
            $searchPattern = '%' . $searchTerm . '%';
        }

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total FROM qsets
            WHERE set_name LIKE ? AND is_saved = 1
        ");
        $stmt->bind_param("s", $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['total'] ?? 0;
    }

    /**
     * Add new question set
     */
    public function add($setName, $setDescription = '') {
        // Se il nome è vuoto o "Nuovo Set", aggiungiamo un timestamp per rendere unico
        if (empty($setName) || $setName === 'Nuovo Set') {
            $setName = 'Nuovo Set - ' . time();
        }

        $stmt = $this->conn->prepare("
            INSERT INTO qsets (set_name, set_description, is_saved)
            VALUES (?, ?, 0)
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
    public function update($setId, $setName, $setDescription) {
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
    public function delete($setId) {
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
     * Set the is_saved flag for a question set
     */
    public function setSaved($setId, $isSaved) {
        $isSavedValue = $isSaved ? 1 : 0;
        $stmt = $this->conn->prepare("
            UPDATE qsets
            SET is_saved = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $isSavedValue, $setId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Get questions in a set
     */
    public function getQuestions($setId) {
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
    public function addQuestion($setId, $questionId, $orderInSet = 0) {
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
    public function addQuestionAtPosition($setId, $questionId, $position) {
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
    public function removeQuestion($setId, $questionId) {
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
    public function updateQuestionsOrder($setId, $questions) {
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
     * Get question IDs for a set ordered by position
     */
    public function getQuestionIds($setId) {
        $stmt = $this->conn->prepare("
            SELECT qq.question_id
            FROM qset_questions qq
            WHERE qq.qset_id = ?
            ORDER BY qq.order_in_set ASC
        ");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['question_id'];
        }
        $stmt->close();

        return $ids;
    }

    /**
     * Get all questions for a set with their qset_question_id (for checking if round exists)
     */
    public function getQuestionsWithQsetId($setId) {
        $stmt = $this->conn->prepare("
            SELECT qq.id as qset_question_id, q.*
            FROM qset_questions qq
            JOIN questions q ON qq.question_id = q.id
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

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
