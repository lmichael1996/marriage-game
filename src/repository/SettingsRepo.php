<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for the `game_settings` table.
 */
class SettingsRepo {
    private mysqli $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Get a single setting value by key. Returns null if not found.
     *
     * @param string $key  Setting key
     * @return string|null Setting value or null
     */
    public function getSetting(string $key): ?string {
        $stmt = $this->conn->prepare("SELECT setting_value FROM game_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? $row['setting_value'] : null;
    }

    /**
     * Get all settings as associative array [setting_key => setting_value]
     *
     * @return array<string, string>  All settings
     */
    public function getAllSettings(): array {
        $result = $this->conn->query("SELECT setting_key, setting_value FROM game_settings");
        $settings = [];

        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    /**
     * Insert or update a single game setting (used internally).
     *
     * @param string $key    Setting key
     * @param string $value  Setting value
     * @return bool          true on success
     */
    private function saveSetting(string $key, string $value): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO game_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->bind_param("sss", $key, $value, $value);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Insert or update only the settings that actually changed
     *
     * @param array<string, string> $settings  [setting_key => setting_value]
     * @return bool                             false if any upsert failed
     */
    public function saveSettings(array $settings): bool {
        // Get current settings to compare and avoid unnecessary updates
        $current = $this->getAllSettings();
        $success = true;

        foreach ($settings as $key => $value) {
            if (($current[$key] ?? null) !== $value) {
                if (!$this->saveSetting($key, $value)) {
                    $success = false;
                }
            }
        }

        return $success;
    }
}
