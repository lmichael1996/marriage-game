<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/services/QuestionService.php';
require_once __DIR__ . '/../src/services/RoomService.php';

requireAdmin();

$questionService = new QuestionService();
$roomService = new RoomService();

// ✅ Room code da GET param (more reliable than session)
$roomCode = $_GET['room_code'] ?? $_SESSION['room_code'] ?? null;

// Carica stanza e set di domande
$room = $roomService->getRoomDetails($roomCode);
if (!$room) {
    header('Location: admin.php');
    exit;
}

// Handler per incrementare il counter via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'increment_counter') {
    if ($room['id']) {
        // Increment counter in session (local to admin)
        $counterKey = 'round_counter_' . $roomCode;
        $_SESSION[$counterKey] = ($_SESSION[$counterKey] ?? 1) + 1;
    }
    exit;
}

$questionSetId = $room['qset_id'];
$currentCounter = $_SESSION['round_counter_' . $roomCode] ?? 1;

// Carica informazioni room solo la prima volta
$roomInfoKey = 'room_info_' . $roomCode;
if (!isset($_SESSION[$roomInfoKey])) {
    $players = $roomService->getRoomPlayers($roomCode) ?? [];
    $totalQuestions = $questionService->getQuestionCountByQset($questionSetId);

    $_SESSION[$roomInfoKey] = [
        'num_players' => count($players),
        'total_questions' => $totalQuestions,
        'started_at' => time()
    ];
}

$roomInfo = $_SESSION[$roomInfoKey];

// Carica domanda corrente
$nextQuestion = $questionService->getQuestionByCounter($questionSetId, $currentCounter);

// ✅ Get active round from DB (not session)
$activeRound = $roomService->getActiveRound($room['id']);
$gameOver = !$activeRound && !$nextQuestion;
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

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; width: 100%; background: white; padding: 30px; border: 2px solid #1a1a1a; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div>
                <div class="info-box">
                    <p><strong>👥 Giocatori connessi:</strong> <?php echo $roomInfo['num_players']; ?></p>
                    <p><strong>🎯 Room Code:</strong> <code><?php echo htmlspecialchars($roomCode); ?></code></p>
                    <p><strong>📊 Domande totali:</strong> <?php echo $roomInfo['total_questions']; ?></p>
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

            <div class="admin-section" style="position: sticky; top: 20px;">
                <h3>🏆 Classifica</h3>
                <div id="leaderboard" style="font-size: 1.1em; display: grid; grid-template-columns: 1fr; gap: 10px; min-height: 100px;">
                    <p class="loading-text">Nessun dato</p>
                </div>
            </div>
        </div>

    <script>
        const roomCode = '<?php echo $roomCode; ?>';
        let timerInterval = null;
        let currentRoundResults = null;

        document.addEventListener('DOMContentLoaded', () => {
            // Load round results from session storage if available
            const storedResults = sessionStorage.getItem('roundResults');
            if (storedResults) {
                currentRoundResults = JSON.parse(storedResults);
                sessionStorage.removeItem('roundResults');  // Clear after reading
            }
            <?php if ($activeRound): ?>
            console.log('Active round timer value:', <?php echo isset($activeRound['timer']) ? $activeRound['timer'] : 'null'; ?>);
            console.log('Active round data:', <?php echo json_encode($activeRound); ?>);
            const timerValue = <?php echo isset($activeRound['timer']) && $activeRound['timer'] !== null ? (int)$activeRound['timer'] : 'null'; ?>;
            console.log('Parsed timer value:', timerValue);
            startCountdown(timerValue || 10);
            <?php endif; ?>
            loadLeaderboard();

            <?php if ($gameOver): ?>
            // Load final leaderboard when game is over
            loadFinalLeaderboard();
            <?php endif; ?>
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
                        // After 2 seconds, show correct answer in green and fetch top answers
                        setTimeout(() => {
                            if (correctAnswerNum) {
                                const correctOption = document.querySelector('#option-' + correctAnswerNum);
                                if (correctOption) {
                                    correctOption.style.background = '#4caf50';
                                }
                            }

                            // Fetch top 10 answers when timer expires
                            const roundId = <?php echo isset($activeRound['id']) ? $activeRound['id'] : 'null'; ?>;
                            console.log('Fetching top answers for roundId:', roundId);
                            if (roundId) {
                                fetch('../src/api/api.php?endpoint=round_answers&round_id=' + roundId)
                                    .then(r => r.json())
                                    .then(data => {
                                        console.log('Full response:', data);
                                        console.log('top_answers:', data.top_answers);
                                        console.log('top_answers length:', data.top_answers ? data.top_answers.length : 0);
                                        if (data.success && data.top_answers && data.top_answers.length > 0) {
                                            console.log('Setting currentRoundResults:', data.top_answers);
                                            currentRoundResults = data.top_answers;
                                            loadLeaderboard();  // Update the leaderboard display
                                        } else {
                                            console.log('No top_answers in response or empty array');
                                            currentRoundResults = [];
                                            loadLeaderboard();
                                        }
                                    })
                                    .catch(e => console.error('Error fetching top answers:', e));
                            } else {
                                console.log('roundId is null!');
                            }

                            // Enable next button after showing correct answer
                            if (nextBtn) {
                                nextBtn.disabled = false;
                                nextBtn.style.animation = 'pulse 1s infinite';
                            }
                        }, 2000);
                    } else {
                        // For clickfirst, enable button after 2 seconds and fetch top answers
                        setTimeout(() => {
                            // Fetch top 10 answers for clickfirst too
                            const roundId = <?php echo isset($activeRound['id']) ? $activeRound['id'] : 'null'; ?>;
                            console.log('Fetching top answers for clickfirst roundId:', roundId);
                            if (roundId) {
                                fetch('../src/api/api.php?endpoint=round_answers&round_id=' + roundId)
                                    .then(r => r.json())
                                    .then(data => {
                                        console.log('Full response (clickfirst):', data);
                                        console.log('top_answers:', data.top_answers);
                                        console.log('top_answers length:', data.top_answers ? data.top_answers.length : 0);
                                        if (data.success && data.top_answers && data.top_answers.length > 0) {
                                            console.log('Setting currentRoundResults:', data.top_answers);
                                            currentRoundResults = data.top_answers;
                                            loadLeaderboard();  // Update the leaderboard display
                                        } else {
                                            console.log('No top_answers in response or empty array');
                                            currentRoundResults = [];
                                            loadLeaderboard();
                                        }
                                    })
                                    .catch(e => console.error('Error fetching top answers:', e));
                            } else {
                                console.log('roundId is null!');
                            }

                            if (nextBtn) {
                                nextBtn.disabled = false;
                                nextBtn.style.animation = 'pulse 1s infinite';
                            }
                        }, 2000);
                    }
                }
            }, 1000);
        }

        function startRound(questionId) {
            console.log('startRound called with questionId:', questionId);
            fetch('../src/api/api.php?endpoint=game&action=start_round', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question_id: questionId })
            })
            .then(r => {
                console.log('start_round response status:', r.status);
                return r.json();
            })
            .then(data => {
                console.log('start_round response data:', data);
                if (data.success) {
                    // Reload page with room_code in GET param
                    const roomCode = '<?php echo htmlspecialchars($roomCode); ?>';
                    location.href = 'room-admin.php?room_code=' + encodeURIComponent(roomCode);
                } else {
                    console.error('Errore avvio round: ' + (data.error || data.message || 'Impossibile avviare il round'));
                }
            })
            .catch(e => {
                console.error('start_round error:', e);
            });
        }

        function nextQuestion(roundId) {
            // Invia POST per incrementare il counter nella session (no GET parameters)
            const formData = new FormData();
            formData.append('action', 'increment_counter');

            fetch('room-admin.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                // Ricarica la pagina con room_code nel GET param
                const roomCode = '<?php echo htmlspecialchars($roomCode); ?>';
                location.href = 'room-admin.php?room_code=' + encodeURIComponent(roomCode);
            }).catch(err => console.error('Errore:', err));
        }

        function loadLeaderboard() {
            // Check if there are round results
            if (currentRoundResults && currentRoundResults.length > 0) {
                const el = document.getElementById('leaderboard');
                let html = '';
                currentRoundResults.forEach((answer, i) => {
                    const medal = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : (i + 1) + '.'));
                    const time = parseFloat(answer.answer_time).toFixed(2) + 's';
                    html += `<div style="padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: bold; margin-bottom: 8px;">${medal}</div>
                        <div style="font-size: 1em; color: #333;">${answer.username}</div>
                        <div style="font-size: 0.9em; color: #666; margin-top: 4px;">${time}</div>
                    </div>`;
                });
                el.innerHTML = html;
                return;
            }

            // Otherwise, show empty
            document.getElementById('leaderboard').innerHTML = '<p class="loading-text">Nessun dato</p>';
        }

        function loadFinalLeaderboard() {
            fetch('../src/api/api.php?endpoint=final_leaderboard')
                .then(r => r.json())
                .then(data => {
                    console.log('Final leaderboard response:', data);
                    if (data.success && data.leaderboard && data.leaderboard.length > 0) {
                        const el = document.getElementById('final-leaderboard');
                        let html = '';
                        data.leaderboard.forEach((player) => {
                            html += `<div style="padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 6px; text-align: center; margin-bottom: 10px;">
                                <div style="font-size: 1.8em; font-weight: bold; margin-bottom: 8px;">${player.medal}</div>
                                <div style="font-size: 1.2em; color: #333; font-weight: 600;">${player.username}</div>
                                <div style="font-size: 1.1em; color: #28a745; margin-top: 8px; font-weight: bold;">${player.score} punti</div>
                            </div>`;
                        });
                        el.innerHTML = html;
                    } else {
                        console.log('No leaderboard data');
                        document.getElementById('final-leaderboard').innerHTML = '<p class="loading-text">Nessun dato disponibile</p>';
                    }
                })
                .catch(e => {
                    console.error('Error loading final leaderboard:', e);
                    document.getElementById('final-leaderboard').innerHTML = '<p class="loading-text">Errore nel caricamento</p>';
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
