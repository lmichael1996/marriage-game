USE marriage_game;

-- Inserimento 20 set di domande di test
INSERT INTO question_sets (set_name, set_description) VALUES
('Animali del Mondo', 'Curiosità e fatti interessanti sugli animali'),
('Arte e Pittura', 'Grandi artisti e capolavori dell''arte'),
('Astronomia', 'Stelle, pianeti e misteri dell''universo'),
('Cinema Italiano', 'Film e registi del cinema italiano'),
('Cucina Italiana', 'Piatti tradizionali e ricette regionali'),
('Geografia Europa', 'Capitali, bandiere e luoghi d''Europa'),
('Geografia Mondiale', 'Paesi, continenti e geografia del mondo'),
('Giochi da Tavolo', 'Monopoly, Risiko e altri classici'),
('Harry Potter', 'Il mondo magico di J.K. Rowling'),
('Invenzioni Storiche', 'Scoperte che hanno cambiato il mondo'),
('Letteratura Classica', 'Grandi autori e opere letterarie'),
('Matematica Base', 'Operazioni e problemi matematici semplici'),
('Mitologia Greca', 'Dei, eroi e leggende dell''antica Grecia'),
('Musica Pop', 'Cantanti e band della musica pop mondiale'),
('Personaggi Storici', 'Grandi figure della storia mondiale'),
('Quiz Matrimonio', 'Domande divertenti per conoscere gli sposi'),
('Scienze Naturali', 'Biologia, chimica e fisica di base'),
('Sport Olimpici', 'Discipline, record e campioni olimpici'),
('Storia Italiana', 'Eventi e personaggi della storia d''Italia'),
('Videogiochi', 'Console, giochi e cultura videoludica');

-- Domande per "Animali del Mondo" (set_id = 1)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(1, 1, 'multiple', 'Qual è l''animale più veloce del mondo?', 'Leone', 'Ghepardo', 'Leopardo', 'Tigre', 2, 30),
(1, 2, 'truefalse', 'I pinguini possono volare', 'Vero', 'Falso', '', '', 2, 20),
(1, 3, 'multiple', 'Qual è il mammifero più grande del mondo?', 'Elefante africano', 'Balenottera azzurra', 'Giraffa', 'Ippopotamo', 2, 25),
(1, 4, 'multiple', 'Quanti cuori ha il polpo?', '1', '2', '3', '5', 3, 30),
(1, 5, 'truefalse', 'Gli squali sono pesci', 'Vero', 'Falso', '', '', 1, 20),
(1, 6, 'multiple', 'Quale animale può dormire in piedi?', 'Mucca', 'Cavallo', 'Pecora', 'Tutti', 4, 25);

-- Domande per "Arte e Pittura" (set_id = 2)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(2, 1, 'multiple', 'Chi ha dipinto la Cappella Sistina?', 'Leonardo', 'Michelangelo', 'Raffaello', 'Donatello', 2, 30),
(2, 2, 'multiple', 'Dove si trova la Gioconda?', 'Uffizi', 'Louvre', 'Prado', 'Metropolitan', 2, 25),
(2, 3, 'truefalse', 'Van Gogh si tagliò un orecchio', 'Vero', 'Falso', '', '', 1, 20),
(2, 4, 'multiple', 'Chi ha dipinto Guernica?', 'Dalí', 'Picasso', 'Miró', 'Goya', 2, 30),
(2, 5, 'multiple', 'In quale città nacque Leonardo da Vinci?', 'Firenze', 'Vinci', 'Milano', 'Roma', 2, 25),
(2, 6, 'truefalse', 'La Gioconda non ha sopracciglia', 'Vero', 'Falso', '', '', 1, 20);

-- Domande per "Astronomia" (set_id = 3)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(3, 1, 'multiple', 'Qual è il pianeta più grande del sistema solare?', 'Saturno', 'Giove', 'Nettuno', 'Urano', 2, 25),
(3, 2, 'truefalse', 'Il Sole è una stella', 'Vero', 'Falso', '', '', 1, 15),
(3, 3, 'multiple', 'Quanti pianeti ci sono nel sistema solare?', '7', '8', '9', '10', 2, 25),
(3, 4, 'multiple', 'Quale pianeta è chiamato il pianeta rosso?', 'Venere', 'Marte', 'Mercurio', 'Giove', 2, 20),
(3, 5, 'truefalse', 'La Luna è un pianeta', 'Vero', 'Falso', '', '', 2, 15),
(3, 6, 'multiple', 'Quanto tempo impiega la luce del Sole a raggiungere la Terra?', '8 secondi', '8 minuti', '8 ore', '8 giorni', 2, 30);

-- Domande per "Cinema Italiano" (set_id = 4)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(4, 1, 'multiple', 'Chi ha diretto La Dolce Vita?', 'Visconti', 'Fellini', 'Antonioni', 'Rossellini', 2, 30),
(4, 2, 'multiple', 'In che anno uscì Ladri di biciclette?', '1945', '1948', '1951', '1954', 2, 30),
(4, 3, 'truefalse', 'Roberto Benigni ha vinto l''Oscar', 'Vero', 'Falso', '', '', 1, 20),
(4, 4, 'multiple', 'Chi ha interpretato Totò?', 'Antonio De Curtis', 'Totò Sapore', 'Alberto Sordi', 'Vittorio Gassman', 1, 25),
(4, 5, 'multiple', 'Quale film di Tornatore ha vinto l''Oscar?', 'Malena', 'Cinema Paradiso', 'La leggenda del pianista', 'Baaria', 2, 30);

-- Domande per "Cucina Italiana" (set_id = 5)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(5, 1, 'multiple', 'Qual è l''ingrediente principale del pesto?', 'Prezzemolo', 'Basilico', 'Rucola', 'Menta', 2, 25),
(5, 2, 'truefalse', 'La pizza Margherita ha il pomodoro', 'Vero', 'Falso', '', '', 1, 15),
(5, 3, 'multiple', 'Di quale regione è tipica la carbonara?', 'Campania', 'Lazio', 'Toscana', 'Emilia', 2, 25),
(5, 4, 'multiple', 'Qual è il formaggio del parmigiano?', 'Latte di bufala', 'Latte di pecora', 'Latte di mucca', 'Latte di capra', 3, 30),
(5, 5, 'truefalse', 'Il tiramisù contiene caffè', 'Vero', 'Falso', '', '', 1, 15),
(5, 6, 'multiple', 'Quale città è famosa per il panettone?', 'Roma', 'Napoli', 'Milano', 'Torino', 3, 25);

-- Domande per "Geografia Europa" (set_id = 6)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(6, 1, 'multiple', 'Qual è la capitale della Germania?', 'Monaco', 'Berlino', 'Amburgo', 'Francoforte', 2, 20),
(6, 2, 'multiple', 'Qual è il fiume più lungo d''Europa?', 'Danubio', 'Reno', 'Volga', 'Tamigi', 3, 30),
(6, 3, 'truefalse', 'La Svizzera fa parte dell''Unione Europea', 'Vero', 'Falso', '', '', 2, 20),
(6, 4, 'multiple', 'Quanti stati confina l''Italia?', '4', '5', '6', '7', 3, 25),
(6, 5, 'multiple', 'Qual è la montagna più alta d''Europa?', 'Monte Bianco', 'Cervino', 'Monte Rosa', 'Elbrus', 4, 30),
(6, 6, 'truefalse', 'La Spagna ha una monarchia', 'Vero', 'Falso', '', '', 1, 20);

-- Domande per "Geografia Mondiale" (set_id = 7)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(7, 1, 'multiple', 'Qual è il paese più grande del mondo?', 'Canada', 'Cina', 'Russia', 'USA', 3, 25),
(7, 2, 'multiple', 'Qual è il deserto più grande del mondo?', 'Sahara', 'Gobi', 'Antartide', 'Kalahari', 3, 30),
(7, 3, 'truefalse', 'L''Australia è un continente', 'Vero', 'Falso', '', '', 1, 15),
(7, 4, 'multiple', 'Quanti continenti ci sono?', '5', '6', '7', '8', 3, 20),
(7, 5, 'multiple', 'Quale oceano è il più grande?', 'Atlantico', 'Pacifico', 'Indiano', 'Artico', 2, 25),
(7, 6, 'truefalse', 'L''Islanda si trova sopra il circolo polare artico', 'Vero', 'Falso', '', '', 2, 25);

-- Domande per "Giochi da Tavolo" (set_id = 8)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(8, 1, 'multiple', 'In quale gioco si deve conquistare il mondo?', 'Monopoly', 'Risiko', 'Scarabeo', 'Cluedo', 2, 25),
(8, 2, 'truefalse', 'Nel Monopoly si possono costruire alberghi', 'Vero', 'Falso', '', '', 1, 20),
(8, 3, 'multiple', 'Quante caselle ha la scacchiera?', '49', '64', '81', '100', 2, 25),
(8, 4, 'multiple', 'Quale pezzo degli scacchi può muoversi a L?', 'Alfiere', 'Torre', 'Cavallo', 'Regina', 3, 25),
(8, 5, 'truefalse', 'A Cluedo si deve scoprire un assassino', 'Vero', 'Falso', '', '', 1, 15),
(8, 6, 'multiple', 'Quanti pezzi ha ogni giocatore a Dama?', '10', '12', '15', '16', 2, 25);

-- Domande per "Harry Potter" (set_id = 9)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(9, 1, 'multiple', 'Qual è il nome completo di Harry Potter?', 'Harry James Potter', 'Harry John Potter', 'Harry Jack Potter', 'Harry Joseph Potter', 1, 30),
(9, 2, 'multiple', 'Quale casa di Hogwarts ha come animale un leone?', 'Serpeverde', 'Corvonero', 'Grifondoro', 'Tassorosso', 3, 25),
(9, 3, 'truefalse', 'Hermione ha un gatto', 'Vero', 'Falso', '', '', 1, 20),
(9, 4, 'multiple', 'Come si chiama il gufo di Harry?', 'Edvige', 'Grattastinchi', 'Testadipelo', 'Ron', 1, 25),
(9, 5, 'multiple', 'Chi è il preside di Hogwarts?', 'Silente', 'Piton', 'McGranitt', 'Lumacorno', 1, 25),
(9, 6, 'truefalse', 'Voldemort ha paura della morte', 'Vero', 'Falso', '', '', 1, 20);

-- Domande per "Invenzioni Storiche" (set_id = 10)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(10, 1, 'multiple', 'Chi ha inventato la lampadina?', 'Tesla', 'Edison', 'Einstein', 'Franklin', 2, 25),
(10, 2, 'truefalse', 'Il telefono fu inventato da Meucci', 'Vero', 'Falso', '', '', 1, 20),
(10, 3, 'multiple', 'In che anno fu inventata la ruota?', '3500 a.C.', '1000 a.C.', '500 d.C.', '1000 d.C.', 1, 35),
(10, 4, 'multiple', 'Chi ha inventato la radio?', 'Edison', 'Marconi', 'Tesla', 'Bell', 2, 25),
(10, 5, 'truefalse', 'I fratelli Wright inventarono l''aeroplano', 'Vero', 'Falso', '', '', 1, 20),
(10, 6, 'multiple', 'Chi inventò la stampa a caratteri mobili?', 'Galileo', 'Newton', 'Gutenberg', 'Leonardo', 3, 30);

-- Domande per "Letteratura Classica" (set_id = 11)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(11, 1, 'multiple', 'Chi ha scritto la Divina Commedia?', 'Petrarca', 'Dante', 'Boccaccio', 'Ariosto', 2, 25),
(11, 2, 'multiple', 'Quale di questi è un romanzo di Manzoni?', 'I Malavoglia', 'I Promessi Sposi', 'Il Gattopardo', 'Pinocchio', 2, 25),
(11, 3, 'truefalse', 'Shakespeare era italiano', 'Vero', 'Falso', '', '', 2, 15),
(11, 4, 'multiple', 'Chi ha scritto l''Odissea?', 'Virgilio', 'Omero', 'Ovidio', 'Eschilo', 2, 25),
(11, 5, 'multiple', 'Quale poeta scrisse "Il cinque maggio"?', 'Leopardi', 'Manzoni', 'Foscolo', 'Carducci', 2, 30),
(11, 6, 'truefalse', 'Pinocchio fu scritto da Collodi', 'Vero', 'Falso', '', '', 1, 20);

-- Domande per "Matematica Base" (set_id = 12)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(12, 1, 'multiple', 'Quanto fa 7 x 8?', '54', '56', '58', '64', 2, 20),
(12, 2, 'multiple', 'Qual è la radice quadrata di 64?', '6', '7', '8', '9', 3, 25),
(12, 3, 'truefalse', '5 + 5 = 10', 'Vero', 'Falso', '', '', 1, 10),
(12, 4, 'multiple', 'Quanto fa 100 diviso 4?', '20', '25', '30', '40', 2, 20),
(12, 5, 'multiple', 'Quale numero è primo?', '9', '15', '17', '21', 3, 25),
(12, 6, 'truefalse', 'Il numero Pi greco è infinito', 'Vero', 'Falso', '', '', 1, 20);

-- Domande per "Mitologia Greca" (set_id = 13)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(13, 1, 'multiple', 'Chi è il re degli dei greci?', 'Poseidone', 'Zeus', 'Ade', 'Apollo', 2, 25),
(13, 2, 'multiple', 'Chi è la dea della sapienza?', 'Afrodite', 'Artemide', 'Atena', 'Era', 3, 25),
(13, 3, 'truefalse', 'Poseidone è il dio del mare', 'Vero', 'Falso', '', '', 1, 15),
(13, 4, 'multiple', 'Chi uccise la Medusa?', 'Ercole', 'Perseo', 'Teseo', 'Achille', 2, 30),
(13, 5, 'multiple', 'Quante fatiche dovette compiere Ercole?', '7', '10', '12', '15', 3, 25),
(13, 6, 'truefalse', 'Icaro volò troppo vicino al sole', 'Vero', 'Falso', '', '', 1, 20);

-- Domande per "Musica Pop" (set_id = 14)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(14, 1, 'multiple', 'Chi era il frontman dei Queen?', 'Elton John', 'Freddie Mercury', 'David Bowie', 'Mick Jagger', 2, 25),
(14, 2, 'multiple', 'Quale cantante è chiamata "The Queen of Pop"?', 'Beyoncé', 'Madonna', 'Lady Gaga', 'Britney Spears', 2, 25),
(14, 3, 'truefalse', 'I Beatles erano americani', 'Vero', 'Falso', '', '', 2, 15),
(14, 4, 'multiple', 'Chi cantava "Thriller"?', 'Prince', 'Michael Jackson', 'James Brown', 'Stevie Wonder', 2, 25),
(14, 5, 'multiple', 'Quale band cantava "Bohemian Rhapsody"?', 'The Who', 'Pink Floyd', 'Queen', 'Led Zeppelin', 3, 25),
(14, 6, 'truefalse', 'Elvis Presley era chiamato "The King"', 'Vero', 'Falso', '', '', 1, 15);

-- Domande per "Personaggi Storici" (set_id = 15)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(15, 1, 'multiple', 'Chi scoprì l''America?', 'Vespucci', 'Colombo', 'Magellano', 'Cook', 2, 25),
(15, 2, 'multiple', 'Chi fu il primo imperatore di Roma?', 'Cesare', 'Augusto', 'Nerone', 'Traiano', 2, 30),
(15, 3, 'truefalse', 'Napoleone era francese', 'Vero', 'Falso', '', '', 2, 20),
(15, 4, 'multiple', 'Chi dipinse la Cappella Sistina?', 'Leonardo', 'Michelangelo', 'Raffaello', 'Caravaggio', 2, 25),
(15, 5, 'multiple', 'Chi scrisse "Il Principe"?', 'Dante', 'Machiavelli', 'Petrarca', 'Boccaccio', 2, 25),
(15, 6, 'truefalse', 'Gandhi era indiano', 'Vero', 'Falso', '', '', 1, 15);

-- Domande per "Quiz Matrimonio" (set_id = 16)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(16, 1, 'multiple', 'Dove si sono conosciuti gli sposi?', 'Al lavoro', 'All''università', 'In vacanza', 'Ad una festa', 2, 30),
(16, 2, 'truefalse', 'Gli sposi hanno fatto il primo viaggio insieme in Italia', 'Vero', 'Falso', '', '', 1, 20),
(16, 3, 'multiple', 'Qual è il piatto preferito dello sposo?', 'Pizza', 'Pasta alla carbonara', 'Bistecca', 'Sushi', 2, 25),
(16, 4, 'clickfirst', 'Chi ha detto "Ti amo" per primo?', '', '', '', '', NULL, 30),
(16, 5, 'multiple', 'In che mese si sono fidanzati?', 'Gennaio', 'Maggio', 'Settembre', 'Dicembre', 3, 30),
(16, 6, 'truefalse', 'Lo sposo ha una sorella', 'Vero', 'Falso', '', '', 1, 20);

-- Domande per "Scienze Naturali" (set_id = 17)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(17, 1, 'multiple', 'Qual è l''elemento chimico dell''acqua?', 'CO2', 'H2O', 'O2', 'NaCl', 2, 20),
(17, 2, 'truefalse', 'La fotosintesi produce ossigeno', 'Vero', 'Falso', '', '', 1, 20),
(17, 3, 'multiple', 'Quanti denti ha un adulto?', '28', '30', '32', '34', 3, 25),
(17, 4, 'multiple', 'Qual è l''organo più grande del corpo umano?', 'Fegato', 'Polmone', 'Pelle', 'Cervello', 3, 25),
(17, 5, 'truefalse', 'Il diamante è il minerale più duro', 'Vero', 'Falso', '', '', 1, 20),
(17, 6, 'multiple', 'Quale gas respiriamo?', 'Ossigeno', 'Azoto', 'Anidride carbonica', 'Idrogeno', 1, 20);

-- Domande per "Sport Olimpici" (set_id = 18)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(18, 1, 'multiple', 'Dove si svolsero le prime Olimpiadi moderne?', 'Parigi', 'Atene', 'Londra', 'Roma', 2, 30),
(18, 2, 'multiple', 'Quanti anelli ha il simbolo olimpico?', '4', '5', '6', '7', 2, 20),
(18, 3, 'truefalse', 'Le Olimpiadi si tengono ogni 4 anni', 'Vero', 'Falso', '', '', 1, 15),
(18, 4, 'multiple', 'Chi detiene il record dei 100m?', 'Carl Lewis', 'Usain Bolt', 'Justin Gatlin', 'Asafa Powell', 2, 30),
(18, 5, 'multiple', 'In quale sport si usa una racchetta?', 'Pallavolo', 'Tennis', 'Basket', 'Calcio', 2, 20),
(18, 6, 'truefalse', 'Il nuoto sincronizzato è uno sport olimpico', 'Vero', 'Falso', '', '', 1, 20);

-- Domande per "Storia Italiana" (set_id = 19)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(19, 1, 'multiple', 'In che anno fu unificata l''Italia?', '1848', '1861', '1870', '1900', 2, 30),
(19, 2, 'multiple', 'Chi fu il primo re d''Italia?', 'Garibaldi', 'Vittorio Emanuele II', 'Cavour', 'Mazzini', 2, 30),
(19, 3, 'truefalse', 'Roma fu sempre la capitale d''Italia', 'Vero', 'Falso', '', '', 2, 20),
(19, 4, 'multiple', 'Chi guidò la spedizione dei Mille?', 'Mazzini', 'Garibaldi', 'Cavour', 'D''Azeglio', 2, 25),
(19, 5, 'multiple', 'In che anno finì la Seconda Guerra Mondiale?', '1943', '1944', '1945', '1946', 3, 25),
(19, 6, 'truefalse', 'Mussolini fondò il fascismo', 'Vero', 'Falso', '', '', 1, 20);

-- Domande per "Videogiochi" (set_id = 20)
INSERT INTO rounds (question_set_id, round_number, round_type, question, option1, option2, option3, option4, correct_answer, timer) VALUES
(20, 1, 'multiple', 'Chi è il protagonista di Super Mario?', 'Luigi', 'Mario', 'Wario', 'Yoshi', 2, 20),
(20, 2, 'multiple', 'Quale console produsse la Sony?', 'Xbox', 'Nintendo', 'PlayStation', 'Sega', 3, 20),
(20, 3, 'truefalse', 'Minecraft è stato creato da Microsoft', 'Vero', 'Falso', '', '', 2, 25),
(20, 4, 'multiple', 'In quale gioco si catturano Pokémon?', 'Zelda', 'Pokémon', 'Digimon', 'Mario', 2, 20),
(20, 5, 'multiple', 'Quale gioco ha come protagonista Link?', 'Mario', 'Zelda', 'Pokémon', 'Sonic', 2, 25),
(20, 6, 'truefalse', 'Fortnite è un gioco di strategia', 'Vero', 'Falso', '', '', 2, 20);

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
