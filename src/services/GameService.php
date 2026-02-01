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
            // Only store room_id, round_number, and type_game
            // Rankings will be computed on-demand from player_answers
            $type_game = $question['round_type'] ?? 'multiple';
            $roundId = $this->roundRepo->createRound($room['id'], $question['round_number'], $type_game);

            if (!$roundId) {
                return [
                    'success' => false,
                    'error' => 'Errore nella creazione del round'
                ];
            }

            // Store active round in session with global key (shared across users in same room)
            // Use a file or database to store active round per room
            // For now, use a session key that's not per-user
            $_SESSION['active_round_' . $roomCode] = $roundId;
            $_SESSION['active_question_' . $roomCode] = $questionId;

            // Also store in a file for cross-session access
            $this->storeActiveRoundToFile($roomCode, $roundId, $questionId);

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
     * Get active round - from file storage for cross-session access
     */
    public function getActiveRound($questionSetId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $roomCode = $_SESSION['room_code'] ?? null;

        if (!$roomCode) {
            return null;
        }

        // Try to get active round from file (cross-session)
        $activeRoundId = $this->getActiveRoundFromFile($roomCode);

        // If we have an active round ID, fetch and return it
        if ($activeRoundId) {
            return $this->roundRepo->getRoundById($activeRoundId);
        }

        // No active round
        return null;
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

            // Chiudi il round in repository (compatibility)
            $closed = $this->roundRepo->closeRound($roundId);

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
                'success' => (bool)$closed,
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
     * Ottieni statistiche del round
     */
    public function getRoundStats($roundId) {
        return $this->roundRepo->getRoundStats($roundId);
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
        if (!$round || !isset($round['correct_answer'])) {
            throw new Exception('Risposta corretta non trovata per questo round');
        }

        // Check if answer is correct
        $is_correct = ($answer == $round['correct_answer']) ? 1 : 0;

        error_log("Answer check: user_answer=$answer, correct_answer={$round['correct_answer']}, is_correct=$is_correct");

        // Save the answer ONLY if correct
        if ($is_correct) {
            $this->answerRepo->submitAnswer($roundId, $userId, $timeTaken);
        }

        return ['is_correct' => $is_correct];
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
}
