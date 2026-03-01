<?php
require_once __DIR__ . '/../src/utils/auth.php';

// Check if player is logged in
requirePlayer();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .player-container {
            display: grid;
            grid-template-columns: 1fr;
            grid-auto-rows: max-content;
            gap: 20px;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            align-items: start;
        }

        .player-main {
            background: #fff;
            border: 2px solid #333;
            border-radius: 0;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .sidebar-title {
            font-weight: 600;
            font-size: 1.1em;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
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
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .round-info h2 {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 15px;
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
            border-radius: 0;
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

        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .option-btn {
            background: #fff;
            border: 2px solid #333;
            border-radius: 0;
            padding: 25px 15px;
            cursor: pointer;
            text-align: center;
            font-size: 1em;
            font-weight: 600;
            color: #333;
            transition: all 0.2s ease;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .option-btn:hover:not(:disabled) {
            background: #333;
            color: #fff;
        }

        .option-btn.selected {
            background: #333;
            color: #fff;
        }

        .option-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-main {
            flex: 1;
            min-width: 200px;
            padding: 12px 20px;
            font-size: 1em;
            font-weight: 600;
            border: 2px solid #333;
            border-radius: 0;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #fff;
            color: #333;
        }

        .btn-main:hover:not(:disabled) {
            background: #333;
            color: #fff;
        }

        .btn-main:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f0f0f0;
        }

        .click-first-btn {
            background: #fff;
            color: #333;
            border: 2px solid #333;
            padding: 40px 60px;
            font-size: 1.8em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-block;
            margin: 20px auto;
        }

        .click-first-btn:hover:not(:disabled) {
            background: #333;
            color: #fff;
        }

        .click-first-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .result-screen {
            text-align: center;
            padding: 30px;
        }

        .result-screen h2 {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .result-screen p {
            font-size: 0.95em;
            color: #666;
        }

        .final-result-screen {
            text-align: center;
            padding: 60px 20px;
            display: none;
        }

        .final-result-screen.winner {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }

        .final-result-screen.loser {
            background: linear-gradient(135deg, #E0E0E0 0%, #BDBDBD 100%);
        }

        .final-result-emoji {
            font-size: 6em;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .final-result-screen h1 {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 20px;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .final-result-screen p {
            font-size: 1.2em;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 0;
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

        @media (max-width: 1024px) {
            .player-container {
                grid-template-columns: 1fr;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .player-main {
                padding: 15px;
            }

            .player-sidebar {
                padding: 15px;
            }

            .round-info {
                margin-bottom: 20px;
                padding-bottom: 15px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn-main {
                width: 100%;
            }

            #timer-value {
                font-size: 2.5em;
            }

            .player-container {
                margin: 15px auto;
                padding: 0 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Giocatore</h1>
            <div class="user-info">
                <span>Giocatore: <?php echo htmlspecialchars(authUsername() ?? 'Giocatore'); ?></span>
                <a href="logout.php?logout=1" class="btn btn-secondary">Logout</a>
            </div>
        </div>

        <div class="player-container">
            <div class="player-main">
                <div id="waiting-screen" class="waiting-screen">
                    <div class="spinner"></div>
                    <h2>In attesa del prossimo round...</h2>
                    <p>L'admin avvierà presto un nuovo round</p>
                </div>

                <div id="game-screen" style="display: none;">
                    <div class="round-info">
                        <h2>Round <span id="round-number">-</span></h2>
                        <p id="question-text"></p>
                    </div>

                    <div class="timer-box">
                        <h4>⏱️ Timer Rimanente</h4>
                        <span id="timer-value">10</span>
                    </div>

                    <div id="answer-grid" class="options-grid">
                        <div class="option-btn" data-answer="1" onclick="selectAnswer(1)" id="btn-1"></div>
                        <div class="option-btn" data-answer="2" onclick="selectAnswer(2)" id="btn-2"></div>
                        <div class="option-btn" data-answer="3" onclick="selectAnswer(3)" id="btn-3"></div>
                        <div class="option-btn" data-answer="4" onclick="selectAnswer(4)" id="btn-4"></div>
                    </div>

                    <div id="click-first-screen" style="display: none; text-align: center;">
                        <button class="click-first-btn" onclick="submitClickFirst()" id="click-first-btn">⚡ CLICCA!</button>
                    </div>
                </div>

                <div id="result-screen" class="result-screen" style="display: none;">
                    <div id="result-content">
                        <h2>Risposta registrata!</h2>
                        <p style="margin-top: 15px;">In attesa del prossimo round...</p>
                    </div>
                </div>

                <div id="final-result-screen" class="final-result-screen">
                    <div id="final-result-content">
                        <div class="final-result-emoji" id="final-emoji">🏆</div>
                        <h1 id="final-title">Ho Vinto!</h1>
                        <p id="final-message">Complimenti! Sei il vincitore di questa partita!</p>
                    </div>
                </div>

                <div id="game-cancelled-screen" class="final-result-screen" style="background: linear-gradient(135deg, #9E9E9E 0%, #757575 100%);">
                    <div id="game-cancelled-content">
                        <div class="final-result-emoji">❌</div>
                        <h1>Partita Annullata</h1>
                        <p>L'amministratore ha chiuso la partita</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentRoundCounter = 1;
        let currentRoundId = null;
        let timerInterval = null;
        let startTime = null;
        let hasAnswered = false;
        let selectedAnswer = null;
        let roundInProgress = false;
        let gameEnded = false;  // Flag to stop polling when game ends
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
                .then(response => response.json())
                .then(data => {
                    // Se la room è chiusa (partita finita), mostra vincitore/perdente
                    if (data.status === 'closed') {
                        gameEnded = true;
                        clearInterval(checkGameStateInterval);
                        clearInterval(checkRoomStatusInterval);
                        checkWinnerStatus();
                        return;
                    }
                    // Se la room è cancellata dall'admin, mostra schermata annullamento
                    if (data.status === 'cancelled') {
                        gameEnded = true;
                        clearInterval(checkGameStateInterval);
                        clearInterval(checkRoomStatusInterval);
                        showGameCancelled();
                        return;
                    }
                });
        }

        function checkGameState() {
            if (gameEnded) return;  // Stop checking if game has ended

            fetch('../src/api/api.php?endpoint=game&action=get_game_state&counter=' + currentRoundCounter)
                .then(response => response.json())
                .then(data => {
                    console.log("checkGameState response:", data);

                    // Verifica se la partita è terminata (room closed o vincitore)
                    if (data.game_finished || data.status_room === 'closed') {
                        console.log("Game finished detected (status_room=closed), checking winner");
                        gameEnded = true;
                        clearInterval(checkGameStateInterval);
                        clearInterval(checkRoomStatusInterval);
                        checkWinnerStatus();
                        return;
                    }

                    // Check if admin cancelled the room
                    if (data.status_room === 'cancelled') {
                        console.log("Game cancelled by admin (room status = cancelled)");
                        gameEnded = true;
                        clearInterval(checkGameStateInterval);
                        clearInterval(checkRoomStatusInterval);
                        showGameCancelled();
                        return;
                    }

                    if (data.success === false || !data.round_number) {
                        console.log("No round available, showing waiting screen");
                        showWaitingScreen();
                        return;
                    }

                    const roundNumber = data.round_number;
                    if (roundNumber === currentRoundCounter && !hasAnswered && !roundInProgress) {
                        console.log("Starting round", roundNumber);
                        startRound(data);
                    } else if (hasAnswered) {
                        console.log("Already answered, showing waiting screen");
                        showWaitingScreen();
                    }
                });
        }

        function startRound(round) {
            roundInProgress = true;
            clearInterval(timerInterval);

            hasAnswered = false;
            selectedAnswer = null;
            startTime = Date.now();
            currentRoundId = round.id;

            document.getElementById('round-number').textContent = round.round_number;
            document.getElementById('question-text').textContent = round.question || '';

            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'block';

            const answerGrid = document.getElementById('answer-grid');
            const clickFirstScreen = document.getElementById('click-first-screen');

            if (round.round_type === 'clickfirst') {
                answerGrid.style.display = 'none';
                clickFirstScreen.style.display = 'block';
                document.getElementById('click-first-btn').disabled = false;
            } else if (round.round_type === 'truefalse') {
                setupRound(round, 2);
            } else {
                setupRound(round, 4);
            }

            startTimer(round.timer || 10);
        }

        function setupRound(round, numOptions) {
            const answerGrid = document.getElementById('answer-grid');
            answerGrid.style.display = 'grid';
            document.getElementById('click-first-screen').style.display = 'none';

            for (let i = 1; i <= 4; i++) {
                const btn = document.getElementById('btn-' + i);
                if (i <= numOptions) {
                    btn.style.display = 'flex';
                    btn.textContent = round['option' + i] || 'Opzione ' + i;
                } else {
                    btn.style.display = 'none';
                }
                btn.classList.remove('selected');
            }
        }

        function selectAnswer(answer) {
            if (hasAnswered) return;

            selectedAnswer = answer;

            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.classList.remove('selected');
            });

            document.querySelector(`[data-answer="${answer}"]`).classList.add('selected');

            // Invia direttamente la risposta senza aspettare il pulsante
            hasAnswered = true;
            const timeTaken = (Date.now() - startTime) / 1000;
            clearInterval(timerInterval);

            submitAnswer(selectedAnswer, timeTaken);
        }

        function submitSelectedAnswer() {
            // Questa funzione non è più usata ma la lascio per compatibilità
            if (hasAnswered || !selectedAnswer) return;

            hasAnswered = true;
            const timeTaken = (Date.now() - startTime) / 1000;
            clearInterval(timerInterval);

            submitAnswer(selectedAnswer, timeTaken);
        }

        function submitClickFirst() {
            if (hasAnswered) return;

            hasAnswered = true;
            clearInterval(timerInterval);
            document.getElementById('click-first-btn').disabled = true;

            const timeTaken = (Date.now() - startTime) / 1000;
            submitAnswer(1, timeTaken);
        }

        function submitAnswer(answer, timeTaken) {
            fetch('../src/api/api.php?endpoint=answer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    round_id: currentRoundId,
                    answer: answer,
                    time_taken: timeTaken
                })
            })
            .then(response => response.json())
            .then(() => {
                currentRoundCounter++;

                document.getElementById('game-screen').style.display = 'none';
                document.getElementById('result-screen').style.display = 'block';

                setTimeout(() => {
                    document.getElementById('result-screen').style.display = 'none';
                    showWaitingScreen();
                }, 2000);
            });
        }

        function startTimer(initialTime = 10) {
            let timeLeft = parseInt(initialTime);
            if (isNaN(timeLeft) || timeLeft <= 0) timeLeft = 10;

            document.getElementById('timer-value').textContent = timeLeft;

            if (timerInterval) clearInterval(timerInterval);

            timerInterval = setInterval(() => {
                timeLeft--;
                document.getElementById('timer-value').textContent = timeLeft;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    if (!hasAnswered) timeExpired();
                }
            }, 1000);
        }

        function timeExpired() {
            if (hasAnswered) return;

            hasAnswered = true;
            currentRoundCounter++;

            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'block';
            document.getElementById('result-content').innerHTML = '<h2>⏱ Tempo scaduto!</h2><p>Non hai risposto in tempo</p>';

            setTimeout(() => {
                document.getElementById('result-screen').style.display = 'none';
                showWaitingScreen();
            }, 2000);
        }

        function showWaitingScreen() {
            hasAnswered = false;
            roundInProgress = false;

            if (timerInterval) clearInterval(timerInterval);

            document.getElementById('waiting-screen').style.display = 'block';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'none';
            document.getElementById('final-result-screen').style.display = 'none';
            document.getElementById('game-cancelled-screen').style.display = 'none';
        }

        function checkWinnerStatus() {
            console.log("checkWinnerStatus called - polling for winner");
            // Poll for winner check, retrying until we get a result
            const maxAttempts = 20;
            let attempts = 0;

            function tryCheckWinner() {
                fetch('../src/api/api.php?endpoint=game&action=check_winner')
                    .then(response => response.json())
                    .then(data => {
                        console.log("Winner check attempt", attempts + 1, ":", data);
                        if (data.success) {
                            console.log("Winner check result - is_winner:", data.is_winner);
                            showFinalResult(data.is_winner);
                        } else {
                            console.log("Winner check failed:", data.error);
                            attempts++;
                            if (attempts < maxAttempts) {
                                setTimeout(tryCheckWinner, 500);
                            } else {
                                // Fallback: show waiting screen if we can't determine winner
                                console.log("Max attempts reached, showing waiting screen");
                                showWaitingScreen();
                            }
                        }
                    })
                    .catch(error => {
                        console.log("Winner check error:", error);
                        attempts++;
                        if (attempts < maxAttempts) {
                            setTimeout(tryCheckWinner, 500);
                        } else {
                            showWaitingScreen();
                        }
                    });
            }

            tryCheckWinner();
        }

        function showFinalResult(isWinner) {
            // Hide all other screens
            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'none';
            document.getElementById('final-result-screen').style.display = 'block';
            document.getElementById('game-cancelled-screen').style.display = 'none';

            const finalScreen = document.getElementById('final-result-screen');
            const emoji = document.getElementById('final-emoji');
            const title = document.getElementById('final-title');
            const message = document.getElementById('final-message');

            if (isWinner) {
                finalScreen.classList.remove('loser');
                finalScreen.classList.add('winner');
                emoji.textContent = '🏆';
                title.textContent = 'Ho Vinto!';
                message.textContent = 'Complimenti! Sei il vincitore di questa partita! 🎉';
            } else {
                finalScreen.classList.remove('winner');
                finalScreen.classList.add('loser');
                emoji.textContent = '😢';
                title.textContent = 'Ho Perso!';
                message.textContent = 'Buona fortuna nella prossima partita!';
            }
        }

        function showGameCancelled() {
            // Hide all other screens
            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'none';
            document.getElementById('final-result-screen').style.display = 'none';
            document.getElementById('game-cancelled-screen').style.display = 'block';
        }
    </script>
</body>
</html>
