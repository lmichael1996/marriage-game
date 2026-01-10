<?php
require_once __DIR__ . '/../src/config/auth.php';
require_once __DIR__ . '/../src/models/Round.php';
require_once __DIR__ . '/../src/models/QuestionSet.php';

requireAdmin();

$roundModel = new Round();
$questionSetModel = new QuestionSet();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_question_set') {
        $name = $_POST['set_name'] ?? '';
        $description = $_POST['set_description'] ?? '';
        
        if ($name) {
            $setId = $questionSetModel->create($name, $description);
            header('Location: admin.php?tab=sets&success=created');
            exit;
        }
    }
    
    if ($action === 'update_question_set') {
        $setId = intval($_POST['set_id'] ?? 0);
        $name = $_POST['set_name'] ?? '';
        $description = $_POST['set_description'] ?? '';
        
        if ($setId && $name) {
            $questionSetModel->update($setId, $name, $description);
            header('Location: admin.php?tab=sets&success=updated');
            exit;
        }
    }
    
    if ($action === 'delete_question_set') {
        $setId = intval($_POST['set_id'] ?? 0);
        if ($setId) {
            $questionSetModel->delete($setId);
            header('Location: admin.php?tab=sets&success=deleted');
            exit;
        }
    }
    
    if ($action === 'add_question') {
        $setId = intval($_POST['question_set_id'] ?? 0);
        $round_type = $_POST['round_type'] ?? 'multiple';
        $question = $_POST['question'] ?? '';
        $option1 = $_POST['option1'] ?? '';
        $option2 = $_POST['option2'] ?? '';
        $option3 = $_POST['option3'] ?? '';
        $option4 = $_POST['option4'] ?? '';
        $correct_answer = intval($_POST['correct_answer'] ?? 0);
        
        if ($round_type === 'clickfirst') {
            $correct_answer = null;
        }
        
        if ($setId && $question) {
            $conn = getDBConnection();
            // Get next round number
            $stmt = $conn->prepare("SELECT MAX(round_number) as max_num FROM rounds WHERE question_set_id = ?");
            $stmt->bind_param("i", $setId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $nextNumber = ($row['max_num'] ?? 0) + 1;
            
            $stmt = $conn->prepare("INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iisssssss", $setId, $nextNumber, $round_type, $question, $option1, $option2, $option3, $option4, $correct_answer);
            $stmt->execute();
            $conn->close();
            
            header('Location: admin.php?tab=sets&success=question_added');
            exit;
        }
    }
    
    if ($action === 'create_rounds') {
        $setId = intval($_POST['question_set_id'] ?? 0);
        $num_rounds = intval($_POST['num_rounds'] ?? 0);
        
        for ($i = 1; $i <= $num_rounds; $i++) {
            $round_type = $_POST["round_type_$i"] ?? 'multiple';
            $question = $_POST["question_$i"] ?? '';
            $option1 = $_POST["option1_$i"] ?? '';
            $option2 = $_POST["option2_$i"] ?? '';
            $option3 = $_POST["option3_$i"] ?? '';
            $option4 = $_POST["option4_$i"] ?? '';
            $correct_answer = intval($_POST["correct_answer_$i"] ?? 0);
            
            if ($round_type === 'clickfirst') {
                $correct_answer = null;
            }
            
            if ($question) {
                $conn = getDBConnection();
                $stmt = $conn->prepare("INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iissssssss", $setId, $i, $round_type, $question, $option1, $option2, $option3, $option4, $correct_answer);
                $stmt->execute();
            }
        }
        
        header('Location: admin.php?tab=sets&set=' . $setId . '&success=rounds_created');
        exit;
    }
    
    if ($action === 'save_single_round') {
        $round_number = intval($_POST['round_number'] ?? 0);
        $round_type = $_POST['round_type'] ?? 'multiple';
        $question = $_POST['question'] ?? '';
        $option1 = $_POST['option1'] ?? '';
        $option2 = $_POST['option2'] ?? '';
        $option3 = $_POST['option3'] ?? '';
        $option4 = $_POST['option4'] ?? '';
        $correct_answer = intval($_POST['correct_answer'] ?? 0);
        
        if ($round_type === 'clickfirst') {
            $correct_answer = null;
        }
        
        if ($question) {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("SELECT id FROM rounds WHERE round_number = ?");
            $stmt->bind_param("i", $round_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $round_id = $row['id'];
                
                $stmt = $conn->prepare("UPDATE rounds SET round_type=?, question=?, option1=?, option2=?, option3=?, option4=?, correct_answer=? WHERE id=?");
                $stmt->bind_param("ssssssii", $round_type, $question, $option1, $option2, $option3, $option4, $correct_answer, $round_id);
                $stmt->execute();
            }
            
            $conn->close();
            echo json_encode(['success' => true]);
            exit;
        }
    }
    
    if ($action === 'start_round') {
        $round_id = $_POST['round_id'] ?? 0;
        $roundModel->startRound($round_id);
        header('Location: admin.php');
        exit;
    }
    
    if ($action === 'close_round') {
        $round_id = $_POST['round_id'] ?? 0;
        $roundModel->closeRound($round_id);
        header('Location: admin.php');
        exit;
    }
}

// Get data
$questionSets = $questionSetModel->getAll();
$rounds = $roundModel->getAllRoundsWithStats();
$active_round = $roundModel->getActiveRound();

// Handle search
$searchQuery = $_GET['search'] ?? '';
if ($searchQuery) {
    $questionSets = $questionSetModel->search($searchQuery);
}

// Get selected set
$selectedSetId = $_GET['set'] ?? null;
$selectedSet = null;
$setRounds = [];
if ($selectedSetId) {
    $selectedSet = $questionSetModel->getById($selectedSetId);
    $setRounds = $questionSetModel->getRounds($selectedSetId);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Marriage Game</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Panel - Marriage Game</h1>
            <div class="user-info">
                <span>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php?logout=1" class="btn btn-secondary">Logout</a>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <div class="admin-nav">
            <a href="#sets" class="nav-item active" onclick="showTab('sets', this)">
                <span class="nav-label">Set Domande</span>
            </a>
            <a href="#game" class="nav-item" onclick="showTab('game', this)">
                <span class="nav-label">Partita</span>
            </a>
            <a href="#settings" class="nav-item" onclick="showTab('settings', this)">
                <span class="nav-label">Impostazioni</span>
            </a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                switch($_GET['success']) {
                    case 'created':
                        echo 'Set creato con successo';
                        break;
                    case 'updated':
                        echo 'Set aggiornato';
                        break;
                    case 'deleted':
                        echo 'Set eliminato';
                        break;
                    case 'question_added':
                        echo 'Domanda aggiunta';
                        break;
                    case 'rounds_created':
                        echo 'Domande create';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($active_round): ?>
            <div class="alert alert-success">
                <h3>Round Attivo: #<?php echo $active_round['round_number']; ?>
                <?php 
                $type_labels = [
                    'multiple' => '(Scelta Multipla)',
                    'truefalse' => '(Vero/Falso)',
                    'clickfirst' => '(Clicca per Primo)'
                ];
                echo ' ' . ($type_labels[$active_round['round_type']] ?? '');
                ?>
                </h3>
                <p><strong>Domanda:</strong> <?php echo htmlspecialchars($active_round['question']); ?></p>
                <?php if ($active_round['round_type'] !== 'clickfirst'): ?>
                <ol>
                    <li><?php echo htmlspecialchars($active_round['option1']); ?></li>
                    <li><?php echo htmlspecialchars($active_round['option2']); ?></li>
                    <?php if ($active_round['round_type'] === 'multiple'): ?>
                    <li><?php echo htmlspecialchars($active_round['option3']); ?></li>
                    <li><?php echo htmlspecialchars($active_round['option4']); ?></li>
                    <?php endif; ?>
                </ol>
                <p><strong>Risposta corretta:</strong> Opzione <?php echo $active_round['correct_answer']; ?></p>
                <?php else: ?>
                <p style="color: #ffc107; font-weight: bold;">⚡ Modalità: Il primo che clicca vince!</p>
                <?php endif; ?>
                
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="close_round">
                    <input type="hidden" name="round_id" value="<?php echo $active_round['id']; ?>">
                    <button type="submit" class="btn btn-danger">Chiudi Round</button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Tab: Set di Domande -->
        <div id="tab-sets" class="tab-content active">
            <div class="admin-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <h2 style="margin: 0;">Set di Domande</h2>
                    <button class="btn btn-primary" onclick="showCreateSetModal()">+ Nuovo Set</button>
                </div>
                
                <!-- Search Bar -->
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <input type="text" id="search-sets" placeholder="Cerca set..." style="flex: 1;">
                    <button class="btn btn-primary" onclick="searchSetsTable()">Cerca</button>
                </div>
                
                <!-- Table View -->
                <div id="table-view">
                    <table class="sets-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrizione</th>
                                <th>Domande</th>
                                <th>Data</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="sets-table-body">
                            <?php foreach ($questionSets as $set): ?>
                            <tr data-set-id="<?php echo $set['id']; ?>">
                                <td><strong><?php echo htmlspecialchars($set['set_name']); ?></strong></td>
                                <td><?php echo $set['set_description'] ? htmlspecialchars($set['set_description']) : '<em style="color: #999;">Nessuna</em>'; ?></td>
                                <td><span class="badge-small"><?php echo $set['total_rounds']; ?></span></td>
                                <td><?php echo date('d/m/y', strtotime($set['id'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button class="btn-icon btn-warning" onclick="editSet(<?php echo $set['id']; ?>, '<?php echo addslashes($set['set_name']); ?>', '<?php echo addslashes($set['set_description']); ?>')" title="Modifica">✎</button>
                                        <button class="btn-icon btn-danger" onclick="deleteSet(<?php echo $set['id']; ?>, '<?php echo addslashes($set['set_name']); ?>')" title="Elimina">×</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (empty($questionSets)): ?>
                    <div class="info-box" style="text-align: center; margin-top: 20px;">
                        <h3>Nessun set di domande</h3>
                        <p>Clicca su "Nuovo Set" per iniziare.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Tab: Rounds - RIMOSSO -->
        
        
        <!-- Tab: Partita -->
        <div id="tab-game" class="tab-content">
            <div class="admin-section">
                <h2>Gestione Partite</h2>
                
                <!-- Partite Test -->
                <div class="info-box warning">
                    <h3>🧪 Modalità Test</h3>
                    <p>Crea partite di test per provare i round senza salvare i risultati nel database principale.</p>
                    
                    <div class="btn-group">
                        <button class="btn btn-warning" onclick="createTestGame()">🎮 Crea Partita Test</button>
                        <button class="btn btn-secondary" onclick="viewTestGames()">📋 Visualizza Partite Test</button>
                        <button class="btn btn-danger" onclick="deleteAllTestGames()">🗑️ Elimina Tutte</button>
                    </div>
                </div>
                
                <!-- Lista Partite Test -->
                <div id="test-games-list" style="display: none; margin-bottom: 30px;">
                    <h3>📋 Partite Test Attive</h3>
                    <div id="test-games-container"></div>
                </div>
                
                <!-- Statistiche Generali -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-number"><?php echo count($rounds); ?></div>
                        <div class="stat-label">Round Totali</div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-number"><?php echo count(array_filter($rounds, fn($r) => $r['status'] === 'closed')); ?></div>
                        <div class="stat-label">Round Completati</div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-number"><?php echo count(array_filter($rounds, fn($r) => $r['status'] === 'pending')); ?></div>
                        <div class="stat-label">Round In Attesa</div>
                    </div>
                </div>
                
                <!-- Classifica Finale -->
                <?php if (count($rounds) > 0 && count(array_filter($rounds, fn($r) => $r['status'] === 'closed')) > 0): ?>
                <div class="info-box" style="background: #fff; border-color: #4A90E2;">
                    <h3>🏆 Classifica Finale</h3>
                    <p style="color: #666;">Classifica aggregata di tutti i round completati</p>
                    <!-- TODO: Implementare logica classifica finale -->
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tab: Impostazioni -->
        <div id="tab-settings" class="tab-content">
            <div class="admin-section">
                <h2>Impostazioni</h2>
                <p>Sezione per configurare timer, numero massimo round, opzioni generali, ecc.</p>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        function showTab(tabName, element) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.getElementById('tab-' + tabName).classList.add('active');
            element.classList.add('active');
            event.preventDefault();
        }
        
        // Question Sets Management
        function showCreateSetModal() {
            const name = prompt('Inserisci il nome del set di domande:');
            if (!name) return;
            
            const description = prompt('Inserisci una descrizione (opzionale):');
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="create_question_set">
                <input type="hidden" name="set_name" value="${name}">
                <input type="hidden" name="set_description" value="${description || ''}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function editSet(id, name, description) {
            const newName = prompt('Modifica il nome:', name);
            if (!newName) return;
            
            const newDescription = prompt('Modifica la descrizione:', description);
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_question_set">
                <input type="hidden" name="set_id" value="${id}">
                <input type="hidden" name="set_name" value="${newName}">
                <input type="hidden" name="set_description" value="${newDescription || ''}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function deleteSet(id, name) {
            if (!confirm(`⚠️ Sei sicuro di voler eliminare il set "${name}"?\nTutti i round associati verranno eliminati.`)) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_question_set">
                <input type="hidden" name="set_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function viewSet(id) {
            window.location.href = 'admin.php?tab=rounds&set=' + id;
        }
        
        // Search in table view
        function searchSetsTable() {
            const input = document.getElementById('search-sets').value.toLowerCase();
            const rows = document.querySelectorAll('#sets-table-body tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }
        
        // Search on Enter key
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-sets');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchSetsTable();
                    }
                });
            }
        });
        
        function viewSet(id) {
            window.location.href = 'admin.php?tab=rounds&set=' + id;
        }
        
        // Test Games Management
        let testGames = [];
        
        function createTestGame() {
            const gameName = prompt('Inserisci un nome per la partita test:', 'Test ' + new Date().toLocaleDateString());
            if (!gameName) return;
            
            const testGame = {
                id: Date.now(),
                name: gameName,
                created: new Date().toLocaleString('it-IT'),
                rounds: <?php echo count($rounds); ?>,
                status: 'attiva'
            };
            
            testGames.push(testGame);
            localStorage.setItem('testGames', JSON.stringify(testGames));
            
            alert('✅ Partita test "' + gameName + '" creata con successo!');
            viewTestGames();
        }
        
        function viewTestGames() {
            const savedGames = localStorage.getItem('testGames');
            if (savedGames) {
                testGames = JSON.parse(savedGames);
            }
            
            const listDiv = document.getElementById('test-games-list');
            const container = document.getElementById('test-games-container');
            
            if (testGames.length === 0) {
                listDiv.style.display = 'none';
                alert('ℹ️ Nessuna partita test disponibile.');
                return;
            }
            
            listDiv.style.display = 'block';
            container.innerHTML = '';
            
            testGames.forEach((game) => {
                const gameCard = document.createElement('div');
                gameCard.className = 'test-game-card';
                gameCard.innerHTML = `
                    <div class="test-game-info">
                        <h4>🎮 ${game.name}</h4>
                        <p>📅 ${game.created} | 🎯 Round: ${game.rounds} | <span style="color: #27ae60; font-weight: bold;">${game.status}</span></p>
                    </div>
                    <div class="test-game-actions">
                        <button class="btn btn-primary" onclick="playTestGame(${game.id})">▶️ Gioca</button>
                        <button class="btn btn-secondary" onclick="viewTestGameStats(${game.id})">📊 Stats</button>
                        <button class="btn btn-danger" onclick="deleteTestGame(${game.id})">🗑️</button>
                    </div>
                `;
                container.appendChild(gameCard);
            });
        }
        
        function playTestGame(gameId) {
            const game = testGames.find(g => g.id === gameId);
            if (!game) {
                alert('❌ Partita test non trovata!');
                return;
            }
            
            alert('🎮 Avvio partita test: ' + game.name + '\n\nIn modalità test, i risultati non verranno salvati nel database principale.');
            // TODO: Implementare logica per giocare partita test
            window.open('player.php?test=1&game=' + gameId, '_blank');
        }
        
        function viewTestGameStats(gameId) {
            const game = testGames.find(g => g.id === gameId);
            if (!game) {
                alert('❌ Partita test non trovata!');
                return;
            }
            
            alert('📊 Statistiche partita: ' + game.name + '\n\nFunzionalità in sviluppo...');
            // TODO: Mostrare statistiche dettagliate
        }
        
        function deleteTestGame(gameId) {
            if (!confirm('⚠️ Sei sicuro di voler eliminare questa partita test?')) return;
            
            testGames = testGames.filter(g => g.id !== gameId);
            localStorage.setItem('testGames', JSON.stringify(testGames));
            
            alert('✅ Partita test eliminata!');
            viewTestGames();
        }
        
        function deleteAllTestGames() {
            if (!confirm('⚠️ ATTENZIONE: Vuoi eliminare TUTTE le partite test?\nQuesta azione non può essere annullata!')) return;
            
            testGames = [];
            localStorage.removeItem('testGames');
            
            alert('✅ Tutte le partite test sono state eliminate!');
            document.getElementById('test-games-list').style.display = 'none';
        }
        
        // Round data from PHP
        const existingRounds = <?php echo json_encode($rounds); ?>;
        
        // Load saved round into form
        function loadSavedRound() {
            const select = document.getElementById('saved_rounds');
            const selectedOption = select.options[select.selectedIndex];
            
            if (!selectedOption.value) return;
            
            const roundData = JSON.parse(selectedOption.getAttribute('data-round'));
            
            // Set number of rounds to 1 to show single round form
            document.getElementById('num_rounds').value = '1';
            generateRoundForms();
            
            // Hide the bulk save button and show individual save
            document.getElementById('save-button-container').style.display = 'none';
            
            // Wait for form to be generated, then populate with data
            setTimeout(() => {
                const roundNum = 1; // Always use 1 for editing
                
                // Set round type
                document.getElementById(`round_type_${roundNum}`).value = roundData.round_type;
                updateRoundForm(roundNum);
                
                // Wait for type update, then set values
                setTimeout(() => {
                    // Set question
                    document.getElementById(`question_${roundNum}`).value = roundData.question;
                    
                    if (roundData.round_type !== 'clickfirst') {
                        // Set options
                        document.getElementById(`option1_${roundNum}`).value = roundData.option1 || '';
                        document.getElementById(`option2_${roundNum}`).value = roundData.option2 || '';
                        
                        if (roundData.round_type === 'multiple') {
                            document.getElementById(`option3_${roundNum}`).value = roundData.option3 || '';
                            document.getElementById(`option4_${roundNum}`).value = roundData.option4 || '';
                        }
                        
                        // Set correct answer
                        const correctRadio = document.querySelector(`input[name="correct_answer_${roundNum}"][value="${roundData.correct_answer}"]`);
                        if (correctRadio) correctRadio.checked = true;
                    }
                    
                    // Add individual save button for editing
                    const container = document.getElementById('rounds-container');
                    const editButton = document.createElement('div');
                    editButton.className = 'info-box';
                    editButton.style.cssText = 'background: #fff3e0; border-color: #ff9800; margin-top: 20px;';
                    editButton.innerHTML = `
                        <button type="button" class="btn btn-warning" onclick="saveRound(${roundData.round_number}, ${roundData.id})" style="width: 100%; font-size: 1.05em; padding: 10px;">
                            ✏️ Modifica Round #${roundData.round_number}
                        </button>
                    `;
                    container.appendChild(editButton);
                }, 100);
            }, 100);
        }
        
        // Save single round
        function saveRound(roundNum, roundId) {
            const formRoundNum = 1; // Always use 1 for the form fields when editing
            const roundType = document.getElementById(`round_type_${formRoundNum}`).value;
            const question = document.getElementById(`question_${formRoundNum}`).value;
            
            if (!question.trim()) {
                alert('La domanda è obbligatoria!');
                return;
            }
            
            let formData = new FormData();
            formData.append('action', 'save_single_round');
            formData.append('round_number', roundNum);
            formData.append('round_type', roundType);
            formData.append('question', question);
            
            if (roundType !== 'clickfirst') {
                const option1 = document.getElementById(`option1_${formRoundNum}`).value;
                const option2 = document.getElementById(`option2_${formRoundNum}`).value;
                
                if (!option1.trim() || !option2.trim()) {
                    alert('Le prime due opzioni sono obbligatorie!');
                    return;
                }
                
                formData.append('option1', option1);
                formData.append('option2', option2);
                
                if (roundType === 'multiple') {
                    const option3 = document.getElementById(`option3_${formRoundNum}`).value;
                    const option4 = document.getElementById(`option4_${formRoundNum}`).value;
                    
                    if (!option3.trim() || !option4.trim()) {
                        alert('Tutte e 4 le opzioni sono obbligatorie per la scelta multipla!');
                        return;
                    }
                    
                    formData.append('option3', option3);
                    formData.append('option4', option4);
                } else {
                    formData.append('option3', '');
                    formData.append('option4', '');
                }
                
                const correctAnswer = document.querySelector(`input[name="correct_answer_${formRoundNum}"]:checked`);
                if (!correctAnswer) {
                    alert('Seleziona la risposta corretta!');
                    return;
                }
                formData.append('correct_answer', correctAnswer.value);
            } else {
                formData.append('option1', '');
                formData.append('option2', '');
                formData.append('option3', '');
                formData.append('option4', '');
                formData.append('correct_answer', '0');
            }
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert('✅ Round salvato con successo!');
                location.reload();
            })
            .catch(error => {
                alert('❌ Errore durante il salvataggio: ' + error);
            });
        }
        
        // Generate round forms dynamically
        function generateRoundForms() {
            const numRounds = parseInt(document.getElementById('num_rounds').value);
            const container = document.getElementById('rounds-container');
            const saveButtonContainer = document.getElementById('save-button-container');
            
            container.innerHTML = '';
            
            if (numRounds > 0) {
                saveButtonContainer.style.display = 'block';
                
                for (let i = 1; i <= numRounds; i++) {
                    const roundDiv = document.createElement('div');
                    roundDiv.className = 'round-form-section';
                    
                    // Find existing round data
                    const existingRound = existingRounds.find(r => r.round_number === i);
                    
                    roundDiv.innerHTML = `
                        <h3>Round ${i}</h3>
                        
                        <div class="form-group">
                            <label for="round_type_${i}">Tipo di Round:</label>
                            <select id="round_type_${i}" name="round_type_${i}" onchange="updateRoundForm(${i})" required>
                                <option value="multiple">Domanda a scelta multipla (4 opzioni)</option>
                                <option value="truefalse">Vero o Falso</option>
                                <option value="clickfirst">Clicca per primo (chi clicca prima vince)</option>
                            </select>
                        </div>
                                
                                <div class="form-group">
                                    <label for="question_${i}">Domanda:</label>
                                    <textarea id="question_${i}" name="question_${i}" rows="2" required placeholder="Inserisci la domanda per questo round"></textarea>
                                </div>
                                
                                <div id="options_container_${i}">
                                    <div class="form-group">
                                        <label for="option1_${i}">Opzione 1:</label>
                                        <input type="text" id="option1_${i}" name="option1_${i}" placeholder="Prima risposta">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="option2_${i}">Opzione 2:</label>
                                        <input type="text" id="option2_${i}" name="option2_${i}" placeholder="Seconda risposta">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="option3_${i}">Opzione 3:</label>
                                        <input type="text" id="option3_${i}" name="option3_${i}" placeholder="Terza risposta">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="option4_${i}">Opzione 4:</label>
                                        <input type="text" id="option4_${i}" name="option4_${i}" placeholder="Quarta risposta">
                                    </div>
                                    
                                    <div class="form-group" id="correct_answer_container_${i}">
                                        <label>Risposta Corretta:</label>
                                        <div class="answer-buttons">
                                            <label class="answer-option">
                                                <input type="radio" name="correct_answer_${i}" value="1">
                                                <span>Opzione 1</span>
                                            </label>
                                            <label class="answer-option">
                                                <input type="radio" name="correct_answer_${i}" value="2">
                                                <span>Opzione 2</span>
                                            </label>
                                            <label class="answer-option">
                                                <input type="radio" name="correct_answer_${i}" value="3">
                                                <span>Opzione 3</span>
                                            </label>
                                            <label class="answer-option">
                                                <input type="radio" name="correct_answer_${i}" value="4">
                                                <span>Opzione 4</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                    `;
                    container.appendChild(roundDiv);
                }
            } else {
                saveButtonContainer.style.display = 'none';
            }
        }
        
        function updateRoundForm(roundNum) {
            const roundType = document.getElementById(`round_type_${roundNum}`).value;
            const container = document.getElementById(`options_container_${roundNum}`);
            
            if (roundType === 'clickfirst') {
                // Clicca per primo - nascondi tutto
                container.innerHTML = `
                    <div class="info-box warning">
                        <p style="margin: 0; font-weight: 600;">⚡ Modalità "Clicca per primo": Il primo giocatore che clicca vince!</p>
                        <p style="margin: 5px 0 0 0; font-size: 0.9em;">Non serve impostare opzioni o risposta corretta.</p>
                    </div>
                `;
            } else if (roundType === 'truefalse') {
                // Vero o Falso - 2 opzioni
                container.innerHTML = `
                    <div class="form-group">
                        <label for="option1_${roundNum}">Opzione Vero:</label>
                        <input type="text" id="option1_${roundNum}" name="option1_${roundNum}" value="Vero" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="option2_${roundNum}">Opzione Falso:</label>
                        <input type="text" id="option2_${roundNum}" name="option2_${roundNum}" value="Falso" required>
                    </div>
                    
                    <input type="hidden" id="option3_${roundNum}" name="option3_${roundNum}" value="">
                    <input type="hidden" id="option4_${roundNum}" name="option4_${roundNum}" value="">
                    
                    <div class="form-group">
                        <label>Risposta Corretta:</label>
                        <div class="answer-buttons">
                            <label class="answer-option">
                                <input type="radio" name="correct_answer_${roundNum}" value="1" required>
                                <span>Vero</span>
                            </label>
                            <label class="answer-option">
                                <input type="radio" name="correct_answer_${roundNum}" value="2">
                                <span>Falso</span>
                            </label>
                        </div>
                    </div>
                `;
            } else {
                // Scelta multipla - 4 opzioni
                container.innerHTML = `
                    <div class="form-group">
                        <label for="option1_${roundNum}">Opzione 1:</label>
                        <input type="text" id="option1_${roundNum}" name="option1_${roundNum}" required placeholder="Prima risposta">
                    </div>
                    
                    <div class="form-group">
                        <label for="option2_${roundNum}">Opzione 2:</label>
                        <input type="text" id="option2_${roundNum}" name="option2_${roundNum}" required placeholder="Seconda risposta">
                    </div>
                    
                    <div class="form-group">
                        <label for="option3_${roundNum}">Opzione 3:</label>
                        <input type="text" id="option3_${roundNum}" name="option3_${roundNum}" required placeholder="Terza risposta">
                    </div>
                    
                    <div class="form-group">
                        <label for="option4_${roundNum}">Opzione 4:</label>
                        <input type="text" id="option4_${roundNum}" name="option4_${roundNum}" required placeholder="Quarta risposta">
                    </div>
                    
                    <div class="form-group">
                        <label>Risposta Corretta:</label>
                        <div class="answer-buttons">
                            <label class="answer-option">
                                <input type="radio" name="correct_answer_${roundNum}" value="1" required>
                                <span>Opzione 1</span>
                            </label>
                            <label class="answer-option">
                                <input type="radio" name="correct_answer_${roundNum}" value="2">
                                <span>Opzione 2</span>
                            </label>
                            <label class="answer-option">
                                <input type="radio" name="correct_answer_${roundNum}" value="3">
                                <span>Opzione 3</span>
                            </label>
                            <label class="answer-option">
                                <input type="radio" name="correct_answer_${roundNum}" value="4">
                                <span>Opzione 4</span>
                            </label>
                        </div>
                    </div>
                `;
            }
        }
        
        // Add Question Modal
        function showAddQuestionModal(setId, setName) {
            document.getElementById('add-question-set-id').value = setId;
            document.getElementById('add-question-set-name').textContent = setName;
            document.getElementById('add-question-modal').style.display = 'flex';
            document.getElementById('question-type-select').value = 'multiple';
            updateQuestionFormFields();
        }
        
        function closeAddQuestionModal() {
            document.getElementById('add-question-modal').style.display = 'none';
            document.getElementById('add-question-form').reset();
        }
        
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('add-question-modal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeAddQuestionModal();
                    }
                });
            }
        });
        
        function updateQuestionFormFields() {
            const questionType = document.getElementById('question-type-select').value;
            const multipleOptions = document.getElementById('multiple-choice-options');
            const trueFalseOptions = document.getElementById('true-false-options');
            const correctAnswerSection = document.getElementById('correct-answer-section');
            
            // Hide all
            multipleOptions.style.display = 'none';
            trueFalseOptions.style.display = 'none';
            correctAnswerSection.style.display = 'none';
            
            if (questionType === 'multiple') {
                multipleOptions.style.display = 'block';
                correctAnswerSection.style.display = 'block';
                document.getElementById('correct-answer-label').textContent = 'Risposta Corretta (1-4):';
                // Update select options
                document.getElementById('correct-answer').innerHTML = `
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                `;
            } else if (questionType === 'truefalse') {
                trueFalseOptions.style.display = 'block';
                correctAnswerSection.style.display = 'block';
                document.getElementById('correct-answer-label').textContent = 'Risposta Corretta:';
                // Set default values for True/False
                document.getElementById('option-tf-1').value = 'Vero';
                document.getElementById('option-tf-2').value = 'Falso';
                // Update select options
                document.getElementById('correct-answer').innerHTML = `
                    <option value="1">1 - Vero</option>
                    <option value="2">2 - Falso</option>
                `;
            }
            // clickfirst doesn't need options or correct answer
        }
        
        // Add Question Popup Functions
        function showAddQuestionPopup() {
            document.getElementById('add-question-popup').style.display = 'flex';
            updatePopupQuestionFormFields();
        }
        
        function closeAddQuestionPopup() {
            document.getElementById('add-question-popup').style.display = 'none';
            document.getElementById('add-question-popup-form').reset();
        }
        
        function updatePopupQuestionFormFields() {
            const questionType = document.getElementById('popup-question-type').value;
            const multipleOptions = document.getElementById('popup-multiple-choice-options');
            const correctAnswerSelect = document.getElementById('popup-correct-answer');
            
            if (questionType === 'multiple') {
                multipleOptions.style.display = 'block';
                correctAnswerSelect.parentElement.style.display = 'block';
                correctAnswerSelect.innerHTML = `
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                `;
            } else if (questionType === 'truefalse') {
                multipleOptions.style.display = 'block';
                // Set Vero/Falso values
                document.getElementById('popup-option-1').value = 'Vero';
                document.getElementById('popup-option-2').value = 'Falso';
                document.getElementById('popup-option-3').value = '';
                document.getElementById('popup-option-4').value = '';
                document.getElementById('popup-option-3').parentElement.style.display = 'none';
                document.getElementById('popup-option-4').parentElement.style.display = 'none';
                
                correctAnswerSelect.parentElement.style.display = 'block';
                correctAnswerSelect.innerHTML = `
                    <option value="1">1 - Vero</option>
                    <option value="2">2 - Falso</option>
                `;
            } else if (questionType === 'clickfirst') {
                multipleOptions.style.display = 'none';
                correctAnswerSelect.parentElement.style.display = 'none';
            }
        }
        
        // Confirm Modal
        let confirmCallback = null;
        
        function confirmAddQuestion() {
            const setSelect = document.getElementById('popup-question-set-id');
            const setName = setSelect.options[setSelect.selectedIndex].text;
            const questionText = document.getElementById('popup-question-text').value;
            
            if (!setSelect.value || !questionText) {
                alert('Compila tutti i campi obbligatori');
                return;
            }
            
            const message = `Vuoi aggiungere questa domanda al set "${setName}"?`;
            showConfirmModal(message, function() {
                document.getElementById('add-question-popup-form').submit();
            });
        }
        
        function showConfirmModal(message, callback) {
            document.getElementById('confirm-message').textContent = message;
            document.getElementById('confirm-modal').style.display = 'flex';
            confirmCallback = callback;
        }
        
        function closeConfirmModal() {
            document.getElementById('confirm-modal').style.display = 'none';
            confirmCallback = null;
        }
        
        function executeConfirmedAction() {
            if (confirmCallback) {
                confirmCallback();
            }
            closeConfirmModal();
        }
    </script>
    
    <!-- Add Question Modal -->
    <div id="add-question-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Aggiungi Domanda</h2>
                <button onclick="closeAddQuestionModal()" class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <div style="background: #f0f8ff; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9em;">
                    <strong>Set:</strong> <span id="add-question-set-name"></span>
                </div>
                
                <form id="add-question-form" method="POST" action="">
                    <input type="hidden" name="action" value="add_question">
                    <input type="hidden" id="add-question-set-id" name="question_set_id">
                    
                    <div class="form-group">
                        <label for="question-type-select">Tipo:</label>
                        <select id="question-type-select" name="round_type" onchange="updateQuestionFormFields()" required>
                            <option value="multiple">Scelta Multipla (4 opzioni)</option>
                            <option value="truefalse">Vero/Falso</option>
                            <option value="clickfirst">Clicca per Primo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="question-text">Domanda:</label>
                        <textarea id="question-text" name="question" rows="3" required placeholder="Inserisci la domanda..."></textarea>
                    </div>
                    
                    <!-- Multiple Choice Options -->
                    <div id="multiple-choice-options">
                        <div class="form-group">
                            <label for="option-1">Opzione 1:</label>
                            <input type="text" id="option-1" name="option1" placeholder="Prima risposta">
                        </div>
                        <div class="form-group">
                            <label for="option-2">Opzione 2:</label>
                            <input type="text" id="option-2" name="option2" placeholder="Seconda risposta">
                        </div>
                        <div class="form-group">
                            <label for="option-3">Opzione 3:</label>
                            <input type="text" id="option-3" name="option3" placeholder="Terza risposta">
                        </div>
                        <div class="form-group">
                            <label for="option-4">Opzione 4:</label>
                            <input type="text" id="option-4" name="option4" placeholder="Quarta risposta">
                        </div>
                    </div>
                    
                    <!-- True/False Options -->
                    <div id="true-false-options" style="display: none;">
                        <div class="form-group">
                            <label for="option-tf-1">Opzione 1 (Vero):</label>
                            <input type="text" id="option-tf-1" name="option1" value="Vero" placeholder="Vero">
                        </div>
                        <div class="form-group">
                            <label for="option-tf-2">Opzione 2 (Falso):</label>
                            <input type="text" id="option-tf-2" name="option2" value="Falso" placeholder="Falso">
                        </div>
                        <input type="hidden" name="option3" value="">
                        <input type="hidden" name="option4" value="">
                    </div>
                    
                    <div id="correct-answer-section" class="form-group">
                        <label id="correct-answer-label" for="correct-answer">Risposta Corretta (1-4):</label>
                        <select id="correct-answer" name="correct_answer">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" onclick="closeAddQuestionModal()" class="btn btn-secondary">Annulla</button>
                        <button type="submit" class="btn btn-success">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Question Popup (Simplified) -->
    <div id="add-question-popup" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Aggiungi Domanda</h2>
                <button onclick="closeAddQuestionPopup()" class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="add-question-popup-form" method="POST" action="">
                    <input type="hidden" name="action" value="add_question">
                    
                    <div class="form-group">
                        <label for="popup-question-set-id">Set di Domande:</label>
                        <select id="popup-question-set-id" name="question_set_id" required>
                            <option value="">-- Seleziona Set --</option>
                            <?php foreach ($questionSets as $set): ?>
                                <option value="<?php echo $set['id']; ?>"><?php echo htmlspecialchars($set['set_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="popup-question-type">Tipo:</label>
                        <select id="popup-question-type" name="round_type" onchange="updatePopupQuestionFormFields()" required>
                            <option value="multiple">Scelta Multipla (4 opzioni)</option>
                            <option value="truefalse">Vero/Falso</option>
                            <option value="clickfirst">Clicca per Primo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="popup-question-text">Domanda:</label>
                        <textarea id="popup-question-text" name="question" rows="3" required placeholder="Inserisci la domanda..."></textarea>
                    </div>
                    
                    <!-- Multiple Choice Options -->
                    <div id="popup-multiple-choice-options">
                        <div class="form-group">
                            <label for="popup-option-1">Opzione 1:</label>
                            <input type="text" id="popup-option-1" name="option1" placeholder="Prima risposta">
                        </div>
                        <div class="form-group">
                            <label for="popup-option-2">Opzione 2:</label>
                            <input type="text" id="popup-option-2" name="option2" placeholder="Seconda risposta">
                        </div>
                        <div class="form-group">
                            <label for="popup-option-3">Opzione 3:</label>
                            <input type="text" id="popup-option-3" name="option3" placeholder="Terza risposta">
                        </div>
                        <div class="form-group">
                            <label for="popup-option-4">Opzione 4:</label>
                            <input type="text" id="popup-option-4" name="option4" placeholder="Quarta risposta">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="popup-correct-answer">Risposta Corretta:</label>
                        <select id="popup-correct-answer" name="correct_answer" required>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" onclick="closeAddQuestionPopup()" class="btn btn-secondary">Annulla</button>
                        <button type="button" onclick="confirmAddQuestion()" class="btn btn-success">Aggiungi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div id="confirm-modal" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Conferma</h2>
                <button onclick="closeConfirmModal()" class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <p id="confirm-message" style="text-align: center; font-size: 1.1em;"></p>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeConfirmModal()" class="btn btn-secondary">Annulla</button>
                    <button type="button" onclick="executeConfirmedAction()" class="btn btn-success">Conferma</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

