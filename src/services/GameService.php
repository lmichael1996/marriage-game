<?php
require_once __DIR__ . '/../repository/RoundRepo.php';
require_once __DIR__ . '/../repository/AnswerRepo.php';
require_once __DIR__ . '/../repository/SettingsRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoomRepo.php';

/**
 * GameService - Gestisce la logica di business del gioco
 */
class GameService {
    private $roundRepo;
    private $answerRepo;
    private $settingsRepo;
    private $playerRepo;
    private $roomRepo;
    private $questionService;

    public function __construct() {
        $this->roundRepo = new RoundRepo();
        $this->answerRepo = new AnswerRepo();
        $this->settingsRepo = new SettingsRepo();
        $this->playerRepo = new PlayerRepo();
        $this->roomRepo = new RoomRepo();
        $this->questionService = new QuestionService();
    }

    /**
     * Avvia un nuovo round
     */
    public function startRound($questionId, $roomCode) {
        try {
            if (!$roomCode) {
                return ['success' => false, 'error' => 'Nessuna stanza attiva'];
            }

            $question = $this->questionService->getQuestionById($questionId);
            if (!$question) {
                return ['success' => false, 'error' => 'Domanda non trovata'];
            }

            $room = $this->roomRepo->getRoomByCode($roomCode);
            if (!$room) {
                return ['success' => false, 'error' => 'Stanza non trovata'];
            }

            $roundId = $this->roundRepo->createRound($room['id'], $questionId);
            if (!$roundId) {
                return ['success' => false, 'error' => 'Errore nella creazione del round'];
            }

            return [
                'success' => true,
                'message' => 'Round avviato',
                'roundId' => $roundId,
                'questionId' => $questionId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get active round from session (usato da game.php per il judge)
     */
    public function getActiveRound($questionSetId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $player = svc('auth')->getPlayer();
        $roomCode = $player['room_code'] ?? ($_SESSION['code_player'] ?? null);
        if (!$roomCode) {
            return null;
        }

        return $_SESSION['active_round_data_' . $roomCode] ?? null;
    }

    /**
     * Chiudi un round e salva il ranking
     */
    public function closeRound($roundId) {
        $round = $this->roundRepo->getRoundById($roundId);
        if (!$round) {
            return ['success' => false, 'error' => 'Round non trovato'];
        }

        try {
            $topAnswers = $this->answerRepo->getTopFastestAnswers($roundId, 10);

            // Build and save ranking JSON
            $roundType = $round['round_type'] ?? 'multiple';
            $ranking = [];
            foreach ($topAnswers as $i => $answer) {
                $position = $i + 1;
                $ranking[] = [
                    'position'    => $position,
                    'username'    => $answer['username'],
                    'player_id'   => (int)$answer['player_id'],
                    'answer_time' => (float)$answer['answer_time'],
                    'points'      => $this->getPointsForPosition($roundType, $position)
                ];
            }
            $this->roundRepo->saveRanking($roundId, $ranking);

            return [
                'success' => true,
                'message' => 'Round chiuso',
                'nextRound' => ($round['round_number'] ?? 0) + 1,
                'top_answers' => $topAnswers
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ottieni i punti per una posizione in base al tipo di round
     */
    private function getPointsForPosition($roundType, $position) {
        if ($position > 10) return 0;

        if ($roundType === 'clickfirst') {
            return $position === 1 ? intval($this->settingsRepo->getSetting('points_clickfirst')) : 0;
        }

        $prefix = $roundType === 'truefalse' ? 'points_tf' : 'points_mult';
        $suffix = match ($position) {
            1 => '1st', 2 => '2nd', 3 => '3rd',
            default => "{$position}th"
        };

        $setting = $this->settingsRepo->getSetting("{$prefix}_{$suffix}");
        return $setting ? intval($setting) : 0;
    }

    /**
     * Imposta il vincitore del clickfirst: riscrive il ranking con solo il giocatore selezionato
     */
    public function setClickfirstWinner($roundId, $winnerIndex) {
        $round = $this->roundRepo->getRoundById($roundId);
        if (!$round) {
            return ['success' => false, 'error' => 'Round non trovato'];
        }

        $currentRanking = json_decode($round['ranking'] ?? '[]', true);
        if (!is_array($currentRanking) || !isset($currentRanking[$winnerIndex])) {
            return ['success' => false, 'error' => 'Indice vincitore non valido'];
        }

        $winner = $currentRanking[$winnerIndex];
        $points = intval($this->settingsRepo->getSetting('points_clickfirst'));

        $newRanking = [[
            'position'    => 1,
            'username'    => $winner['username'],
            'player_id'   => (int)$winner['player_id'],
            'answer_time' => (float)$winner['answer_time'],
            'points'      => $points,
        ]];

        $this->roundRepo->saveRanking($roundId, $newRanking);

        return ['success' => true, 'message' => 'Vincitore clickfirst salvato'];
    }

    /**
     * Segna che il giudice ha deciso per questo round (clickfirst)
     */
    public function markJudgeDecided($roundId) {
        $this->roundRepo->setJudgeDecided($roundId);
    }

    /**
     * Controlla se il giudice ha deciso per questo round
     */
    public function isJudgeDecided($roundId) {
        return $this->roundRepo->isJudgeDecided($roundId);
    }

    /**
     * Ottieni la classifica generale (usata dal judge in game.php)
     */
    public function getLeaderboard($roomCode = null) {
        return $this->answerRepo->getLeaderboard($roomCode);
    }

    /**
     * Submit answer using round_id directly
     */
    public function submitAnswerByRoundId($roundId, $answer, $timeTaken) {
        $player = svc('auth')->getPlayer();
        $playerId = $player['player_id'] ?? null;
        if (!$playerId) {
            throw new Exception('Player ID non trovato');
        }

        $round = $this->roundRepo->getRoundById($roundId);
        if (!$round) {
            throw new Exception('Round non trovato');
        }
        if (!isset($round['round_type'])) {
            throw new Exception('Tipo di round non trovato');
        }

        if ($round['round_type'] === 'clickfirst') {
            $this->answerRepo->submitAnswer($roundId, $playerId, $timeTaken);
            return ['success' => true, 'is_correct' => 1];
        }

        if (!isset($round['correct_answer'])) {
            throw new Exception('Risposta corretta non trovata per questo round');
        }

        $isCorrect = ($answer == $round['correct_answer']) ? 1 : 0;
        if ($isCorrect) {
            $this->answerRepo->submitAnswer($roundId, $playerId, $timeTaken);
        }

        return ['success' => true, 'is_correct' => $isCorrect];
    }

    /**
     * Get top fastest answers for a round
     */
    public function getTopAnswers($roundId, $limit = 10) {
        return $this->answerRepo->getTopFastestAnswers($roundId, $limit);
    }

    /**
     * Get final leaderboard for a room
     */
    public function getFinalLeaderboard($roomCode = null, $roomId = null) {
        if (!$roomId) {
            if (!$roomCode) {
                $player = svc('auth')->getPlayer();
                $roomCode = $player['room_code'] ?? ($_SESSION['code_player'] ?? null);
            }
            if (!$roomCode) {
                return ['success' => false, 'leaderboard' => []];
            }
            $room = $this->roomRepo->getRoomByCode($roomCode);
        } else {
            $room = $this->roomRepo->getRoomById($roomId);
        }

        try {
            if (!$room) {
                return ['success' => false, 'leaderboard' => []];
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
            foreach ($latestRounds as $round) {
                $ranking = json_decode($round['ranking'] ?? '[]', true);
                if (!is_array($ranking)) continue;
                foreach ($ranking as $entry) {
                    $username = $entry['username'] ?? null;
                    $points   = $entry['points'] ?? 0;
                    if ($username) {
                        $playerScores[$username] = ($playerScores[$username] ?? 0) + $points;
                    }
                }
            }

            arsort($playerScores);

            $medals = ['🥇', '🥈', '🥉'];
            $leaderboard = [];
            $first = true;

            foreach ($playerScores as $username => $score) {
                $index = count($leaderboard);
                $leaderboard[] = [
                    'username' => $username,
                    'score'    => $score,
                    'medal'    => $medals[$index] ?? ''
                ];

                if ($first) {
                    $player = $this->playerRepo->getPlayerByRoomAndUsername($room['id'], $username);
                    if ($player) {
                        $this->roomRepo->setWinner($room['id'], $player['id']);
                    }
                    $first = false;
                }
            }

            return ['success' => true, 'leaderboard' => $leaderboard];
        } catch (Exception $e) {
            return ['success' => false, 'leaderboard' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Reset all rounds for a question set
     */
    public function resetGameByQuestionSet($questionSetId) {
        return $this->roundRepo->resetRoundsBySetId($questionSetId);
    }
}
