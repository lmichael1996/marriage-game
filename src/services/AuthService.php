<?php
require_once __DIR__ . '/../repository/UserRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/../repository/TokenRepo.php';

/**
 * Auth - Gestisce la logica di autenticazione
 * Setta cookie DB token per identità, sessione PHP solo per stato UI.
 */
class AuthService {
    private $userRepo;
    private $playerRepo;
    private $roomRepo;
    private $tokenRepo;

    public function __construct() {
        $this->userRepo = new UserRepo();
        $this->playerRepo = new PlayerRepo();
        $this->roomRepo = new RoomRepo();
        $this->tokenRepo = new TokenRepo();
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

        // Cookie DB token per identità admin
        $this->tokenRepo->setAdminCookie((int)$user['id'], $user['username']);

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
        if ($roomExists['winner_id'] !== null) {
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

        // Cookie DB token per identità player
        $this->tokenRepo->setPlayerCookie($playerId, $username, $roomCode);

        return 'player';
    }

    /**
     * Judge login with room code (code_judge)
     * @return string 'judge' on success
     * @throws Exception on failure
     */
    public function judgeLogin($roomCode) {
        $roomCode = strtoupper(trim($roomCode));

        if (empty($roomCode)) {
            throw new Exception('Codice stanza obbligatorio');
        }

        // Cerca stanza tramite code_judge
        $room = $this->roomRepo->getRoomByCode($roomCode);
        if (!$room || $room['code_judge'] !== $roomCode) {
            throw new Exception('Codice giudice non valido');
        }

        // Verifica stato stanza (accetta open e running)
        if (!in_array($room['status_room'], ['open', 'running'])) {
            $statusMessage = match($room['status_room']) {
                'cancelled' => 'La stanza è stata cancellata',
                'closed' => 'La stanza è stata chiusa',
                default => 'Lo stato della stanza non consente l\'ingresso'
            };
            throw new Exception($statusMessage);
        }

        // Crea record giudice
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO judges (room_id) VALUES (?)");
        $stmt->bind_param('i', $room['id']);
        $stmt->execute();
        $judgeId = $db->insert_id;
        $stmt->close();
        $db->close();

        if (!$judgeId) {
            throw new Exception('Errore durante la creazione del giudice');
        }

        // Cookie DB token per identità judge
        $this->tokenRepo->setJudgeCookie($judgeId, $roomCode);

        return 'judge';
    }

    /**
     * Logout — cancella cookie auth + distrugge sessione
     * @return bool true on success
     */
    public function logout() {
        $this->tokenRepo->clearAll();
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
        return true;
    }

    // ── Verifica autenticazione ──────────────────────────────────────────

    /** Ritorna il payload admin dal cookie, o null. */
    public function getAdmin(): ?array {
        return $this->tokenRepo->getAdmin();
    }

    /** Ritorna il payload player dal cookie, o null. */
    public function getPlayer(): ?array {
        return $this->tokenRepo->getPlayer();
    }

    /** Ritorna il payload judge dal cookie, o null. */
    public function getJudge(): ?array {
        return $this->tokenRepo->getJudge();
    }

    /** Ritorna il payload di qualsiasi utente autenticato, o null. */
    public function getAnyUser(): ?array {
        return $this->tokenRepo->getAnyUser();
    }

    /** Setta il cookie admin (utile per aggiornamento credenziali). */
    public function setAdminCookie(int $userId, string $username): void {
        $this->tokenRepo->setAdminCookie($userId, $username);
    }

    /** Cancella tutti i cookie auth e token dal DB. */
    public function clearAll(): void {
        $this->tokenRepo->clearAll();
    }
}
