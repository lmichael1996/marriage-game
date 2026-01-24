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

// Get room info - Try from session first, then get latest active room
$roomCode = $_SESSION['room_code'] ?? null;
$questionSetId = null;
$room = null;

if ($roomCode) {
    // Get room by code from session
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
    error_log("game_admin.php - Loading questions for set ID: $questionSetId");
    $setResult = $admin->getQuestionSetRounds($questionSetId);
    error_log("game_admin.php - getQuestionSetRounds($questionSetId) returned: " . json_encode($setResult));
    if ($setResult['success']) {
        $questions = $setResult['rounds'];
        error_log("game_admin.php - questions loaded: " . count($questions));
        $setInfoResult = $admin->getQuestionSetById($questionSetId);
        if ($setInfoResult['success']) {
            $setInfo = $setInfoResult['set'];
        }
    } else {
        error_log("game_admin.php - ERROR loading questions: " . ($setResult['error'] ?? 'unknown error'));
    }
} else {
    error_log("game_admin.php - No question_set_id found! Room: " . json_encode($room));
}

// Get current game state for this room's question set
$gameState = $game->getGameState($questionSetId);
$activeRound = $gameState['active_round'] ?? null;
error_log("game_admin.php - Active round: " . json_encode($activeRound));

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
error_log("game_admin.php - Next question: " . json_encode($nextQuestion));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        }
        
        .game-layout {
            max-width: 1400px;
            margin: 25px auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .main-game-area {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .leaderboard-sidebar {
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .leaderboard-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 3px solid #1a1a1a;
            padding: 30px;
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.5s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-radius: 8px;
        }
        
        .leaderboard-box.visible {
            opacity: 1;
            transform: translateX(0);
        }
        
        .leaderboard-box h3 {
            font-size: 1.6em;
            font-weight: 700;
            margin-bottom: 25px;
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid rgba(255,255,255,0.3);
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            letter-spacing: 1px;
        }
        
        .leaderboard-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 18px 16px;
            margin-bottom: 12px;
            background: rgba(255,255,255,0.95);
            border: 2px solid transparent;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .leaderboard-item:hover {
            transform: translateX(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .leaderboard-item.first {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border-color: #ffb300;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
            transform: scale(1.02);
        }
        
        .leaderboard-item.second {
            background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
            border-color: #a0a0a0;
            font-weight: 600;
            box-shadow: 0 3px 12px rgba(192, 192, 192, 0.4);
        }
        
        .leaderboard-item.third {
            background: linear-gradient(135deg, #cd7f32 0%, #e8a864 100%);
            border-color: #a0522d;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 3px 12px rgba(205, 127, 50, 0.4);
        }
        
        .leaderboard-position {
            font-size: 1.5em;
            font-weight: 700;
            margin-right: 18px;
            min-width: 40px;
            text-align: center;
        }
        
        .leaderboard-item.first .leaderboard-position {
            font-size: 2em;
        }
        
        .leaderboard-name {
            flex: 1;
            font-size: 1.15em;
            font-weight: 500;
        }
        
        .leaderboard-item.first .leaderboard-name {
            font-size: 1.25em;
        }
        
        .leaderboard-score {
            font-size: 1.3em;
            font-weight: 700;
            color: #4caf50;
            background: rgba(76, 175, 80, 0.1);
            padding: 6px 12px;
            border-radius: 6px;
            min-width: 70px;
            text-align: center;
        }
        
        .leaderboard-item.first .leaderboard-score {
            font-size: 1.5em;
            color: #1a5d1a;
            background: rgba(26, 93, 26, 0.15);
        }
        
        .leaderboard-item.third .leaderboard-score {
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .leaderboard-empty {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255,255,255,0.9);
            font-style: italic;
            font-size: 1.1em;
        }

        .question-display {
            background: #fff;
            border: 2px solid #1a1a1a;
            padding: 40px;
            min-height: 400px;
        }

        .question-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1a1a1a;
        }

        .question-header h2 {
            font-size: 2em;
            font-weight: 400;
            margin-bottom: 10px;
        }

        .round-badge {
            display: inline-block;
            padding: 8px 20px;
            background: #f0f0f0;
            border: 1px solid #1a1a1a;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .round-badge.active {
            background: #4caf50;
            color: #fff;
            border-color: #4caf50;
        }

        .question-text {
            font-size: 1.6em;
            text-align: center;
            margin: 30px 0;
            line-height: 1.6;
            font-weight: 400;
            min-height: 80px;
        }

        .options-display {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 30px 0;
        }

        .option-box {
            background: #f9f9f9;
            border: 2px solid #1a1a1a;
            padding: 25px;
            text-align: center;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .option-box.correct {
            background: #e8f5e9;
            border-color: #4caf50;
        }

        .option-number {
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 8px;
            color: #666;
        }

        .option-text {
            font-size: 1.1em;
            line-height: 1.4;
        }

        .game-controls-box {
            background: #fff;
            border: 2px solid #1a1a1a;
            padding: 30px;
        }

        .game-controls-box h3 {
            font-size: 1.3em;
            font-weight: 400;
            margin-bottom: 20px;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #1a1a1a;
        }

        .control-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-large {
            padding: 18px 30px;
            font-size: 1.1em;
            font-weight: 600;
        }

        .timer-info {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            margin: 20px 0;
            font-size: 1.1em;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .game-layout {
                grid-template-columns: 1fr;
            }
            
            .leaderboard-sidebar {
                position: static;
                order: -1; /* Show leaderboard above on mobile */
            }
            
            .question-display {
                padding: 25px;
            }

            .question-text {
                font-size: 1.3em;
            }

            .options-display {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <button class="btn btn-danger" onclick="cancelGame()">🚫 Chiudi Partita</button>
            </div>
        </div>

        <?php if (empty($questions) && $questionSetId): ?>
            <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 4px;">
                <strong>⚠️ Debug Info:</strong><br>
                Room Code: <?php echo htmlspecialchars($roomCode ?? 'NULL'); ?><br>
                Question Set ID: <?php echo htmlspecialchars($questionSetId ?? 'NULL'); ?><br>
                Questions Count: <?php echo count($questions); ?><br>
                Room Data: <?php echo htmlspecialchars(json_encode($room)); ?>
            </div>
        <?php endif; ?>

        <div class="game-layout">
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
                        <div class="empty-state">
                            <div class="empty-state-icon">🎯</div>
                            <h3>Nessun Round Disponibile</h3>
                            <p>Tutte le domande sono state completate o non ci sono domande nel set</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Game Controls -->
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
                        <?php else: ?>
                            <button class="btn btn-secondary btn-large" disabled>
                                🏁 Partita Terminata
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Leaderboard Sidebar -->
            <div class="leaderboard-sidebar">
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
        
        <?php if ($activeRound): ?>
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
                    if (nextBtn) {
                        nextBtn.disabled = false;
                        nextBtn.style.animation = 'pulse 1s infinite';
                    }
                    // Highlight correct answer
                    highlightCorrectAnswer();
                    // Show leaderboard when timer ends
                    loadLeaderboard();
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
                    
                    if (data.success && data.leaderboard && data.leaderboard.length > 0) {
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
        
        // Start timer when page loads only if there's time remaining
        document.addEventListener('DOMContentLoaded', () => {
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
        });
        <?php endif; ?>
        
        function nextQuestion(roundId) {
            if (!confirm('Passare alla prossima domanda?')) {
                return;
            }
            
            console.log('Closing round:', roundId);
            
            // Close current round and reload to show next question
            fetch('../src/api/api.php?endpoint=game&action=close_round&round_id=' + roundId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ round_id: roundId })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text(); // Get as text first to see what we're receiving
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Errore: ' + (data.error || data.message || 'Impossibile passare alla prossima domanda'));
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    alert('Errore: risposta del server non valida. Controlla la console per dettagli.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Errore nella comunicazione con il server: ' + error.message);
            });
        }
        
        function startRound(roundId) {
            console.log('Admin: Avvio round', roundId);
            fetch('../src/api/api.php?endpoint=game&action=start_round', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    round_id: roundId
                })
            })
            .then(response => {
                console.log('Admin: Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Admin: Response data:', data);
                if (data.success) {
                    console.log('Admin: Round avviato con successo, ricarico pagina');
                    location.reload();
                } else {
                    console.error('Admin: Errore avvio round:', data.error);
                    alert('Errore: ' + (data.error || 'Impossibile avviare il round'));
                }
            })
            .catch(error => {
                console.error('Admin: Fetch error:', error);
                alert('Errore nella comunicazione con il server');
            });
        }

        function showResults() {
            alert('Risultati round mostrati ai giocatori!');
        }

        function showLeaderboard() {
            fetch('../src/api/api.php?endpoint=leaderboard')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.leaderboard) {
                        let message = '🏆 CLASSIFICA GENERALE 🏆\n\n';
                        data.leaderboard.forEach((player, index) => {
                            const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '  ';
                            message += `${medal} ${index + 1}. ${player.username}: ${player.total_score} punti\n`;
                        });
                        alert(message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function cancelGame() {
            if (!confirm('Vuoi chiudere la partita? Tutti i round saranno riportati allo stato iniziale e la stanza verrà cancellata.')) {
                return;
            }
            
            const questionSetId = <?php echo $questionSetId ?? 0; ?>;
            const roomCode = '<?php echo $roomCode ?? ''; ?>';
            
            // Prima resetta la partita, poi cancella la stanza
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
                
                // Se il reset ha successo, procedi con la cancellazione della stanza
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
                    alert('Partita chiusa con successo');
                    window.location.href = 'admin.php';
                } else {
                    alert('Errore: ' + (data.error || 'Impossibile chiudere la partita'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore: ' + error.message);
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

