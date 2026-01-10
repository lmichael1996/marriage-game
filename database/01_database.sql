-- Database setup for Marriage Game
CREATE DATABASE IF NOT EXISTS marriage_game;
USE marriage_game;

-- Users table (for login)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'player') DEFAULT 'player'
);

-- Question Sets table
CREATE TABLE IF NOT EXISTS question_sets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    set_name VARCHAR(255) NOT NULL,
    set_description TEXT
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
    FOREIGN KEY (question_set_id) REFERENCES question_sets(id) ON DELETE SET NULL
);
