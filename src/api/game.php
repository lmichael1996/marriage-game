<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Round.php';
require_once __DIR__ . '/../models/QuestionSet.php';

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

if ($action === 'get_set') {
    $setId = $_GET['set_id'] ?? 0;
    
    if (!$setId) {
        echo json_encode([
            'success' => false,
            'message' => 'Set ID mancante'
        ]);
        exit();
    }
    
    $questionSetModel = new QuestionSet();
    $set = $questionSetModel->getSetById($setId);
    
    if ($set) {
        echo json_encode([
            'success' => true,
            'set' => $set,
            'rounds' => $set['rounds'] ?? []
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Set non trovato'
        ]);
    }
    exit();
}

if ($action === 'start_round') {
    $roundId = $_GET['round_id'] ?? 0;
    
    if (!$roundId) {
        echo json_encode([
            'success' => false,
            'message' => 'Round ID mancante'
        ]);
        exit();
    }
    
    $success = $roundModel->startRound($roundId);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Round avviato' : 'Errore nell\'avvio del round'
    ]);
    exit();
}

echo json_encode([
    'success' => false,
    'message' => 'Azione non valida'
]);
?>
