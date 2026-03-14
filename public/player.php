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
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        body {
            background: #f0f2f5;
            padding: 20px;
            display: block;
        }

        @media (max-width: 640px) {
            body { padding: 10px; }
        }

        #click-first-btn {
            transition: transform 0.15s ease;
            -webkit-tap-highlight-color: transparent;
            outline: none;
            user-select: none;
        }
        #click-first-btn.pressed {
            transform: scale(0.85);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Giocatore <?php echo htmlspecialchars($_SESSION['auth_player']['username'] ?? 'Giocatore'); ?></h1>
            <a href="logout.php?role=player" class="btn btn-secondary" id="logout-btn">Logout</a>
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
                        <img src="../assets/image/button.png" onclick="submitClickFirst()" id="click-first-btn"
                             ontouchstart="this.classList.add('pressed')"
                             ontouchend="this.classList.remove('pressed')"
                             onmousedown="this.classList.add('pressed')"
                             onmouseup="this.classList.remove('pressed')"
                             style="cursor: pointer; max-width: 80%; height: auto;" alt="Clicca!">
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

                <div id="game-cancelled-screen" class="game-cancelled-screen">
                    <div class="game-cancelled-emoji">❌</div>
                    <h1>Partita Annullata</h1>
                    <p>L'amministratore ha chiuso la partita</p>
                </div>
            </div>
        </div>

        <div id="violation-screen" class="game-cancelled-screen" style="display:none;">
            <div class="game-cancelled-emoji">👀</div>
            <h1>Ehi, dove vai?!</h1>
            <p>Niente scappatelle durante la partita!</p>
        </div>
    </div>

    <script>
        // ── Anti-cheat: detect page leave on mobile ──────────────────
        // Uses visibilitychange + blur + pagehide to catch all cases:
        // - switching tab, switching app, home button, task switcher,
        //   notification tap, control center (iOS)
        let violated = false;
        const isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
                      || (navigator.maxTouchPoints > 1 && window.innerWidth < 1024);

        function onPageLeave() {
            if (roundInProgress && !violated) {
                violated = true;
                showViolation();
            }
        }

        function startWatchdog() {
            if (!isMobile || violated) return;
            document.addEventListener('visibilitychange', onVisibilityChange);
            window.addEventListener('blur', onPageLeave);
            window.addEventListener('pagehide', onPageLeave);
        }

        function stopWatchdog() {
            document.removeEventListener('visibilitychange', onVisibilityChange);
            window.removeEventListener('blur', onPageLeave);
            window.removeEventListener('pagehide', onPageLeave);
        }

        function onVisibilityChange() {
            if (document.hidden) onPageLeave();
        }

        function showViolation() {
            gameEnded = true;
            clearInterval(checkGameStateInterval);
            clearInterval(checkRoomStatusInterval);
            stopWatchdog();
            unlockNavigation();
            if (timerInterval) clearInterval(timerInterval);

            document.querySelector('.player-container').style.display = 'none';
            document.getElementById('violation-screen').style.display = 'block';
        }

        function lockNavigation() {
            const btn = document.getElementById('logout-btn');
            btn.style.opacity = '0.5';
            btn.style.pointerEvents = 'none';
        }

        function unlockNavigation() {
            const btn = document.getElementById('logout-btn');
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
        }

        let currentRoundCounter = 1;
        let currentRoundId = null;
        let timerInterval = null;
        let startTime = null;
        let hasAnswered = false;
        let roundInProgress = false;
        let gameEnded = false;  // Flag to stop polling when game ends
        let checkGameStateInterval = null;
        let checkRoomStatusInterval = null;

        document.addEventListener('DOMContentLoaded', () => {
            // Recupera il progresso del player prima di avviare il polling
            fetch('../src/api/api.php?endpoint=player_progress')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.answered > 0) {
                        currentRoundCounter = data.answered + 1;
                    }
                })
                .catch(() => {})
                .finally(() => {
                    checkRoomStatusInterval = setInterval(checkRoomStatus, 1000);
                    checkGameStateInterval = setInterval(checkGameState, 1000);
                    checkGameState();
                });
        });

        function checkRoomStatus() {
            if (gameEnded) return;

            fetch('../src/api/api.php?endpoint=check_room_status')
                .then(response => response.json())
                .then(data => {
                    if (gameEnded) return;  // another callback may have ended the game

                    // Se la room è chiusa (partita finita), chiedi placement via get_game_state
                    if (data.status === 'closed') {
                        gameEnded = true;
                        clearInterval(checkGameStateInterval);
                        clearInterval(checkRoomStatusInterval);
                        fetch('../src/api/api.php?endpoint=game&action=get_game_state&counter=' + currentRoundCounter)
                            .then(r => r.json())
                            .then(d => {
                                if (d.placement !== undefined) {
                                    showFinalResult(d.placement);
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
                })
                .catch(() => {});
        }

        function checkGameState() {
            if (gameEnded) return;

            fetch('../src/api/api.php?endpoint=game&action=get_game_state&counter=' + currentRoundCounter)
                .then(response => response.json())
                .then(data => {
                    if (gameEnded) return;  // another callback may have ended the game

                    // Partita terminata
                    if (data.game_finished || data.status_room === 'closed') {
                        gameEnded = true;
                        clearInterval(checkGameStateInterval);
                        clearInterval(checkRoomStatusInterval);
                        if (data.placement !== undefined) {
                            showFinalResult(data.placement);
                        } else {
                            checkWinnerStatus();
                        }
                        return;
                    }

                    // Partita annullata dall'admin
                    if (data.status_room === 'cancelled') {
                        gameEnded = true;
                        clearInterval(checkGameStateInterval);
                        clearInterval(checkRoomStatusInterval);
                        showGameCancelled();
                        return;
                    }

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
                });
        }

        function startRound(round) {
            roundInProgress = true;
            clearInterval(timerInterval);
            startWatchdog();
            lockNavigation();

            hasAnswered = false;
            startTime = Date.now();
            currentRoundId = round.id;

            // Category colored border + header
            const catColor = round.category_color || '#74b9ff';
            const catName = round.category_name || '';
            const playerMain = document.querySelector('.player-main');
            if (playerMain) playerMain.style.borderColor = catColor;
            const catHeader = document.getElementById('category-header');
            if (catHeader) {
                catHeader.style.display = 'flex';
                catHeader.style.background = catColor;
                document.getElementById('header-round').textContent = round.round_number;
                document.getElementById('header-category').textContent = catName;
            }

            document.getElementById('question-text').textContent = round.question || '';

            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'block';

            const answerGrid = document.getElementById('answer-grid');
            const clickFirstScreen = document.getElementById('click-first-screen');

            if (round.question_type === 'clickfirst') {
                answerGrid.style.display = 'none';
                clickFirstScreen.style.display = 'block';
                document.getElementById('click-first-btn').style.pointerEvents = 'auto';
            } else if (round.question_type === 'truefalse') {
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
                    btn.textContent = round.question_type === 'truefalse'
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

            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.classList.remove('selected');
            });

            document.querySelector(`[data-answer="${answer}"]`).classList.add('selected');

            hasAnswered = true;
            const timeTaken = (Date.now() - startTime) / 1000;
            clearInterval(timerInterval);

            submitAnswer(answer, timeTaken);
        }

        function submitClickFirst() {
            if (hasAnswered) return;

            hasAnswered = true;
            clearInterval(timerInterval);
            document.getElementById('click-first-btn').style.pointerEvents = 'none';

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
                showWaitingScreen();
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
            showWaitingScreen();
        }

        function showWaitingScreen() {
            hasAnswered = false;
            roundInProgress = false;
            stopWatchdog();
            unlockNavigation();

            if (timerInterval) clearInterval(timerInterval);

            document.getElementById('waiting-screen').style.display = 'block';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('final-result-screen').style.display = 'none';
            document.getElementById('game-cancelled-screen').style.display = 'none';

            // Hide category header bar when waiting
            const catHeader = document.getElementById('category-header');
            if (catHeader) catHeader.style.display = 'none';
        }

        function checkWinnerStatus() {
            const maxAttempts = 20;
            let attempts = 0;

            function tryCheckWinner() {
                fetch('../src/api/api.php?endpoint=game&action=check_winner')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showFinalResult(data.placement);
                        } else {
                            attempts++;
                            if (attempts < maxAttempts) {
                                setTimeout(tryCheckWinner, 500);
                            } else {
                                // Fallback: show waiting screen if we can't determine winner
                                showWaitingScreen();
                            }
                        }
                    })
                    .catch(() => {
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

        function showFinalResult(placement) {
            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('final-result-screen').style.display = 'block';
            document.getElementById('game-cancelled-screen').style.display = 'none';

            // Hide category header
            const catHeader = document.getElementById('category-header');
            if (catHeader) catHeader.style.display = 'none';

            const finalScreen = document.getElementById('final-result-screen');
            const emoji = document.getElementById('final-emoji');
            const title = document.getElementById('final-title');
            const message = document.getElementById('final-message');

            const podium = {
                1: { emoji: '🥇', title: '1° Posto!', message: 'Complimenti! Sei il vincitore di questa partita! 🎉', cls: 'winner' },
                2: { emoji: '🥈', title: '2° Posto!', message: 'Ottimo risultato! Sei arrivato secondo! 👏', cls: 'winner' },
                3: { emoji: '🥉', title: '3° Posto!', message: 'Bel lavoro! Sei sul podio! 💪', cls: 'winner' },
            };

            const info = podium[placement];
            finalScreen.classList.remove('winner', 'loser');

            if (info) {
                finalScreen.classList.add(info.cls);
                emoji.textContent = info.emoji;
                title.textContent = info.title;
                message.textContent = info.message;
            } else {
                finalScreen.classList.add('loser');
                emoji.textContent = placement > 0 ? '🏁' : '😢';
                title.textContent = placement > 0 ? `${placement}° Posto` : 'Partita terminata';
                message.textContent = 'Buona fortuna nella prossima partita!';
            }
        }

        function showGameCancelled() {
            document.getElementById('waiting-screen').style.display = 'none';
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('final-result-screen').style.display = 'none';
            document.getElementById('game-cancelled-screen').style.display = 'block';

            // Hide category header
            const catHeader = document.getElementById('category-header');
            if (catHeader) catHeader.style.display = 'none';
        }
    </script>
</body>
</html>
