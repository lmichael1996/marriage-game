<!-- Tab: Impostazioni Generali -->
<div class="admin-section">
    <div class="tab-header">
        <h2>ℹ️ Impostazioni Generali</h2>
    </div>

    <div class="settings-group">
        <h3>👤 Credenziali Amministratore</h3>

        <form method="POST" action="admin.php" id="admin-credentials-form">
            <input type="hidden" name="action" value="update_admin_credentials">

            <div class="form-group">
                <label for="admin-username">Username:</label>
                <input type="text" id="admin-username" name="admin_username" value="<?php echo htmlspecialchars(authUsername() ?? 'admin'); ?>">
                <small>Utilizzato per effettuare il login al pannello di amministrazione.</small>
            </div>

            <div class="form-group">
                <label for="admin-new-password">Nuova Password:</label>
                <input type="password" id="admin-new-password" name="admin_new_password" placeholder="Lascia vuoto per mantenere la password attuale">
                <small>Minimo 6 caratteri. Lascia vuoto per non modificarla.</small>
            </div>

            <div class="form-group">
                <label for="admin-confirm-password">Conferma Password:</label>
                <input type="password" id="admin-confirm-password" name="admin_confirm_password" placeholder="Reinserisci la nuova password">
                <small>Deve corrispondere alla nuova password inserita sopra.</small>
            </div>

            <div id="credentials-message" class="credentials-message"></div>

            <div class="button-container">
                <button type="submit" class="btn btn-success">🔐 Aggiorna Credenziali</button>
            </div>
        </form>
    </div>

    <hr class="settings-divider" style="margin-top: 2rem;">

    <div class="settings-group">
        <h3>🎮 Impostazioni Punteggi</h3>

        <!-- Popup Risultato -->
        <div id="settings-result-popup">
            <div class="modal-content">
                <div class="modal-body">
                    <p id="settings-result-message"></p>
                    <div class="modal-actions-center">
                        <button type="button" class="btn btn-primary" onclick="closeSettingsPopup()">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <form id="settings-form" onsubmit="saveSettingsWithAjax(event)">
            <input type="hidden" name="action" value="save_settings">

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

            <div class="button-container">
                <button type="submit" class="btn btn-primary">Salva Impostazioni</button>
            </div>
        </form>
    </div>

    <hr class="settings-divider">

    <div class="settings-group settings-group-spaced">
        <h3>🗄️ Database</h3>

        <p class="description-text">Visualizza il contenuto di tutte le tabelle del database.</p>

        <div class="button-container">
            <a href="/public/utils/database.php" target="_blank" class="btn btn-secondary">📊 Visualizza Database</a>
        </div>
    </div>
</div>

<script>
    // Form submission for credentials
    document.getElementById('admin-credentials-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const newPassword = document.getElementById('admin-new-password').value;
        const confirmPassword = document.getElementById('admin-confirm-password').value;
        const msg = document.getElementById('credentials-message');
        const hasPassword = newPassword || confirmPassword;

        // Validation password solo se inserita
        if (hasPassword) {
            if (newPassword.length < 6) {
                msg.innerHTML = '<div class="alert-warning">⚠️ La password deve avere almeno 6 caratteri</div>';
                return;
            }

            if (newPassword !== confirmPassword) {
                msg.innerHTML = '<div class="alert-error">✗ Le password non corrispondono</div>';
                return;
            }
        }

        const formData = new FormData(this);

        fetch('admin.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.text())
        .then(data => {
            if (hasPassword) {
                msg.innerHTML = '<div class="alert-success">✓ Credenziali aggiornate. Effettua nuovamente il login.</div>';
                document.getElementById('admin-new-password').value = '';
                document.getElementById('admin-confirm-password').value = '';
                setTimeout(() => {
                    window.location.href = 'logout.php?role=admin';
                }, 2000);
            } else {
                msg.innerHTML = '<div class="alert-success">✓ Username aggiornato correttamente.</div>';
                setTimeout(() => msg.innerHTML = '', 3000);
            }
        })
        .catch(err => {
            msg.innerHTML = '<div class="alert-error">✗ Errore durante l\'aggiornamento delle credenziali</div>';
            setTimeout(() => msg.innerHTML = '', 3000);
        });
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

        popup.style.display = 'flex';
        popup.style.alignItems = 'center';
        popup.style.justifyContent = 'center';

        // Chiudi automaticamente dopo 3 secondi SOLO se c'è errore
        if (!isSuccess) {
            setTimeout(() => {
                closeSettingsPopup();
            }, 3000);
        }
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
    }
</script>
