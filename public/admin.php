<?php
session_start();

require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/utils/admin_helper.php';
require_once __DIR__ . '/../src/services/AdminService.php';
require_once __DIR__ . '/../src/services/GameService.php';
require_once __DIR__ . '/../src/services/QuestionService.php';

requireAdmin();

$admin = new AdminService();
$question = new QuestionService();
$game = new GameService();
$questionSet = $question; // Alias for backward compatibility

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_tab'])) {
    $_SESSION['admin_tab'] = $_POST['change_tab'];
    exit;
}

$currentTab = $_SESSION['admin_tab'] ?? 'sets';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!empty($action)) {
        if ($action === 'save_settings' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            handleAction($action, $admin, $game, $question, $questionSet);
            exit;
        }
        handleAction($action, $admin, $game, $question, $questionSet);
    }
}

handleAjaxRequest($question);

// Inizializza variabili di sessione per la paginazione
if (!isset($_SESSION['questions_page'])) $_SESSION['questions_page'] = 1;
if (!isset($_SESSION['sets_page'])) $_SESSION['sets_page'] = 1;

// Carica dati solo per il tab attivo
if ($currentTab === 'sets') {
    // Aggiorna pagina se ricevuto POST
    if (!empty($_POST['questions_page'])) $_SESSION['questions_page'] = (int)$_POST['questions_page'];

    $searchQuery = $_POST['search_query'] ?? '';
    $searchType = $_POST['search_type'] ?? 'contains';
    $category = $_POST['category'] ?? '';
    $currentPage = $_SESSION['questions_page'];
    $questionsData = getAllQuestions($question, $searchQuery, $currentPage, $searchType, $category);
    $questions = $questionsData['questions'];
    $pagination = $questionsData['pagination'];
    $categories = $question->getAllCategories();
} elseif ($currentTab === 'settings') {
    // Aggiorna pagina se ricevuto POST
    if (!empty($_POST['sets_page'])) $_SESSION['sets_page'] = (int)$_POST['sets_page'];

    $setSearchQuery = $_POST['set_search_query'] ?? '';
    $setSearchType = $_POST['set_search_type'] ?? 'contains';
    $setCurrentPage = $_SESSION['sets_page'];
    if (!empty($setSearchQuery)) {
        $setsData = $questionSet->search($setSearchQuery, $setSearchType, $setCurrentPage);
    } else {
        $setsData = $questionSet->getAll($setCurrentPage);
    }
    $questionSets = $setsData['sets'];
    $pagination = $setsData;
} elseif ($currentTab === 'general') {
    $settingsResult = $admin->getAllSettings();
    $gameSettings = $settingsResult['success'] ? $settingsResult['settings'] : [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Mvquiz</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/tab.css">
    <link rel="stylesheet" href="../assets/css/views.css">
    <link rel="stylesheet" href="../assets/css/settings-group.css">
    <link rel="stylesheet" href="../assets/css/popup.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px; flex: 1;">
                <div style="width: 65px; height: 65px; background: url('../assets/image/background.jpg') no-repeat center / contain; display: inline-block;"></div>
                <h1>Mvquiz Admin</h1>
            </div>
            <div class="user-info">
                <a href="logout.php?logout=1" class="btn btn-secondary">Logout</a>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="admin-nav">
            <a class="nav-item" data-tab="sets">
                <span class="nav-label">❓ Domande</span>
            </a>
            <a class="nav-item" data-tab="settings">
                <span class="nav-label">🎮 Partita</span>
            </a>
            <a class="nav-item" data-tab="general">
                <span class="nav-label">ℹ️ Impostazioni Generali</span>
            </a>
        </div>

        <!-- Tab Content -->
        <div id="tab-sets" class="tab-content">
            <?php if ($currentTab === 'sets') include 'views/questions.php'; ?>
        </div>

        <div id="tab-settings" class="tab-content">
            <?php if ($currentTab === 'settings') include 'views/game.php'; ?>
        </div>

        <div id="tab-general" class="tab-content">
            <?php if ($currentTab === 'general') include 'views/settings.php'; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = '<?php echo $currentTab; ?>';

            // Rimuovi active da tutti
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            // Aggiungi active al tab corrente con transizione
            const navItem = document.querySelector('[data-tab="' + activeTab + '"]');
            if (navItem) {
                navItem.classList.add('active');
                const tabContent = document.getElementById('tab-' + activeTab);
                if (tabContent) {
                    // Trigger reflow per attivare la transizione
                    tabContent.offsetHeight;
                    tabContent.classList.add('active');
                }
            }

            // Click sui tab con transizione
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabName = this.getAttribute('data-tab');

                    // Se il tab è già attivo, non fare nulla
                    if (this.classList.contains('active')) {
                        return;
                    }

                    // Anima il tab attuale
                    document.querySelectorAll('.tab-content.active').forEach(content => {
                        content.classList.remove('active');
                    });
                    document.querySelectorAll('.nav-item.active').forEach(nav => {
                        nav.classList.remove('active');
                    });

                    // Invia POST per cambiare tab
                    fetch('admin.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'change_tab=' + encodeURIComponent(tabName)
                    }).then(() => location.reload());
                });
            });
        });
    </script>
</body>
</html>
