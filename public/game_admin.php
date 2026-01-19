<?php
session_start();

// Check admin access
if (!isset($_SESSION['logged_in_via_login']) || !isset($_SESSION['user_id']) || isset($_SESSION['player_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../src/controllers/GameController.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';
require_once __DIR__ . '/../src/repository/RoomRepo.php';
require_once __DIR__ . '/../src/repository/PlayerRepo.php';
require_once __DIR__ . '/../src/config/database.php';

$game = new GameController();
$admin = new AdminController();
$roomRepo = new RoomRepo();
$playerRepo = new PlayerRepo();

// Get room info - Try from session first, then get latest active room
$roomCode = $_SESSION['room_code'] ?? null;
$questionSetId = null;
$room = null;

if ($roomCode) {
    $room = $roomRepo->getRoomByCode($roomCode);
    if ($room) {
        $questionSetId = $room['question_set_id'];
    }
}

// If no room in session, get the latest active room for this admin
if (!$room) {
    $userId = $_SESSION['user_id'];
    // Get latest room created by this admin
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE created_by = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    $conn->close();
    
    if ($room) {
        $roomCode = $room['room_code'];
        $questionSetId = $room['question_set_id'];
        // Save in session for next time
        $_SESSION['room_code'] = $roomCode;
    }
}

// Get questions from the set
$questions = [];
$setInfo = null;
if ($questionSetId) {
    $setResult = $admin->getQuestionSetRounds($questionSetId);
    error_log("game_admin.php - getQuestionSetRounds($questionSetId) returned: " . json_encode($setResult));
    if ($setResult['success']) {
        $questions = $setResult['rounds'];
        error_log("game_admin.php - questions loaded: " . count($questions));
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
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .game-layout {
            max-width: 1200px;
            margin: 25px auto;
        }

        .main-game-area {
            display: flex;
            flex-direction: column;
            gap: 25px;
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
                <a href="admin.php" class="btn btn-secondary">Torna a Admin</a>
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
                            ⏱ Timer: <strong><?php echo $activeRound['timer'] ?? 10; ?> secondi</strong>
                        </div>

                        <div class="question-text">
                            <?php echo htmlspecialchars($activeRound['question'] ?? 'Nessuna domanda'); ?>
                        </div>

                        <?php if ($activeRound['round_type'] != 'clickfirst'): ?>
                            <div class="options-display">
                                <div class="option-box">
                                    <div class="option-number">1</div>
                                    <div class="option-text"><?php echo htmlspecialchars($activeRound['option1'] ?? 'Opzione 1'); ?></div>
                                </div>
                                <div class="option-box">
                                    <div class="option-number">2</div>
                                    <div class="option-text"><?php echo htmlspecialchars($activeRound['option2'] ?? 'Opzione 2'); ?></div>
                                </div>
                                <?php if ($activeRound['round_type'] == 'multiple'): ?>
                                    <div class="option-box">
                                        <div class="option-number">3</div>
                                        <div class="option-text"><?php echo htmlspecialchars($activeRound['option3'] ?? 'Opzione 3'); ?></div>
                                    </div>
                                    <div class="option-box">
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
                            <button class="btn btn-danger btn-large" onclick="closeRound()">
                                ⏹ Chiudi Round
                            </button>
                            <button class="btn btn-primary btn-large" onclick="showResults()">
                                📊 Mostra Risultati
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
                        <button class="btn btn-secondary btn-large" onclick="showLeaderboard()">
                            🏆 Classifica
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        function closeRound() {
            if (!confirm('Vuoi chiudere il round corrente e mostrare i risultati?')) {
                return;
            }

            fetch('../src/api/api.php?endpoint=game&action=close_round', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResults();
                    setTimeout(() => location.reload(), 3000);
                } else {
                    alert('Errore: ' + (data.error || 'Impossibile chiudere il round'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
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

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
