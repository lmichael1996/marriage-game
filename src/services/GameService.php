<?php
require_once __DIR__ . '/../repository/RoundRepo.php';
require_once __DIR__ . '/../repository/AnswerRepo.php';
require_once __DIR__ . '/../repository/SettingsRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/QuestionService.php';

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
     * Get room by code
     */
    private function getRoomByCode($roomCode) {
        return $this->roomRepo->getRoomByCode($roomCode);
    }

    /**
     * Avvia un nuovo round - persist active round in session
     */
    public function startRound($questionId, $roomCode = null) {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            // Use roomCode from param or from session
            if (!$roomCode && isset($_SESSION['room_code'])) {
                $roomCode = $_SESSION['room_code'];
            }

            if (!$roomCode) {
                return [
                    'success' => false,
                    'error' => 'Nessuna stanza attiva'
                ];
            }

            // Get the question details (this is the question to start)
            $question = $this->questionService->getQuestionById($questionId);
            if (!$question) {
                return [
                    'success' => false,
                    'error' => 'Domanda non trovata'
                ];
            }

            // Get room info to get room_id
            $room = $this->getRoomByCode($roomCode);
            if (!$room) {
                return [
                    'success' => false,
                    'error' => 'Stanza non trovata'
                ];
            }

            // Create a new round record in the database
            $roundId = $this->roundRepo->createRound($room['id'], $questionId);

            if (!$roundId) {
                return [
                    'success' => false,
                    'error' => 'Errore nella creazione del round'
                ];
            }

            // Salva il round attivo completo in session con tutti i dati della domanda
            $activeRoundData = array_merge(
                [
                    'id' => $roundId,
                    'room_id' => $room['id'],
                    'question_id' => $questionId,
                    'round_number' => $_SESSION['round_counter_' . $roomCode] ?? 1
                ],
                $question  // Aggiunge tutti i campi della domanda (question, option1, option2, etc)
            );
            $_SESSION['active_round_data_' . $roomCode] = $activeRoundData;

            return [
                'success' => true,
                'message' => 'Round avviato',
                'roundId' => $roundId,
                'questionId' => $questionId
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get active round from session (non serve il DB)
     */
    public function getActiveRound($questionSetId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $roomCode = $_SESSION['room_code'] ?? null;

        if (!$roomCode) {
            return null;
        }

        // Leggi il round attivo dalla session
        $activeRoundData = $_SESSION['active_round_data_' . $roomCode] ?? null;

        return $activeRoundData;
    }

    /**
     * Chiudi un round e calcola i punteggi
     */
    public function closeRound($roundId) {
        $round = $this->roundRepo->getRoundById($roundId);

        if (!$round) {
            return [
                'success' => false,
                'error' => 'Round non trovato'
            ];
        }

        try {
            // Calcola i punteggi in base al tipo di round
            $this->calculateScores($roundId, $round['round_type']);

            // Chiudi il round in repository (compatibility) - metodo non esiste in RoundRepo
            // $closed = $this->roundRepo->closeRound($roundId);

            // Get top 10 fastest answers
            $topAnswers = $this->answerRepo->getTopFastestAnswers($roundId, 10);

            // Clear active round from session and file
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $roomCode = $_SESSION['room_code'] ?? null;
            if ($roomCode) {
                unset($_SESSION['active_round_' . $roomCode]);
                // Clear from file as well
                $this->clearActiveRoundFromFile($roomCode);
                // Track the last completed round number for progression
                $_SESSION['last_completed_round_' . $roomCode] = $round['round_number'];
            } else {
                unset($_SESSION['active_round']);
                $_SESSION['last_completed_round'] = $round['round_number'];
            }

            return [
                'success' => true,
                'message' => 'Round chiuso',
                'nextRound' => $round['round_number'] + 1,
                'top_answers' => $topAnswers
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calcola i punteggi per un round
     */
    private function calculateScores($roundId, $roundType) {
        $answers = $this->answerRepo->getRoundAnswers($roundId);

        if (empty($answers)) {
            return;
        }

        // Ordina per tempo di risposta (più veloce prima)
        usort($answers, function($a, $b) {
            return $a['time_taken'] - $b['time_taken'];
        });

        $position = 1;
        foreach ($answers as $answer) {
            if (!$answer['is_correct']) {
                continue;
            }

            // Points are calculated dynamically in the leaderboard query
            // No need to store them in player_answers table
            $points = $this->getPointsForPosition($roundType, $position);

            // Solo per clickfirst, vince solo il primo
            if ($roundType === 'clickfirst') {
                break;
            }

            $position++;

            // Max 10 posizioni premiate
            if ($position > 10) {
                break;
            }
        }
    }

    /**
     * Ottieni i punti per una posizione in base al tipo di round
     */
    private function getPointsForPosition($roundType, $position) {
        if ($position > 10) {
            return 0;
        }

        $settingKey = '';

        switch ($roundType) {
            case 'multiple':
                $settingKey = "points_mult_{$position}";
                if ($position === 1) $settingKey = 'points_mult_1st';
                elseif ($position === 2) $settingKey = 'points_mult_2nd';
                elseif ($position === 3) $settingKey = 'points_mult_3rd';
                else $settingKey = "points_mult_{$position}th";
                break;

            case 'truefalse':
                $settingKey = "points_tf_{$position}";
                if ($position === 1) $settingKey = 'points_tf_1st';
                elseif ($position === 2) $settingKey = 'points_tf_2nd';
                elseif ($position === 3) $settingKey = 'points_tf_3rd';
                else $settingKey = "points_tf_{$position}th";
                break;

            case 'clickfirst':
                return $position === 1 ? intval($this->settingsRepo->getSetting('points_clickfirst')) : 0;
        }

        $setting = $this->settingsRepo->getSetting($settingKey);
        return $setting ? intval($setting) : 0;
    }

    /**
     * Ottieni la classifica generale
     */
    public function getLeaderboard($roomCode = null) {
        return $this->answerRepo->getLeaderboard($roomCode);
    }

    /**
     * Submit player answer
     */
    public function submitAnswer($userId, $roundNumber, $answer, $timeTaken) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $roomCode = $_SESSION['room_code'] ?? null;

        if (!$roomCode) {
            throw new Exception('Room code non trovato nella sessione');
        }

        // Get room info to get room_id
        $room = $this->roomRepo->getRoomByCode($roomCode);
        if (!$room) {
            throw new Exception('Stanza non trovata');
        }

        // Get round by room_id and round_number to get roundId
        $roundData = $this->roundRepo->getRoundByRoomAndNumber($room['id'], $roundNumber);
        if (!$roundData) {
            throw new Exception('Round non trovato');
        }

        $roundId = $roundData['id'];

        // Check if user already answered this round
        if ($this->answerRepo->hasAnswered($roundId, $userId)) {
            throw new Exception('Hai già risposto a questo round');
        }

        // Get full round data including correct_answer from questions table
        $round = $this->roundRepo->getRoundById($roundId);
        if (!$round) {
            throw new Exception('Round non trovato');
        }

        // Verifica che il round abbia i dati necessari
        if (!isset($round['round_type'])) {
            throw new Exception('Tipo di round non trovato');
        }

        // Per il tipo "clickfirst", accetta sempre la risposta (correct_answer può essere NULL)
        if ($round['round_type'] === 'clickfirst') {
            $is_correct = 1; // Sempre corretta per clickfirst
        } else {
            // Per gli altri tipi, correct_answer deve essere definito
            if (!isset($round['correct_answer'])) {
                throw new Exception('Risposta corretta non trovata per questo round');
            }
            // Per gli altri tipi, verifica se la risposta è corretta
            $is_correct = ($answer == $round['correct_answer']) ? 1 : 0;
        }

        error_log("Answer check: user_answer=$answer, correct_answer={$round['correct_answer']}, is_correct=$is_correct, round_type={$round['round_type']}");

        // Salva la risposta (per clickfirst, viene sempre salvata)
        $this->answerRepo->submitAnswer($roundId, $userId, $timeTaken);

        return ['is_correct' => $is_correct];
    }

    /**
     * Submit answer using round_id directly
     */
    public function submitAnswerByRoundId($roundId, $answer, $timeTaken) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $playerId = $_SESSION['player_id'] ?? null;

        if (!$playerId) {
            throw new Exception('Player ID non trovato nella sessione');
        }

        // Get full round data including correct_answer from questions table
        $round = $this->roundRepo->getRoundById($roundId);
        if (!$round) {
            throw new Exception('Round non trovato');
        }

        // Verifica che il round abbia i dati necessari
        if (!isset($round['round_type'])) {
            throw new Exception('Tipo di round non trovato');
        }

        // Per il tipo "clickfirst", accetta sempre la risposta (correct_answer può essere NULL)
        if ($round['round_type'] === 'clickfirst') {
            $is_correct = 1; // Sempre corretta per clickfirst
        } else {
            // Per gli altri tipi, correct_answer deve essere definito
            if (!isset($round['correct_answer'])) {
                throw new Exception('Risposta corretta non trovata per questo round');
            }
            // Per gli altri tipi, verifica se la risposta è corretta
            $is_correct = ($answer == $round['correct_answer']) ? 1 : 0;
        }

        // Salva la risposta
        $this->answerRepo->submitAnswer($roundId, $playerId, $timeTaken);

        return [
            'success' => true,
            'is_correct' => $is_correct
        ];
    }

    /**
     * Store active round info to a file for cross-session access
     */
    private function storeActiveRoundToFile($roomCode, $roundId, $questionId) {
        $filePath = sys_get_temp_dir() . '/marriage_game_active_round_' . $roomCode . '.json';
        $data = [
            'round_id' => $roundId,
            'question_id' => $questionId,
            'timestamp' => time()
        ];
        file_put_contents($filePath, json_encode($data));
    }

    /**
     * Get active round info from file for cross-session access
     */
    private function getActiveRoundFromFile($roomCode) {
        $filePath = sys_get_temp_dir() . '/marriage_game_active_round_' . $roomCode . '.json';
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            return $data['round_id'] ?? null;
        }
        return null;
    }

    /**
     * Clear active round from file
     */
    private function clearActiveRoundFromFile($roomCode) {
        $filePath = sys_get_temp_dir() . '/marriage_game_active_round_' . $roomCode . '.json';
        if (file_exists($filePath)) {
            unlink($filePath);
        }
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
    public function getFinalLeaderboard($roomCode = null) {
        if (!$roomCode && isset($_SESSION['room_code'])) {
            $roomCode = $_SESSION['room_code'];
        }

        if (!$roomCode) {
            return ['success' => false, 'leaderboard' => []];
        }

        try {
            $room = $this->getRoomByCode($roomCode);
            if (!$room) {
                return ['success' => false, 'leaderboard' => []];
            }

            $allRounds = $this->roundRepo->getRoundsByRoom($room['id']);

            // Default scoring
            $scoreMap = [
                'clickfirst' => [1 => 50],
                'multiple' => [1 => 25, 2 => 18, 3 => 15, 4 => 12, 5 => 10, 6 => 8, 7 => 6, 8 => 4, 9 => 2, 10 => 1],
                'truefalse' => [1 => 20, 2 => 15, 3 => 12, 4 => 10, 5 => 8, 6 => 6, 7 => 5, 8 => 3, 9 => 2, 10 => 1]
            ];

            $playerScores = [];

            foreach ($allRounds as $round) {
                $topAnswers = $this->answerRepo->getTopFastestAnswers($round['id'], 10);

                foreach ($topAnswers as $index => $answer) {
                    $username = $answer['username'];
                    $position = $index + 1;
                    $points = $scoreMap['multiple'][$position] ?? 0;

                    if (!isset($playerScores[$username])) {
                        $playerScores[$username] = 0;
                    }
                    $playerScores[$username] += $points;
                }
            }

            arsort($playerScores);

            $medals = ['🥇', '🥈', '🥉'];
            $leaderboard = [];
            $isFirstWinner = true;

            foreach ($playerScores as $username => $score) {
                $index = count($leaderboard);
                $medal = $medals[$index] ?? '';
                $leaderboard[] = [
                    'username' => $username,
                    'score' => $score,
                    'medal' => $medal
                ];

                // Insert the first player (winner) into the winners table
                if ($isFirstWinner) {
                    $player = $this->playerRepo->getPlayerByRoomAndUsername($room['id'], $username);
                    if ($player) {
                        $this->roomRepo->insertWinner($room['id'], $player['id']);
                    }
                    $isFirstWinner = false;
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
