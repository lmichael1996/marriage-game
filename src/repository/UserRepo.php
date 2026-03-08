<?php
require_once __DIR__ . '/../config/database.php';

class UserRepo {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    public function authenticate($username, $password) {
        $stmt = $this->conn->prepare("SELECT id, username, user_password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['user_password'])) {
                $stmt->close();
                return $user;
            }
        }

        $stmt->close();
        return false;
    }

    public function getUserById($userId) {
        $stmt = $this->conn->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    public function updateCredentials($userId, $newUsername, $newPassword = null) {
        if ($newPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
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
