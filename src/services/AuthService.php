<?php
require_once __DIR__ . '/../repository/UserRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/../repository/JudgeRepo.php';

/**
 * AuthService — Autenticazione basata su sessione PHP.
 * La sessione viene avviata centralmente da auth.php.
 */
class AuthService {
    private $userRepo;
    private $playerRepo;
    private $roomRepo;
    private $judgeRepo;

    public function __construct() {
        $this->userRepo = new UserRepo();
        $this->playerRepo = new PlayerRepo();
        $this->roomRepo = new RoomRepo();
        $this->judgeRepo = new JudgeRepo();
    }

    // ── Login ────────────────────────────────────────────────────────────

    public function adminLogin(string $username, string $password): string {
        $user = $this->userRepo->authenticate($username, $password);
        if (!$user) {
            throw new Exception('Username o password non validi');
        }

        $_SESSION['auth_admin'] = [
            'role'     => 'admin',
            'user_id'  => (int)$user['id'],
            'username' => $user['username'],
        ];

        // Reset tab attiva → Domande dopo login
        $_SESSION['admin_tab'] = 'sets';

        return 'admin';
    }

    public function playerLogin(string $username, string $roomCode): string {
        $username = trim($username);
        $roomCode = strtoupper(trim($roomCode));

        if (empty($username)) throw new Exception('Nome giocatore obbligatorio');
        if (empty($roomCode)) throw new Exception('Codice stanza obbligatorio');

        $roomExists = $this->roomRepo->getRoomByCode($roomCode);
        if (!$roomExists) throw new Exception('Codice stanza non valido');

        if ($roomExists['status_room'] !== 'open') {
            $statusMessage = match($roomExists['status_room']) {
                'running'   => 'La partita è già iniziata, non puoi unirti',
                'cancelled' => 'La stanza è stata cancellata',
                'closed'    => 'La stanza è stata chiusa',
                default     => 'Lo stato della stanza non consente l\'ingresso'
            };
            throw new Exception($statusMessage);
        }

        if ($roomExists['winner_id'] !== null) {
            throw new Exception('La partita in questa stanza è già terminata');
        }

        try {
            $playerId = $this->playerRepo->createPlayer($username, $roomExists['id']);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (!$playerId) throw new Exception('Errore durante la creazione del giocatore');

        $_SESSION['auth_player'] = [
            'role'      => 'player',
            'player_id' => (int)$playerId,
            'username'  => $username,
            'room_code' => $roomCode,
        ];

        return 'player';
    }

    public function judgeLogin(string $roomCode): string {
        $roomCode = strtoupper(trim($roomCode));
        if (empty($roomCode)) throw new Exception('Codice stanza obbligatorio');

        $room = $this->roomRepo->getRoomByCode($roomCode);
        if (!$room || $room['code_judge'] !== $roomCode) {
            throw new Exception('Codice giudice non valido');
        }

        if (!in_array($room['status_room'], ['open', 'running'])) {
            $statusMessage = match($room['status_room']) {
                'cancelled' => 'La stanza è stata cancellata',
                'closed'    => 'La stanza è stata chiusa',
                default     => 'Lo stato della stanza non consente l\'ingresso'
            };
            throw new Exception($statusMessage);
        }

        $judgeId = $this->judgeRepo->createJudge($room['id']);

        if (!$judgeId) throw new Exception('Errore durante la creazione del giudice');

        $_SESSION['auth_judge'] = [
            'role'      => 'judge',
            'judge_id'  => (int)$judgeId,
            'room_code' => $roomCode,
            'room_id'   => (int)$room['id'],
        ];

        return 'judge';
    }

    // ── Logout ───────────────────────────────────────────────────────────

    public function logout(?string $role = null): bool {
        if ($role) {
            unset($_SESSION['auth_' . $role]);
            // Pulisci anche dati collegati al ruolo
            if ($role === 'admin') {
                unset($_SESSION['active_room_code'], $_SESSION['active_judge_code'], $_SESSION['room_id']);
            }
        } else {
            session_destroy();
        }
        return true;
    }

    // ── Verifica autenticazione ──────────────────────────────────────────

    public function getAdmin(): ?array {
        return $_SESSION['auth_admin'] ?? null;
    }

    public function getPlayer(): ?array {
        return $_SESSION['auth_player'] ?? null;
    }

    public function getJudge(): ?array {
        return $_SESSION['auth_judge'] ?? null;
    }

    public function getAnyUser(): ?array {
        return $this->getAdmin() ?? $this->getPlayer() ?? $this->getJudge();
    }

    public function updateAdminSession(int $userId, string $username): void {
        $_SESSION['auth_admin'] = [
            'role'     => 'admin',
            'user_id'  => $userId,
            'username' => $username,
        ];
    }
}
