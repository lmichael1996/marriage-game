<?php

/**
 * Handles business logic for questions and categories.
 */
class QuestionService {

    public function __construct(
        private QuestionRepo $questionRepo
    ) {}

    // ── Questions ────────────────────────────────────────────────────────

    /**
     * Get a question by ID.
     *
     * @param int $questionId  Question ID
     * @return array|null  Question data or null
     */
    public function getQuestionById(int $questionId): ?array {
        return $this->questionRepo->getQuestionById($questionId);
    }

    /**
     * Get all questions with pagination.
     *
     * @param int $page     Page number
     * @param int $perPage  Items per page
     * @return array  Paginated result
     */
    public function getAllQuestions(int $page = 1, int $perPage = 10): array {
        $all = $this->questionRepo->getAll();
        $total = count($all);

        return [
            'questions'  => array_slice($all, ($page - 1) * $perPage, $perPage),
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * Add a single question.
     *
     * @param array $questionData  Question data (must include 'question' key)
     * @return array  ['success' => true, 'question_id' => int] or error array
     */
    public function addQuestion(array $questionData): array {
        $questionId = $this->questionRepo->insertQuestion($questionData);
        if (!$questionId) {
            return [
                'success' => false,
                'error' => 'Impossibile aggiungere la domanda'
            ];
        }

        return [
            'success' => true,
            'question_id' => $questionId
        ];
    }

    /**
     * Update a question.
     *
     * @param array $questionData  Must include 'id' and 'question'
     * @return array  Result
     */
    public function updateQuestion(array $questionData): array {
        if (!isset($questionData['id'])) {
            return [
                'success' => false,
                'error' => 'ID domanda obbligatorio'
            ];
        }

        $success = $this->questionRepo->updateQuestion($questionData);
        if (!$success) {
            return [
                'success' => false,
                'error' => 'Impossibile aggiornare la domanda'
            ];
        }

        return ['success' => true];
    }

    /**
     * Delete a question.
     *
     * @param int $questionId  Question ID
     * @return array  Result
     */
    public function deleteQuestion(int $questionId): array {
        $success = $this->questionRepo->deleteQuestion($questionId);
        if (!$success) {
            return [
                'success' => false,
                'error' => 'Impossibile eliminare la domanda'
            ];
        }

        return ['success' => true];
    }

    // ── Categories ───────────────────────────────────────────────────────

    /**
     * Get all categories.
     *
     * @return array  List of categories
     */
    public function getAllCategories(): array {
        return $this->questionRepo->getAllCategories();
    }

    /**
     * Add a new category.
     *
     * @param string $name   Category name
     * @param string $color  Category color
     * @return bool
     */
    public function addCategory(string $name, string $color): bool {
        return $this->questionRepo->addCategory($name, $color);
    }

    /**
     * Update a category.
     *
     * @param int    $id     Category ID
     * @param string $name   Category name
     * @param string $color  Category color
     * @return bool
     */
    public function updateCategory(int $id, string $name, string $color): bool {
        return $this->questionRepo->updateCategory($id, $name, $color);
    }

    /**
     * Delete a category.
     *
     * @param int $id  Category ID
     * @return bool
     */
    public function deleteCategory(int $id): bool {
        return $this->questionRepo->deleteCategory($id);
    }
}
