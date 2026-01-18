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
     * Avvia un nuovo round
     */
    public function startRound($roundId) {
        return $this->roundRepo->startRound($roundId);
    }
    
    /**
     * Get active round
     */
    public function getActiveRound() {
        return $this->roundRepo->getActiveRound();
    }
    
    /**
     * Chiudi un round e calcola i punteggi
     */
    public function closeRound($roundId) {
        $round = $this->roundRepo->getRoundById($roundId);
        
        if (!$round) {
            return false;
        }
        
        // Calcola i punteggi in base al tipo di round
        $this->calculateScores($roundId, $round['round_type']);
        
        // Chiudi il round
        return $this->roundRepo->closeRound($roundId);
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
            
            $points = $this->getPointsForPosition($roundType, $position);
            
            if ($points > 0) {
                $this->answerRepo->updateScore($answer['id'], $points);
            }
            
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
        
        if ($round['status_round'] !== 'active') {
            throw new Exception('Round non più attivo');
        }
        
        // Check if answer is correct
        $is_correct = ($answer == $round['correct_answer']) ? 1 : 0;
        
        // Save answer
        $this->answerRepo->submitAnswer($roundId, $userId, $answer, $timeTaken, $is_correct);
        
        return ['is_correct' => $is_correct];
    }
}
