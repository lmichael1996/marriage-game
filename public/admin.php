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
    
    if ($action === 'create_set_with_questions') {
        $setName = $_POST['set_name'] ?? '';
        $setDescription = $_POST['set_description'] ?? '';
        
        // Debug log
        error_log("Creating set: $setName with " . ($_POST['num_questions'] ?? 0) . " questions");
        
        if ($setName) {
            // Create the question set
            $setId = $questionSetModel->create($setName, $setDescription);
            
            if (!$setId) {
                die("Errore nella creazione del set");
            }
            
            // Add questions if provided
            $numQuestions = intval($_POST['num_questions'] ?? 0);
            $conn = getDBConnection();
            
            for ($i = 1; $i <= $numQuestions; $i++) {
                $question = $_POST["question_$i"] ?? '';
                $type = $_POST["type_$i"] ?? 'multiple';
                $timer = intval($_POST["timer_$i"] ?? 30);
                
                if (!$question) continue;
                
                $option1 = $_POST["option_{$i}_1"] ?? '';
                $option2 = $_POST["option_{$i}_2"] ?? '';
                $option3 = $_POST["option_{$i}_3"] ?? '';
                $option4 = $_POST["option_{$i}_4"] ?? '';
                $correct = intval($_POST["correct_$i"] ?? 1);
                
                // Auto-set options for true/false
                if ($type === 'truefalse') {
                    $option1 = 'Vero';
                    $option2 = 'Falso';
                    $option3 = '';
                    $option4 = '';
                }
                
                // For clickfirst, no correct answer needed and no timer
                if ($type === 'clickfirst') {
                    $correct = null;
                    $option1 = $option2 = $option3 = $option4 = '';
                    $timer = null;
                }
                
                // Insert round with question_set_id and timer
                $stmt = $conn->prepare("INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iissssssii", $setId, $i, $type, $question, $option1, $option2, $option3, $option4, $correct, $timer);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->close();
            
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
    
    if ($action === 'update_set_with_questions') {
        $setId = intval($_POST['set_id'] ?? 0);
        $setName = $_POST['set_name'] ?? '';
        $setDescription = $_POST['set_description'] ?? '';
        
        if ($setId && $setName) {
            // Update set name and description
            $questionSetModel->update($setId, $setName, $setDescription);
            
            // Delete existing rounds for this set
            $conn = getDBConnection();
            $stmt = $conn->prepare("DELETE FROM rounds WHERE question_set_id = ?");
            $stmt->bind_param("i", $setId);
            $stmt->execute();
            $stmt->close();
            
            // Add new questions
            $numQuestions = intval($_POST['num_questions'] ?? 0);
            for ($i = 1; $i <= $numQuestions; $i++) {
                $question = $_POST["question_$i"] ?? '';
                $type = $_POST["type_$i"] ?? 'multiple';
                $timer = intval($_POST["timer_$i"] ?? 30);
                
                if (!$question) continue;
                
                $option1 = $_POST["option_{$i}_1"] ?? '';
                $option2 = $_POST["option_{$i}_2"] ?? '';
                $option3 = $_POST["option_{$i}_3"] ?? '';
                $option4 = $_POST["option_{$i}_4"] ?? '';
                $correct = intval($_POST["correct_$i"] ?? 1);
                
                // Auto-set options for true/false
                if ($type === 'truefalse') {
                    $option1 = 'Vero';
                    $option2 = 'Falso';
                    $option3 = '';
                    $option4 = '';
                }
                
                // For clickfirst, no timer
                if ($type === 'clickfirst') {
                    $correct = null;
                    $option1 = $option2 = $option3 = $option4 = '';
                    $timer = null;
                }
                
                $stmt = $conn->prepare("INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("iissssssii", $setId, $i, $type, $question, $option1, $option2, $option3, $option4, $correct, $timer);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->close();
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

// Handle GET requests for AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'get_set_questions') {
        $setId = intval($_GET['set_id'] ?? 0);
        if ($setId) {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT * FROM rounds WHERE question_set_id = ? ORDER BY round_number");
            $stmt->bind_param("i", $setId);
            $stmt->execute();
            $result = $stmt->get_result();
            $questions = [];
            while ($row = $result->fetch_assoc()) {
                $questions[] = $row;
            }
            $stmt->close();
            $conn->close();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'questions' => $questions]);
            exit;
        }
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
                <p class="clickfirst-warning">⚡ Modalità: Il primo che clicca vince!</p>
                <?php endif; ?>
                
                <form method="POST" action="" class="inline-form">
                    <input type="hidden" name="action" value="close_round">
                    <input type="hidden" name="round_id" value="<?php echo $active_round['id']; ?>">
                    <button type="submit" class="btn btn-danger">Chiudi Round</button>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Tab: Set di Domande -->
        <div id="tab-sets" class="tab-content active">
            <div class="admin-section">
                <div class="header-actions">
                    <h2>Set di Domande</h2>
                    <button class="btn btn-success" onclick="showCreateSetModal()">+ Nuovo Set</button>
                </div>
                
                <!-- Search Bar -->
                <div class="search-bar">
                    <input type="text" id="search-sets" placeholder="Cerca set...">
                    <select id="search-criteria">
                        <option value="contains">Contiene</option>
                        <option value="exact">Esattamente</option>
                        <option value="starts">Inizia con</option>
                        <option value="ends">Finisce con</option>
                    </select>
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
                                <th>Ultima Modifica</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="sets-table-body">
                            <?php foreach ($questionSets as $set): ?>
                            <tr data-set-id="<?php echo $set['id']; ?>">
                                <td><strong><?php echo htmlspecialchars($set['set_name']); ?></strong></td>
                                <td><?php echo $set['set_description'] ? htmlspecialchars($set['set_description']) : '<em class="empty-description">Nessuna</em>'; ?></td>
                                <td><span class="badge-small"><?php echo $set['total_rounds']; ?></span></td>
                                <td><?php echo isset($set['updated_at']) ? date('d/m/Y H:i', strtotime($set['updated_at'])) : '-'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon btn-warning" onclick="editSet(<?php echo $set['id']; ?>, '<?php echo addslashes($set['set_name']); ?>', '<?php echo addslashes($set['set_description']); ?>')" title="Modifica">✎</button>
                                        <button class="btn-icon btn-danger" onclick="deleteSet(<?php echo $set['id']; ?>, '<?php echo addslashes($set['set_name']); ?>')" title="Elimina">×</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (empty($questionSets)): ?>
                    <div class="info-box loading-text">
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
                <h2>Gestione Partita</h2>
                
                <!-- Step 1: Select Question Set -->
                <div class="game-step">
                    <h3>1. Seleziona Set di Domande</h3>
                    
                    <!-- Search Bar -->
                    <div class="search-bar">
                        <input type="text" id="search-game-sets" placeholder="Cerca set...">
                        <select id="search-game-criteria">
                            <option value="contains">Contiene</option>
                            <option value="exact">Esattamente</option>
                            <option value="starts">Inizia con</option>
                            <option value="ends">Finisce con</option>
                        </select>
                        <button class="btn btn-secondary" onclick="searchGameSets()">Cerca</button>
                    </div>
                    
                    <!-- Table View -->
                    <div id="game-table-view">
                        <table class="sets-table">
                            <thead>
                                <tr>
                                    <th>Nome Set</th>
                                    <th>Descrizione</th>
                                    <th>N° Domande</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody id="game-sets-table-body">
                                <?php foreach ($questionSets as $set): ?>
                                    <?php 
                                        $setRounds = array_filter($rounds, fn($r) => $r['question_set_id'] == $set['id']);
                                        $questionCount = count($setRounds);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($set['set_name']); ?></td>
                                        <td><?php echo htmlspecialchars($set['set_description'] ?? '-'); ?></td>
                                        <td><?php echo $questionCount; ?></td>
                                        <td>
                                            <button class="btn btn-primary" onclick="selectGameSet(<?php echo $set['id']; ?>, '<?php echo addslashes($set['set_name']); ?>')">
                                                Seleziona
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Step 2: Create Room -->
                <div class="game-step" id="step-room" style="display: none;">
                    <h3>2. Avvia Stanza</h3>
                    <p>Crea una stanza per permettere ai giocatori di connettersi.</p>
                    <div id="set-selected-info" style="margin-bottom: 15px;">
                        <p><strong>Set selezionato:</strong> <span id="selected-set-display"></span></p>
                    </div>
                    <div id="room-control">
                        <button class="btn btn-success" onclick="createRoom()" id="create-room-btn">
                            Avvia Stanza
                        </button>
                        <div id="room-info" style="display: none; margin-top: 15px;">
                            <div class="info-box">
                                <p><strong>🎮 Stanza attiva</strong></p>
                                <p>Codice stanza: <strong><span id="room-code">------</span></strong></p>
                                <p>I giocatori possono ora connettersi utilizzando questo codice.</p>
                            </div>
                            <button class="btn btn-danger" onclick="closeRoom()" id="close-room-btn">
                                Chiudi Stanza
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Connected Devices -->
                <div class="game-step" id="step-devices" style="display: none;">
                    <h3>3. Dispositivi Connessi</h3>
                    <p>Attendi che i giocatori si connettano.</p>
                    <div id="connected-devices-view">
                        <table class="sets-table">
                            <thead>
                                <tr>
                                    <th>Giocatore</th>
                                    <th>Stato</th>
                                    <th>Connesso da</th>
                                </tr>
                            </thead>
                            <tbody id="connected-devices-body">
                                <tr>
                                    <td colspan="3" class="loading-text">
                                        Caricamento dispositivi connessi...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Start Game Section -->
                    <div id="game-start-section" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #ddd;">
                        <div id="final-info">
                            <p><strong>Set selezionato:</strong> <span id="selected-set-name-final"></span></p>
                            <p><strong>Giocatori connessi:</strong> <span id="connected-count">0</span></p>
                            <button class="btn btn-success" onclick="startGame()" id="start-game-btn" disabled style="margin-top: 15px;">
                                Avvia Partita
                            </button>
                        </div>
                    </div>
                </div>
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
        function editSet(id, name, description) {
            // Carica il set con le sue domande nel modal
            fetch(`admin.php?action=get_set_questions&set_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showEditSetModal(id, name, description, data.questions);
                    } else {
                        alert('Errore nel caricamento delle domande');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Errore nel caricamento del set');
                });
        }
        
        function showEditSetModal(setId, name, description, questions) {
            document.getElementById('create-set-form').action = '';
            document.querySelector('input[name="action"]').value = 'update_set_with_questions';
            
            // Add hidden field for set_id
            let setIdInput = document.getElementById('edit-set-id');
            if (!setIdInput) {
                setIdInput = document.createElement('input');
                setIdInput.type = 'hidden';
                setIdInput.id = 'edit-set-id';
                setIdInput.name = 'set_id';
                document.getElementById('create-set-form').appendChild(setIdInput);
            }
            setIdInput.value = setId;
            
            // Fill form
            document.getElementById('set-name').value = name;
            document.getElementById('set-description').value = description || '';
            document.getElementById('num-questions').value = questions.length;
            
            // Generate fields and fill with data
            generateQuestionFields();
            
            setTimeout(() => {
                questions.forEach((q, idx) => {
                    const i = idx + 1;
                    const typeSelect = document.querySelector(`select[name="type_${i}"]`);
                    const questionTextarea = document.querySelector(`textarea[name="question_${i}"]`);
                    
                    if (typeSelect) typeSelect.value = q.round_type;
                    if (questionTextarea) questionTextarea.value = q.question;
                    
                    updateQuestionType(i, q.round_type);
                    
                    setTimeout(() => {
                        if (q.round_type === 'multiple') {
                            const timer = document.querySelector(`input[name="timer_${i}"]`);
                            const opt1 = document.querySelector(`input[name="option_${i}_1"]`);
                            const opt2 = document.querySelector(`input[name="option_${i}_2"]`);
                            const opt3 = document.querySelector(`input[name="option_${i}_3"]`);
                            const opt4 = document.querySelector(`input[name="option_${i}_4"]`);
                            const correct = document.querySelector(`select[name="correct_${i}"]`);
                            
                            if (timer) timer.value = q.timer || 30;
                            if (opt1) opt1.value = q.option1 || '';
                            if (opt2) opt2.value = q.option2 || '';
                            if (opt3) opt3.value = q.option3 || '';
                            if (opt4) opt4.value = q.option4 || '';
                            if (correct) correct.value = q.correct_answer;
                        } else if (q.round_type === 'truefalse') {
                            const timer = document.querySelector(`input[name="timer_${i}"]`);
                            const correct = document.querySelector(`select[name="correct_${i}"]`);
                            if (timer) timer.value = q.timer || 30;
                            if (correct) correct.value = q.correct_answer;
                        }
                    }, 50);
                });
            }, 100);
            
            document.querySelector('.modal-header h2').textContent = 'Modifica Set di Domande';
            document.querySelector('.modal-actions .btn-success').textContent = 'Salva Modifiche';
            document.getElementById('create-set-modal').style.display = 'flex';
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
        
        // Search in table view
        function searchSetsTable() {
            const input = document.getElementById('search-sets').value.toLowerCase();
            const criteria = document.getElementById('search-criteria').value;
            const rows = document.querySelectorAll('#sets-table-body tr');
            
            // Remove previous highlights
            rows.forEach(row => {
                row.querySelectorAll('td').forEach(td => {
                    if (td.dataset.originalText) {
                        td.innerHTML = td.dataset.originalText;
                    }
                });
            });
            
            if (!input) {
                rows.forEach(row => row.style.display = '');
                return;
            }
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                let matches = false;
                
                switch(criteria) {
                    case 'exact':
                        // Cerca esattamente nel nome del set (prima colonna)
                        const setName = row.cells[0].textContent.toLowerCase().trim();
                        matches = setName === input;
                        break;
                    case 'starts':
                        matches = text.startsWith(input);
                        break;
                    case 'ends':
                        matches = text.endsWith(input);
                        break;
                    case 'contains':
                    default:
                        matches = text.includes(input);
                        break;
                }
                
                row.style.display = matches ? '' : 'none';
                
                // Highlight matching text
                if (matches && input) {
                    row.querySelectorAll('td').forEach((td, index) => {
                        // Skip action column (last column)
                        if (index === row.cells.length - 1) return;
                        
                        if (!td.dataset.originalText) {
                            td.dataset.originalText = td.innerHTML;
                        }
                        
                        const cellText = td.textContent;
                        const cellTextLower = cellText.toLowerCase();
                        const regex = new RegExp(`(${input.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                        
                        let shouldHighlight = false;
                        switch(criteria) {
                            case 'exact':
                                shouldHighlight = index === 0 && cellTextLower.trim() === input;
                                break;
                            case 'starts':
                                shouldHighlight = cellTextLower.startsWith(input);
                                break;
                            case 'ends':
                                shouldHighlight = cellTextLower.endsWith(input);
                                break;
                            case 'contains':
                            default:
                                shouldHighlight = cellTextLower.includes(input);
                                break;
                        }
                        
                        if (shouldHighlight) {
                            td.innerHTML = cellText.replace(regex, '<mark>$1</mark>');
                        }
                    });
                }
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
            
            const searchGameInput = document.getElementById('search-game-sets');
            if (searchGameInput) {
                searchGameInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchGameSets();
                    }
                });
            }
        });
        
        // Search in game sets table
        function searchGameSets() {
            const input = document.getElementById('search-game-sets').value.toLowerCase();
            const criteria = document.getElementById('search-game-criteria').value;
            const rows = document.querySelectorAll('#game-sets-table-body tr');
            
            // Remove previous highlights
            rows.forEach(row => {
                row.querySelectorAll('td').forEach(td => {
                    if (td.dataset.originalText) {
                        td.innerHTML = td.dataset.originalText;
                    }
                });
            });
            
            if (!input) {
                rows.forEach(row => row.style.display = '');
                return;
            }
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                let matches = false;
                
                switch(criteria) {
                    case 'exact':
                        const setName = row.cells[0].textContent.toLowerCase().trim();
                        matches = setName === input;
                        break;
                    case 'starts':
                        matches = text.startsWith(input);
                        break;
                    case 'ends':
                        matches = text.endsWith(input);
                        break;
                    case 'contains':
                    default:
                        matches = text.includes(input);
                        break;
                }
                
                row.style.display = matches ? '' : 'none';
                
                // Highlight matching text
                if (matches && input) {
                    row.querySelectorAll('td').forEach((td, index) => {
                        // Skip action column (last column)
                        if (index === row.cells.length - 1) return;
                        
                        if (!td.dataset.originalText) {
                            td.dataset.originalText = td.innerHTML;
                        }
                        
                        const cellText = td.textContent;
                        const cellTextLower = cellText.toLowerCase();
                        const regex = new RegExp(`(${input.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                        
                        let shouldHighlight = false;
                        switch(criteria) {
                            case 'exact':
                                shouldHighlight = index === 0 && cellTextLower.trim() === input;
                                break;
                            case 'starts':
                                shouldHighlight = cellTextLower.startsWith(input);
                                break;
                            case 'ends':
                                shouldHighlight = cellTextLower.endsWith(input);
                                break;
                            case 'contains':
                            default:
                                shouldHighlight = cellTextLower.includes(input);
                                break;
                        }
                        
                        if (shouldHighlight) {
                            td.innerHTML = cellText.replace(regex, '<mark>$1</mark>');
                        }
                    });
                }
            });
        }
        
        // Game management variables
        let selectedSetId = null;
        let selectedSetName = '';
        let roomActive = false;
        let roomCode = '';
        
        // Create room
        function createRoom() {
            // Generate random room code
            roomCode = Math.random().toString(36).substring(2, 8).toUpperCase();
            roomActive = true;
            
            // Save room code on server
            fetch('/src/api/create_room.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_code: roomCode
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    document.getElementById('create-room-btn').style.display = 'none';
                    document.getElementById('room-info').style.display = 'block';
                    document.getElementById('room-code').textContent = roomCode;
                    
                    // Show next steps
                    document.getElementById('step-devices').style.display = 'block';
                    
                    // Start device polling
                    if (!devicesInterval) {
                        updateConnectedDevices();
                        devicesInterval = setInterval(updateConnectedDevices, 1000);
                    }
                } else {
                    alert('Errore nella creazione della stanza');
                }
            })
            .catch(error => {
                console.error('Error creating room:', error);
                alert('Errore nella creazione della stanza');
            });
        }
        
        // Close room
        function closeRoom() {
            if (confirm('Vuoi chiudere la stanza? Tutti i giocatori verranno disconnessi.')) {
                // Call API to signal room closure
                fetch('/src/api/close_room.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        roomActive = false;
                        roomCode = '';
                        
                        // Update UI
                        document.getElementById('create-room-btn').style.display = 'inline-block';
                        document.getElementById('room-info').style.display = 'none';
                        
                        // Hide next steps
                        document.getElementById('step-devices').style.display = 'none';
                        
                        // Reset selection
                        selectedSetId = null;
                        selectedSetName = '';
                        
                        // Stop device polling
                        if (devicesInterval) {
                            clearInterval(devicesInterval);
                            devicesInterval = null;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error closing room:', error);
                    alert('Errore nella chiusura della stanza');
                });
            }
        }
        
        // Select game set
        function selectGameSet(setId, setName) {
            selectedSetId = setId;
            selectedSetName = setName;
            
            // Remove previous selection highlight
            document.querySelectorAll('#game-sets-table-body tr').forEach(row => {
                row.classList.remove('selected-row');
            });
            
            // Add selection highlight to clicked row
            event.target.closest('tr').classList.add('selected-row');
            
            // Show step-room section
            document.getElementById('step-room').style.display = 'block';
            document.getElementById('selected-set-name-room').textContent = setName;
            
            // Update final info if visible
            document.getElementById('selected-set-name-final').textContent = setName;
            
            // Enable start button if there are connected devices
            updateStartButton();
        }
        
        // Update start button state
        function updateStartButton() {
            const connectedCount = document.getElementById('connected-count').textContent;
            const startBtn = document.getElementById('start-game-btn');
            
            if (selectedSetId && parseInt(connectedCount) > 0) {
                startBtn.disabled = false;
            } else {
                startBtn.disabled = true;
            }
        }
        
        // Start game with selected set
        function startGame() {
            if (!selectedSetId) {
                alert('Seleziona prima un set di domande');
                return;
            }
            
            const connectedCount = document.getElementById('connected-count').textContent;
            if (parseInt(connectedCount) === 0) {
                alert('Nessun giocatore connesso');
                return;
            }
            
            if (confirm(`Avviare la partita "${selectedSetName}" con ${connectedCount} giocatori?`)) {
                // Load the question set and start the first round
                loadQuestionSet(selectedSetId);
            }
        }
        
        // Load question set and start first round
        function loadQuestionSet(setId) {
            fetch('../src/api/game.php?action=get_set&set_id=' + setId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.rounds && data.rounds.length > 0) {
                        // Start the first round automatically
                        const firstRound = data.rounds[0];
                        startRound(firstRound.id);
                    } else {
                        alert('Nessuna domanda trovata nel set selezionato');
                    }
                })
                .catch(error => {
                    console.error('Error loading question set:', error);
                    alert('Errore nel caricamento del set di domande');
                });
        }
        
        // Start a specific round
        function startRound(roundId) {
            fetch('../src/api/game.php?action=start_round&round_id=' + roundId, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Round started:', roundId);
                    // Optionally redirect to game view or show success message
                    alert('Prima domanda avviata!');
                } else {
                    alert('Errore nell\'avvio della domanda');
                }
            })
            .catch(error => {
                console.error('Error starting round:', error);
                alert('Errore nell\'avvio della domanda');
            });
        }
        
        // Update connected devices table
        function updateConnectedDevices() {
            fetch('../src/api/connected_devices.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('connected-devices-body');
                        const countSpan = document.getElementById('connected-count');
                        
                        // Update count
                        if (countSpan) {
                            countSpan.textContent = data.count;
                            updateStartButton();
                        }
                        
                        if (data.devices.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="3" class="loading-text">
                                        Nessun dispositivo connesso
                                    </td>
                                </tr>
                            `;
                        } else {
                            tbody.innerHTML = data.devices.map(device => `
                                <tr>
                                    <td>${device.username}</td>
                                    <td>
                                        <span class="status-indicator"></span>
                                        ${device.status === 'online' ? 'Online' : 'Offline'}
                                    </td>
                                    <td>${new Date(device.last_seen).toLocaleString('it-IT')}</td>
                                </tr>
                            `).join('');
                        }
                    }
                })
                .catch(error => {
                    console.error('Errore caricamento dispositivi:', error);
                });
        }
        
        // Auto-refresh connected devices
        let devicesInterval;
        document.addEventListener('DOMContentLoaded', function() {
            // Don't start polling automatically - wait for room creation
            
            // Stop refresh when leaving game tab
            const tabs = document.querySelectorAll('.nav-btn');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const target = this.dataset.tab;
                    if (target === 'game') {
                        // Resume refresh when entering game tab (if room is active)
                        if (!devicesInterval && roomActive) {
                            updateConnectedDevices();
                            devicesInterval = setInterval(updateConnectedDevices, 1000);
                        }
                    } else {
                        // Stop refresh when leaving game tab
                        if (devicesInterval) {
                            clearInterval(devicesInterval);
                            devicesInterval = null;
                        }
                    }
                });
            });
        });
        
        // Create Set with Questions Modal
        function showCreateSetModal() {
            // Reset form to create mode
            document.querySelector('input[name="action"]').value = 'create_set_with_questions';
            const setIdInput = document.getElementById('edit-set-id');
            if (setIdInput) setIdInput.remove();
            
            document.querySelector('.modal-header h2').textContent = 'Nuovo Set di Domande';
            document.querySelector('.modal-actions .btn-success').textContent = 'Salva Set';
            
            document.getElementById('create-set-modal').style.display = 'flex';
            generateQuestionFields();
        }
        
        function closeCreateSetModal() {
            document.getElementById('create-set-modal').style.display = 'none';
            document.getElementById('create-set-form').reset();
            document.getElementById('questions-container').innerHTML = '';
            
            // Remove edit-set-id if exists
            const setIdInput = document.getElementById('edit-set-id');
            if (setIdInput) setIdInput.remove();
            
            // Reset to create mode
            document.querySelector('input[name="action"]').value = 'create_set_with_questions';
            document.querySelector('.modal-header h2').textContent = 'Nuovo Set di Domande';
            document.querySelector('.modal-actions .btn-success').textContent = 'Salva Set';
        }
        
        function generateQuestionFields() {
            const num = parseInt(document.getElementById('num-questions').value) || 1;
            const container = document.getElementById('questions-container');
            const currentQuestions = container.querySelectorAll('.question-block');
            const currentCount = currentQuestions.length;
            
            if (num > currentCount) {
                // Aggiungi nuove domande
                for (let i = currentCount + 1; i <= num; i++) {
                    const questionDiv = document.createElement('div');
                    questionDiv.className = 'question-block';
                    questionDiv.innerHTML = `
                        <h3>Domanda ${i}</h3>
                        
                        <div class="form-group">
                            <label>Tipo:</label>
                            <select name="type_${i}" onchange="updateQuestionType(${i}, this.value)">
                                <option value="multiple">Scelta Multipla</option>
                                <option value="truefalse">Vero/Falso</option>
                                <option value="clickfirst">Clicca per Primo</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Domanda:</label>
                            <textarea name="question_${i}" rows="2" placeholder="Inserisci la domanda..." required></textarea>
                        </div>
                        
                        <div id="options-${i}">
                            <div class="form-group">
                                <label>Timer (secondi):</label>
                                <input type="number" name="timer_${i}" min="5" max="120" value="30" placeholder="30">
                            </div>
                            <div class="form-group">
                                <label>Opzione 1:</label>
                                <input type="text" name="option_${i}_1" placeholder="Prima opzione">
                            </div>
                            <div class="form-group">
                                <label>Opzione 2:</label>
                                <input type="text" name="option_${i}_2" placeholder="Seconda opzione">
                            </div>
                            <div class="form-group">
                                <label>Opzione 3:</label>
                                <input type="text" name="option_${i}_3" placeholder="Terza opzione">
                            </div>
                            <div class="form-group">
                                <label>Opzione 4:</label>
                                <input type="text" name="option_${i}_4" placeholder="Quarta opzione">
                            </div>
                            <div class="form-group">
                                <label>Risposta Corretta:</label>
                                <select name="correct_${i}">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                            </div>
                        </div>
                    `;
                    container.appendChild(questionDiv);
                }
            } else if (num < currentCount) {
                // Rimuovi le domande in eccesso
                for (let i = currentCount; i > num; i--) {
                    const lastQuestion = container.querySelector('.question-block:last-child');
                    if (lastQuestion) {
                        lastQuestion.remove();
                    }
                }
            }
        }
        
        function updateQuestionType(index, type) {
            const optionsDiv = document.getElementById(`options-${index}`);
            
            if (type === 'truefalse') {
                optionsDiv.innerHTML = `
                    <div class="form-group">
                        <label>Timer (secondi):</label>
                        <input type="number" name="timer_${index}" min="5" max="120" value="30" placeholder="30">
                    </div>
                    <div class="form-group">
                        <label>Risposta Corretta:</label>
                        <select name="correct_${index}">
                            <option value="1">Vero</option>
                            <option value="2">Falso</option>
                        </select>
                    </div>
                `;
            } else if (type === 'clickfirst') {
                optionsDiv.innerHTML = '<p class="no-options-message">Nessuna opzione necessaria per questo tipo di domanda.</p>';
            } else {
                optionsDiv.innerHTML = `
                    <div class="form-group">
                        <label>Timer (secondi):</label>
                        <input type="number" name="timer_${index}" min="5" max="120" value="30" placeholder="30">
                    </div>
                    <div class="form-group">
                        <label>Opzione 1:</label>
                        <input type="text" name="option_${index}_1" placeholder="Prima opzione">
                    </div>
                    <div class="form-group">
                        <label>Opzione 2:</label>
                        <input type="text" name="option_${index}_2" placeholder="Seconda opzione">
                    </div>
                    <div class="form-group">
                        <label>Opzione 3:</label>
                        <input type="text" name="option_${index}_3" placeholder="Terza opzione">
                    </div>
                    <div class="form-group">
                        <label>Opzione 4:</label>
                        <input type="text" name="option_${index}_4" placeholder="Quarta opzione">
                    </div>
                    <div class="form-group">
                        <label>Risposta Corretta:</label>
                        <select name="correct_${index}">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                `;
            }
        }
    </script>
    
    <!-- Create Set Modal -->
    <div id="create-set-modal" class="modal-overlay">
        <div class="modal-content modal-content-large">
            <div class="modal-header">
                <h2>Nuovo Set di Domande</h2>
                <button onclick="closeCreateSetModal()" class="modal-close">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="create-set-form" method="POST" action="">
                    <input type="hidden" name="action" value="create_set_with_questions">
                    
                    <div class="form-group">
                        <label for="set-name">Nome Set:</label>
                        <input type="text" id="set-name" name="set_name" required placeholder="Es: Cultura Generale">
                    </div>
                    
                    <div class="form-group">
                        <label for="set-description">Descrizione:</label>
                        <textarea id="set-description" name="set_description" rows="2" placeholder="Breve descrizione del set..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="num-questions">Numero di Domande:</label>
                        <input type="number" id="num-questions" name="num_questions" min="1" max="20" value="5" onchange="generateQuestionFields()" oninput="generateQuestionFields()">
                    </div>
                    
                    <div id="questions-container" class="questions-container">
                        <!-- Questions will be generated here -->
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" onclick="closeCreateSetModal()" class="btn btn-secondary">Annulla</button>
                        <button type="submit" class="btn btn-success">Salva Set</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>