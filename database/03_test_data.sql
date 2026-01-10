USE marriage_game;

-- Inserimento set di domande di test
INSERT INTO question_sets (set_name, set_description) VALUES
('Quiz Matrimonio Classico', 'Domande divertenti per conoscere meglio gli sposi'),
('Cultura Generale', 'Domande di cultura generale e curiosità'),
('Sport e Intrattenimento', 'Domande su sport, cinema e musica');

-- Domande per "Quiz Matrimonio Classico" (set_id = 1)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer) VALUES
(1, 1, 'multiple', 'Dove si sono conosciuti gli sposi?', 'Al lavoro', 'All università', 'In vacanza', 'Ad una festa', 2),
(1, 2, 'truefalse', 'Gli sposi hanno fatto il primo viaggio insieme in Italia', 'Vero', 'Falso', '', '', 1),
(1, 3, 'multiple', 'Qual è il piatto preferito dello sposo?', 'Pizza', 'Pasta alla carbonara', 'Bistecca', 'Sushi', 2),
(1, 4, 'clickfirst', 'Chi ha detto "Ti amo" per primo?', '', '', '', '', NULL),
(1, 5, 'multiple', 'In che mese si sono fidanzati?', 'Gennaio', 'Maggio', 'Settembre', 'Dicembre', 3);

-- Domande per "Cultura Generale" (set_id = 2)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer) VALUES
(2, 1, 'multiple', 'Qual è la capitale della Francia?', 'Londra', 'Parigi', 'Roma', 'Madrid', 2),
(2, 2, 'truefalse', 'La Terra è piatta', 'Vero', 'Falso', '', '', 2),
(2, 3, 'multiple', 'Chi ha dipinto la Gioconda?', 'Michelangelo', 'Raffaello', 'Leonardo da Vinci', 'Caravaggio', 3),
(2, 4, 'multiple', 'Quanti continenti ci sono?', '5', '6', '7', '8', 3),
(2, 5, 'truefalse', 'Il Sole è una stella', 'Vero', 'Falso', '', '', 1);

-- Domande per "Sport e Intrattenimento" (set_id = 3)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer) VALUES
(3, 1, 'multiple', 'Chi ha vinto più Mondiali di calcio?', 'Italia', 'Germania', 'Brasile', 'Argentina', 3),
(3, 2, 'multiple', 'Quale film ha vinto l Oscar nel 2020?', 'Joker', 'Parasite', '1917', 'Once Upon a Time', 2),
(3, 3, 'truefalse', 'Le Olimpiadi si tengono ogni 4 anni', 'Vero', 'Falso', '', '', 1),
(3, 4, 'clickfirst', 'Chi è il cantante più veloce a premere!', '', '', '', '', NULL),
(3, 5, 'multiple', 'Quanti giocatori ha una squadra di basket?', '5', '6', '7', '11', 1);
