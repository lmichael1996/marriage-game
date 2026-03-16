<?php

/**
 * Handles question-set API endpoints (CRUD, questions in set, ordering).
 */
class SetController
{
    public function getQuestionSet(): void
    {
        $setId = $_GET['id'] ?? null;
        if (!$setId) Router::error('ID set obbligatorio');

        $set = Container::set()->getQuestionSetById($setId);
        if (!$set) Router::error('Set di domande non trovato', 404);

        Router::respond([
            'success' => true,
            'set'     => $set,
        ]);
    }

    public function addQuestionSet(): void
    {
        $data    = Router::input();
        $setName = $data['set_name'] ?? '';
        if (!$setName) Router::error('Nome set obbligatorio');

        $result = Container::set()->addSet($setName, $data['set_description'] ?? '');
        if (is_array($result)) {
            Router::error($result['error'] ?? 'Impossibile creare il set');
        }
        Router::respond([
            'success' => true,
            'set_id'  => $result,
            'message' => 'Set di domande creato',
        ]);
    }

    public function updateQuestionSet(): void
    {
        $data = Router::input();
        if (!($data['set_id'] ?? null) || !($data['set_name'] ?? '')) {
            Router::error('ID set e nome obbligatori');
        }
        $result = Container::set()->updateSet($data['set_id'], $data['set_name'], $data['set_description'] ?? '');
        if (!($result['success'] ?? false)) {
            Router::error($result['error'] ?? 'Impossibile aggiornare il set');
        }
        Router::respond([
            'success' => true,
            'message' => 'Set di domande aggiornato',
        ]);
    }

    public function deleteQuestionSet(): void
    {
        $data = Router::input();
        if (!($data['set_id'] ?? null)) Router::error('ID set obbligatorio');

        $result = Container::set()->deleteSet($data['set_id']);
        if (!($result['success'] ?? false)) {
            Router::error($result['error'] ?? 'Impossibile eliminare il set', 500);
        }
        Router::respond([
            'success' => true,
            'message' => 'Set di domande eliminato',
        ]);
    }

    public function getSetQuestions(): void
    {
        $setId = $_GET['set_id'] ?? null;
        if (!$setId) Router::error('ID set obbligatorio');

        Router::respond([
            'success'   => true,
            'questions' => Container::set()->getSetQuestions($setId),
        ]);
    }

    public function addQuestionToSet(): void
    {
        $data = Router::input();
        if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) {
            Router::error('ID set e ID domanda obbligatori');
        }

        $q         = Container::set();
        $questions = $q->getSetQuestions($data['set_id']);
        $maxOrder  = 0;
        foreach ($questions as $qItem) {
            if ($qItem['order_in_set'] > $maxOrder) $maxOrder = $qItem['order_in_set'];
            if ($qItem['id'] == $data['question_id']) Router::error('Domanda già presente nel set');
        }

        if (!$q->addQuestionToSet($data['set_id'], $data['question_id'], $maxOrder + 1)) {
            Router::error('Domanda già presente nel set o impossibile aggiungerla');
        }
        Router::respond([
            'success' => true,
            'message' => 'Domanda aggiunta',
        ]);
    }

    public function removeQuestionFromSet(): void
    {
        $data = Router::input();
        if (!($data['set_id'] ?? null) || !($data['question_id'] ?? null)) {
            Router::error('ID set e ID domanda obbligatori');
        }
        Container::set()->removeQuestion($data['set_id'], $data['question_id']);
        Router::respond([
            'success' => true,
            'message' => 'Domanda rimossa',
        ]);
    }

    public function updateQuestionOrder(): void
    {
        $data = Router::input();
        if (!($data['set_id'] ?? null) || empty($data['questions'])) {
            Router::error('ID set e domande obbligatori');
        }
        if (!Container::set()->updateQuestionsOrder($data['set_id'], $data['questions'])) {
            Router::error('Impossibile aggiornare l\'ordine');
        }
        Router::respond([
            'success' => true,
            'message' => 'Ordine aggiornato',
        ]);
    }
}
