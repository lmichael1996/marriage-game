-- ============================================
-- DEFAULT DATA - Marriage Game
-- ============================================
USE marriage_game;

-- Insert default admin user
INSERT INTO
    users (username, user_password)
VALUES
    (
        'admin',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
    );

-- Insert default question categories
INSERT INTO
    question_categories (category_name, color)
VALUES
    ('Generale', '#ecf0f1');

-- Insert default game settings
INSERT INTO
    game_settings (setting_key, setting_value)
VALUES
    ('points_mult_1st', 25),
    ('points_mult_2nd', 18),
    ('points_mult_3rd', 15),
    ('points_mult_4th', 12),
    ('points_mult_5th', 10),
    ('points_mult_6th', 8),
    ('points_mult_7th', 6),
    ('points_mult_8th', 4),
    ('points_mult_9th', 2),
    ('points_mult_10th', 1),
    ('points_tf_1st', 20),
    ('points_tf_2nd', 15),
    ('points_tf_3rd', 12),
    ('points_tf_4th', 10),
    ('points_tf_5th', 8),
    ('points_tf_6th', 6),
    ('points_tf_7th', 5),
    ('points_tf_8th', 3),
    ('points_tf_9th', 2),
    ('points_tf_10th', 1),
    ('points_clickfirst', 50);

-- Insert default question categories
INSERT INTO
    question_categories (category_name, color)
VALUES
    ('Scienza', '#e3f2fd'),
    ('Storia', '#f3e5f5'),
    ('Sport', '#fff3e0'),
    ('Intrattenimento', '#fce4ec');

-- Insert sample questions (25 questions across all categories and types)
INSERT INTO
    questions (
        round_type,
        question,
        option1,
        option2,
        option3,
        option4,
        category_id,
        correct_answer,
        timer
    )
VALUES
    -- Multiple Choice - Generale (1-5)
    (
        'multiple',
        'Partita terminata',
        'Saturno',
        'Giove',
        'Nettuno',
        'Urano',
        2,
        2,
        30
    ),
    (
        'multiple',
        'In che anno è caduto il Muro di Berlino?',
        '1987',
        '1989',
        '1991',
        '1985',
        3,
        2,
        30
    );
