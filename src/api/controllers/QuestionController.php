<?php

/**
 * Handles question and category API endpoints.
 */
class QuestionController
{
    public function getQuestion(): void
    {
        $id = $_GET['id'] ?? null;
        if (!$id) Router::error('ID domanda obbligatorio');

        $result = Container::question()->getQuestionById($id);
        if (!$result) Router::error('Domanda non trovata', 404);

        Router::respond([
            'success'  => true,
            'question' => $result,
        ]);
    }

    public function getQuestions(): void
    {
        Router::respond([
            'success'   => true,
            'questions' => Container::question()->getAllQuestions(1, 1000)['questions'],
        ]);
    }

    public function getCategories(): void
    {
        Router::respond([
            'success'    => true,
            'categories' => Container::question()->getAllCategories(),
        ]);
    }

    public function saveCategories(): void
    {
        $data = Router::input();
        $q    = Container::question();

        foreach ($data['deleted'] ?? [] as $id) {
            $q->deleteCategory($id);
        }
        foreach ($data['updated'] ?? [] as $cat) {
            if (isset($cat['id'], $cat['name'], $cat['color'])) {
                $q->updateCategory($cat['id'], $cat['name'], $cat['color']);
            }
        }
        foreach ($data['added'] ?? [] as $cat) {
            if (isset($cat['name'], $cat['color'])) {
                $q->addCategory($cat['name'], $cat['color']);
            }
        }

        Router::respond([
            'success' => true,
            'message' => 'Modifiche salvate',
        ]);
    }
}
