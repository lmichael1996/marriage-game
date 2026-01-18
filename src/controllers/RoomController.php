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
    public function createRoom($userId, $questionSetId = null) {
        try {
            $result = $this->roomService->createRoom($userId, $questionSetId);
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
}
