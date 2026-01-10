<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/PlayerAnswer.php';

requireLogin();

header('Content-Type: application/json');

$playerAnswerModel = new PlayerAnswer();
$leaderboard = $playerAnswerModel->getLeaderboard();

echo json_encode([
    'success' => true,
    'leaderboard' => $leaderboard
]);
?>
