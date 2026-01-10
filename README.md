# Marriage Game

Un gioco interattivo con sistema di login, pannello admin e interfaccia giocatore con timer di 10 secondi.

## 🎯 Caratteristiche

- **Sistema di Login**: Autenticazione per admin e giocatori
- **Pannello Admin**: 
  - Creazione e gestione round
  - Impostazione risposta corretta (4 opzioni)
  - Avvio e chiusura round in tempo reale
- **Interfaccia Giocatore**:
  - 4 pulsanti di risposta
  - Timer di 10 secondi per round
  - Aggiornamento automatico stato gioco
  - Feedback immediato dopo la risposta

## 📋 Requisiti

- PHP 7.4+
- MySQL 5.7+
- Server web (Apache/Nginx)

## 📁 Struttura del Progetto

```
marriage-game/
├── index.php                 # Entry point (redirect a public/)
├── database.sql             # Schema database
├── .htaccess                # Configurazione Apache
├── README.md
│
├── src/                     # Backend - API e logica
│   ├── config/
│   │   ├── database.php    # Configurazione DB
│   │   └── auth.php        # Gestione autenticazione
│   ├── models/             # Modelli dati
│   │   ├── User.php
│   │   ├── Round.php
│   │   └── PlayerAnswer.php
│   └── api/                # Endpoint API
│       ├── game.php        # Stato del gioco
│       ├── answer.php      # Invio risposte
│       └── leaderboard.php # Classifica
│
└── public/                  # Frontend - Pagine pubbliche
    ├── css/
    │   └── style.css
    ├── login.php
    ├── logout.php
    ├── admin.php
    └── player.php
```

## 🚀 Installazione

1. **Clona o copia i file** nella directory del web server

2. **Configura il database**:
   - Apri `src/config/database.php` e modifica le credenziali se necessario:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'marriage_game');
   ```

3. **Importa il database**:
   ```bash
   mysql -u root -p < database.sql
   ```
   
   Oppure tramite phpMyAdmin, importa il file `database.sql`

4. **Configura il web server**:
   
   **Apache (.htaccess già incluso nella root):**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ public/$1 [L]
   ```
   
   **Nginx:**
   ```nginx
   location / {
       try_files $uri $uri/ /public/$uri /public/index.php?$args;
   }
   ```
   
   **PHP Built-in Server:**
   ```bash
   php -S localhost:8000
   ```

5. **Accedi all'applicazione**:
   - Apri il browser su `http://localhost:8000`

## 🔑 Credenziali di Default

**Admin:**
- Username: `admin`
- Password: `admin123`

## 📖 Come Usare

### Per l'Admin:

1. Accedi con le credenziali admin
2. Crea un nuovo round specificando:
   - Numero del round
   - Domanda (opzionale)
   - Risposta corretta (1-4)
3. Clicca "Avvia Round" per rendere il round attivo
4. I giocatori avranno 10 secondi per rispondere
5. Clicca "Chiudi Round" per terminarlo
6. Puoi modificare la risposta corretta anche dopo

### Per i Giocatori:

1. Crea un account giocatore o accedi
2. Attendi che l'admin avvii un round
3. Quando il round inizia, hai 10 secondi per scegliere una delle 4 opzioni
4. Riceverai un feedback immediato dopo aver risposto
5. Attendi il prossimo round

## 🏗️ Architettura

### Backend (src/)

- **config/**: Configurazioni per database e autenticazione
- **models/**: Classi PHP per gestire logica dei dati (User, Round, PlayerAnswer)
- **api/**: Endpoint REST API per comunicazione client-server

### Frontend (public/)

- **Pagine PHP**: Login, admin panel, player interface
- **CSS**: Styling responsive e moderno
- **JavaScript**: Gestione real-time del gioco (polling, timer, UI)

## 🔌 API Endpoints

### `src/api/game.php`
- `GET ?action=get_game_state` - Ottiene lo stato corrente del gioco

### `src/api/answer.php`
- `POST ?action=submit` - Invia risposta giocatore
  ```json
  {
    "round_id": 1,
    "answer": 2,
    "time_taken": 5.23
  }
  ```

### `src/api/leaderboard.php`
- `GET` - Ottiene classifica giocatori

## 🗄️ Database

Il database include le seguenti tabelle:

- `users` - Utenti (admin e giocatori)
- `rounds` - Round di gioco
- `player_answers` - Risposte dei giocatori
- `game_state` - Stato corrente del gioco

## ⚙️ Note Tecniche

- Il sistema controlla lo stato del gioco ogni 2 secondi tramite polling
- Il timer di 10 secondi è gestito lato client
- Le risposte vengono validate sul server
- I round chiudono automaticamente dopo 10 secondi dall'avvio
- Architettura MVC con separazione backend/frontend

## 🔒 Sicurezza

- Le password sono hashate con `password_hash()`
- Protezione SQL injection con prepared statements
- Validazione sessioni per tutte le pagine protette
- Separazione permessi admin/giocatore
- Separazione logica backend (src/) e frontend (public/)

## 🎨 Personalizzazione

- Modifica il timer in `public/player.php` (variabile `timeLeft`)
- Cambia i colori in `public/css/style.css`
- Aggiungi più opzioni di risposta modificando i modelli e le interfacce
- Estendi le API in `src/api/` per nuove funzionalità

## 🐛 Troubleshooting

**Errore di connessione al database:**
- Verifica le credenziali in `src/config/database.php`
- Assicurati che MySQL sia in esecuzione
- Controlla che il database `marriage_game` sia stato creato

**Errori 404 sulle API:**
- Verifica i path relativi nelle chiamate fetch
- Controlla la configurazione del web server

**Il timer non funziona:**
- Verifica che JavaScript sia abilitato nel browser
- Controlla la console del browser per errori

**I giocatori non vedono il round attivo:**
- Verifica che l'admin abbia cliccato "Avvia Round"
- Controlla che il round non sia già scaduto (>10 secondi)
- Verifica le chiamate API nella console del browser

## 📝 Licenza

Progetto open source - Libero per uso personale e commerciale.
