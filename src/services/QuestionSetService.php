<?php
require_once __DIR__ . '/../repository/QuestionSetRepo.php';

class QuestionSetService {
    private $repo;

    public function __construct() {
        $this->repo = new QuestionSetRepo();
    }

    /**
     * Get all question sets with pagination
     */
    public function getAll($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $sets = $this->repo->getAll($limit, $offset);
        $total = $this->repo->getTotalCount();

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
        $sets = $this->repo->search($searchTerm, $searchType, $limit, $offset);
        $total = $this->repo->countSearch($searchTerm, $searchType);

        return [
            'sets' => $sets,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    /**
     * Get question set by ID
     */
    public function getById($setId) {
        return $this->repo->getById($setId);
    }

    /**
     * Add new question set
     */
    public function add($setName, $setDescription = '') {
        if (empty($setName)) {
            throw new Exception("Set name is required");
        }

        $setId = $this->repo->add($setName, $setDescription);
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

        $success = $this->repo->update($setId, $setName, $setDescription);
        if (!$success) {
            throw new Exception("Failed to update question set");
        }

        return $success;
    }

    /**
     * Delete question set
     */
    public function delete($setId) {
        $success = $this->repo->delete($setId);
        if (!$success) {
            throw new Exception("Failed to delete question set");
        }

        return $success;
    }

    /**
     * Get questions in set
     */
    public function getQuestions($setId) {
        return $this->repo->getQuestions($setId);
    }

    /**
     * Add question to set
     */
    public function addQuestion($setId, $questionId, $orderInSet = 0) {
        return $this->repo->addQuestion($setId, $questionId, $orderInSet);
    }

    /**
     * Remove question from set
     */
    public function removeQuestion($setId, $questionId) {
        return $this->repo->removeQuestion($setId, $questionId);
    }
}
