<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/utils/helper.php';

requireAdmin();
extract(loadRoomAdmin());

if ($room['has_ranking'] ?? false) {
    echo "<div class='alert alert-error'>
            <h2>Partita Terminata</h2>
            <p>Questa stanza ha già una classifica finale. Accesso negato.</p>
            <a href='admin.php'>Torna alla Dashboard</a>
          </div>";
    exit;
}

// Resolve question data from active round or next question
$q         = $activeRound ?? $question;
$catColor  = $q['category_color'] ?? '#74b9ff';
$catName   = $q['category_name'] ?? '';
$isClickFirst = ($q['question_type'] ?? '') === 'clickfirst';
$isTrueFalse  = ($q['question_type'] ?? '') === 'truefalse';
$isMultiple   = ($q['question_type'] ?? '') === 'multiple';
$opt1 = $isTrueFalse ? 'Vero'  : ($q['option1'] ?? '');
$opt2 = $isTrueFalse ? 'Falso' : ($q['option2'] ?? '');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/core.css?v=22">
    <link rel="stylesheet" href="../assets/css/admin.css?v=22">
    <link rel="stylesheet" href="../assets/css/game.css?v=22">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=22">
    <script src="../assets/js/api.js?v=23"></script>
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
                    <p><span>❓ Domande:</span> <strong><?php echo $roomInfo['total_questions'] - $skippedQuestions; ?></strong></p>
                    <p><span>🎮 Codice giocatore:</span> <code><?php echo htmlspecialchars($room['code_player']); ?></code></p>
                    <?php if (!empty($room['code_judge'])): ?>
                        <p><span>⚖️ Codice giudice:</span> <code><?php echo htmlspecialchars($room['code_judge']); ?></code></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="game-card" style="border: <?php echo $gameOver ? '0' : '5px solid ' . htmlspecialchars($catColor); ?>;">

                <?php if ($gameOver): ?>
                    <div class="game-step game-over">
                        <h2>🏆 Classifica Finale</h2>
                        <div id="final-leaderboard" class="final-leaderboard">
                            <div class="empty-state">Caricamento...</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="game-step<?php if ($activeRound): ?> active<?php endif; ?>">
                        <div class="category-header" style="background: <?php echo htmlspecialchars($catColor); ?>;">
                            <span class="round-label">Domanda <?php echo $counter - $skippedQuestions; ?> — <?php if ($isClickFirst): ?>⚡ <?php endif; ?><?php echo htmlspecialchars($catName); ?></span>
                            <?php if ($activeRound): ?>
                                <span class="timer-label">⏱️ <span id="timer"><?php echo $activeRound['timer'] ?? 30; ?></span></span>
                            <?php endif; ?>
                        </div>
                        <p><?php echo htmlspecialchars($q['question']); ?></p>

                        <?php if (!$isClickFirst): ?>
                            <div class="options-grid">
                                <div <?php if ($activeRound): ?>id="option-1" <?php endif; ?>class="option-btn"><?php echo htmlspecialchars($opt1); ?></div>
                                <div <?php if ($activeRound): ?>id="option-2" <?php endif; ?>class="option-btn"><?php echo htmlspecialchars($opt2); ?></div>
                                <?php if ($isMultiple): ?>
                                    <div <?php if ($activeRound): ?>id="option-3" <?php endif; ?>class="option-btn"><?php echo htmlspecialchars($q['option3']); ?></div>
                                    <div <?php if ($activeRound): ?>id="option-4" <?php endif; ?>class="option-btn"><?php echo htmlspecialchars($q['option4']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="button-group">
                            <?php if ($activeRound): ?>
                                <button class="btn-main btn-success-custom" id="next-btn" onclick="nextQuestion()" disabled>
                                    ➡️ Prossima Domanda
                                </button>
                            <?php else: ?>
                                <button class="btn-main" onclick="startRound(<?php echo $question['id']; ?>)">
                                    ▶ Avvia Round
                                </button>
                                <button class="btn-main btn-warning" onclick="skipQuestion()">
                                    ⏭ Salta Domanda
                                </button>
                            <?php endif; ?>
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
        const activeRoundId = <?php echo $activeRound['id'] ?? 'null'; ?>;
        const judgeConnected = <?php echo $judgeConnected ? 'true' : 'false'; ?>;
        const isClickFirst = <?php echo $isClickFirst ? 'true' : 'false'; ?>;
        const gameOver = <?php echo $gameOver ? 'true' : 'false'; ?>;
        let timerInterval = null;

        const reload = () => location.href = 'room-admin.php?room_id=' + roomId;

        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($activeRound): ?>
            startCountdown(<?php echo $activeRound['timer'] ?? 30; ?>);
            <?php elseif ($gameOver): ?>
            loadFinalLeaderboard();
            <?php endif; ?>
        });

        function startCountdown(seconds) {
            let timeLeft = seconds;
            const timerEl = document.getElementById('timer');
            const nextBtn = document.getElementById('next-btn');

            timerInterval = setInterval(() => {
                timeLeft--;
                timerEl.textContent = timeLeft;

                if (timeLeft <= 5) timerEl.style.color = '#dc143c';
                else if (timeLeft <= 10) timerEl.style.color = '#ffc107';

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    setTimeout(() => onTimerEnd(nextBtn), 2000);
                }
            }, 1000);
        }

        function onTimerEnd(nextBtn) {
            const correctAnswer = <?php echo $activeRound['correct_answer'] ?? 'null'; ?>;

            if (!isClickFirst && correctAnswer) {
                document.querySelector('#option-' + correctAnswer)?.classList.add('correct');
            }

            if (activeRoundId) {
                api('game&action=close_round', { round_id: activeRoundId })
                    .then(() => loadRoundAnswers(activeRoundId))
                    .catch(() => loadRoundAnswers(activeRoundId));
            }

            if (nextBtn && !isClickFirst) {
                enableNextBtn(nextBtn);
            }
        }

        function enableNextBtn(btn) {
            btn.disabled = false;
            btn.style.animation = 'pulse 1s infinite';
        }

        function skipQuestion() {
            location.href = 'room-admin.php?room_id=' + roomId + '&skip=1';
        }

        function startRound(questionId) {
            api('game&action=start_round', { question_id: questionId })
                .then(data => { if (data.success) reload(); });
        }

        function nextQuestion() {
            if (isClickFirst && activeRoundId) {
                const selected = document.querySelector('input[name="clickfirst-winner"]:checked');
                if (!selected) { reload(); return; }

                api('game&action=set_clickfirst_winner', {
                    round_id: activeRoundId,
                    winner_index: parseInt(selected.value)
                }).then(data => { if (data.success) reload(); });
            } else {
                reload();
            }
        }

        function loadFinalLeaderboard() {
            api('final_leaderboard&room_id=' + roomId)
                .then(data => {
                    const el = document.getElementById('final-leaderboard');

                    if (!data.success || !data.leaderboard?.length) {
                        el.innerHTML = '<div class="empty-state">Nessun risultato disponibile</div>';
                        return;
                    }
                    const medals = ['🥇', '🥈', '🥉'];
                    el.innerHTML = data.leaderboard.map((p, i) => `
                        <div class="leaderboard-item">
                            <div class="leaderboard-position">#${i + 1}</div>
                            <div class="medal">${medals[i] || ''}</div>
                            <div class="leaderboard-info">
                                <div class="leaderboard-name">${p.username}</div>
                                <div class="leaderboard-time score-text">${p.score} punti</div>
                            </div>
                        </div>`).join('');
                });
        }

        function loadRoundAnswers(roundId) {
            if (isClickFirst && judgeConnected) {
                document.getElementById('leaderboard').innerHTML =
                    '<div class="empty-state">⚖️ In attesa del giudice...</div>';
                pollJudgeDecision(roundId);
                return;
            }

            api('round_answers&round_id=' + roundId)
                .then(data => {
                    const board = document.getElementById('leaderboard');
                    const nextBtn = document.getElementById('next-btn');

                    if (!data.success || !data.top_answers?.length) {
                        board.innerHTML = '<div class="empty-state">Nessuna risposta</div>';
                        if (isClickFirst && nextBtn) enableNextBtn(nextBtn);
                        return;
                    }

                    const medals = ['🥇', '🥈', '🥉'];
                    board.innerHTML = data.top_answers.map((a, i) => {
                        const medal = isClickFirst
                            ? `<input type="radio" name="clickfirst-winner" value="${i}">`
                            : (medals[i] || (i + 1 + '.'));
                        return `<div class="leaderboard-item">
                            <div class="medal">${medal}</div>
                            <div class="leaderboard-info">
                                <div class="leaderboard-name">${a.username}</div>
                                <div class="leaderboard-time">${parseFloat(a.answer_time).toFixed(2)}s</div>
                            </div>
                        </div>`;
                    }).join('');

                    if (isClickFirst && nextBtn) {
                        document.querySelectorAll('input[name="clickfirst-winner"]')
                            .forEach(r => r.addEventListener('change', () => enableNextBtn(nextBtn)));
                    }
                });
        }

        function pollJudgeDecision(roundId) {
            const poll = setInterval(() => {
                api('game&action=check_judge_decision&round_id=' + roundId)
                    .then(data => {
                        if (data.success && data.judge_decided) {
                            clearInterval(poll);
                            document.getElementById('leaderboard').innerHTML =
                                '<div class="empty-state">✅ Giudice ha deciso</div>';
                            reload();
                        }
                    });
            }, 1000);
        }

        function goBack() {
            if (gameOver) return deleteRoom();
            document.getElementById('modal-end-game').style.display = 'flex';
        }

        function deleteRoom() {
            api('delete_room', {})
            .then(data => {
                if (data.success) window.location.href = 'admin.php';
                else showToast('Errore: ' + (data.error || data.message), 'error');
            })
            .catch(() => showToast('Errore nella chiusura della stanza', 'error'));
        }
    </script>

    <!-- MODALE CONFERMA TERMINA PARTITA -->
    <div id="modal-end-game" class="modal-overlay" style="display: none;">
        <div class="modal-content modal-content-medium">
            <div class="modal-header">
                <h2>⚠️ Termina Partita</h2>
                <button type="button" class="modal-close" onclick="document.getElementById('modal-end-game').style.display='none';">✕</button>
            </div>
            <div class="modal-body">
                <p>Sei sicuro di voler terminare la partita?</p>
                <p class="modal-subtitle">Questa azione non può essere annullata.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-end-game').style.display='none';">Annulla</button>
                <button type="button" class="btn btn-danger" onclick="deleteRoom()">✓ Termina</button>
            </div>
        </div>
    </div>

    <div id="toast-container" class="toast-container"></div>
    <script>
        function showToast(message, type = 'info', duration = 3000) {
            const container = document.getElementById('toast-container');
            const icons = { success: '✓', error: '✗', warning: '⚠', info: 'ℹ' };

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.style.setProperty('--toast-duration', duration + 'ms');
            toast.innerHTML = `
                <span class="toast-icon">${icons[type] || icons.info}</span>
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.classList.add('toast-hiding'); setTimeout(() => this.parentElement.remove(), 300)">×</button>
                <div class="toast-progress"></div>
            `;

            container.appendChild(toast);
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.classList.add('toast-hiding');
                    setTimeout(() => toast.remove(), 300);
                }
            }, duration);
        }
    </script>
</body>
</html>
