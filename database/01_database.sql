-- Database setup for Marriage Game
CREATE DATABASE IF NOT EXISTS marriage_game;

USE marriage_game;

-- Users table (for admin login only)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    user_password VARCHAR(255) NOT NULL
);

-- Tabella per le impostazioni del gioco
CREATE TABLE IF NOT EXISTS game_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value INT
);

-- Question Sets table
CREATE TABLE IF NOT EXISTS question_sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    set_name VARCHAR(255) UNIQUE NOT NULL,
    set_description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Questions table (previously called rounds)
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_set_id INT DEFAULT NULL,
    round_number INT NOT NULL,
    round_type ENUM('multiple', 'truefalse', 'clickfirst') DEFAULT 'multiple',
    question TEXT,
    option1 TEXT,
    option2 TEXT,
    option3 TEXT,
    option4 TEXT,
    correct_answer INT CHECK (
        correct_answer BETWEEN 1
        AND 4
    ),
    timer INT DEFAULT 10,
    FOREIGN KEY (question_set_id) REFERENCES question_sets(id) ON DELETE CASCADE
);

-- Rooms table (stores active game rooms)
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(10) UNIQUE NOT NULL,
    question_set_id INT DEFAULT NULL,
    FOREIGN KEY (question_set_id) REFERENCES question_sets(id) ON DELETE CASCADE
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

-- Player Answers table (stores answers submitted by players)
CREATE TABLE IF NOT EXISTS rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    round_number INT NOT NULL,
    type_game ENUM('multiple', 'truefalse', 'clickfirst') DEFAULT 'multiple',
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS player_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    answer_time DECIMAL(10, 4) NOT NULL,
    FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE,
    UNIQUE (round_id, username)
);
