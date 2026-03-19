<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Giocatore - MVquiz</title>
    <link rel="stylesheet" href="../assets/css/core.css?v=22">
    <link rel="stylesheet" href="../assets/css/login.css?v=22">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=22">
    <script src="../assets/js/api.js?v=23"></script>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>🎮 MVquiz</h1>
            <h2>Login Giocatore</h2>

            <div id="error-message" class="error hidden"></div>

            <form id="player-login-form">
                <div class="form-group">
                    <label for="username">👤 Nome Giocatore:</label>
                    <input type="text" id="username" name="username" maxlength="15" required autofocus placeholder="Inserisci il tuo nome giocatore">
                </div>

                <div class="form-group">
                    <label for="room_code">🔑 Codice Stanza:</label>
                    <input type="text" id="room_code" name="room_code" class="uppercase" required maxlength="6" placeholder="Esempio: ABC123">
                    <small class="form-helper-text">Chiedi il codice all'amministratore</small>
                </div>

                <button type="submit" class="btn btn-primary">🚀 Entra nel Gioco</button>
            </form>

            <div class="login-nav">
                <p><a href="../index.html" class="login-nav-link">← Torna alla Home</a></p>
            </div>
        </div>
    </div>

    <script>
        // Check if code parameter is in URL
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const codeFromUrl = urlParams.get('code');

            if (codeFromUrl) {
                const roomCodeInput = document.getElementById('room_code');
                roomCodeInput.value = codeFromUrl.toUpperCase();
                roomCodeInput.disabled = true;

                // Optional: focus on username field
                document.getElementById('username').focus();
            }
        });

        // Handle form submission
        document.getElementById('player-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const roomCode = document.getElementById('room_code').value.trim().toUpperCase();
            const errorDiv = document.getElementById('error-message');

            errorDiv.classList.add('hidden');

            if (!username || !roomCode) {
                errorDiv.textContent = 'Inserisci nome e codice stanza';
                errorDiv.classList.remove('hidden');
                return;
            }

            try {
                const result = await api('player_login', {
                    username: username,
                    room_code: roomCode
                });

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    errorDiv.textContent = result.error || 'Errore nel login';
                    errorDiv.classList.remove('hidden');
                }
            } catch {
                errorDiv.textContent = 'Errore di connessione. Riprova.';
                errorDiv.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
