<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Giudice - MVquiz</title>
    <link rel="stylesheet" href="../assets/css/core.css?v=18">
    <link rel="stylesheet" href="../assets/css/login.css?v=18">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=18">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>⚖️ MVquiz</h1>
            <h2>Login Giudice</h2>

            <div id="error-message" class="error hidden"></div>

            <form id="judge-login-form">
                <div class="form-group">
                    <label for="room_code">🔑 Codice Stanza:</label>
                    <input type="text" id="room_code" name="room_code" class="uppercase" maxlength="6" required autofocus placeholder="Esempio: ABC123">
                    <small class="form-helper-text">Chiedi il codice all'amministratore</small>
                </div>

                <button type="submit" class="btn btn-primary">⚖️ Entra come Giudice</button>
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
            }
        });

        document.getElementById('judge-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const roomCode = document.getElementById('room_code').value.trim().toUpperCase();
            const errorDiv = document.getElementById('error-message');

            errorDiv.classList.add('hidden');

            if (!roomCode) {
                errorDiv.textContent = 'Inserisci il codice stanza del giudice';
                errorDiv.classList.remove('hidden');
                return;
            }

            try {
                const response = await fetch('../src/api/api.php?endpoint=judge_login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        room_code: roomCode
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    errorDiv.textContent = result.error || 'Errore nel login';
                    errorDiv.classList.remove('hidden');
                }
            } catch (error) {
                errorDiv.textContent = 'Errore di connessione. Riprova.';
                errorDiv.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
