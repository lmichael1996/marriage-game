<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - MVquiz</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>🔐 MVquiz</h1>
            <h2>Login Admin</h2>

            <div id="error-message" class="error hidden"></div>

            <form id="admin-login-form">
                <div class="form-group">
                    <label for="username">👤 Username Admin:</label>
                    <input type="text" id="username" name="username" required autofocus placeholder="Inserisci username">
                </div>

                <div class="form-group">
                    <label for="password">🔒 Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Inserisci password">
                </div>

                <button type="submit" class="btn btn-primary">Accedi</button>
            </form>

            <div class="login-nav">
                <p><a href="../index.html" class="login-nav-link">← Torna alla Home</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error-message');

            errorDiv.classList.add('hidden');

            if (!username || !password) {
                errorDiv.textContent = 'Inserisci username e password';
                errorDiv.classList.remove('hidden');
                return;
            }

            try {
                const response = await fetch('../src/api/api.php?endpoint=admin_login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password
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
