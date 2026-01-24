<?php
require_once __DIR__ . '/../src/utils/AuthHelper.php';

// Check if player is logged in
AuthHelper::requirePlayer();
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
        let currentRoundId = null;
        let timerInterval = null;
        let startTime = null;
        let hasAnswered = false;
        let selectedAnswer = null;
        let answeredRounds = new Set(); // Keep track of rounds already answered
        
        // Check for active round every 2 seconds
        setInterval(checkGameState, 2000);
        checkGameState(); // Initial check
        
        // Check if room is still open every 2 seconds
        setInterval(checkRoomStatus, 2000);
        
        function checkRoomStatus() {
            fetch('../src/api/api.php?endpoint=check_room_status')
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
            fetch('../src/api/api.php?endpoint=game&action=get_game_state')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Game state received:', data);
                    console.log('Has active_round?', !!data.active_round);
                    console.log('Has answered?', hasAnswered);
                    console.log('Current round ID:', currentRoundId);
                    
                    if (data.success === false) {
                        console.error('API Error:', data.error);
                        return;
                    }
                    
                    if (data.active_round) {
                        console.log('Active round found:', data.active_round.id);
                        // Only start the round if we haven't answered it yet
                        if (!answeredRounds.has(data.active_round.id) && currentRoundId !== data.active_round.id) {
                            console.log('Starting new round');
                            startRound(data.active_round);
                        } else if (answeredRounds.has(data.active_round.id)) {
                            console.log('Already answered this round, waiting for next');
                            // Make sure we're showing waiting screen
                            if (document.getElementById('game-screen').style.display !== 'none') {
                                showWaitingScreen();
                            }
                        } else {
                            console.log('Already showing this round');
                        }
                    } else if (!data.active_round) {
                        console.log('No active round, showing waiting screen');
                        showWaitingScreen();
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
        }
        
        function startRound(round) {
            console.log('Starting player round:', round);
            console.log('About to change screens...');
            
            // Clear any existing timer from previous round
            clearInterval(timerInterval);
            
            currentRoundId = round.id;
            hasAnswered = false;
            selectedAnswer = null;
            startTime = Date.now();
            
            document.getElementById('round-number').textContent = round.round_number;
            document.getElementById('question-text').textContent = round.question || '';
            
            console.log('Hiding waiting screen...');
            document.getElementById('waiting-screen').style.display = 'none';
            console.log('Hiding result screen...');
            document.getElementById('result-screen').style.display = 'none';
            console.log('Showing game screen...');
            document.getElementById('game-screen').style.display = 'block';
            console.log('Hiding submit container...');
            document.getElementById('submit-container').style.display = 'none';
            console.log('Screens changed successfully!');
            console.log('Waiting screen display:', document.getElementById('waiting-screen').style.display);
            console.log('Game screen display:', document.getElementById('game-screen').style.display);
            console.log('Result screen display:', document.getElementById('result-screen').style.display);
            
            const answerGrid = document.getElementById('answer-grid');
            const clickFirstScreen = document.getElementById('click-first-screen');
            
            if (round.round_type === 'clickfirst') {
                // Clicca per primo
                answerGrid.style.display = 'none';
                clickFirstScreen.style.display = 'block';
                document.getElementById('click-first-btn').disabled = false;
                startTimer(round.timer || 10);
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
                
                startTimer(round.timer || 10);
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
                
                startTimer(round.timer || 10);
            }
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
            
            // Stop timer
            clearInterval(timerInterval);
            
            // Send answer to server
            fetch('../src/api/api.php?endpoint=answer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    round_id: currentRoundId,
                    answer: selectedAnswer,
                    time_taken: timeTaken
                })
            })
            .then(response => response.json())
            .then(data => {
                // Mark this round as answered
                answeredRounds.add(currentRoundId);
                
                // Show result and then go to waiting screen
                document.getElementById('game-screen').style.display = 'none';
                document.getElementById('result-screen').style.display = 'block';
                
                // Update result content with success message
                document.getElementById('result-content').innerHTML = `
                    <h2>Risposta inviata!</h2>
                    <p>Risposta: <span id="user-answer">${selectedAnswer}</span></p>
                    <p>Tempo impiegato: <span id="time-taken">${timeTaken}</span> secondi</p>
                    <p>In attesa della prossima domanda...</p>
                `;
                
                // After 2 seconds, go to waiting screen for next question
                setTimeout(() => {
                    document.getElementById('result-screen').style.display = 'none';
                    showWaitingScreen();
                }, 2000);
            })
            .catch(error => console.error('Error:', error));
        }
        
        function submitClickFirst() {
            if (hasAnswered) return;
            
            hasAnswered = true;
            clearInterval(timerInterval);
            document.getElementById('click-first-btn').disabled = true;
            
            const timeTaken = (Date.now() - startTime) / 1000;
            
            fetch('../src/api/api.php?endpoint=answer', {
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
                // Mark this round as answered
                answeredRounds.add(currentRoundId);
                
                document.getElementById('game-screen').style.display = 'none';
                document.getElementById('result-screen').style.display = 'block';
                
                const resultContent = document.getElementById('result-content');
                resultContent.innerHTML = `
                    <h2>⚡ Clic registrato!</h2>
                    <p>Tempo di reazione: ${timeTaken.toFixed(3)} secondi</p>
                    <p>In attesa della prossima domanda...</p>
                `;
                
                // Go to waiting screen after 2 seconds
                setTimeout(() => {
                    document.getElementById('result-screen').style.display = 'none';
                    showWaitingScreen();
                }, 2000);
            })
            .catch(error => console.error('Error:', error));
        }
        
        function startTimer(initialTime = 10) {
            let timeLeft = initialTime;
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
            
            // Mark this round as answered (even though time expired)
            answeredRounds.add(currentRoundId);
            
            document.getElementById('submit-container').style.display = 'none';
            
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('result-screen').style.display = 'block';
            document.getElementById('result-content').innerHTML = `
                <h2>Tempo scaduto!</h2>
                <p>Non hai risposto in tempo</p>
                <p>In attesa della prossima domanda...</p>
            `;
            
            setTimeout(() => {
                document.getElementById('result-screen').style.display = 'none';
                showWaitingScreen();
            }, 2000);
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
