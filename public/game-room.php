<?php
session_start();
require_once __DIR__ . '/../src/utils/auth.php';
require_once __DIR__ . '/../src/services/AdminService.php';
require_once __DIR__ . '/../src/services/QuestionService.php';

// Check admin access
// TODO: Debug - temporarily disabled
// requireAdmin();

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
    <link rel="stylesheet" href="../assets/css/tab.css">
    <link rel="stylesheet" href="../assets/css/views.css">
    <link rel="stylesheet" href="../assets/css/settings-group.css">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <link rel="stylesheet" href="../assets/css/game-room.css">
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
        let selectedGameSetIsSaved = true;
        let roomActive = false;
        let roomCode = '';
        let devicesInterval = null;
        let minPlayers = 1; // Valore fisso dopo rimozione dal database

        // Initialize from PHP data if set_id was provided via URL
        <?php if ($selectedSet): ?>
        (function() {
            selectedGameSetId = <?php echo (int)$selectedSet['id']; ?>;
            selectedGameSetName = <?php echo json_encode($selectedSet['set_name']); ?>;
            selectedGameSetIsSaved = <?php echo (int)($selectedSet['is_saved'] ?? 1); ?> === 1;
        })();
        <?php endif; ?>

        // Holds server-provided QR image (data URI) when available
        let serverQrDataUri = null;

        // Generate QR code for room code. If dataUri is provided (or serverQrDataUri exists),
        // render the image element from the data URI so it matches exactly the server/PDF QR.
        function generateQRCode(code, dataUri = null) {
            const container = document.getElementById('qr-code-container');
            container.innerHTML = ''; // Clear previous QR

            const effectiveDataUri = dataUri || serverQrDataUri;
            if (effectiveDataUri) {
                const img = document.createElement('img');
                img.src = effectiveDataUri;
                img.style.border = '1px solid #ccc';
                img.style.display = 'block';
                img.style.margin = '10px auto';
                img.style.width = '350px';
                img.style.height = '350px';
                container.appendChild(img);
                return;
            }

            // Fallback: generate the QR client-side using QRCode.js from the URL
            // Genera l'URL del QR code (stesso URL che usa il PDF)
            // NON codificare - la libreria lo farà
            const baseUrl = 'http://151.21.203.214:9000/public/login-player.php';
            const qrUrl = baseUrl + '?code=' + code;

            // Usa la libreria QRCode.js per generare il QR direttamente nel container
            try {
                new QRCode(container, {
                    text: qrUrl,
                    width: 350,
                    height: 350,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.L
                });

                // Aggiungi styling al canvas
                const canvas = container.querySelector('canvas');
                if (canvas) {
                    canvas.style.border = '1px solid #ccc';
                    canvas.style.display = 'block';
                    canvas.style.margin = '10px auto';
                }
            } catch (error) {
                console.error('Errore nella generazione del QR:', error);
            }
        }

        // Generate PDF with QR code and room code
        function downloadPDF() {
            const roomCode = document.getElementById('room-code').textContent;

            if (!roomCode) {
                alert('Room code non disponibile.');
                return;
            }

            // Richiedi il PDF all'API - l'API genererà il QR coerente
            fetch('/src/api/api.php?endpoint=generate_pdf', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_code: roomCode
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
                    selectedGameSetIsSaved = selectedSet.is_saved === 1;

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
                }),
                credentials: 'include'
            })
            .then(response => {
                console.log('Create room response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Create room response data:', data);
                if (data.success) {
                    roomCode = data.room_code;

                    // Show room info
                    document.getElementById('btn-create-room').style.display = 'none';
                    document.getElementById('room-info').classList.remove('hidden');
                    document.getElementById('room-code').textContent = roomCode;

                    // Carica l'immagine QR direttamente dalla cartella public/qrcodes/
                    console.log('QR image URL from server:', data.qr_image_url);
                    if (data.qr_image_url) {
                        const container = document.getElementById('qr-code-container');
                        container.innerHTML = ''; // Clear
                        const img = document.createElement('img');
                        // Costruisci il percorso assoluto dal browser (es. /qrcodes/room_ABC.jpg)
                        const imgSrc = '/' + data.qr_image_url;
                        console.log('Setting image src to:', imgSrc);
                        img.src = imgSrc;
                        img.style.border = '1px solid #ccc';
                        img.style.display = 'block';
                        img.style.margin = '10px auto';
                        img.style.width = '350px';
                        img.style.height = '350px';
                        container.appendChild(img);
                    } else {
                        // Fallback: genera il QR nel browser se il server non l'ha fornito
                        console.log('No qr_image_url, generating QR client-side');
                        generateQRCode(roomCode);
                    }

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
                    roomActive = false;
                    alert('Errore nella creazione della stanza: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                roomActive = false;
                console.error('Create room error:', error);
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
