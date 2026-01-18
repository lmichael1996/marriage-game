<?php
// Check admin access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['logged_in_via_login']) || !isset($_SESSION['user_id']) || isset($_SESSION['player_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    require_once __DIR__ . '/../src/controllers/AdminController.php';
    require_once __DIR__ . '/../src/controllers/GameController.php';
    
    $admin = new AdminController();
    $game = new GameController();
    
    // Process POST actions
    switch ($action) {
        case 'change_credentials':
        case 'update_credentials':
            $result = $admin->updateCredentials(
                $_SESSION['user_id'],
                $_POST['new_username'] ?? '',
                $_POST['new_password'] ?? null,
                $_POST['confirm_password'] ?? null
            );
            
            if ($result['success']) {
                $_SESSION['username'] = $result['new_username'];
                header('Location: admin.php?tab=settings&success=credentials_updated');
            } else {
                header('Location: admin.php?tab=settings&error=' . urlencode($result['error']));
            }
            exit;
            
        case 'save_settings':
            $result = $admin->saveSettings($_POST);
            
            if ($result['success']) {
                header('Location: admin.php?tab=settings&success=settings_saved');
            } else {
                header('Location: admin.php?tab=settings&error=' . urlencode($result['error']));
            }
            exit;
            
        case 'create_set_with_questions':
            $numQuestions = intval($_POST['num_questions'] ?? 0);
            $questions = [];
            
            for ($i = 1; $i <= $numQuestions; $i++) {
                $question = $_POST["question_$i"] ?? '';
                if (!$question) continue;
                
                $questions[] = [
                    'question' => $question,
                    'type' => $_POST["type_$i"] ?? 'multiple',
                    'timer' => intval($_POST["timer_$i"] ?? 30),
                    'option1' => $_POST["option_{$i}_1"] ?? '',
                    'option2' => $_POST["option_{$i}_2"] ?? '',
                    'option3' => $_POST["option_{$i}_3"] ?? '',
                    'option4' => $_POST["option_{$i}_4"] ?? '',
                    'correct' => intval($_POST["correct_$i"] ?? 1)
                ];
            }
            
            $result = $admin->createSetWithQuestions(
                $_POST['set_name'] ?? '',
                $_POST['set_description'] ?? '',
                $questions
            );
            
            if ($result['success']) {
                header('Location: admin.php?tab=sets&success=created');
            } else {
                header('Location: admin.php?tab=sets&error=' . urlencode($result['error']));
            }
            exit;
            
        case 'update_set_with_questions':
            $setId = intval($_POST['set_id'] ?? 0);
            $numQuestions = intval($_POST['num_questions'] ?? 0);
            $questions = [];
            
            for ($i = 1; $i <= $numQuestions; $i++) {
                $question = $_POST["question_$i"] ?? '';
                if (!$question) continue;
                
                $questions[] = [
                    'question' => $question,
                    'type' => $_POST["type_$i"] ?? 'multiple',
                    'timer' => intval($_POST["timer_$i"] ?? 30),
                    'option1' => $_POST["option_{$i}_1"] ?? '',
                    'option2' => $_POST["option_{$i}_2"] ?? '',
                    'option3' => $_POST["option_{$i}_3"] ?? '',
                    'option4' => $_POST["option_{$i}_4"] ?? '',
                    'correct' => intval($_POST["correct_$i"] ?? 1)
                ];
            }
            
            $result = $admin->updateSetWithQuestions(
                $setId,
                $_POST['set_name'] ?? '',
                $_POST['set_description'] ?? '',
                $questions
            );
            
            if ($result['success']) {
                header('Location: admin.php?tab=sets&success=updated');
            } else {
                header('Location: admin.php?tab=sets&error=' . urlencode($result['error']));
            }
            exit;
            
        case 'delete_question_set':
            $result = $admin->deleteQuestionSet(intval($_POST['set_id'] ?? 0));
            
            if ($result['success']) {
                header('Location: admin.php?tab=sets&success=deleted');
            } else {
                header('Location: admin.php?tab=sets&error=' . urlencode($result['error']));
            }
            exit;
            
        case 'start_round':
            $result = $game->startRound(intval($_POST['round_id'] ?? 0));
            
            if ($result['success']) {
                header('Location: admin.php');
            } else {
                header('Location: admin.php?error=' . urlencode($result['error']));
            }
            exit;
            
        case 'close_round':
            $result = $game->closeRound(intval($_POST['round_id'] ?? 0));
            
            if ($result['success']) {
                header('Location: admin.php');
            } else {
                header('Location: admin.php?error=' . urlencode($result['error']));
            }
            exit;
            
        default:
            header('Location: admin.php?error=invalid_action');
            exit;
    }
}

require_once __DIR__ . '/../src/controllers/AdminController.php';
require_once __DIR__ . '/../src/controllers/GameController.php';

$admin = new AdminController();
$game = new GameController();

// Handle GET requests for AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'get_set_questions') {
        $setId = intval($_GET['set_id'] ?? 0);
        if ($setId) {
            $result = $admin->getSetQuestions($setId);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
    }
}

// Get data
$questionSetsResult = $admin->getAllQuestionSets();
$questionSets = $questionSetsResult['success'] ? $questionSetsResult['sets'] : [];

// TODO: Questi metodi devono essere aggiunti ai controller
$rounds = []; // $roundModel->getAllRoundsWithStats();
$active_round = null; // $roundModel->getActiveRound();

// Handle search
$searchQuery = $_GET['search'] ?? '';
if ($searchQuery) {
    $searchResult = $admin->searchQuestionSets($searchQuery);
    $questionSets = $searchResult['success'] ? $searchResult['sets'] : [];
}

// Get selected set
$selectedSetId = $_GET['set'] ?? null;
$selectedSet = null;
$setRounds = [];
if ($selectedSetId) {
    $setResult = $admin->getQuestionSetById($selectedSetId);
    $selectedSet = $setResult['success'] ? $setResult['set'] : null;
    
    $roundsResult = $admin->getQuestionSetRounds($selectedSetId);
    $setRounds = $roundsResult['success'] ? $roundsResult['rounds'] : [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <script>
        // Define showTab early to avoid "not defined" errors
        function showTab(tabName, element) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.getElementById('tab-' + tabName).classList.add('active');
            if (element) {
                element.classList.add('active');
            }
        }
    </script>
    
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
                            <tr data-set-id="<?php echo $set['id']; ?>" onclick="selectSetRow(this, <?php echo $set['id']; ?>, '<?php echo addslashes($set['set_name']); ?>')">
                                <td><strong><?php echo htmlspecialchars($set['set_name']); ?></strong></td>
                                <td><?php echo $set['set_description'] ? htmlspecialchars($set['set_description']) : '<em class="empty-description">Nessuna</em>'; ?></td>
                                <td><span class="badge-small"><?php echo $set['total_rounds']; ?></span></td>
                                <td><?php echo isset($set['updated_at']) ? date('d/m/Y H:i', strtotime($set['updated_at'])) : '-'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon btn-warning" onclick="event.stopPropagation(); editSet(<?php echo $set['id']; ?>, '<?php echo addslashes($set['set_name']); ?>', '<?php echo addslashes($set['set_description']); ?>')" title="Modifica">✎</button>
                                        <button class="btn-icon btn-danger" onclick="event.stopPropagation(); deleteSet(<?php echo $set['id']; ?>, '<?php echo addslashes($set['set_name']); ?>')" title="Elimina">×</button>
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
                                        $setRounds = array_filter($rounds, function($r) use ($set) {
                                            return $r['question_set_id'] == $set['id'];
                                        });
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
                <h2>Impostazioni Generali</h2>
                
                <?php if (isset($_GET['success']) && $_GET['success'] === 'credentials_updated'): ?>
                    <div class="success-message">Credenziali aggiornate con successo!</div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                
                <!-- Username and Password Section -->
                <form method="POST" action="" style="margin-bottom: 30px;">
                    <input type="hidden" name="action" value="change_credentials">
                    
                    <div class="form-section">
                        <h3>Credenziali Amministratore</h3>
                        
                        <div class="form-group">
                            <label for="new_username">Username:</label>
                            <input type="text" id="new_username" name="new_username" 
                                   value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" 
                                   required minlength="3" maxlength="50">
                            <small>Username per accedere al pannello amministratore</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nuova Password:</label>
                            <input type="password" id="new_password" name="new_password" 
                                   minlength="6" maxlength="255">
                            <small>Lascia vuoto per mantenere la password attuale. Minimo 6 caratteri.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Conferma Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   minlength="6" maxlength="255">
                            <small>Reinserisci la nuova password</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Aggiorna Credenziali</button>
                    </div>
                </form>
                
                <form id="settings-form" method="POST" action="">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <!-- Game Settings -->
                    <div class="form-section">
                        <h3>Impostazioni Partita</h3>
                        <div class="form-group">
                            <label for="min_players">Numero minimo giocatori:</label>
                            <input type="number" id="min_players" name="min_players" 
                                   min="1" max="100" value="2" required>
                            <small>Numero minimo di giocatori per iniziare una partita</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_players">Numero massimo giocatori:</label>
                            <input type="number" id="max_players" name="max_players" 
                                   min="1" max="100" value="50" required>
                            <small>Numero massimo di giocatori in una stanza</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="auto_next_round">
                                <input type="checkbox" id="auto_next_round" name="auto_next_round" value="1">
                                Passa automaticamente al round successivo
                            </label>
                            <small>Avvia automaticamente il round successivo dopo un tempo prestabilito</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="auto_next_delay">Ritardo auto-avanzamento (secondi):</label>
                            <input type="number" id="auto_next_delay" name="auto_next_delay" 
                                   min="3" max="30" value="5" disabled>
                            <small>Tempo di attesa prima del round successivo (se auto-avanzamento attivo)</small>
                        </div>
                    </div>
                    
                    <!-- Scoring Settings -->
                    <div class="form-section">
                        <h3>Punteggi - Risposte Multiple</h3>
                        <p><strong>Sistema punteggi stile Formula 1</strong>: i punti vengono assegnati in base alla posizione in classifica (primi 10).</p>
                        
                        <table class="points-table">
                            <thead>
                                <tr>
                                    <th>Posizione</th>
                                    <th>Punti</th>
                                    <th>Posizione</th>
                                    <th>Punti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><label for="points_mult_1st">1° Posto</label></td>
                                    <td><input type="number" id="points_mult_1st" name="points_mult_1st" min="1" max="1000" value="25" required></td>
                                    <td><label for="points_mult_6th">6° Posto</label></td>
                                    <td><input type="number" id="points_mult_6th" name="points_mult_6th" min="1" max="1000" value="8" required></td>
                                </tr>
                                <tr>
                                    <td><label for="points_mult_2nd">2° Posto</label></td>
                                    <td><input type="number" id="points_mult_2nd" name="points_mult_2nd" min="1" max="1000" value="18" required></td>
                                    <td><label for="points_mult_7th">7° Posto</label></td>
                                    <td><input type="number" id="points_mult_7th" name="points_mult_7th" min="1" max="1000" value="6" required></td>
                                </tr>
                                <tr>
                                    <td><label for="points_mult_3rd">3° Posto</label></td>
                                    <td><input type="number" id="points_mult_3rd" name="points_mult_3rd" min="1" max="1000" value="15" required></td>
                                    <td><label for="points_mult_8th">8° Posto</label></td>
                                    <td><input type="number" id="points_mult_8th" name="points_mult_8th" min="1" max="1000" value="4" required></td>
                                </tr>
                                <tr>
                                    <td><label for="points_mult_4th">4° Posto</label></td>
                                    <td><input type="number" id="points_mult_4th" name="points_mult_4th" min="1" max="1000" value="12" required></td>
                                    <td><label for="points_mult_9th">9° Posto</label></td>
                                    <td><input type="number" id="points_mult_9th" name="points_mult_9th" min="1" max="1000" value="2" required></td>
                                </tr>
                                <tr>
                                    <td><label for="points_mult_5th">5° Posto</label></td>
                                    <td><input type="number" id="points_mult_5th" name="points_mult_5th" min="1" max="1000" value="10" required></td>
                                    <td><label for="points_mult_10th">10° Posto</label></td>
                                    <td><input type="number" id="points_mult_10th" name="points_mult_10th" min="1" max="1000" value="1" required></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-section">
                        <h3>Punteggi - Vero o Falso</h3>
                        <p><strong>Primi 10 classificati</strong> (più facile, punteggi ridotti).</p>
                        
                        <table class="points-table">
                            <thead>
                                <tr>
                                    <th>Posizione</th>
                                    <th>Punti</th>
                                    <th>Posizione</th>
                                    <th>Punti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><label for="points_tf_1st">1° Posto</label></td>
                                    <td><input type="number" id="points_tf_1st" name="points_tf_1st" min="1" max="1000" value="20" required></td>
                                    <td><label for="points_tf_6th">6° Posto</label></td>
                                    <td><input type="number" id="points_tf_6th" name="points_tf_6th" min="1" max="1000" value="6" required></td>
                                </tr>
                                <tr>
                                    <td><label for="points_tf_2nd">2° Posto</label></td>
                                    <td><input type="number" id="points_tf_2nd" name="points_tf_2nd" min="1" max="1000" value="15" required></td>
                                    <td><label for="points_tf_7th">7° Posto</label></td>
                                    <td><input type="number" id="points_tf_7th" name="points_tf_7th" min="1" max="1000" value="5" required></td>
                                </tr>
                                <tr>
                                    <td><label for="points_tf_3rd">3° Posto</label></td>
                                    <td><input type="number" id="points_tf_3rd" name="points_tf_3rd" min="1" max="1000" value="12" required></td>
                                    <td><label for="points_tf_8th">8° Posto</label></td>
                                    <td><input type="number" id="points_tf_8th" name="points_tf_8th" min="1" max="1000" value="3" required></td>
                                </tr>
                                <tr>
                                    <td><label for="points_tf_4th">4° Posto</label></td>
                                    <td><input type="number" id="points_tf_4th" name="points_tf_4th" min="1" max="1000" value="10" required></td>
                                    <td><label for="points_tf_9th">9° Posto</label></td>
                                    <td><input type="number" id="points_tf_9th" name="points_tf_9th" min="1" max="1000" value="2" required></td>
                                </tr>
                                <tr>
                                    <td><label for="points_tf_5th">5° Posto</label></td>
                                    <td><input type="number" id="points_tf_5th" name="points_tf_5th" min="1" max="1000" value="8" required></td>
                                    <td><label for="points_tf_10th">10° Posto</label></td>
                                    <td><input type="number" id="points_tf_10th" name="points_tf_10th" min="1" max="1000" value="1" required></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-section">
                        <h3>Punteggi - Tocca per Primo</h3>
                        <p><strong>Solo il primo che clicca ottiene punti</strong>, tutti gli altri: 0 punti.</p>
                        
                        <div class="form-group">
                            <label for="points_clickfirst">Punti per il primo giocatore:</label>
                            <input type="number" id="points_clickfirst" name="points_clickfirst" 
                                   min="1" max="1000" value="50" required style="width: 150px;">
                            <small>Il primo giocatore che clicca ottiene questi punti (gli altri: 0 punti)</small>
                        </div>
                    </div>
                    
                    <!-- Display Settings -->
                    <div class="form-section">
                        <h3>Visualizzazione</h3>
                        <div class="form-group">
                            <label for="show_leaderboard">
                                <input type="checkbox" id="show_leaderboard" name="show_leaderboard" value="1" checked>
                                Mostra classifica in tempo reale
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="show_correct_answer">
                                <input type="checkbox" id="show_correct_answer" name="show_correct_answer" value="1" checked>
                                Mostra risposta corretta dopo ogni round
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Salva Impostazioni</button>
                        <button type="button" class="btn btn-secondary" onclick="loadSettings()">Ripristina</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Enable/disable auto-next delay based on checkbox
        const autoNextCheckbox = document.getElementById('auto_next_round');
        const autoNextDelay = document.getElementById('auto_next_delay');
        
        if (autoNextCheckbox && autoNextDelay) {
            autoNextCheckbox.addEventListener('change', function() {
                autoNextDelay.disabled = !this.checked;
            });
            
            // Set initial state
            autoNextDelay.disabled = !autoNextCheckbox.checked;
        }
        
        let selectedSetId = <?php echo $selectedSetId ? $selectedSetId : 'null'; ?>;
        
        // Select set row
        function selectSetRow(row, setId, setName) {
            // Se la riga è già selezionata, deseleziona
            if (row.classList.contains('selected-row')) {
                deselectAllRows();
                return;
            }
            
            // Deseleziona tutte le altre righe
            deselectAllRows();
            
            // Seleziona questa riga
            row.classList.add('selected-row');
            selectedSetId = setId;
            
            // Nascondi i bottoni modifica/elimina originali
            const actionButtons = row.querySelector('.action-buttons');
            if (actionButtons) {
                actionButtons.style.display = 'none';
            }
            
            // Aggiungi pulsante X per eliminare
            const deleteCell = row.cells[4];
            deleteCell.innerHTML = `
                <button class="btn-icon btn-danger" onclick="event.stopPropagation(); deleteSelectedSet(${setId}, '${setName.replace(/'/g, "\\'")}'); return false;" title="Elimina" style="font-size: 1.5em; padding: 5px 15px;">×</button>
            `;
            
            // Disabilita search bar
            const searchInput = document.getElementById('search-sets');
            const searchCriteria = document.getElementById('search-criteria');
            const searchButton = document.querySelector('.search-bar .btn-primary');
            
            if (searchInput) searchInput.disabled = true;
            if (searchCriteria) searchCriteria.disabled = true;
            if (searchButton) searchButton.disabled = true;
            
            // Nascondi tutte le altre righe
            document.querySelectorAll('#sets-table-body tr').forEach(tr => {
                if (tr !== row) {
                    tr.style.display = 'none';
                }
            });
        }
        
        function deselectAllRows() {
            selectedSetId = null;
            
            // Rimuovi selezione
            document.querySelectorAll('#sets-table-body tr').forEach(row => {
                row.classList.remove('selected-row');
                row.style.display = '';
                
                // Ripristina i bottoni originali
                const setId = row.dataset.setId;
                const setName = row.cells[0].textContent.trim();
                const setDescription = row.cells[1].textContent.trim();
                
                const actionButtons = row.querySelector('.action-buttons');
                if (actionButtons) {
                    actionButtons.style.display = 'flex';
                }
                
                // Ripristina cell azioni originali se modificata
                const deleteCell = row.cells[4];
                if (!deleteCell.querySelector('.action-buttons')) {
                    deleteCell.innerHTML = `
                        <div class="action-buttons">
                            <button class="btn-icon btn-warning" onclick="event.stopPropagation(); editSet(${setId}, '${setName}', '${setDescription}')" title="Modifica">✎</button>
                            <button class="btn-icon btn-danger" onclick="event.stopPropagation(); deleteSet(${setId}, '${setName}')" title="Elimina">×</button>
                        </div>
                    `;
                }
            });
            
            // Riabilita search bar
            const searchInput = document.getElementById('search-sets');
            const searchCriteria = document.getElementById('search-criteria');
            const searchButton = document.querySelector('.search-bar .btn-primary');
            
            if (searchInput) searchInput.disabled = false;
            if (searchCriteria) searchCriteria.disabled = false;
            if (searchButton) searchButton.disabled = false;
        }
        
        function deleteSelectedSet(id, name) {
            if (confirm(`Sei sicuro di voler eliminare il set "${name}"?`)) {
                deleteSet(id, name);
            }
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
            // First, clear the questions container
            document.getElementById('questions-container').innerHTML = '';
            
            const actionInput = document.querySelector('input[name="action"]');
            actionInput.value = 'update_set_with_questions';
            console.log('Action field set to:', actionInput.value); // Debug
            
            // Add hidden field for set_id BEFORE resetting values
            let setIdInput = document.getElementById('edit-set-id');
            if (!setIdInput) {
                setIdInput = document.createElement('input');
                setIdInput.type = 'hidden';
                setIdInput.id = 'edit-set-id';
                setIdInput.name = 'set_id';
                document.getElementById('create-set-form').appendChild(setIdInput);
            }
            setIdInput.value = setId;
            
            console.log('Editing set ID:', setId, 'Field value:', setIdInput.value); // Debug
            console.log('Form will submit with action:', actionInput.value, 'and set_id:', setIdInput.value); // Debug
            
            // Fill form (don't use reset() as it would clear set_id)
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
        let selectedGameSetId = null;
        let selectedGameSetName = '';
        let roomActive = false;
        let roomCode = '';
        
        // Create room
        function createRoom() {
            if (!selectedGameSetId) {
                alert('Seleziona prima un set di domande');
                return;
            }
            
            roomActive = true;
            
            // Save room code on server
            fetch('/src/api/api.php?endpoint=create_room', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    question_set_id: selectedGameSetId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Save room code from server
                    roomCode = data.room_code;
                    
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
                fetch('/src/api/api.php?endpoint=close_room', {
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
                        selectedGameSetId = null;
                        selectedGameSetName = '';
                        
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
            selectedGameSetId = setId;
            selectedGameSetName = setName;
            
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
            
            // Allow starting with 0 or more players for debug
            if (selectedGameSetId) {
                startBtn.disabled = false;
            } else {
                startBtn.disabled = true;
            }
        }
        
        // Start game with selected set
        function startGame() {
            if (!selectedGameSetId) {
                alert('Seleziona prima un set di domande');
                return;
            }
            
            const connectedCount = document.getElementById('connected-count').textContent;
            
            if (confirm(`Avviare la partita "${selectedGameSetName}" con ${connectedCount} giocatori?`)) {
                // Load the question set and start the first round
                loadQuestionSet(selectedGameSetId);
            }
        }
        
        // Load question set and start first round
        function loadQuestionSet(setId) {
            fetch('../src/api/api.php?endpoint=game&action=get_set&set_id=' + setId)
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
            fetch('../src/api/api.php?endpoint=game&action=start_round&round_id=' + roundId, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Round started:', roundId);
                    // Redirect to game page
                    window.location.href = 'game.php';
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
            fetch('../src/api/api.php?endpoint=connected_devices')
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
            // Add submit listener to debug form data
            const createSetForm = document.getElementById('create-set-form');
            if (createSetForm) {
                createSetForm.addEventListener('submit', function(e) {
                    const actionValue = document.querySelector('input[name="action"]').value;
                    const setIdInput = document.getElementById('edit-set-id');
                    const setIdValue = setIdInput ? setIdInput.value : 'NOT PRESENT';
                    console.log('FORM SUBMIT - Action:', actionValue, 'Set ID:', setIdValue);
                });
            }
            
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
            document.getElementById('create-set-form').reset();
            document.getElementById('questions-container').innerHTML = '';
            document.querySelector('input[name="action"]').value = 'create_set_with_questions';
            const setIdInput = document.getElementById('edit-set-id');
            if (setIdInput) setIdInput.remove();
            
            document.querySelector('.modal-header h2').textContent = 'Nuovo Set di Domande';
            document.querySelector('.modal-actions .btn-success').textContent = 'Salva Set';
            
            // Set default number of questions
            document.getElementById('num-questions').value = 5;
            
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
                <form id="create-set-form" method="POST" action="admin.php">
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