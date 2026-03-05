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
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .judge-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            grid-auto-rows: max-content;
            gap: 20px;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            align-items: start;
        }

        .judge-main {
            background: #fff;
            border: 2px solid #333;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .judge-sidebar {
            background: #fff;
            border: 2px solid #333;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: fit-content;
        }

        .sidebar-title {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .waiting-screen {
            text-align: center;
            padding: 40px 20px;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f0f0f0;
            border-top: 4px solid #333;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 30px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .waiting-screen h2 {
            font-size: 1.4em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .waiting-screen p {
            font-size: 0.95em;
            color: #666;
        }

        .round-info {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .round-info h2 {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        #question-text {
            font-size: 1.2em;
            color: #333;
            margin: 15px 0;
            font-weight: 600;
            line-height: 1.6;
        }

        .timer-box {
            background: #f5f5f5;
            color: #333;
            border: 2px solid #333;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .timer-box h4 {
            margin: 0 0 12px 0;
            font-size: 1em;
            font-weight: 600;
            color: #333;
        }

        #timer-value {
            font-size: 3em;
            font-weight: bold;
            color: #333;
            font-variant-numeric: tabular-nums;
        }

        .round-type-badge {
            display: inline-block;
            padding: 4px 12px;
            font-size: 0.85em;
            font-weight: 600;
            border: 1px solid #333;
            margin-bottom: 10px;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .option-btn {
            padding: 12px;
            background: #f0f0f0;
            color: #333;
            border: 1px solid #999;
            font-weight: 600;
            font-size: 0.95em;
            text-align: center;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .option-btn.correct {
            background: #4caf50;
            color: #fff;
            border-color: #333;
            font-weight: bold;
        }

        .btn-judge-confirm {
            width: 100%;
            padding: 12px 20px;
            margin-top: 15px;
            font-size: 1em;
            font-weight: 600;
            background: #fff;
            color: #333;
            border: 2px solid #333;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }

        .btn-judge-confirm:hover:not(:disabled) {
            background: #333;
            color: #fff;
        }

        .btn-judge-confirm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f0f0f0;
        }

        .judge-waiting-msg {
            text-align: center;
            padding: 15px;
            color: #666;
            font-style: italic;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            margin-bottom: 8px;
            border-left: 3px solid #333;
            transition: all 0.2s ease;
        }

        .leaderboard-item:hover {
            background: #f0f0f0;
        }

        .medal {
            font-size: 1.3em;
            min-width: 30px;
            text-align: center;
        }

        .leaderboard-info {
            flex: 1;
        }

        .leaderboard-name {
            font-weight: 600;
            color: #333;
            font-size: 0.95em;
        }

        .leaderboard-time {
            font-size: 0.85em;
            color: #666;
        }

        .empty-state {
            color: #999;
            font-size: 0.9em;
            padding: 15px;
            text-align: center;
        }

        .final-result-screen {
            text-align: center;
            padding: 40px 20px;
            display: none;
        }

        .final-leaderboard {
            max-width: 500px;
            margin: 20px auto;
            text-align: left;
        }

        .final-leaderboard .leaderboard-item {
            border-left-width: 4px;
        }

        .game-cancelled-screen {
            text-align: center;
            padding: 60px 20px;
            display: none;
            background: linear-gradient(135deg, #9E9E9E 0%, #757575 100%);
        }

        .game-cancelled-screen h1 {
            font-size: 2em;
            color: #fff;
            margin-bottom: 15px;
        }

        .game-cancelled-screen p {
            font-size: 1.1em;
            color: #fff;
        }

        .game-cancelled-emoji {
            font-size: 4em;
            margin-bottom: 20px;
        }

        @media (max-width: 1024px) {
            .judge-container {
                grid-template-columns: 1fr;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .judge-main { padding: 15px; }
            .judge-sidebar { padding: 15px; }
            .judge-container { margin: 15px auto; padding: 0 10px; }
            #timer-value { font-size: 2.5em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚖️ Giudice</h1>
            <div class="user-info">
                <span>Stanza: <?php echo htmlspecialchars($judge['room_code'] ?? ''); ?></span>
                <a href="logout.php?role=judge" class="btn btn-secondary">Logout</a>
            </div>
        </div>

        <div class="judge-container">
            <div class="judge-main">
                <!-- Waiting -->
                <div id="waiting-screen" class="waiting-screen">
                    <div class="spinner"></div>
                    <h2>In attesa del prossimo round...</h2>
                    <p>L'admin avvierà presto un nuovo round</p>
                </div>

                <!-- Round in corso -->
                <div id="game-screen" style="display: none;">
                    <div class="round-info">
                        <span class="round-type-badge" id="round-type-badge"></span>
                        <h2>Round <span id="round-number">-</span></h2>
                        <p id="question-text"></p>
                    </div>

                    <div id="options-container" class="options-grid" style="display: none;"></div>

                    <div class="timer-box">
                        <h4>⏱️ Timer Rimanente</h4>
                        <span id="timer-value">-</span>
                    </div>

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

            <div class="judge-sidebar">
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
                    if (data.status === 'closed') {
                        gameEnded = true;
                        clearAllIntervals();
                        showFinalLeaderboard();
                    } else if (data.status === 'cancelled') {
                        gameEnded = true;
                        clearAllIntervals();
                        showGameCancelled();
                    }
                });
        }

        function checkGameState() {
            if (gameEnded) return;

            fetch('../src/api/api.php?endpoint=game&action=get_game_state&counter=' + currentRoundCounter)
                .then(r => r.json())
                .then(data => {
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
            currentRoundType = round.round_type || 'multiple';

            document.getElementById('round-number').textContent = round.round_number;
            document.getElementById('question-text').textContent = round.question || '';

            const typeLabels = {
                'multiple': '📝 Scelta Multipla',
                'truefalse': '✅ Vero/Falso',
                'clickfirst': '⚡ Clicca per Primo'
            };
            document.getElementById('round-type-badge').textContent = typeLabels[round.round_type] || round.round_type;

            // Mostra le opzioni di risposta
            const optionsContainer = document.getElementById('options-container');
            if (round.round_type === 'clickfirst') {
                optionsContainer.style.display = 'none';
                optionsContainer.innerHTML = '';
            } else {
                let optionsHtml = '';
                const maxOptions = round.round_type === 'truefalse' ? 2 : 4;
                const tfLabels = { 1: 'Vero', 2: 'Falso' };
                for (let i = 1; i <= maxOptions; i++) {
                    const text = round.round_type === 'truefalse'
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
                else timerEl.style.color = '#333';

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerEl.style.color = '#333';
                    onRoundEnd();
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

            // Attendi 2 secondi (come room-admin), poi carica classifica round
            setTimeout(() => {
                loadRoundLeaderboard(currentRoundId);
            }, 2000);
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
            document.querySelector('.judge-sidebar').style.display = 'none';
            document.querySelector('.judge-container').style.gridTemplateColumns = '1fr';
            document.querySelector('.judge-container').style.maxWidth = '800px';

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
                                    <div class="leaderboard-time" style="color: #333; font-weight: 600;">${p.score} punti</div>
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
            document.querySelector('.judge-sidebar').style.display = 'none';
            document.querySelector('.judge-container').style.gridTemplateColumns = '1fr';
            document.querySelector('.judge-container').style.maxWidth = '800px';
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
