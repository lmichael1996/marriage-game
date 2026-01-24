-- Database setup for Marriage Game
CREATE DATABASE IF NOT EXISTS marriage_game;
USE marriage_game;

-- Users table (for admin login only)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    user_password VARCHAR(255) NOT NULL
);

-- Question Sets table
CREATE TABLE IF NOT EXISTS question_sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    set_name VARCHAR(255) UNIQUE NOT NULL,
    set_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rounds table
CREATE TABLE IF NOT EXISTS rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_set_id INT DEFAULT NULL,
    round_number INT NOT NULL,
    round_type ENUM('multiple', 'truefalse', 'clickfirst') DEFAULT 'multiple',
    question TEXT,
    option1 TEXT,
    option2 TEXT,
    option3 TEXT,
    option4 TEXT,
    correct_answer INT,
    timer INT DEFAULT 10 COMMENT 'Timer in secondi per la domanda',
    status_round ENUM('pending', 'active', 'closed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (question_set_id) REFERENCES question_sets(id) ON DELETE SET NULL,
    INDEX idx_question_set_id (question_set_id),
    INDEX idx_status (status_round),
    INDEX idx_round_type (round_type)
);

-- Rooms table (stores active game rooms)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(10) UNIQUE NOT NULL,
    question_set_id INT DEFAULT NULL COMMENT 'Set di domande selezionato per questa stanza',
    status_room ENUM('waiting', 'active', 'closed', 'canceled') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (question_set_id) REFERENCES question_sets(id) ON DELETE SET NULL,
    INDEX idx_room_code (room_code),
    INDEX idx_status (status_room)
);

-- Players table (for game participants associated with a room)
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    room_code VARCHAR(10) NOT NULL,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_code (room_code)
);

-- Player Answers table (stores answers submitted by players)
CREATE TABLE IF NOT EXISTS player_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    player_id INT NOT NULL,
    answer INT NOT NULL COMMENT 'Risposta data dal giocatore (1-4 per multiple, 1-2 per true/false)',
    time_taken INT DEFAULT 0 COMMENT 'Tempo impiegato in millisecondi',
    is_correct TINYINT(1) DEFAULT 0 COMMENT '1 se corretta, 0 se sbagliata',
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    INDEX idx_round_id (round_id),
    INDEX idx_player_id (player_id),
    UNIQUE KEY unique_answer (round_id, player_id) COMMENT 'Un giocatore può rispondere solo una volta per round'
);

-- Tabella per le impostazioni del gioco
CREATE TABLE IF NOT EXISTS game_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;