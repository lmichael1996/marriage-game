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
    <link rel="stylesheet" href="../assets/css/logo.css">
    <style>
        body {
            background: #f0f2f5;
            padding: 20px;
            display: block;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ===== HEADER ===== */
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
            font-size: 1.4em;
            font-weight: 800;
            color: #2d3436;
        }

        .header .user-info span {
            font-weight: 600;
            color: #2d3436;
        }

        /* ===== LAYOUT ===== */
        .player-container {
            max-width: 700px;
            margin: 0 auto;
        }

        /* ===== MAIN CARD ===== */
        .player-main {
            background: #fff;
            border: none;
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        /* ===== CATEGORY HEADER BAR (colored) ===== */
        .category-header {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.4s ease;
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

        /* ===== WAITING SCREEN ===== */
        .waiting-screen {
            text-align: center;
            padding: 60px 25px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e9ecef;
            border-top: 4px solid #74b9ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 25px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .waiting-screen h2 {
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 10px;
            color: #2d3436;
        }

        .waiting-screen p {
            font-size: 0.95em;
            color: #636e72;
        }

        /* ===== QUESTION AREA ===== */
        .round-info {
            padding: 35px 25px;
            text-align: center;
            border: none;
            margin: 0;
        }

        #question-text {
            font-size: 1.35em;
            color: #2d3436;
            margin: 0;
            font-weight: 800;
            line-height: 1.4;
        }

        /* ===== ANSWER BUTTONS (rounded pills, single column) ===== */
        .options-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 0;
            padding: 0 25px 25px;
        }

        .option-btn {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            padding: 18px 28px;
            cursor: pointer;
            text-align: center;
            font-size: 1.05em;
            font-weight: 700;
            color: #2d3436;
            transition: all 0.2s ease;
            min-height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
        }

        .option-btn:hover:not(:disabled) {
            background: #e9ecef;
            border-color: #74b9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .option-btn.selected {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(26,26,46,0.3);
        }

        .option-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ===== ACTION BUTTONS ===== */
        .button-group {
            display: flex;
            gap: 10px;
            padding: 0 25px 25px;
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
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(26,26,46,0.3);
            font-family: inherit;
        }

        .btn-main:hover:not(:disabled) {
            background: linear-gradient(135deg, #16213e 0%, #0f3460 100%);
            box-shadow: 0 6px 20px rgba(26,26,46,0.4);
            transform: translateY(-2px);
        }

        .btn-main:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ===== CLICK FIRST ===== */
        .click-first-btn {
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 30px 60px;
            font-size: 1.6em;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-block;
            margin: 20px auto;
            box-shadow: 0 6px 25px rgba(225,112,85,0.4);
            font-family: inherit;
        }

        .click-first-btn:hover:not(:disabled) {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 10px 35px rgba(225,112,85,0.5);
        }

        .click-first-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ===== RESULT SCREEN ===== */
        .result-screen {
            text-align: center;
            padding: 40px 25px;
        }

        .result-screen h2 {
            font-size: 1.4em;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 10px;
        }

        .result-screen p {
            font-size: 0.95em;
            color: #636e72;
        }

        /* ===== FINAL RESULT ===== */
        .final-result-screen {
            text-align: center;
            padding: 60px 20px;
            display: none;
            border-radius: 20px;
        }

        .final-result-screen.winner {
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
        }

        .final-result-screen.loser {
            background: linear-gradient(135deg, #b2bec3 0%, #636e72 100%);
        }

        .final-result-emoji {
            font-size: 5em;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .final-result-screen h1 {
            font-size: 2.5em;
            font-weight: 800;
            margin-bottom: 15px;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.15);
        }

        .final-result-screen p {
            font-size: 1.1em;
            color: #fff;
        }

        .social-links a:hover {
            background: rgba(255,255,255,0.3) !important;
            opacity: 1;
            text-decoration: none;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 640px) {
            body { padding: 10px; }

            .header {
                padding: 16px 18px;
                border-radius: 16px;
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .header h1 { font-size: 1.2em; }

            .player-main { border-radius: 16px; }

            .category-header { padding: 14px 18px; }

            .round-info { padding: 25px 18px; }

            #question-text { font-size: 1.15em; }

            .options-grid { padding: 0 18px 18px; gap: 10px; }

            .option-btn { padding: 16px 20px; font-size: 0.95em; }

            .button-group {
                flex-direction: column;
                padding: 0 18px 18px;
            }

            .btn-main { width: 100%; min-width: unset; }

            .click-first-btn {
                padding: 25px 50px;
                font-size: 1.4em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Giocatore <?php echo htmlspecialchars(authUsername() ?? 'Giocatore'); ?></h1>
            <a href="logout.php?role=player" class="btn btn-secondary">Logout</a>
        </div>

        <div class="player-container">
            <div class="player-main">
                <!-- Category colored header bar -->
                <div id="category-header" class="category-header" style="display: none;">
                    <span class="round-label">Domanda <span id="header-round">-</span> — <span id="header-category">Categoria</span></span>
                    <span class="timer-label">⏱️ <span id="header-timer">10</span></span>
                </div>

                <div id="waiting-screen" class="waiting-screen">
                    <div class="spinner"></div>
                    <h2>In attesa del prossimo round...</h2>
                    <p>L'admin avvierà presto un nuovo round</p>
                </div>

                <div id="game-screen" style="display: none;">
                    <div class="round-info">
                        <p id="question-text"></p>
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
                        <h1 id="final-title">Hai Vinto!</h1>
                        <p id="final-message">Complimenti! Sei il vincitore di questa partita!</p>

                        <div class="social-links" style="margin-top: 30px; display: flex; justify-content: center; gap: 15px;">
                            <a href="https://www.instagram.com/mvmusicaeventi/?hl=it" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; border-radius: 25px; background: rgba(255,255,255,0.3); color: #fff; text-decoration: none; font-weight: 600; font-size: 0.95em; border: 2px solid rgba(255,255,255,0.5);">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="#fff"><path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5A4.25 4.25 0 0 0 20.5 16.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7zm5.25-2.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                                Instagram
                            </a>
                        </div>
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
                    // Se la room è chiusa (partita finita), chiedi is_winner via get_game_state
                    if (data.status === 'closed') {
                        gameEnded = true;
                        clearInterval(checkGameStateInterval);
                        clearInterval(checkRoomStatusInterval);
                        // Usa get_game_state che ritorna is_winner direttamente
                        fetch('../src/api/api.php?endpoint=game&action=get_game_state&counter=' + currentRoundCounter)
                            .then(r => r.json())
                            .then(d => {
                                if (d.is_winner !== undefined) {
                                    showFinalResult(d.is_winner);
                                } else {
                                    checkWinnerStatus();
                                }
                            })
                            .catch(() => checkWinnerStatus());
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
                        console.log("Game finished detected, is_winner:", data.is_winner);
                        gameEnded = true;
                        clearInterval(checkGameStateInterval);
                        clearInterval(checkRoomStatusInterval);
                        if (data.is_winner !== undefined) {
                            showFinalResult(data.is_winner);
                        } else {
                            checkWinnerStatus();
                        }
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

            // Category colored header bar
            const catColor = round.category_color || '#74b9ff';
            const catName = round.category_name || '';
            const catHeader = document.getElementById('category-header');
            if (catHeader) {
                catHeader.style.display = 'flex';
                catHeader.style.background = `linear-gradient(135deg, ${catColor} 0%, ${catColor}dd 100%)`;
                document.getElementById('header-round').textContent = round.round_number;
                document.getElementById('header-category').textContent = catName;
            }

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
            answerGrid.style.display = 'flex';
            document.getElementById('click-first-screen').style.display = 'none';

            const tfLabels = { 1: 'Vero', 2: 'Falso' };

            for (let i = 1; i <= 4; i++) {
                const btn = document.getElementById('btn-' + i);
                if (i <= numOptions) {
                    btn.style.display = 'flex';
                    btn.textContent = round.round_type === 'truefalse'
                        ? tfLabels[i]
                        : (round['option' + i] || 'Opzione ' + i);
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

                document.getElementById('result-content').innerHTML = '<h2>✅ Risposta registrata!</h2><p style="margin-top: 15px;">In attesa del prossimo round...</p>';
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

            const headerTimer = document.getElementById('header-timer');
            if (headerTimer) headerTimer.textContent = timeLeft;

            if (timerInterval) clearInterval(timerInterval);

            timerInterval = setInterval(() => {
                timeLeft--;
                if (headerTimer) {
                    headerTimer.textContent = timeLeft;
                    if (timeLeft <= 5) headerTimer.style.color = '#dc143c';
                    else if (timeLeft <= 10) headerTimer.style.color = '#ffc107';
                    else headerTimer.style.color = '#2d3436';
                }

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    if (headerTimer) headerTimer.style.color = '#2d3436';
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

            // Hide category header bar when waiting
            const catHeader = document.getElementById('category-header');
            if (catHeader) catHeader.style.display = 'none';
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

            // Hide category header
            const catHeader = document.getElementById('category-header');
            if (catHeader) catHeader.style.display = 'none';

            const finalScreen = document.getElementById('final-result-screen');
            const emoji = document.getElementById('final-emoji');
            const title = document.getElementById('final-title');
            const message = document.getElementById('final-message');

            if (isWinner) {
                finalScreen.classList.remove('loser');
                finalScreen.classList.add('winner');
                emoji.textContent = '🏆';
                title.textContent = 'Hai Vinto!';
                message.textContent = 'Complimenti! Sei il vincitore di questa partita! 🎉';
            } else {
                finalScreen.classList.remove('winner');
                finalScreen.classList.add('loser');
                emoji.textContent = '😢';
                title.textContent = 'Hai Perso!';
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

            // Hide category header
            const catHeader = document.getElementById('category-header');
            if (catHeader) catHeader.style.display = 'none';
        }
    </script>
</body>
</html>
