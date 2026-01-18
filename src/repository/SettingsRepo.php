<?php
require_once __DIR__ . '/../config/database.php';

class SettingsRepo {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    /**
     * Get a single setting value
     */
    public function getSetting($key) {
        $stmt = $this->conn->prepare("SELECT setting_value FROM game_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['setting_value'] : null;
    }
    
    /**
     * Get all settings as key-value array
     */
    public function getAllSettings() {
        $result = $this->conn->query("SELECT setting_key, setting_value FROM game_settings");
        $settings = [];
        
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }
    
    /**
     * Save or update a setting
     */
    public function saveSetting($key, $value) {
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
     * Save multiple settings at once
     */
    public function saveSettings($settings) {
        $success = true;
        
        foreach ($settings as $key => $value) {
            if (!$this->saveSetting($key, $value)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Update timer in game_state
     */
    public function updateTimer($seconds) {
        $stmt = $this->conn->prepare("UPDATE game_state SET timer_seconds = ?");
        $stmt->bind_param("i", $seconds);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get timer from game_state
     */
    public function getTimer() {
        $result = $this->conn->query("SELECT timer_seconds FROM game_state LIMIT 1");
        $row = $result->fetch_assoc();
        
        return $row ? (int)$row['timer_seconds'] : 0;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
