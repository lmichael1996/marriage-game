<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/services/GameService.php';
require_once __DIR__ . '/../src/services/AdminService.php';
require_once __DIR__ . '/../src/services/QuestionService.php';

// Check admin access
requireAdmin();

$game = new GameService();
$admin = new AdminService();
$questionService = new QuestionService();

// Get room info
$roomCode = $_SESSION['room_code'] ?? null;
$questionSetId = null;

if ($roomCode) {
    // Get room details to find question set
    require_once __DIR__ . '/../src/repository/RoomRepo.php';
    $roomRepo = new RoomRepo();
    $room = $roomRepo->getRoomByCode($roomCode);
    if ($room) {
        $questionSetId = $room['question_set_id'];
    }
}

// Get questions from the set
$questions = [];
$setInfo = null;
if ($questionSetId) {
    $setInfo = $questionService->getQuestionSetWithQuestions($questionSetId);
    if ($setInfo) {
        $questions = $setInfo['questions'] ?? [];
    }
}

// Get current game state (use GameService directly)
$activeRound = $game->getActiveRound($questionSetId);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .game-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 25px;
            margin-top: 25px;
        }

        .questions-sidebar {
            background: #fff;
            border: 2px solid #1a1a1a;
            padding: 25px;
            height: fit-content;
        }

        .questions-sidebar h3 {
            font-size: 1.2em;
            font-weight: 400;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #1a1a1a;
        }

        .question-list {
            list-style: none;
        }

        .question-item {
            padding: 12px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .question-item:hover {
            background: #f0f0f0;
        }

        .question-item.active {
            background: #1a1a1a;
            color: #fff;
            border-color: #1a1a1a;
        }

        .question-item.completed {
            background: #f9f9f9;
            opacity: 0.7;
        }

        .question-number {
            font-weight: 600;
            margin-right: 10px;
        }

        .question-status {
            font-size: 0.9em;
        }

        .game-display {
            background: #fff;
            border: 2px solid #1a1a1a;
            padding: 40px;
            min-height: 600px;
        }

        .question-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #1a1a1a;
        }

        .question-header h2 {
            font-size: 2.5em;
            font-weight: 400;
            margin-bottom: 15px;
        }

        .question-type-badge {
            display: inline-block;
            padding: 8px 20px;
            background: #f0f0f0;
            border: 1px solid #1a1a1a;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .question-content {
            max-width: 900px;
            margin: 0 auto;
        }

        .question-text {
            font-size: 1.8em;
            text-align: center;
            margin-bottom: 50px;
            line-height: 1.6;
            font-weight: 400;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }

        .option-card {
            background: #fff;
            border: 2px solid #1a1a1a;
            padding: 30px;
            text-align: center;
            transition: all 0.2s ease;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .option-card.correct {
            background: #e8f5e9;
            border-color: #4caf50;
        }

        .option-number {
            font-size: 1.5em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .option-text {
            font-size: 1.2em;
            line-height: 1.5;
        }

        .game-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .timer-display {
            text-align: center;
            font-size: 1.2em;
            color: #666;
            margin-bottom: 20px;
        }

        .no-question {
            text-align: center;
            padding: 80px 20px;
            color: #666;
        }

        .no-question h3 {
            font-size: 1.8em;
            font-weight: 400;
            margin-bottom: 15px;
        }

        @media (max-width: 1024px) {
            .game-container {
                grid-template-columns: 1fr;
            }

            .questions-sidebar {
                order: 2;
            }

            .game-display {
                order: 1;
            }
        }

        @media (max-width: 768px) {
            .game-display {
                padding: 25px;
            }

            .question-header h2 {
                font-size: 1.8em;
            }

            .question-text {
                font-size: 1.3em;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Partita in Corso</h1>
            <div class="user-info">
                <span>Stanza: <strong><?php echo htmlspecialchars($roomCode ?? 'N/A'); ?></strong></span>
                <?php if ($setInfo): ?>
                    <span>Set: <strong><?php echo htmlspecialchars($setInfo['set_name']); ?></strong></span>
                <?php endif; ?>
                <a href="admin.php" class="btn btn-secondary">Torna a Admin</a>
            </div>
        </div>

        <div class="game-container">
            <!-- Questions Sidebar -->
            <div class="questions-sidebar">
                <h3>📋 Domande (<?php echo count($questions); ?>)</h3>
                <ul class="question-list">
                    <?php if (empty($questions)): ?>
                        <li style="text-align: center; color: #666; padding: 20px;">
                            Nessuna domanda nel set
                        </li>
                    <?php else: ?>
                        <?php foreach ($questions as $index => $q): ?>
                            <li class="question-item <?php echo ($activeRound && $activeRound['id'] == $q['id']) ? 'active' : ''; ?>"
                                data-question-id="<?php echo $q['id']; ?>">
                                <span>
                                    <span class="question-number"><?php echo ($index + 1); ?>.</span>
                                    <?php
                                        $preview = substr($q['question'] ?? 'Domanda', 0, 30);
                                        echo htmlspecialchars($preview) . (strlen($q['question']) > 30 ? '...' : '');
                                    ?>
                                </span>
                                <span class="question-status">
                                    <?php
                                        if ($activeRound && $activeRound['id'] == $q['id']) echo '▶';
                                        else echo '○';
                                    ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Game Display -->
            <div class="game-display">
                <?php if ($activeRound): ?>
                    <div class="question-header">
                        <h2>Round <?php echo $activeRound['round_number']; ?></h2>
                        <span class="question-type-badge">
                            <?php
                                $types = [
                                    'multiple' => 'Scelta Multipla',
                                    'truefalse' => 'Vero o Falso',
                                    'clickfirst' => 'Clicca per Primo'
                                ];
                                echo $types[$activeRound['round_type']] ?? 'Domanda';
                            ?>
                        </span>
                    </div>

                    <div class="question-content">
                        <div class="timer-display">
                            ⏱ Timer: <?php echo $activeRound['timer'] ?? 10; ?> secondi
                        </div>

                        <div class="question-text">
                            <?php echo htmlspecialchars($activeRound['question'] ?? 'Nessuna domanda'); ?>
                        </div>

                        <?php if ($activeRound['round_type'] != 'clickfirst'): ?>
                            <div class="options-grid">
                                <div class="option-card <?php echo ($activeRound['correct_answer'] == 1) ? 'correct' : ''; ?>">
                                    <div class="option-number">1</div>
                                    <div class="option-text"><?php echo htmlspecialchars($activeRound['option1'] ?? 'Opzione 1'); ?></div>
                                </div>
                                <div class="option-card <?php echo ($activeRound['correct_answer'] == 2) ? 'correct' : ''; ?>">
                                    <div class="option-number">2</div>
                                    <div class="option-text"><?php echo htmlspecialchars($activeRound['option2'] ?? 'Opzione 2'); ?></div>
                                </div>
                                <?php if ($activeRound['round_type'] == 'multiple'): ?>
                                    <div class="option-card <?php echo ($activeRound['correct_answer'] == 3) ? 'correct' : ''; ?>">
                                        <div class="option-number">3</div>
                                        <div class="option-text"><?php echo htmlspecialchars($activeRound['option3'] ?? 'Opzione 3'); ?></div>
                                    </div>
                                    <div class="option-card <?php echo ($activeRound['correct_answer'] == 4) ? 'correct' : ''; ?>">
                                        <div class="option-number">4</div>
                                        <div class="option-text"><?php echo htmlspecialchars($activeRound['option4'] ?? 'Opzione 4'); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 60px 20px;">
                                <div style="font-size: 3em; margin-bottom: 20px;">⚡</div>
                                <div style="font-size: 1.5em; color: #666;">
                                    I giocatori devono cliccare più velocemente possibile!
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="game-controls">
                            <button class="btn btn-danger" onclick="closeRound()">
                                Chiudi Round
                            </button>
                            <button class="btn btn-primary" onclick="showLeaderboard()">
                                Mostra Classifica
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-question">
                        <h3>🎯 Nessun Round Attivo</h3>
                        <p style="margin-bottom: 30px;">Avvia un nuovo round per iniziare il gioco</p>
                        <?php if (!empty($questions)): ?>
                            <button class="btn btn-primary" onclick="startNextRound()">
                                Avvia Prossimo Round
                            </button>
                        <?php else: ?>
                            <p style="color: #999;">Nessuna domanda disponibile nel set selezionato</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 3 seconds to update game state
        setInterval(() => {
            location.reload();
        }, 3000);

        function startNextRound() {
            const questions = <?php echo json_encode($questions); ?>;
            const activeRoundId = <?php echo $activeRound ? $activeRound['id'] : 'null'; ?>;

            // Find first question that's not the active one
            const nextQuestion = questions.find(q => q.id !== activeRoundId);

            if (nextQuestion) {
                fetch('../src/api/api.php?endpoint=game&action=start_round', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        round_id: nextQuestion.id
                    })
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
                    console.error('Error:', error);
                    alert('Errore nella comunicazione con il server');
                });
            } else {
                alert('Nessun round disponibile da avviare');
            }
        }

        function closeRound() {
            if (!confirm('Vuoi chiudere il round corrente?')) {
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
                    location.reload();
                } else {
                    alert('Errore: ' + (data.error || 'Impossibile chiudere il round'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore nella comunicazione con il server');
            });
        }

        function showLeaderboard() {
            fetch('../src/api/api.php?endpoint=leaderboard')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.leaderboard) {
                        let message = '🏆 CLASSIFICA 🏆\n\n';
                        data.leaderboard.forEach((player, index) => {
                            message += `${index + 1}. ${player.username}: ${player.total_score} punti\n`;
                        });
                        alert(message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
            border: 3px solid #1a1a1a;
            padding: 30px;
        }

        .game-sidebar {
            background: rgba(255, 255, 255, 0.95);
            border: 3px solid #1a1a1a;
            padding: 20px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1a1a1a;
        }

        .header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 400;
        }

        .btn {
            padding: 12px 24px;
            border: 2px solid #1a1a1a;
            background: #fff;
            color: #1a1a1a;
            cursor: pointer;
            font-family: Georgia, serif;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn:hover {
            background: #1a1a1a;
            color: #fff;
        }

        .btn-danger {
            background: #dc143c;
            color: #fff;
            border-color: #dc143c;
        }

        .btn-danger:hover {
            background: #a00;
            border-color: #a00;
        }

        .btn-success {
            background: #28a745;
            color: #fff;
            border-color: #28a745;
        }

        .btn-success:hover {
            background: #1e7e34;
            border-color: #1e7e34;
        }

        .round-display {
            background: #f8f8f8;
            border: 2px solid #1a1a1a;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }

        .round-display h2 {
            margin: 0 0 20px 0;
            font-size: 1.8em;
            font-weight: 400;
        }

        .round-type {
            display: inline-block;
            padding: 8px 16px;
            background: #1a1a1a;
            color: #fff;
            margin-bottom: 20px;
            font-size: 0.9em;
        }

        .question-text {
            font-size: 1.4em;
            margin: 30px 0;
            line-height: 1.6;
        }

        .options-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }

        .options-list li {
            background: #fff;
            border: 2px solid #1a1a1a;
            padding: 15px 20px;
            margin: 10px 0;
            font-size: 1.1em;
        }

        .options-list li.correct {
            background: #d4edda;
            border-color: #28a745;
        }

        .timer-display {
            font-size: 3em;
            font-weight: 700;
            color: #dc143c;
            margin: 20px 0;
        }

        .no-round {
            text-align: center;
            padding: 60px 20px;
        }

        .no-round h2 {
            font-size: 2em;
            color: #666;
            margin-bottom: 20px;
        }

        .leaderboard h3 {
            margin: 0 0 20px 0;
            font-size: 1.5em;
            font-weight: 400;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 10px;
        }

        .player-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            margin: 8px 0;
            background: #f8f8f8;
            border: 2px solid #e0e0e0;
        }

        .player-item.top-3 {
            background: #fff;
            border-color: #1a1a1a;
        }

        .player-rank {
            font-size: 1.2em;
            font-weight: 700;
            min-width: 40px;
        }

        .player-rank.gold { color: #ffd700; }
        .player-rank.silver { color: #c0c0c0; }
        .player-rank.bronze { color: #cd7f32; }

        .player-name {
            flex: 1;
            font-size: 1em;
            margin: 0 15px;
        }

        .player-score {
            font-size: 1.2em;
            font-weight: 700;
            color: #dc143c;
        }

        .round-controls {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .waiting-players {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .waiting-players h4 {
            margin: 0 0 15px 0;
            font-size: 1em;
            color: #666;
        }

        .answer-count {
            font-size: 1.5em;
            color: #28a745;
            font-weight: 700;
            margin: 20px 0;
        }

        @media (max-width: 1200px) {
            .game-container {
                grid-template-columns: 1fr;
            }

            .game-sidebar {
                max-height: 500px;
            }
        }
    </style>
</head>
<body>
    <div class="game-container">
        <div class="game-main">
            <div class="header">
                <h1>🎮 Partita in Corso</h1>
                <div>
                    <a href="admin.php?tab=game" class="btn">← Pannello Admin</a>
                    <a href="logout.php?logout=1" class="btn btn-danger">Logout</a>
                </div>
            </div>

            <?php if ($active_round): ?>
                <div class="round-display">
                    <h2>Round #<?php echo $active_round['round_number']; ?></h2>

                    <?php
                    $type_labels = [
                        'multiple' => 'Scelta Multipla',
                        'truefalse' => 'Vero o Falso',
                        'clickfirst' => 'Clicca per Primo'
                    ];
                    ?>
                    <span class="round-type"><?php echo $type_labels[$active_round['round_type']] ?? 'Domanda'; ?></span>

                    <div class="question-text">
                        <?php echo htmlspecialchars($active_round['question']); ?>
                    </div>

                    <?php if ($active_round['round_type'] !== 'clickfirst'): ?>
                        <ol class="options-list" start="1">
                            <li><?php echo htmlspecialchars($active_round['option1']); ?></li>
                            <li><?php echo htmlspecialchars($active_round['option2']); ?></li>
                            <?php if ($active_round['round_type'] === 'multiple'): ?>
                                <li><?php echo htmlspecialchars($active_round['option3']); ?></li>
                                <li><?php echo htmlspecialchars($active_round['option4']); ?></li>
                            <?php endif; ?>
                        </ol>

                        <div style="margin-top: 30px;">
                            <strong>Risposta Corretta:</strong>
                            Opzione <?php echo $active_round['correct_answer']; ?>
                        </div>
                    <?php else: ?>
                        <div style="margin: 40px 0; font-size: 1.5em; color: #dc143c;">
                            ⚡ Il primo che clicca vince!
                        </div>
                    <?php endif; ?>

                    <?php if ($active_round['timer']): ?>
                        <div class="timer-display" id="timer">
                            <?php echo $active_round['timer']; ?>s
                        </div>
                    <?php endif; ?>

                    <div class="answer-count" id="answer-count">
                        <span id="answer-total">0</span> risposte ricevute
                    </div>

                    <div class="round-controls">
                        <form method="POST" action="admin.php" style="flex: 1;">
                            <input type="hidden" name="action" value="close_round">
                            <input type="hidden" name="round_id" value="<?php echo $active_round['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="width: 100%;">
                                Chiudi Round
                            </button>
                        </form>

                        <button class="btn btn-success" onclick="showResults()" style="flex: 1;">
                            Mostra Risultati
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-round">
                    <h2>Nessun round attivo</h2>
                    <p>Torna al pannello admin per avviare una partita.</p>
                    <a href="admin.php?tab=game" class="btn" style="margin-top: 20px;">
                        Vai al Pannello Admin
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="game-sidebar">
            <div class="leaderboard">
                <h3>🏆 Classifica</h3>
                <?php if (empty($players)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">
                        Nessun giocatore connesso
                    </p>
                <?php else: ?>
                    <?php
                    $rank = 1;
                    foreach ($players as $player):
                        $rankClass = '';
                        if ($rank === 1) $rankClass = 'gold';
                        elseif ($rank === 2) $rankClass = 'silver';
                        elseif ($rank === 3) $rankClass = 'bronze';

                        $itemClass = $rank <= 3 ? 'player-item top-3' : 'player-item';
                    ?>
                        <div class="<?php echo $itemClass; ?>">
                            <span class="player-rank <?php echo $rankClass; ?>">#<?php echo $rank; ?></span>
                            <span class="player-name"><?php echo htmlspecialchars($player['username']); ?></span>
                            <span class="player-score"><?php echo $player['total_score']; ?>pt</span>
                        </div>
                    <?php
                        $rank++;
                    endforeach;
                    ?>
                <?php endif; ?>
            </div>

            <?php if ($active_round): ?>
            <div class="waiting-players">
                <h4>In attesa di risposte...</h4>
                <div id="waiting-list">
                    Caricamento...
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh leaderboard
        function refreshLeaderboard() {
            fetch('../src/api/api.php?endpoint=leaderboard')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update leaderboard (you can implement dynamic update here)
                        console.log('Leaderboard updated', data.leaderboard);
                    }
                })
                .catch(error => console.error('Error refreshing leaderboard:', error));
        }

        // Update answer count
        function updateAnswerCount() {
            fetch('../src/api/api.php?endpoint=game&action=answer_count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('answer-total').textContent = data.count;
                    }
                })
                .catch(error => console.error('Error updating answer count:', error));
        }

        // Show results
        function showResults() {
            alert('Funzionalità "Mostra Risultati" in arrivo!');
            // TODO: Implement results display
        }

        // Timer countdown
        <?php if ($active_round && $active_round['timer']): ?>
        let timeLeft = <?php echo $active_round['timer']; ?>;
        const timerDisplay = document.getElementById('timer');

        const countdown = setInterval(() => {
            timeLeft--;
            timerDisplay.textContent = timeLeft + 's';

            if (timeLeft <= 0) {
                clearInterval(countdown);
                timerDisplay.textContent = 'Tempo scaduto!';
                timerDisplay.style.color = '#666';
            } else if (timeLeft <= 5) {
                timerDisplay.style.color = '#dc143c';
            } else if (timeLeft <= 10) {
                timerDisplay.style.color = '#ff6b35';
            }
        }, 1000);
        <?php endif; ?>

        // Auto-refresh data every 2 seconds
        setInterval(() => {
            refreshLeaderboard();
            updateAnswerCount();
        }, 2000);

        // Initial load
        updateAnswerCount();
    </script>
</body>
</html>
