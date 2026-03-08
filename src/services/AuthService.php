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
     * @throws Exception on invalid credentials
     */
    public function adminLogin(string $username, string $password): void {
        $userId = $this->userRepo->authenticate($username, $password);
        if (!$userId) {
            throw new Exception('Invalid username or password');
        }

        $_SESSION['auth_admin'] = [
            'role'     => 'admin',
            'user_id'  => $userId,
            'username' => $username,
        ];

        $_SESSION['admin_tab'] = 'sets';
    }

    /**
     * Player login via username and room code.
     * Creates a new player record in the room.
     *
     * @param string $username  Player display name
     * @param string $roomCode  Room player code
     * @throws Exception on invalid input, room not found, or duplicate username
     */
    public function playerLogin(string $username, string $roomCode): void {
        $username = trim($username);
        $roomCode = strtoupper(trim($roomCode));

        if (empty($username)) throw new Exception('Player name is required');
        if (empty($roomCode)) throw new Exception('Room code is required');

        $room = $this->roomRepo->getOpenRoomByPlayerCode($roomCode);
        if (!$room) throw new Exception('Invalid room code or room not available');

        $playerId = $this->playerRepo->createPlayer($username, $room['id']);
        if (!$playerId) throw new Exception('Failed to create player');

        $_SESSION['auth_player'] = [
            'role'      => 'player',
            'player_id' => (int)$playerId,
            'username'  => $username,
            'room_code' => $roomCode,
            'room_id'   => (int)$room['id'],
        ];
    }

    /**
     * Judge login via room code.
     * Creates a new judge record for the room (one per room).
     *
     * @param string $roomCode  Room judge code
     * @throws Exception on invalid code, room not found, or judge already connected
     */
    public function judgeLogin(string $roomCode): void {
        $roomCode = strtoupper(trim($roomCode));
        if (empty($roomCode)) throw new Exception('Room code is required');

        $room = $this->roomRepo->getRoomByJudgeCode($roomCode);
        if (!$room) {
            throw new Exception('Invalid judge code or room not available');
        }

        $judgeId = $this->judgeRepo->createJudge($room['id']);
        if (!$judgeId) throw new Exception('Failed to create judge');

        $_SESSION['auth_judge'] = [
            'role'      => 'judge',
            'judge_id'  => (int)$judgeId,
            'room_code' => $roomCode,
            'room_id'   => (int)$room['id'],
        ];
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
     * @return array  ['username' => string]
     * @throws Exception on validation or update failure
     */
    public function updateCredentials(int $userId, string $newUsername, ?string $newPassword = null, ?string $confirmPassword = null): array {
        if (empty($newUsername)) {
            $currentUser = $this->userRepo->getUserById($userId);
            if (!$currentUser) {
                throw new Exception('User not found');
            }
            $newUsername = $currentUser['username'];
        }

        if (!empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Passwords do not match');
            }
            if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
                throw new Exception('Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters');
            }
        }

        $success = $this->userRepo->updateCredentials($userId, $newUsername, $newPassword);
        if (!$success) {
            throw new Exception('Failed to update credentials');
        }

        return ['username' => $newUsername];
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
