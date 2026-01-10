<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Round.php';

requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$roundModel = new Round();

if ($action === 'get_game_state') {
    $activeRound = $roundModel->getActiveRound();
    
    echo json_encode([
        'success' => true,
        'active_round' => $activeRound
    ]);
    exit();
}

echo json_encode([
    'success' => false,
    'message' => 'Azione non valida'
]);
?>
