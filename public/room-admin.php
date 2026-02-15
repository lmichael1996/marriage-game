<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/services/QuestionService.php';
require_once __DIR__ . '/../src/services/RoomService.php';

requireAdmin();

$questionService = new QuestionService();
$roomService = new RoomService();

// Get room code
$roomCode = $_GET['room_code'] ?? $_SESSION['room_code'] ?? null;
$room = $roomService->getRoomDetails($roomCode);
if (!$room) {
    header('Location: admin.php');
    exit;
}

// POST handler: Increment counter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'increment_counter') {
    $key = 'round_counter_' . $roomCode;
    $_SESSION[$key] = ($_SESSION[$key] ?? 1) + 1;
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Get counter (increments every time admin clicks "Prossima Domanda")
$counterKey = 'round_counter_' . $roomCode;
$counter = $_SESSION[$counterKey] ?? 1;

// Get question by counter position
$question = $questionService->getQuestionByCounter($room['qset_id'], $counter);

// Get active round from DB for this question
$activeRound = null;
if ($question) {
    $allRounds = $roomService->getActiveRound($room['id']);
    if ($allRounds && $allRounds['question_id'] == $question['id']) {
        $activeRound = $allRounds;
    }
}

// Game over: No question at current counter = we've finished all questions
$gameOver = !$question;

// Room info
$roomInfoKey = 'room_info_' . $roomCode;
if (!isset($_SESSION[$roomInfoKey])) {
    $_SESSION[$roomInfoKey] = [
        'num_players' => count($roomService->getRoomPlayers($roomCode) ?? []),
        'total_questions' => $questionService->getQuestionCountByQset($room['qset_id'])
    ];
}
$roomInfo = $_SESSION[$roomInfoKey];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/game_admin.css">
    <link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Gestione Partita</h1>
            <button class="btn btn-secondary" onclick="goBack()">← Torna a Admin</button>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; width: 100%; background: white; padding: 30px; border-radius: 8px;">
            <div>
                <div class="info-box">
                    <p><strong>👥 Giocatori:</strong> <?php echo $roomInfo['num_players']; ?></p>
                    <p><strong>🎯 Room Code:</strong> <code><?php echo htmlspecialchars($roomCode); ?></code></p>
                    <p><strong>📊 Domande:</strong> <?php echo $roomInfo['total_questions']; ?></p>
                    <p><strong>Domanda Attuale:</strong> <?php echo $counter; ?></p>
                </div>

                <?php if ($gameOver): ?>
                    <div class="game-step" style="background: #e8f5e9; border-color: #28a745;">
                        <h3 style="color: #28a745;">🎉 Partita Terminata!</h3>
                        <div id="final-leaderboard" style="margin-top: 20px;">
                            <p>Caricamento classifica...</p>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <button class="btn btn-secondary" onclick="goBack()">← Torna</button>
                        </div>
                    </div>
                <?php elseif ($activeRound): ?>
                    <!-- Active round: show timer, options, and "Prossima Domanda" button -->
                    <div class="game-step">
                        <h3>📋 Round Attivo: #<?php echo $activeRound['round_number']; ?></h3>
                        <p><strong><?php echo htmlspecialchars($activeRound['question']); ?></strong></p>

                        <?php if ($activeRound['round_type'] !== 'clickfirst'): ?>
                            <div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                                <strong style="display: block; margin-bottom: 15px;">📋 Risposte:</strong>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div id="option-1" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer;">
                                        <?php echo htmlspecialchars($activeRound['option1']); ?>
                                    </div>
                                    <div id="option-2" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer;">
                                        <?php echo htmlspecialchars($activeRound['option2']); ?>
                                    </div>
                                    <?php if ($activeRound['round_type'] === 'multiple'): ?>
                                        <div id="option-3" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer;">
                                            <?php echo htmlspecialchars($activeRound['option3']); ?>
                                        </div>
                                        <div id="option-4" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer;">
                                            <?php echo htmlspecialchars($activeRound['option4']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p style="color: #ffc107; font-weight: bold;">⚡ Chi clicca primo vince!</p>
                        <?php endif; ?>

                        <div class="info-box" style="text-align: center; margin: 20px 0;">
                            <h4>⏱️ Timer: <span id="timer" style="font-size: 2.5em; color: #28a745;"><?php echo $activeRound['timer'] ?? 30; ?></span>s</h4>
                        </div>
                        <div style="text-align: center;">
                            <button class="btn btn-success" id="next-btn" onclick="nextQuestion()" disabled style="padding: 15px 30px;">
                                ➡️ Prossima Domanda
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- No active round: show "AVVIA ROUND" button with options preview -->
                    <div class="game-step">
                        <h3>📋 Domanda #<?php echo $counter; ?></h3>
                        <p><strong><?php echo htmlspecialchars($question['question']); ?></strong></p>

                        <?php if ($question['round_type'] !== 'clickfirst'): ?>
                            <div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                                <strong style="display: block; margin-bottom: 15px;">📋 Risposte:</strong>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center;">
                                        <?php echo htmlspecialchars($question['option1']); ?>
                                    </div>
                                    <div style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center;">
                                        <?php echo htmlspecialchars($question['option2']); ?>
                                    </div>
                                    <?php if ($question['round_type'] === 'multiple'): ?>
                                        <div style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center;">
                                            <?php echo htmlspecialchars($question['option3']); ?>
                                        </div>
                                        <div style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center;">
                                            <?php echo htmlspecialchars($question['option4']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p style="color: #ffc107; font-weight: bold; margin: 20px 0;">⚡ Chi clicca primo vince!</p>
                        <?php endif; ?>

                        <div style="text-align: center; margin-top: 20px;">
                            <button class="btn btn-primary" onclick="startRound(<?php echo $question['id']; ?>)" style="padding: 15px 40px;">
                                ▶ AVVIA ROUND
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="position: sticky; top: 20px;">
                <h3>🏆 Classifica</h3>
                <div id="leaderboard" style="display: grid; gap: 10px;">
                    <p>Nessun dato</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const roomCode = '<?php echo $roomCode; ?>';
        let timerInterval = null;

        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($activeRound): ?>
            startCountdown(<?php echo $activeRound['timer'] ?? 30; ?>);
            <?php endif; ?>

            <?php if ($gameOver): ?>
            loadFinalLeaderboard();
            <?php endif; ?>
        });

        function startCountdown(seconds) {
            let timeLeft = seconds;
            const timerEl = document.getElementById('timer');
            const nextBtn = document.getElementById('next-btn');
            let correctAnswer = null;
            let isClickFirst = false;

            timerInterval = setInterval(() => {
                timeLeft--;
                timerEl.textContent = timeLeft;

                if (timeLeft <= 5) timerEl.style.color = '#dc143c';
                else if (timeLeft <= 10) timerEl.style.color = '#ffc107';

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);

                    // After 2 seconds, show correct answer
                    setTimeout(() => {
                        const correctAnswer = <?php echo isset($activeRound['correct_answer']) ? $activeRound['correct_answer'] : 'null'; ?>;
                        const isClickFirst = '<?php echo isset($activeRound['round_type']) ? $activeRound['round_type'] : ''; ?>' === 'clickfirst';

                        // Show correct answer for non-clickfirst rounds
                        if (!isClickFirst && correctAnswer) {
                            const correctOption = document.querySelector('#option-' + correctAnswer);
                            if (correctOption) {
                                correctOption.style.background = '#4caf50';
                            }
                        }

                        // Load top answers when timer expires
                        const roundId = <?php echo isset($activeRound['id']) ? $activeRound['id'] : 'null'; ?>;
                        if (roundId) {
                            loadRoundAnswers(roundId);
                        }

                        // Enable next button
                        if (nextBtn) {
                            nextBtn.disabled = false;
                            nextBtn.style.animation = 'pulse 1s infinite';
                        }
                    }, 2000);
                }
            }, 1000);
        }

        function startRound(questionId) {
            fetch('../src/api/api.php?endpoint=game&action=start_round', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question_id: questionId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.href = 'room-admin.php?room_code=' + encodeURIComponent(roomCode);
                }
            });
        }

        function nextQuestion() {
            const form = new FormData();
            form.append('action', 'increment_counter');

            fetch('room-admin.php?room_code=' + encodeURIComponent(roomCode), {
                method: 'POST',
                body: form
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.href = 'room-admin.php?room_code=' + encodeURIComponent(roomCode);
                }
            });
        }

        function loadFinalLeaderboard() {
            fetch('../src/api/api.php?endpoint=final_leaderboard')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.leaderboard?.length > 0) {
                        let html = '';
                        data.leaderboard.forEach((p) => {
                            html += `<div style="padding: 15px; background: #f5f5f5; border-radius: 6px; text-align: center; margin-bottom: 10px;">
                                <div style="font-size: 2em;">${p.medal}</div>
                                <div style="font-size: 1.2em; font-weight: bold;">${p.username}</div>
                                <div style="color: #28a745; font-weight: bold;">${p.score} punti</div>
                            </div>`;
                        });
                        document.getElementById('final-leaderboard').innerHTML = html;
                    }
                });
        }

        function loadRoundAnswers(roundId) {
            fetch('../src/api/api.php?endpoint=round_answers&round_id=' + roundId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.top_answers?.length > 0) {
                        let html = '';
                        data.top_answers.forEach((answer, i) => {
                            const medal = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : (i + 1) + '.'));
                            const time = parseFloat(answer.answer_time).toFixed(2) + 's';
                            html += `<div style="padding: 15px; background: #f5f5f5; border-radius: 6px; text-align: center; margin-bottom: 10px;">
                                <div style="font-size: 1.5em; font-weight: bold;">${medal}</div>
                                <div style="font-size: 1em;">${answer.username}</div>
                                <div style="font-size: 0.9em; color: #666; margin-top: 4px;">${time}</div>
                            </div>`;
                        });
                        document.getElementById('leaderboard').innerHTML = html;
                    }
                });
        }

        function goBack() {
            if (confirm('Termina partita?')) {
                window.location.href = 'admin.php';
            }
        }
    </script>
</body>
</html>
