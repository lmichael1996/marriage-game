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
        'Vero',
        'Falso',
        '',
        '',
        2,
        1,
        20
    ),
    (
        'truefalse',
        'L\'acqua bolle a 100 gradi Celsius al livello del mare',
        'Vero',
        'Falso',
        '',
        '',
        2,
        1,
        20
    ),
    (
        'truefalse',
        'Gli atomi sono le particelle più piccole della materia',
        'Vero',
        'Falso',
        '',
        '',
        2,
        2,
        20
    ),
    (
        'truefalse',
        'La velocità della luce è di circa 300.000 km/s',
        'Vero',
        'Falso',
        '',
        '',
        2,
        1,
        20
    ),
    (
        'truefalse',
        'Le piante producono ossigeno durante la fotosintesi',
        'Vero',
        'Falso',
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
        'Vero',
        'Falso',
        '',
        '',
        5,
        1,
        20
    ),
    (
        'truefalse',
        'La Monna Lisa è stata dipinta da Michelangelo',
        'Vero',
        'Falso',
        '',
        '',
        5,
        2,
        20
    ),
    (
        'truefalse',
        'Harry Potter è composto da 7 libri',
        'Vero',
        'Falso',
        '',
        '',
        5,
        1,
        20
    ),
    (
        'truefalse',
        'Breaking Bad ha avuto 6 stagioni',
        'Vero',
        'Falso',
        '',
        '',
        5,
        2,
        20
    ),
    (
        'truefalse',
        'Game of Thrones è basato su libri di George R. R. Martin',
        'Vero',
        'Falso',
        '',
        '',
        5,
        1,
        20
    );

-- Create a sample question set
INSERT INTO
    qsets (set_name, set_description, is_saved)
VALUES
    (
        'Trivia Generale',
        'Un set di domande trivia su vari argomenti',
        TRUE
    );

-- Associate questions with the question set (via qset_questions) - 25 domande
INSERT INTO
    qset_questions (qset_id, question_id, order_in_set)
VALUES
    (1, 1, 1),
    (1, 2, 2),
    (1, 3, 3),
    (1, 4, 4),
    (1, 5, 5),
    (1, 6, 6),
    (1, 7, 7),
    (1, 8, 8),
    (1, 9, 9),
    (1, 10, 10),
    (1, 11, 11),
    (1, 12, 12),
    (1, 13, 13),
    (1, 14, 14),
    (1, 15, 15),
    (1, 16, 16),
    (1, 17, 17),
    (1, 18, 18),
    (1, 19, 19),
    (1, 20, 20),
    (1, 21, 21),
    (1, 22, 22),
    (1, 23, 23),
    (1, 24, 24),
    (1, 25, 25);
