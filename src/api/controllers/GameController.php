<?php

/**
 * Handles game API endpoints (rounds, answers, leaderboard, progress).
 *
 * The 'game' endpoint uses a sub-action via $_GET['action'].
 * The router dispatches to gameAction(), which further routes internally.
 */
class GameController
{
    // ── Sub-router for game?action=... ───────────────────────────────────

    public function gameAction(): void
    {
        $action = $_GET['action'] ?? '';
        match ($action) {
            'get_game_state'        => $this->getGameState(),
            'start_round'           => $this->startRound(),
            'close_round'           => $this->closeRound(),
            'skip_round'            => $this->skipRound(),
            'set_clickfirst_winner' => $this->setClickfirstWinner(),
            'judge_advance'         => $this->judgeAdvance(),
            'check_judge_decision'  => $this->checkJudgeDecision(),
            'check_winner'          => $this->checkWinner(),
            'close_game'            => $this->closeGame(),
            default                 => Router::error('Azione non valida'),
        };
    }

    // ── Answer submission ────────────────────────────────────────────────

    public function answer(): void
    {
        $data = Router::input();

        if (!($data['round_id'] ?? 0))     Router::error('ID round obbligatorio');
        if (($data['answer'] ?? '') === '') Router::error('Risposta obbligatoria');

        $result = Container::game()->submitAnswerByRoundId($data['round_id'], $data['answer'], $data['time_taken'] ?? 0);
        if (!($result['success'] ?? true)) {
            Router::error($result['error'] ?? 'Impossibile inviare la risposta', 500);
        }
        Router::respond($result);
    }

    // ── Round answers & leaderboard ──────────────────────────────────────

    public function roundAnswers(): void
    {
        $roundId = $_GET['round_id'] ?? 0;
        if (!$roundId) {
            Router::respond([
                'success'     => false,
                'top_answers' => [],
                'message'     => 'ID round obbligatorio',
            ]);
        }
        Router::respond([
            'success'     => true,
            'top_answers' => Container::game()->getTopAnswers($roundId, 10),
        ]);
    }

    public function finalLeaderboard(): void
    {
        $roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : authRoomId();
        if (!$roomId) {
            Router::respond(['success' => false, 'leaderboard' => []]);
        }
        Router::respond(Container::game()->getFinalLeaderboard($roomId));
    }

    public function playerProgress(): void
    {
        $result = Container::game()->getPlayerProgress();
        if (is_array($result)) {
            Router::error($result['error'] ?? 'Impossibile ottenere il progresso', 500);
        }
        Router::respond([
            'success'  => true,
            'answered' => $result,
        ]);
    }

    // ── Game state & rounds (sub-actions) ────────────────────────────────

    private function getGameState(): void
    {
        $roomId = authRoomId();
        if (!$roomId) Router::respond(['success' => false]);

        $room     = Container::room();
        $roomData = $room->getRoomDetails($roomId);
        if (!$roomData) Router::respond(['success' => false]);

        $statusRoom = $roomData['status_room'] ?? null;

        // If the room is closed or has a ranking, notify the player immediately
        if ($statusRoom === 'closed' || ($roomData['has_ranking'] ?? false)) {
            if (!($roomData['has_ranking'] ?? false)) {
                $room->markFinalRanking($roomData['id']);
            }

            $placement = 0;
            $playerId  = authPlayerId();
            if ($playerId) {
                $placement = $room->getPlacement($roomData['id'], $playerId);
            }

            Router::respond([
                'success'       => false,
                'game_finished' => true,
                'status_room'   => $statusRoom,
                'placement'     => $placement,
            ]);
        }

        if ($statusRoom === 'cancelled') {
            Router::respond([
                'success'     => false,
                'status_room' => 'cancelled',
            ]);
        }

        $counter  = max(1, intval($_GET['counter'] ?? 1));
        $result   = $room->getRoundByPosition($roomData['id'], $counter);

        if ($result) {
            $playerId       = authPlayerId();
            $alreadyAnswered = $playerId
                ? Container::game()->hasAnswered($playerId, (int)$result['id'])
                : false;

            $response = array_merge($result, [
                'success'          => true,
                'status_room'      => $statusRoom,
                'already_answered' => $alreadyAnswered,
            ]);
        } else {
            $response = ['success' => false, 'status_room' => $statusRoom];
        }

        Router::respond($response);
    }

    private function startRound(): void
    {
        $questionId = $_GET['question_id'] ?? 0;
        if (!$questionId && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $questionId = Router::input()['question_id'] ?? 0;
        }
        if (!$questionId) Router::error('ID domanda obbligatorio');

        $roomId = authRoomId();
        if (!$roomId) Router::error('ID stanza non trovato');

        $result = Container::game()->startRound($questionId, $roomId);
        if (!($result['success'] ?? true)) {
            Router::error($result['error'] ?? 'Impossibile avviare il round', 500);
        }
        Router::respond($result);
    }

    private function closeRound(): void
    {
        $roundId = $_GET['round_id'] ?? 0;
        if (!$roundId && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $roundId = Router::input()['round_id'] ?? 0;
        }
        if (!$roundId) Router::error('ID round obbligatorio');

        $result = Container::game()->closeRound($roundId);
        if (!($result['success'] ?? true)) {
            Router::error($result['error'] ?? 'Impossibile chiudere il round', 500);
        }
        Router::respond($result);
    }

    private function skipRound(): void
    {
        $roundId = $_GET['round_id'] ?? 0;
        if (!$roundId && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $roundId = Router::input()['round_id'] ?? 0;
        }
        if (!$roundId) Router::error('ID round obbligatorio');

        $result = Container::game()->skipRound($roundId);
        if (!($result['success'] ?? true)) {
            Router::error($result['error'] ?? 'Impossibile saltare il round', 500);
        }
        Router::respond($result);
    }

    private function setClickfirstWinner(): void
    {
        $data        = Router::input();
        $roundId     = $data['round_id'] ?? 0;
        $winnerIndex = $data['winner_index'] ?? null;
        if (!$roundId)            Router::error('ID round obbligatorio');
        if ($winnerIndex === null) Router::error('Indice vincitore obbligatorio');

        $result = Container::game()->setClickfirstWinner($roundId, (int)$winnerIndex);
        if (!($result['success'] ?? true)) {
            Router::error($result['error'] ?? 'Impossibile impostare il vincitore', 500);
        }
        Router::respond($result);
    }

    private function judgeAdvance(): void
    {
        $data    = Router::input();
        $roundId = $data['round_id'] ?? 0;
        if (!$roundId) Router::error('ID round obbligatorio');

        Container::game()->markJudgeDecided($roundId);
        Router::respond([
            'success' => true,
            'message' => 'Avanzamento giudice segnalato',
        ]);
    }

    private function checkJudgeDecision(): void
    {
        $roundId = $_GET['round_id'] ?? 0;
        if (!$roundId) Router::error('ID round obbligatorio');

        Router::respond([
            'success'       => true,
            'judge_decided' => Container::game()->isJudgeDecided($roundId),
        ]);
    }

    private function checkWinner(): void
    {
        $roomId = authRoomId();
        $fail   = ['success' => false, 'placement' => 0];
        if (!$roomId) Router::respond($fail);

        $room     = Container::room();
        $roomData = $room->getRoomDetails($roomId);
        if (!$roomData) Router::respond($fail);

        // If the room is closed but there's no ranking yet, compute it now
        if (!($roomData['has_ranking'] ?? false) && ($roomData['status_room'] ?? '') === 'closed') {
            $room->markFinalRanking($roomData['id']);
        }

        $playerId = authPlayerId();
        if (!$playerId) Router::respond($fail);

        $placement = $room->getPlacement($roomData['id'], $playerId);

        if ($roomData['has_ranking'] ?? false) {
            Router::respond([
                'success'       => true,
                'game_finished' => true,
                'placement'     => $placement,
            ]);
        }
        Router::respond([
            'success'   => true,
            'placement' => $placement,
        ]);
    }

    private function closeGame(): void
    {
        $roomId = authRoomId();
        if (!$roomId) Router::error('Stanza non autorizzata', 403);

        $result = Container::room()->finishGame($roomId);
        Router::respond($result);
    }
}
