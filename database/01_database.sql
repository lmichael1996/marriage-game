-- Database setup for Marriage Game
CREATE DATABASE IF NOT EXISTS marriage_game;

USE marriage_game;

-- Users table (for admin login only)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    user_password VARCHAR(255) NOT NULL
);

-- Game settings
CREATE TABLE IF NOT EXISTS game_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value INT
);

-- Categories table (for question categorization and color coding)
CREATE TABLE IF NOT EXISTS question_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    color VARCHAR(20) DEFAULT '#6c757d'
);

-- Questions table (stores individual questions)
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT,
    option1 TEXT,
    option2 TEXT,
    option3 TEXT,
    option4 TEXT,
    category_id INT DEFAULT 1,
    correct_answer INT CHECK (
        correct_answer BETWEEN 1
        AND 4
    ),
    timer INT DEFAULT 10,
    round_type ENUM('multiple', 'truefalse', 'clickfirst') DEFAULT 'multiple'
);

-- Question Sets table
CREATE TABLE IF NOT EXISTS qsets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    set_name VARCHAR(255) UNIQUE NOT NULL,
    set_description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Junction table for Many-to-Many relationship between qsets and questions
CREATE TABLE IF NOT EXISTS qset_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qset_id INT NOT NULL,
    question_id INT NOT NULL,
    order_in_set INT DEFAULT 0,
    FOREIGN KEY (qset_id) REFERENCES qsets(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE (qset_id, question_id)
);

-- Rooms table (stores active game rooms)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_player VARCHAR(10) UNIQUE NOT NULL,
    code_judge VARCHAR(10) UNIQUE NOT NULL,
    qset_id INT DEFAULT NULL,
    -- Store QR code as data URI (base64 encoded image)
    qr_uri_player LONGTEXT DEFAULT NULL,
    qr_uri_judge LONGTEXT DEFAULT NULL,
    status_room ENUM('open', 'running', 'closed', 'cancelled') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (qset_id) REFERENCES qsets(id) ON DELETE CASCADE
);

-- Players table (for game participants associated with a room)
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    room_id INT NOT NULL,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE (username, room_id)
);

CREATE TABLE IF NOT EXISTS judges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Rounds table (stores game rounds with associated questions from qset_questions)
CREATE TABLE IF NOT EXISTS rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    question_id INT DEFAULT NULL,
    ranking JSON DEFAULT NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Player Answers table (stores players' answers for each round)
CREATE TABLE IF NOT EXISTS player_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    player_id INT NOT NULL,
    answer_time DECIMAL(10, 4) NOT NULL,
    FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    UNIQUE (round_id, player_id)
);

CREATE TABLE IF NOT EXISTS winners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNIQUE NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES players(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_hash VARCHAR(64) UNIQUE NOT NULL,
    auth_role ENUM('admin', 'player', 'judge') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_id INT DEFAULT NULL,
    player_id INT DEFAULT NULL,
    judge_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE,
    -- Un solo token attivo per utente/ruolo
    UNIQUE (auth_role, user_id),
    UNIQUE (auth_role, player_id),
    UNIQUE (auth_role, judge_id),
    -- Indice per query di pulizia TTL
    INDEX idx_created_at (created_at),
    -- Hash SHA-256 = esattamente 64 caratteri hex
    CHECK (CHAR_LENGTH(token_hash) = 64),
    -- Ogni ruolo deve avere esattamente il suo ID valorizzato
    CHECK (
        auth_role != 'admin'
        OR (
            user_id IS NOT NULL
            AND player_id IS NULL
            AND judge_id IS NULL
        )
    ),
    CHECK (
        auth_role != 'player'
        OR (
            player_id IS NOT NULL
            AND user_id IS NULL
            AND judge_id IS NULL
        )
    ),
    CHECK (
        auth_role != 'judge'
        OR (
            judge_id IS NOT NULL
            AND user_id IS NULL
            AND player_id IS NULL
        )
    )
);

-- Evento MySQL: Cancella i token di autenticazione scaduti (più vecchi di 24 ore)
SET
    GLOBAL event_scheduler = ON;

DROP EVENT IF EXISTS clean_expired_tokens;

CREATE EVENT clean_expired_tokens ON SCHEDULE EVERY 1 HOUR DO
DELETE FROM
    auth_tokens
WHERE
    TIMESTAMPDIFF(HOUR, created_at, NOW()) >= 24;

DROP EVENT IF EXISTS close_abandoned_rooms;

CREATE EVENT close_abandoned_rooms ON SCHEDULE EVERY 1 HOUR DO
UPDATE
    rooms
SET
    status_room = 'cancelled'
WHERE
    (
        status_room = 'open'
        OR status_room = 'running'
    )
    AND TIMESTAMPDIFF(HOUR, created_at, NOW()) >= 24;
