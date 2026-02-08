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
        $result = $this->conn->query("SELECT COUNT(*) as total FROM qsets");
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
            WHERE qs.set_name LIKE ?
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
            WHERE set_name LIKE ?
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
}
