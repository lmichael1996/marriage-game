<?php
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/QuestionRepo.php';

/**
 * RoomService - Gestisce la logica di business delle stanze
 */
class RoomService {
    private $roomRepo;
    private $playerRepo;
    private $questionRepo;
    
    public function __construct() {
        $this->roomRepo = new RoomRepo();
        $this->playerRepo = new PlayerRepo();
        $this->questionRepo = new QuestionRepo();
    }
    
    /**
     * Crea una nuova stanza
     */
    public function createRoom($questionSetId = null) {
        // Genera un codice univoco di 6 caratteri
        $roomCode = $this->generateUniqueRoomCode();
        
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
        
        if ($room['status_room'] !== 'waiting') {
            return [
                'success' => false,
                'error' => 'La stanza non è in attesa'
            ];
        }
        
        $success = $this->roomRepo->startRoom($roomCode);
        
        return [
            'success' => $success,
            'message' => $success ? 'Stanza avviata' : 'Errore nell\'avvio'
        ];
    }
    
    /**
     * Chiudi una stanza
     */
    public function closeRoom($roomCode) {
        $success = $this->roomRepo->closeRoom($roomCode);
        
        return [
            'success' => $success,
            'message' => $success ? 'Stanza chiusa' : 'Errore nella chiusura'
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
        
        $room['players'] = $this->getRoomPlayers($roomCode);
        $room['player_count'] = count($room['players']);
        
        if ($room['question_set_id']) {
            $room['question_set'] = $this->questionSetRepo->getById($room['question_set_id']);
        }
        
        return $room;
    }
    
    /**
     * Verifica se una stanza è valida e attiva
     */
    public function isRoomActive($roomCode) {
        return $this->roomRepo->verifyRoomCode($roomCode);
    }
    
    /**
     * Cancella una stanza
     */
    public function cancelRoom($roomCode) {
        $room = $this->roomRepo->getRoomByCode($roomCode);
        
        if (!$room) {
            return [
                'success' => false,
                'error' => 'Stanza non trovata'
            ];
        }
        
        $success = $this->roomRepo->cancelRoom($roomCode);
        
        return [
            'success' => $success,
            'message' => $success ? 'Stanza cancellata con successo' : 'Errore nella cancellazione della stanza'
        ];
    }
    
    /**
     * Get question set ID for a room
     */
    public function getQuestionSetIdByRoomCode($roomCode) {
        return $this->roomRepo->getQuestionSetIdByRoomCode($roomCode);
    }
}
