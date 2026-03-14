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
    <link rel="stylesheet" href="../assets/css/main.css?v=3">
</head>
<body class="player-page">
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

                    <div id="click-first-screen" style="display: none;">
                        <img src="../assets/image/button.png" onclick="submitClickFirst()" id="click-first-btn"
                             ontouchstart="this.classList.add('pressed')"
                             ontouchend="this.classList.remove('pressed')"
                             onmousedown="this.classList.add('pressed')"
                             onmouseup="this.classList.remove('pressed')"
                             alt="Clicca!">
                    </div>
                </div>

                <div id="final-result-screen" class="final-result-screen">
                    <div id="final-result-content">
                        <div class="final-result-emoji" id="final-emoji">🏆</div>
                        <h1 id="final-title">Hai Vinto!</h1>
                        <p id="final-message">Complimenti! Sei il vincitore di questa partita!</p>

                        <div class="social-links">
                            <a href="https://www.instagram.com/mvmusicaeventi/?hl=it" target="_blank">
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
        // ── DOM refs ──────────────────────────────────────────────────
        const screens = {
            waiting:   document.getElementById('waiting-screen'),
            game:      document.getElementById('game-screen'),
            result:    document.getElementById('final-result-screen'),
            cancelled: document.getElementById('game-cancelled-screen'),
            violation: document.getElementById('violation-screen'),
        };
        const catHeader   = document.getElementById('category-header');
        const headerTimer = document.getElementById('header-timer');
        const logoutBtn   = document.getElementById('logout-btn');
        const answerGrid  = document.getElementById('answer-grid');
        const clickFirst  = document.getElementById('click-first-screen');
        const clickBtn    = document.getElementById('click-first-btn');

        // ── State ──────────────────────────────────────────────────────
        let currentRoundCounter = 1;
        let currentRoundId = null;
        let timerInterval = null;
        let startTime = null;
        let hasAnswered = false;
        let roundInProgress = false;
        let gameEnded = false;
        let violated = false;
        let checkGameStateInterval = null;
        let checkRoomStatusInterval = null;

        const isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
                      || (navigator.maxTouchPoints > 1 && window.innerWidth < 1024);

        // ── Helpers ────────────────────────────────────────────────────
        function api(qs) {
            return fetch('../src/api/api.php?' + qs).then(r => r.json());
        }

        /** Show one screen, hide all others + category header */
        function showScreen(name) {
            Object.entries(screens).forEach(([k, el]) => el.style.display = k === name ? 'block' : 'none');
            catHeader.style.display = 'none';
        }

        /** Mark game as ended and stop all polling */
        function endGame() {
            gameEnded = true;
            clearInterval(checkGameStateInterval);
            clearInterval(checkRoomStatusInterval);
            stopTimer();
            stopWatchdog();
            unlockNavigation();
        }

        function stopTimer() {
            if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        }

        function lockNavigation() {
            logoutBtn.classList.add('disabled-link');
            logoutBtn.style.opacity = '0.5';
            logoutBtn.style.pointerEvents = 'none';
        }

        function unlockNavigation() {
            logoutBtn.classList.remove('disabled-link');
            logoutBtn.style.opacity = '1';
            logoutBtn.style.pointerEvents = 'auto';
        }

        // ── Anti-cheat: detect page leave on mobile ───────────────────
        function onPageLeave() {
            if (roundInProgress && !violated) {
                violated = true;
                endGame();
                document.querySelector('.player-container').style.display = 'none';
                showScreen('violation');
            }
        }

        function onVisibilityChange() { if (document.hidden) onPageLeave(); }

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

        // ── Init ───────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            api('endpoint=player_progress')
                .then(data => { if (data.success && data.answered > 0) currentRoundCounter = data.answered + 1; })
                .catch(() => {})
                .finally(() => {
                    checkRoomStatusInterval = setInterval(checkRoomStatus, 1000);
                    checkGameStateInterval  = setInterval(checkGameState, 1000);
                    checkGameState();
                });
        });

        // ── Polling ────────────────────────────────────────────────────
        function checkRoomStatus() {
            if (gameEnded) return;
            api('endpoint=check_room_status').then(data => {
                if (gameEnded) return;
                if (data.status === 'closed') {
                    endGame();
                    api('endpoint=game&action=get_game_state&counter=' + currentRoundCounter)
                        .then(d => d.placement !== undefined ? showFinalResult(d.placement) : checkWinnerStatus())
                        .catch(() => checkWinnerStatus());
                } else if (data.status === 'cancelled') {
                    endGame();
                    showScreen('cancelled');
                }
            }).catch(() => {});
        }

        function checkGameState() {
            if (gameEnded) return;
            api('endpoint=game&action=get_game_state&counter=' + currentRoundCounter).then(data => {
                if (gameEnded) return;

                if (data.game_finished || data.status_room === 'closed') {
                    endGame();
                    data.placement !== undefined ? showFinalResult(data.placement) : checkWinnerStatus();
                    return;
                }
                if (data.status_room === 'cancelled') {
                    endGame();
                    showScreen('cancelled');
                    return;
                }
                if (!data.success && !data.round_number) { showScreen('waiting'); return; }
                if (data.round_number === currentRoundCounter && !hasAnswered && !roundInProgress) {
                    startRound(data);
                } else if (hasAnswered) {
                    showScreen('waiting');
                }
            });
        }

        // ── Round lifecycle ────────────────────────────────────────────
        function startRound(round) {
            roundInProgress = true;
            hasAnswered = false;
            startTime = Date.now();
            currentRoundId = round.id;
            stopTimer();
            startWatchdog();
            lockNavigation();

            // Category header
            const color = round.category_color || '#74b9ff';
            document.querySelector('.player-main').style.borderColor = color;
            catHeader.style.display = 'flex';
            catHeader.style.background = color;
            document.getElementById('header-round').textContent = round.round_number;
            document.getElementById('header-category').textContent = round.category_name || '';

            document.getElementById('question-text').textContent = round.question || '';
            showScreen('game');
            catHeader.style.display = 'flex'; // re-show after showScreen hid it

            if (round.question_type === 'clickfirst') {
                answerGrid.style.display = 'none';
                clickFirst.style.display = 'block';
                clickBtn.style.pointerEvents = 'auto';
            } else {
                setupRound(round, round.question_type === 'truefalse' ? 2 : 4);
            }

            startTimer(round.timer || 10);
        }

        function setupRound(round, numOptions) {
            answerGrid.style.display = 'flex';
            clickFirst.style.display = 'none';

            const tfLabels = { 1: 'Vero', 2: 'Falso' };
            for (let i = 1; i <= 4; i++) {
                const btn = document.getElementById('btn-' + i);
                btn.style.display = i <= numOptions ? 'flex' : 'none';
                btn.textContent = i <= numOptions
                    ? (round.question_type === 'truefalse' ? tfLabels[i] : (round['option' + i] || 'Opzione ' + i))
                    : '';
                btn.classList.remove('selected');
            }
        }

        // ── Answers ────────────────────────────────────────────────────
        function selectAnswer(answer) {
            if (hasAnswered) return;
            document.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
            document.querySelector(`[data-answer="${answer}"]`).classList.add('selected');
            hasAnswered = true;
            stopTimer();
            submitAnswer(answer, (Date.now() - startTime) / 1000);
        }

        function submitClickFirst() {
            if (hasAnswered) return;
            hasAnswered = true;
            stopTimer();
            clickBtn.style.pointerEvents = 'none';
            submitAnswer(1, (Date.now() - startTime) / 1000);
        }

        function submitAnswer(answer, timeTaken) {
            fetch('../src/api/api.php?endpoint=answer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ round_id: currentRoundId, answer, time_taken: timeTaken })
            }).then(r => r.json()).then(() => {
                currentRoundCounter++;
                resetRound();
                showScreen('waiting');
            });
        }

        function timeExpired() {
            if (hasAnswered) return;
            hasAnswered = true;
            currentRoundCounter++;
            resetRound();
            showScreen('waiting');
        }

        /** Common cleanup after a round ends (answer, timeout, etc.) */
        function resetRound() {
            hasAnswered = false;
            roundInProgress = false;
            stopTimer();
            stopWatchdog();
            unlockNavigation();
        }

        // ── Timer ──────────────────────────────────────────────────────
        function startTimer(initialTime = 10) {
            let timeLeft = parseInt(initialTime) || 10;
            headerTimer.textContent = timeLeft;
            headerTimer.style.color = '#2d3436';
            stopTimer();

            timerInterval = setInterval(() => {
                timeLeft--;
                headerTimer.textContent = timeLeft;
                headerTimer.style.color = timeLeft <= 5 ? '#dc143c' : timeLeft <= 10 ? '#ffc107' : '#2d3436';

                if (timeLeft <= 0) {
                    stopTimer();
                    headerTimer.style.color = '#2d3436';
                    if (!hasAnswered) timeExpired();
                }
            }, 1000);
        }

        // ── End-game screens ───────────────────────────────────────────
        function checkWinnerStatus() {
            let attempts = 0;
            (function tryCheck() {
                api('endpoint=game&action=check_winner').then(data => {
                    if (data.success) return showFinalResult(data.placement);
                    if (++attempts < 20) setTimeout(tryCheck, 500);
                    else showScreen('waiting');
                }).catch(() => {
                    if (++attempts < 20) setTimeout(tryCheck, 500);
                    else showScreen('waiting');
                });
            })();
        }

        function showFinalResult(placement) {
            showScreen('result');

            const finalScreen = screens.result;
            const podium = {
                1: { emoji: '🥇', title: '1° Posto!', msg: 'Complimenti! Sei il vincitore di questa partita! 🎉', cls: 'winner' },
                2: { emoji: '🥈', title: '2° Posto!', msg: 'Ottimo risultato! Sei arrivato secondo! 👏',        cls: 'winner' },
                3: { emoji: '🥉', title: '3° Posto!', msg: 'Bel lavoro! Sei sul podio! 💪',                     cls: 'winner' },
            };
            const info = podium[placement];
            finalScreen.classList.remove('winner', 'loser');

            if (info) {
                finalScreen.classList.add(info.cls);
                document.getElementById('final-emoji').textContent   = info.emoji;
                document.getElementById('final-title').textContent   = info.title;
                document.getElementById('final-message').textContent = info.msg;
            } else {
                finalScreen.classList.add('loser');
                document.getElementById('final-emoji').textContent   = placement > 0 ? '🏁' : '😢';
                document.getElementById('final-title').textContent   = placement > 0 ? `${placement}° Posto` : 'Partita terminata';
                document.getElementById('final-message').textContent = 'Buona fortuna nella prossima partita!';
            }
        }
    </script>
</body>
</html>
