<?php
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoundRepo.php';
require_once __DIR__ . '/../repository/SetRepo.php';
require_once __DIR__ . '/../repository/JudgeRepo.php';
require_once __DIR__ . '/../utils/QRGenerator.php';

/**
 * RoomService - Gestisce la logica di business delle stanze
 */
class RoomService {
    private $roomRepo;
    private $playerRepo;
    private $roundRepo;
    private $setRepo;
    private $judgeRepo;

    public function __construct() {
        $this->roomRepo = new RoomRepo();
        $this->playerRepo = new PlayerRepo();
        $this->roundRepo = new RoundRepo();
        $this->setRepo = new SetRepo();
        $this->judgeRepo = new JudgeRepo();
    }

    /**
     * Crea una nuova stanza e salva il QR code come data URI nel DB.
     * Non salviamo file su disco: solo data URI (base64 encoded).
     */
    public function createRoom($questionSetId = null) {
        // Istanzia due oggetti QRGenerator con nome pagina login
        $qrGeneratorPlayer = new QRGenerator('login-player.php');
        $qrGeneratorJudge  = new QRGenerator('login-judge.php');

        // Genera codice e SVG QR per player
        $playerPair = $qrGeneratorPlayer->generate();
        $codePlayer = $playerPair['code'];

        // Genera codice e SVG QR per judge
        $judgePair = $qrGeneratorJudge->generate();
        $codeJudge = $judgePair['code'];

        // Salva SVG come base64 nel DB
        $qrPlayerBase64 = !empty($playerPair['svg']) ? base64_encode($playerPair['svg']) : null;
        $qrJudgeBase64  = !empty($judgePair['svg'])  ? base64_encode($judgePair['svg'])  : null;

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
        $room['has_winner'] = $room['winner_id'] !== null;

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
            $correctAnswers = $this->roomRepo->countCorrectAnswersByPlayer($roomId, $player['id']);
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
        return $this->roomRepo->setWinner($roomId, $winnerId);
    }

    /**
     * Check if a player is the winner
     */
    public function isWinner($roomId, $playerId) {
        $winnerId = $this->roomRepo->getWinnerId($roomId);
        return $winnerId !== null && $winnerId == $playerId;
    }

    /**
     * Get connected devices (players) for a room by code
     */
    public function getConnectedDevices($codePlayer) {
        $roomData = $this->getRoomDetails($codePlayer);
        if (!$roomData) return ['devices' => [], 'count' => 0, 'judge_connected' => false];

        $devices = $this->playerRepo->getPlayersByRoomId($roomData['id']);
        $devices = is_array($devices) ? $devices : [];

        $judgeConnected = $this->judgeRepo->isJudgeConnected($roomData['id']);

        return ['devices' => $devices, 'count' => count($devices), 'judge_connected' => $judgeConnected];
    }

    /**
     * Get a player by room ID and username
     */
    public function getPlayerByRoomAndUsername($roomId, $username) {
        return $this->playerRepo->getPlayerByRoomAndUsername($roomId, $username);
    }

    /**
     * Get room by ID
     */
    public function getRoomById(int $roomId): ?array {
        return $this->roomRepo->getRoomById($roomId) ?: null;
    }

    /**
     * Get count of players connected to a room
     */
    public function getPlayerCount(int $roomId): int {
        $players = $this->playerRepo->getPlayersByRoomId($roomId);
        return count($players ?? []);
    }

    /**
     * Controlla se il giudice è connesso alla stanza
     */
    public function isJudgeConnected(int $roomId): bool {
        return $this->judgeRepo->isJudgeConnected($roomId);
    }
}

