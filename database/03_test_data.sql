-- ============================================
-- TEST DATA - Marriage Game
-- ============================================
USE marriage_game;

-- Category IDs from 02_default_data.sql:
-- 1=Generale, 2=Storia, 3=Sport, 4=Scienze, 5=Geografia, 6=Film, 7=Arte, 8=Musica, 9=Cucina
-- Insert sample questions (45 questions, 5 per category, mixed types)
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
    -- ── Generale (1) ── multiple ──
    (
        'multiple',
        'Qual è la capitale della Francia?',
        'Lione',
        'Parigi',
        'Marsiglia',
        'Tolosa',
        1,
        2,
        10
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
        10
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
        10
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
        10
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
        10
    ),
    -- ── Storia (2) ── clickfirst ──
    (
        'clickfirst',
        'In quale anno è caduto l''Impero Romano d''Occidente?',
        '',
        '',
        '',
        '',
        2,
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
        2,
        NULL,
        15
    ),
    (
        'clickfirst',
        'In quale anno Colombo ha raggiunto l''America?',
        '',
        '',
        '',
        '',
        2,
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
        2,
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
        2,
        NULL,
        15
    ),
    -- ── Sport (3) ── multiple ──
    (
        'multiple',
        'In quale anno si sono svolti i Giochi Olimpici di Rio?',
        '2012',
        '2014',
        '2016',
        '2018',
        3,
        3,
        10
    ),
    (
        'multiple',
        'Quanti giocatori ha una squadra di calcio in campo?',
        '10',
        '11',
        '12',
        '9',
        3,
        2,
        10
    ),
    (
        'multiple',
        'Quale squadra ha vinto più Champions League nella storia?',
        'Barcelona',
        'Real Madrid',
        'Bayern Monaco',
        'Liverpool',
        3,
        2,
        10
    ),
    (
        'multiple',
        'In quale città si sono svolti i Giochi Olimpici del 2020?',
        'Tokyo',
        'Pechino',
        'Londra',
        'Rio',
        3,
        1,
        10
    ),
    (
        'multiple',
        'Quale tennista ha vinto più Grand Slam nella storia?',
        'Roger Federer',
        'Rafael Nadal',
        'Novak Djokovic',
        'Andy Murray',
        3,
        3,
        10
    ),
    -- ── Scienze (4) ── truefalse ──
    (
        'truefalse',
        'Il carbonio è un non-metallo',
        '',
        '',
        '',
        '',
        4,
        1,
        20
    ),
    (
        'truefalse',
        'L''acqua bolle a 100 gradi Celsius al livello del mare',
        '',
        '',
        '',
        '',
        4,
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
        4,
        2,
        20
    ),
    (
        'truefalse',
        'La velocità della luce è di circa 100.000 km/s',
        '',
        '',
        '',
        '',
        4,
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
        4,
        1,
        20
    ),
    -- ── Geografia (5) ── multiple ──
    (
        'multiple',
        'Qual è il fiume più lungo del mondo?',
        'Mississippi',
        'Nilo',
        'Rio delle Amazzoni',
        'Yangtze',
        5,
        2,
        10
    ),
    (
        'multiple',
        'In quale continente si trova il deserto del Sahara?',
        'Asia',
        'Africa',
        'America del Sud',
        'Oceania',
        5,
        2,
        10
    ),
    (
        'multiple',
        'Quale nazione ha più isole al mondo?',
        'Indonesia',
        'Filippine',
        'Svezia',
        'Giappone',
        5,
        3,
        10
    ),
    (
        'truefalse',
        'L''Australia è sia un paese che un continente',
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
        'Il Monte Everest si trova in Africa',
        '',
        '',
        '',
        '',
        5,
        2,
        20
    ),
    -- ── Film (6) ── truefalse + multiple ──
    (
        'truefalse',
        'Avatar è il film con il maggior incasso di tutti i tempi',
        '',
        '',
        '',
        '',
        6,
        1,
        20
    ),
    (
        'truefalse',
        'Harry Potter è composto da 7 libri',
        '',
        '',
        '',
        '',
        6,
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
        6,
        2,
        20
    ),
    (
        'multiple',
        'Chi ha diretto "Il Padrino"?',
        'Martin Scorsese',
        'Francis Ford Coppola',
        'Steven Spielberg',
        'Stanley Kubrick',
        6,
        2,
        10
    ),
    (
        'multiple',
        'In quale anno è uscito il primo film di Star Wars?',
        '1975',
        '1977',
        '1979',
        '1980',
        6,
        2,
        10
    ),
    -- ── Arte (7) ── multiple + truefalse ──
    (
        'multiple',
        'Chi ha dipinto la Cappella Sistina?',
        'Leonardo da Vinci',
        'Raffaello',
        'Michelangelo',
        'Caravaggio',
        7,
        3,
        10
    ),
    (
        'multiple',
        'In quale museo si trova la Gioconda?',
        'Uffizi',
        'Louvre',
        'British Museum',
        'Prado',
        7,
        2,
        10
    ),
    (
        'truefalse',
        'La Monna Lisa è stata dipinta da Michelangelo',
        '',
        '',
        '',
        '',
        7,
        2,
        20
    ),
    (
        'multiple',
        'Quale movimento artistico è associato a Salvador Dalí?',
        'Cubismo',
        'Impressionismo',
        'Surrealismo',
        'Futurismo',
        7,
        3,
        10
    ),
    (
        'clickfirst',
        'In quale anno Van Gogh ha dipinto "Notte Stellata"?',
        '',
        '',
        '',
        '',
        7,
        NULL,
        15
    ),
    -- ── Musica (8) ── multiple + clickfirst ──
    (
        'multiple',
        'Chi ha composto "Le Quattro Stagioni"?',
        'Mozart',
        'Vivaldi',
        'Beethoven',
        'Bach',
        8,
        2,
        10
    ),
    (
        'multiple',
        'Quale strumento suonava Jimi Hendrix?',
        'Batteria',
        'Basso',
        'Chitarra',
        'Tastiera',
        8,
        3,
        10
    ),
    (
        'clickfirst',
        'In quale anno i Beatles si sono sciolti?',
        '',
        '',
        '',
        '',
        8,
        NULL,
        15
    ),
    (
        'truefalse',
        'Mozart è nato in Austria',
        '',
        '',
        '',
        '',
        8,
        1,
        20
    ),
    (
        'multiple',
        'Quale band ha cantato "Bohemian Rhapsody"?',
        'The Beatles',
        'Led Zeppelin',
        'Queen',
        'Pink Floyd',
        8,
        3,
        10
    ),
    -- ── Cucina (9) ── multiple + truefalse ──
    (
        'multiple',
        'Qual è l''ingrediente principale del pesto genovese?',
        'Prezzemolo',
        'Basilico',
        'Rucola',
        'Spinaci',
        9,
        2,
        10
    ),
    (
        'multiple',
        'Da quale regione italiana proviene la pizza margherita?',
        'Lazio',
        'Sicilia',
        'Campania',
        'Toscana',
        9,
        3,
        10
    ),
    (
        'truefalse',
        'Il risotto alla milanese contiene zafferano',
        '',
        '',
        '',
        '',
        9,
        1,
        20
    ),
    (
        'multiple',
        'Quale formaggio si usa nella carbonara tradizionale?',
        'Parmigiano',
        'Pecorino Romano',
        'Grana Padano',
        'Mozzarella',
        9,
        2,
        10
    ),
    (
        'clickfirst',
        'In quale anno è nata la Nutella?',
        '',
        '',
        '',
        '',
        9,
        NULL,
        15
    );

-- ── Question sets ──────────────────────────────────────────────────
-- Set 1: Mix Generale (5 domande dal cat. Generale)
INSERT INTO
    qsets (set_name, set_description)
VALUES
    ('Mix Generale', 'Domande di cultura generale'),
    (
        'Storia Veloce',
        'Domande click-first sulla storia'
    ),
    ('Sport Mania', 'Domande su sport internazionali'),
    ('Scienza Quiz', 'Domande vero/falso di scienze'),
    ('Giro del Mondo', 'Domande di geografia'),
    ('Cinefili', 'Domande su film e serie TV'),
    ('Arte & Storia', 'Mix arte e storia'),
    ('Musica Maestro', 'Domande sulla musica'),
    ('A Tavola!', 'Domande di cucina italiana');

-- Associate questions with sets (5 domande ciascuno)
INSERT INTO
    qset_questions (qset_id, question_id, order_in_set)
VALUES
    -- Set 1: Mix Generale → domande 1-5
    (1, 1, 1),
    (1, 2, 2),
    (1, 3, 3),
    (1, 4, 4),
    (1, 5, 5),
    -- Set 2: Storia Veloce → domande 6-10
    (2, 6, 1),
    (2, 7, 2),
    (2, 8, 3),
    (2, 9, 4),
    (2, 10, 5),
    -- Set 3: Sport Mania → domande 11-15
    (3, 11, 1),
    (3, 12, 2),
    (3, 13, 3),
    (3, 14, 4),
    (3, 15, 5),
    -- Set 4: Scienza Quiz → domande 16-20
    (4, 16, 1),
    (4, 17, 2),
    (4, 18, 3),
    (4, 19, 4),
    (4, 20, 5),
    -- Set 5: Giro del Mondo → domande 21-25
    (5, 21, 1),
    (5, 22, 2),
    (5, 23, 3),
    (5, 24, 4),
    (5, 25, 5),
    -- Set 6: Cinefili → domande 26-10
    (6, 26, 1),
    (6, 27, 2),
    (6, 28, 3),
    (6, 29, 4),
    (6, 10, 5),
    -- Set 7: Arte & Storia → domande 31-35
    (7, 31, 1),
    (7, 32, 2),
    (7, 33, 3),
    (7, 34, 4),
    (7, 35, 5),
    -- Set 8: Musica Maestro → domande 36-40
    (8, 36, 1),
    (8, 37, 2),
    (8, 38, 3),
    (8, 39, 4),
    (8, 40, 5),
    -- Set 9: A Tavola! → domande 41-45
    (9, 41, 1),
    (9, 42, 2),
    (9, 43, 3),
    (9, 44, 4),
    (9, 45, 5);
