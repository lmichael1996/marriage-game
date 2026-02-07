<!-- Tab: Domande e Gestione Partita -->
<div class="admin-section">
    <div class="tab-header-with-button">
        <h2>❓ Domande Disponibili</h2>
        <button class="btn btn-success" id="btn-new-question">+ Nuova Domanda</button>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <form method="GET" action="admin.php" class="search-form">
            <input type="hidden" name="tab" value="questions">
            <input type="text" name="search" id="search-questions" placeholder="Cerca domande..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <select name="search_type" id="search-type" class="search-filter">
                <option value="contains" <?php echo ($_GET['search_type'] ?? 'contains') === 'contains' ? 'selected' : ''; ?>>Contiene</option>
                <option value="starts_with" <?php echo ($_GET['search_type'] ?? '') === 'starts_with' ? 'selected' : ''; ?>>Inizia con</option>
            </select>
            <select name="category" id="filter-category" class="search-filter">
                <option value="">🌐 Tutte</option>
                <option value="general" <?php echo ($_GET['category'] ?? '') === 'general' ? 'selected' : ''; ?>>Generale</option>
                <option value="science" <?php echo ($_GET['category'] ?? '') === 'science' ? 'selected' : ''; ?>>Scienza</option>
                <option value="history" <?php echo ($_GET['category'] ?? '') === 'history' ? 'selected' : ''; ?>>Storia</option>
                <option value="sports" <?php echo ($_GET['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sport</option>
                <option value="entertainment" <?php echo ($_GET['category'] ?? '') === 'entertainment' ? 'selected' : ''; ?>>Intrattenimento</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Cerca</button>
            <?php if (!empty($_GET['search']) || !empty($_GET['category'])): ?>
                <a href="?tab=questions" class="btn btn-secondary">✖ Cancella</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table View -->
    <div class="settings-group">
        <table class="questions-table">
            <thead>
                <tr>
                    <th>Domanda</th>
                    <th>Categoria</th>
                    <th>Tipo</th>
                    <th>Timer</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                <tr data-question-id="<?php echo $q['id']; ?>">
                    <td><strong><?php
                        $question = htmlspecialchars($q['question']);
                        // Evidenzia il testo cercato in giallo se presente
                        if (!empty($_GET['search'])) {
                            $search = htmlspecialchars($_GET['search']);
                            $highlighted = preg_replace(
                                '/(' . preg_quote($search, '/') . ')/i',
                                '<mark>$1</mark>',
                                $question
                            );
                            echo $highlighted;
                        } else {
                            echo $question;
                        }
                    ?></strong></td>
                    <td>
                        <span class="badge-category" style="background: <?php echo htmlspecialchars($q['color'] ?? '#ecf0f1'); ?>; color: #333;">
                            <?php echo htmlspecialchars($q['category_name'] ?? 'Generale'); ?>
                        </span>
                    </td>
                    <td><span class="badge-type"><?php
                        $typeMap = [
                            'multiple' => '📋 Multiple',
                            'truefalse' => '✔️ Vero/Falso',
                            'clickfirst' => '⚡ Clicca 1°'
                        ];
                        echo $typeMap[$q['round_type']] ?? ucfirst($q['round_type']);
                    ?></span></td>
                    <td><?php echo $q['timer']; ?>s</td>
                    <td>
                        <button class="btn-icon btn-warning btn-edit-question" data-question-id="<?php echo $q['id']; ?>" title="Modifica">✎</button>
                        <button class="btn-icon btn-danger btn-delete-question" data-question-id="<?php echo $q['id']; ?>" title="Elimina">×</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="pagination-controls">
            <button id="btn-prev-page" class="btn btn-secondary" title="Pagina precedente">⬅ Precedente</button>
            <span id="page-info" class="page-info">Pagina 1</span>
            <button id="btn-next-page" class="btn btn-secondary" title="Pagina successiva">Successiva ➜</button>
        </div>

        <?php if (empty($questions)): ?>
        <div class="info-box loading-text">
            <h3>Nessuna domanda</h3>
            <p>Clicca su "Nuova Domanda" per iniziare.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form quando cambia la categoria
    document.getElementById('filter-category').addEventListener('change', function() {
        const form = this.closest('form');
        form.submit();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit-question')) {
            const questionId = e.target.closest('.btn-edit-question').getAttribute('data-question-id');

            // Carica dati domanda tramite API
            fetch(`/src/api/api.php?endpoint=get_question&id=${questionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.question) {
                        const q = data.question;

                        // Popola i campi del form
                        document.getElementById('edit-question-id').value = q.id;
                        document.getElementById('edit-question').value = q.question;
                        document.getElementById('edit-type').value = q.round_type;
                        document.getElementById('edit-category').value = q.category_id;
                        document.getElementById('edit-timer').value = q.timer;

                        // Trigger change per mostrare i campi risposte
                        document.getElementById('edit-type').dispatchEvent(new Event('change'));

                        // Popola le risposte se disponibili
                        if (q.answer1) document.getElementById('edit-answer1').value = q.answer1;
                        if (q.answer2) document.getElementById('edit-answer2').value = q.answer2;
                        if (q.answer3) document.getElementById('edit-answer3').value = q.answer3;
                        if (q.answer4) document.getElementById('edit-answer4').value = q.answer4;
                        if (q.correct_answer) document.getElementById('edit-correct').value = q.correct_answer;

                        // Mostra il modal
                        const modal = document.getElementById('modal-edit-question');
                        modal.style.display = 'flex';
                    } else {
                        alert('Errore nel caricamento della domanda');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Errore nel caricamento della domanda');
                });
            return;
        }
        if (e.target.closest('.btn-delete-question')) {
            const questionId = e.target.closest('.btn-delete-question').getAttribute('data-question-id');
            const modal = document.getElementById('modal-delete-question');
            const btnConfirmDelete = document.getElementById('btn-confirm-delete');

            modal.style.display = 'flex';

            // Aggiorna il button per eliminare con l'ID corretto
            btnConfirmDelete.onclick = function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_question">
                    <input type="hidden" name="question_id" value="${questionId}">
                `;
                document.body.appendChild(form);
                form.submit();
            };
        }
    });

    // Paginazione domande - Server-side con GET parameters
    const ROWS_PER_PAGE = 10;
    const total = <?php echo (int)($pagination['total'] ?? 0); ?>;
    let totalPages = total > 0 ? Math.ceil(total / ROWS_PER_PAGE) : 1;
    let currentPage = <?php echo isset($_GET['page']) ? (int)$_GET['page'] : 1; ?>;

    function getQueryParams() {
        const search = new URLSearchParams(window.location.search);
        const params = new URLSearchParams();

        // Mantieni i parametri di ricerca e filtro
        if (search.has('search')) params.append('search', search.get('search'));
        if (search.has('search_type')) params.append('search_type', search.get('search_type'));
        if (search.has('category')) params.append('category', search.get('category'));

        return params;
    }

    function navigateToPage(page) {
        const params = getQueryParams();
        params.append('page', page);
        params.append('tab', 'questions');

        window.location.href = 'admin.php?' + params.toString();
    }

    function updatePaginationUI() {
        const pageInfo = document.getElementById('page-info');
        const start = (currentPage - 1) * ROWS_PER_PAGE + 1;
        const end = Math.min(currentPage * ROWS_PER_PAGE, total);

        pageInfo.textContent = `Domande ${start}-${end} di ${total}`;

        // Abilita/disabilita pulsanti
        document.getElementById('btn-prev-page').disabled = currentPage === 1;
        document.getElementById('btn-next-page').disabled = currentPage >= totalPages;
    }

    document.getElementById('btn-prev-page').addEventListener('click', () => {
        if (currentPage > 1) navigateToPage(currentPage - 1);
    });
    document.getElementById('btn-next-page').addEventListener('click', () => {
        if (currentPage < totalPages) navigateToPage(currentPage + 1);
    });

    // Aggiorna UI paginazione al caricamento
    updatePaginationUI();

    // Gestione popup Nuova Domanda
    const btnNewQuestion = document.getElementById('btn-new-question');
    const modalOverlay = document.getElementById('modal-add-question');

    if (btnNewQuestion) {
        btnNewQuestion.addEventListener('click', () => {
            modalOverlay.style.display = 'flex';
        });
    }

    // Chiudi modal
    const closeBtn = document.querySelector('#modal-add-question .modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modalOverlay.style.display = 'none';
            document.getElementById('form-new-question').reset();
        });
    }

    // Chiudi modal quando clicchi sul overlay
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                modalOverlay.style.display = 'none';
                document.getElementById('form-new-question').reset();
            }
        });
    }

    // Invia form aggiunta domanda
    const formNewQuestion = document.getElementById('form-new-question');
    if (formNewQuestion) {
        formNewQuestion.addEventListener('submit', (e) => {
            // Validazione: controlla che sia selezionato un tipo valido
            const typeSelected = document.getElementById('new-type').value;

            if (!typeSelected) {
                e.preventDefault();
                alert('Seleziona il tipo di domanda');
                return;
            }

            e.preventDefault();
            const formData = new FormData(formNewQuestion);
            formData.append('action', 'add_question');

            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Response:', data);
                // Chiudi modal e ricarica la pagina
                modalOverlay.style.display = 'none';
                formNewQuestion.reset();
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore nell\'aggiunta della domanda');
            });
        });
    }

    // Gestisci visibilità campi risposte basato sul tipo
    const typeSelect = document.getElementById('new-type');
    const answersContainer = document.getElementById('answers-container');
    const answer3Group = document.getElementById('answer3-group');
    const answer4Group = document.getElementById('answer4-group');
    const correctSelect = document.getElementById('new-correct');

    if (typeSelect) {
        typeSelect.addEventListener('change', (e) => {
            const type = e.target.value;

            // Mostra/nascondi sezione risposte
            if (type === 'truefalse') {
                answersContainer.style.display = 'block';
                answer3Group.style.display = 'none';
                answer4Group.style.display = 'none';
                // Nascondi i campi input answer1 e answer2 per vero/falso
                document.getElementById('new-answer1').parentElement.style.display = 'none';
                document.getElementById('new-answer2').parentElement.style.display = 'none';
                // Limita a 2 risposte
                correctSelect.innerHTML = '<option value="1">Vero</option><option value="2">Falso</option>';
            } else if (type === 'multiple') {
                answersContainer.style.display = 'block';
                answer3Group.style.display = 'block';
                answer4Group.style.display = 'block';
                // Mostra i campi input answer
                document.getElementById('new-answer1').parentElement.style.display = 'block';
                document.getElementById('new-answer2').parentElement.style.display = 'block';
                document.getElementById('new-answer1').placeholder = 'Risposta corretta';
                document.getElementById('new-answer2').placeholder = 'Risposta sbagliata';
                document.getElementById('new-answer3').placeholder = 'Risposta sbagliata';
                document.getElementById('new-answer4').placeholder = 'Risposta sbagliata';
                correctSelect.innerHTML = '<option value="1">Risposta 1</option><option value="2">Risposta 2</option><option value="3">Risposta 3</option><option value="4">Risposta 4</option>';
            } else if (type === 'clickfirst') {
                answersContainer.style.display = 'none';
            } else {
                answersContainer.style.display = 'none';
            }
        });
    }

    // ========== MODAL MODIFICA DOMANDA ==========
    const modalEditQuestion = document.getElementById('modal-edit-question');
    const closeEditBtn = document.querySelector('#modal-edit-question .modal-close');
    const formEditQuestion = document.getElementById('form-edit-question');

    if (closeEditBtn) {
        closeEditBtn.addEventListener('click', () => {
            modalEditQuestion.style.display = 'none';
        });
    }

    if (modalEditQuestion) {
        modalEditQuestion.addEventListener('click', (e) => {
            if (e.target === modalEditQuestion) {
                modalEditQuestion.style.display = 'none';
            }
        });
    }

    // Gestisci visibilità campi risposte per modal edit
    const editTypeSelect = document.getElementById('edit-type');
    const editAnswersContainer = document.getElementById('edit-answers-container');
    const editAnswer3Group = document.getElementById('edit-answer3-group');
    const editAnswer4Group = document.getElementById('edit-answer4-group');
    const editCorrectSelect = document.getElementById('edit-correct');

    if (editTypeSelect) {
        editTypeSelect.addEventListener('change', (e) => {
            const type = e.target.value;

            if (type === 'truefalse') {
                editAnswersContainer.style.display = 'block';
                editAnswer3Group.style.display = 'none';
                editAnswer4Group.style.display = 'none';
                // Nascondi i campi input answer1 e answer2 per vero/falso
                document.getElementById('edit-answer1').parentElement.style.display = 'none';
                document.getElementById('edit-answer2').parentElement.style.display = 'none';
                editCorrectSelect.innerHTML = '<option value="1">Vero</option><option value="2">Falso</option>';
            } else if (type === 'multiple') {
                editAnswersContainer.style.display = 'block';
                editAnswer3Group.style.display = 'block';
                editAnswer4Group.style.display = 'block';
                // Mostra i campi input answer
                document.getElementById('edit-answer1').parentElement.style.display = 'block';
                document.getElementById('edit-answer2').parentElement.style.display = 'block';
                document.getElementById('edit-answer1').placeholder = 'Risposta corretta';
                document.getElementById('edit-answer2').placeholder = 'Risposta sbagliata';
                document.getElementById('edit-answer3').placeholder = 'Risposta sbagliata';
                document.getElementById('edit-answer4').placeholder = 'Risposta sbagliata';
                editCorrectSelect.innerHTML = '<option value="1">Risposta 1</option><option value="2">Risposta 2</option><option value="3">Risposta 3</option><option value="4">Risposta 4</option>';
            } else if (type === 'clickfirst') {
                editAnswersContainer.style.display = 'none';
            } else {
                editAnswersContainer.style.display = 'none';
            }
        });
    }

    if (formEditQuestion) {
        formEditQuestion.addEventListener('submit', (e) => {
            // Validazione: controlla che sia selezionato un tipo valido
            const typeSelected = document.getElementById('edit-type').value;

            if (!typeSelected) {
                e.preventDefault();
                alert('Seleziona il tipo di domanda');
                return;
            }

            e.preventDefault();
            const formData = new FormData(formEditQuestion);
            formData.append('action', 'update_question');

            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Response:', data);
                modalEditQuestion.style.display = 'none';
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore nella modifica della domanda');
            });
        });
    }

    // ========== MODAL ELIMINAZIONE DOMANDA ==========
    const modalDeleteQuestion = document.getElementById('modal-delete-question');
    const closeDeleteBtn = document.querySelector('#modal-delete-question .modal-close');

    if (closeDeleteBtn) {
        closeDeleteBtn.addEventListener('click', () => {
            modalDeleteQuestion.style.display = 'none';
        });
    }

    if (modalDeleteQuestion) {
        modalDeleteQuestion.addEventListener('click', (e) => {
            if (e.target === modalDeleteQuestion) {
                modalDeleteQuestion.style.display = 'none';
            }
        });
    }
});
</script>

<!-- Modal per aggiungere nuova domanda -->
<div id="modal-add-question" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>➕ Nuova Domanda</h2>
            <button type="button" class="modal-close">✕</button>
        </div>
        <form id="form-new-question" class="modal-body">
            <div class="form-group">
                <label for="new-question">Domanda *</label>
                <textarea id="new-question" name="question" required rows="3" placeholder="Inserisci il testo della domanda..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="new-type">Tipo *</label>
                    <select id="new-type" name="round_type" required>
                        <option value="">Seleziona tipo...</option>
                        <option value="multiple">📋 Multiple Choice</option>
                        <option value="truefalse">✔️ Vero/Falso</option>
                        <option value="clickfirst">⚡ Clicca il Primo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new-category">Categoria *</label>
                    <select id="new-category" name="category_id" required>
                        <option value="">Seleziona categoria...</option>
                        <option value="1">Generale</option>
                        <option value="2">Scienza</option>
                        <option value="3">Storia</option>
                        <option value="4">Sport</option>
                        <option value="5">Intrattenimento</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="new-timer">Timer (secondi) *</label>
                    <input type="number" id="new-timer" name="timer" required min="5" max="120" value="30">
                </div>
            </div>

            <div id="answers-container" style="display: none;">
                <h3>Risposte</h3>
                <div class="form-group">
                    <label for="new-answer1">Risposta 1 *</label>
                    <input type="text" id="new-answer1" name="answer1" placeholder="Risposta corretta">
                </div>
                <div class="form-group">
                    <label for="new-answer2">Risposta 2 *</label>
                    <input type="text" id="new-answer2" name="answer2" placeholder="Risposta sbagliata">
                </div>
                <div class="form-group" id="answer3-group" style="display: none;">
                    <label for="new-answer3">Risposta 3 *</label>
                    <input type="text" id="new-answer3" name="answer3" placeholder="Risposta sbagliata">
                </div>
                <div class="form-group" id="answer4-group" style="display: none;">
                    <label for="new-answer4">Risposta 4 *</label>
                    <input type="text" id="new-answer4" name="answer4" placeholder="Risposta sbagliata">
                </div>
                <div class="form-group">
                    <label for="new-correct">Risposta Corretta *</label>
                    <select id="new-correct" name="correct_answer">
                        <option value="1">Risposta 1</option>
                        <option value="2">Risposta 2</option>
                        <option value="3">Risposta 3</option>
                        <option value="4">Risposta 4</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-add-question').style.display='none'; document.getElementById('form-new-question').reset();">Annulla</button>
                <button type="submit" class="btn btn-success">✓ Aggiungi Domanda</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal per modificare domanda -->
<div id="modal-edit-question" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>✎ Modifica Domanda</h2>
            <button type="button" class="modal-close">✕</button>
        </div>
        <form id="form-edit-question" class="modal-body">
            <input type="hidden" id="edit-question-id" name="question_id">
            <div class="form-group">
                <label for="edit-question">Domanda *</label>
                <textarea id="edit-question" name="question" required rows="3" placeholder="Inserisci il testo della domanda..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit-type">Tipo *</label>
                    <select id="edit-type" name="round_type" required>
                        <option value="">Seleziona tipo...</option>
                        <option value="multiple">📋 Multiple Choice</option>
                        <option value="truefalse">✔️ Vero/Falso</option>
                        <option value="clickfirst">⚡ Clicca il Primo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-category">Categoria *</label>
                    <select id="edit-category" name="category_id" required>
                        <option value="">Seleziona categoria...</option>
                        <option value="1">Generale</option>
                        <option value="2">Scienza</option>
                        <option value="3">Storia</option>
                        <option value="4">Sport</option>
                        <option value="5">Intrattenimento</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit-timer">Timer (secondi) *</label>
                    <input type="number" id="edit-timer" name="timer" required min="5" max="120" value="30">
                </div>
            </div>

            <div id="edit-answers-container" style="display: none;">
                <h3>Risposte</h3>
                <div class="form-group">
                    <label for="edit-answer1">Risposta 1 *</label>
                    <input type="text" id="edit-answer1" name="answer1" placeholder="Risposta corretta">
                </div>
                <div class="form-group">
                    <label for="edit-answer2">Risposta 2 *</label>
                    <input type="text" id="edit-answer2" name="answer2" placeholder="Risposta sbagliata">
                </div>
                <div class="form-group" id="edit-answer3-group" style="display: none;">
                    <label for="edit-answer3">Risposta 3 *</label>
                    <input type="text" id="edit-answer3" name="answer3" placeholder="Risposta sbagliata">
                </div>
                <div class="form-group" id="edit-answer4-group" style="display: none;">
                    <label for="edit-answer4">Risposta 4 *</label>
                    <input type="text" id="edit-answer4" name="answer4" placeholder="Risposta sbagliata">
                </div>
                <div class="form-group">
                    <label for="edit-correct">Risposta Corretta *</label>
                    <select id="edit-correct" name="correct_answer">
                        <option value="1">Risposta 1</option>
                        <option value="2">Risposta 2</option>
                        <option value="3">Risposta 3</option>
                        <option value="4">Risposta 4</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-edit-question').style.display='none';">Annulla</button>
                <button type="submit" class="btn btn-success">✓ Salva Modifiche</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal per confermare eliminazione domanda -->
<div id="modal-delete-question" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-content-small">
        <div class="modal-header">
            <h2>⚠️ Conferma Eliminazione</h2>
            <button type="button" class="modal-close">✕</button>
        </div>
        <div class="modal-body">
            <p>Sei sicuro di voler eliminare questa domanda?</p>
            <p style="color: #666; font-size: 0.9em;">Questa azione non può essere annullata.</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-delete-question').style.display='none';">Annulla</button>
            <button type="button" id="btn-confirm-delete" class="btn btn-danger">✓ Elimina</button>
        </div>
    </div>
</div>
