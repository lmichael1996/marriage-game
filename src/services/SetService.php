<?php

/**
 * Handles business logic for question sets and their questions.
 */
class SetService {

    public function __construct(
        private SetRepo $setRepo
    ) {}

    /**
     * Get a question by its position (order_in_set) within a set.
     *
     * @param int $qsetId   Question set ID
     * @param int $counter  Position (1-based)
     * @return array|null  Question data or null
     */
    public function getQuestionByCounter(int $qsetId, int $counter): ?array {
        return $this->setRepo->getQuestionAtPosition($qsetId, $counter);
    }

    /**
     * Get the total number of questions in a set.
     *
     * @param int $qsetId  Question set ID
     * @return int  Question count
     */
    public function getQuestionCountByQset(int $qsetId): int {
        return $this->setRepo->countQuestions($qsetId);
    }

    /**
     * Get all question sets with pagination.
     *
     * @param int $page   Page number
     * @param int $limit  Items per page
     * @return array  Paginated result
     */
    public function getAllQuestionSets(int $page = 1, int $limit = 10): array {
        $all = $this->setRepo->getAllSets();
        $total = count($all);

        return [
            'sets'       => array_slice($all, ($page - 1) * $limit, $limit),
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    /**
     * Search question sets.
     *
     * @param string $searchTerm  Search term
     * @param string $searchType  Search type (contains, exact, etc.)
     * @param int    $page        Page number
     * @param int    $limit       Items per page
     * @return array  Paginated result
     */
    public function searchSets(string $searchTerm, string $searchType = 'contains', int $page = 1, int $limit = 10): array {
        $all = $this->setRepo->searchSets($searchTerm, $searchType);
        $total = count($all);

        return [
            'sets'       => array_slice($all, ($page - 1) * $limit, $limit),
            'total'      => $total,
            'page'       => $page,
            'limit'      => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    /**
     * Get a question set by ID.
     *
     * @param int $setId  Set ID
     * @return array|null  Set data or null
     */
    public function getQuestionSetById(int $setId): ?array {
        return $this->setRepo->getSetById($setId);
    }

    /**
     * Add a new question set.
     *
     * @param string $name         Set name
     * @param string $description  Set description
     * @return int|array  New set ID, or error array
     */
    public function addSet(string $name, string $description = ''): int|array {
        $setId = $this->setRepo->createSet($name, $description);
        if (!$setId) {
            return [
                'success' => false,
                'error' => 'Impossibile creare il set di domande'
            ];
        }

        return $setId;
    }

    /**
     * Update a question set.
     *
     * @param int    $setId        Set ID
     * @param string $name         Set name
     * @param string $description  Set description
     * @return array  Result array
     */
    public function updateSet(int $setId, string $name, string $description = ''): array {
        $success = $this->setRepo->updateSet($setId, $name, $description);
        if (!$success) {
            return [
                'success' => false,
                'error' => 'Impossibile aggiornare il set di domande'
            ];
        }

        return ['success' => true];
    }

    /**
     * Delete a question set.
     *
     * @param int $setId  Set ID
     * @return array  Result array
     */
    public function deleteSet(int $setId): array {
        $success = $this->setRepo->deleteSet($setId);
        if (!$success) {
            return [
                'success' => false,
                'error' => 'Impossibile eliminare il set di domande'
            ];
        }

        return ['success' => true];
    }

    /**
     * Get questions in a set.
     *
     * @param int $setId  Set ID
     * @return array  List of questions
     */
    public function getSetQuestions(int $setId): array {
        return $this->setRepo->getQuestions($setId);
    }

    /**
     * Add a question to a set.
     *
     * @param int $setId       Set ID
     * @param int $questionId  Question ID
     * @param int $orderInSet  Position (0 = append)
     * @return bool
     */
    public function addQuestionToSet(int $setId, int $questionId, int $orderInSet = 0): bool {
        return $this->setRepo->addQuestionToSet($setId, $questionId, $orderInSet);
    }

    /**
     * Add a question to a set at a specific position.
     *
     * @param int $setId       Set ID
     * @param int $questionId  Question ID
     * @param int $position    Target position
     * @return bool
     */
    public function addQuestionAtPosition(int $setId, int $questionId, int $position): bool {
        return $this->setRepo->insertQuestionAt($setId, $questionId, $position);
    }

    /**
     * Reorder questions in a set.
     *
     * @param int   $setId      Set ID
     * @param array $questions  Ordered list of question IDs
     * @return bool
     */
    public function updateQuestionsOrder(int $setId, array $questions): bool {
        return $this->setRepo->reorderQuestions($setId, $questions);
    }

    /**
     * Remove a question from a set.
     *
     * @param int $setId       Set ID
     * @param int $questionId  Question ID
     * @return bool
     */
    public function removeQuestion(int $setId, int $questionId): bool {
        return $this->setRepo->removeQuestionFromSet($setId, $questionId);
    }
}
