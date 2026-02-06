<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Giocatore - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/mobile.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>🎮 Marriage Game</h1>
            <h2>Login Giocatore</h2>

            <div id="error-message" class="error hidden"></div>

            <form id="player-login-form">
                <div class="form-group">
                    <label for="username">👤 Nome Giocatore:</label>
                    <input type="text" id="username" name="username" required autofocus placeholder="Inserisci il tuo nome">
                </div>

                <div class="form-group">
                    <label for="room_code">🔑 Codice Stanza:</label>
                    <input type="text" id="room_code" name="room_code" class="uppercase" required placeholder="es: ABC123" maxlength="10">
                    <small class="form-helper-text">Chiedi il codice all'amministratore</small>
                </div>

                <button type="submit" class="btn btn-primary">🚀 Entra nel Gioco</button>
            </form>

            <div class="login-nav">
                <a href="login-admin.php" class="login-nav-link">
                    🔐 Sei un amministratore? Clicca qui
                </a>
            </div>
        </div>
    </div>

    <script>
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
                const response = await fetch('../src/api/api.php?endpoint=player_login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: username,
                        room_code: roomCode
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    errorDiv.textContent = result.error;
                    errorDiv.classList.remove('hidden');
                }
            } catch (error) {
                errorDiv.textContent = 'Errore di connessione. Riprova.';
                errorDiv.classList.remove('hidden');
                console.error('Login error:', error);
            }
        });

        // Auto-uppercase for room code
        document.getElementById('room_code').addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>
