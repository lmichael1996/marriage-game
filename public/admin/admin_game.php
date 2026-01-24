<!-- Tab: Partita -->
<div class="admin-section">
    <h2>Gestione Partita</h2>
    
    <!-- Step 1: Select Question Set -->
    <div class="game-step" id="step-select-set">
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
            <button class="btn btn-secondary" id="btn-search-game-sets">Cerca</button>
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
                        <tr>
                            <td><?php echo htmlspecialchars($set['set_name']); ?></td>
                            <td><?php echo htmlspecialchars($set['set_description'] ?? '-'); ?></td>
                            <td><?php echo $set['total_rounds']; ?></td>
                            <td>
                                <button class="btn btn-primary btn-select-game-set" data-set-id="<?php echo $set['id']; ?>" data-set-name="<?php echo htmlspecialchars($set['set_name']); ?>">
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
    <div class="game-step hidden" id="step-room">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="margin: 0;">2. Avvia Stanza</h3>
            <button class="btn btn-secondary" id="btn-back-to-sets" style="font-size: 0.9rem;">
                ← Cambia Set
            </button>
        </div>
        <p>Crea una stanza per permettere ai giocatori di connettersi.</p>
        <div id="set-selected-info">
            <p><strong>Set selezionato:</strong> <span id="selected-set-display"></span></p>
        </div>
        <div id="room-control">
            <button class="btn btn-success" id="create-room-btn">
                Avvia Stanza
            </button>
            <div id="room-info" class="hidden">
                <div class="info-box">
                    <p><strong>🎮 Stanza attiva</strong></p>
                    <p>Codice stanza: <strong><span id="room-code">------</span></strong></p>
                    <p>I giocatori possono ora connettersi utilizzando questo codice.</p>
                </div>
                <button class="btn btn-danger" id="close-room-btn">
                    Chiudi Stanza
                </button>
            </div>
        </div>
    </div>
    
    <!-- Step 3: Connected Devices -->
    <div class="game-step hidden" id="step-devices">
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
        <div id="game-start-section">
            <div id="final-info">
                <p><strong>Set selezionato:</strong> <span id="selected-set-name-final"></span></p>
                <p><strong>Giocatori connessi:</strong> <span id="connected-count">0</span></p>
                <button class="btn btn-success btn-margin-top" id="start-game-btn" disabled>
                    Avvia Partita
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Game Tab - JavaScript
    let selectedGameSetId = null;
    let selectedGameSetName = '';
    let roomActive = false;
    let roomCode = '';
    let devicesInterval;
    
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
    
    // Back to set selection
    function backToSetSelection() {
        const step1 = document.getElementById('step-select-set');
        const step2 = document.getElementById('step-room');
        const step3 = document.getElementById('step-devices');
        
        // Hide step 3 if visible
        if (!step3.classList.contains('hidden')) {
            step3.classList.add('hidden');
            step3.style.opacity = '';
            step3.style.transform = '';
        }
        
        // Animate step 2 out
        step2.style.opacity = '0';
        step2.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            step2.classList.add('hidden');
            step2.style.opacity = '';
            step2.style.transform = '';
            
            // Show step 1
            step1.classList.remove('hidden');
            
            // Force reflow
            step1.offsetHeight;
            
            // Animate step 1 in
            setTimeout(() => {
                step1.style.opacity = '1';
                step1.style.transform = 'translateY(0)';
            }, 50);
        }, 400);
        
        // Clear selection
        selectedGameSetId = null;
        selectedGameSetName = '';
        
        // Remove row highlight
        document.querySelectorAll('#game-sets-table-body tr').forEach(row => {
            row.classList.remove('selected-row');
        });
    }
    
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
        
        // Hide step 1 with animation
        const step1 = document.getElementById('step-select-set');
        step1.style.opacity = '0';
        step1.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            step1.classList.add('hidden');
            
            // Show step 2 with animation
            const step2 = document.getElementById('step-room');
            step2.classList.remove('hidden');
            
            // Force reflow
            step2.offsetHeight;
            
            setTimeout(() => {
                step2.style.opacity = '1';
                step2.style.transform = 'translateY(0)';
            }, 50);
        }, 400);
        
        // Update set name displays
        document.getElementById('selected-set-display').textContent = setName;
        document.getElementById('selected-set-name-final').textContent = setName;
        
        // Enable start button if there are connected devices
        updateStartButton();
    }
    
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
            console.log('Create room response:', data);
            if (data.success) {
                // Save room code from server
                roomCode = data.room_code;
                
                // Update UI
                document.getElementById('create-room-btn').style.display = 'none';
                document.getElementById('room-info').classList.remove('hidden');
                document.getElementById('room-code').textContent = roomCode;
                
                // Disable back button
                const btnBackToSets = document.getElementById('btn-back-to-sets');
                if (btnBackToSets) {
                    btnBackToSets.disabled = true;
                    btnBackToSets.style.opacity = '0.5';
                    btnBackToSets.style.cursor = 'not-allowed';
                    btnBackToSets.title = 'Chiudi la stanza prima di cambiare set';
                }
                
                // Show step 3 with animation
                const step3 = document.getElementById('step-devices');
                step3.classList.remove('hidden');
                step3.style.opacity = '0';
                step3.style.transform = 'translateY(20px)';
                
                // Force reflow
                step3.offsetHeight;
                
                setTimeout(() => {
                    step3.style.opacity = '1';
                    step3.style.transform = 'translateY(0)';
                }, 50);
                
                // Start device polling
                if (!devicesInterval) {
                    updateConnectedDevices();
                    devicesInterval = setInterval(updateConnectedDevices, 1000);
                }
            } else {
                console.error('Room creation failed:', data);
                alert('Errore nella creazione della stanza: ' + (data.error || 'Errore sconosciuto'));
            }
        })
        .catch(error => {
            console.error('Error creating room:', error);
            alert('Errore nella creazione della stanza: ' + error.message);
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
                    document.getElementById('room-info').classList.add('hidden');
                    
                    // Re-enable back button
                    const btnBackToSets = document.getElementById('btn-back-to-sets');
                    if (btnBackToSets) {
                        btnBackToSets.disabled = false;
                        btnBackToSets.style.opacity = '1';
                        btnBackToSets.style.cursor = 'pointer';
                        btnBackToSets.title = '';
                    }
                    
                    // Hide next steps
                    document.getElementById('step-devices').classList.add('hidden');
                    
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
            // Call API to set room status to 'active'
            fetch('../src/api/api.php?endpoint=start_room', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to game admin page
                    window.location.href = 'game_admin.php';
                } else {
                    alert('Errore nell\'avvio della partita: ' + (data.error || 'Errore sconosciuto'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore nella comunicazione con il server');
            });
        }
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
    
    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Search on Enter key
        const searchGameInput = document.getElementById('search-game-sets');
        if (searchGameInput) {
            searchGameInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchGameSets();
                }
            });
        }
        
        // Search button
        const btnSearchGameSets = document.getElementById('btn-search-game-sets');
        if (btnSearchGameSets) {
            btnSearchGameSets.addEventListener('click', searchGameSets);
        }
        
        // Back to set selection button
        const btnBackToSets = document.getElementById('btn-back-to-sets');
        if (btnBackToSets) {
            btnBackToSets.addEventListener('click', backToSetSelection);
        }
        
        // Select game set buttons (event delegation)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-select-game-set')) {
                const btn = e.target.closest('.btn-select-game-set');
                const setId = parseInt(btn.getAttribute('data-set-id'));
                const setName = btn.getAttribute('data-set-name');
                selectGameSet(setId, setName);
            }
        });
        
        // Create room button
        const createRoomBtn = document.getElementById('create-room-btn');
        if (createRoomBtn) {
            createRoomBtn.addEventListener('click', createRoom);
        }
        
        // Close room button
        const closeRoomBtn = document.getElementById('close-room-btn');
        if (closeRoomBtn) {
            closeRoomBtn.addEventListener('click', closeRoom);
        }
        
        // Start game button
        const startGameBtn = document.getElementById('start-game-btn');
        if (startGameBtn) {
            startGameBtn.addEventListener('click', startGame);
        }
    });
</script>
