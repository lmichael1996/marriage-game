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

if ($room['has_winner'] ?? false) {
    echo "<div class='alert alert-error'>
            <h2>Partita Terminata</h2>
            <p>Questa stanza ha già un vincitore. Accesso negato.</p>
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
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/game_admin.css">
    <link rel="stylesheet" href="../assets/css/mobile.css">
    <style>
        .game-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            grid-auto-rows: max-content;
            gap: 20px;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            align-items: start;
        }

        .game-container.game-over-layout {
            grid-template-columns: 1fr;
            max-width: 800px;
        }

        .game-main {
            background: #fff;
            border: none;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        .game-sidebar {
            background: #fff;
            border: none;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            height: fit-content;
            max-height: none;
            overflow-y: visible;
        }

        .info-box {
            background: #f8f9fa;
            border: none;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 20px;
        }

        .info-box p {
            margin: 8px 0;
            font-size: 0.95em;
            color: #636e72;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-box span {
            font-weight: 600;
            color: #636e72;
        }

        .info-box code {
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 1.05em;
        }

        .game-step {
            background: #fff;
            border: none;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .game-step h3 {
            margin-top: 0;
            margin-bottom: 12px;
            color: #2d3436;
            font-size: 1.2em;
            font-weight: 800;
            border-bottom: none;
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-badge {
            font-size: 0.7em;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 12px;
            color: #2d3436;
        }

        /* ===== CATEGORY HEADER BAR (colored) ===== */
        .category-header {
            padding: 16px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 16px;
            margin-bottom: 15px;
        }

        .category-header .round-label {
            color: #2d3436;
            font-weight: 800;
            font-size: 1.1em;
        }

        .category-header .timer-label {
            color: #2d3436;
            font-weight: 800;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .game-step p {
            font-size: 1.35em;
            color: #2d3436;
            font-weight: 800;
            line-height: 1.4;
            text-align: center;
            margin: 10px 0;
        }

        .game-step.active {
            border: none;
            background: #fff;
        }

        .game-step.game-over {
            border: none;
            background: #fff;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 15px 0;
            padding: 15px;
            background: transparent;
            border: none;
            border-radius: 0;
        }

        .option-btn {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            padding: 16px 22px;
            cursor: default;
            text-align: center;
            font-size: 1em;
            font-weight: 700;
            color: #2d3436;
            transition: none;
            min-height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            user-select: none;
            pointer-events: none;
        }

        .option-btn:hover {
            background: #f8f9fa;
            border-color: #e9ecef;
        }

        .option-btn.correct {
            background: #00b894;
            border-color: transparent;
            color: #fff;
            font-weight: 800;
            box-shadow: 0 4px 15px rgba(0,184,148,0.3);
        }

        .timer-box {
            background: #f8f9fa;
            color: #2d3436;
            border: none;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .timer-box h4 {
            margin: 0 0 12px 0;
            font-size: 1em;
            font-weight: 700;
            color: #636e72;
        }

        #timer {
            font-size: inherit;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.05em;
            color: #2d3436;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn-main {
            flex: 1;
            min-width: 200px;
            padding: 14px 24px;
            font-size: 1em;
            font-weight: 700;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #74b9ff;
            color: #fff;
            box-shadow: 0 4px 15px rgba(116,185,255,0.3);
        }

        .btn-main:hover:not(:disabled) {
            background: #0984e3;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(116,185,255,0.4);
        }

        .btn-success-custom {
            background: #00b894;
            color: #fff;
            border: none;
            box-shadow: 0 4px 15px rgba(0,184,148,0.3);
        }

        .btn-success-custom:hover:not(:disabled) {
            background: #00a381;
            color: #fff;
            box-shadow: 0 6px 20px rgba(0,184,148,0.4);
        }

        .btn-primary-custom {
            background: #74b9ff;
            color: #fff;
            border: none;
            box-shadow: 0 4px 15px rgba(116,185,255,0.3);
        }

        .btn-primary-custom:hover:not(:disabled) {
            background: #0984e3;
            color: #fff;
            box-shadow: 0 6px 20px rgba(116,185,255,0.4);
        }

        .btn-main:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #dfe6e9;
            box-shadow: none;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #f8f9fa;
            border: none;
            border-radius: 14px;
            margin-bottom: 8px;
            border-left: 4px solid #74b9ff;
            transition: all 0.2s ease;
        }

        .leaderboard-item:hover {
            background: #f0f2f5;
            transform: translateX(3px);
        }

        .medal {
            font-size: 1.6em;
            min-width: 30px;
        }

        .leaderboard-info {
            flex: 1;
        }

        .leaderboard-name {
            font-weight: 700;
            color: #2d3436;
            font-size: 0.95em;
        }

        .leaderboard-time {
            font-size: 0.8em;
            color: #636e72;
            margin-top: 2px;
        }

        .sidebar-title {
            font-size: 1.1em;
            font-weight: 800;
            margin-bottom: 15px;
            color: #2d3436;
            padding-bottom: 10px;
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 20px 15px;
            color: #b2bec3;
            font-size: 0.95em;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px 25px;
            background: #fff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        .header h1 {
            margin: 0;
            font-size: 1.4em;
            color: #2d3436;
            font-weight: 800;
        }

        @media (max-width: 1024px) {
            .game-container {
                grid-template-columns: 1fr;
            }

            .game-sidebar {
                position: static;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .game-main {
                padding: 15px;
            }

            .game-sidebar {
                padding: 15px;
            }

            .info-box {
                padding: 12px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn-main {
                width: 100%;
            }

            #timer {
                font-size: 2em;
            }

            .game-container {
                margin: 15px auto;
                padding: 0 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Gestione Partita</h1>
            <button class="btn btn-secondary" onclick="goBack()">← Torna a Admin</button>
        </div>

        <div class="game-container<?php if ($gameOver): ?> game-over-layout<?php endif; ?>">
            <div class="game-main">
                <div class="info-box">
                    <p><span>👥 Giocatori:</span> <strong><?php echo $roomInfo['num_players']; ?></strong></p>
                    <p><span>🎯 Room Code:</span> <code><?php echo htmlspecialchars($room['code_player']); ?></code></p>
                    <p><span>📊 Domande:</span> <strong><?php echo $roomInfo['total_questions']; ?></strong></p>
                </div>

                <?php if ($gameOver): ?>
                    <div class="game-step game-over">
                        <h3>🎉 Partita Terminata!</h3>
                        <div id="final-leaderboard" style="margin-top: 20px;">
                            <p class="empty-state">Caricamento classifica...</p>
                        </div>
                    </div>
                <?php elseif ($activeRound): ?>
                    <?php $catColor = $activeRound['category_color'] ?? '#74b9ff'; $catName = $activeRound['category_name'] ?? ''; ?>
                    <div class="game-step active">
                        <div class="category-header" style="background: <?php echo htmlspecialchars($catColor); ?>;">
                            <span class="round-label">Domanda #<?php echo $counter; ?> — <?php echo htmlspecialchars($catName); ?></span>
                            <span class="timer-label">⏱️ <span id="timer"><?php echo $activeRound['timer'] ?? 30; ?></span></span>
                        </div>
                        <p><?php echo htmlspecialchars($activeRound['question']); ?></p>

                        <?php if ($activeRound['round_type'] !== 'clickfirst'): ?>
                            <?php
                                $opt1 = $activeRound['round_type'] === 'truefalse' ? 'Vero' : $activeRound['option1'];
                                $opt2 = $activeRound['round_type'] === 'truefalse' ? 'Falso' : $activeRound['option2'];
                            ?>
                            <div class="options-grid">
                                <div id="option-1" class="option-btn">
                                    <?php echo htmlspecialchars($opt1); ?>
                                </div>
                                <div id="option-2" class="option-btn">
                                    <?php echo htmlspecialchars($opt2); ?>
                                </div>
                                <?php if ($activeRound['round_type'] === 'multiple'): ?>
                                    <div id="option-3" class="option-btn">
                                        <?php echo htmlspecialchars($activeRound['option3']); ?>
                                    </div>
                                    <div id="option-4" class="option-btn">
                                        <?php echo htmlspecialchars($activeRound['option4']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #ffc107; font-weight: bold; text-align: center; font-size: 1.1em; padding: 20px; background: #fffbf0; border-radius: 8px;">⚡ Chi clicca primo vince!</p>
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
                    <?php $catColor = $question['category_color'] ?? '#74b9ff'; $catName = $question['category_name'] ?? ''; ?>
                    <div class="game-step">
                        <div class="category-header" style="background: <?php echo htmlspecialchars($catColor); ?>;">
                            <span class="round-label">Domanda #<?php echo $counter; ?> — <?php echo htmlspecialchars($catName); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($question['question']); ?></p>

                        <?php if ($question['round_type'] !== 'clickfirst'): ?>
                            <?php
                                $qOpt1 = $question['round_type'] === 'truefalse' ? 'Vero' : $question['option1'];
                                $qOpt2 = $question['round_type'] === 'truefalse' ? 'Falso' : $question['option2'];
                            ?>
                            <div class="options-grid">
                                <div class="option-btn">
                                    <?php echo htmlspecialchars($qOpt1); ?>
                                </div>
                                <div class="option-btn">
                                    <?php echo htmlspecialchars($qOpt2); ?>
                                </div>
                                <?php if ($question['round_type'] === 'multiple'): ?>
                                    <div class="option-btn">
                                        <?php echo htmlspecialchars($question['option3']); ?>
                                    </div>
                                    <div class="option-btn">
                                        <?php echo htmlspecialchars($question['option4']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #ffc107; font-weight: bold; text-align: center; font-size: 1.1em; padding: 20px; background: #fffbf0; border-radius: 8px;">⚡ Chi clicca primo vince!</p>
                        <?php endif; ?>

                        <div class="button-group">
                            <button class="btn-main btn-primary-custom" onclick="startRound(<?php echo $question['id']; ?>)">
                                ▶ AVVIA ROUND
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
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
            currentRoundType = '<?php echo $activeRound['round_type'] ?? ''; ?>';
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
                            '<?php echo isset($activeRound['round_type']) ? $activeRound['round_type'] : ''; ?>' ===
                            'clickfirst';

                        if (!isClickFirst && correctAnswer) {
                            const correctOption = document.querySelector(
                                '#option-' + correctAnswer
                            );
                            if (correctOption) {
                                correctOption.style.background = '#4caf50';
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
                                    <div class="leaderboard-time" style="color: #333; font-weight: 600;">${p.score} punti</div>
                                </div>
                            </div>`;
                        });

                        document.getElementById('final-leaderboard').innerHTML = html;
                        document.getElementById('leaderboard').innerHTML = html;
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
                        let html = '';

                        data.top_answers.forEach((answer, i) => {
                            const medals = ['🥇', '🥈', '🥉'];
                            const medal = isClickFirst
                                ? `<input type="radio" name="clickfirst-winner" value="${i}">`
                                : (medals[i] || i + 1 + '.');
                            const time = parseFloat(
                                answer.answer_time
                            ).toFixed(2) + 's';

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
                        const nextBtn = document.getElementById('next-btn');
                        if (nextBtn) {
                            nextBtn.disabled = false;
                            nextBtn.style.animation = 'pulse 1s infinite';
                        }
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
