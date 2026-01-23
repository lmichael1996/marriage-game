<?php

class AuthHelper {
    /**
     * Check if user is logged in (admin or player)
     */
    public static function requireLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['logged_in_via_login']) || 
            (!isset($_SESSION['user_id']) && !isset($_SESSION['player_id']))) {
            header('Location: ../index.php');
            exit();
        }
    }
    
    /**
     * Check if user is admin
     */
    public static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['logged_in_via_login']) || 
            !isset($_SESSION['user_id']) || 
            isset($_SESSION['player_id'])) {
            header('Location: admin_login.php');
            exit();
        }
    }
    
    /**
     * Check if user is player
     */
    public static function requirePlayer() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['logged_in_via_login']) || 
            !isset($_SESSION['player_id']) || 
            !isset($_SESSION['room_code'])) {
            header('Location: player_login.php');
            exit();
        }
    }
    
    /**
     * Get current user ID (works for both admin and player)
     */
    public static function getUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_id'] ?? $_SESSION['player_id'] ?? null;
    }
    
    /**
     * Check if current user is admin
     */
    public static function isAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']) && !isset($_SESSION['player_id']);
    }
    
    /**
     * Check if current user is player
     */
    public static function isPlayer() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['player_id']);
    }
}
