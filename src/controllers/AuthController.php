<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../models/Room.php';

class AuthController {
    private $userModel;
    private $playerModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userModel = new User();
        $this->playerModel = new Player();
    }
    
    /**
     * Guest login with room code
     */
    public function guestLogin($username, $roomCode) {
        // Verify room code exists and is active
        if (!Room::verifyRoomCode($roomCode)) {
            return [
                'success' => false,
                'error' => 'Codice stanza non valido o stanza non attiva'
            ];
        }
        
        // Create new player associated with this room
        $playerId = $this->playerModel->createPlayer($username, $roomCode);
        
        if ($playerId) {
            $_SESSION['player_id'] = $playerId;
            $_SESSION['username'] = $username;
            $_SESSION['room_code'] = strtoupper($roomCode);
            
            return [
                'success' => true,
                'redirect' => 'player.php'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Errore durante la creazione del player'
        ];
    }
    
    /**
     * Admin login with password
     */
    public function adminLogin($username, $password) {
        $user = $this->userModel->authenticate($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // Admin login = no room_code
            
            return [
                'success' => true,
                'redirect' => 'admin.php'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Username o password non validi'
        ];
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        return [
            'success' => true,
            'redirect' => 'login.php'
        ];
    }
    
    /**
     * Check if user is logged in (admin or player)
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) || isset($_SESSION['player_id']);
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return isset($_SESSION['user_id']) && !isset($_SESSION['player_id']);
    }
    
    /**
     * Check if user is player
     */
    public function isPlayer() {
        return isset($_SESSION['player_id']) && isset($_SESSION['room_code']);
    }
}
