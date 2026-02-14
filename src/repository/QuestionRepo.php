<?php
require_once __DIR__ . '/../config/database.php';

class QuestionRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function create($name, $description = '') {
        $stmt = $this->conn->prepare("INSERT INTO qsets (set_name, set_description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function getAll($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $result = $this->conn->query("
            SELECT qs.id,
                   qs.set_name,
                   qs.set_description,
                   qs.updated_at,
                   COUNT(qq.id) as total_rounds
            FROM qsets qs
            LEFT JOIN qset_questions qq ON qq.qset_id = qs.id
            GROUP BY qs.id, qs.set_name, qs.set_description, qs.updated_at
            ORDER BY qs.set_name ASC
            LIMIT $perPage OFFSET $offset
        ");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        return $data;
    }

    public function getTotalCount() {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM qsets");
        $row = $result->fetch_assoc();
        return (int)$row['total'];
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM qsets WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getQuestionById($questionId) {
        $stmt = $this->conn->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($id, $name, $description) {
        $stmt = $this->conn->prepare("UPDATE qsets SET set_name = ?, set_description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        // Delete all rounds in this set
        $stmt = $this->conn->prepare("DELETE FROM questions WHERE question_set_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Delete the set
        $stmt = $this->conn->prepare("DELETE FROM qsets WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function search($query) {
        $searchTerm = "%$query%";
        $stmt = $this->conn->prepare("
            SELECT qs.*,
                   COUNT(r.id) as total_rounds
            FROM qsets qs
            LEFT JOIN questions r ON r.question_set_id = qs.id
            WHERE qs.set_name LIKE ?
            GROUP BY qs.id, qs.set_name, qs.set_description, qs.updated_at
            ORDER BY qs.set_name ASC
        ");
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRounds($setId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM questions
            WHERE question_set_id = ?
            ORDER BY round_number ASC
        ");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Create a question set with multiple questions
     */
    public function createWithQuestions($setName, $setDescription, $questions) {
        // Create the question set
        $setId = $this->create($setName, $setDescription);

        if (!$setId) {
            return false;
        }

        // Add questions
        foreach ($questions as $i => $q) {
            $roundNumber = $i + 1;
            $question = $q['question'] ?? '';
            $type = $q['type'] ?? 'multiple';
            $timer = isset($q['timer']) ? intval($q['timer']) : 30;

            if (!$question) continue;

            $option1 = $q['option1'] ?? '';
            $option2 = $q['option2'] ?? '';
            $option3 = $q['option3'] ?? '';
            $option4 = $q['option4'] ?? '';
            $correct = isset($q['correct']) ? intval($q['correct']) : 1;

            // Auto-set options for true/false
            if ($type === 'truefalse') {
                $option1 = 'Vero';
                $option2 = 'Falso';
                $option3 = '';
                $option4 = '';
            }

            // For clickfirst, no correct answer needed and no timer
            if ($type === 'clickfirst') {
                $correct = null;
                $option1 = $option2 = $option3 = $option4 = '';
                $timer = null;
            }

            // Insert round
            $stmt = $this->conn->prepare("
                INSERT INTO questions (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iissssssii", $setId, $roundNumber, $type, $question, $option1, $option2, $option3, $option4, $correct, $timer);
            $stmt->execute();
            $stmt->close();
        }

        return $setId;
    }

    /**
     * Update a question set with questions
     */
    public function updateWithQuestions($setId, $setName, $setDescription, $questions) {
        // Update set name and description
        $this->update($setId, $setName, $setDescription);

        // Get existing rounds for this set
        $stmt = $this->conn->prepare("SELECT id, round_number FROM questions WHERE question_set_id = ? ORDER BY round_number");
        $stmt->bind_param("i", $setId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingRounds = [];
        while ($row = $result->fetch_assoc()) {
            $existingRounds[$row['round_number']] = $row['id'];
        }
        $stmt->close();

        // Update or insert questions
        foreach ($questions as $i => $q) {
            $roundNumber = $i + 1;
            $question = $q['question'] ?? '';
            $type = $q['type'] ?? 'multiple';
            $timer = isset($q['timer']) ? intval($q['timer']) : 30;

            if (!$question) continue;

            $option1 = $q['option1'] ?? '';
            $option2 = $q['option2'] ?? '';
            $option3 = $q['option3'] ?? '';
            $option4 = $q['option4'] ?? '';
            $correct = isset($q['correct']) ? intval($q['correct']) : 1;

            // Auto-set options for true/false
            if ($type === 'truefalse') {
                $option1 = 'Vero';
                $option2 = 'Falso';
                $option3 = '';
                $option4 = '';
            }

            // For clickfirst, no correct answer and no options needed
            if ($type === 'clickfirst') {
                $correct = null;
                $option1 = $option2 = $option3 = $option4 = '';
                // timer is kept as-is from the input
            }

            // Update existing round or insert new one
            if (isset($existingRounds[$roundNumber])) {
                // Update existing round
                $stmt = $this->conn->prepare("
                    UPDATE questions
                    SET round_type = ?, question = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_answer = ?, timer = ?
                    WHERE id = ?
                ");
                $roundId = $existingRounds[$roundNumber];
                $stmt->bind_param("sssssssii", $type, $question, $option1, $option2, $option3, $option4, $correct, $timer, $roundId);
                $stmt->execute();
                $stmt->close();
                unset($existingRounds[$roundNumber]);
            } else {
                // Insert new round
                $stmt = $this->conn->prepare("
                    INSERT INTO questions (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iissssssii", $setId, $roundNumber, $type, $question, $option1, $option2, $option3, $option4, $correct, $timer);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Delete remaining rounds
        foreach ($existingRounds as $roundId) {
            $stmt = $this->conn->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->bind_param("i", $roundId);
            $stmt->execute();
            $stmt->close();
        }

        return true;
    }

    /**
     * Get all individual questions with pagination
     */
    public function getAllQuestions($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $result = $this->conn->query("
            SELECT q.id, q.round_type, q.question, q.option1, q.option2, q.option3, q.option4, q.correct_answer, q.timer, q.category_id,
                   c.category_name, c.color
            FROM questions q
            LEFT JOIN question_categories c ON q.category_id = c.id
            ORDER BY q.question ASC
            LIMIT $perPage OFFSET $offset
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get total count of questions
     */
    public function getTotalQuestionsCount() {
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
        $answer1 = $data['answer1'] ?? '';
        $answer2 = $data['answer2'] ?? '';
        $answer3 = $data['answer3'] ?? '';
        $answer4 = $data['answer4'] ?? '';
        $correctAnswer = intval($data['correct_answer'] ?? 1);

        $stmt = $this->conn->prepare("
            UPDATE questions
            SET question = ?, round_type = ?, category_id = ?, timer = ?,
                option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_answer = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssiissssii",
            $question, $type, $categoryId, $timer,
            $answer1, $answer2, $answer3, $answer4, $correctAnswer, $id
        );

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
}

