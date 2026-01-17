<?php
require_once __DIR__ . '/../repository/Room.php';
require_once __DIR__ . '/../config/database.php';

class RoomController {
    private $db;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = getDBConnection();
    }
    
    /**
     * Create a new room
     */
    public function createRoom($roomCode) {
        if (!isset($_SESSION['user_id'])) {
            return [
                'success' => false,
                'error' => 'Admin non autenticato'
            ];
        }
        
        $adminId = $_SESSION['user_id'];
        Room::createRoom($roomCode, $adminId);
        
        return [
            'success' => true,
            'room_code' => strtoupper($roomCode)
        ];
    }
    
    /**
     * Close the active room and remove all players
     */
    public function closeRoom() {
        // Get active room
        $room = Room::getActiveRoom();
        
        if ($room) {
            $roomCode = $room['code'];
            
            // Delete all players for this room
            $stmt = $this->db->prepare("DELETE FROM players WHERE room_code = ?");
            $stmt->bind_param("s", $roomCode);
            $stmt->execute();
            $stmt->close();
        }
        
        // Close the active room
        Room::closeRoom();
        
        // Set a flag to signal room closure to connected players
        $flagFile = sys_get_temp_dir() . '/marriage_game_room_closed.flag';
        file_put_contents($flagFile, time());
        
        return [
            'success' => true,
            'message' => 'Stanza chiusa. Tutti i giocatori sono stati rimossi.'
        ];
    }
    
    /**
     * Get active room information
     */
    public function getActiveRoom() {
        $room = Room::getActiveRoom();
        
        if ($room) {
            return [
                'success' => true,
                'room' => $room
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Nessuna stanza attiva'
        ];
    }
    
    /**
     * Check if room is still open
     */
    public function checkRoomStatus() {
        $flagFile = sys_get_temp_dir() . '/marriage_game_room_closed.flag';
        
        // Check if room was closed in the last 5 seconds
        if (file_exists($flagFile)) {
            $closedTime = (int)file_get_contents($flagFile);
            $currentTime = time();
            
            if (($currentTime - $closedTime) < 5) {
                // Room was just closed, signal logout
                return [
                    'room_open' => false,
                    'message' => 'La stanza è stata chiusa dall\'admin'
                ];
            } else {
                // Old flag, remove it
                unlink($flagFile);
            }
        }
        
        return [
            'room_open' => true
        ];
    }
    
    /**
     * Get connected devices (players)
     */
    public function getConnectedDevices() {
        // Get active room
        $room = Room::getActiveRoom();
        
        if (!$room) {
            return [
                'success' => true,
                'devices' => [],
                'count' => 0
            ];
        }
        
        $roomCode = $room['code'];
        
        // Get all players for this room
        $stmt = $this->db->prepare("
            SELECT 
                id,
                username,
                'online' as status,
                last_seen
            FROM players 
            WHERE room_code = ?
            ORDER BY connected_at ASC
        ");
        
        $stmt->bind_param("s", $roomCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $devices = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return [
            'success' => true,
            'devices' => $devices,
            'count' => count($devices)
        ];
    }
}
