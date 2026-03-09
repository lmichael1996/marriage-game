-- ============================================
-- TEST DATA - Marriage Game
-- ============================================
USE marriage_game;

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
        question_type,
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
    -- Multiple Choice - Generale (1)
    (
        'multiple',
        'Qual è la capitale della Francia?',
        'Lione',
        'Parigi',
        'Marsiglia',
        'Tolosa',
        1,
        2,
        30
    ),
    (
        'multiple',
        'Quanti continenti ci sono sulla Terra?',
        '5',
        '6',
        '7',
        '8',
        1,
        3,
        30
    ),
    (
        'multiple',
        'Chi ha scritto "Don Chisciotte"?',
        'Machado',
        'Cervantes',
        'Lorca',
        'García Márquez',
        1,
        2,
        30
    ),
    (
        'multiple',
        'Qual è il pianeta più grande del nostro sistema solare?',
        'Saturno',
        'Giove',
        'Nettuno',
        'Urano',
        1,
        2,
        30
    ),
    (
        'multiple',
        'In quale paese si trova la statua della Libertà?',
        'Francia',
        'Stati Uniti',
        'Italia',
        'Inghilterra',
        1,
        2,
        30
    ),
    -- True/False - Scienza (2)
    (
        'truefalse',
        'Il carbonio è un non-metallo',
        '',
        '',
        '',
        '',
        2,
        1,
        20
    ),
    (
        'truefalse',
        'L\'acqua bolle a 100 gradi Celsius al livello del mare',
        '',
        '',
        '',
        '',
        2,
        1,
        20
    ),
    (
        'truefalse',
        'Gli atomi sono le particelle più piccole della materia',
        '',
        '',
        '',
        '',
        2,
        2,
        20
    ),
    (
        'truefalse',
        'La velocità della luce è di circa 300.000 km/s',
        '',
        '',
        '',
        '',
        2,
        1,
        20
    ),
    (
        'truefalse',
        'Le piante producono ossigeno durante la fotosintesi',
        '',
        '',
        '',
        '',
        2,
        1,
        20
    ),
    -- Click First - Storia (3)
    (
        'clickfirst',
        'In quale anno è caduto l\'Impero Romano d\'Occidente?',
        '',
        '',
        '',
        '',
        3,
        NULL,
        15
    ),
    (
        'clickfirst',
        'Quanti anni durò la Guerra dei Cento Anni?',
        '',
        '',
        '',
        '',
        3,
        NULL,
        15
    ),
    (
        'clickfirst',
        'In quale anno Colombo ha raggiunto l\'America?',
        '',
        '',
        '',
        '',
        3,
        NULL,
        15
    ),
    (
        'clickfirst',
        'Quanti anni ha regnato Napoleone?',
        '',
        '',
        '',
        '',
        3,
        NULL,
        15
    ),
    (
        'clickfirst',
        'In quale anno iniziò la Rivoluzione Francese?',
        '',
        '',
        '',
        '',
        3,
        NULL,
        15
    ),
    -- Multiple Choice - Sport (4)
    (
        'multiple',
        'In quale anno si sono svolti i Giochi Olimpici di Rio?',
        '2012',
        '2014',
        '2016',
        '2018',
        4,
        3,
        30
    ),
    (
        'multiple',
        'Quanti giocatori ha una squadra di calcio in campo?',
        '10',
        '11',
        '12',
        '9',
        4,
        2,
        30
    ),
    (
        'multiple',
        'Quale squadra ha vinto più Champions League nella storia?',
        'Barcelona',
        'Real Madrid',
        'Bayern Monaco',
        'Liverpool',
        4,
        2,
        30
    ),
    (
        'multiple',
        'In quale città si sono svolti i Giochi Olimpici del 2020?',
        'Tokyo',
        'Pechino',
        'Londra',
        'Rio',
        4,
        1,
        30
    ),
    (
        'multiple',
        'Quale tennista ha vinto più Grand Slam nella storia?',
        'Roger Federer',
        'Rafael Nadal',
        'Novak Djokovic',
        'Andy Murray',
        4,
        3,
        30
    ),
    -- True/False - Intrattenimento (5)
    (
        'truefalse',
        'Avatar è il film con il maggior incasso di tutti i tempi',
        '',
        '',
        '',
        '',
        5,
        1,
        20
    ),
    (
        'truefalse',
        'La Monna Lisa è stata dipinta da Michelangelo',
        '',
        '',
        '',
        '',
        5,
        2,
        20
    ),
    (
        'truefalse',
        'Harry Potter è composto da 7 libri',
        '',
        '',
        '',
        '',
        5,
        1,
        20
    ),
    (
        'truefalse',
        'Breaking Bad ha avuto 6 stagioni',
        '',
        '',
        '',
        '',
        5,
        2,
        20
    ),
    (
        'truefalse',
        'Game of Thrones è basato su libri di George R. R. Martin',
        '',
        '',
        '',
        '',
        5,
        1,
        20
    );

-- Create a sample question set
INSERT INTO
    qsets (set_name, set_description)
VALUES
    (
        'Trivia Generale',
        'Un set di domande trivia su vari argomenti'
    );

-- Associate questions with the question set (via qset_questions) - 25 domande
INSERT INTO
    qset_questions (qset_id, question_id, order_in_set)
VALUES
    (1, 1, 1),
    (1, 2, 2),
    (1, 3, 3),
    (1, 4, 4),
    (1, 5, 5);

-- Add additional question sets (5 questions each)
INSERT INTO
    qsets (set_name, set_description)
VALUES
    (
        'Set Scienza Base',
        'Domande di scienza per principianti'
    ),
    (
        'Set Storia Antica',
        'Domande sulla storia antica e medioevo'
    ),
    (
        'Set Sport Mondiale',
        'Domande su sport internazionali'
    ),
    (
        'Set Cultura Generale',
        'Domande di cultura generale miste'
    );

-- Associate questions with new sets
INSERT INTO
    qset_questions (qset_id, question_id, order_in_set)
VALUES
    -- Set 2: Scienza Base (domande 1-5)
    (2, 1, 1),
    (2, 2, 2),
    (2, 3, 3),
    (2, 4, 4),
    (2, 5, 5),
    -- Set 3: Storia Antica (domande 6-10)
    (3, 6, 1),
    (3, 7, 2),
    (3, 8, 3),
    (3, 9, 4),
    (3, 10, 5),
    -- Set 4: Sport Mondiale (domande 11-15)
    (4, 11, 1),
    (4, 12, 2),
    (4, 13, 3),
    (4, 14, 4),
    (4, 15, 5),
    -- Set 5: Cultura Generale (domande 16-20)
    (5, 16, 1),
    (5, 17, 2),
    (5, 18, 3),
    (5, 19, 4),
    (5, 20, 5);

/*
 -- Add 20 more question sets (5 questions each) - Sets 6-25
 INSERT INTO
 qsets (set_name, set_description)
 VALUES
 (
 'Set Geografia',
 'Domande di geografia mondiale'
 ),
 (
 'Set Matematica',
 'Domande di matematica e logica'
 ),
 (
 'Set Letteratura',
 'Domande su autori e opere letterarie',
 TRUE
 ),
 (
 'Set Musica Classica',
 'Domande su compositori e musica classica',
 TRUE
 ),
 ('Set Cinema', 'Domande su film e cinema', TRUE),
 (
 'Set Biologia',
 'Domande di biologia e genetica',
 TRUE
 ),
 (
 'Set Fisica',
 'Domande di fisica e meccanica',
 TRUE
 ),
 (
 'Set Chimica',
 'Domande di chimica organica',
 TRUE
 ),
 (
 'Set Astronomia',
 'Domande su pianeti e spazio',
 TRUE
 ),
 (
 'Set Filosofia',
 'Domande di filosofia antica e moderna',
 TRUE
 ),
 (
 'Set Arte Rinascimentale',
 'Domande su pittori del rinascimento',
 TRUE
 ),
 (
 'Set Mitologia Greca',
 'Domande su dei e eroi greci',
 TRUE
 ),
 (
 'Set Cucina Italiana',
 'Domande su piatti e tradizioni culinarie',
 TRUE
 ),
 (
 'Set Calcio Europeo',
 'Domande su squadre e giocatori di calcio',
 TRUE
 ),
 (
 'Set Tennis Mondiale',
 'Domande su campioni e tornei di tennis',
 TRUE
 ),
 (
 'Set Automobili',
 'Domande su marchi e modelli di auto',
 TRUE
 ),
 (
 'Set Fotografia',
 'Domande su tecniche e fotografi famosi',
 TRUE
 ),
 (
 'Set Economia',
 'Domande di economia e finanza',
 TRUE
 ),
 (
 'Set Politica Internazionale',
 'Domande su governi e relazioni internazionali',
 TRUE
 ),
 (
 'Set Psicologia',
 'Domande di psicologia comportamentale',
 TRUE
 );
 
 -- Associate questions with new sets (6-25) - ciclando attraverso le 25 domande
 INSERT INTO
 qset_questions (qset_id, question_id, order_in_set)
 VALUES
 -- Set 6: Geografia (domande 21-25, 1-2)
 (6, 21, 1),
 (6, 22, 2),
 (6, 23, 3),
 (6, 24, 4),
 (6, 25, 5),
 -- Set 7: Matematica (domande 1, 6-9)
 (7, 1, 1),
 (7, 6, 2),
 (7, 7, 3),
 (7, 8, 4),
 (7, 9, 5),
 -- Set 8: Letteratura (domande 2, 10-13)
 (8, 2, 1),
 (8, 10, 2),
 (8, 11, 3),
 (8, 12, 4),
 (8, 13, 5),
 -- Set 9: Musica Classica (domande 3, 14-17)
 (9, 3, 1),
 (9, 14, 2),
 (9, 15, 3),
 (9, 16, 4),
 (9, 17, 5),
 -- Set 10: Cinema (domande 4, 18-21)
 (10, 4, 1),
 (10, 18, 2),
 (10, 19, 3),
 (10, 20, 4),
 (10, 21, 5),
 -- Set 11: Biologia (domande 5, 22-25, 1)
 (11, 5, 1),
 (11, 22, 2),
 (11, 23, 3),
 (11, 24, 4),
 (11, 25, 5),
 -- Set 12: Fisica (domande 6, 1-4)
 (12, 6, 1),
 (12, 1, 2),
 (12, 2, 3),
 (12, 3, 4),
 (12, 4, 5),
 -- Set 13: Chimica (domande 7, 5, 10-12)
 (13, 7, 1),
 (13, 5, 2),
 (13, 10, 3),
 (13, 11, 4),
 (13, 12, 5),
 -- Set 14: Astronomia (domande 8, 13-16)
 (14, 8, 1),
 (14, 13, 2),
 (14, 14, 3),
 (14, 15, 4),
 (14, 16, 5),
 -- Set 15: Filosofia (domande 9, 17-20)
 (15, 9, 1),
 (15, 17, 2),
 (15, 18, 3),
 (15, 19, 4),
 (15, 20, 5),
 -- Set 16: Arte Rinascimentale (domande 10, 21-24)
 (16, 10, 1),
 (16, 21, 2),
 (16, 22, 3),
 (16, 23, 4),
 (16, 24, 5),
 -- Set 17: Mitologia Greca (domande 11, 25, 1-3)
 (17, 11, 1),
 (17, 25, 2),
 (17, 1, 3),
 (17, 2, 4),
 (17, 3, 5),
 -- Set 18: Cucina Italiana (domande 12, 4-7)
 (18, 12, 1),
 (18, 4, 2),
 (18, 5, 3),
 (18, 6, 4),
 (18, 7, 5),
 -- Set 19: Calcio Europeo (domande 13, 8-11)
 (19, 13, 1),
 (19, 8, 2),
 (19, 9, 3),
 (19, 10, 4),
 (19, 11, 5),
 -- Set 20: Tennis Mondiale (domande 12-16)
 (20, 12, 1),
 (20, 13, 2),
 (20, 14, 3),
 (20, 15, 4),
 (20, 16, 5),
 -- Set 21: Automobili (domande 15, 16-19)
 (21, 15, 1),
 (21, 16, 2),
 (21, 17, 3),
 (21, 18, 4),
 (21, 19, 5),
 -- Set 22: Fotografia (domande 16, 20-23)
 (22, 16, 1),
 (22, 20, 2),
 (22, 21, 3),
 (22, 22, 4),
 (22, 23, 5),
 -- Set 23: Economia (domande 17, 24-25, 1-2)
 (23, 17, 1),
 (23, 24, 2),
 (23, 25, 3),
 (23, 1, 4),
 (23, 2, 5),
 -- Set 24: Politica Internazionale (domande 18, 3-6)
 (24, 18, 1),
 (24, 3, 2),
 (24, 4, 3),
 (24, 5, 4),
 (24, 6, 5),
 -- Set 25: Psicologia (domande 19, 7-10)
 (25, 19, 1),
 (25, 7, 2),
 (25, 8, 3),
 (25, 9, 4),
 (25, 10, 5);
 */
