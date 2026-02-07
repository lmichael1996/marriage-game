<?php
require_once __DIR__ . '/../repository/QuestionRepo.php';
require_once __DIR__ . '/../repository/RoundRepo.php';

/**
 * QuestionService - Gestisce la logica di business dei set di domande
 * Restituisce dati o lancia eccezioni, il controller gestisce gli errori
 */
class QuestionService {
    private $questionRepo;
    private $roundRepo;

    public function __construct() {
        $this->questionRepo = new QuestionRepo();
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
    public function validateQuestions($questions) {
        $errors = [];

        foreach ($questions as $index => $question) {
            $num = $index + 1;

            if (empty($question['question'])) {
                $errors[] = "Domanda $num: testo mancante";
            }

            if (empty($question['type'])) {
                $errors[] = "Domanda $num: tipo mancante";
            }

            if ($question['type'] === 'multiple') {
                if (empty($question['option1']) || empty($question['option2'])) {
                    $errors[] = "Domanda $num: servono almeno 2 opzioni";
                }

                if (empty($question['correct']) || $question['correct'] < 1 || $question['correct'] > 4) {
                    $errors[] = "Domanda $num: risposta corretta non valida";
                }
            }

            if ($question['type'] === 'truefalse') {
                if (empty($question['correct']) || !in_array($question['correct'], [1, 2])) {
                    $errors[] = "Domanda $num: risposta corretta deve essere 1 o 2";
                }
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode('; ', $errors));
        }

        return true;
    }

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
     * Search question sets
     * @return array
     */
    public function searchQuestionSets($query) {
        return $this->questionRepo->search($query);
    }

    /**
     * Get question set by ID
     * @return array|null
     */
    public function getQuestionSetById($id) {
        return $this->questionRepo->getById($id);
    }

    /**
     * Get rounds for a question set
     * @return array
     */
    public function getQuestionSetRounds($setId) {
        return $this->questionRepo->getRounds($setId);
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
}

