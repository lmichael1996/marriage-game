<?php
require_once __DIR__ . '/../repository/SetRepo.php';
require_once __DIR__ . '/../repository/RoundRepo.php';

/**
 * SetService - Gestisce la logica di business per i SET di domande
 */
class SetService {
    private $setRepo;
    private $roundRepo;

    public function __construct() {
        $this->setRepo = new SetRepo();
        $this->roundRepo = new RoundRepo();
    }

    /**
     * Get all question sets (paginated)
     */
    public function getAll($page = 1, $limit = 10) {
        $all = $this->setRepo->getAllSets();
        $total = count($all);

        return [
            'sets' => array_slice($all, ($page - 1) * $limit, $limit),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Search question sets (paginated)
     */
    public function search($searchTerm, $searchType = 'contains', $page = 1, $limit = 10) {
        $all = $this->setRepo->searchSets($searchTerm, $searchType);
        $total = count($all);

        return [
            'sets' => array_slice($all, ($page - 1) * $limit, $limit),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get question set by ID
     */
    public function getById($setId) {
        return $this->setRepo->getSetById($setId);
    }

    /**
     * Add new question set
     */
    public function add($setName, $setDescription = '') {
        if (empty($setName)) {
            throw new Exception("Set name is required");
        }

        $setId = $this->setRepo->createSet($setName, $setDescription);
        if (!$setId) {
            throw new Exception("Failed to create question set");
        }

        return $setId;
    }

    /**
     * Update question set
     */
    public function update($setId, $setName, $setDescription = '') {
        if (empty($setId) || empty($setName)) {
            throw new Exception("Set ID and name are required");
        }

        $success = $this->setRepo->updateSet($setId, $setName, $setDescription);
        if (!$success) {
            throw new Exception("Failed to update question set");
        }

        return $success;
    }

    /**
     * Delete question set
     */
    public function delete($setId) {
        if (empty($setId)) {
            throw new Exception("Set ID is required");
        }

        $success = $this->setRepo->deleteSet($setId);
        if (!$success) {
            throw new Exception("Failed to delete question set");
        }

        return $success;
    }

    /**
     * Get questions in a set
     */
    public function getQuestions($setId) {
        return $this->setRepo->getQuestions($setId);
    }

    /**
     * Add question to set
     */
    public function addQuestionToSet($setId, $questionId, $orderInSet = 0) {
        if (empty($setId) || empty($questionId)) {
            throw new Exception("Set ID and question ID are required");
        }

        return $this->setRepo->addQuestionToSet($setId, $questionId, $orderInSet);
    }

    /**
     * Add question at specific position
     */
    public function addQuestionAtPosition($setId, $questionId, $position) {
        if (empty($setId) || empty($questionId) || empty($position)) {
            throw new Exception("Set ID, question ID, and position are required");
        }

        return $this->setRepo->insertQuestionAt($setId, $questionId, $position);
    }

    /**
     * Update questions order in set
     */
    public function updateQuestionsOrder($setId, $questions) {
        if (empty($setId) || empty($questions)) {
            throw new Exception("Set ID and questions are required");
        }

        return $this->setRepo->reorderQuestions($setId, $questions);
    }

    /**
     * Remove question from set
     */
    public function removeQuestion($setId, $questionId) {
        if (empty($setId) || empty($questionId)) {
            throw new Exception("Set ID and question ID are required");
        }

        return $this->setRepo->removeQuestionFromSet($setId, $questionId);
    }

    /**
     * Move question up in set
     */
    public function moveQuestionUp($setId, $questionId) {
        if (empty($setId) || empty($questionId)) {
            throw new Exception("Set ID and question ID are required");
        }

        return $this->setRepo->moveQuestionUp($setId, $questionId);
    }
}
?>
