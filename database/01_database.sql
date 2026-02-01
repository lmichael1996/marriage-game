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
    user_one VARCHAR(50) DEFAULT NULL,
    points_user_one INT DEFAULT 0,
    user_two VARCHAR(50) DEFAULT NULL,
    points_user_two INT DEFAULT 0,
    user_three VARCHAR(50) DEFAULT NULL,
    points_user_three INT DEFAULT 0,
    user_four VARCHAR(50) DEFAULT NULL,
    points_user_four INT DEFAULT 0,
    user_five VARCHAR(50) DEFAULT NULL,
    points_user_five INT DEFAULT 0,
    user_six VARCHAR(50) DEFAULT NULL,
    points_user_six INT DEFAULT 0,
    user_seven VARCHAR(50) DEFAULT NULL,
    points_user_seven INT DEFAULT 0,
    user_eight VARCHAR(50) DEFAULT NULL,
    points_user_eight INT DEFAULT 0,
    user_nine VARCHAR(50) DEFAULT NULL,
    points_user_nine INT DEFAULT 0,
    user_ten VARCHAR(50) DEFAULT NULL,
    points_user_ten INT DEFAULT 0,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);
