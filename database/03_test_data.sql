USE marriage_game;USE marriage_game;



-- Inserimento domande (119 domande totali)-- Inserimento 20 set di domande di test

INSERT INTO questions (round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUESINSERT INTO

('multiple', 'Qual è la capitale della Francia?', 'Londra', 'Parigi', 'Berlino', 'Madrid', 2, 10),    qsets (set_name, set_description)

('multiple', 'Quanti continenti ci sono sulla Terra?', 'Cinque', 'Sei', 'Sette', 'Otto', 3, 10),VALUES

('multiple', 'Chi ha dipinto la Gioconda?', 'Michelangelo', 'Leonardo da Vinci', 'Raffaello', 'Botticelli', 2, 10),    (

('multiple', 'Qual è il pianeta più grande del sistema solare?', 'Saturno', 'Giove', 'Urano', 'Nettuno', 2, 10),        'Animali del Mondo',

('multiple', 'In che anno è avvenuta la caduta del Muro di Berlino?', '1987', '1988', '1989', '1990', 3, 10),        'Curiosità e fatti interessanti sugli animali'

('multiple', 'Qual è l''oceano più grande?', 'Atlantico', 'Indiano', 'Pacifico', 'Artico', 3, 10),    ),

('multiple', 'Chi ha scritto "Orgoglio e Pregiudizio"?', 'Charlotte Brontë', 'Jane Austen', 'Emily Dickinson', 'George Eliot', 2, 10),    (

('multiple', 'Qual è l''elemento chimico con simbolo Au?', 'Argento', 'Oro', 'Alluminio', 'Arsenico', 2, 10),        'Arte e Pittura',

('multiple', 'In quale anno è nata la Wikipedia?', '2000', '2001', '2002', '2003', 3, 10),        'Grandi artisti e capolavori dell''arte'

('multiple', 'Chi è il fondatore di Microsoft?', 'Steve Jobs', 'Bill Gates', 'Mark Zuckerberg', 'Elon Musk', 2, 10),    ),

('truefalse', 'La Grande Muraglia Cinese è visibile dallo spazio ad occhio nudo', 'Vero', 'Falso', '', '', 2, 10),    (

('truefalse', 'Un anno luce è una misura di tempo', 'Vero', 'Falso', '', '', 2, 10),        'Astronomia',

('truefalse', 'Il diamante è il materiale più duro in natura', 'Vero', 'Falso', '', '', 1, 10),        'Stelle, pianeti e misteri dell''universo'

('truefalse', 'Il sole è una stella', 'Vero', 'Falso', '', '', 1, 10),    ),

('truefalse', 'L''Italia è la terza nazione più grande d''Europa', 'Vero', 'Falso', '', '', 2, 10),    (

('multiple', 'Qual è il fiume più lungo del mondo?', 'Amazzoni', 'Nilo', 'Yangtsé', 'Mississippi', 2, 10),        'Cinema Italiano',

('multiple', 'In quale città si trova la Torre Eiffel?', 'Londra', 'Berlino', 'Parigi', 'Amsterdam', 3, 10),        'Film e registi del cinema italiano'

('multiple', 'Quanti lati ha un ottagono?', 'Sei', 'Sette', 'Otto', 'Nove', 3, 10),    ),

('multiple', 'Chi ha inventato la lampadina?', 'Nicola Tesla', 'Thomas Edison', 'Alexander Graham Bell', 'Michael Faraday', 2, 10),    (

('multiple', 'Qual è il paese più popoloso del mondo?', 'India', 'Cina', 'Indonesia', 'Pakistan', 1, 10),        'Cucina Italiana',

('truefalse', 'L''acqua bolle a 100 gradi Celsius al livello del mare', 'Vero', 'Falso', '', '', 1, 10),        'Piatti tradizionali e ricette regionali'

('multiple', 'Quale sport è conosciuto come "il re degli sport"?', 'Tennis', 'Calcio', 'Basket', 'Nuoto', 2, 10),    ),

('multiple', 'In quale anno è iniziata la Seconda Guerra Mondiale?', '1938', '1939', '1940', '1941', 2, 10),    (

('multiple', 'Qual è la moneta ufficiale del Giappone?', 'Yuan', 'Won', 'Yen', 'Baht', 3, 10),        'Geografia Europa',

('multiple', 'Chi ha scritto l''Odissea?', 'Virgilio', 'Omero', 'Erodoto', 'Tucidide', 2, 10),        'Capitali, bandiere e luoghi d''Europa'

('truefalse', 'Il cervello umano utilizza il 10% delle sue capacità', 'Vero', 'Falso', '', '', 2, 10),    ),

('multiple', 'Quale gas respirano le piante durante la fotosintesi?', 'Ossigeno', 'Anidride carbonica', 'Azoto', 'Idrogeno', 2, 10),    (

('multiple', 'In quale paese si trovano le Piramidi di Giza?', 'Libia', 'Egitto', 'Sudan', 'Arabia Saudita', 2, 10),        'Geografia Mondiale',

('multiple', 'Quanti anni ha avuto Mozart quando morì?', '35', '39', '45', '51', 2, 10),        'Paesi, continenti e geografia del mondo'

('multiple', 'Quale città è la capitale dell''Australia?', 'Sydney', 'Melbourne', 'Canberra', 'Brisbane', 3, 10),    ),

('multiple', 'Chi è considerato il padre della filosofia moderna?', 'Platone', 'Cartesio', 'Aristotele', 'Kant', 2, 10),    (

('truefalse', 'I pipistrelli sono ciechi', 'Vero', 'Falso', '', '', 2, 10),        'Giochi da Tavolo',

('multiple', 'In quale anno è caduto l''Impero Romano d''Occidente?', '410', '440', '476', '500', 3, 10),        'Monopoly, Risiko e altri classici'

('multiple', 'Qual è il paese più freddo della Terra?', 'Canada', 'Russia', 'Antartide', 'Groenlandia', 3, 10),    ),

('truefalse', 'L''Everest è la montagna più alta della Terra', 'Vero', 'Falso', '', '', 1, 10),    (

('multiple', 'Chi ha scoperto l''America nel 1492?', 'Leif Erikson', 'Cristoforo Colombo', 'Bartolomeo Diaz', 'Vasco da Gama', 2, 10),        'Harry Potter',

('multiple', 'Quanti elementi ci sono nella tavola periodica?', '98', '106', '118', '127', 3, 10),        'Il mondo magico di J.K. Rowling'

('multiple', 'Quale artista ha tagliato un pezzo della sua orecchia?', 'Pablo Picasso', 'Vincent van Gogh', 'Claude Monet', 'Salvador Dalì', 2, 10),    ),

('multiple', 'In quale anno è stato inventato l''internet?', '1969', '1983', '1991', '1995', 1, 10),    (

('truefalse', 'Il miele non scade mai', 'Vero', 'Falso', '', '', 1, 10),        'Invenzioni Storiche',

('multiple', 'Quale città ospita il Vaticano?', 'Milano', 'Roma', 'Napoli', 'Firenze', 2, 10),        'Scoperte che hanno cambiato il mondo'

('multiple', 'Quanti giorni ha l''anno bisestile?', '363', '364', '365', '366', 4, 10),    ),

('multiple', 'Chi ha scritto "Le Mille e una notte"?', 'Anonimo', 'Scheherazade', 'Abu Muhammad', 'Desconosciuto', 1, 10),    (

('multiple', 'Quale gas è essenziale per la respirazione umana?', 'Azoto', 'Ossigeno', 'Anidride carbonica', 'Elio', 2, 10),        'Letteratura Classica',

('multiple', 'In quale anno è stata fondata la Coca-Cola?', '1886', '1890', '1895', '1900', 1, 10),        'Grandi autori e opere letterarie'

('truefalse', 'Napoleone era francese', 'Vero', 'Falso', '', '', 1, 10),    ),

('multiple', 'Quale continente è il più piccolo?', 'Australia', 'Europa', 'Asia', 'Africa', 1, 10),    (

('multiple', 'Chi è stato il primo presidente degli Stati Uniti?', 'Thomas Jefferson', 'George Washington', 'James Madison', 'John Adams', 2, 10),        'Matematica Base',

('multiple', 'In quale anno è avvenuta la Rivoluzione Francese?', '1787', '1788', '1789', '1790', 3, 10),        'Operazioni e problemi matematici semplici'

('multiple', 'Quale animale è il più veloce sulla terra?', 'Leone', 'Antilope', 'Ghepardo', 'Cavallo', 3, 10),    ),

('truefalse', 'Il cuore umano pompa il sangue', 'Vero', 'Falso', '', '', 1, 10),    (

('multiple', 'Quale numero atomico ha l''idrogeno?', '1', '2', '3', '4', 1, 10),        'Mitologia Greca',

('multiple', 'In quale città è situata la Statua della Libertà?', 'Boston', 'Filadelfia', 'New York', 'Washington', 3, 10),        'Dei, eroi e leggende dell''antica Grecia'

('multiple', 'Quale poeta italiano ha scritto la "Divina Commedia"?', 'Petrarca', 'Dante Alighieri', 'Boccaccio', 'Ariosto', 2, 10),    ),

('multiple', 'In quale anno è stato lanciato il primo satellite artificiale?', '1955', '1956', '1957', '1958', 3, 10),    (

('truefalse', 'La Terra è piatta', 'Vero', 'Falso', '', '', 2, 10),        'Musica Pop',

('multiple', 'Quanti stati indipendenti ci sono nel mondo?', '190', '193', '195', '200', 3, 10),        'Cantanti e band della musica pop mondiale'

('multiple', 'Chi ha dipinto la Cappella Sistina?', 'Raffaello', 'Michelangelo', 'Leonardo da Vinci', 'Donato Bramante', 2, 10),    ),

('multiple', 'Quale città è conosciuta come la "Città Eterna"?', 'Atene', 'Roma', 'Istanbul', 'Gerusalemme', 2, 10),    (

('multiple', 'In quale anno è morto Winston Churchill?', '1963', '1964', '1965', '1966', 3, 10),        'Personaggi Storici',

('multiple', 'Quale animale depone le uova più grandi?', 'Struzzo', 'Emù', 'Oca', 'Cigno', 1, 10),        'Grandi figure della storia mondiale'

('truefalse', 'La velocità della luce è costante', 'Vero', 'Falso', '', '', 1, 10),    ),

('multiple', 'In quale regione italiana si trova il Vesuvio?', 'Campania', 'Lazio', 'Sicilia', 'Calabria', 1, 10),    (

('multiple', 'Quale elemento è il più comune nell''universo?', 'Elio', 'Idrogeno', 'Ossigeno', 'Carbonio', 2, 10),        'Quiz Matrimonio',

('multiple', 'Chi ha inventato la stampa a caratteri mobili?', 'Ci Lun', 'Gutenberg', 'Bi Sheng', 'Wang Zhen', 2, 10),        'Domande divertenti per conoscere gli sposi'

('multiple', 'In quale anno è stato abolito lo schiavismo negli USA?', '1861', '1862', '1863', '1864', 3, 10),    ),

('truefalse', 'Lo zucchero è solubile in acqua', 'Vero', 'Falso', '', '', 1, 10),    (

('multiple', 'Quale è il deserto più grande del mondo?', 'Sahara', 'Antartide', 'Gobi', 'Kalahari', 2, 10),        'Scienze Naturali',

('multiple', 'Chi ha scritto "Orgoglio e Pregiudizio"?', 'Jane Austen', 'Charlotte Brontë', 'Emily Brontë', 'George Eliot', 1, 10),        'Biologia, chimica e fisica di base'

('multiple', 'Quanti sensi ha l''uomo?', 'Quattro', 'Cinque', 'Sei', 'Sette', 2, 10),    ),

('multiple', 'In quale città si trova il Colosseo?', 'Napoli', 'Roma', 'Firenze', 'Venezia', 2, 10),    (

('truefalse', 'La luce si muove più velocemente del suono', 'Vero', 'Falso', '', '', 1, 10),        'Sport Olimpici',

('multiple', 'Quale è il vulcano più attivo del mondo?', 'Etna', 'Piton de la Fournaise', 'Sakurajima', 'Merapi', 3, 10),        'Discipline, record e campioni olimpici'

('multiple', 'Chi è autore della teoria della gravità?', 'Galilei', 'Newton', 'Einstein', 'Kepler', 2, 10),    ),

('multiple', 'In quale anno è nata l''Organizzazione delle Nazioni Unite?', '1944', '1945', '1946', '1947', 2, 10),    (

('multiple', 'Quale numero romano rappresenta 50?', 'XL', 'L', 'LX', 'XC', 2, 10),        'Storia Italiana',

('truefalse', 'L''oro è il metallo più denso', 'Vero', 'Falso', '', '', 2, 10),        'Eventi e personaggi della storia d''Italia'

('multiple', 'Chi ha vinto il primo Nobel per la Pace?', 'Theodore Roosevelt', 'Auguste Beernaert', 'Henry Dunant', 'Albert Gobat', 3, 10),    ),

('multiple', 'In quale paese si trova la Grande Barriera Corallina?', 'Indonesia', 'Filippine', 'Australia', 'Messico', 3, 10),    (

('multiple', 'Quale scienziata ha scoperto il radio?', 'Marie Curie', 'Rosalind Franklin', 'Emmy Noether', 'Hedy Lamarr', 1, 10),        'Videogiochi',

('multiple', 'In quale anno è iniziata la Guerra dei Cent''anni?', '1337', '1340', '1350', '1360', 1, 10),        'Console, giochi e cultura videoludica'

('truefalse', 'Il ghiaccio galleggia nell''acqua', 'Vero', 'Falso', '', '', 1, 10),    );

('multiple', 'Quale è la popolazione dell''India?', 'Oltre 1 miliardo', 'Intorno a 800 milioni', 'Intorno a 500 milioni', 'Intorno a 300 milioni', 1, 10),

('multiple', 'Chi ha scritto "Romeo e Giulietta"?', 'Christopher Marlowe', 'William Shakespeare', 'Ben Jonson', 'John Webster', 2, 10),-- Inserimento domande (senza question_set_id e round_number)

('multiple', 'In quale città si trova il Big Ben?', 'Edimburgo', 'Londra', 'Manchester', 'Bristol', 2, 10),INSERT INTO

('multiple', 'Quale è il numero di ossa nel corpo adulto umano?', '186', '206', '226', '246', 2, 10),    questions (

('truefalse', 'La Terra orbita intorno al Sole', 'Vero', 'Falso', '', '', 1, 10),        round_type,

('multiple', 'Chi è il filosofo autore di "Critica della ragion pura"?', 'Hegel', 'Kant', 'Hume', 'Schopenhauer', 2, 10),        question,

('multiple', 'In quale anno è stata fondata l''Unione Europea?', '1957', '1960', '1965', '1970', 1, 10),        option1,

('multiple', 'Quale animale può muovere le orecchie indipendentemente?', 'Gatto', 'Cane', 'Cavallo', 'Tutti gli animali', 4, 10),        option2,

('truefalse', 'L''atmosfera terrestre contiene principalmente azoto', 'Vero', 'Falso', '', '', 1, 10),        option3,

('multiple', 'Chi ha dipinto "La Notte Stellata"?', 'Paul Cézanne', 'Vincent van Gogh', 'Claude Monet', 'Paul Gauguin', 2, 10),        option4,

('multiple', 'In quale anno è stato abolito il feudalesimo in Francia?', '1789', '1790', '1791', '1792', 1, 10);        correct_answer,

        timer

-- Inserimento game_settings (F1-style scoring)    )

INSERT INTO game_settings (setting_key, setting_value) VALUESVALUES

('clickfirst', 50),    (

('multiple_1', 25),        'multiple',

('multiple_2', 18),        'Qual è l''animale più veloce del mondo?',

('multiple_3', 15),        'Leone',

('multiple_4', 12),        'Ghepardo',

('multiple_5', 10),        'Leopardo',

('multiple_6', 8),        'Tigre',

('multiple_7', 6),        2,

('multiple_8', 4),        10

('multiple_9', 2),    ),

('multiple_10', 1),    (

('truefalse_1', 20),        'truefalse',

('truefalse_2', 15),        'I pinguini possono volare',

('truefalse_3', 12),        'Vero',

('truefalse_4', 10),        'Falso',

('truefalse_5', 8),        '',

('truefalse_6', 6),        '',

('truefalse_7', 5),        2,

('truefalse_8', 3),        10

('truefalse_9', 2),    ),

('truefalse_10', 1);    (

        'multiple',
        'Qual è il mammifero più grande del mondo?',
        'Elefante africano',
        'Balenottera azzurra',
        'Giraffa',
        'Ippopotamo',
        2,
        10
    ),
    (
        'multiple',
        'Quanti cuori ha il polpo?',
        '1',
        '2',
        '3',
        '5',
        3,
        10
    ),
    (
        'truefalse',
        'Gli squali sono pesci',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Quale animale può dormire in piedi?',
        'Mucca',
        'Cavallo',
        'Pecora',
        'Tutti',
        4,
        10
    ),
    (
        'multiple',
        'Chi ha dipinto la Cappella Sistina?',
        'Leonardo',
        'Michelangelo',
        'Raffaello',
        'Donatello',
        2,
        30
    ),
    (
        'multiple',
        'Dove si trova la Gioconda?',
        'Uffizi',
        'Louvre',
        'Prado',
        'Metropolitan',
        2,
        25
    ),
    (
        'truefalse',
        'Van Gogh si tagliò un orecchio',
        'Vero',
        'Falso',
        '',
        '',
        1,
        20
    ),
    (
        'multiple',
        'Chi ha dipinto Guernica?',
        'Dalí',
        'Picasso',
        'Miró',
        'Goya',
        2,
        30
    ),
    (
        'multiple',
        'In quale città nacque Leonardo da Vinci?',
        'Firenze',
        'Vinci',
        'Milano',
        'Roma',
        2,
        25
    ),
    (
        'truefalse',
        'La Gioconda non ha sopracciglia',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Qual è il pianeta più grande del sistema solare?',
        'Saturno',
        'Giove',
        'Nettuno',
        'Urano',
        2,
        25
    ),
    (
        'truefalse',
        'Il Sole è una stella',
        'Vero',
        'Falso',
        '',
        '',
        1,
        15
    ),
    (
        'multiple',
        'Quanti pianeti ci sono nel sistema solare?',
        '7',
        '8',
        '9',
        '10',
        2,
        25
    ),
    (
        'multiple',
        'Quale pianeta è chiamato il pianeta rosso?',
        'Venere',
        'Marte',
        'Mercurio',
        'Giove',
        2,
        20
    ),
    (
        'truefalse',
        'La Luna è un pianeta',
        'Vero',
        'Falso',
        '',
        '',
        2,
        15
    ),
    (
        'multiple',
        'Quanto tempo impiega la luce del Sole a raggiungere la Terra?',
        '8 secondi',
        '8 minuti',
        '8 ore',
        '8 giorni',
        2,
        10
    ),
    (
        'multiple',
        'Chi ha diretto La Dolce Vita?',
        'Visconti',
        'Fellini',
        'Antonioni',
        'Rossellini',
        2,
        30
    ),
    (
        'multiple',
        'In che anno uscì Ladri di biciclette?',
        '1945',
        '1948',
        '1951',
        '1954',
        2,
        30
    ),
    (
        'truefalse',
        'Roberto Benigni ha vinto l''Oscar',
        'Vero',
        'Falso',
        '',
        '',
        1,
        20
    ),
    (
        'multiple',
        'Chi ha interpretato Totò?',
        'Antonio De Curtis',
        'Totò Sapore',
        'Alberto Sordi',
        'Vittorio Gassman',
        1,
        25
    ),
    (
        'multiple',
        'Quale film di Tornatore ha vinto l''Oscar?',
        'Malena',
        'Cinema Paradiso',
        'La leggenda del pianista',
        'Baaria',
        2,
        10
    ),
    (
        'multiple',
        'Qual è l''ingrediente principale del pesto?',
        'Prezzemolo',
        'Basilico',
        'Rucola',
        'Menta',
        2,
        25
    ),
    (
        'truefalse',
        'La pizza Margherita ha il pomodoro',
        'Vero',
        'Falso',
        '',
        '',
        1,
        15
    ),
    (
        'multiple',
        'Di quale regione è tipica la carbonara?',
        'Campania',
        'Lazio',
        'Toscana',
        'Emilia',
        2,
        25
    ),
    (
        'multiple',
        'Qual è il formaggio del parmigiano?',
        'Latte di bufala',
        'Latte di pecora',
        'Latte di mucca',
        'Latte di capra',
        3,
        30
    ),
    (
        'truefalse',
        'Il tiramisù contiene caffè',
        'Vero',
        'Falso',
        '',
        '',
        1,
        15
    ),
    (
        'multiple',
        'Quale città è famosa per il panettone?',
        'Roma',
        'Napoli',
        'Milano',
        'Torino',
        3,
        10
    ),
    (
        'multiple',
        'Qual è la capitale della Germania?',
        'Monaco',
        'Berlino',
        'Amburgo',
        'Francoforte',
        2,
        20
    ),
    (
        'multiple',
        'Qual è il fiume più lungo d''Europa?',
        'Danubio',
        'Reno',
        'Volga',
        'Tamigi',
        3,
        30
    ),
    (
        'truefalse',
        'La Svizzera fa parte dell''Unione Europea',
        'Vero',
        'Falso',
        '',
        '',
        2,
        20
    ),
    (
        'multiple',
        'Quanti stati confina l''Italia?',
        '4',
        '5',
        '6',
        '7',
        3,
        25
    ),
    (
        'multiple',
        'Qual è la montagna più alta d''Europa?',
        'Monte Bianco',
        'Cervino',
        'Monte Rosa',
        'Elbrus',
        4,
        30
    ),
    (
        'truefalse',
        'La Spagna ha una monarchia',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Qual è il paese più grande del mondo?',
        'Canada',
        'Cina',
        'Russia',
        'USA',
        3,
        25
    ),
    (
        'multiple',
        'Qual è il deserto più grande del mondo?',
        'Sahara',
        'Gobi',
        'Antartide',
        'Kalahari',
        3,
        30
    ),
    (
        'truefalse',
        'L''Australia è un continente',
        'Vero',
        'Falso',
        '',
        '',
        1,
        15
    ),
    (
        'multiple',
        'Quanti continenti ci sono?',
        '5',
        '6',
        '7',
        '8',
        3,
        20
    ),
    (
        'multiple',
        'Quale oceano è il più grande?',
        'Atlantico',
        'Pacifico',
        'Indiano',
        'Artico',
        2,
        25
    ),
    (
        'truefalse',
        'L''Islanda si trova sopra il circolo polare artico',
        'Vero',
        'Falso',
        '',
        '',
        2,
        10
    ),
    (
        'multiple',
        'In quale gioco si deve conquistare il mondo?',
        'Monopoly',
        'Risiko',
        'Scarabeo',
        'Cluedo',
        2,
        25
    ),
    (
        'truefalse',
        'Nel Monopoly si possono costruire alberghi',
        'Vero',
        'Falso',
        '',
        '',
        1,
        20
    ),
    (
        'multiple',
        'Quante caselle ha la scacchiera?',
        '49',
        '64',
        '81',
        '100',
        2,
        25
    ),
    (
        'multiple',
        'Quale pezzo degli scacchi può muoversi a L?',
        'Alfiere',
        'Torre',
        'Cavallo',
        'Regina',
        3,
        25
    ),
    (
        'truefalse',
        'A Cluedo si deve scoprire un assassino',
        'Vero',
        'Falso',
        '',
        '',
        1,
        15
    ),
    (
        'multiple',
        'Quanti pezzi ha ogni giocatore a Dama?',
        '10',
        '12',
        '15',
        '16',
        2,
        10
    ),
    (
        'multiple',
        'Qual è il nome completo di Harry Potter?',
        'Harry James Potter',
        'Harry John Potter',
        'Harry Jack Potter',
        'Harry Joseph Potter',
        1,
        30
    ),
    (
        'multiple',
        'Quale casa di Hogwarts ha come animale un leone?',
        'Serpeverde',
        'Corvonero',
        'Grifondoro',
        'Tassorosso',
        3,
        25
    ),
    (
        'truefalse',
        'Hermione ha un gatto',
        'Vero',
        'Falso',
        '',
        '',
        1,
        20
    ),
    (
        'multiple',
        'Come si chiama il gufo di Harry?',
        'Edvige',
        'Grattastinchi',
        'Testadipelo',
        'Ron',
        1,
        25
    ),
    (
        'multiple',
        'Chi è il preside di Hogwarts?',
        'Silente',
        'Piton',
        'McGranitt',
        'Lumacorno',
        1,
        25
    ),
    (
        'truefalse',
        'Voldemort ha paura della morte',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Chi ha inventato la lampadina?',
        'Tesla',
        'Edison',
        'Einstein',
        'Franklin',
        2,
        25
    ),
    (
        'truefalse',
        'Il telefono fu inventato da Meucci',
        'Vero',
        'Falso',
        '',
        '',
        1,
        20
    ),
    (
        'multiple',
        'In che anno fu inventata la ruota?',
        '3500 a.C.',
        '1000 a.C.',
        '500 d.C.',
        '1000 d.C.',
        1,
        35
    ),
    (
        'multiple',
        'Chi ha inventato la radio?',
        'Edison',
        'Marconi',
        'Tesla',
        'Bell',
        2,
        25
    ),
    (
        'truefalse',
        'I fratelli Wright inventarono l''aeroplano',
        'Vero',
        'Falso',
        '',
        '',
        1,
        20
    ),
    (
        'multiple',
        'Chi inventò la stampa a caratteri mobili?',
        'Galileo',
        'Newton',
        'Gutenberg',
        'Leonardo',
        3,
        10
    ),
    (
        'multiple',
        'Chi ha scritto la Divina Commedia?',
        'Petrarca',
        'Dante',
        'Boccaccio',
        'Ariosto',
        2,
        25
    ),
    (
        'multiple',
        'Quale di questi è un romanzo di Manzoni?',
        'I Malavoglia',
        'I Promessi Sposi',
        'Il Gattopardo',
        'Pinocchio',
        2,
        25
    ),
    (
        'truefalse',
        'Shakespeare era italiano',
        'Vero',
        'Falso',
        '',
        '',
        2,
        15
    ),
    (
        'multiple',
        'Chi ha scritto l''Odissea?',
        'Virgilio',
        'Omero',
        'Ovidio',
        'Eschilo',
        2,
        25
    ),
    (
        'multiple',
        'Quale poeta scrisse "Il cinque maggio"?',
        'Leopardi',
        'Manzoni',
        'Foscolo',
        'Carducci',
        2,
        30
    ),
    (
        'truefalse',
        'Pinocchio fu scritto da Collodi',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Quanto fa 7 x 8?',
        '54',
        '56',
        '58',
        '64',
        2,
        20
    ),
    (
        'multiple',
        'Qual è la radice quadrata di 64?',
        '6',
        '7',
        '8',
        '9',
        3,
        25
    ),
    (
        'truefalse',
        '5 + 5 = 10',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Quanto fa 100 diviso 4?',
        '20',
        '25',
        '30',
        '40',
        2,
        20
    ),
    (
        'multiple',
        'Quale numero è primo?',
        '9',
        '15',
        '17',
        '21',
        3,
        25
    ),
    (
        'truefalse',
        'Il numero Pi greco è infinito',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Chi è il re degli dei greci?',
        'Poseidone',
        'Zeus',
        'Ade',
        'Apollo',
        2,
        25
    ),
    (
        'multiple',
        'Chi è la dea della sapienza?',
        'Afrodite',
        'Artemide',
        'Atena',
        'Era',
        3,
        25
    ),
    (
        'truefalse',
        'Poseidone è il dio del mare',
        'Vero',
        'Falso',
        '',
        '',
        1,
        15
    ),
    (
        'multiple',
        'Chi uccise la Medusa?',
        'Ercole',
        'Perseo',
        'Teseo',
        'Achille',
        2,
        30
    ),
    (
        'multiple',
        'Quante fatiche dovette compiere Ercole?',
        '7',
        '10',
        '12',
        '15',
        3,
        25
    ),
    (
        'truefalse',
        'Icaro volò troppo vicino al sole',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Chi era il frontman dei Queen?',
        'Elton John',
        'Freddie Mercury',
        'David Bowie',
        'Mick Jagger',
        2,
        25
    ),
    (
        'multiple',
        'Quale cantante è chiamata "The Queen of Pop"?',
        'Beyoncé',
        'Madonna',
        'Lady Gaga',
        'Britney Spears',
        2,
        25
    ),
    (
        'truefalse',
        'I Beatles erano americani',
        'Vero',
        'Falso',
        '',
        '',
        2,
        15
    ),
    (
        'multiple',
        'Chi cantava "Thriller"?',
        'Prince',
        'Michael Jackson',
        'James Brown',
        'Stevie Wonder',
        2,
        25
    ),
    (
        'multiple',
        'Quale band cantava "Bohemian Rhapsody"?',
        'The Who',
        'Pink Floyd',
        'Queen',
        'Led Zeppelin',
        3,
        25
    ),
    (
        'truefalse',
        'Elvis Presley era chiamato "The King"',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Chi scoprì l''America?',
        'Vespucci',
        'Colombo',
        'Magellano',
        'Cook',
        2,
        25
    ),
    (
        'multiple',
        'Chi fu il primo imperatore di Roma?',
        'Cesare',
        'Augusto',
        'Nerone',
        'Traiano',
        2,
        30
    ),
    (
        'truefalse',
        'Napoleone era francese',
        'Vero',
        'Falso',
        '',
        '',
        2,
        20
    ),
    (
        'multiple',
        'Chi dipinse la Cappella Sistina?',
        'Leonardo',
        'Michelangelo',
        'Raffaello',
        'Caravaggio',
        2,
        25
    ),
    (
        'multiple',
        'Chi scrisse "Il Principe"?',
        'Dante',
        'Machiavelli',
        'Petrarca',
        'Boccaccio',
        2,
        25
    ),
    (
        'truefalse',
        'Gandhi era indiano',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Dove si sono conosciuti gli sposi?',
        'Al lavoro',
        'All''università',
        'In vacanza',
        'Ad una festa',
        2,
        30
    ),
    (
        'truefalse',
        'Gli sposi hanno fatto il primo viaggio insieme in Italia',
        'Vero',
        'Falso',
        '',
        '',
        1,
        20
    ),
    (
        'multiple',
        'Qual è il piatto preferito dello sposo?',
        'Pizza',
        'Pasta alla carbonara',
        'Bistecca',
        'Sushi',
        2,
        25
    ),
    (
        'clickfirst',
        'Chi ha detto "Ti amo" per primo?',
        '',
        '',
        '',
        '',
        1,
        30
    ),
    (
        'multiple',
        'In che mese si sono fidanzati?',
        'Gennaio',
        'Maggio',
        'Settembre',
        'Dicembre',
        3,
        30
    ),
    (
        'truefalse',
        'Lo sposo ha una sorella',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Qual è l''elemento chimico dell''acqua?',
        'CO2',
        'H2O',
        'O2',
        'NaCl',
        2,
        20
    ),
    (
        'truefalse',
        'La fotosintesi produce ossigeno',
        'Vero',
        'Falso',
        '',
        '',
        1,
        20
    ),
    (
        'multiple',
        'Quanti denti ha un adulto?',
        '28',
        '30',
        '32',
        '34',
        3,
        25
    ),
    (
        'multiple',
        'Qual è l''organo più grande del corpo umano?',
        'Fegato',
        'Polmone',
        'Pelle',
        'Cervello',
        3,
        25
    ),
    (
        'truefalse',
        'Il diamante è il minerale più duro',
        'Vero',
        'Falso',
        '',
        '',
        1,
        20
    ),
    (
        'multiple',
        'Quale gas respiriamo?',
        'Ossigeno',
        'Azoto',
        'Anidride carbonica',
        'Idrogeno',
        1,
        10
    ),
    (
        'multiple',
        'Dove si svolsero le prime Olimpiadi moderne?',
        'Parigi',
        'Atene',
        'Londra',
        'Roma',
        2,
        30
    ),
    (
        'multiple',
        'Quanti anelli ha il simbolo olimpico?',
        '4',
        '5',
        '6',
        '7',
        2,
        20
    ),
    (
        'truefalse',
        'Le Olimpiadi si tengono ogni 4 anni',
        'Vero',
        'Falso',
        '',
        '',
        1,
        15
    ),
    (
        'multiple',
        'Chi detiene il record dei 100m?',
        'Carl Lewis',
        'Usain Bolt',
        'Justin Gatlin',
        'Asafa Powell',
        2,
        30
    ),
    (
        'multiple',
        'In quale sport si usa una racchetta?',
        'Pallavolo',
        'Tennis',
        'Basket',
        'Calcio',
        2,
        20
    ),
    (
        'truefalse',
        'Il nuoto sincronizzato è uno sport olimpico',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'In che anno fu unificata l''Italia?',
        '1848',
        '1861',
        '1870',
        '1900',
        2,
        30
    ),
    (
        'multiple',
        'Chi fu il primo re d''Italia?',
        'Garibaldi',
        'Vittorio Emanuele II',
        'Cavour',
        'Mazzini',
        2,
        30
    ),
    (
        'truefalse',
        'Roma fu sempre la capitale d''Italia',
        'Vero',
        'Falso',
        '',
        '',
        2,
        20
    ),
    (
        'multiple',
        'Chi guidò la spedizione dei Mille?',
        'Mazzini',
        'Garibaldi',
        'Cavour',
        'D''Azeglio',
        2,
        25
    ),
    (
        'multiple',
        'In che anno finì la Seconda Guerra Mondiale?',
        '1943',
        '1944',
        '1945',
        '1946',
        3,
        25
    ),
    (
        'truefalse',
        'Mussolini fondò il fascismo',
        'Vero',
        'Falso',
        '',
        '',
        1,
        10
    ),
    (
        'multiple',
        'Chi è il protagonista di Super Mario?',
        'Luigi',
        'Mario',
        'Wario',
        'Yoshi',
        2,
        20
    ),
    (
        'multiple',
        'Quale console produsse la Sony?',
        'Xbox',
        'Nintendo',
        'PlayStation',
        'Sega',
        3,
        20
    ),
    (
        'truefalse',
        'Minecraft è stato creato da Microsoft',
        'Vero',
        'Falso',
        '',
        '',
        2,
        25
    ),
    (
        'multiple',
        'In quale gioco si catturano Pokémon?',
        'Zelda',
        'Pokémon',
        'Digimon',
        'Mario',
        2,
        20
    ),
    (
        'multiple',
        'Quale gioco ha come protagonista Link?',
        'Mario',
        'Zelda',
        'Pokémon',
        'Sonic',
        2,
        25
    ),
    (
        'truefalse',
        'Fortnite è un gioco di strategia',
        'Vero',
        'Falso',
        '',
        '',
        2,
        10
    );

-- Tabella di join N:to:N tra qsets e questions
INSERT INTO
    qset_questions (question_set_id, question_id, order_in_set)
VALUES
    (1, 1, 1),
    (1, 2, 2),
    (1, 3, 3),
    (1, 4, 4),
    (1, 5, 5),
    (1, 6, 6),
    (2, 7, 1),
    (2, 8, 2),
    (2, 9, 3),
    (2, 10, 4),
    (2, 11, 5),
    (2, 12, 6),
    (3, 13, 1),
    (3, 14, 2),
    (3, 15, 3),
    (3, 16, 4),
    (3, 17, 5),
    (3, 18, 6),
    (4, 19, 1),
    (4, 20, 2),
    (4, 21, 3),
    (4, 22, 4),
    (4, 23, 5),
    (5, 24, 1),
    (5, 25, 2),
    (5, 26, 3),
    (5, 27, 4),
    (5, 28, 5),
    (5, 29, 6),
    (6, 30, 1),
    (6, 31, 2),
    (6, 32, 3),
    (6, 33, 4),
    (6, 34, 5),
    (6, 35, 6),
    (7, 36, 1),
    (7, 37, 2),
    (7, 38, 3),
    (7, 39, 4),
    (7, 40, 5),
    (7, 41, 6),
    (8, 42, 1),
    (8, 43, 2),
    (8, 44, 3),
    (8, 45, 4),
    (8, 46, 5),
    (8, 47, 6),
    (9, 48, 1),
    (9, 49, 2),
    (9, 50, 3),
    (9, 51, 4),
    (9, 52, 5),
    (9, 53, 6),
    (10, 54, 1),
    (10, 55, 2),
    (10, 56, 3),
    (10, 57, 4),
    (10, 58, 5),
    (10, 59, 6),
    (11, 60, 1),
    (11, 61, 2),
    (11, 62, 3),
    (11, 63, 4),
    (11, 64, 5),
    (11, 65, 6),
    (12, 66, 1),
    (12, 67, 2),
    (12, 68, 3),
    (12, 69, 4),
    (12, 70, 5),
    (12, 71, 6),
    (13, 72, 1),
    (13, 73, 2),
    (13, 74, 3),
    (13, 75, 4),
    (13, 76, 5),
    (13, 77, 6),
    (14, 78, 1),
    (14, 79, 2),
    (14, 80, 3),
    (14, 81, 4),
    (14, 82, 5),
    (14, 83, 6),
    (15, 84, 1),
    (15, 85, 2),
    (15, 86, 3),
    (15, 87, 4),
    (15, 88, 5),
    (15, 89, 6),
    (16, 90, 1),
    (16, 91, 2),
    (16, 92, 3),
    (16, 93, 4),
    (16, 94, 5),
    (16, 95, 6),
    (17, 96, 1),
    (17, 97, 2),
    (17, 98, 3),
    (17, 99, 4),
    (17, 100, 5),
    (17, 101, 6),
    (18, 102, 1),
    (18, 103, 2),
    (18, 104, 3),
    (18, 105, 4),
    (18, 106, 5),
    (18, 107, 6),
    (19, 108, 1),
    (19, 109, 2),
    (19, 110, 3),
    (19, 111, 4),
    (19, 112, 5),
    (19, 113, 6),
    (20, 114, 1),
    (20, 115, 2),
    (20, 116, 3),
    (20, 117, 4),
    (20, 118, 5),
    (20, 119, 6);

-- Inserimento game_settings con scoring F1-style
INSERT INTO
    game_settings (setting_key, setting_value)
VALUES
    ('clickfirst', 50),
    ('multiple_1', 25),
    ('multiple_2', 18),
    ('multiple_3', 15),
    ('multiple_4', 12),
    ('multiple_5', 10),
    ('multiple_6', 8),
    ('multiple_7', 6),
    ('multiple_8', 4),
    ('multiple_9', 2),
    ('multiple_10', 1),
    ('truefalse_1', 20),
    ('truefalse_2', 15),
    ('truefalse_3', 12),
    ('truefalse_4', 10),
    ('truefalse_5', 8),
    ('truefalse_6', 6),
    ('truefalse_7', 5),
    ('truefalse_8', 3),
    ('truefalse_9', 2),
    ('truefalse_10', 1);
