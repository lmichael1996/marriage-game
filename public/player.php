<?php
require_once __DIR__ . '/../src/config/auth.php';
requireLogin();

// Ensure player is not admin
if (isAdmin()) {
    header('Location: admin.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player - Marriage Game</title>
    <link rel="stylesheet" href="css/style.css">
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
                    <button class="answer-btn" data-answer="1" onclick="submitAnswer(1)" id="btn-1">
                        <span class="answer-number">1</span>
                        <span class="answer-label" id="option1-text">Opzione 1</span>
                    </button>
                    <button class="answer-btn" data-answer="2" onclick="submitAnswer(2)" id="btn-2">
                        <span class="answer-number">2</span>
                        <span class="answer-label" id="option2-text">Opzione 2</span>
                    </button>
                    <button class="answer-btn" data-answer="3" onclick="submitAnswer(3)" id="btn-3">
                        <span class="answer-number">3</span>
                        <span class="answer-label" id="option3-text">Opzione 3</span>
                    </button>
                    <button class="answer-btn" data-answer="4" onclick="submitAnswer(4)" id="btn-4">
                        <span class="answer-number">4</span>
                        <span class="answer-label" id="option4-text">Opzione 4</span>
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
        let currentRoundId = null;
        let timerInterval = null;
        let startTime = null;
        let hasAnswered = false;
        
        // Check for active round every 2 seconds
        setInterval(checkGameState, 2000);
        checkGameState(); // Initial check
        
        // Check if room is still open every 2 seconds
        setInterval(checkRoomStatus, 2000);
        
        function checkRoomStatus() {
            fetch('../src/api/check_room_status.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.room_open) {
                        // Room closed, redirect to login
                        alert(data.message || 'La stanza è stata chiusa');
                        window.location.href = 'logout.php';
                    }
                })
                .catch(error => console.error('Error checking room status:', error));
        }
        
        function checkGameState() {
            fetch('../src/api/game.php?action=get_game_state')
                .then(response => response.json())
                .then(data => {
                    if (data.active_round && !hasAnswered) {
                        if (currentRoundId !== data.active_round.id) {
                            startRound(data.active_round);
                        }
                    } else if (!data.active_round) {
                        showWaitingScreen();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function startRound(round) {
            currentRoundId = round.id;
            hasAnswered = false;
            startTime = Date.now();
            
            document.getElementById('round-number').textContent = round.round_number;
            document.getElementById('question-text').textContent = round.question || '';
            
            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'block';
            
            const answerGrid = document.getElementById('answer-grid');
            const clickFirstScreen = document.getElementById('click-first-screen');
            
            if (round.round_type === 'clickfirst') {
                // Clicca per primo
                answerGrid.style.display = 'none';
                clickFirstScreen.style.display = 'block';
                document.getElementById('click-first-btn').disabled = false;
                startTimer();
            } else if (round.round_type === 'truefalse') {
                // Vero o Falso - mostra solo 2 pulsanti
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
                
                startTimer();
            } else {
                // Scelta multipla - mostra tutti e 4 i pulsanti
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
                
                startTimer();
            }
        }
        
        function submitClickFirst() {
            if (hasAnswered) return;
                btn.classList.remove('selected');
            });
            
            // Start 10 second timer
            startTimer();
        }
        
        function submitClickFirst() {
            if (hasAnswered) return;
            
            hasAnswered = true;
            clearInterval(timerInterval);
            document.getElementById('click-first-btn').disabled = true;
            
            const timeTaken = (Date.now() - startTime) / 1000;
            
            fetch('../src/api/answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    round_id: currentRoundId,
                    answer: 1, // For clickfirst, answer doesn't matter
                    time_taken: timeTaken
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('game-screen').style.display = 'none';
                document.getElementById('result-screen').style.display = 'block';
                
                const resultContent = document.getElementById('result-content');
                resultContent.innerHTML = `
                    <h2>⚡ Clic registrato!</h2>
                    <p>Tempo di reazione: ${timeTaken.toFixed(3)} secondi</p>
                    <p>Attendi la fine del round per vedere chi è stato più veloce!</p>
                `;
            })
            .catch(error => console.error('Error:', error));
        }
        
        function startTimer() {
            let timeLeft = 10;
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
        
        function submitAnswer(answer) {
            if (hasAnswered) return;
            
            hasAnswered = true;
            const timeTaken = ((Date.now() - startTime) / 1000).toFixed(2);
            
            // Disable all buttons
            document.querySelectorAll('.answer-btn').forEach(btn => {
                btn.disabled = true;
            });
            
            // Highlight selected button
            document.querySelector(`button[data-answer="${answer}"]`).classList.add('selected');
            
            // Stop timer
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            
            // Send answer to server
            fetch('../src/api/answer.php?action=submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    round_id: currentRoundId,
                    answer: answer,
                    time_taken: timeTaken
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult(answer, timeTaken);
            })
            .catch(error => console.error('Error:', error));
        }
        
        function timeExpired() {
            hasAnswered = true;
            document.querySelectorAll('.answer-btn').forEach(btn => {
                btn.disabled = true;
            });
            
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'block';
            document.getElementById('result-content').innerHTML = `
                <h2>Tempo scaduto!</h2>
                <p>Non hai risposto in tempo</p>
            `;
            
            setTimeout(() => {
                currentRoundId = null;
                hasAnswered = false;
                checkGameState();
            }, 3000);
        }
        
        function showResult(answer, timeTaken) {
            document.getElementById('user-answer').textContent = answer;
            document.getElementById('time-taken').textContent = timeTaken;
            
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'block';
            
            setTimeout(() => {
                currentRoundId = null;
                hasAnswered = false;
                checkGameState();
            }, 3000);
        }
        
        function showWaitingScreen() {
            currentRoundId = null;
            hasAnswered = false;
            
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
