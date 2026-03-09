<?php

/**
 * Handles game business logic (rounds, answers, leaderboard).
 */
class GameService {

    public function __construct(
        private RoundRepo $roundRepo,
        private AnswerRepo $answerRepo,
        private RoomRepo $roomRepo,
        private QuestionService $questionService,
        private SettingsService $settingsService
    ) {}

    /**
     * Start a new round for a given question and room.
     *
     * @param int $questionId  Question ID
     * @param int $roomId      Room ID
     * @return array  Result with roundId on success
     */
    public function startRound(int $questionId, int $roomId): array {
        $question = $this->questionService->getQuestionById($questionId);
        if (!$question) {
            return [
                'success' => false,
                'error' => 'Domanda non trovata'
            ];
        }

        $roundId = $this->roundRepo->createRound($roomId, $questionId);
        if (!$roundId) {
            return [
                'success' => false,
                'error' => 'Impossibile creare il round'
            ];
        }

        return [
            'success'    => true,
            'message'    => 'Round started',
            'roundId'    => $roundId,
            'questionId' => $questionId
        ];
    }

    /**
     * Close a round: build ranking from top answers and save it.
     *
     * @param int $roundId  Round ID
     * @return array  Result with ranking and next round number
     */
    public function closeRound(int $roundId): array {
        $round = $this->roundRepo->getRoundById($roundId);
        if (!$round) {
            return [
                'success' => false,
                'error' => 'Round non trovato'
            ];
        }

        $topAnswers = $this->answerRepo->getTopFastestAnswers($roundId, 10);
        $roundType = $round['question_type'] ?? 'multiple';
        $isClickfirst = $roundType === 'clickfirst';

        // Pre-load all points for the round type in a single pass
        $pointsMap = $isClickfirst ? [] : $this->settingsService->getPointsMap($roundType);

        $ranking = [];
        foreach ($topAnswers as $i => $answer) {
            $ranking[] = [
                'username'    => $answer['username'],
                'player_id'   => (int)$answer['player_id'],
                'answer_time' => (float)$answer['answer_time'],
                'points'      => $isClickfirst
                    ? 0  // Points assigned later by the judge via setClickfirstWinner()
                    : ($pointsMap[$i + 1] ?? 0)
            ];
        }
        $this->roundRepo->saveRanking($roundId, $ranking);

        return [
            'success'     => true,
            'message'     => 'Round closed',
            'nextRound'   => ($round['round_number'] ?? 0) + 1,
            'top_answers' => $topAnswers
        ];
    }

    /**
     * Set the clickfirst winner: overwrite ranking with only the selected player.
     *
     * @param int $roundId      Round ID
     * @param int $winnerIndex  Index in the current ranking array
     * @return array  Result
     */
    public function setClickfirstWinner(int $roundId, int $winnerIndex): array {
        $round = $this->roundRepo->getRoundById($roundId);
        if (!$round) {
            return [
                'success' => false,
                'error' => 'Round non trovato'
            ];
        }

        $currentRanking = json_decode($round['ranking'] ?? '[]', true);
        if (!isset($currentRanking[$winnerIndex])) {
            return [
                'success' => false,
                'error' => 'Indice del vincitore non valido'
            ];
        }

        $winner = $currentRanking[$winnerIndex];
        $points = $this->settingsService->getClickfirstPoints();

        $newRanking = [[
            'username'    => $winner['username'],
            'player_id'   => (int)$winner['player_id'],
            'answer_time' => (float)$winner['answer_time'],
            'points'      => $points,
        ]];

        $this->roundRepo->saveRanking($roundId, $newRanking);

        return [
            'success' => true,
            'message' => 'Vincitore Clickfirst salvato'
        ];
    }

    /**
     * Mark that the judge has decided for this round (clickfirst).
     *
     * @param int $roundId  Round ID
     */
    public function markJudgeDecided(int $roundId): void {
        $this->roundRepo->setJudgeDecided($roundId);
    }

    /**
     * Check if the judge has decided for this round.
     *
     * @param int $roundId  Round ID
     * @return bool
     */
    public function isJudgeDecided(int $roundId): bool {
        return $this->roundRepo->isJudgeDecided($roundId);
    }

    /**
     * Submit a player's answer for a round.
     *
     * @param int        $roundId    Round ID
     * @param string|int $answer     Player's answer
     * @param float      $timeTaken  Time taken in seconds
     * @return array  Result with is_correct flag, or error array
     */
    public function submitAnswerByRoundId(int $roundId, mixed $answer, float $timeTaken): array {
        $player = Container::auth()->getPlayer();
        $playerId = $player['player_id'] ?? null;
        if (!$playerId) {
            return [
                'success' => false,
                'error' => 'Giocatore non autenticato'
            ];
        }

        $round = $this->roundRepo->getRoundById($roundId);
        if (!$round) {
            return [
                'success' => false,
                'error' => 'Round non trovato'
            ];
        }

        if ($round['question_type'] === 'clickfirst') {
            $this->answerRepo->submitAnswer($roundId, $playerId, $timeTaken);
            return [
                'success' => true,
                'is_correct' => 1
            ];
        }

        // For multiple choice and true/false, check correctness
        $isCorrect = $answer == $round['correct_answer'];
        if ($isCorrect) {
            // Only save the answer if correct, to be ranked in closeRound()
            $this->answerRepo->submitAnswer($roundId, $playerId, $timeTaken);
        }

        return [
            'success' => true,
            'is_correct' => $isCorrect
        ];
    }

    /**
     * Get top fastest answers for a round.
     *
     * @param int $roundId  Round ID
     * @param int $limit    Max results
     * @return array  List of top answers
     */
    public function getTopAnswers(int $roundId, int $limit = 10): array {
        return $this->answerRepo->getTopFastestAnswers($roundId, $limit);
    }

    /**
     * Build the final leaderboard for a room, summing points from all rounds.
     * Only the latest round per question is counted. Sets the room winner.
     *
     * @param int|null $roomId  Room ID (defaults to current session room)
     * @return array  Result with leaderboard entries
     */
    public function getFinalLeaderboard(?int $roomId = null): array {
        $roomId = $roomId ?? authRoomId();
        $room = $this->roomRepo->getRoomById($roomId);

        if (!$room) {
            return [
                'success' => false,
                'leaderboard' => []
            ];
        }

        $allRounds = $this->roundRepo->getRoundsByRoom($room['id']);

        // Keep only the latest round for each question
        $latestRounds = [];
        foreach ($allRounds as $round) {
            $qid = $round['question_id'];
            if (!isset($latestRounds[$qid]) || $round['id'] > $latestRounds[$qid]['id']) {
                $latestRounds[$qid] = $round;
            }
        }

        // Sum points from saved ranking JSON
        $playerScores = [];
        $playerNames  = [];
        foreach ($latestRounds as $round) {
            $ranking = json_decode($round['ranking'] ?? '[]', true);
            foreach ($ranking as $entry) {
                $playerId = $entry['player_id'] ?? null;
                $points   = $entry['points'] ?? 0;
                if ($playerId) {
                    $playerScores[$playerId] = ($playerScores[$playerId] ?? 0) + $points;
                    $playerNames[$playerId]  = $entry['username'] ?? '';
                }
            }
        }

        arsort($playerScores);

        $leaderboard = [];
        foreach ($playerScores as $playerId => $score) {
            $leaderboard[] = [
                'player_id' => $playerId,
                'username'  => $playerNames[$playerId],
                'score'     => $score,
            ];
        }

        // Save the ranking JSON (without display fields like medals)
        if (!empty($leaderboard)) {
            $this->roomRepo->setFinalRanking($room['id'], $leaderboard);
        }

        // Add medals for API response only
        $medals = ['🥇', '🥈', '🥉'];
        foreach ($leaderboard as $i => &$entry) {
            $entry['medal'] = $medals[$i] ?? '';
        }
        unset($entry);

        return [
            'success' => true,
            'leaderboard' => $leaderboard
        ];
    }

    /**
     * Get the number of rounds the current player has answered in their room.
     *
     * @return int|array Number of answered rounds, or error array
     */
    public function getPlayerProgress(): int|array {
        $player = Container::auth()->getPlayer();
        $playerId = $player['player_id'] ?? null;
        $roomId   = $player['room_id'] ?? null;
        if (!$playerId || !$roomId) {
            return ['success' => false, 'error' => 'Giocatore non autenticato'];
        }

        return $this->answerRepo->countAnsweredRounds($playerId, $roomId);
    }
}
