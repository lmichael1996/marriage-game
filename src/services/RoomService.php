<?php
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/QuestionRepo.php';
require_once __DIR__ . '/../repository/RoundRepo.php';
require_once __DIR__ . '/../repository/SetRepo.php';
require_once __DIR__ . '/../utils/ImageGenerator.php';

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
     * Crea una nuova stanza e salva il QR code come data URI nel DB.
     * Non salviamo file su disco: solo data URI (base64 encoded).
     */
    public function createRoom($questionSetId = null) {
        // Genera due codici univoci per player e judge
        $codePlayer = $this->generateUniqueRoomCode();
        $codeJudge = $this->generateUniqueRoomCode();

        // Genera i QR codes come immagini binarie
        $qrPlayerData = $this->generateQRImage($codePlayer);
        $qrJudgeData = $this->generateQRImage($codeJudge);

        $qrPlayerBase64 = null;
        $qrJudgeBase64 = null;

        if ($qrPlayerData !== null) {
            error_log("RoomService: Generated QR player image (" . strlen($qrPlayerData) . " bytes) for room: $codePlayer");
            $qrPlayerBase64 = base64_encode($qrPlayerData);
        } else {
            error_log("Warning: Failed to generate QR player image for room: $codePlayer");
        }

        if ($qrJudgeData !== null) {
            error_log("RoomService: Generated QR judge image (" . strlen($qrJudgeData) . " bytes) for room: $codeJudge");
            $qrJudgeBase64 = base64_encode($qrJudgeData);
        } else {
            error_log("Warning: Failed to generate QR judge image for room: $codeJudge");
        }

        // Salva la stanza con entrambi i codici e QR
        $roomId = $this->roomRepo->createRoom($codePlayer, $codeJudge, $questionSetId, $qrPlayerBase64, $qrJudgeBase64);

        if ($roomId) {
            return [
                'success' => true,
                'room_id' => $roomId,
                'code_player' => $codePlayer,
                'code_judge' => $codeJudge
            ];
        }

        return [
            'success' => false,
            'error' => 'Errore nella creazione della stanza'
        ];
    }    /**
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

        $success = $this->roomRepo->startRoom($room['id']);

        return [
            'success' => $success,
            'message' => $success ? 'Stanza avviata' : 'Errore nell\'avvio',
            'room_id' => $room['id']
        ];
    }

    /**
     * Finish game - update room status to 'closed' when all rounds are completed
     */
    public function finishGame($roomId) {
        try {
            $result = $this->roomRepo->closeRoom($roomId);
            return $result;
        } catch (Exception $e) {
            error_log("finishGame exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel room (admin manually closes room)
     */
    public function cancelRoom($roomCode) {
        try {
            $room = $this->roomRepo->getRoomByCode($roomCode);
            if (!$room) {
                return [
                    'success' => false,
                    'message' => 'Stanza non trovata'
                ];
            }
            $result = $this->roomRepo->cancelRoom($room['id']);
            return $result;
        } catch (Exception $e) {
            error_log("cancelRoom exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ];
        }
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

        $room['players'] = $this->playerRepo->getPlayersByRoomId($room['id']);
        $room['player_count'] = count($room['players'] ?? []);

        if ($room['qset_id'] ?? null) {
            $room['question_set'] = $this->setRepo->getById($room['qset_id']);
        }

        return $room;
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

    /**
     * Genera il QR code come immagine binaria
     */
    private function generateQRImage($roomCode) {
        return ImageGenerator::generateQRFromRoomCode($roomCode);
    }
}

