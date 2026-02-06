<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/utils/admin_helper.php';
require_once __DIR__ . '/../src/services/AdminService.php';
require_once __DIR__ . '/../src/services/GameService.php';
require_once __DIR__ . '/../src/services/QuestionService.php';

requireAdmin();

$admin = new AdminService();
$question = new QuestionService();
$game = new GameService();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_settings' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        handleAction($action, $admin, $game, $question);
        exit;
    }
    handleAction($action, $admin, $game, $question);
}

// Handle GET requests for AJAX
handleAjaxRequest($question);

// Get data
$searchQuery = $_GET['search'] ?? '';
$searchType = $_GET['search_type'] ?? 'contains';
$category = $_GET['category'] ?? '';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$questionsData = getAllQuestions($question, $searchQuery, $currentPage, $searchType, $category);
$questions = $questionsData['questions'];
$pagination = $questionsData['pagination'];

$settingsResult = $admin->getAllSettings();
$gameSettings = $settingsResult['success'] ? $settingsResult['settings'] : [];
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
            <a href="#sets" class="nav-item active" data-tab="sets">
                <span class="nav-label">❓ Domande</span>
            </a>
            <a href="#settings" class="nav-item" data-tab="settings">
                <span class="nav-label">🎮 Partita</span>
            </a>
            <a href="#general" class="nav-item" data-tab="general">
                <span class="nav-label">ℹ️ Impostazioni Generali</span>
            </a>
        </div>

        <!-- Tab: Partita -->
        <div id="tab-sets" class="tab-content active">
            <?php include 'admin/questions.php'; ?>
        </div>

        <!-- Tab: Gestione Partita -->
        <div id="tab-settings" class="tab-content">
            <?php include 'admin/game.php'; ?>
        </div>

        <!-- Tab: Impostazioni Generali -->
        <div id="tab-general" class="tab-content">
            <?php include 'admin/settings.php'; ?>
        </div>
    </div>

    <script>
        // Tab navigation
        function showTab(tabName, element) {
            const newTab = document.getElementById('tab-' + tabName);
            const currentTab = document.querySelector('.tab-content.active');

            if (currentTab === newTab) return;

            if (currentTab) {
                currentTab.classList.remove('active');
            }
            newTab.classList.add('active');

            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            if (element) element.classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.nav-item').forEach(navItem => {
                navItem.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabName = this.getAttribute('data-tab');
                    showTab(tabName, this);
                });
            });
        });
    </script>
</body>
</html>
