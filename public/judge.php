<?php
require_once __DIR__ . '/../src/utils/auth.php';

requireJudge();
$judge = authJudge();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giudice - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=17">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚖️ Giudice</h1>
            <a href="logout.php?role=judge" class="btn btn-secondary">Logout</a>
        </div>

        <div class="game-grid">
            <div class="game-card">
                <!-- Waiting -->
                <div id="waiting-screen" class="waiting-screen">
                    <div class="spinner"></div>
                    <h2>In attesa del prossimo round...</h2>
                    <p>L'admin avvierà presto un nuovo round</p>
                </div>

                <!-- Round in corso -->
                <div id="game-screen" style="display: none;">
                    <div class="category-header" id="category-header-bar">
                        <span class="round-label">Domanda <span id="round-number">-</span> — <span id="category-name"></span></span>
                        <span class="timer-label">⏱️ <span id="timer-value">-</span></span>
                    </div>

                    <p id="question-text"></p>

                    <div id="options-container" class="options-grid" style="display: none;"></div>

                    <button id="judge-confirm-btn" class="btn-judge-confirm" disabled onclick="judgeNextRound()">
                        ➡️ Prossima Domanda
                    </button>
                </div>

                <!-- Fine partita -->
                <div id="final-result-screen" class="final-result-screen">
                    <h2>🏆 Classifica Finale</h2>
                    <div id="final-leaderboard" class="final-leaderboard">
                        <div class="empty-state">Caricamento...</div>
                    </div>
                </div>

                <!-- Partita annullata -->
                <div id="game-cancelled-screen" class="game-cancelled-screen">
                    <div class="game-cancelled-emoji">❌</div>
                    <h1>Partita Annullata</h1>
                    <p>L'amministratore ha chiuso la partita</p>
                </div>
            </div>

            <div class="game-sidebar">
                <div class="sidebar-title">🏆 Classifica</div>
                <div id="sidebar-leaderboard">
                    <div class="empty-state">Nessun dato</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentRoundCounter = 1;
        let currentRoundId = null;
        let currentCorrectAnswer = null;
        let currentRoundType = null;
        let timerInterval = null;
        let roundInProgress = false;
        let roundEnded = false;
        let gameEnded = false;
        let checkGameStateInterval = null;
        let checkRoomStatusInterval = null;

        document.addEventListener('DOMContentLoaded', () => {
            checkRoomStatusInterval = setInterval(checkRoomStatus, 1000);
            checkGameStateInterval = setInterval(checkGameState, 1000);
            checkGameState();
        });

        function checkRoomStatus() {
            if (gameEnded) return;

            fetch('../src/api/api.php?endpoint=check_room_status')
                .then(r => r.json())
                .then(data => {
                    if (gameEnded) return;

                    if (data.status === 'closed') {
                        gameEnded = true;
                        clearAllIntervals();
                        showFinalLeaderboard();
                    } else if (data.status === 'cancelled') {
                        gameEnded = true;
                        clearAllIntervals();
                        showGameCancelled();
                    }
                })
                .catch(() => {});
        }

        function checkGameState() {
            if (gameEnded) return;

            fetch('../src/api/api.php?endpoint=game&action=get_game_state&counter=' + currentRoundCounter)
                .then(r => r.json())
                .then(data => {
                    if (gameEnded) return;

                    if (data.game_finished || data.status_room === 'closed') {
                        gameEnded = true;
                        clearAllIntervals();
                        showFinalLeaderboard();
                        return;
                    }

                    if (data.status_room === 'cancelled') {
                        gameEnded = true;
                        clearAllIntervals();
                        showGameCancelled();
                        return;
                    }

                    if (data.success === false || !data.round_number) {
                        if (!roundInProgress) showWaitingScreen();
                        return;
                    }

                    const roundNumber = data.round_number;
                    if (roundNumber === currentRoundCounter && !roundInProgress) {
                        startRound(data);
                    }
                });
        }

        function startRound(round) {
            roundInProgress = true;
            roundEnded = false;
            currentRoundId = round.id;
            currentCorrectAnswer = round.correct_answer || null;
            currentRoundType = round.question_type || 'multiple';

            // Colora bordo card + header
            const catColor = round.category_color || 'transparent';
            const catName = round.category_name || '';
            const gameCard = document.querySelector('.game-card');
            if (gameCard) gameCard.style.border = `5px solid ${catColor}`;
            const headerBar = document.getElementById('category-header-bar');
            if (headerBar) headerBar.style.background = catColor;

            document.getElementById('round-number').textContent = round.round_number;
            const isClickFirst = currentRoundType === 'clickfirst';
            document.getElementById('category-name').textContent = (isClickFirst ? '⚡ ' : '') + catName;
            document.getElementById('question-text').textContent = round.question || '';

            // Mostra le opzioni di risposta
            const optionsContainer = document.getElementById('options-container');
            if (round.question_type === 'clickfirst') {
                optionsContainer.style.display = 'none';
                optionsContainer.innerHTML = '';
            } else {
                let optionsHtml = '';
                const maxOptions = round.question_type === 'truefalse' ? 2 : 4;
                const tfLabels = { 1: 'Vero', 2: 'Falso' };
                for (let i = 1; i <= maxOptions; i++) {
                    const text = round.question_type === 'truefalse'
                        ? tfLabels[i]
                        : (round['option' + i] || '');
                    if (text) {
                        optionsHtml += `<div class="option-btn" id="judge-option-${i}">${text}</div>`;
                    }
                }
                optionsContainer.innerHTML = optionsHtml;
                optionsContainer.style.display = 'grid';
            }

            document.getElementById('sidebar-leaderboard').innerHTML = '<div class="empty-state">In attesa...</div>';

            hideAll();
            document.getElementById('game-screen').style.display = 'block';

            startTimer(round.timer || 10);
        }

        function startTimer(initialTime) {
            let timeLeft = parseInt(initialTime);
            if (isNaN(timeLeft) || timeLeft <= 0) timeLeft = 10;

            document.getElementById('timer-value').textContent = timeLeft;

            if (timerInterval) clearInterval(timerInterval);

            timerInterval = setInterval(() => {
                timeLeft--;
                const timerEl = document.getElementById('timer-value');
                timerEl.textContent = timeLeft;

                if (timeLeft <= 5) timerEl.style.color = '#dc143c';
                else if (timeLeft <= 10) timerEl.style.color = '#ffc107';
                else timerEl.style.color = '#2d3436';

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerEl.style.color = '#2d3436';
                    setTimeout(() => onRoundEnd(), 2000);
                }
            }, 1000);
        }

        function onRoundEnd() {
            if (roundEnded) return;
            roundEnded = true;

            // Evidenzia la risposta corretta
            if (currentCorrectAnswer && currentRoundType !== 'clickfirst') {
                const correctEl = document.getElementById('judge-option-' + currentCorrectAnswer);
                if (correctEl) {
                    correctEl.classList.add('correct');
                }
            }

            // Carica classifica round (delay già applicato prima di onRoundEnd)
            loadRoundLeaderboard(currentRoundId);
        }

        function loadRoundLeaderboard(roundId) {
            const confirmBtn = document.getElementById('judge-confirm-btn');
            confirmBtn.disabled = true;

            fetch('../src/api/api.php?endpoint=round_answers&round_id=' + roundId)
                .then(r => r.json())
                .then(data => {
                    const isClickFirst = currentRoundType === 'clickfirst';

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

                        document.getElementById('sidebar-leaderboard').innerHTML = html;

                        if (isClickFirst) {
                            document.querySelectorAll('input[name="clickfirst-winner"]').forEach(radio => {
                                radio.addEventListener('change', () => {
                                    confirmBtn.disabled = false;
                                });
                            });
                        }
                    } else {
                        document.getElementById('sidebar-leaderboard').innerHTML = '<div class="empty-state">Nessuna risposta</div>';
                    }

                    if (isClickFirst) {
                        // Per clickfirst: abilitato subito se nessuna risposta, altrimenti dopo selezione radio
                        if (!data.success || !data.top_answers?.length) {
                            confirmBtn.disabled = false;
                        }
                    } else {
                        // Per altri round: bottone disabilitato, attendi che l'admin avanzi
                        confirmBtn.disabled = true;
                        waitForNextRound();
                    }
                });
        }

        function judgeNextRound() {
            const confirmBtn = document.getElementById('judge-confirm-btn');
            confirmBtn.disabled = true;

            const selected = document.querySelector('input[name="clickfirst-winner"]:checked');

            function signalJudgeAdvance() {
                return fetch('../src/api/api.php?endpoint=game&action=judge_advance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ round_id: currentRoundId })
                });
            }

            if (currentRoundType === 'clickfirst' && selected) {
                // Clickfirst con vincitore selezionato
                fetch('../src/api/api.php?endpoint=game&action=set_clickfirst_winner', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        round_id: currentRoundId,
                        winner_index: parseInt(selected.value)
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        return signalJudgeAdvance();
                    }
                })
                .then(() => {
                    waitForNextRound();
                });
            } else {
                // Clickfirst senza risposte: segnala advance
                signalJudgeAdvance().then(() => {
                    waitForNextRound();
                });
            }
        }

        function waitForNextRound() {
            const nextCounter = currentRoundCounter + 1;
            const pollInterval = setInterval(() => {
                if (gameEnded) {
                    clearInterval(pollInterval);
                    return;
                }

                fetch('../src/api/api.php?endpoint=game&action=get_game_state&counter=' + nextCounter)
                    .then(r => r.json())
                    .then(data => {
                        if (data.game_finished || data.status_room === 'closed') {
                            clearInterval(pollInterval);
                            gameEnded = true;
                            clearAllIntervals();
                            showFinalLeaderboard();
                            return;
                        }

                        if (data.status_room === 'cancelled') {
                            clearInterval(pollInterval);
                            gameEnded = true;
                            clearAllIntervals();
                            showGameCancelled();
                            return;
                        }

                        if (data.success && data.round_number) {
                            clearInterval(pollInterval);
                            currentRoundCounter = nextCounter;
                            roundInProgress = false;
                            startRound(data);
                        }
                    });
            }, 1000);
        }

        function showFinalLeaderboard() {
            hideAll();
            document.getElementById('final-result-screen').style.display = 'block';
            document.querySelector('.game-sidebar').style.display = 'none';
            document.querySelector('.game-grid').style.gridTemplateColumns = '1fr';
            document.querySelector('.game-grid').style.maxWidth = '800px';

            fetch('../src/api/api.php?endpoint=final_leaderboard&room_id=<?php echo $judge['room_id'] ?? 0; ?>')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.leaderboard?.length > 0) {
                        let html = '';
                        data.leaderboard.forEach(p => {
                            html += `<div class="leaderboard-item">
                                <div class="medal">${p.medal}</div>
                                <div class="leaderboard-info">
                                    <div class="leaderboard-name">${p.username}</div>
                                    <div class="leaderboard-time score-text">${p.score} punti</div>
                                </div>
                            </div>`;
                        });
                        document.getElementById('final-leaderboard').innerHTML = html;
                    } else {
                        document.getElementById('final-leaderboard').innerHTML = '<div class="empty-state">Nessun risultato disponibile</div>';
                    }
                });
        }

        function showWaitingScreen() {
            if (roundInProgress) return;
            hideAll();
            document.getElementById('waiting-screen').style.display = 'block';
        }

        function showGameCancelled() {
            hideAll();
            document.getElementById('game-cancelled-screen').style.display = 'block';
            document.querySelector('.game-sidebar').style.display = 'none';
            document.querySelector('.game-grid').style.gridTemplateColumns = '1fr';
            document.querySelector('.game-grid').style.maxWidth = '800px';
        }

        function hideAll() {
            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('final-result-screen').style.display = 'none';
            document.getElementById('game-cancelled-screen').style.display = 'none';
        }

        function clearAllIntervals() {
            if (checkGameStateInterval) clearInterval(checkGameStateInterval);
            if (checkRoomStatusInterval) clearInterval(checkRoomStatusInterval);
            if (timerInterval) clearInterval(timerInterval);
        }
    </script>
</body>
</html>
