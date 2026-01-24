USE marriage_game;

-- Inserimento set di domande di test
INSERT INTO question_sets (set_name, set_description) VALUES
('Quiz Matrimonio Classico', 'Domande divertenti per conoscere meglio gli sposi'),
('Cultura Generale', 'Domande di cultura generale e curiosità'),
('Sport e Intrattenimento', 'Domande su sport, cinema e musica'),
('Per bambini', 'Domande facili e divertenti per i più piccoli');

-- Domande per "Quiz Matrimonio Classico" (set_id = 1)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(1, 1, 'multiple', 'Dove si sono conosciuti gli sposi?', 'Al lavoro', 'All università', 'In vacanza', 'Ad una festa', 2, 30),
(1, 2, 'truefalse', 'Gli sposi hanno fatto il primo viaggio insieme in Italia', 'Vero', 'Falso', '', '', 1, 20),
(1, 3, 'multiple', 'Qual è il piatto preferito dello sposo?', 'Pizza', 'Pasta alla carbonara', 'Bistecca', 'Sushi', 2, 25),
(1, 4, 'clickfirst', 'Chi ha detto "Ti amo" per primo?', '', '', '', '', NULL, 30),
(1, 5, 'multiple', 'In che mese si sono fidanzati?', 'Gennaio', 'Maggio', 'Settembre', 'Dicembre', 3, 30);

-- Domande per "Cultura Generale" (set_id = 2)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(2, 1, 'multiple', 'Qual è la capitale della Francia?', 'Londra', 'Parigi', 'Roma', 'Madrid', 2, 20),
(2, 2, 'truefalse', 'La Terra è piatta', 'Vero', 'Falso', '', '', 2, 15),
(2, 3, 'multiple', 'Chi ha dipinto la Gioconda?', 'Michelangelo', 'Raffaello', 'Leonardo da Vinci', 'Caravaggio', 3, 25),
(2, 4, 'multiple', 'Quanti continenti ci sono?', '5', '6', '7', '8', 3, 20),
(2, 5, 'truefalse', 'Il Sole è una stella', 'Vero', 'Falso', '', '', 1, 15);

-- Domande per "Sport e Intrattenimento" (set_id = 3)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(3, 1, 'multiple', 'Chi ha vinto più Mondiali di calcio?', 'Italia', 'Germania', 'Brasile', 'Argentina', 3, 30),
(3, 2, 'multiple', 'Quale film ha vinto l Oscar nel 2020?', 'Joker', 'Parasite', '1917', 'Once Upon a Time', 2, 35),
(3, 3, 'truefalse', 'Le Olimpiadi si tengono ogni 4 anni', 'Vero', 'Falso', '', '', 1, 20),
(3, 4, 'clickfirst', 'Chi è il cantante più veloce a premere!', '', '', '', '', NULL, 30),
(3, 5, 'multiple', 'Quanti giocatori ha una squadra di basket?', '5', '6', '7', '11', 1, 25);

-- Domande per "Per bambini" (set_id = 4)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(4, 1, 'multiple', 'Qual è il colore del cielo?', 'Verde', 'Blu', 'Rosso', 'Giallo', 2, 5);

-- Inserimento impostazioni di gioco
-- 3 sistemi di punteggio separati per 3 tipi di gioco
INSERT INTO game_settings (setting_key, setting_value) VALUES
-- Impostazioni generali
('min_players', '2'),
('max_players', '50'),
('auto_next_round', '0'),
('auto_next_delay', '5'),

-- Punteggi Multiple Choice (primi 10)
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

-- Punteggi True/False (primi 10)
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

-- Punteggi Click First (solo il primo vince)
('points_clickfirst', '50'),

-- Visualizzazione
('show_leaderboard', '1'),
('show_correct_answer', '1')

ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
