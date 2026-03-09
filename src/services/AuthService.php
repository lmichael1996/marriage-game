<?php
require_once __DIR__ . '/../repository/UserRepo.php';
require_once __DIR__ . '/../repository/PlayerRepo.php';
require_once __DIR__ . '/../repository/RoomRepo.php';
require_once __DIR__ . '/../repository/JudgeRepo.php';

/**
 * Authentication and admin credentials service.
 * Session is started centrally by auth.php.
 */
class AuthService {
    private UserRepo $userRepo;
    private PlayerRepo $playerRepo;
    private RoomRepo $roomRepo;
    private JudgeRepo $judgeRepo;

    private const int MIN_PASSWORD_LENGTH = 6;
    private const int ADMIN_SESSION_LIFETIME = 86400; // 24 hours

    public function __construct() {
        $this->userRepo = new UserRepo();
        $this->playerRepo = new PlayerRepo();
        $this->roomRepo = new RoomRepo();
        $this->judgeRepo = new JudgeRepo();
    }

    // ── Login ────────────────────────────────────────────────────────────

    /**
     * Admin login via username/password.
     *
     * @param string $username  Admin username
     * @param string $password  Plain text password
     * @return array  ['success' => true] or ['success' => false, 'error' => '...']
     */
    public function adminLogin(string $username, string $password): array {
        $userId = $this->userRepo->authenticate($username, $password);
        if (!$userId) {
            return [
                'success' => false,
                'error' => 'Nome utente o password non validi'
            ];
        }

        $_SESSION['auth_admin'] = [
            'role'     => 'admin',
            'user_id'  => $userId,
            'username' => $username,
        ];

        $_SESSION['admin_tab'] = 'sets';

        // Extend session cookie to 24h so admin survives browser close
        setcookie(session_name(), session_id(), [
            'expires'  => time() + self::ADMIN_SESSION_LIFETIME,
            'path'     => '/',
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);

        return ['success' => true];
    }

    /**
     * Player login via username and room code.
     * Creates a new player record in the room.
     *
     * @param string $username  Player display name
     * @param string $roomCode  Room player code
     * @return array  ['success' => true] or ['success' => false, 'error' => '...']
     */
    public function playerLogin(string $username, string $roomCode): array {
        $username = trim($username);
        $roomCode = strtoupper(trim($roomCode));

        if (empty($username)) return [
            'success' => false,
            'error' => 'Il nome del giocatore è obbligatorio'
        ];
        if (empty($roomCode)) return [
            'success' => false,
            'error' => 'Il codice della stanza è obbligatorio'
        ];

        $room = $this->roomRepo->getRoomByPlayerCode($roomCode);
        if (!$room) return [
            'success' => false,
            'error' => 'Codice della stanza non valido'
        ];

        $status = $room['status_room'];
        if ($status === 'running') return [
            'success' => false,
            'error' => 'La partita è già in corso'
        ];
        if ($status !== 'open') return [
            'success' => false,
            'error' => 'La stanza non è più disponibile'
        ];

        $result = $this->playerRepo->createPlayer($username, $room['id']);
        if (is_array($result)) return $result;

        $_SESSION['auth_player'] = [
            'role'      => 'player',
            'player_id' => (int)$result,
            'username'  => $username,
            'room_code' => $roomCode,
            'room_id'   => (int)$room['id'],
        ];

        return ['success' => true];
    }

    /**
     * Judge login via room code.
     * Creates a new judge record for the room (one per room).
     *
     * @param string $roomCode  Room judge code
     * @return array  ['success' => true] or ['success' => false, 'error' => '...']
     */
    public function judgeLogin(string $roomCode): array {
        $roomCode = strtoupper(trim($roomCode));
        if (empty($roomCode)) return [
            'success' => false,
            'error' => 'Il codice della stanza è obbligatorio'
        ];

        $room = $this->roomRepo->getRoomByJudgeCode($roomCode);
        if (!$room) {
            return [
                'success' => false,
                'error' => 'Codice giudice non valido'
            ];
        }

        $status = $room['status_room'];
        if ($status === 'running') {
            return [
                'success' => false,
                'error' => 'La partita è già in corso'
            ];
        }
        if ($status !== 'open') {
            return [
                'success' => false,
                'error' => 'La stanza non è più disponibile'
            ];
        }

        $result = $this->judgeRepo->createJudge($room['id']);
        if (is_array($result)) return $result;

        $_SESSION['auth_judge'] = [
            'role'      => 'judge',
            'judge_id'  => (int)$result,
            'room_code' => $roomCode,
            'room_id'   => (int)$room['id'],
        ];

        return ['success' => true];
    }

    // ── Logout ───────────────────────────────────────────────────────────

    /**
     * Logout a specific role or destroy the entire session.
     *
     * @param string|null $role  Role to logout (admin/player/judge), or null for full session destroy
     * @return bool              Always true
     */
    public function logout(?string $role = null): bool {
        if ($role) {
            unset($_SESSION['auth_' . $role]);
            if ($role === 'admin') {
                unset($_SESSION['active_room_code'], $_SESSION['active_judge_code'], $_SESSION['room_id']);
                // Reset cookie to session-only (dies on browser close)
                setcookie(session_name(), session_id(), [
                    'expires'  => 0,
                    'path'     => '/',
                    'httponly'  => true,
                    'samesite' => 'Lax',
                ]);
            }
        } else {
            session_destroy();
        }
        return true;
    }

    // ── Session getters ──────────────────────────────────────────────────

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

    // ── Admin credentials ──────────────────────────────────────────────

    /**
     * Update admin credentials (username and/or password).
     *
     * @param int         $userId          User ID
     * @param string      $newUsername      New username (empty = keep current)
     * @param string|null $newPassword      New password (null = keep current)
     * @param string|null $confirmPassword  Password confirmation
     * @return array  ['success' => true, 'username' => string] or ['success' => false, 'error' => '...']
     */
    public function updateCredentials(int $userId, string $newUsername, ?string $newPassword = null, ?string $confirmPassword = null): array {
        if (empty($newUsername)) {
            $currentUser = $this->userRepo->getUserById($userId);
            if (!$currentUser) {
                return [
                    'success' => false,
                    'error' => 'Utente non trovato'
                ];
            }
            $newUsername = $currentUser['username'];
        }

        if (!empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                return [
                    'success' => false,
                    'error' => 'Password e conferma non corrispondono'
                ];
            }
            if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
                return [
                    'success' => false,
                    'error' => 'La password deve contenere almeno ' . self::MIN_PASSWORD_LENGTH . ' caratteri'
                ];
            }
        }

        $success = $this->userRepo->updateCredentials($userId, $newUsername, $newPassword);
        if (!$success) {
            return [
                'success' => false,
                'error' => 'Impossibile aggiornare le credenziali'
            ];
        }

        return [
            'success' => true,
            'username' => $newUsername
        ];
    }

    /**
     * Update admin session data after credential change.
     *
     * @param int    $userId    User ID
     * @param string $username  New username
     */
    public function updateAdminSession(int $userId, string $username): void {
        $_SESSION['auth_admin'] = [
            'role'     => 'admin',
            'user_id'  => $userId,
            'username' => $username,
        ];
    }
}
