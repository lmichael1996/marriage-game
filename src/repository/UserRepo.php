<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for the `users` table.
 */
class UserRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Authenticate user by username and password (Argon2ID).
     *
     * @param string $username  Username
     * @param string $password  Plain text password
     * @return int|false        User ID or false on failure
     */
    public function authenticate(string $username, string $password): int|false {
        $stmt = $this->conn->prepare("SELECT id, user_password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['user_password'])) {
                $stmt->close();
                return (int)$user['id'];
            }
        }

        $stmt->close();
        return false;
    }

    /**
     * Get user by ID.
     *
     * @param int $userId  User ID
     * @return array|false User row [id, username] or false
     */
    public function getUserById(int $userId): array|false {
        $stmt = $this->conn->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    /**
     * Update username and optionally password (hashed with Argon2ID).
     *
     * @param int         $userId       User ID
     * @param string      $newUsername   New username
     * @param string|null $newPassword   New plain text password, or null to keep current
     * @return bool                      true on success
     */
    public function updateCredentials(int $userId, string $newUsername, ?string $newPassword = null): bool {
        if ($newPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
            $stmt = $this->conn->prepare("UPDATE users SET username = ?, user_password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $newUsername, $hashedPassword, $userId);
        } else {
            $stmt = $this->conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $newUsername, $userId);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
?>
