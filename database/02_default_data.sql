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
        '$argon2id$v=19$m=65536,t=4,p=1$ZFpvc25WeG9DampMRjR4NQ$+Ah56vOpAE6/R2pOXqqqdfOWY+4IhhZuxqSWKz49e2I'
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
