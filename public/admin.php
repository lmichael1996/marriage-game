<?php
require_once __DIR__ . '/../src/utils/AuthHelper.php';
require_once __DIR__ . '/../src/utils/AdminHelper.php';
require_once __DIR__ . '/../src/controllers/AdminController.php';
require_once __DIR__ . '/../src/controllers/GameController.php';

// Check admin access
AuthHelper::requireAdmin();

$admin = new AdminController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $game = new GameController();
    AdminHelper::handleAction($action, $admin, $game);
}

// Handle GET requests for AJAX
AdminHelper::handleAjaxRequest($admin);

// Get data
$searchQuery = $_GET['search'] ?? '';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$questionSetsData = AdminHelper::getQuestionSets($admin, $searchQuery, $currentPage);
$questionSets = $questionSetsData['sets'];
$pagination = $questionSetsData['pagination'];

// Get game settings
$settingsResult = $admin->getSettings();
$gameSettings = $settingsResult['success'] ? $settingsResult['settings'] : [];

// TODO: Questi metodi devono essere aggiunti ai controller
$rounds = []; // $roundModel->getAllRoundsWithStats();
$active_round = null; // $roundModel->getActiveRound();

// Get selected set
$selectedSetId = $_GET['set'] ?? null;
list($selectedSet, $setRounds) = AdminHelper::getSelectedSet($admin, $selectedSetId);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Smooth tab transitions */
        .tab-content {
            transition: opacity 0.2s ease-out, transform 0.2s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Panel - Marriage Game</h1>
            <div class="user-info">
                <a href="logout.php?logout=1" class="btn btn-secondary">Logout</a>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <div class="admin-nav">
            <a href="#sets" class="nav-item active" data-tab="sets">
                <span class="nav-label">Set Domande</span>
            </a>
            <a href="#game" class="nav-item" data-tab="game">
                <span class="nav-label">Partita</span>
            </a>
            <a href="#settings" class="nav-item" data-tab="settings">
                <span class="nav-label">Impostazioni</span>
            </a>
        </div>
        
        <!-- Tab: Set di Domande -->
        <div id="tab-sets" class="tab-content active">
            <?php include 'admin/admin_sets.php'; ?>
        </div>
        
        <!-- Tab: Partita -->
        <div id="tab-game" class="tab-content">
            <?php include 'admin/admin_game.php'; ?>
        </div>
        
        <!-- Tab: Impostazioni -->
        <div id="tab-settings" class="tab-content">
            <?php include 'admin/admin_settings.php'; ?>
        </div>
    </div>
    
    <!-- Create Set Modal -->
    <div id="create-set-modal" class="modal-overlay">
        <div class="modal-content modal-content-large">
            <div class="modal-header">
                <h2>Nuovo Set di Domande</h2>
                <button id="btn-close-modal-x" class="modal-close">&times;</button>
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
                        <input type="number" id="num-questions" name="num_questions" min="1" max="20" value="5">
                    </div>
                    
                    <div id="questions-container" class="questions-container">
                        <!-- Questions will be generated here -->
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" id="btn-cancel-modal" class="btn btn-secondary">Annulla</button>
                        <button type="submit" class="btn btn-success">Salva Set</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Define showTab early to avoid "not defined" errors
        function showTab(tabName, element) {
            const newTab = document.getElementById('tab-' + tabName);
            const currentTab = document.querySelector('.tab-content.active');
            
            // If clicking the same tab, do nothing
            if (currentTab === newTab) return;
            
            // Animate out current tab
            if (currentTab) {
                currentTab.style.opacity = '0';
                currentTab.style.transform = 'translateX(-10px)';
                
                setTimeout(() => {
                    currentTab.classList.remove('active');
                    currentTab.style.opacity = '';
                    currentTab.style.transform = '';
                    
                    // Animate in new tab
                    newTab.classList.add('active');
                    newTab.style.opacity = '0';
                    newTab.style.transform = 'translateX(10px)';
                    
                    // Force reflow
                    newTab.offsetHeight;
                    
                    setTimeout(() => {
                        newTab.style.opacity = '1';
                        newTab.style.transform = 'translateX(0)';
                    }, 30);
                }, 200);
            } else {
                // No current tab, just show new one
                newTab.classList.add('active');
            }
            
            // Update nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            if (element) {
                element.classList.add('active');
            }
        }

        // Minimal common JavaScript: modal helpers and question fields generation

        // Show Edit Set Modal (used by admin_sets.php)
        function showEditSetModal(setId, name, description, questions) {
            console.log('showEditSetModal called with:', { setId, name, description, questions });
            
            // Get form
            const form = document.getElementById('create-set-form');
            if (!form) {
                console.error('Form not found!');
                return;
            }
            
            // Reset form first
            form.reset();
            
            // Clear questions container
            const container = document.getElementById('questions-container');
            if (container) container.innerHTML = '';
            
            // Set action to update
            const actionInput = form.querySelector('input[name="action"]');
            if (actionInput) {
                actionInput.value = 'update_set_with_questions';
                console.log('Action set to:', actionInput.value);
            }
            
            // Add or update set_id hidden field
            let setIdInput = form.querySelector('input[name="set_id"]');
            if (!setIdInput) {
                setIdInput = document.createElement('input');
                setIdInput.type = 'hidden';
                setIdInput.name = 'set_id';
                setIdInput.id = 'edit-set-id';
                form.appendChild(setIdInput);
                console.log('Created set_id input');
            }
            setIdInput.value = setId;
            console.log('Set ID input value:', setIdInput.value);
            
            // Set form values
            const nameInput = document.getElementById('set-name');
            const descInput = document.getElementById('set-description');
            const numInput = document.getElementById('num-questions');
            
            if (nameInput) nameInput.value = name || '';
            if (descInput) descInput.value = description || '';
            if (numInput) numInput.value = questions?.length || 1;
            
            // Generate question fields
            generateQuestionFields();
            
            // Populate questions after a delay
            if (questions && questions.length > 0) {
                setTimeout(() => {
                    questions.forEach((q, idx) => {
                        const i = idx + 1;
                        
                        // Set question type and text
                        const typeSelect = document.querySelector(`select[name="type_${i}"]`);
                        const questionTextarea = document.querySelector(`textarea[name="question_${i}"]`);
                        
                        if (typeSelect) typeSelect.value = q.round_type;
                        if (questionTextarea) questionTextarea.value = q.question || '';
                        
                        // Update question type UI
                        updateQuestionType(i, q.round_type);
                        
                        // Set type-specific fields
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
                                if (correct) correct.value = q.correct_answer || 1;
                            } else if (q.round_type === 'truefalse') {
                                const timer = document.querySelector(`input[name="timer_${i}"]`);
                                const correct = document.querySelector(`select[name="correct_${i}"]`);
                                
                                if (timer) timer.value = q.timer || 30;
                                if (correct) correct.value = q.correct_answer || 1;
                            }
                        }, 50);
                    });
                }, 100);
            }
            
            // Update modal title and button
            const title = document.querySelector('.modal-header h2');
            const saveBtn = document.querySelector('.modal-actions .btn-success');
            
            if (title) title.textContent = 'Modifica Set di Domande';
            if (saveBtn) saveBtn.textContent = 'Salva Modifiche';
            
            // Show modal
            const modal = document.getElementById('create-set-modal');
            if (modal) modal.style.display = 'flex';
            
            console.log('Modal opened for editing');
        }

        function showCreateSetModal() {
            const form = document.getElementById('create-set-form');
            if (form) form.reset();
            const container = document.getElementById('questions-container');
            if (container) container.innerHTML = '';
                    const actionInput = document.querySelector('input[name="action"]');
                    if (actionInput) actionInput.value = 'create_set_with_questions';
                    const setIdInput = document.getElementById('edit-set-id');
                    if (setIdInput) setIdInput.remove();
                    const title = document.querySelector('.modal-header h2');
                    if (title) title.textContent = 'Nuovo Set di Domande';
                    const saveBtn = document.querySelector('.modal-actions .btn-success');
                    if (saveBtn) saveBtn.textContent = 'Salva Set';
                    const numInput = document.getElementById('num-questions');
                    if (numInput) numInput.value = 5;
                    const modal = document.getElementById('create-set-modal');
                    if (modal) modal.style.display = 'flex';
                    generateQuestionFields();
                }

                function closeCreateSetModal() {
                    const modal = document.getElementById('create-set-modal');
                    if (modal) modal.style.display = 'none';
                    const form = document.getElementById('create-set-form');
                    if (form) form.reset();
                    const container = document.getElementById('questions-container');
                    if (container) container.innerHTML = '';
                    const setIdInput = document.getElementById('edit-set-id');
                    if (setIdInput) setIdInput.remove();
                    const actionInput = document.querySelector('input[name="action"]');
                    if (actionInput) actionInput.value = 'create_set_with_questions';
                    const title = document.querySelector('.modal-header h2');
                    if (title) title.textContent = 'Nuovo Set di Domande';
                    const saveBtn = document.querySelector('.modal-actions .btn-success');
                    if (saveBtn) saveBtn.textContent = 'Salva Set';
                }

                function generateQuestionFields() {
                    const numInput = document.getElementById('num-questions');
                    const num = numInput ? (parseInt(numInput.value) || 1) : 1;
                    const container = document.getElementById('questions-container');
                    if (!container) return;
                    const currentQuestions = container.querySelectorAll('.question-block');
                    const currentCount = currentQuestions.length;

                    if (num > currentCount) {
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
                                        <input type="number" name="timer_${i}" min="5" max="120" value="30">
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
                        for (let i = currentCount; i > num; i--) {
                            const lastQuestion = container.querySelector('.question-block:last-child');
                            if (lastQuestion) lastQuestion.remove();
                        }
                    }
                }

                function updateQuestionType(index, type) {
                    const optionsDiv = document.getElementById(`options-${index}`);
                    if (!optionsDiv) return;
                    if (type === 'truefalse') {
                        optionsDiv.innerHTML = `
                            <div class="form-group">
                                <label>Timer (secondi):</label>
                                <input type="number" name="timer_${index}" min="5" max="120" value="30">
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
                                <input type="number" name="timer_${index}" min="5" max="120" value="30">
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

                // Minimal DOM ready bindings for common elements
                document.addEventListener('DOMContentLoaded', function() {
                    // Navigation tabs: each tab element has data-tab attribute
                    document.querySelectorAll('.nav-item').forEach(navItem => {
                        navItem.addEventListener('click', function(e) {
                            e.preventDefault();
                            const tabName = this.getAttribute('data-tab');
                            showTab(tabName, this);
                        });
                    });

                    // New set button
                    const btnNewSet = document.getElementById('btn-new-set');
                    if (btnNewSet) btnNewSet.addEventListener('click', showCreateSetModal);

                    // Modal close buttons
                    const btnCloseModalX = document.getElementById('btn-close-modal-x');
                    if (btnCloseModalX) btnCloseModalX.addEventListener('click', closeCreateSetModal);
                    const btnCancelModal = document.getElementById('btn-cancel-modal');
                    if (btnCancelModal) btnCancelModal.addEventListener('click', closeCreateSetModal);

                    // Number of questions input
                    const numQuestionsInput = document.getElementById('num-questions');
                    if (numQuestionsInput) {
                        numQuestionsInput.addEventListener('change', generateQuestionFields);
                        numQuestionsInput.addEventListener('input', generateQuestionFields);
                    }

                    // Form submit debug
                    const form = document.getElementById('create-set-form');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            const formData = new FormData(form);
                            console.log('=== FORM SUBMIT DEBUG ===');
                            console.log('Form submit - action:', formData.get('action'));
                            console.log('Form submit - set_id:', formData.get('set_id'));
                            console.log('Form submit - set_name:', formData.get('set_name'));
                            console.log('Form submit - num_questions:', formData.get('num_questions'));
                            
                            // Log all form data
                            console.log('All form data:');
                            for (let pair of formData.entries()) {
                                console.log(pair[0] + ': ' + pair[1]);
                            }
                            console.log('=== END DEBUG ===');
                        });
                    }

                    // Initialize if modal exists
                    generateQuestionFields();
                });
    </script>
</body>
</html>