<?php
require_once __DIR__ . '/../repository/RoundRepo.php';
require_once __DIR__ . '/../repository/AnswerRepo.php';
require_once __DIR__ . '/../repository/SettingsRepo.php';

/**
 * GameService - Gestisce la logica di business del gioco
 */
class GameService {
    private $roundRepo;
    private $answerRepo;
    private $settingsRepo;

    public function __construct() {
        $this->roundRepo = new RoundRepo();
        $this->answerRepo = new AnswerRepo();
        $this->settingsRepo = new SettingsRepo();
    }

    /**
     * Avvia un nuovo round - persist active round in session
     */
    public function startRound($roundId, $roomCode = null) {
        // Validate round exists
        $round = $this->roundRepo->getRoundById($roundId);
        if (!$round) {
            return [
                'success' => false,
                'error' => 'Round non trovato'
            ];
        }

        try {
            // Store active round in session (per-room, keyed by room code or global)
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            // Use roomCode from param or from session
            if (!$roomCode && isset($_SESSION['room_code'])) {
                $roomCode = $_SESSION['room_code'];
            }

            // Store active round in session keyed by room
            if ($roomCode) {
                $_SESSION['active_round_' . $roomCode] = $roundId;
            } else {
                // Fallback: store globally
                $_SESSION['active_round'] = $roundId;
            }

            return [
                'success' => true,
                'message' => 'Round avviato'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get active round - from session storage
     */
    public function getActiveRound($questionSetId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $roomCode = $_SESSION['room_code'] ?? null;

        // Try to get active round from session (per-room)
        if ($roomCode) {
            $activeRoundId = $_SESSION['active_round_' . $roomCode] ?? null;
        } else {
            // Fallback to global session active round
            $activeRoundId = $_SESSION['active_round'] ?? null;
        }

        // If we have an active round ID, fetch and return it
        if ($activeRoundId) {
            return $this->roundRepo->getRoundById($activeRoundId);
        }

        // No active round in session
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

            // Clear active round from session and track last completed round
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $roomCode = $_SESSION['room_code'] ?? null;
            if ($roomCode) {
                unset($_SESSION['active_round_' . $roomCode]);
                // Track the last completed round number for progression
                $_SESSION['last_completed_round_' . $roomCode] = $round['round_number'];
            } else {
                unset($_SESSION['active_round']);
                $_SESSION['last_completed_round'] = $round['round_number'];
            }

            return [
                'success' => (bool)$closed,
                'message' => 'Round chiuso',
                'nextRound' => $round['round_number'] + 1
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
    public function submitAnswer($userId, $roundId, $answer, $timeTaken) {
        // Check if user already answered this round
        if ($this->answerRepo->hasAnswered($roundId, $userId)) {
            throw new Exception('Hai già risposto a questo round');
        }

        // Get round info
        $round = $this->roundRepo->getRoundById($roundId);

        if (!$round) {
            throw new Exception('Round non trovato');
        }

        // Check if answer is correct
        $is_correct = ($answer == $round['correct_answer']) ? 1 : 0;

        // Save answer (without storing the actual answer)
        $this->answerRepo->submitAnswer($roundId, $userId, $timeTaken, $is_correct);

        return ['is_correct' => $is_correct];
    }
}
