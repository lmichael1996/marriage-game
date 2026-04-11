<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for the questions and question_categories tables.
 *
 * Manages CRUD operations for quiz questions and their categories.
 * Question types: 'multiple' (4 options), 'truefalse' (true/false), 'clickfirst' (speed-based).
 * correct_answer is an int (1-4) for multiple/truefalse, NULL for clickfirst.
 */
class QuestionRepo {
    private mysqli $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Get a single question by ID.
     *
     * @param int $questionId The question ID
     * @return array|null Full question row or null if not found
     */
    public function getQuestionById(int $questionId): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $question = $result->fetch_assoc();
        $stmt->close();
        return $question ?: null;
    }

    /**
     * Get all questions joined with category info.
     *
     * @return array List of questions with category_name and color
     */
    public function getAll(): array {
        $result = $this->conn->query("
            SELECT q.*, c.category_name, c.color
            FROM questions q
            LEFT JOIN question_categories c ON q.category_id = c.id
            ORDER BY q.question ASC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Insert a new question.
     *
     * Normalizes fields based on question_type:
     * - multiple:   keeps all 4 options, correct_answer = 1-4
     * - truefalse:  clears options, correct_answer = 1 (true) or 2 (false)
     * - clickfirst: clears options, correct_answer = NULL
     *
     * @param array $questionData Associative array with question, question_type, answer1-4, correct_answer, timer, category_id
     * @return int|false The new question ID on success, false on failure
     */
    public function insertQuestion(array $questionData): int|false {
        $question = $questionData['question'] ?? '';

        if (empty($question)) {
            return false;
        }

        // Default values and normalization
        $roundType = $questionData['question_type'] ?? 'multiple';

        if ($roundType === 'multiple') {
            $answer1 = $questionData['answer1'] ?? '';
            $answer2 = $questionData['answer2'] ?? '';
            $answer3 = $questionData['answer3'] ?? '';
            $answer4 = $questionData['answer4'] ?? '';
        } else {
            $answer1 = '';
            $answer2 = '';
            $answer3 = '';
            $answer4 = '';
        }

        $correctAnswer = $roundType === 'clickfirst' ? null : intval($questionData['correct_answer'] ?? 1);
        $timer = intval($questionData['timer'] ?? 30);
        $categoryId = intval($questionData['category_id'] ?? 1);

        $imageUrl = $questionData['image_url'] ?? null;

        $stmt = $this->conn->prepare("
            INSERT INTO questions (question_type, question, option1, option2, option3, option4, correct_answer, timer, category_id, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssssiis",
            $roundType,
            $question,
            $answer1,
            $answer2,
            $answer3,
            $answer4,
            $correctAnswer,
            $timer,
            $categoryId,
            $imageUrl
        );

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }

        return false;
    }

    /**
     * Update an existing question.
     *
     * Normalizes fields based on question_type (same logic as insertQuestion).
     *
     * @param array $data Associative array with id, question, question_type, answer1-4, correct_answer, timer, category_id
     * @return bool True on success
     */
    public function updateQuestion(array $data): bool {
        $type = $data['question_type'] ?? 'multiple';

        if ($type === 'multiple') {
            $option1 = $data['answer1'] ?? '';
            $option2 = $data['answer2'] ?? '';
            $option3 = $data['answer3'] ?? '';
            $option4 = $data['answer4'] ?? '';
        } else {
            $option1 = $option2 = $option3 = $option4 = '';
        }

        $correctAnswer = $type === 'clickfirst' ? null : intval($data['correct_answer'] ?? 1);

        $imageUrl = $data['image_url'] ?? null;

        $stmt = $this->conn->prepare("
            UPDATE questions
            SET question = ?, question_type = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?,
                correct_answer = ?, timer = ?, category_id = ?, image_url = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssssiisi",
            $data['question'],
            $type,
            $option1,
            $option2,
            $option3,
            $option4,
            $correctAnswer,
            $data['timer'],
            $data['category_id'],
            $imageUrl,
            $data['id']
        );

        return $stmt->execute();
    }

    /**
     * Delete a question by ID.
     *
     * @param int $questionId The question ID to delete
     * @return bool True on success
     */
    public function deleteQuestion(int $questionId): bool {
        $stmt = $this->conn->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        return $stmt->execute();
    }

    /**
     * Get all question categories ordered by ID.
     *
     * @return array List of categories with id, category_name, color
     */
    public function getAllCategories(): array {
        $result = $this->conn->query("
            SELECT id, category_name, color
            FROM question_categories
            ORDER BY id ASC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Add a new question category.
     *
     * @param string $name  Category display name
     * @param string $color Hex color code (e.g. "#ff6b6b")
     * @return bool True on success
     */
    public function addCategory(string $name, string $color): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO question_categories (category_name, color)
            VALUES (?, ?)
        ");
        $stmt->bind_param("ss", $name, $color);
        return $stmt->execute();
    }

    /**
     * Update a category's name and color.
     *
     * @param int    $id    Category ID
     * @param string $name  New category name
     * @param string $color New hex color code
     * @return bool True on success
     */
    public function updateCategory(int $id, string $name, string $color): bool {
        $stmt = $this->conn->prepare("
            UPDATE question_categories
            SET category_name = ?, color = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $name, $color, $id);
        return $stmt->execute();
    }

    /**
     * Delete a category and reassign its questions to the default category (id=1).
     *
     * @param int $id Category ID to delete
     * @return bool True on success
     */
    public function deleteCategory(int $id): bool {
        // Reassign all questions from this category to default (id=1)
        $updateStmt = $this->conn->prepare("
            UPDATE questions
            SET category_id = 1
            WHERE category_id = ?
        ");
        $updateStmt->bind_param("i", $id);
        $updateStmt->execute();

        // Then delete the category
        $stmt = $this->conn->prepare("
            DELETE FROM question_categories
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
