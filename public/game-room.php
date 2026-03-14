<?php
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/utils/helper.php';

requireAdmin();

extract(loadGameRoom());
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Partita - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/main.css">
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
            <!-- Step 2: Create Room -->
            <div class="settings-group" id="step-room">
                <h2>2. Avvia Stanza</h2>

                <p class="selected-set-info" id="selected-set-info">
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

                    <!-- QR Code Tabs -->
                    <div style="margin-top: 20px;">
                        <div class="tab-container">
                            <button class="tab-button active" data-tab="player-qr">
                                👤 Giocatore
                            </button>
                            <button class="tab-button" data-tab="judge-qr">
                                ⚖️ Giudice
                            </button>
                        </div>

                        <!-- Player QR Tab -->
                        <div id="player-qr" class="tab-content active" style="text-align: center; padding: 20px;">
                            <p>Codice: <strong><span id="room-code-player">------</span></strong></p>
                            <div id="qr-code-container-player" style="display: inline-block; border: 2px solid #007bff; padding: 10px; border-radius: 4px;"></div>
                            <p style="margin-top: 10px; font-size: 0.9em;">Inquadra o usa il codice</p>
                        </div>

                        <!-- Judge QR Tab -->
                        <div id="judge-qr" class="tab-content" style="text-align: center; padding: 20px; display: none;">
                            <p>Codice: <strong><span id="room-code-judge">------</span></strong></p>
                            <div id="qr-code-container-judge" style="display: inline-block; border: 2px solid #28a745; padding: 10px; border-radius: 4px;"></div>
                            <p style="margin-top: 10px; font-size: 0.9em;">Inquadra o usa il codice</p>
                        </div>
                    </div>

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
                        <p><strong>Giudice:</strong> <span id="judge-status">❌ Non connesso</span></p>
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
        let codePlayer = '';
        let codeJudge = '';
        let devicesInterval = null;
        let minPlayers = 1; // Valore fisso dopo rimozione dal database

        // Initialize from PHP data if set_id was provided via URL
        <?php if ($selectedSet): ?>
        (function() {
            selectedGameSetId = <?php echo (int)$selectedSet['id']; ?>;
            selectedGameSetName = <?php echo json_encode($selectedSet['set_name']); ?>;
        })();
        <?php endif; ?>

        // Generate QR code for a specific code. If dataUri is provided, render the image.
        function generateQRCode(code, containerId, dataUri = null) {
            const container = document.getElementById(containerId);
            if (!container) {
                console.error('Container not found:', containerId);
                return;
            }

            // Completamente svuota il contenitore da qualsiasi contenuto precedente
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }
            container.innerHTML = '';

            if (dataUri) {
                const img = document.createElement('img');
                img.src = dataUri;
                img.style.border = '1px solid #ccc';
                img.style.display = 'block';
                img.style.margin = '10px auto';
                img.style.width = '250px';
                img.style.height = '250px';
                container.appendChild(img);
                return;
            }

            // Se non c'è dataUri, non fare nulla (il QR verrà generato on-demand dal server quando serve)
            container.innerHTML = '<p style="color: #999; text-align: center;">QR code generato al momento...</p>';
        }

        // Generate PDF with QR code and room code
        function downloadPDF() {
            const codePlayerElem = document.getElementById('room-code-player').textContent;

            if (!codePlayerElem || codePlayerElem === '------') {
                alert('Codice stanza non disponibile.');
                return;
            }

            // Richiedi il PDF all'API - l'API genererà il QR coerente
            fetch('/src/api/api.php?endpoint=generate_pdf', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_code: codePlayerElem
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.pdf_data) {
                    // Scarica il PDF
                    const link = document.createElement('a');
                    link.href = data.pdf_data;
                    link.download = data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert('Errore nella generazione del PDF: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(err => {
                console.error('Errore nel download del PDF:', err);
                alert('Errore nella comunicazione con il server');
            });
        }

        // Pre-select set if passed via URL
        function initializeFromUrl() {
            // If variables are already initialized from PHP, just call confirmSelection
            if (selectedGameSetId) {
                confirmSelection();
                return;
            }

            const urlParams = new URLSearchParams(window.location.search);
            const setIdFromUrl = urlParams.get('set_id');

            if (setIdFromUrl) {
                const setId = parseInt(setIdFromUrl);

                // Since we removed the select element, we need to get the set info from the PHP data
                // Find the set in the questionSets array and extract its info
                const questionSetsJson = <?php echo json_encode($questionSets); ?>;
                const selectedSet = questionSetsJson.find(s => s.id === setId);

                if (selectedSet) {
                    selectedGameSetId = setId;
                    selectedGameSetName = selectedSet.set_name;

                    // Trigger confirmation automatically
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
            document.getElementById('step-room').classList.remove('hidden');
            document.getElementById('step-devices').classList.add('hidden');

            // Only display set name if it doesn't have a timestamp (not temporary)
            // Temporary sets have names like "set temporaneo 1771101661442" (ending with space + 13 digits)
            const hasTimestamp = /\s\d{13}$/.test(selectedGameSetName);

            if (!hasTimestamp) {
                document.getElementById('selected-set-info').style.display = 'block';
                document.getElementById('selected-set-display').textContent = selectedGameSetName;
                document.getElementById('selected-set-name-final').textContent = selectedGameSetName;
            } else {
                document.getElementById('selected-set-info').style.display = 'none';
                document.getElementById('selected-set-display').textContent = '';
                document.getElementById('selected-set-name-final').textContent = '';
            }
        }

        // Set selector and confirm button removed with step-select-set section

        // Back to admin button
        document.getElementById('btn-back-admin').addEventListener('click', function() {
            if (roomActive) {
                alert('⚠️ Non è possibile tornare ad admin con partita aperta. Chiudi la stanza prima di tornare.');
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
                }),
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
                    codePlayer = data.code_player;
                    codeJudge = data.code_judge;

                    // Show room info
                    document.getElementById('btn-create-room').style.display = 'none';
                    document.getElementById('room-info').classList.remove('hidden');

                    // Update both code displays
                    document.getElementById('room-code-player').textContent = codePlayer;
                    document.getElementById('room-code-judge').textContent = codeJudge;

                    // Genera QR per player (usa data URI se fornita dal server)
                    if (data.qr_uri_player) {
                        generateQRCode(codePlayer, 'qr-code-container-player', data.qr_uri_player);
                    } else {
                        generateQRCode(codePlayer, 'qr-code-container-player');
                    }

                    // Genera QR per judge (usa data URI se fornita dal server)
                    if (data.qr_uri_judge) {
                        generateQRCode(codeJudge, 'qr-code-container-judge', data.qr_uri_judge);
                    } else {
                        generateQRCode(codeJudge, 'qr-code-container-judge');
                    }

                    // Disable back button
                    const backBtn = document.getElementById('btn-back-admin');
                    backBtn.title = 'Stanza attiva - Chiudi la stanza prima di tornare';

                    // Show step 3 below step 2 (don't hide step 2)
                    document.getElementById('step-devices').classList.remove('hidden');

                    // Start polling devices
                    if (!devicesInterval) {
                        updateConnectedDevices();
                        devicesInterval = setInterval(updateConnectedDevices, 1000);
                    }
                } else {
                    roomActive = false;
                    alert('Errore nella creazione della stanza: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                roomActive = false;
                alert('Errore nella comunicazione con il server: ' + error.message);
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
                    console.log('Close room response:', data);
                    if (data.success) {
                        roomActive = false;
                        codePlayer = '';
                        codeJudge = '';

                        if (devicesInterval) {
                            clearInterval(devicesInterval);
                            devicesInterval = null;
                        }

                        // Hide room info box using classList (consistent with show)
                        const roomInfoDiv = document.getElementById('room-info');
                        if (roomInfoDiv) {
                            roomInfoDiv.classList.add('hidden');
                        }

                        // Completely clear QR containers
                        const playerContainer = document.getElementById('qr-code-container-player');
                        if (playerContainer) {
                            while (playerContainer.firstChild) {
                                playerContainer.removeChild(playerContainer.firstChild);
                            }
                            playerContainer.innerHTML = '';
                        }

                        const judgeContainer = document.getElementById('qr-code-container-judge');
                        if (judgeContainer) {
                            while (judgeContainer.firstChild) {
                                judgeContainer.removeChild(judgeContainer.firstChild);
                            }
                            judgeContainer.innerHTML = '';
                        }

                        document.getElementById('btn-create-room').style.display = 'block';

                        // Enable back button
                        const backBtn = document.getElementById('btn-back-admin');
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
                    } else {
                        alert('Errore: ' + (data.message || 'impossibile chiudere la stanza'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Errore nella chiusura della stanza: ' + error);
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
                alert(`❌ Numero di giocatori insufficiente!\n\nGiocatori connessi: ${connectedCount}\nMinimo richiesto: ${minPlayers}\n\nAttendi che altri giocatori si connettino.`);
                return;
            }

            const alertQuestion = selectedGameSetName.startsWith('#Temporary set')
                ? `Avviare la partita con ${connectedCount} giocatori?`
                : `Avviare la partita "${selectedGameSetName}" con ${connectedCount} giocatori?`;

            if (confirm(alertQuestion)) {
                fetch('/src/api/api.php?endpoint=start_room', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        code_player: codePlayer
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setTimeout(() => {
                            const roomId = data.room_id;
                            const redirectUrl = 'room-admin.php?room_id=' + roomId;
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
            if (!codePlayer) {
                return;
            }

            const url = `/src/api/api.php?endpoint=connected_devices&code_player=${encodeURIComponent(codePlayer)}`;

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

                        const judgeStatus = document.getElementById('judge-status');
                        if (judgeStatus) {
                            judgeStatus.innerHTML = data.judge_connected
                                ? '✅ Connesso'
                                : '❌ Non connesso';
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

            // Setup tab switching for QR codes
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');

                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                        content.style.display = 'none';
                    });

                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    const activeTab = document.getElementById(tabId);
                    if (activeTab) {
                        activeTab.classList.add('active');
                        activeTab.style.display = 'block';
                    }
                });
            });
        });
</script>
</body>
</html>
