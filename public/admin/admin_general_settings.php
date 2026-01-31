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
                <input type="text" id="admin-username" name="admin_username" value="admin" disabled>
                <small>Username per accedere al pannello amministratore</small>
            </div>
            
            <div class="form-group">
                <label for="admin-new-password">Nuova Password:</label>
                <input type="password" id="admin-new-password" name="admin_new_password" placeholder="Lascia vuoto per mantenere la password attuale">
                <small>Lascia vuoto per mantenere la password attuale. Minimo 6 caratteri.</small>
            </div>
            
            <div class="form-group">
                <label for="admin-confirm-password">Conferma Password:</label>
                <input type="password" id="admin-confirm-password" name="admin_confirm_password" placeholder="Reinserisci la nuova password">
                <small>Reinserisci la nuova password</small>
            </div>
            
            <div id="credentials-message" class="credentials-message"></div>
            
            <div class="button-container">
                <button type="submit" class="btn btn-success">🔐 Aggiorna Credenziali</button>
            </div>
        </form>
    </div>
    
    <hr class="settings-divider">
    
    <div class="settings-group">
        <h3>Informazioni Applicazione</h3>
        
        <div class="setting-item">
            <label>Nome Applicazione:</label>
            <span class="setting-value">Marriage Game</span>
        </div>
        
        <div class="setting-item">
            <label>Versione:</label>
            <span class="setting-value">1.0.0</span>
        </div>
        
        <div class="setting-item">
            <label>Data Creazione:</label>
            <span class="setting-value"><?php echo date('d/m/Y'); ?></span>
        </div>
    </div>
    
    <hr class="settings-divider">
    
    <div class="settings-group">
        <h3>Database</h3>
        
        <div class="setting-item">
            <label>Host Database:</label>
            <span class="setting-value">localhost</span>
        </div>
        
        <div class="setting-item">
            <label>Database:</label>
            <span class="setting-value">marriage_game</span>
        </div>
        
        <div class="setting-item">
            <label>Stato Connessione:</label>
            <span class="setting-value status-ok">✓ Connesso</span>
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
        
        // Validation
        if (newPassword || confirmPassword) {
            if (newPassword.length < 6) {
                msg.innerHTML = '<div class="alert-warning">⚠️ La password deve avere almeno 6 caratteri</div>';
                return;
            }
            
            if (newPassword !== confirmPassword) {
                msg.innerHTML = '<div class="alert-error">✗ Le password non corrispondono</div>';
                return;
            }
        } else {
            msg.innerHTML = '<div class="alert-warning">ℹ️ Nessuna password inserita. Le credenziali rimangono invariate.</div>';
            setTimeout(() => msg.innerHTML = '', 3000);
            return;
        }
        
        const formData = new FormData(this);
        
        fetch('admin.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.text())
        .then(data => {
            msg.innerHTML = '<div class="alert-success">✓ Credenziali aggiornate correttamente. Effettua nuovamente il login.</div>';
            document.getElementById('admin-new-password').value = '';
            document.getElementById('admin-confirm-password').value = '';
            setTimeout(() => {
                window.location.href = 'logout.php?logout=1';
            }, 2000);
        })
        .catch(err => {
            msg.innerHTML = '<div class="alert-error">✗ Errore durante l\'aggiornamento delle credenziali</div>';
            setTimeout(() => msg.innerHTML = '', 3000);
        });
    });
</script>
