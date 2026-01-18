<?php
require_once __DIR__ . '/../repository/UserRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoomRepo.php';

/**
 * Auth - Gestisce la logica di autenticazione
 * Restituisce true/false o lancia eccezioni, il controller gestisce gli errori
 */
class AuthService {
    private $userRepo;
    private $playerRepo;
    private $roomRepo;
    
    public function __construct() {
        $this->userRepo = new UserRepo();
        $this->playerRepo = new PlayerRepo();
        $this->roomRepo = new RoomRepo();
    }
    
    /**
     * Admin login with username and password
     * @return string 'admin' on success
     * @throws Exception on failure
     */
    public function adminLogin($username, $password) {
        $user = $this->userRepo->authenticate($username, $password);
        
        if (!$user) {
            throw new Exception('Username o password non validi');
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = 1;
        $_SESSION['logged_in_via_login'] = true;
        
        return 'admin';
    }
    
    /**
     * Player login with room code
     * @return string 'player' on success
     * @throws Exception on failure
     */
    public function playerLogin($username, $roomCode) {
        // Verify room code exists and is active
        if (!$this->roomRepo->verifyRoomCode($roomCode)) {
            throw new Exception('Codice stanza non valido o stanza non attiva');
        }
        
        // Create new player associated with this room
        $playerId = $this->playerRepo->createPlayer($username, $roomCode);
        
        if (!$playerId) {
            throw new Exception('Errore durante la creazione del player');
        }
        
        $_SESSION['player_id'] = $playerId;
        $_SESSION['username'] = $username;
        $_SESSION['room_code'] = strtoupper($roomCode);
        $_SESSION['logged_in_via_login'] = true;
        
        return 'player';
    }
    
    /**
     * Logout
     * @return bool true on success
     */
    public function logout() {
        session_destroy();
        return true;
    }
}
