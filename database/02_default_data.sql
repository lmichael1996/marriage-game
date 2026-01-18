-- ============================================
-- DATI DEFAULT - Marriage Game
-- ============================================
USE marriage_game;

-- Inserimento utente admin di default
INSERT INTO users (username, user_password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Inserimento valori predefiniti
-- 3 tabelle separate per 3 tipi di gioco
INSERT INTO game_settings (setting_key, setting_value) VALUES
('min_players', '2'),
('max_players', '50'),
('auto_next_round', '0'),
('auto_next_delay', '5'),
('points_mult_1st', '25'),
('points_mult_2nd', '18'),
('points_mult_3rd', '15'),
('points_mult_4th', '12'),
('points_mult_5th', '10'),
('points_mult_6th', '8'),
('points_mult_7th', '6'),
('points_mult_8th', '4'),
('points_mult_9th', '2'),
('points_mult_10th', '1'),
('points_tf_1st', '20'),
('points_tf_2nd', '15'),
('points_tf_3rd', '12'),
('points_tf_4th', '10'),
('points_tf_5th', '8'),
('points_tf_6th', '6'),
('points_tf_7th', '5'),
('points_tf_8th', '3'),
('points_tf_9th', '2'),
('points_tf_10th', '1'),
('points_clickfirst', '50'),
('show_leaderboard', '1'),
('show_correct_answer', '1')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);