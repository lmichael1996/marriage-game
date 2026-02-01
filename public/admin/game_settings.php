<!-- Tab: Impostazioni -->
<div class="admin-section">
    <div class="tab-header">
        <h2>⚙️ Impostazioni Partita</h2>
    </div>

    <!-- Popup Risultato -->
    <div id="settings-result-popup" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-body">
                <p id="settings-result-message"></p>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="button" class="btn btn-primary" onclick="closeSettingsPopup()">OK</button>
                </div>
            </div>
        </div>
    </div>

    <form id="settings-form" onsubmit="saveSettingsWithAjax(event)">
        <input type="hidden" name="action" value="save_settings">
        <!-- Game Settings -->
        <div class="form-section">
            <h3>Impostazioni Partita</h3>
            <div class="form-group">
                <label for="min_players">Numero minimo giocatori:</label>
                <input type="number" id="min_players" name="min_players"
                       min="1" max="100" value="<?php echo $gameSettings['min_players'] ?? 2; ?>" required>
                <small>Numero minimo di giocatori per iniziare una partita</small>
            </div>

            <div class="form-group">
                <label for="max_players">Numero massimo giocatori:</label>
                <input type="number" id="max_players" name="max_players"
                       min="1" max="100" value="<?php echo $gameSettings['max_players'] ?? 50; ?>" required>
                <small>Numero massimo di giocatori in una stanza</small>
            </div>

            <div class="form-group">
                <label for="auto_next_round">
                    <input type="checkbox" id="auto_next_round" name="auto_next_round" value="1"
                           <?php echo !empty($gameSettings['auto_next_round']) ? 'checked' : ''; ?>>
                    Passa automaticamente al round successivo
                </label>
                <small>Avvia automaticamente il round successivo dopo un tempo prestabilito</small>
            </div>

            <div class="form-group">
                <label for="auto_next_delay">Ritardo auto-avanzamento (secondi):</label>
                <input type="number" id="auto_next_delay" name="auto_next_delay"
                       min="3" max="30" value="<?php echo $gameSettings['auto_next_delay'] ?? 5; ?>"
                       <?php echo empty($gameSettings['auto_next_round']) ? 'disabled' : ''; ?>>
                <small>Tempo di attesa prima del round successivo (se auto-avanzamento attivo)</small>
            </div>
        </div>

        <!-- Scoring Settings -->
        <div class="form-section">
            <h3>Punteggi - Risposte Multiple</h3>
            <p><strong>Sistema punteggi stile Formula 1</strong>: i punti vengono assegnati in base alla posizione in classifica (primi 10).</p>

            <table class="points-table">
                <thead>
                    <tr>
                        <th>Posizione</th>
                        <th>Punti</th>
                        <th>Posizione</th>
                        <th>Punti</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><label for="points_mult_1st">1° Posto</label></td>
                        <td><input type="number" id="points_mult_1st" name="points_mult_1st" min="1" max="1000" value="<?php echo $gameSettings['points_mult_1st'] ?? 25; ?>" required></td>
                        <td><label for="points_mult_6th">6° Posto</label></td>
                        <td><input type="number" id="points_mult_6th" name="points_mult_6th" min="1" max="1000" value="<?php echo $gameSettings['points_mult_6th'] ?? 8; ?>" required></td>
                    </tr>
                    <tr>
                        <td><label for="points_mult_2nd">2° Posto</label></td>
                        <td><input type="number" id="points_mult_2nd" name="points_mult_2nd" min="1" max="1000" value="<?php echo $gameSettings['points_mult_2nd'] ?? 18; ?>" required></td>
                        <td><label for="points_mult_7th">7° Posto</label></td>
                        <td><input type="number" id="points_mult_7th" name="points_mult_7th" min="1" max="1000" value="<?php echo $gameSettings['points_mult_7th'] ?? 6; ?>" required></td>
                    </tr>
                    <tr>
                        <td><label for="points_mult_3rd">3° Posto</label></td>
                        <td><input type="number" id="points_mult_3rd" name="points_mult_3rd" min="1" max="1000" value="<?php echo $gameSettings['points_mult_3rd'] ?? 15; ?>" required></td>
                        <td><label for="points_mult_8th">8° Posto</label></td>
                        <td><input type="number" id="points_mult_8th" name="points_mult_8th" min="1" max="1000" value="<?php echo $gameSettings['points_mult_8th'] ?? 4; ?>" required></td>
                    </tr>
                    <tr>
                        <td><label for="points_mult_4th">4° Posto</label></td>
                        <td><input type="number" id="points_mult_4th" name="points_mult_4th" min="1" max="1000" value="<?php echo $gameSettings['points_mult_4th'] ?? 12; ?>" required></td>
                        <td><label for="points_mult_9th">9° Posto</label></td>
                        <td><input type="number" id="points_mult_9th" name="points_mult_9th" min="1" max="1000" value="<?php echo $gameSettings['points_mult_9th'] ?? 2; ?>" required></td>
                    </tr>
                    <tr>
                        <td><label for="points_mult_5th">5° Posto</label></td>
                        <td><input type="number" id="points_mult_5th" name="points_mult_5th" min="1" max="1000" value="<?php echo $gameSettings['points_mult_5th'] ?? 10; ?>" required></td>
                        <td><label for="points_mult_10th">10° Posto</label></td>
                        <td><input type="number" id="points_mult_10th" name="points_mult_10th" min="1" max="1000" value="<?php echo $gameSettings['points_mult_10th'] ?? 1; ?>" required></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="form-section">
            <h3>Punteggi - Vero o Falso</h3>
            <p><strong>Primi 10 classificati</strong> (più facile, punteggi ridotti).</p>

            <table class="points-table">
                <thead>
                    <tr>
                        <th>Posizione</th>
                        <th>Punti</th>
                        <th>Posizione</th>
                        <th>Punti</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><label for="points_tf_1st">1° Posto</label></td>
                        <td><input type="number" id="points_tf_1st" name="points_tf_1st" min="1" max="1000" value="<?php echo $gameSettings['points_tf_1st'] ?? 20; ?>" required></td>
                        <td><label for="points_tf_6th">6° Posto</label></td>
                        <td><input type="number" id="points_tf_6th" name="points_tf_6th" min="1" max="1000" value="<?php echo $gameSettings['points_tf_6th'] ?? 6; ?>" required></td>
                    </tr>
                    <tr>
                        <td><label for="points_tf_2nd">2° Posto</label></td>
                        <td><input type="number" id="points_tf_2nd" name="points_tf_2nd" min="1" max="1000" value="<?php echo $gameSettings['points_tf_2nd'] ?? 15; ?>" required></td>
                        <td><label for="points_tf_7th">7° Posto</label></td>
                        <td><input type="number" id="points_tf_7th" name="points_tf_7th" min="1" max="1000" value="<?php echo $gameSettings['points_tf_7th'] ?? 5; ?>" required></td>
                    </tr>
                    <tr>
                        <td><label for="points_tf_3rd">3° Posto</label></td>
                        <td><input type="number" id="points_tf_3rd" name="points_tf_3rd" min="1" max="1000" value="<?php echo $gameSettings['points_tf_3rd'] ?? 12; ?>" required></td>
                        <td><label for="points_tf_8th">8° Posto</label></td>
                        <td><input type="number" id="points_tf_8th" name="points_tf_8th" min="1" max="1000" value="<?php echo $gameSettings['points_tf_8th'] ?? 3; ?>" required></td>
                    </tr>
                    <tr>
                        <td><label for="points_tf_4th">4° Posto</label></td>
                        <td><input type="number" id="points_tf_4th" name="points_tf_4th" min="1" max="1000" value="<?php echo $gameSettings['points_tf_4th'] ?? 10; ?>" required></td>
                        <td><label for="points_tf_9th">9° Posto</label></td>
                        <td><input type="number" id="points_tf_9th" name="points_tf_9th" min="1" max="1000" value="<?php echo $gameSettings['points_tf_9th'] ?? 2; ?>" required></td>
                    </tr>
                    <tr>
                        <td><label for="points_tf_5th">5° Posto</label></td>
                        <td><input type="number" id="points_tf_5th" name="points_tf_5th" min="1" max="1000" value="<?php echo $gameSettings['points_tf_5th'] ?? 8; ?>" required></td>
                        <td><label for="points_tf_10th">10° Posto</label></td>
                        <td><input type="number" id="points_tf_10th" name="points_tf_10th" min="1" max="1000" value="<?php echo $gameSettings['points_tf_10th'] ?? 1; ?>" required></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="form-section">
            <h3>Punteggi - Tocca per Primo</h3>
            <p><strong>Solo il primo che clicca ottiene punti</strong>, tutti gli altri: 0 punti.</p>

            <div class="form-group">
                <label for="points_clickfirst">Punti per il primo giocatore:</label>
                <input type="number" id="points_clickfirst" name="points_clickfirst"
                       min="1" max="1000" value="<?php echo $gameSettings['points_clickfirst'] ?? 50; ?>" required class="input-narrow">
                <small>Il primo giocatore che clicca ottiene questi punti (gli altri: 0 punti)</small>
            </div>
        </div>

        <!-- Display Settings -->
        <div class="form-section">
            <h3>Visualizzazione</h3>
            <div class="form-group">
                <label for="show_leaderboard">
                    <input type="checkbox" id="show_leaderboard" name="show_leaderboard" value="1"
                           <?php echo !empty($gameSettings['show_leaderboard']) ? 'checked' : ''; ?>>
                    Mostra classifica in tempo reale
                </label>
            </div>

            <div class="form-group">
                <label for="show_correct_answer">
                    <input type="checkbox" id="show_correct_answer" name="show_correct_answer" value="1"
                           <?php echo !empty($gameSettings['show_correct_answer']) ? 'checked' : ''; ?>>
                    Mostra risposta corretta dopo ogni round
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salva Impostazioni</button>
        </div>
    </form>
</div>

<script>
    // Settings Tab - JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Enable/disable auto-next delay based on checkbox
        const autoNextCheckbox = document.getElementById('auto_next_round');
        const autoNextDelay = document.getElementById('auto_next_delay');

        if (autoNextCheckbox && autoNextDelay) {
            autoNextCheckbox.addEventListener('change', function() {
                autoNextDelay.disabled = !this.checked;
            });

            // Set initial state
            autoNextDelay.disabled = !autoNextCheckbox.checked;
        }
    });

    /**
     * Salva le impostazioni via AJAX
     */
    function saveSettingsWithAjax(event) {
        event.preventDefault();

        const form = document.getElementById('settings-form');
        const formData = new FormData(form);

        fetch('admin.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Errore HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Mostra popup di successo
                showSettingsResultPopup('✅ Impostazioni aggiornate con successo!', true);

                // Aggiorna i valori del form con le nuove impostazioni
                updateFormValues(data.settings);
            } else {
                // Mostra errore
                showSettingsResultPopup('❌ Errore nel salvataggio: ' + (data.message || 'Errore sconosciuto'), false);
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            showSettingsResultPopup('❌ Errore di comunicazione con il server: ' + error.message, false);
        });

        return false;
    }

    /**
     * Mostra il popup con il risultato del salvataggio
     */
    function showSettingsResultPopup(message, isSuccess) {
        const popup = document.getElementById('settings-result-popup');
        const messageElement = document.getElementById('settings-result-message');

        messageElement.textContent = message;
        messageElement.style.color = isSuccess ? '#27ae60' : '#e74c3c';
        messageElement.style.fontWeight = 'bold';
        messageElement.style.fontSize = '16px';
        messageElement.style.textAlign = 'center';

        popup.style.display = 'flex';

        // Chiudi automaticamente dopo 3 secondi
        setTimeout(() => {
            closeSettingsPopup();
        }, 3000);
    }

    /**
     * Chiude il popup dei risultati
     */
    function closeSettingsPopup() {
        const popup = document.getElementById('settings-result-popup');
        popup.style.display = 'none';
    }

    /**
     * Aggiorna i valori del form con le nuove impostazioni
     */
    function updateFormValues(settings) {
        // Aggiorna campi normali
        const fieldMappings = {
            'min_players': 'min_players',
            'max_players': 'max_players',
            'auto_next_delay': 'auto_next_delay',
            'points_mult_1st': 'points_mult_1st',
            'points_mult_2nd': 'points_mult_2nd',
            'points_mult_3rd': 'points_mult_3rd',
            'points_mult_4th': 'points_mult_4th',
            'points_mult_5th': 'points_mult_5th',
            'points_mult_6th': 'points_mult_6th',
            'points_mult_7th': 'points_mult_7th',
            'points_mult_8th': 'points_mult_8th',
            'points_mult_9th': 'points_mult_9th',
            'points_mult_10th': 'points_mult_10th',
            'points_tf_1st': 'points_tf_1st',
            'points_tf_2nd': 'points_tf_2nd',
            'points_tf_3rd': 'points_tf_3rd',
            'points_tf_4th': 'points_tf_4th',
            'points_tf_5th': 'points_tf_5th',
            'points_tf_6th': 'points_tf_6th',
            'points_tf_7th': 'points_tf_7th',
            'points_tf_8th': 'points_tf_8th',
            'points_tf_9th': 'points_tf_9th',
            'points_tf_10th': 'points_tf_10th',
            'points_clickfirst': 'points_clickfirst'
        };

        // Aggiorna i campi di input
        Object.keys(fieldMappings).forEach(key => {
            const element = document.getElementById(fieldMappings[key]);
            if (element && settings[key] !== undefined) {
                element.value = settings[key];
            }
        });

        // Aggiorna i checkbox
        const checkboxMappings = {
            'auto_next_round': 'auto_next_round',
            'show_leaderboard': 'show_leaderboard',
            'show_correct_answer': 'show_correct_answer'
        };

        Object.keys(checkboxMappings).forEach(key => {
            const element = document.getElementById(checkboxMappings[key]);
            if (element) {
                element.checked = settings[key] == 1 || settings[key] === '1' || settings[key] === true;
            }
        });

        // Aggiorna lo stato del campo auto_next_delay in base al checkbox
        const autoNextCheckbox = document.getElementById('auto_next_round');
        const autoNextDelay = document.getElementById('auto_next_delay');
        if (autoNextCheckbox && autoNextDelay) {
            autoNextDelay.disabled = !autoNextCheckbox.checked;
        }
    }

    // Load settings function (restore button)
    function loadSettings() {
        location.reload();
    }
</script>
