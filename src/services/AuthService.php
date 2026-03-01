<?php
require_once __DIR__ . '/../repository/UserRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/TokenService.php';

/**
 * Auth - Gestisce la logica di autenticazione
 * Setta cookie HMAC firmati per identità, sessione PHP solo per stato UI.
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

        // Cookie HMAC per identità admin
        TokenService::setAdminCookie((int)$user['id'], $user['username']);

        return 'admin';
    }

    /**
     * Player login with room code
     * @return string 'player' on success
     * @throws Exception on failure
     */
    public function playerLogin($username, $roomCode) {
        // Trim and validate input
        $username = trim($username);
        $roomCode = strtoupper(trim($roomCode));

        if (empty($username)) {
            throw new Exception('Nome giocatore obbligatorio');
        }

        if (empty($roomCode)) {
            throw new Exception('Codice stanza obbligatorio');
        }

        // Check if room exists (any status)
        $roomExists = $this->roomRepo->getRoomByCode($roomCode);
        if (!$roomExists) {
            throw new Exception('Codice stanza non valido');
        }

        // Check if room is in 'open' status
        if ($roomExists['status_room'] !== 'open') {
            $statusMessage = match($roomExists['status_room']) {
                'running' => 'La partita è già iniziata, non puoi unirti',
                'cancelled' => 'La stanza è stata cancellata',
                'closed' => 'La stanza è stata chiusa',
                default => 'Lo stato della stanza non consente l\'ingresso'
            };
            throw new Exception($statusMessage);
        }

        // Verifica se la stanza ha già un vincitore (partita terminata)
        $winner = $this->roomRepo->getWinner($roomExists['id']);
        if ($winner) {
            throw new Exception('La partita in questa stanza è già terminata');
        }

        // Create new player associated with this room
        try {
            $playerId = $this->playerRepo->createPlayer($username, $roomExists['id']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (!$playerId) {
            throw new Exception('Errore durante la creazione del giocatore');
        }

        // Cookie HMAC per identità player
        TokenService::setPlayerCookie($playerId, $username, $roomCode);

        return 'player';
    }

    /**
     * Logout — cancella cookie auth + distrugge sessione
     * @return bool true on success
     */
    public function logout() {
        TokenService::clearAll();
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
        return true;
    }
}
