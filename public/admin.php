<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/utils/helper.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_tab'])) {
    $_SESSION['admin_tab'] = $_POST['change_tab'];
    exit;
}

$currentTab = $_SESSION['admin_tab'] ?? 'sets';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action = $_POST['action'] ?? '')) {
    match ($action) {
        'change_credentials', 'update_credentials', 'update_admin_credentials' => handleCredentials(),
        'save_settings', 'update_general_settings' => handleSettings(),
        'start_round', 'close_round' => handleRound($action),
        'add_question', 'save_question' => handleQuestion(false),
        'update_question' => handleQuestion(true),
        'delete_question' => handleDeleteQuestion(),
        'add_questionset', 'update_questionset', 'delete_questionset' => handleQuestionSet($action),
        default => redirect('sets', null, 'invalid_action'),
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'delete_question') {
    handleDeleteQuestion();
}

$_SESSION['questions_page'] ??= 1;
$_SESSION['sets_page'] ??= 1;

$questions = $categories = $questionSets = $gameSettings = [];
$pagination = ['total' => 0, 'page' => 1, 'perPage' => 10, 'totalPages' => 1];

$tabData = match ($currentTab) {
    'sets'     => loadQuestionsTab(),
    'settings' => loadSetsTab(),
    'general'  => loadGeneralTab(),
    default    => [],
};
extract($tabData);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - MVquiz</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=17">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-brand">
                <div class="header-logo"></div>
                <h1>MVquiz Admin</h1>
            </div>
            <div class="user-info">
                <a href="logout.php?role=admin" class="btn btn-secondary">Logout</a>
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
            const activeTab = '<?php echo htmlspecialchars($currentTab, ENT_QUOTES); ?>';

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

    <!-- Toast Container (globale per tutti i tab) -->
    <div id="toast-container" class="toast-container"></div>
    <script>
    function showToast(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toast-container');
        const icons = { success: '✓', error: '✗', warning: '⚠', info: 'ℹ' };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.setProperty('--toast-duration', duration + 'ms');
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.classList.add('toast-hiding'); setTimeout(() => this.parentElement.remove(), 300)">×</button>
            <div class="toast-progress"></div>
        `;

        container.appendChild(toast);
        setTimeout(() => {
            if (toast.parentElement) {
                toast.classList.add('toast-hiding');
                setTimeout(() => toast.remove(), 300);
            }
        }, duration);
    }
    </script>
</body>
</html>
