<?php
require_once __DIR__ . '/../src/config/auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Player.php';
require_once __DIR__ . '/../src/models/Room.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin.php');
    } else {
        header('Location: player.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $roomCode = $_POST['room_code'] ?? '';
    
    // Guest login with room code (no password required)
    if ($username && $roomCode && !$password) {
        // Verify room code exists and is active
        if (!Room::verifyRoomCode($roomCode)) {
            $error = 'Codice stanza non valido o stanza non attiva';
        } else {
            // Create new player associated with this room
            $playerModel = new Player();
            $playerId = $playerModel->createPlayer($username, $roomCode);
            
            if ($playerId) {
                $_SESSION['player_id'] = $playerId;
                $_SESSION['username'] = $username;
                $_SESSION['room_code'] = strtoupper($roomCode);
                
                header('Location: player.php');
                exit();
            } else {
                $error = 'Errore durante la creazione del player';
            }
        }
    }
    // Standard admin login
    elseif ($username && $password) {
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // Admin login = no room_code
            
            header('Location: admin.php');
            exit();
        } else {
            $error = 'Username o password non validi';
        }
    } else {
        $error = 'Inserisci username e codice stanza, oppure username e password per admin';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Marriage Game</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1>Marriage Game</h1>
            <h2>Login</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
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
</body>
</html>
