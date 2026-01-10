-- ============================================
-- DATI DEFAULT - Marriage Game
-- ============================================
USE marriage_game;

-- Inserimento utente admin di default
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
