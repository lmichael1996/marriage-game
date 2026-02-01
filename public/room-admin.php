<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/services/GameService.php';
require_once __DIR__ . '/../src/services/AdminService.php';
require_once __DIR__ . '/../src/services/QuestionService.php';
require_once __DIR__ . '/../src/repository/RoomRepo.php';
require_once __DIR__ . '/../src/repository/PlayerRepo.php';

requireAdmin();

$game = new GameService();
$admin = new AdminService();
$questionService = new QuestionService();
$roomRepo = new RoomRepo();
$playerRepo = new PlayerRepo();

$roomCode = $_SESSION['room_code'] ?? $_GET['room_code'] ?? null;
$room = null;
$questionSetId = null;

if ($roomCode && !isset($_SESSION['room_code'])) {
    $_SESSION['room_code'] = $roomCode;
}

if ($roomCode) {
    $room = $roomRepo->getRoomByCode($roomCode);
    if ($room) {
        $questionSetId = $room['question_set_id'];
    }
}

if (!$room) {
    header('Location: admin.php');
    exit;
}

$questions = [];
$setInfo = null;
if ($questionSetId) {
    $setInfo = $questionService->getQuestionSetWithQuestions($questionSetId);
    if ($setInfo) {
        $questions = $setInfo['questions'] ?? [];
    }
}

$activeRound = $game->getActiveRound($questionSetId);
$players = $playerRepo->getPlayersByRoom($roomCode) ?? [];

// Determine which question should be next
$nextQuestion = null;
$lastCompletedRound = $_SESSION['last_completed_round_' . $roomCode] ?? 0;

// If there's an active round, find next after that
if ($activeRound) {
    $activeRoundNumber = $activeRound['round_number'];
} else {
    // Otherwise use the last completed round number
    $activeRoundNumber = $lastCompletedRound;
}

// Find next question to display
foreach ($questions as $q) {
    if ($q['round_number'] > $activeRoundNumber) {
        $nextQuestion = $q;
        break;
    }
}

$gameOver = !$activeRound && !$nextQuestion;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/game_admin.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Gestione Partita</h1>
            <button class="btn btn-secondary" onclick="goBack()">← Torna a Admin</button>
        </div>

        <div class="admin-section">
            <h2><?php echo $setInfo ? htmlspecialchars($setInfo['set_name']) : 'Set Domande'; ?></h2>

            <div class="info-box">
                <p><strong>👥 Giocatori connessi:</strong> <?php echo count($players); ?></p>
                <p><strong>🎯 Room Code:</strong> <code><?php echo htmlspecialchars($roomCode); ?></code></p>
            </div>

            <?php if ($activeRound): ?>
                <div class="game-step">
                    <h3>📋 Round Attivo: #<?php echo $activeRound['round_number']; ?></h3>
                    <p><strong>Domanda:</strong> <?php echo htmlspecialchars($activeRound['question']); ?></p>

                    <?php if ($activeRound['round_type'] !== 'clickfirst'): ?>
                        <div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                            <strong style="display: block; margin-bottom: 15px; font-size: 1.1em;">📋 Risposte:</strong>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div id="option-1" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s; font-size: 0.95em;">
                                    <?php echo htmlspecialchars($activeRound['option1']); ?>
                                </div>
                                <div id="option-2" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s; font-size: 0.95em;">
                                    <?php echo htmlspecialchars($activeRound['option2']); ?>
                                </div>
                                <?php if ($activeRound['round_type'] === 'multiple'): ?>
                                    <div id="option-3" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s; font-size: 0.95em;">
                                        <?php echo htmlspecialchars($activeRound['option3']); ?>
                                    </div>
                                    <div id="option-4" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s; font-size: 0.95em;">
                                        <?php echo htmlspecialchars($activeRound['option4']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="color: #ffc107; font-weight: bold;">⚡ Chi clicca primo vince!</p>
                    <?php endif; ?>

                    <div class="info-box" style="margin-top: 20px; text-align: center;">
                        <h4 style="margin: 0 0 10px 0;">⏱️ Timer: <span id="timer" style="font-size: 3em; font-weight: bold; color: #28a745;"><?php echo isset($activeRound['timer']) ? $activeRound['timer'] : 30; ?></span>s</h4>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <button class="btn btn-success" id="next-btn" onclick="nextQuestion(<?php echo isset($activeRound['id']) ? $activeRound['id'] : '0'; ?>)" disabled style="font-size: 1.1em; padding: 15px 30px;">
                            ➡️ Prossima Domanda
                        </button>
                    </div>
                </div>
            <?php elseif ($nextQuestion): ?>
                <div class="game-step">
                    <h3>📋 Round #<?php echo $nextQuestion['round_number']; ?> - Pronto a iniziare</h3>
                    <p><strong>Domanda:</strong> <?php echo htmlspecialchars($nextQuestion['question']); ?></p>

                    <?php if ($nextQuestion['round_type'] !== 'clickfirst'): ?>
                        <div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                            <strong style="display: block; margin-bottom: 15px; font-size: 1.1em;">📋 Risposte:</strong>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div id="option-1" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s; font-size: 0.95em;">
                                    <?php echo htmlspecialchars($nextQuestion['option1']); ?>
                                </div>
                                <div id="option-2" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s; font-size: 0.95em;">
                                    <?php echo htmlspecialchars($nextQuestion['option2']); ?>
                                </div>
                                <?php if ($nextQuestion['round_type'] === 'multiple'): ?>
                                    <div id="option-3" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s; font-size: 0.95em;">
                                        <?php echo htmlspecialchars($nextQuestion['option3']); ?>
                                    </div>
                                    <div id="option-4" style="padding: 12px; background: #5B7FFF; border-radius: 6px; color: white; font-weight: 500; text-align: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s; font-size: 0.95em;">
                                        <?php echo htmlspecialchars($nextQuestion['option4']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="color: #ffc107; font-weight: bold; margin: 20px 0;">⚡ Chi clicca primo vince!</p>
                    <?php endif; ?>

                    <div style="text-align: center; margin-top: 20px;">
                        <button class="btn btn-primary" onclick="startRound(<?php echo isset($nextQuestion['id']) ? $nextQuestion['id'] : '0'; ?>)" style="font-size: 1.1em; padding: 15px 40px;">
                            ▶ AVVIA ROUND
                        </button>
                    </div>
                </div>
            <?php elseif ($gameOver): ?>
                <div class="game-step" style="background: #e8f5e9; border-color: #28a745;">
                    <h3 style="color: #28a745;">🎉 Partita Terminata!</h3>
                    <p>Tutti i round sono stati completati.</p>
                    <div id="final-leaderboard" style="margin-top: 20px;">
                        <p class="loading-text">Caricamento classifica...</p>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <button class="btn btn-secondary" onclick="goBack()" style="font-size: 1.1em; padding: 12px 30px;">
                            ← Torna a Admin
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-section" style="margin-top: 30px;">
            <h3>🏆 Classifica Attuale</h3>
            <div id="leaderboard" style="min-height: 100px;">
                <p class="loading-text">Caricamento classifica...</p>
            </div>
        </div>
    </div>

    <script>
        const roomCode = '<?php echo $roomCode; ?>';
        let timerInterval = null;

        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($activeRound): ?>
            console.log('Active round timer value:', <?php echo isset($activeRound['timer']) ? $activeRound['timer'] : 'null'; ?>);
            console.log('Active round data:', <?php echo json_encode($activeRound); ?>);
            const timerValue = <?php echo isset($activeRound['timer']) && $activeRound['timer'] !== null ? (int)$activeRound['timer'] : 'null'; ?>;
            console.log('Parsed timer value:', timerValue);
            startCountdown(timerValue || 10);
            <?php endif; ?>
            loadLeaderboard();
        });

        function startCountdown(seconds) {
            let timeLeft = parseInt(seconds);
            if (isNaN(timeLeft) || timeLeft <= 0) {
                timeLeft = 10;  // Default a 10 secondi se il timer è invalido
            }
            const timerEl = document.getElementById('timer');
            const nextBtn = document.getElementById('next-btn');
            let correctAnswerNum = null;
            let isClickFirst = false;

            console.log('startCountdown called with:', seconds, 'parsed to:', timeLeft);

            // Get active round data to find correct answer
            <?php if ($activeRound): ?>
            correctAnswerNum = <?php echo $activeRound['correct_answer'] ?? 1; ?>;
            isClickFirst = '<?php echo $activeRound['round_type']; ?>' === 'clickfirst';
            console.log('isClickFirst:', isClickFirst, 'correctAnswerNum:', correctAnswerNum);
            <?php endif; ?>

            timerInterval = setInterval(() => {
                timeLeft--;
                timerEl.textContent = timeLeft;

                if (timeLeft <= 5) {
                    timerEl.style.color = '#dc143c';
                } else if (timeLeft <= 10) {
                    timerEl.style.color = '#ffc107';
                }

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    console.log('Timer finished');

                    // Only show correct answer for non-clickfirst rounds
                    if (!isClickFirst) {
                        // After 2 seconds, show correct answer in green
                        setTimeout(() => {
                            if (correctAnswerNum) {
                                const correctOption = document.querySelector('#option-' + correctAnswerNum);
                                if (correctOption) {
                                    correctOption.style.background = '#4caf50';
                                }
                            }
                            // Enable next button after showing correct answer
                            if (nextBtn) {
                                nextBtn.disabled = false;
                                nextBtn.style.animation = 'pulse 1s infinite';
                            }
                        }, 2000);
                    } else {
                        // For clickfirst, enable button after 2 seconds too
                        setTimeout(() => {
                            if (nextBtn) {
                                nextBtn.disabled = false;
                                nextBtn.style.animation = 'pulse 1s infinite';
                            }
                        }, 2000);
                    }
                }
            }, 1000);
        }

        function startRound(roundId) {
            console.log('startRound called with roundId:', roundId);
            fetch('../src/api/api.php?endpoint=game&action=start_round', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ round_id: roundId })
            })
            .then(r => {
                console.log('start_round response status:', r.status);
                return r.json();
            })
            .then(data => {
                console.log('start_round response data:', data);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Errore: ' + (data.error || 'Impossibile avviare il round'));
                }
            })
            .catch(e => {
                console.error('start_round error:', e);
                alert('Errore: ' + e.message);
            });
        }

        function nextQuestion(roundId) {
            console.log('nextQuestion called with roundId:', roundId);
            fetch('../src/api/api.php?endpoint=game&action=close_round&round_id=' + roundId, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ round_id: roundId })
            })
            .then(r => {
                console.log('close_round response status:', r.status);
                return r.json();
            })
            .then(data => {
                console.log('close_round response data:', data);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Errore: ' + (data.error || 'Impossibile passare al prossimo round'));
                }
            })
            .catch(e => {
                console.error('close_round error:', e);
                alert('Errore: ' + e.message);
            });
        }

        function loadLeaderboard() {
            fetch('../src/api/api.php?endpoint=leaderboard&room_code=' + roomCode)
                .then(r => r.json())
                .then(data => {
                    const el = document.getElementById('leaderboard');
                    if (data.success && data.leaderboard && data.leaderboard.length > 0) {
                        let html = '<ul style="list-style: none; padding: 0;">';
                        data.leaderboard.forEach((player, i) => {
                            const medal = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : (i + 1) + '.'));
                            html += `<li style="padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">
                                <span>${medal} ${player.username}</span>
                                <strong>${player.total_answers || 0}</strong>
                            </li>`;
                        });
                        html += '</ul>';
                        el.innerHTML = html;
                    } else {
                        el.innerHTML = '<p class="loading-text">Nessun dato</p>';
                    }
                })
                .catch(e => {
                    document.getElementById('leaderboard').innerHTML = '<p class="loading-text">Errore</p>';
                });
        }

        function goBack() {
            if (confirm('Sei sicuro? La partita verrà terminata.')) {
                window.location.href = 'admin.php';
            }
        }
    </script>
</body>
</html>
