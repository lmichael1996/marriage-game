<?php
require_once __DIR__ . '/../config/database.php';

class Room {
    private static function getRoomFile() {
        return sys_get_temp_dir() . '/marriage_game_active_room.json';
    }
    
    public static function createRoom($code, $adminId) {
        $roomData = [
            'code' => strtoupper($code),
            'admin_id' => $adminId,
            'created_at' => time()
        ];
        
        file_put_contents(self::getRoomFile(), json_encode($roomData));
        return true;
    }
    
    public static function getActiveRoom() {
        $roomFile = self::getRoomFile();
        if (file_exists($roomFile)) {
            $data = json_decode(file_get_contents($roomFile), true);
            return $data;
        }
        return null;
    }
    
    public static function closeRoom() {
        $roomFile = self::getRoomFile();
        if (file_exists($roomFile)) {
            unlink($roomFile);
        }
        return true;
    }
    
    public static function verifyRoomCode($code) {
        $room = self::getActiveRoom();
        if ($room && strtoupper($code) === $room['code']) {
            return true;
        }
        return false;
    }
}
