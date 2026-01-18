<?php
require_once __DIR__ . '/../services/AuthService.php';

class AuthController {
    private $auth;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->authService = new AuthService();
    }
    
    /**
     * Handle login - decide which type and format response
     * @return array with 'success', 'redirect', and 'error' keys
     */
    public function login($username, $password, $roomCode) {
        try {
            // Validate input
            if (!$username) {
                return [
                    'success' => false,
                    'error' => 'Inserisci username'
                ];
            }
            
            // Admin login (username + password)
            if ($password) {
                $userType = $this->authService->adminLogin($username, $password);
                return [
                    'success' => true,
                    'redirect' => 'admin.php'
                ];
            }
            // Player login (username + room code)
            elseif ($roomCode) {
                $userType = $this->authService->playerLogin($username, $roomCode);
                return [
                    'success' => true,
                    'redirect' => 'player.php'
                ];
            }
            // Missing credentials
            else {
                return [
                    'success' => false,
                    'error' => 'Inserisci codice stanza o password per admin'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle logout
     * @return array with 'success' and 'redirect' keys
     */
    public function logout() {
        try {
            $this->authService->logout();
            return [
                'success' => true,
                'redirect' => 'login.php'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}