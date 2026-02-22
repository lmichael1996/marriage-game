<?php
require_once __DIR__ . '/../repository/SetRepo.php';
require_once __DIR__ . '/../repository/QuestionRepo.php';
require_once __DIR__ . '/../repository/RoundRepo.php';

/**
 * QuestionService - Gestisce la logica di business delle domande e set di domande
 * Unifica QuestionService e QuestionSetService
 * Restituisce dati o lancia eccezioni, il controller gestisce gli errori
 */
class QuestionService {
    private $questionRepo;
    private $setRepo;
    private $roundRepo;

    public function __construct() {
        $this->questionRepo = new QuestionRepo();
        $this->setRepo = new SetRepo();
        $this->roundRepo = new RoundRepo();
    }

    /**
     * Recupera una domanda per ID
     */
    public function getQuestionById($questionId) {
        return $this->questionRepo->getQuestionById($questionId);
    }

    /**
     * Get question by counter (order_in_set) for a specific question set
     */
    public function getQuestionByCounter($qsetId, $counter) {
        return $this->setRepo->getQuestionByCounter($qsetId, $counter);
    }

    /**
     * Get total count of questions in a specific question set
     */
    public function getQuestionCountByQset($qsetId) {
        return $this->setRepo->getQuestionCountByQset($qsetId);
    }

    /**
     * Get all individual questions with pagination
     * @return array
     */
    public function getAllQuestions($page = 1, $perPage = 10) {
        return [
            'questions' => $this->questionRepo->getAll($page, $perPage),
            'total' => $this->questionRepo->getTotalCount(),
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($this->questionRepo->getTotalCount() / $perPage)
        ];
    }

    /**
     * Get all question sets (alias for getAll)
     * @return array
     */
    public function getAllQuestionSets($page = 1, $perPage = 10) {
        return $this->getAll($page, $perPage);
    }

    /**
     * Get question set by ID (alias for getById)
     * @return array|null
     */
    public function getQuestionSetById($setId) {
        return $this->getById($setId);
    }

    /**
     * Ottieni un set con tutte le sue domande
     * @return array|null
     */
    public function getQuestionSetWithQuestions($setId) {
        $set = $this->setRepo->getById($setId);

        if (!$set) {
            return null;
        }

        $set['questions'] = $this->roundRepo->getRoundsByQuestionSet($setId);
        $set['question_count'] = count($set['questions']);

        return $set;
    }

    /**
     * Valida le domande prima di salvarle
     * @throws Exception with validation errors
     */
    /**
     * Add a single question to a set
     * @return bool true on success
     * @throws Exception on failure
     */
    public function addQuestion($questionData) {
        // Se è un array con question, è il nuovo metodo
        if (is_array($questionData) && isset($questionData['question'])) {
            $questionId = $this->questionRepo->insertQuestion($questionData);

            if (!$questionId) {
                throw new Exception('Errore nell\'aggiunta della domanda');
            }

            return ['success' => true, 'question_id' => $questionId];
        }

        throw new Exception('Dati incompleti');
    }

    /**
     * Update a single question
     * @return array with success and error keys
     */
    public function updateQuestion($questionData) {
        try {
            if (!isset($questionData['id'])) {
                return ['success' => false, 'error' => 'ID domanda mancante'];
            }

            if (empty($questionData['question'])) {
                return ['success' => false, 'error' => 'Testo domanda obbligatorio'];
            }

            $success = $this->questionRepo->updateQuestion($questionData);

            if (!$success) {
                return ['success' => false, 'error' => 'Errore nell\'aggiornamento della domanda'];
            }

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a single question
     * @return array with success and error keys
     */
    public function deleteQuestion($questionId) {
        try {
            if (empty($questionId)) {
                return ['success' => false, 'error' => 'ID domanda mancante'];
            }

            $success = $this->questionRepo->deleteQuestion($questionId);

            if (!$success) {
                return ['success' => false, 'error' => 'Errore nell\'eliminazione della domanda'];
            }

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all categories
     */
    public function getAllCategories() {
        return $this->questionRepo->getAllCategories();
    }

    /**
     * Add a new category
     * @return bool
     */
    /**
     * Add a category
     * @return bool
     */
    public function addCategory($name, $color) {
        if (empty($name) || empty($color)) {
            throw new Exception('Nome e colore obbligatori');
        }
        return $this->questionRepo->addCategory($name, $color);
    }

    /**
     * Update a category
     * @return bool
     */
    public function updateCategory($id, $name, $color) {
        if (empty($id) || empty($name) || empty($color)) {
            throw new Exception('ID, nome e colore obbligatori');
        }
        return $this->questionRepo->updateCategory($id, $name, $color);
    }

    /**
     * Delete a category
     * @return bool
     */
    public function deleteCategory($id) {
        if (empty($id)) {
            throw new Exception('ID obbligatorio');
        }
        return $this->questionRepo->deleteCategory($id);
    }

    // ===== METODI DA QuestionSetService =====

    /**
     * Get all question sets with pagination
     */
    public function getAll($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $sets = $this->setRepo->getAll($limit, $offset);
        $total = $this->setRepo->getTotalCount();

        return [
            'sets' => $sets,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    /**
     * Search question sets
     */
    public function search($searchTerm, $searchType = 'contains', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $sets = $this->setRepo->search($searchTerm, $searchType, $limit, $offset);
        $total = $this->setRepo->countSearch($searchTerm, $searchType);

        return [
            'sets' => $sets,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    /**
     * Get question set by ID (alias)
     */
    public function getById($setId) {
        return $this->setRepo->getById($setId);
    }

    /**
     * Add new question set
     */
    public function add($setName, $setDescription = '') {
        if (empty($setName)) {
            throw new Exception("Set name is required");
        }

        $setId = $this->setRepo->add($setName, $setDescription);
        if (!$setId) {
            throw new Exception("Failed to create question set");
        }

        return $setId;
    }

    /**
     * Update question set
     */
    public function update($setId, $setName, $setDescription = '') {
        if (empty($setName)) {
            throw new Exception("Set name is required");
        }

        $success = $this->setRepo->update($setId, $setName, $setDescription);
        if (!$success) {
            throw new Exception("Failed to update question set");
        }

        return $success;
    }

    /**
     * Delete question set
     */
    public function delete($setId) {
        $success = $this->setRepo->delete($setId);
        if (!$success) {
            throw new Exception("Failed to delete question set");
        }

        return $success;
    }

    /**
     * Set the is_saved flag for a question set
     */
    public function setSaved($setId, $isSaved) {
        $success = $this->setRepo->setSaved($setId, $isSaved);
        if (!$success) {
            throw new Exception("Failed to update question set saved status");
        }

        return $success;
    }

    /**
     * Get questions in set
     */
    public function getQuestions($setId) {
        return $this->setRepo->getQuestions($setId);
    }

    /**
     * Add question to set (from QuestionSetService)
     */
    public function addQuestionToSet($setId, $questionId, $orderInSet = 0) {
        return $this->setRepo->addQuestion($setId, $questionId, $orderInSet);
    }

    /**
     * Add question to set at a specific position
     */
    public function addQuestionAtPosition($setId, $questionId, $position) {
        return $this->setRepo->addQuestionAtPosition($setId, $questionId, $position);
    }

    /**
     * Update questions order
     */
    public function updateQuestionsOrder($setId, $questions) {
        return $this->setRepo->updateQuestionsOrder($setId, $questions);
    }

    /**
     * Remove question from set
     */
    public function removeQuestion($setId, $questionId) {
        return $this->setRepo->removeQuestion($setId, $questionId);
    }

    /**
     * Move question up in set
     */
    public function moveQuestionUp($setId, $questionId) {
        return $this->setRepo->moveQuestionUp($setId, $questionId);
    }

    /**
     * Move question down in set
     */
    public function moveQuestionDown($setId, $questionId) {
        return $this->setRepo->moveQuestionDown($setId, $questionId);
    }
}

