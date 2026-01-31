<?php
require_once __DIR__ . '/../services/RoomService.php';

class RoomController {
    private $roomService;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->roomService = new RoomService();
    }
    
    /**
     * Create a new room
     */
    public function createRoom($questionSetId = null) {
        try {
            $result = $this->roomService->createRoom($questionSetId);
            return [
                'success' => true,
                'room_code' => $result['room_code'],
                'room_id' => $result['room_id']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Close room
     */
    public function closeRoom($roomCode) {
        try {
            $this->roomService->closeRoom($roomCode);
            return [
                'success' => true,
                'message' => 'Stanza chiusa. Tutti i giocatori sono stati rimossi.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete room completely
     */
    public function deleteRoom($roomCode) {
        try {
            $this->roomService->deleteRoom($roomCode);
            return [
                'success' => true,
                'message' => 'Stanza eliminata completamente.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel room
     */
    public function cancelRoom($roomCode) {
        try {
            $result = $this->roomService->cancelRoom($roomCode);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Start room (set status to active)
     */
    public function startRoom($roomCode) {
        try {
            $result = $this->roomService->startRoom($roomCode);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get room players
     */
    public function getRoomPlayers($roomCode) {
        try {
            $players = $this->roomService->getRoomPlayers($roomCode);
            return [
                'success' => true,
                'devices' => $players,
                'count' => count($players)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get question set ID for a room
     */
    public function getQuestionSetIdByRoomCode($roomCode) {
        return $this->roomService->getQuestionSetIdByRoomCode($roomCode);
    }
}
