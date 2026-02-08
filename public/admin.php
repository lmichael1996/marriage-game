<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/utils/admin_helper.php';
require_once __DIR__ . '/../src/services/AdminService.php';
require_once __DIR__ . '/../src/services/GameService.php';
require_once __DIR__ . '/../src/services/QuestionService.php';
require_once __DIR__ . '/../src/services/QuestionSetService.php';

requireAdmin();
session_start();

$admin = new AdminService();
$question = new QuestionService();
$game = new GameService();
$questionSet = new QuestionSetService();

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

// Carica dati solo per il tab attivo
if ($currentTab === 'sets') {
    $searchQuery = $_POST['search_query'] ?? '';
    $searchType = $_POST['search_type'] ?? 'contains';
    $category = $_POST['category'] ?? '';
    $currentPage = $_POST['questions_page'] ?? 1;
    $questionsData = getAllQuestions($question, $searchQuery, $currentPage, $searchType, $category);
    $questions = $questionsData['questions'];
    $pagination = $questionsData['pagination'];
    $categories = $question->getAllCategories();
} elseif ($currentTab === 'settings') {
    $setSearchQuery = $_POST['set_search_query'] ?? '';
    $setSearchType = $_POST['set_search_type'] ?? 'contains';
    $setCurrentPage = $_POST['sets_page'] ?? 1;
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
    <title>Admin - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/mobile.css">
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
            <?php if ($currentTab === 'sets') include 'admin/questions.php'; ?>
        </div>

        <div id="tab-settings" class="tab-content">
            <?php if ($currentTab === 'settings') include 'admin/game.php'; ?>
        </div>

        <div id="tab-general" class="tab-content">
            <?php if ($currentTab === 'general') include 'admin/settings.php'; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = '<?php echo $currentTab; ?>';

            // Rimuovi active da tutti
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            // Aggiungi active al tab corrente
            const navItem = document.querySelector('[data-tab="' + activeTab + '"]');
            if (navItem) {
                navItem.classList.add('active');
                const tabContent = document.getElementById('tab-' + activeTab);
                if (tabContent) tabContent.classList.add('active');
            }

            // Click sui tab
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabName = this.getAttribute('data-tab');
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
