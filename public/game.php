<?php
// Check admin access
session_start();
if (!isset($_SESSION['logged_in_via_login']) || !isset($_SESSION['user_id']) || isset($_SESSION['player_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../src/repository/RoundRepo.php';
require_once __DIR__ . '/../src/repository/PlayerRepo.php';

$roundModel = new RoundRepo();
$playerModel = new PlayerRepo();

// Get active round
$active_round = $roundModel->getActiveRound();

// Get all players
$conn = getDBConnection();
$result = $conn->query("SELECT * FROM players ORDER BY total_score DESC");
$players = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partita in Corso - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: url('../assets/image/background.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: Georgia, serif;
            color: #1a1a1a;
            margin: 0;
            padding: 20px;
        }

        .game-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }

        .game-main {
            background: rgba(255, 255, 255, 0.95);
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
