-- Database setup for Marriage Game
CREATE DATABASE IF NOT EXISTS marriage_game;
USE marriage_game;

-- Users table (for admin login only)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
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

-- Question Sets table
CREATE TABLE IF NOT EXISTS question_sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    set_name VARCHAR(255) NOT NULL,
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
    timer INT DEFAULT 30 COMMENT 'Timer in secondi per la domanda',
    status ENUM('pending', 'active', 'closed') DEFAULT 'pending',
    FOREIGN KEY (question_set_id) REFERENCES question_sets(id) ON DELETE SET NULL
);

