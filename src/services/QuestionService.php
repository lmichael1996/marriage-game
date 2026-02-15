<?php
require_once __DIR__ . '/../repository/QuestionRepo.php';
require_once __DIR__ . '/../repository/QuestionSetRepo.php';
require_once __DIR__ . '/../repository/RoundRepo.php';

/**
 * QuestionService - Gestisce la logica di business delle domande e set di domande
 * Unifica QuestionService e QuestionSetService
 * Restituisce dati o lancia eccezioni, il controller gestisce gli errori
 */
class QuestionService {
    private $questionRepo;
    private $questionSetRepo;
    private $roundRepo;

    public function __construct() {
        $this->questionRepo = new QuestionRepo();
        $this->questionSetRepo = new QuestionSetRepo();
        $this->roundRepo = new RoundRepo();
    }

    /**
     * Recupera una domanda per ID
     */
    public function getQuestionById($questionId) {
        return $this->questionRepo->getQuestionById($questionId);
    }

    /**
     * Crea un nuovo set di domande con le sue domande
     * @return int Set ID
     * @throws Exception on failure
     */
    public function createQuestionSet($setName, $setDescription, $questions) {
        if (empty($setName)) {
            throw new Exception('Nome set obbligatorio');
        }

        if (empty($questions)) {
            throw new Exception('Almeno una domanda è obbligatoria');
        }

        $setId = $this->questionRepo->createWithQuestions($setName, $setDescription, $questions);

        if (!$setId) {
            throw new Exception('Errore nella creazione del set');
        }

        return $setId;
    }

    /**
     * Aggiorna un set di domande esistente
     * @return bool true on success
     * @throws Exception on failure
     */
    public function updateQuestionSet($setId, $setName, $setDescription, $questions) {
        if (empty($setId) || empty($setName)) {
            throw new Exception('ID e nome set sono obbligatori');
        }

        $success = $this->questionRepo->updateWithQuestions($setId, $setName, $setDescription, $questions);

        if (!$success) {
            throw new Exception('Errore nell\'aggiornamento del set');
        }

        return true;
    }

    /**
     * Elimina un set di domande
     * @return bool true on success
     * @throws Exception on failure
     */
    public function deleteQuestionSet($setId) {
        $success = $this->questionRepo->delete($setId);

        if (!$success) {
            throw new Exception('Errore nell\'eliminazione del set');
        }

        return true;
    }

    /**
     * Ottieni tutti i set di domande
     * @return array
     */
    public function getAllQuestionSets($page = 1, $perPage = 10) {
        return [
            'sets' => $this->questionRepo->getAll($page, $perPage),
            'total' => $this->questionRepo->getTotalCount(),
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($this->questionRepo->getTotalCount() / $perPage)
        ];
    }

    /**
     * Ottieni un set con tutte le sue domande
     * @return array|null
     */
    public function getQuestionSetWithQuestions($setId) {
        $set = $this->questionRepo->getById($setId);

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
     * Search question sets
     * @return array
     */
    public function getAllQuestions($page = 1, $perPage = 10) {
        return [
            'questions' => $this->questionRepo->getAllQuestions($page, $perPage),
            'total' => $this->questionRepo->getTotalQuestionsCount(),
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($this->questionRepo->getTotalQuestionsCount() / $perPage)
        ];
    }

    /**
     * Get question set by ID
     * @return array|null
     */
    public function getQuestionSetById($id) {
        return $this->questionRepo->getById($id);
    }

    /**
     * Get all categories
     * @return array
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
        $sets = $this->questionSetRepo->getAll($limit, $offset);
        $total = $this->questionSetRepo->getTotalCount();

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
        $sets = $this->questionSetRepo->search($searchTerm, $searchType, $limit, $offset);
        $total = $this->questionSetRepo->countSearch($searchTerm, $searchType);

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
        return $this->questionSetRepo->getById($setId);
    }

    /**
     * Add new question set
     */
    public function add($setName, $setDescription = '') {
        if (empty($setName)) {
            throw new Exception("Set name is required");
        }

        $setId = $this->questionSetRepo->add($setName, $setDescription);
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

        $success = $this->questionSetRepo->update($setId, $setName, $setDescription);
        if (!$success) {
            throw new Exception("Failed to update question set");
        }

        return $success;
    }

    /**
     * Delete question set
     */
    public function delete($setId) {
        $success = $this->questionSetRepo->delete($setId);
        if (!$success) {
            throw new Exception("Failed to delete question set");
        }

        return $success;
    }

    /**
     * Set the is_saved flag for a question set
     */
    public function setSaved($setId, $isSaved) {
        $success = $this->questionSetRepo->setSaved($setId, $isSaved);
        if (!$success) {
            throw new Exception("Failed to update question set saved status");
        }

        return $success;
    }

    /**
     * Get questions in set
     */
    public function getQuestions($setId) {
        return $this->questionSetRepo->getQuestions($setId);
    }

    /**
     * Add question to set (from QuestionSetService)
     */
    public function addQuestionToSet($setId, $questionId, $orderInSet = 0) {
        return $this->questionSetRepo->addQuestion($setId, $questionId, $orderInSet);
    }

    /**
     * Add question to set at a specific position
     */
    public function addQuestionAtPosition($setId, $questionId, $position) {
        return $this->questionSetRepo->addQuestionAtPosition($setId, $questionId, $position);
    }

    /**
     * Update questions order
     */
    public function updateQuestionsOrder($setId, $questions) {
        return $this->questionSetRepo->updateQuestionsOrder($setId, $questions);
    }

    /**
     * Remove question from set
     */
    public function removeQuestion($setId, $questionId) {
        return $this->questionSetRepo->removeQuestion($setId, $questionId);
    }

    /**
     * Move question up in set
     */
    public function moveQuestionUp($setId, $questionId) {
        return $this->questionSetRepo->moveQuestionUp($setId, $questionId);
    }

    /**
     * Move question down in set
     */
    public function moveQuestionDown($setId, $questionId) {
        return $this->questionSetRepo->moveQuestionDown($setId, $questionId);
    }
}

