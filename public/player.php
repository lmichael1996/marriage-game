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
    <link rel="stylesheet" href="../assets/css/player.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Marriage Game</h1>
            <div class="user-info">
                <span>Giocatore: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php?logout=1" class="btn btn-secondary">Logout</a>
            </div>
        </div>

        <div class="game-area">
            <div id="waiting-screen" class="waiting-screen">
                <div class="spinner"></div>
                <h2>In attesa del prossimo round...</h2>
                <p>L'admin avvierà presto un nuovo round</p>
            </div>

            <div id="game-screen" class="game-screen" style="display: none;">
                <div class="round-info">
                    <h2>Round <span id="round-number">-</span></h2>
                    <p id="question-text" style="font-size: 1.3em; color: #333; margin: 15px 0; font-weight: 600;"></p>
                </div>

                <div class="timer-container">
                    <div class="timer">
                        <span id="timer-value">10</span>
                    </div>
                    <p>secondi rimanenti</p>
                </div>

                <div class="answer-grid" id="answer-grid">
                    <button class="answer-btn" data-answer="1" onclick="selectAnswer(1)" id="btn-1">
                        <span class="answer-number">1</span>
                        <span class="answer-label" id="option1-text">Opzione 1</span>
                    </button>
                    <button class="answer-btn" data-answer="2" onclick="selectAnswer(2)" id="btn-2">
                        <span class="answer-number">2</span>
                        <span class="answer-label" id="option2-text">Opzione 2</span>
                    </button>
                    <button class="answer-btn" data-answer="3" onclick="selectAnswer(3)" id="btn-3">
                        <span class="answer-number">3</span>
                        <span class="answer-label" id="option3-text">Opzione 3</span>
                    </button>
                    <button class="answer-btn" data-answer="4" onclick="selectAnswer(4)" id="btn-4">
                        <span class="answer-number">4</span>
                        <span class="answer-label" id="option4-text">Opzione 4</span>
                    </button>
                </div>

                <div class="submit-container" id="submit-container" style="display: none; margin-top: 30px; text-align: center;">
                    <button class="btn-submit" onclick="submitSelectedAnswer()" id="submit-btn">
                        ✓ INVIA RISPOSTA
                    </button>
                </div>

                <div id="click-first-screen" style="display: none; text-align: center;">
                    <button class="btn btn-primary" onclick="submitClickFirst()" id="click-first-btn" style="font-size: 2em; padding: 50px 80px; border-radius: 20px; cursor: pointer;">
                        ⚡ CLICCA!
                    </button>
                </div>
            </div>

            <div id="result-screen" class="result-screen" style="display: none;">
                <div id="result-content">
                    <h2>Risposta inviata!</h2>
                    <p>Hai scelto l'opzione <span id="user-answer"></span></p>
                    <p class="time-info">Tempo impiegato: <span id="time-taken"></span> secondi</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentRoundCounter = 1;  // Inizializza a 1
        let timerInterval = null;
        let startTime = null;
        let hasAnswered = false;
        let selectedAnswer = null;
        let roundInProgress = false;  // Previene ri-esecuzione dello stesso round

        // Controlla lo stato della stanza ogni 2 secondi
        setInterval(checkRoomStatus, 2000);

        // Controlla il nuovo round ogni 2 secondi
        setInterval(checkGameState, 2000);
        checkGameState(); // Check iniziale

        function checkRoomStatus() {
            fetch('../src/api/api.php?endpoint=check_room_status')
                .then(response => response.json())
                .then(data => {
                    if (!data.room_open) {
                        alert(data.message || 'La stanza è stata chiusa');
                        window.location.href = 'logout.php';
                    }
                })
                .catch(error => console.error('Room status error:', error));
        }


        function checkGameState() {
            fetch('../src/api/api.php?endpoint=game&action=get_game_state')
                .then(response => response.json())
                .then(data => {
                    console.log('Game state received:', data, 'currentRoundCounter:', currentRoundCounter, 'roundInProgress:', roundInProgress);

                    if (data.success === false || !data.round_number) {
                        // Nessun round attivo, rimani in attesa
                        showWaitingScreen();
                        return;
                    }

                    const roundNumber = data.round_number;

                    // Se il round è nuovo (numero più alto del counter)
                    if (roundNumber > currentRoundCounter && !hasAnswered && !roundInProgress) {
                        // Nuovo round disponibile
                        currentRoundCounter = roundNumber;
                        startRound(data);
                    }
                    // Se è lo stesso round e non abbiamo ancora risposto
                    else if (roundNumber === currentRoundCounter && !hasAnswered && !roundInProgress) {
                        // Stesso round, inizia se non è già in corso
                        startRound(data);
                    }
                    // Se abbiamo già risposto, rimani in attesa
                    else if (hasAnswered) {
                        showWaitingScreen();
                    }
                })
                .catch(error => console.error('Game state error:', error));
        }

        function startRound(round) {
            console.log('Starting round:', round);
            console.log('Timer from server:', round.timer);

            roundInProgress = true;

            clearInterval(timerInterval);

            hasAnswered = false;
            selectedAnswer = null;
            startTime = Date.now();

            document.getElementById('round-number').textContent = round.round_number;
            document.getElementById('question-text').textContent = round.question || '';

            // Nascondi tutte le schermate tranne quella di gioco
            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'block';
            document.getElementById('submit-container').style.display = 'none';

            const answerGrid = document.getElementById('answer-grid');
            const clickFirstScreen = document.getElementById('click-first-screen');

            if (round.round_type === 'clickfirst') {
                answerGrid.style.display = 'none';
                clickFirstScreen.style.display = 'block';
                document.getElementById('click-first-btn').disabled = false;
            } else if (round.round_type === 'truefalse') {
                answerGrid.style.display = 'grid';
                clickFirstScreen.style.display = 'none';

                document.getElementById('option1-text').textContent = round.option1 || 'Vero';
                document.getElementById('option2-text').textContent = round.option2 || 'Falso';

                document.getElementById('btn-1').style.display = 'flex';
                document.getElementById('btn-2').style.display = 'flex';
                document.getElementById('btn-3').style.display = 'none';
                document.getElementById('btn-4').style.display = 'none';

                document.querySelectorAll('.answer-btn').forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('selected');
                });
            } else {
                // Scelta multipla
                answerGrid.style.display = 'grid';
                clickFirstScreen.style.display = 'none';

                document.getElementById('option1-text').textContent = round.option1 || 'Opzione 1';
                document.getElementById('option2-text').textContent = round.option2 || 'Opzione 2';
                document.getElementById('option3-text').textContent = round.option3 || 'Opzione 3';
                document.getElementById('option4-text').textContent = round.option4 || 'Opzione 4';

                document.getElementById('btn-1').style.display = 'flex';
                document.getElementById('btn-2').style.display = 'flex';
                document.getElementById('btn-3').style.display = 'flex';
                document.getElementById('btn-4').style.display = 'flex';

                document.querySelectorAll('.answer-btn').forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('selected');
                });
            }

            startTimer(round.timer || 10);
        }

        function selectAnswer(answer) {
            if (hasAnswered) return;

            selectedAnswer = answer;

            // Remove selection from all buttons
            document.querySelectorAll('.answer-btn').forEach(btn => {
                btn.classList.remove('selected');
            });

            // Highlight selected button
            document.querySelector(`button[data-answer="${answer}"]`).classList.add('selected');

            // Show submit button
            document.getElementById('submit-container').style.display = 'block';
        }

        function submitSelectedAnswer() {
            if (hasAnswered || !selectedAnswer) return;

            hasAnswered = true;
            const timeTaken = ((Date.now() - startTime) / 1000).toFixed(2);
            clearInterval(timerInterval);

            fetch('../src/api/api.php?endpoint=answer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    round_number: currentRoundCounter,
                    answer: selectedAnswer,
                    time_taken: timeTaken
                })
            })
            .then(response => response.json())
            .then(data => {
                // Incrementa il counter per il prossimo round
                currentRoundCounter++;
                console.log('Answer submitted, incremented counter to:', currentRoundCounter);

                document.getElementById('game-screen').style.display = 'none';
                document.getElementById('result-screen').style.display = 'block';
                document.getElementById('result-content').innerHTML = `
                    <h2>Risposta inviata!</h2>
                    <p>Risposta: <span>${selectedAnswer}</span></p>
                    <p>Tempo: <span>${timeTaken}</span> sec</p>
                    <p>In attesa...</p>
                `;

                setTimeout(() => {
                    document.getElementById('result-screen').style.display = 'none';
                    showWaitingScreen();
                }, 2000);
            })
            .catch(error => console.error('Submit error:', error));
        }

        function submitClickFirst() {
            if (hasAnswered) return;

            hasAnswered = true;
            clearInterval(timerInterval);
            document.getElementById('click-first-btn').disabled = true;

            const timeTaken = (Date.now() - startTime) / 1000;

            fetch('../src/api/api.php?endpoint=answer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    round_number: currentRoundCounter,
                    answer: 1,
                    time_taken: timeTaken
                })
            })
            .then(response => response.json())
            .then(data => {
                // Incrementa il counter per il prossimo round
                currentRoundCounter++;
                console.log('Click first registered, incremented counter to:', currentRoundCounter);

                document.getElementById('game-screen').style.display = 'none';
                document.getElementById('result-screen').style.display = 'block';
                document.getElementById('result-content').innerHTML = `
                    <h2>⚡ Clic registrato!</h2>
                    <p>Tempo: ${timeTaken.toFixed(3)} sec</p>
                    <p>In attesa...</p>
                `;

                setTimeout(() => {
                    document.getElementById('result-screen').style.display = 'none';
                    showWaitingScreen();
                }, 2000);
            })
            .catch(error => console.error('Click first error:', error));
        }

        function startTimer(initialTime = 10) {
            console.log('startTimer called with initialTime:', initialTime);
            let timeLeft = parseInt(initialTime);

            if (isNaN(timeLeft) || timeLeft <= 0) {
                timeLeft = 10;
            }

            document.getElementById('timer-value').textContent = timeLeft;

            if (timerInterval) {
                clearInterval(timerInterval);
            }

            timerInterval = setInterval(() => {
                timeLeft--;
                document.getElementById('timer-value').textContent = timeLeft;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    if (!hasAnswered) {
                        timeExpired();
                    }
                }
            }, 1000);
        }

        function timeExpired() {
            if (hasAnswered) return;

            hasAnswered = true;

            // Incrementa il counter per il prossimo round
            currentRoundCounter++;
            console.log('Time expired, incremented counter to:', currentRoundCounter);

            document.getElementById('submit-container').style.display = 'none';

            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'block';
            document.getElementById('result-content').innerHTML = `
                <h2>Tempo scaduto!</h2>
                <p>Non hai risposto in tempo</p>
                <p>In attesa...</p>
            `;

            setTimeout(() => {
                document.getElementById('result-screen').style.display = 'none';
                showWaitingScreen();
            }, 2000);
        }

        function showWaitingScreen() {
            hasAnswered = false;
            roundInProgress = false;  // Reset quando torniamo in attesa

            if (timerInterval) {
                clearInterval(timerInterval);
            }

            document.getElementById('waiting-screen').style.display = 'block';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'none';
        }
    </script>
</body>
</html>
