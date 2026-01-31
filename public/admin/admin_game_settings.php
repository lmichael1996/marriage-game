<!-- Tab: Impostazioni -->
<div class="admin-section">
    <div class="tab-header">
        <h2>⚙️ Impostazioni Partita</h2>
    </div>
    
    <?php if (isset($_GET['success']) && $_GET['success'] === 'credentials_updated'): ?>
        <div class="success-message">Credenziali aggiornate con successo!</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form id="settings-form" method="POST" action="">
        <input type="hidden" name="action" value="save_settings">        <!-- Game Settings -->
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
            <button type="button" class="btn btn-secondary" id="btn-restore-settings">Ripristina</button>
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
    
    // Load settings function (restore button)
    function loadSettings() {
        location.reload();
    }
</script>
