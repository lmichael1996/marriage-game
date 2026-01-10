<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Round.php';
require_once __DIR__ . '/../models/PlayerAnswer.php';

requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'submit') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $round_id = $data['round_id'] ?? 0;
    $answer = $data['answer'] ?? 0;
    $time_taken = $data['time_taken'] ?? 0;
    $user_id = $_SESSION['user_id'];
    
    $playerAnswerModel = new PlayerAnswer();
    $roundModel = new Round();
    
    // Check if user already answered this round
    if ($playerAnswerModel->hasAnswered($round_id, $user_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Hai già risposto a questo round'
        ]);
        exit();
    }
    
    // Get correct answer
    $round = $roundModel->getRoundById($round_id);
    
    if (!$round) {
        echo json_encode([
            'success' => false,
            'message' => 'Round non trovato'
        ]);
        exit();
    }
    
    $is_correct = ($answer == $round['correct_answer']) ? 1 : 0;
    
    // Save answer
    $playerAnswerModel->submitAnswer($round_id, $user_id, $answer, $time_taken, $is_correct);
    
    echo json_encode([
        'success' => true,
        'is_correct' => $is_correct
    ]);
    exit();
}

echo json_encode([
    'success' => false,
    'message' => 'Azione non valida'
]);
?>
