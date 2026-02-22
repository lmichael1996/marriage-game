<?php
// Test per verificare la creazione della stanza e del file QR

session_start();
$_SESSION['admin_logged_in'] = true;

require_once __DIR__ . '/src/services/RoomService.php';

$room = new RoomService();

echo "Testing QR creation...\n";
$result = $room->createRoom(null);

echo "Result: ";
print_r($result);

if ($result['success']) {
    $code = $result['room_code'];
    $qrFile = __DIR__ . '/public/qrcodes/room_' . $code . '.jpg';
    echo "\nChecking file: $qrFile\n";
    echo "File exists: " . (file_exists($qrFile) ? "YES" : "NO") . "\n";

    if (file_exists($qrFile)) {
        echo "File size: " . filesize($qrFile) . " bytes\n";
    }

    $roomData = $room->getRoomByCode($code);
    echo "\nRoom from DB: ";
    print_r($roomData);
}
?>
