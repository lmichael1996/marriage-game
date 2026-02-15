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
    <link rel="stylesheet" href="../assets/css/player.css">
    <link rel="stylesheet" href="../assets/css/mobile.css">
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

        <div class="layout-wrapper">
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
    </div>

    <script>
        // ============ STATE ============
        let currentRoundCounter = 1;
        let currentRoundId = null;
        let timerInterval = null;
        let startTime = null;
        let hasAnswered = false;
        let selectedAnswer = null;
        let roundInProgress = false;

        // ============ INIT ============
        document.addEventListener('DOMContentLoaded', () => {
            setInterval(checkRoomStatus, 1000);
            setInterval(checkGameState, 1000);
            checkGameState();
        });

        // ============ CHECKS ============
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
            // Ask API for the round at currentRoundCounter position
            fetch('../src/api/api.php?endpoint=game&action=get_game_state&counter=' + currentRoundCounter)
                .then(response => response.json())
                .then(data => {
                    if (data.success === false || !data.round_number) {
                        showWaitingScreen();
                        return;
                    }

                    const roundNumber = data.round_number;
                    if (roundNumber === currentRoundCounter && !hasAnswered && !roundInProgress) {
                        startRound(data);
                    } else if (hasAnswered) {
                        showWaitingScreen();
                    }
                })
                .catch(error => console.error('Game state error:', error));
        }

        // ============ ROUND LOGIC ============
        function startRound(round) {
            roundInProgress = true;
            clearInterval(timerInterval);

            hasAnswered = false;
            selectedAnswer = null;
            startTime = Date.now();
            currentRoundId = round.id;  // Store the round ID

            // Update UI
            document.getElementById('round-number').textContent = round.round_number;
            document.getElementById('question-text').textContent = round.question || '';

            // Hide all screens
            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'block';
            document.getElementById('submit-container').style.display = 'none';

            const answerGrid = document.getElementById('answer-grid');
            const clickFirstScreen = document.getElementById('click-first-screen');

            // Setup buttons based on round type
            if (round.round_type === 'clickfirst') {
                answerGrid.style.display = 'none';
                clickFirstScreen.style.display = 'block';
                document.getElementById('click-first-btn').disabled = false;
            } else if (round.round_type === 'truefalse') {
                setupTrueFalseRound(round);
            } else {
                setupMultipleChoiceRound(round);
            }

            startTimer(round.timer || 10);
        }

        function setupTrueFalseRound(round) {
            const answerGrid = document.getElementById('answer-grid');
            answerGrid.style.display = 'grid';
            document.getElementById('click-first-screen').style.display = 'none';

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
        }

        function setupMultipleChoiceRound(round) {
            const answerGrid = document.getElementById('answer-grid');
            answerGrid.style.display = 'grid';
            document.getElementById('click-first-screen').style.display = 'none';

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

        function selectAnswer(answer) {
            if (hasAnswered) return;

            selectedAnswer = answer;

            document.querySelectorAll('.answer-btn').forEach(btn => {
                btn.classList.remove('selected');
            });

            document.querySelector(`button[data-answer="${answer}"]`).classList.add('selected');
            document.getElementById('submit-container').style.display = 'block';
        }

        // ============ SUBMIT ============
        function submitSelectedAnswer() {
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
            .then(data => {
                currentRoundCounter++;

                document.getElementById('game-screen').style.display = 'none';
                document.getElementById('result-screen').style.display = 'block';
                document.getElementById('result-content').innerHTML = `
                    <h2>✓ Risposta registrata!</h2>
                    <p>Tempo: ${timeTaken.toFixed(2)}s</p>
                    <p style="margin-top: 15px; font-size: 0.9em; color: #666;">In attesa del prossimo round...</p>
                `;

                setTimeout(() => {
                    document.getElementById('result-screen').style.display = 'none';
                    showWaitingScreen();
                }, 2000);
            })
            .catch(error => {
                console.error('Submit error:', error);
                hasAnswered = false;
                alert('Errore nell\'invio della risposta');
            });
        }

        // ============ TIMER ============
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

            document.getElementById('submit-container').style.display = 'none';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'block';
            document.getElementById('result-content').innerHTML = `
                <h2>⏱ Tempo scaduto!</h2>
                <p>Non hai risposto in tempo</p>
                <p style="margin-top: 15px; font-size: 0.9em; color: #666;">In attesa del prossimo round...</p>
            `;

            setTimeout(() => {
                document.getElementById('result-screen').style.display = 'none';
                showWaitingScreen();
            }, 2000);
        }

        // ============ UI ============
        function showWaitingScreen() {
            hasAnswered = false;
            roundInProgress = false;

            if (timerInterval) clearInterval(timerInterval);

            document.getElementById('waiting-screen').style.display = 'block';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'none';
        }

    </script>
</body>
</html>
