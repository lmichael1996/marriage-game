<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Marriage Game</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>Marriage Game</h1>
            <h2>Login</h2>
            
            <div id="error-message" class="error" style="display: none;"></div>
            
            <form id="login-form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="room_code">Codice Stanza:</label>
                    <input type="text" id="room_code" name="room_code" placeholder="Inserisci codice" style="text-transform: uppercase;">
                </div>
                
                <div class="form-group">
                    <label for="password">Password (solo admin):</label>
                    <input type="password" id="password" name="password" placeholder="Opzionale per giocatori">
                </div>
                
                <button type="submit" class="btn btn-primary">Accedi</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const roomCode = document.getElementById('room_code').value;
            const errorDiv = document.getElementById('error-message');
            
            errorDiv.style.display = 'none';
            
            try {
                const response = await fetch('../src/api/api.php?endpoint=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        room_code: roomCode
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    errorDiv.textContent = result.error;
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'Errore di connessione. Riprova.';
                errorDiv.style.display = 'block';
                console.error('Login error:', error);
            }
        });
    </script>
</body>
</html>
