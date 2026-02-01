<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/services/AdminService.php';
require_once __DIR__ . '/../src/services/QuestionService.php';

// Check admin access
requireAdmin();

// Use services directly (Controller layer removed)
$admin = new AdminService();
$questionService = new QuestionService();

// Get game settings (AdminService returns key=>value settings)
$gameSettings = $admin->getAllSettings() ?: [];

// Get all question sets for the dropdown (QuestionService returns structured array)
$questionSetsResult = $questionService->getAllQuestionSets(1, 100);
$questionSets = $questionSetsResult['sets'] ?? [];

// Get selected set ID from URL if available
$selectedSetId = isset($_GET['set_id']) ? (int)$_GET['set_id'] : null;
$selectedSet = null;

if ($selectedSetId) {
    $selectedSet = $questionService->getQuestionSetById($selectedSetId);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .game-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: #fff;
            padding: 1.5rem;
            border: 2px solid #1a1a1a;
        }

        .game-management-header h1 {
            margin: 0;
            font-size: 1.6em;
        }

        .back-link {
            padding: 0.75rem 1.5rem;
            background: #fff;
            border: 2px solid #1a1a1a;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-block;
        }

        .back-link:hover:not(:disabled) {
            background: #1a1a1a;
            color: #fff;
        }

        .back-link:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .game-step {
            background: #fff;
            padding: 2rem;
            border: 2px solid #1a1a1a;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .game-step.hidden {
            display: none;
        }

        .game-step h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #1a1a1a;
        }

        .set-selector {
            margin-bottom: 1.5rem;
        }

        .set-selector label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .set-selector select {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            border: 2px solid #1a1a1a;
            background: #fff;
            cursor: pointer;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #4caf50;
            margin-right: 0.5rem;
        }

        .info-box {
            padding: 1.25rem;
            margin: 1rem 0;
            background: #f5f5f5;
            border-left: 4px solid #1a1a1a;
        }

        .info-box p {
            margin: 0.5rem 0;
        }

        #room-info {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }

        #game-start-section {
            padding: 1.25rem;
            margin-top: 1.5rem;
            background: #f5f5f5;
            border: 2px solid #1a1a1a;
            text-align: center;
        }

        #final-info p {
            margin: 0.75rem 0;
        }

        .button-container {
            text-align: center;
            margin-top: 1rem;
        }

        .button-container-mb {
            text-align: center;
            margin-bottom: 1rem;
        }

        .button-container-close {
            text-align: center;
            margin-top: 1rem;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="game-management-header">
            <h1>🎮 Gestione Partita</h1>
            <button id="btn-back-admin" class="back-link" title="Chiudi la stanza prima di tornare">
                ← Torna a Admin
            </button>
        </div>

        <!-- Step 1: Select Question Set -->
        <div class="admin-section">
            <div class="game-step" id="step-select-set">
                <h2>1. Seleziona Set di Domande</h2>

                <div class="set-selector">
                    <label for="set-select">Scegli un set:</label>
                    <select id="set-select">
                        <option value="">-- Seleziona un set --</option>
                        <?php foreach ($questionSets as $set): ?>
                            <option value="<?php echo $set['id']; ?>" data-name="<?php echo htmlspecialchars($set['set_name']); ?>">
                                <?php echo htmlspecialchars($set['set_name']); ?> (<?php echo $set['total_rounds']; ?> domande)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (empty($questionSets)): ?>
                    <div class="info-box loading-text">
                        <p>⚠️ Nessun set di domande disponibile. <a href="admin.php">Vai a creare un nuovo set</a></p>
                    </div>
                <?php endif; ?>

                <div class="button-container">
                    <button class="btn btn-success" id="btn-confirm-set" disabled>
                        Continua
                    </button>
                </div>
            </div>

            <!-- Step 2: Create Room -->
            <div class="game-step hidden" id="step-room">
                <h2>2. Avvia Stanza</h2>

                <p style="padding: 0.75rem; background-color: #e8f5e9; border-left: 4px solid #4caf50; margin-bottom: 1.5rem;">
                    <strong>✓ Set selezionato:</strong> <span id="selected-set-display" style="color: #2e7d32; font-weight: bold;"></span>
                </p>

                <p style="margin-bottom: 1.5rem;">Clicca su "Avvia Stanza" per permettere ai giocatori di connettersi con il codice stanza.</p>

                <div class="button-container-mb">
                    <button class="btn btn-success" id="btn-create-room">
                        Avvia Stanza
                    </button>
                </div>

                <div id="room-info" class="info-box" style="display: none;">
                    <p><strong>🎮 Stanza Attiva</strong></p>
                    <p>Codice stanza: <strong><span id="room-code">------</span></strong></p>
                    <p>I giocatori possono ora connettersi utilizzando questo codice.</p>
                    <div class="button-container-close">
                        <button class="btn btn-danger" id="btn-close-room">
                            Chiudi Stanza
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Connected Devices -->
            <div class="game-step hidden" id="step-devices">
                <h2>3. Dispositivi Connessi</h2>
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
                <div id="game-start-section" style="margin-top: 2rem;">
                    <div id="final-info">
                        <p><strong>Set selezionato:</strong> <span id="selected-set-name-final"></span></p>
                        <p><strong>Giocatori connessi:</strong> <span id="connected-count">0</span></p>
                        <div class="button-container">
                            <button class="btn btn-success" id="btn-start-game" disabled>
                                Avvia Partita
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedGameSetId = null;
        let selectedGameSetName = '';
        let roomActive = false;
        let roomCode = '';
        let devicesInterval = null;
        let minPlayers = <?php echo isset($gameSettings['min_players']) ? (int)$gameSettings['min_players'] : 1; ?>;

        // Pre-select set if passed via URL
        function initializeFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const setIdFromUrl = urlParams.get('set_id');

            if (setIdFromUrl) {
                const setId = parseInt(setIdFromUrl);
                const selectElement = document.getElementById('set-select');
                const option = selectElement.querySelector(`option[value="${setId}"]`);

                if (option) {
                    selectElement.value = setId;
                    selectedGameSetId = setId;
                    selectedGameSetName = option.getAttribute('data-name');

                    // Trigger confirmation automatically
                    document.getElementById('btn-confirm-set').disabled = false;
                    confirmSelection();
                }
            }
        }

        function confirmSelection() {
            if (!selectedGameSetId) {
                alert('Seleziona un set di domande');
                return;
            }

            // Show step 2
            document.getElementById('step-select-set').classList.add('hidden');
            document.getElementById('step-room').classList.remove('hidden');
            document.getElementById('step-devices').classList.add('hidden');

            document.getElementById('selected-set-display').textContent = selectedGameSetName;
            document.getElementById('selected-set-name-final').textContent = selectedGameSetName;
        }

        // Set selector change
        document.getElementById('set-select').addEventListener('change', function() {
            const setId = parseInt(this.value);
            if (setId) {
                selectedGameSetId = setId;
                selectedGameSetName = this.options[this.selectedIndex].getAttribute('data-name');
                document.getElementById('btn-confirm-set').disabled = false;
            } else {
                selectedGameSetId = null;
                selectedGameSetName = '';
                document.getElementById('btn-confirm-set').disabled = true;
            }
        });

        // Confirm set button
        document.getElementById('btn-confirm-set').addEventListener('click', confirmSelection);

        // Back to admin button
        document.getElementById('btn-back-admin').addEventListener('click', function() {
            if (roomActive) {
                alert('⚠️ Stanza ancora attiva! Chiudi la stanza prima di tornare a Admin.');
                return;
            }
            window.location.href = 'admin.php';
        });

        // Create room
        document.getElementById('btn-create-room').addEventListener('click', function() {
            if (!selectedGameSetId) {
                alert('Seleziona un set di domande');
                return;
            }

            roomActive = true;

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
                    roomCode = data.room_code;

                    // Show room info
                    document.getElementById('btn-create-room').style.display = 'none';
                    document.getElementById('room-info').style.display = 'block';
                    document.getElementById('room-code').textContent = roomCode;

                    // Disable back button
                    const backBtn = document.getElementById('btn-back-admin');
                    backBtn.disabled = true;
                    backBtn.title = 'Stanza attiva - Chiudi la stanza prima di tornare';

                    // Show step 3 below step 2 (don't hide step 2)
                    document.getElementById('step-devices').classList.remove('hidden');

                    // Start polling devices
                    if (!devicesInterval) {
                        updateConnectedDevices();
                        devicesInterval = setInterval(updateConnectedDevices, 1000);
                    }
                } else {
                    alert('Errore nella creazione della stanza: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore nella comunicazione con il server');
            });
        });

        // Close room
        document.getElementById('btn-close-room').addEventListener('click', function() {
            if (confirm('Vuoi chiudere la stanza? Tutti i giocatori verranno disconnessi.')) {
                fetch('/src/api/api.php?endpoint=delete_room', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        roomActive = false;
                        roomCode = '';

                        if (devicesInterval) {
                            clearInterval(devicesInterval);
                            devicesInterval = null;
                        }

                        // Hide room info and show create room button again
                        document.getElementById('room-info').style.display = 'none';
                        document.getElementById('btn-create-room').style.display = 'block';

                        // Enable back button
                        const backBtn = document.getElementById('btn-back-admin');
                        backBtn.disabled = false;
                        backBtn.title = 'Torna a Admin';

                        // Hide step 3 (devices)
                        document.getElementById('step-devices').classList.add('hidden');

                        // Clear devices table
                        document.getElementById('connected-devices-body').innerHTML = `
                            <tr>
                                <td colspan="3" class="loading-text">
                                    Caricamento dispositivi connessi...
                                </td>
                            </tr>
                        `;

                        // Reset devices count
                        document.getElementById('connected-count').textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Errore nella chiusura della stanza');
                });
            }
        });

        // Update start button
        function updateStartButton() {
            const connectedCount = parseInt(document.getElementById('connected-count').textContent) || 0;
            const startBtn = document.getElementById('btn-start-game');

            if (selectedGameSetId && connectedCount >= minPlayers) {
                startBtn.disabled = false;
            } else {
                startBtn.disabled = true;
            }
        }

        // Start game
        document.getElementById('btn-start-game').addEventListener('click', function() {
            if (!selectedGameSetId) {
                alert('Seleziona un set di domande');
                return;
            }

            const connectedCount = parseInt(document.getElementById('connected-count').textContent) || 0;

            if (connectedCount < minPlayers) {
                alert(`❌ Numero di giocatori insufficiente!\n\nGiocatori connessi: ${connectedCount}\nMinimo richiesto: ${minPlayers}\n\nAttendi che altri giocatori si connettano.`);
                return;
            }

            if (confirm(`Avviare la partita "${selectedGameSetName}" con ${connectedCount} giocatori?`)) {
                fetch('/src/api/api.php?endpoint=start_room', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('start_room response:', data, 'roomCode:', roomCode);
                    if (data.success) {
                        setTimeout(() => {
                            const redirectUrl = 'room-admin.php?room_code=' + encodeURIComponent(roomCode);
                            console.log('Redirecting to:', redirectUrl);
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        alert('Errore nell\'avvio della partita: ' + (data.error || 'Errore sconosciuto'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Errore nella comunicazione con il server');
                });
            }
        });

        // Update connected devices
        function updateConnectedDevices() {
            fetch('/src/api/api.php?endpoint=connected_devices')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('connected-devices-body');
                        const countSpan = document.getElementById('connected-count');

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
                            tbody.innerHTML = data.devices.map(device => {
                                const connectedDate = new Date(device.connected_at);
                                const timeString = connectedDate.toLocaleTimeString('it-IT', {
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    second: '2-digit'
                                });
                                return `
                                <tr>
                                    <td>${device.username}</td>
                                    <td>
                                        <span class="status-indicator"></span>
                                        Online
                                    </td>
                                    <td>${timeString}</td>
                                </tr>
                            `;
                            }).join('');
                        }
                    }
                })
                .catch(error => {
                    console.error('Errore caricamento dispositivi:', error);
                });
        }

        // Add smooth transition styles
        const style = document.createElement('style');
        style.textContent = `
            .game-step {
                transition: all 0.4s ease-in-out;
                opacity: 1;
                transform: translateY(0);
                max-height: 5000px;
                overflow: hidden;
            }
            .game-step.hidden {
                opacity: 0;
                transform: translateY(-20px);
                max-height: 0;
                margin: 0;
                padding: 0;
            }
        `;
        document.head.appendChild(style);

        // Initialize from URL if set_id is provided
        document.addEventListener('DOMContentLoaded', function() {
            initializeFromUrl();
        });
    </script>
</body>
</html>
