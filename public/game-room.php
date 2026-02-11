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
$settingsResult = $admin->getAllSettings();
$gameSettings = $settingsResult['settings'] ?? [];

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
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/mobile.css">
    <link rel="stylesheet" href="../assets/css/game-room.css">
    <link rel="stylesheet" href="../assets/css/qr-code.css">
    <script src="../assets/js/qrcode.min.js"></script>
    <script src="../assets/js/html2pdf.bundle.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 Gestione Partita</h1>
            <div class="user-info">
                <button id="btn-back-admin" class="btn btn-secondary" title="Chiudi la stanza prima di tornare">
                    ← Torna a Admin
                </button>
            </div>
        </div>

        <!-- Game Management Sections -->
        <div class="admin-section">
            <div class="settings-group" id="step-select-set">
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
            <div class="settings-group hidden" id="step-room">
                <h2>2. Avvia Stanza</h2>

                <p class="selected-set-info">
                    <strong>✓ Set selezionato:</strong> <span id="selected-set-display"></span>
                </p>

                <p class="room-section-text">Clicca su "Avvia Stanza" per permettere ai giocatori di connettersi con il codice stanza.</p>

                <div class="button-container-mb">
                    <button class="btn btn-success" id="btn-create-room">
                        Avvia Stanza
                    </button>
                </div>

                <div id="room-info" class="info-box hidden">
                    <p><strong>🎮 Stanza Attiva</strong></p>
                    <p>Codice stanza: <strong><span id="room-code">------</span></strong></p>
                    <div id="qr-code-container"></div>
                    <p>I giocatori possono ora connettersi utilizzando questo codice o inquadrando il QR.</p>
                    <div class="button-container-close">
                        <button class="btn btn-info" id="btn-download-pdf">
                            📄 Scarica PDF
                        </button>
                        <button class="btn btn-danger" id="btn-close-room">
                            Chiudi Stanza
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Connected Devices -->
            <div class="settings-group hidden" id="step-devices">
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
                <div id="game-start-section">
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
        let minPlayers = 1; // Valore fisso dopo rimozione dal database

        // Generate QR code for room code
        function generateQRCode(code) {
            const container = document.getElementById('qr-code-container');
            container.innerHTML = ''; // Clear previous QR

            // Create QR code with URL pointing to player join page
            const qrUrl = `http://151.21.203.214:9000/public/login-player.php?code=${encodeURIComponent(code)}`;
            new QRCode(container, {
                text: qrUrl,
                width: 200,
                height: 200,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        // Generate PDF with QR code and room code
        function downloadPDF() {
            const roomCode = document.getElementById('room-code').textContent;
            const qrCanvas = document.querySelector('#qr-code-container canvas');

            if (!qrCanvas) {
                alert('QR code non ancora generato. Attendi un momento.');
                return;
            }

            // Create HTML content for PDF
            const pdfContent = `
                <div style="text-align: center; padding: 20px; font-family: Arial, sans-serif;">
                    <h1>🎮 Marriage Game - Stanza Attiva</h1>
                    <p style="font-size: 18px; margin: 20px 0;">Codice Stanza:</p>
                    <p style="font-size: 48px; font-weight: bold; letter-spacing: 10px; margin: 20px 0; font-family: monospace;">${roomCode}</p>
                    <p style="font-size: 16px; margin: 30px 0;">Inquadra il QR code per connetterti:</p>
                    <div style="margin: 20px auto; padding: 20px; display: flex; justify-content: center; align-items: center;">
                        ${qrCanvas.parentElement.innerHTML}
                    </div>
                    <p style="font-size: 14px; margin-top: 20px; color: #666;">I giocatori possono connettersi usando il codice stanza o il QR code.</p>
                </div>
            `;

            // Generate PDF
            const element = document.createElement('div');
            element.innerHTML = pdfContent;

            const opt = {
                margin: [15, 10, 15, 10],
                filename: `marriage-game-stanza-${roomCode}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, allowTaint: true },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' },
                pagebreak: { mode: [] }
            };

            html2pdf().set(opt).from(element).save();
        }

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
                    document.getElementById('room-info').classList.remove('hidden');
                    document.getElementById('room-code').textContent = roomCode;

                    // Generate QR code
                    generateQRCode(roomCode);

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
                alert('Errore nella comunicazione con il server');
            });
        });

        // Download PDF
        document.getElementById('btn-download-pdf').addEventListener('click', function() {
            downloadPDF();
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
                        document.getElementById('room-info').classList.add('hidden');
                        document.getElementById('qr-code-container').innerHTML = '';
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
                    if (data.success) {
                        setTimeout(() => {
                            const redirectUrl = 'room-admin.php?room_code=' + encodeURIComponent(roomCode);
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        alert('Errore nell\'avvio della partita: ' + (data.error || 'Errore sconosciuto'));
                    }
                })
                .catch(error => {
                    alert('Errore nella comunicazione con il server');
                });
            }
        });

        // Update connected devices
        function updateConnectedDevices() {
            if (!roomCode) {
                return;
            }

            const url = `/src/api/api.php?endpoint=connected_devices&room_code=${encodeURIComponent(roomCode)}`;

            fetch(url, {
                credentials: 'include'
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('connected-devices-body');
                        const countSpan = document.getElementById('connected-count');

                        const devices = Array.isArray(data.devices) ? data.devices : [];
                        const count = data.count || devices.length || 0;

                        if (countSpan) {
                            countSpan.textContent = count;
                            updateStartButton();
                        }

                        if (devices.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="3" class="loading-text">
                                        Nessun dispositivo connesso
                                    </td>
                                </tr>
                            `;
                        } else {
                            tbody.innerHTML = devices.map(device => {
                                let timeString = 'Ora sconosciuta';
                                try {
                                    if (device.connected_at) {
                                        const connectedDate = new Date(device.connected_at);
                                        if (!isNaN(connectedDate.getTime())) {
                                            timeString = connectedDate.toLocaleTimeString('it-IT', {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                                second: '2-digit'
                                            });
                                        }
                                    }
                                } catch (e) {
                                    // Date parsing error - use default time string
                                }

                                return `
                                <tr>
                                    <td>${device.username || 'Giocatore'}</td>
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
                    // Silent error handling for polling
                });
        }

        // Initialize from URL if set_id is provided
        document.addEventListener('DOMContentLoaded', function() {
            initializeFromUrl();
        });
</script>
</body>
</html>
