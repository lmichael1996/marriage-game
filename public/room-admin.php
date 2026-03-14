<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/utils/helper.php';

requireAdmin();

// Verifica se la stanza ha già un vincitore (partita terminata)
// (va prima del loadRoomAdmin per evitare caricamento dati inutile)

// POST handler: Increment counter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'increment_counter') {
    $roomId = (int)($_GET['room_id'] ?? $_SESSION['room_id'] ?? 0);
    $key = 'round_counter_' . $roomId;
    $_SESSION[$key] = ($_SESSION[$key] ?? 1) + 1;
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

extract(loadRoomAdmin());

if ($room['has_ranking'] ?? false) {
    echo "<div class='alert alert-error'>
            <h2>Partita Terminata</h2>
            <p>Questa stanza ha già una classifica finale. Accesso negato.</p>
            <a href='admin.php'>Torna alla Dashboard</a>
          </div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        /* Room-admin: options are read-only */
        .option-btn {
            cursor: default;
            user-select: none;
            pointer-events: none;
            transition: none;
        }
        .option-btn:hover {
            background: #f8f9fa;
            border-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Gestione Partita</h1>
            <button class="btn btn-secondary" onclick="goBack()">← Torna a Admin</button>
        </div>

        <div class="game-grid<?php if ($gameOver): ?> game-over-layout<?php endif; ?>">
            <div class="game-main-wrapper">
                <?php if (!$gameOver): ?>
                <div class="info-box">
                    <p><span>👥 Giocatori:</span> <strong><?php echo $roomInfo['num_players']; ?></strong></p>
                    <p><span>❓ Domande:</span> <strong><?php echo $roomInfo['total_questions']; ?></strong></p>
                    <p><span>🎮 Codice giocatore:</span> <code><?php echo htmlspecialchars($room['code_player']); ?></code></p>
                    <?php if (!empty($room['code_judge'])): ?>
                        <p><span>⚖️ Codice giudice:</span> <code><?php echo htmlspecialchars($room['code_judge']); ?></code></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="game-card" style="border: 3px solid <?php echo htmlspecialchars($activeRound['category_color'] ?? $question['category_color'] ?? 'transparent'); ?>;">

                <?php if ($gameOver): ?>
                    <div class="game-step game-over">
                        <h2>🏆 Classifica Finale</h2>
                        <div id="final-leaderboard" class="final-leaderboard">
                            <div class="empty-state">Caricamento...</div>
                        </div>
                    </div>
                <?php elseif ($activeRound): ?>
                    <?php $catColor = $activeRound['category_color'] ?? '#74b9ff'; $catName = $activeRound['category_name'] ?? ''; $isClickFirst = $activeRound['question_type'] === 'clickfirst'; ?>
                    <div class="game-step active">
                        <div class="category-header" style="background: <?php echo htmlspecialchars($catColor); ?>;">
                            <span class="round-label">Domanda #<?php echo $counter; ?> — <?php if ($isClickFirst): ?>⚡ <?php endif; ?><?php echo htmlspecialchars($catName); ?></span>
                            <span class="timer-label">⏱️ <span id="timer"><?php echo $activeRound['timer'] ?? 30; ?></span></span>
                        </div>
                        <p><?php echo htmlspecialchars($activeRound['question']); ?></p>

                        <?php if (!$isClickFirst): ?>
                            <?php
                                $opt1 = $activeRound['question_type'] === 'truefalse' ? 'Vero' : $activeRound['option1'];
                                $opt2 = $activeRound['question_type'] === 'truefalse' ? 'Falso' : $activeRound['option2'];
                            ?>
                            <div class="options-grid">
                                <div id="option-1" class="option-btn">
                                    <?php echo htmlspecialchars($opt1); ?>
                                </div>
                                <div id="option-2" class="option-btn">
                                    <?php echo htmlspecialchars($opt2); ?>
                                </div>
                                <?php if ($activeRound['question_type'] === 'multiple'): ?>
                                    <div id="option-3" class="option-btn">
                                        <?php echo htmlspecialchars($activeRound['option3']); ?>
                                    </div>
                                    <div id="option-4" class="option-btn">
                                        <?php echo htmlspecialchars($activeRound['option4']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="button-group">
                            <button
                                class="btn-main btn-success-custom"
                                id="next-btn"
                                onclick="nextQuestion()"
                                disabled
                            >
                                ➡️ Prossima Domanda
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php $catColor = $question['category_color'] ?? '#74b9ff'; $catName = $question['category_name'] ?? ''; $isClickFirst = $question['question_type'] === 'clickfirst'; ?>
                    <div class="game-step">
                        <div class="category-header" style="background: <?php echo htmlspecialchars($catColor); ?>;">
                            <span class="round-label">Domanda #<?php echo $counter; ?> — <?php if ($isClickFirst): ?>⚡ <?php endif; ?><?php echo htmlspecialchars($catName); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($question['question']); ?></p>

                        <?php if (!$isClickFirst): ?>
                            <?php
                                $qOpt1 = $question['question_type'] === 'truefalse' ? 'Vero' : $question['option1'];
                                $qOpt2 = $question['question_type'] === 'truefalse' ? 'Falso' : $question['option2'];
                            ?>
                            <div class="options-grid">
                                <div class="option-btn">
                                    <?php echo htmlspecialchars($qOpt1); ?>
                                </div>
                                <div class="option-btn">
                                    <?php echo htmlspecialchars($qOpt2); ?>
                                </div>
                                <?php if ($question['question_type'] === 'multiple'): ?>
                                    <div class="option-btn">
                                        <?php echo htmlspecialchars($question['option3']); ?>
                                    </div>
                                    <div class="option-btn">
                                        <?php echo htmlspecialchars($question['option4']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="button-group">
                            <button class="btn-main btn-primary-custom" onclick="startRound(<?php echo $question['id']; ?>)">
                                ▶ AVVIA ROUND
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </div>

            <?php if (!$gameOver): ?>
            <div class="game-sidebar">
                <div class="sidebar-title">🏆 Classifica</div>
                <div id="leaderboard">
                    <div class="empty-state">Nessun dato</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const roomId = <?php echo $roomId; ?>;
        const roomCode = '<?php echo $room['code_player']; ?>';
        const activeRoundId = <?php echo isset($activeRound['id']) ? $activeRound['id'] : 'null'; ?>;
        const judgeConnected = <?php echo $judgeConnected ? 'true' : 'false'; ?>;
        let timerInterval = null;
        let currentRoundType = null;

        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($activeRound): ?>
            currentRoundType = '<?php echo $activeRound['question_type'] ?? ''; ?>';
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
            const roundId = <?php echo isset($activeRound['id']) ? $activeRound['id'] : 'null'; ?>;

            timerInterval = setInterval(() => {
                timeLeft--;
                timerEl.textContent = timeLeft;

                if (timeLeft <= 5) timerEl.style.color = '#dc143c';
                else if (timeLeft <= 10) timerEl.style.color = '#ffc107';

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);

                    setTimeout(() => {
                        const correctAnswer = <?php echo isset($activeRound['correct_answer']) ? $activeRound['correct_answer'] : 'null'; ?>;
                        const isClickFirst =
                            '<?php echo isset($activeRound['question_type']) ? $activeRound['question_type'] : ''; ?>' ===
                            'clickfirst';

                        if (!isClickFirst && correctAnswer) {
                            const correctOption = document.querySelector(
                                '#option-' + correctAnswer
                            );
                            if (correctOption) {
                                correctOption.classList.add('correct');
                            }
                        }

                        if (roundId) {
                            // Close round (saves ranking) and show answers
                            fetch('../src/api/api.php?endpoint=game&action=close_round', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ round_id: roundId })
                            }).then(() => loadRoundAnswers(roundId))
                              .catch(() => loadRoundAnswers(roundId));
                        }

                        if (nextBtn && !isClickFirst) {
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
                    location.href = 'room-admin.php?room_id=' + roomId;
                }
            });
        }

        function nextQuestion() {
            // Clickfirst: salva il vincitore selezionato prima di avanzare
            if (currentRoundType === 'clickfirst' && activeRoundId) {
                const selected = document.querySelector('input[name="clickfirst-winner"]:checked');
                if (!selected) {
                    // Nessuna risposta o nessun radio: procedi senza vincitore
                    proceedToNextQuestion();
                    return;
                }

                fetch('../src/api/api.php?endpoint=game&action=set_clickfirst_winner', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        round_id: activeRoundId,
                        winner_index: parseInt(selected.value)
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        proceedToNextQuestion();
                    }
                });
            } else {
                proceedToNextQuestion();
            }
        }

        function proceedToNextQuestion() {
            const form = new FormData();
            form.append('action', 'increment_counter');

            fetch('room-admin.php?room_id=' + roomId, {
                method: 'POST',
                body: form,
            })
                .then((r) => r.json())
                .then((data) => {
                    if (data.success) {
                        location.href = 'room-admin.php?room_id=' + roomId;
                    }
                });
        }

        function loadFinalLeaderboard() {
            fetch('../src/api/api.php?endpoint=final_leaderboard&room_id=' + roomId)
                .then((r) => r.json())
                .then((data) => {
                    if (data.success && data.leaderboard?.length > 0) {
                        let html = '';
                        data.leaderboard.forEach((p) => {
                            html += `<div class="leaderboard-item">
                                <div class="medal">${p.medal}</div>
                                <div class="leaderboard-info">
                                    <div class="leaderboard-name">${p.username}</div>
                                    <div class="leaderboard-time" style="color: #2d3436; font-weight: 700;">${p.score} punti</div>
                                </div>
                            </div>`;
                        });
                        document.getElementById('final-leaderboard').innerHTML = html;
                    } else {
                        document.getElementById('final-leaderboard').innerHTML = '<div class="empty-state">Nessun risultato disponibile</div>';
                    }
                });
        }

        function loadRoundAnswers(roundId) {
            const isClickFirst = currentRoundType === 'clickfirst';

            // Clickfirst con giudice connesso: delega al judge
            if (isClickFirst && judgeConnected) {
                document.getElementById('leaderboard').innerHTML =
                    '<div class="empty-state">⚖️ In attesa del giudice...</div>';
                pollJudgeDecision(roundId);
                return;
            }

            fetch(
                '../src/api/api.php?endpoint=round_answers&round_id=' +
                    roundId
            )
                .then((r) => r.json())
                .then((data) => {
                    if (data.success && data.top_answers?.length > 0) {
                        const medals = ['🥇', '🥈', '🥉'];
                        let html = '';

                        data.top_answers.forEach((answer, i) => {
                            const medal = isClickFirst
                                ? `<input type="radio" name="clickfirst-winner" value="${i}">`
                                : (medals[i] || (i + 1 + '.'));
                            const time = parseFloat(answer.answer_time).toFixed(2) + 's';

                            html += `<div class="leaderboard-item">
                                <div class="medal">${medal}</div>
                                <div class="leaderboard-info">
                                    <div class="leaderboard-name">${answer.username}</div>
                                    <div class="leaderboard-time">${time}</div>
                                </div>
                            </div>`;
                        });
                        document.getElementById('leaderboard').innerHTML =
                            html;

                        // Clickfirst: abilita "Prossima Domanda" solo dopo selezione radio
                        if (isClickFirst) {
                            const nextBtn = document.getElementById('next-btn');
                            if (nextBtn) {
                                document.querySelectorAll('input[name="clickfirst-winner"]').forEach(radio => {
                                    radio.addEventListener('change', () => {
                                        nextBtn.disabled = false;
                                        nextBtn.style.animation = 'pulse 1s infinite';
                                    });
                                });
                            }
                        }
                    } else if (isClickFirst) {
                        // Nessuna risposta nel clickfirst: abilita prossima domanda
                        document.getElementById('leaderboard').innerHTML = '<div class="empty-state">Nessuna risposta</div>';
                        const nextBtn = document.getElementById('next-btn');
                        if (nextBtn) {
                            nextBtn.disabled = false;
                            nextBtn.style.animation = 'pulse 1s infinite';
                        }
                    } else {
                        document.getElementById('leaderboard').innerHTML = '<div class="empty-state">Nessuna risposta</div>';
                    }
                });
        }

        function pollJudgeDecision(roundId) {
            const pollInterval = setInterval(() => {
                fetch('../src/api/api.php?endpoint=game&action=check_judge_decision&round_id=' + roundId)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.judge_decided) {
                            clearInterval(pollInterval);
                            document.getElementById('leaderboard').innerHTML =
                                '<div class="empty-state">✅ Giudice ha deciso</div>';
                            // Auto-avanza alla prossima domanda
                            proceedToNextQuestion();
                        }
                    });
            }, 1000);
        }

        function goBack() {
            if (confirm('Termina partita?')) {
                fetch('../src/api/api.php?endpoint=delete_room', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            window.location.href = 'admin.php';
                        } else {
                            alert(
                                'Errore nella chiusura della stanza: ' +
                                    (data.error || data.message)
                            );
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                        alert('Errore nella chiusura della stanza');
                    });
            }
        }
    </script>
</body>
</html>
