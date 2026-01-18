<?php
require_once __DIR__ . '/../repository/UserRepo.php';
require_once __DIR__ . '/../repository/SettingsRepo.php';

/**
 * AdminService - Gestisce la logica di business per admin
 * Restituisce dati o lancia eccezioni, il controller gestisce gli errori
 */
class AdminService {
    private $userRepo;
    private $settingsRepo;
    
    public function __construct() {
        $this->userRepo = new UserRepo();
        $this->settingsRepo = new SettingsRepo();
    }
    
    /**
     * Update admin credentials
     * @return array with new username
     * @throws Exception on failure
     */
    public function updateCredentials($userId, $newUsername, $newPassword = null, $confirmPassword = null) {
        if (empty($newUsername)) {
            throw new Exception('Username obbligatorio');
        }
        
        // Validate password if provided
        if (!empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Le password non corrispondono');
            }
            if (strlen($newPassword) < 6) {
                throw new Exception('La password deve essere di almeno 6 caratteri');
            }
        }
        
        $success = $this->userRepo->updateCredentials($userId, $newUsername, $newPassword);
        
        if (!$success) {
            throw new Exception('Errore nell\'aggiornamento delle credenziali');
        }
        
        return ['username' => $newUsername];
    }
    
    /**
     * Save game settings
     * @return bool true on success
     * @throws Exception on failure
     */
    public function saveSettings($settingsData) {
        $settings = [
            'min_players' => intval($settingsData['min_players'] ?? 2),
            'max_players' => intval($settingsData['max_players'] ?? 50),
            'auto_next_round' => isset($settingsData['auto_next_round']) ? 1 : 0,
            'auto_next_delay' => intval($settingsData['auto_next_delay'] ?? 5),
            
            // Multiple Choice points
            'points_mult_1st' => intval($settingsData['points_mult_1st'] ?? 25),
            'points_mult_2nd' => intval($settingsData['points_mult_2nd'] ?? 18),
            'points_mult_3rd' => intval($settingsData['points_mult_3rd'] ?? 15),
            'points_mult_4th' => intval($settingsData['points_mult_4th'] ?? 12),
            'points_mult_5th' => intval($settingsData['points_mult_5th'] ?? 10),
            'points_mult_6th' => intval($settingsData['points_mult_6th'] ?? 8),
            'points_mult_7th' => intval($settingsData['points_mult_7th'] ?? 6),
            'points_mult_8th' => intval($settingsData['points_mult_8th'] ?? 4),
            'points_mult_9th' => intval($settingsData['points_mult_9th'] ?? 2),
            'points_mult_10th' => intval($settingsData['points_mult_10th'] ?? 1),
            
            // True/False points
            'points_tf_1st' => intval($settingsData['points_tf_1st'] ?? 20),
            'points_tf_2nd' => intval($settingsData['points_tf_2nd'] ?? 15),
            'points_tf_3rd' => intval($settingsData['points_tf_3rd'] ?? 12),
            'points_tf_4th' => intval($settingsData['points_tf_4th'] ?? 10),
            'points_tf_5th' => intval($settingsData['points_tf_5th'] ?? 8),
            'points_tf_6th' => intval($settingsData['points_tf_6th'] ?? 6),
            'points_tf_7th' => intval($settingsData['points_tf_7th'] ?? 5),
            'points_tf_8th' => intval($settingsData['points_tf_8th'] ?? 3),
            'points_tf_9th' => intval($settingsData['points_tf_9th'] ?? 2),
            'points_tf_10th' => intval($settingsData['points_tf_10th'] ?? 1),
            
            // Click First points
            'points_clickfirst' => intval($settingsData['points_clickfirst'] ?? 50),
            
            'show_leaderboard' => isset($settingsData['show_leaderboard']) ? 1 : 0,
            'show_correct_answer' => isset($settingsData['show_correct_answer']) ? 1 : 0
        ];
        
        $success = $this->settingsRepo->saveSettings($settings);
        
        if (!$success) {
            throw new Exception('Errore nel salvataggio delle impostazioni');
        }
        
        return true;
    }
    
    /**
     * Get all game settings
     * @return array
     */
    public function getAllSettings() {
        return $this->settingsRepo->getAllSettings();
    }
    
    /**
     * Update timer
     * @return bool
     * @throws Exception on failure
     */
    public function updateTimer($seconds) {
        $success = $this->settingsRepo->updateTimer($seconds);
        if (!$success) {
            throw new Exception('Errore nell\'aggiornamento del timer');
        }
        return true;
    }
    
    /**
     * Get timer
     * @return int
     */
    public function getTimer() {
        return $this->settingsRepo->getTimer();
    }
}
