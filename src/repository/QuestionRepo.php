<?php
require_once __DIR__ . '/../config/database.php';

class QuestionRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function getQuestionById($questionId) {
        $stmt = $this->conn->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all individual questions with pagination
     */
    public function getAll($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $result = $this->conn->query("
            SELECT q.id, q.round_type, q.question, q.option1, q.option2, q.option3, q.option4, q.correct_answer, q.timer, q.category_id,
                   c.category_name, c.color
            FROM questions q
            LEFT JOIN question_categories c ON q.category_id = c.id
            WHERE q.id NOT IN (1, 2)
            ORDER BY q.question ASC
            LIMIT $perPage OFFSET $offset
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get total count of questions
     */
    public function getTotalCount() {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM questions");
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }

    /**
     * Insert a single question
     */
    public function insertQuestion($questionData) {
        $question = $questionData['question'] ?? '';
        $roundType = $questionData['round_type'] ?? 'multiple';
        $categoryId = intval($questionData['category_id'] ?? 1);
        $timer = intval($questionData['timer'] ?? 30);

        // Campi risposte
        $answer1 = $questionData['answer1'] ?? '';
        $answer2 = $questionData['answer2'] ?? '';
        $answer3 = $questionData['answer3'] ?? '';
        $answer4 = $questionData['answer4'] ?? '';
        $correctAnswer = isset($questionData['correct_answer']) ? intval($questionData['correct_answer']) : null;

        // Validazione base
        if (empty($question)) {
            return false;
        }

        // Normalizza i dati in base al tipo
        if ($roundType === 'truefalse') {
            $answer1 = $answer1 ?: 'Vero';
            $answer2 = $answer2 ?: 'Falso';
            $answer3 = '';
            $answer4 = '';
            $correctAnswer = intval($correctAnswer ?? 1);
        } else if ($roundType === 'multiple') {
            // Assicura che tutte le risposte siano presenti
            $correctAnswer = intval($correctAnswer ?? 1);
        } else if ($roundType === 'clickfirst') {
            $answer1 = $answer2 = $answer3 = $answer4 = '';
            $correctAnswer = null;
        }

        // Inserisci la domanda
        $stmt = $this->conn->prepare("
            INSERT INTO questions (round_type, question, option1, option2, option3, option4, correct_answer, timer, category_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssssii",
            $roundType,
            $question,
            $answer1,
            $answer2,
            $answer3,
            $answer4,
            $correctAnswer,
            $timer,
            $categoryId
        );

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }

        return false;
    }

    /**
     * Update a single question
     */
    public function updateQuestion($data) {
        $id = $data['id'] ?? null;
        $question = $data['question'] ?? '';
        $type = $data['round_type'] ?? '';
        $categoryId = $data['category_id'] ?? 1;
        $timer = $data['timer'] ?? 30;
        $answer1 = $data['answer1'] ?? null;
        $answer2 = $data['answer2'] ?? null;
        $answer3 = $data['answer3'] ?? null;
        $answer4 = $data['answer4'] ?? null;
        $correctAnswer = $data['correct_answer'] ?? null;

        // Build dynamic query based on what fields are provided
        $updateFields = ["question = ?", "round_type = ?", "category_id = ?", "timer = ?"];
        $params = [$question, $type, $categoryId, $timer];
        $paramTypes = "ssii";

        // Only update answer fields if they are provided
        if ($answer1 !== null) {
            $updateFields[] = "option1 = ?";
            $params[] = $answer1;
            $paramTypes .= "s";
        }
        if ($answer2 !== null) {
            $updateFields[] = "option2 = ?";
            $params[] = $answer2;
            $paramTypes .= "s";
        }
        if ($answer3 !== null) {
            $updateFields[] = "option3 = ?";
            $params[] = $answer3;
            $paramTypes .= "s";
        }
        if ($answer4 !== null) {
            $updateFields[] = "option4 = ?";
            $params[] = $answer4;
            $paramTypes .= "s";
        }
        if ($correctAnswer !== null) {
            $updateFields[] = "correct_answer = ?";
            $params[] = $correctAnswer;
            $paramTypes .= "i";
        }

        $params[] = $id;
        $paramTypes .= "i";

        $updateQuery = "UPDATE questions SET " . implode(", ", $updateFields) . " WHERE id = ?";

        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bind_param($paramTypes, ...$params);

        return $stmt->execute();
    }

    /**
     * Delete a single question
     */
    public function deleteQuestion($questionId) {
        $stmt = $this->conn->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        return $stmt->execute();
    }

    /**
     * Get all categories
     */
    public function getAllCategories() {
        $result = $this->conn->query("
            SELECT id, category_name, color
            FROM question_categories
            ORDER BY id ASC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Add a new category
     */
    public function addCategory($name, $color) {
        $stmt = $this->conn->prepare("
            INSERT INTO question_categories (category_name, color)
            VALUES (?, ?)
        ");
        $stmt->bind_param("ss", $name, $color);
        return $stmt->execute();
    }

    /**
     * Update a category
     */
    public function updateCategory($id, $name, $color) {
        $stmt = $this->conn->prepare("
            UPDATE question_categories
            SET category_name = ?, color = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $name, $color, $id);
        return $stmt->execute();
    }

    /**
     * Delete a category
     */
    public function deleteCategory($id) {
        // Riaassegna tutte le domande di questa categoria alla categoria di default (id=1)
        $updateStmt = $this->conn->prepare("
            UPDATE questions
            SET category_id = 1
            WHERE category_id = ?
        ");
        $updateStmt->bind_param("i", $id);
        $updateStmt->execute();

        // Poi elimina la categoria
        $stmt = $this->conn->prepare("
            DELETE FROM question_categories
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
