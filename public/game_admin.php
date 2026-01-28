<?php
require_once __DIR__ . '/../src/utils/AuthHelper.php';
require_once __DIR__ . '/../src/controllers/GameController.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';
require_once __DIR__ . '/../src/repository/RoomRepo.php';
require_once __DIR__ . '/../src/repository/PlayerRepo.php';
require_once __DIR__ . '/../src/config/database.php';

// Check admin access
AuthHelper::requireAdmin();

$game = new GameController();
$admin = new AdminController();
$roomRepo = new RoomRepo();
$playerRepo = new PlayerRepo();

// Get room info - Try from session first, then from GET parameter as fallback
$roomCode = $_SESSION['room_code'] ?? $_GET['room_code'] ?? null;
$questionSetId = null;
$room = null;

// Save room_code to session if it came from GET param
if ($roomCode && !isset($_SESSION['room_code'])) {
    $_SESSION['room_code'] = $roomCode;
}

if ($roomCode) {
    // Get room by code from session (or from GET param if freshly set)
    $room = $roomRepo->getRoomByCode($roomCode);
    if ($room) {
        // Check if room is in valid state (waiting or active)
        if ($room['status_room'] === 'waiting' || $room['status_room'] === 'active') {
            $questionSetId = $room['question_set_id'];
        } else {
            // Room is closed or canceled, clear from session
            unset($_SESSION['room_code']);
            $room = null;
            $roomCode = null;
        }
    } else {
        // Room not found, clear from session
        unset($_SESSION['room_code']);
        $roomCode = null;
    }
}

// If no valid room, cancel any active rooms and redirect to admin
if (!$room) {
    // Cancel all active rooms that don't match the session (orphaned rooms)
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE rooms SET status_room = 'canceled', closed_at = NOW() WHERE status_room = 'active'");
    $stmt->execute();
    $conn->close();
    
    // Redirect back to admin panel
    header('Location: admin.php');
    exit;
}

// Get questions from the set
$questions = [];
$setInfo = null;
if ($questionSetId) {
    $setResult = $admin->getQuestionSetRounds($questionSetId);
    if ($setResult['success']) {
        $questions = $setResult['rounds'];
        $setInfoResult = $admin->getQuestionSetById($questionSetId);
        if ($setInfoResult['success']) {
            $setInfo = $setInfoResult['set'];
        }
    }
}

// Get current game state for this room's question set
$gameState = $game->getGameState($questionSetId);
$activeRound = $gameState['active_round'] ?? null;

// Get connected players
$players = [];
if ($roomCode) {
    $players = $playerRepo->getPlayersByRoom($roomCode);
}

// Find next question to start
$nextQuestion = null;
foreach ($questions as $q) {
    if ($q['status_round'] === 'pending') {
        $nextQuestion = $q;
        break;
    }
}

// Get final leaderboard if game is over
$finalLeaderboard = [];
if (!$activeRound && !$nextQuestion && $roomCode) {
    $leaderboardResult = $game->getLeaderboard($roomCode);
    if ($leaderboardResult['success']) {
        $finalLeaderboard = $leaderboardResult['leaderboard'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/game_admin.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Gestione Partita</h1>
            <div class="user-info">
                <span>Stanza: <strong><?php echo htmlspecialchars($roomCode ?? 'N/A'); ?></strong></span>
                <?php if ($setInfo): ?>
                    <span>Set: <strong><?php echo htmlspecialchars($setInfo['set_name']); ?></strong></span>
                <?php endif; ?>
                <button class="btn btn-danger" id="cancel-game-btn">🚫 Chiudi Partita</button>
            </div>
        </div>

        <div class="game-layout <?php echo (!$activeRound && !$nextQuestion) ? 'game-over' : ''; ?>">
            <!-- Main Game Area -->
            <div class="main-game-area">
                <!-- Question Display -->
                <div class="question-display">
                    <?php if ($activeRound): ?>
                        <div class="question-header">
                            <h2>Round <?php echo $activeRound['round_number']; ?></h2>
                            <span class="round-badge active">
                                ▶ IN CORSO
                            </span>
                        </div>

                        <div class="timer-info">
                            ⏱ Timer: <strong id="countdown-timer"><?php echo $activeRound['timer'] ?? 10; ?></strong> secondi
                        </div>

                        <div class="question-text">
                            <?php echo htmlspecialchars($activeRound['question'] ?? 'Nessuna domanda'); ?>
                        </div>

                        <?php if ($activeRound['round_type'] != 'clickfirst'): ?>
                            <div class="options-display">
                                <div class="option-box" id="option-1">
                                    <div class="option-number">1</div>
                                    <div class="option-text"><?php echo htmlspecialchars($activeRound['option1'] ?? 'Opzione 1'); ?></div>
                                </div>
                                <div class="option-box" id="option-2">
                                    <div class="option-number">2</div>
                                    <div class="option-text"><?php echo htmlspecialchars($activeRound['option2'] ?? 'Opzione 2'); ?></div>
                                </div>
                                <?php if ($activeRound['round_type'] == 'multiple'): ?>
                                    <div class="option-box" id="option-3">
                                        <div class="option-number">3</div>
                                        <div class="option-text"><?php echo htmlspecialchars($activeRound['option3'] ?? 'Opzione 3'); ?></div>
                                    </div>
                                    <div class="option-box" id="option-4">
                                        <div class="option-number">4</div>
                                        <div class="option-text"><?php echo htmlspecialchars($activeRound['option4'] ?? 'Opzione 4'); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px 20px;">
                                <div style="font-size: 2.5em; margin-bottom: 15px;">⚡</div>
                                <div style="font-size: 1.3em; color: #666;">
                                    Clicca per Primo!
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($nextQuestion): ?>
                        <div class="question-header">
                            <h2>Prossimo Round: <?php echo $nextQuestion['round_number']; ?></h2>
                            <span class="round-badge">
                                PRONTO PER L'AVVIO
                            </span>
                        </div>

                        <div class="timer-info">
                            ⏱ Timer: <strong><?php echo $nextQuestion['timer'] ?? 10; ?> secondi</strong>
                        </div>

                        <div class="question-text">
                            <?php echo htmlspecialchars($nextQuestion['question'] ?? 'Nessuna domanda'); ?>
                        </div>

                        <?php if ($nextQuestion['round_type'] != 'clickfirst'): ?>
                            <div class="options-display">
                                <div class="option-box">
                                    <div class="option-number">1</div>
                                    <div class="option-text"><?php echo htmlspecialchars($nextQuestion['option1'] ?? 'Opzione 1'); ?></div>
                                </div>
                                <div class="option-box">
                                    <div class="option-number">2</div>
                                    <div class="option-text"><?php echo htmlspecialchars($nextQuestion['option2'] ?? 'Opzione 2'); ?></div>
                                </div>
                                <?php if ($nextQuestion['round_type'] == 'multiple'): ?>
                                    <div class="option-box">
                                        <div class="option-number">3</div>
                                        <div class="option-text"><?php echo htmlspecialchars($nextQuestion['option3'] ?? 'Opzione 3'); ?></div>
                                    </div>
                                    <div class="option-box">
                                        <div class="option-number">4</div>
                                        <div class="option-text"><?php echo htmlspecialchars($nextQuestion['option4'] ?? 'Opzione 4'); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Final Leaderboard when game is over -->
                        <div class="question-header">
                            <h2>🏆 Classifica Finale</h2>
                            <span class="round-badge">
                                PARTITA TERMINATA
                            </span>
                        </div>
                        
                        <div id="final-leaderboard-content" class="final-leaderboard">
                            <?php if (!empty($finalLeaderboard)): ?>
                                <div class="leaderboard-stats-summary">
                                    <h3>📊 Statistiche Partita</h3>
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <div class="stat-label">Giocatori Totali</div>
                                            <div class="stat-value"><?php echo count($finalLeaderboard); ?></div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Tempo Medio</div>
                                            <div class="stat-value">
                                                <?php 
                                                $totalAvgTime = 0;
                                                foreach ($finalLeaderboard as $p) {
                                                    $totalAvgTime += $p['avg_time'] ?? 0;
                                                }
                                                echo number_format($totalAvgTime / count($finalLeaderboard) / 1000, 2);
                                                ?>s
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Risposte Corrette</div>
                                            <div class="stat-value">
                                                <?php 
                                                $totalCorrect = 0;
                                                foreach ($finalLeaderboard as $p) {
                                                    $totalCorrect += $p['correct_answers'] ?? 0;
                                                }
                                                echo $totalCorrect;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="leaderboard-list">
                                    <?php foreach ($finalLeaderboard as $index => $player): ?>
                                        <?php 
                                        $position = $index + 1;
                                        $itemClass = 'leaderboard-item';
                                        if ($position === 1) $itemClass .= ' first';
                                        elseif ($position === 2) $itemClass .= ' second';
                                        elseif ($position === 3) $itemClass .= ' third';
                                        
                                        $medal = '';
                                        if ($position === 1) $medal = '🥇';
                                        elseif ($position === 2) $medal = '🥈';
                                        elseif ($position === 3) $medal = '🥉';
                                        else $medal = $position . '.';
                                        
                                        $avgTimeSeconds = number_format(($player['avg_time'] ?? 0) / 1000, 2);
                                        ?>
                                        <div class="<?php echo $itemClass; ?>">
                                            <div class="rank"><?php echo $medal; ?></div>
                                            <div class="player-info">
                                                <div class="player-name"><?php echo htmlspecialchars($player['username']); ?></div>
                                                <div class="player-stats">
                                                    ✅ <?php echo $player['correct_answers'] ?? 0; ?>/<?php echo $player['total_answers'] ?? 0; ?> | ⏱️ <?php echo $avgTimeSeconds; ?>s
                                                </div>
                                            </div>
                                            <div class="score"><?php echo $player['total_score'] ?? 0; ?> pt</div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="leaderboard-empty">
                                    Nessun giocatore in classifica
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Game Controls (hidden when game is over) -->
                <?php if ($activeRound || $nextQuestion): ?>
                <div class="game-controls-box">
                    <h3>🎮 Controlli Partita</h3>
                    <div class="control-buttons">
                        <?php if ($activeRound): ?>
                            <button class="btn btn-primary btn-large" id="next-question-btn" onclick="nextQuestion(<?php echo $activeRound['id']; ?>)" disabled>
                                ➡️ Prossima Domanda
                            </button>
                        <?php elseif ($nextQuestion): ?>
                            <button class="btn btn-success btn-large" onclick="startRound(<?php echo $nextQuestion['id']; ?>)">
                                ▶ AVVIA ROUND
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Leaderboard Sidebar -->
            <div class="leaderboard-sidebar">
                <!-- Round Leaderboard (Top 10 fastest correct answers) -->
                <div class="round-leaderboard-box" id="round-leaderboard-box" style="display: none;">
                    <h3>⚡ Top 10 Round</h3>
                    <div id="round-leaderboard-content">
                        <div class="round-leaderboard-empty">
                            In attesa di risposte...
                        </div>
                    </div>
                </div>
                
                <!-- General Leaderboard -->
                <div class="leaderboard-box" id="leaderboard-box">
                    <h3>🏆 Classifica</h3>
                    <div id="leaderboard-content">
                        <div class="leaderboard-empty">
                            Attendi la fine del timer per vedere la classifica
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Timer countdown
        let timerInterval = null;
        let timeRemaining = <?php echo $activeRound ? intval($activeRound['timer'] ?? 10) : 0; ?>;
        
        // Start countdown timer
        function startCountdown() {
            const timerDisplay = document.getElementById('countdown-timer');
            const nextBtn = document.getElementById('next-question-btn');
            
            timerInterval = setInterval(() => {
                timeRemaining--;
                if (timerDisplay) {
                    timerDisplay.textContent = timeRemaining;
                    
                    // Change color when time is running out
                    if (timeRemaining <= 5) {
                        timerDisplay.style.color = '#dc3545'; // Red
                    } else if (timeRemaining <= 10) {
                        timerDisplay.style.color = '#ffc107'; // Yellow
                    }
                }
                
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    // Highlight correct answer and show leaderboards after 2 seconds
                    setTimeout(() => {
                        if (nextBtn) {
                            nextBtn.disabled = false;
                            nextBtn.style.animation = 'pulse 1s infinite';
                        }
                        highlightCorrectAnswer();
                        loadLeaderboard();
                        loadRoundLeaderboard(<?php echo $activeRound['id']; ?>);
                    }, 2000);
                }
            }, 1000);
        }
        
        // Highlight correct answer
        function highlightCorrectAnswer() {
            const correctAnswer = <?php echo json_encode($activeRound['correct_answer'] ?? null); ?>;
            if (correctAnswer) {
                const correctBox = document.getElementById('option-' + correctAnswer);
                if (correctBox) {
                    correctBox.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                    correctBox.style.border = '3px solid #155724';
                    correctBox.style.transform = 'scale(1.05)';
                    correctBox.style.boxShadow = '0 8px 25px rgba(40, 167, 69, 0.4)';
                    correctBox.style.transition = 'all 0.5s ease';
                    
                    // Add a checkmark icon
                    const checkmark = document.createElement('div');
                    checkmark.innerHTML = '✓';
                    checkmark.style.cssText = 'position: absolute; top: 10px; right: 10px; font-size: 2em; color: white; font-weight: bold; animation: fadeIn 0.5s ease;';
                    correctBox.style.position = 'relative';
                    correctBox.appendChild(checkmark);
                }
            }
        }
        
        // Load and display leaderboard
        function loadLeaderboard() {
            const leaderboardBox = document.getElementById('leaderboard-box');
            const leaderboardContent = document.getElementById('leaderboard-content');
            
            fetch('../src/api/api.php?endpoint=leaderboard&room_code=<?php echo $roomCode; ?>')
                .then(response => response.json())
                .then(data => {
                    console.log('Leaderboard data:', data);
                    
                    if (!data.success) {
                        console.error('Leaderboard error:', data.error || data.message);
                        leaderboardContent.innerHTML = '<div class="leaderboard-empty">Errore: ' + (data.error || 'Sconosciuto') + '</div>';
                        leaderboardBox.classList.add('visible');
                        return;
                    }
                    
                    if (data.leaderboard && data.leaderboard.length > 0) {
                        let html = '<ul class="leaderboard-list">';
                        
                        data.leaderboard.forEach((player, index) => {
                            const position = index + 1;
                            let itemClass = 'leaderboard-item';
                            
                            if (position === 1) itemClass += ' first';
                            else if (position === 2) itemClass += ' second';
                            else if (position === 3) itemClass += ' third';
                            
                            let medal = '';
                            if (position === 1) medal = '🥇';
                            else if (position === 2) medal = '🥈';
                            else if (position === 3) medal = '🥉';
                            else medal = position;
                            
                            html += `
                                <li class="${itemClass}">
                                    <span class="leaderboard-position">${medal}</span>
                                    <span class="leaderboard-name">${player.username}</span>
                                    <span class="leaderboard-score">${player.total_score || 0} pt</span>
                                </li>
                            `;
                        });
                        
                        html += '</ul>';
                        leaderboardContent.innerHTML = html;
                    } else {
                        leaderboardContent.innerHTML = '<div class="leaderboard-empty">Nessun punteggio disponibile</div>';
                    }
                    
                    // Show leaderboard with animation
                    leaderboardBox.classList.add('visible');
                })
                .catch(error => {
                    console.error('Error loading leaderboard:', error);
                    leaderboardContent.innerHTML = '<div class="leaderboard-empty">Errore nel caricamento</div>';
                });
        }
        
        // Load and display round leaderboard (top 10 fastest correct answers)
        function loadRoundLeaderboard(roundId) {
            const roundLeaderboardBox = document.getElementById('round-leaderboard-box');
            const roundLeaderboardContent = document.getElementById('round-leaderboard-content');
            
            fetch('../src/api/api.php?endpoint=round_leaderboard&room_code=<?php echo $roomCode; ?>&round_id=' + roundId)
                .then(response => response.json())
                .then(data => {
                    console.log('Round leaderboard data:', data);
                    
                    if (data.success && data.leaderboard && data.leaderboard.length > 0) {
                        let html = '<ul class="round-leaderboard-list">';
                        
                        data.leaderboard.forEach((player, index) => {
                            const position = index + 1;
                            const points = player.points || 0;
                            
                            let positionBadge = '';
                            if (position === 1) positionBadge = '🥇';
                            else if (position === 2) positionBadge = '🥈';
                            else if (position === 3) positionBadge = '🥉';
                            else positionBadge = position + '.';
                            
                            html += `
                                <li class="round-leaderboard-item">
                                    <span class="round-position">${positionBadge}</span>
                                    <span class="round-player-name">${player.username}</span>
                                    <span class="round-time">${parseFloat(player.time_taken).toFixed(2)}s</span>
                                    <span class="round-points">+${points} pt</span>
                                </li>
                            `;
                        });
                        
                        html += '</ul>';
                        roundLeaderboardContent.innerHTML = html;
                        roundLeaderboardBox.style.display = 'block';
                    } else {
                        roundLeaderboardContent.innerHTML = '<div class="round-leaderboard-empty">Nessuna risposta corretta</div>';
                        roundLeaderboardBox.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading round leaderboard:', error);
                    roundLeaderboardContent.innerHTML = '<div class="round-leaderboard-empty">Errore nel caricamento</div>';
                });
        }
        
        // Start timer when page loads only if there's time remaining
        document.addEventListener('DOMContentLoaded', () => {
            // Set up cancel game button
            const cancelGameBtn = document.getElementById('cancel-game-btn');
            if (cancelGameBtn) {
                cancelGameBtn.addEventListener('click', cancelGame);
            }
            
            <?php if ($activeRound): ?>
            if (timeRemaining > 0) {
                startCountdown();
            } else if (timeRemaining === 0) {
                // Timer already expired, show correct answer and leaderboard
                highlightCorrectAnswer();
                loadLeaderboard();
                const nextBtn = document.getElementById('next-question-btn');
                if (nextBtn) {
                    nextBtn.disabled = false;
                    nextBtn.style.animation = 'pulse 1s infinite';
                }
            }
            <?php endif; ?>
            
            <?php if (!$activeRound && !$nextQuestion): ?>
            // Game is over, load final leaderboard
            console.log('Game is over, loading final leaderboard...');
            loadFinalLeaderboard();
            <?php endif; ?>
        });
        
        // Load final leaderboard when game ends
        function loadFinalLeaderboard() {
            console.log('Loading final leaderboard...');
            const finalLeaderboardContent = document.getElementById('final-leaderboard-content');
            
            if (!finalLeaderboardContent) {
                console.error('final-leaderboard-content element not found');
                return;
            }
            
            fetch('../src/api/api.php?endpoint=leaderboard&room_code=<?php echo $roomCode; ?>')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Leaderboard data:', data);
                    
                    if (data.success && data.leaderboard && data.leaderboard.length > 0) {
                        let html = '<ul class="leaderboard-list">';
                        
                        data.leaderboard.forEach((player, index) => {
                            const position = index + 1;
                            let itemClass = 'leaderboard-item';
                            
                            if (position === 1) itemClass += ' first';
                            else if (position === 2) itemClass += ' second';
                            else if (position === 3) itemClass += ' third';
                            
                            let medal = '';
                            if (position === 1) medal = '🥇 ';
                            else if (position === 2) medal = '🥈 ';
                            else if (position === 3) medal = '🥉 ';
                            else medal = position + '. ';
                            
                            html += `
                                <li class="${itemClass}">
                                    <span class="leaderboard-position">${medal}</span>
                                    <span class="leaderboard-name">${player.username}</span>
                                    <span class="leaderboard-score">${player.total_score || 0} pt</span>
                                </li>
                            `;
                        });
                        
                        html += '</ul>';
                        finalLeaderboardContent.innerHTML = html;
                        console.log('Final leaderboard loaded successfully');
                    } else {
                        console.log('No leaderboard data available');
                        finalLeaderboardContent.innerHTML = '<div class="leaderboard-empty">Nessun giocatore in classifica</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading final leaderboard:', error);
                    if (finalLeaderboardContent) {
                        finalLeaderboardContent.innerHTML = '<div class="leaderboard-empty">Errore nel caricamento della classifica</div>';
                    }
                });
        }
        
        function nextQuestion(roundId) {
            // Close current round and reload to show next question
            fetch('../src/api/api.php?endpoint=game&action=close_round&round_id=' + roundId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ round_id: roundId })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + (data.error || data.message || 'Impossibile passare alla prossima domanda'));
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Errore: risposta del server non valida.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Errore nella comunicazione con il server: ' + error.message);
            });
        }
        
        function startRound(roundId) {
            fetch('../src/api/api.php?endpoint=game&action=start_round', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ round_id: roundId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Errore: ' + (data.error || 'Impossibile avviare il round'));
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Errore nella comunicazione con il server');
            });
        }
        
        function cancelGame() {
            if (!confirm('Vuoi chiudere la partita? Tutti i round saranno riportati allo stato iniziale e la stanza verrà cancellata.')) {
                return;
            }
            
            const questionSetId = <?php echo $questionSetId ?? 0; ?>;
            const roomCode = '<?php echo $roomCode ?? ''; ?>';
            
            // Reset della partita
            fetch('../src/api/api.php?endpoint=game&action=reset_game', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    question_set_id: questionSetId,
                    room_code: roomCode
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || data.message || 'Impossibile resettare la partita');
                }
                
                // Cancellazione della stanza
                return fetch('../src/api/api.php?endpoint=cancel_room', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const questionSetId = <?php echo $questionSetId ?? 0; ?>;
                    setTimeout(() => {
                        window.location.href = 'admin.php?question_set_id=' + questionSetId;
                    }, 500);
                } else {
                    alert('Errore: ' + (data.error || 'Impossibile chiudere la partita'));
                }
            })
            .catch(error => {
                console.error('Error cancelling game:', error);
                alert('Errore: ' + error.message);
            });
        }
    </script>
</body>
</html>