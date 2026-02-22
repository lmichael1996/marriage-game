<?php
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/QuestionRepo.php';
require_once __DIR__ . '/../repository/RoundRepo.php';
require_once __DIR__ . '/../repository/SetRepo.php';

/**
 * RoomService - Gestisce la logica di business delle stanze
 */
class RoomService {
    private $roomRepo;
    private $playerRepo;
    private $questionRepo;
    private $roundRepo;
    private $questionSetRepo;

    public function __construct() {
        $this->roomRepo = new RoomRepo();
        $this->playerRepo = new PlayerRepo();
        $this->questionRepo = new QuestionRepo();
        $this->roundRepo = new RoundRepo();
        $this->setRepo = new SetRepo();
    }

    /**
     * Crea una nuova stanza
     */
    public function createRoom($questionSetId = null) {
        // Genera un codice univoco di 6 caratteri
        $roomCode = $this->generateUniqueRoomCode();

        // Salva la stanza con il room code
        $roomId = $this->roomRepo->createRoom($roomCode, $questionSetId);

        if ($roomId) {
            return [
                'success' => true,
                'room_id' => $roomId,
                'room_code' => $roomCode
            ];
        }

        return [
            'success' => false,
            'error' => 'Errore nella creazione della stanza'
        ];
    }

    /**
     * Genera un codice stanza univoco
     */
    private function generateUniqueRoomCode() {
        $attempts = 0;
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
            $exists = $this->roomRepo->getRoomByCode($code);
            $attempts++;
        } while ($exists && $attempts < 10);

        return $code;
    }

    /**
     * Avvia una stanza
     */
    public function startRoom($roomCode) {
        $room = $this->roomRepo->getRoomByCode($roomCode);

        if (!$room) {
            return [
                'success' => false,
                'error' => 'Stanza non trovata'
            ];
        }

        // Reset all rounds to pending status before starting
        // Support both 'qset_id' (DB column) and legacy 'question_set_id' key
        $qsetId = $room['qset_id'] ?? $room['question_set_id'] ?? null;
        if ($qsetId) {
            $this->roundRepo->resetRoundsBySetId($qsetId);
        }

        $success = $this->roomRepo->startRoom($roomCode);

        return [
            'success' => $success,
            'message' => $success ? 'Stanza avviata' : 'Errore nell\'avvio'
        ];
    }

    /**
     * Delete room completely
     */
    public function deleteRoom($roomCode) {
        // First, remove all players from the room
        $this->playerRepo->deletePlayersByRoom($roomCode);

        // Then delete the room itself
        $success = $this->roomRepo->deleteRoom($roomCode);

        return [
            'success' => $success,
            'message' => $success ? 'Stanza eliminata' : 'Errore nell\'eliminazione'
        ];
    }

    /**
     * Ottieni i giocatori di una stanza
     */
    public function getRoomPlayers($roomCode) {
        return $this->playerRepo->getPlayersByRoom($roomCode);
    }

    /**
     * Ottieni dettagli completi di una stanza
     */
    public function getRoomDetails($roomCode) {
        $room = $this->roomRepo->getRoomByCode($roomCode);

        if (!$room) {
            return null;
        }

        // Controlla se la stanza ha già un vincitore (partita finita)
        $winner = $this->roomRepo->getWinner($room['id']);
        if ($winner) {
            $room['has_winner'] = true;
            $room['winner'] = $winner;
        } else {
            $room['has_winner'] = false;
        }

        $room['players'] = $this->getRoomPlayers($roomCode);
        $room['player_count'] = count($room['players']);

        if ($room['qset_id'] ?? null) {
            $room['question_set'] = $this->setRepo->getById($room['qset_id']);
        }

        return $room;
    }

    /**
     * Get question set ID for a room
     */
    public function getQuestionSetIdByRoomCode($roomCode) {
        return $this->roomRepo->getQuestionSetIdByRoomCode($roomCode);
    }

    /**
     * Get active round for a room (reads from DB, not session)
     */
    public function getActiveRound($roomId) {
        return $this->roundRepo->getActiveRound($roomId);
    }

    /**
     * Get round by position for player polling
     */
    public function getRoundByPosition($roomId, $position) {
        return $this->roundRepo->getRoundByPosition($roomId, $position);
    }

    /**
     * Delete all rounds for a room (used when advancing to next question)
     */
    public function deleteRoundByRoom($roomId) {
        return $this->roundRepo->deleteRoundByRoom($roomId);
    }

    /**
     * Inserisci un round nella stanza
     */
    public function insertRound($roomId, $questionId = null) {
        return $this->roundRepo->insertRound($roomId, $questionId);
    }

    /**
     * Mark the winner of a room (highest score)
     */
    public function markWinner($roomId) {
        // Get the room details
        $room = $this->roomRepo->getRoomById($roomId);
        if (!$room) {
            return false;
        }

        // Get all players and their scores
        $players = $this->playerRepo->getPlayersByRoomId($roomId);
        if (!$players || count($players) === 0) {
            return false;
        }

        // Calculate scores for each player
        $scores = [];
        foreach ($players as $player) {
            $correctAnswers = $this->roomRepo->countCorrectAnswersByPlayer($roomId, $player['username']);
            $scores[$player['id']] = [
                'username' => $player['username'],
                'score' => $correctAnswers
            ];
        }

        // Find winner (highest score)
        $winnerId = null;
        $maxScore = -1;
        foreach ($scores as $playerId => $data) {
            if ($data['score'] > $maxScore) {
                $maxScore = $data['score'];
                $winnerId = $playerId;
            }
        }

        if (!$winnerId) {
            return false;
        }

        // Insert winner record
        return $this->roomRepo->insertWinner($roomId, $winnerId);
    }

    /**
     * Check if a player is the winner
     */
    public function isWinner($roomId, $playerId) {
        $winner = $this->roomRepo->getWinner($roomId);
        if ($winner && $winner['user_id'] == $playerId) {
            return true;
        }
        return false;
    }

    /**
     * Mark a round as skipped
     */
    public function skipRound($roundId) {
        return $this->roundRepo->updateRound($roundId, ['is_skipped' => true]);
    }
}
