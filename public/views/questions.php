<!-- Tab: Domande e Gestione Partita -->
<div class="admin-section">
    <div class="tab-header-with-button">
        <h2>❓ Domande Disponibili</h2>
        <div class="button-group">
            <button class="btn btn-success" id="btn-new-question">+ Nuova Domanda</button>
            <button class="btn btn-warning" id="btn-new-category">Categorie</button>
        </div>
    </div>

    <!-- Search Bar -->
    <form method="POST" action="admin.php" class="search-toolbar" id="search-questions-form">
        <input type="hidden" name="questions_page" id="questions_page" value="<?php echo (int)($_SESSION['questions_page'] ?? 1); ?>">
        <div class="search-input-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" name="search_query" id="search-questions" placeholder="Cerca domande..." value="<?php echo htmlspecialchars($_POST['search_query'] ?? ''); ?>">
        </div>
        <select name="search_type" id="search-type" class="search-select">
            <option value="starts_with" <?php echo ($_POST['search_type'] ?? '') === 'starts_with' ? 'selected' : ''; ?>>Inizia con</option>
            <option value="contains" <?php echo ($_POST['search_type'] ?? 'contains') === 'contains' ? 'selected' : ''; ?>>Contiene</option>
            <option value="ends_with" <?php echo ($_POST['search_type'] ?? '') === 'ends_with' ? 'selected' : ''; ?>>Finisce con</option>
            <option value="exact" <?php echo ($_POST['search_type'] ?? '') === 'exact' ? 'selected' : ''; ?>>Esattamente</option>
        </select>
        <select name="category" id="filter-category" class="search-select">
            <option value="">🌐 Tutte</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" data-color="<?php echo htmlspecialchars($cat['color'] ?? '#6c757d'); ?>" <?php echo ($_POST['category'] ?? '') === (string)$cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['category_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="search-btn">Cerca</button>
        <?php if (!empty($_POST['search_query']) || !empty($_POST['category'])): ?>
            <button type="button" class="search-btn search-btn-clear" id="clear-questions-search">✖</button>
        <?php endif; ?>
    </form>

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
                        $questionText = htmlspecialchars($q['question']);
                        if (!empty($_POST['search_query'])) {
                            $search = htmlspecialchars($_POST['search_query']);
                            $searchType = $_POST['search_type'] ?? 'contains';
                            switch ($searchType) {
                                case 'starts_with':
                                    $pattern = '/^(' . preg_quote($search, '/') . ')/i';
                                    break;
                                case 'ends_with':
                                    $pattern = '/(' . preg_quote($search, '/') . ')$/i';
                                    break;
                                case 'exact':
                                    $pattern = '/^(' . preg_quote($search, '/') . ')$/i';
                                    break;
                                default: // contains
                                    $pattern = '/(' . preg_quote($search, '/') . ')/i';
                            }
                            $highlighted = preg_replace(
                                $pattern,
                                '<mark>$1</mark>',
                                $questionText
                            );
                            echo $highlighted;
                        } else {
                            echo $questionText;
                        }
                    ?></strong></td>
                    <td>
                        <span class="badge-category">
                            <span class="color-dot" style="background: <?php echo htmlspecialchars($q['color'] ?? '#6c757d'); ?>;"></span>
                            <?php echo htmlspecialchars($q['category_name'] ?? 'Generale'); ?>
                        </span>
                    </td>
                    <td><span class="badge-type"><?php
                        $typeMap = [
                            'multiple' => '📋 Scelta multipla',
                            'truefalse' => '✔️ Vero/Falso',
                            'clickfirst' => '⚡ Clicca per primo'
                        ];
                        echo $typeMap[$q['question_type']] ?? ucfirst($q['question_type']);
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

        <?php if (empty($questions)): ?>
        <div class="info-box loading-text">
            <h3>Nessuna domanda</h3>
            <p>Clicca su "Nuova Domanda" per iniziare.</p>
        </div>
        <?php endif; ?>

        <!-- Pagination Controls -->
        <div class="pagination-controls">
            <button id="btn-prev-page" class="btn btn-secondary" title="Pagina precedente">⬅ Precedente</button>
            <span id="page-info" class="page-info">Pagina 1</span>
            <button id="btn-next-page" class="btn btn-secondary" title="Pagina successiva">Successiva ➜</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========== TRACCIAMENTO MODIFICHE CATEGORIE ==========
    let categoryChanges = {
        deleted: [],    // ID delle categorie eliminate
        updated: [],    // Categorie modificate {id, name, color}
        added: []       // Categorie nuove {name, color}
    };

    // Auto-submit form quando cambia la categoria
    document.getElementById('filter-category').addEventListener('change', function() {
        colorCategorySelect(this);
        const form = this.closest('form');
        form.submit();
    });

    // Colora le option del select categorie con il pallino colorato
    function colorCategorySelect(select) {
        select.querySelectorAll('option[data-color]').forEach(opt => {
            opt.style.color = opt.dataset.color;
        });
        const selected = select.options[select.selectedIndex];
        if (selected && selected.dataset.color) {
            select.style.color = selected.dataset.color;
        } else {
            select.style.color = '';
        }
    }
    colorCategorySelect(document.getElementById('filter-category'));

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
                        document.getElementById('edit-type').value = q.question_type;
                        document.getElementById('edit-category').value = q.category_id;
                        document.getElementById('edit-timer').value = q.timer;

                        // Trigger change per mostrare i campi risposte
                        document.getElementById('edit-type').dispatchEvent(new Event('change'));

                        // Popola le risposte in base al tipo
                        if (q.question_type === 'truefalse') {
                            if (q.option1) document.getElementById('edit-answer1').value = q.option1;
                            if (q.option2) document.getElementById('edit-answer2').value = q.option2;
                            if (q.correct_answer) document.getElementById('edit-correct').value = q.correct_answer;
                        } else if (q.question_type === 'multiple') {
                            if (q.option1) document.getElementById('edit-answer1').value = q.option1;
                            if (q.option2) document.getElementById('edit-answer2').value = q.option2;
                            if (q.option3) document.getElementById('edit-answer3').value = q.option3;
                            if (q.option4) document.getElementById('edit-answer4').value = q.option4;
                            if (q.correct_answer) document.getElementById('edit-correct').value = q.correct_answer;
                        }

                        // Mostra il modal
                        const modal = document.getElementById('modal-edit-question');
                        modal.style.display = 'flex';
                    } else {
                        showToast('Errore nel caricamento della domanda', 'error');
                    }
                })
                .catch(error => {
                    showToast('Errore nel caricamento della domanda', 'error');
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

    // Paginazione domande - Session-based con POST
    const ROWS_PER_PAGE = 10;
    const total = <?php echo (int)($pagination['total'] ?? 0); ?>;
    let totalPages = total > 0 ? Math.ceil(total / ROWS_PER_PAGE) : 1;
    let currentPage = <?php echo (int)($_SESSION['questions_page'] ?? 1); ?>;

    function updatePaginationUI() {
        const pageInfo = document.getElementById('page-info');
        const btnPrev = document.getElementById('btn-prev-page');
        const btnNext = document.getElementById('btn-next-page');

        if (!pageInfo || !btnPrev || !btnNext) return;

        // Se non ci sono domande, mostra 'Nessuna domanda disponibile' invece di 'Domande 0-0 di 0'
        if (total === 0) {
            pageInfo.textContent = `Nessuna domanda disponibile`;
        } else {
            const start = (currentPage - 1) * ROWS_PER_PAGE + 1;
            const end = Math.min(currentPage * ROWS_PER_PAGE, total);
            pageInfo.textContent = `Domande ${start}-${end} di ${total}`;
        }

        btnPrev.disabled = currentPage === 1;
        btnNext.disabled = currentPage >= totalPages;
    }

    function submitPage(page) {
        const form = document.querySelector('form[action="admin.php"]');
        const pageInput = document.getElementById('questions_page');
        if (form && pageInput) {
            pageInput.value = page;
            form.submit();
        }
    }

    const btnPrev = document.getElementById('btn-prev-page');
    const btnNext = document.getElementById('btn-next-page');

    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (currentPage > 1) submitPage(currentPage - 1);
        });
    }

    if (btnNext) {
        btnNext.addEventListener('click', () => {
            if (currentPage < totalPages) submitPage(currentPage + 1);
        });
    }

    updatePaginationUI();

    // Handle clear search
    const clearBtn = document.getElementById('clear-questions-search');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            const form = document.querySelector('form[action="admin.php"]');
            if (form) {
                const searchInput = document.querySelector('input[name="search_query"]');
                const categorySelect = document.querySelector('select[name="category"]');
                const pageInput = document.getElementById('questions_page');
                if (searchInput) searchInput.value = '';
                if (categorySelect) categorySelect.value = '';
                if (pageInput) pageInput.value = 1;
                form.submit();
            }
        });
    }

    // Gestione popup Nuova Domanda
    const btnNewQuestion = document.getElementById('btn-new-question');
    const modalOverlay = document.getElementById('modal-add-question');

    if (btnNewQuestion) {
        btnNewQuestion.addEventListener('click', () => {
            modalOverlay.style.display = 'flex';
            // Trigger change event per mostrare le risposte in base al tipo selezionato
            const typeSelect = document.getElementById('new-type');
            if (typeSelect) {
                typeSelect.dispatchEvent(new Event('change'));
            }
        });
    }

    // Gestione popup Nuova Categoria
    const btnNewCategory = document.getElementById('btn-new-category');

    if (btnNewCategory) {
        btnNewCategory.addEventListener('click', () => {
            document.getElementById('modal-categories').style.display = 'flex';
        });
    }

    // Funzione per aggiornare la listbox categorie
    function updateCategorySelect() {
        fetch('/src/api/api.php?endpoint=get_categories')
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.categories)) {
                    // Aggiorna il select di filtro ricerca
                    const filterSelect = document.getElementById('filter-category');
                    const filterCurrentValue = filterSelect.value;
                    while (filterSelect.options.length > 1) {
                        filterSelect.remove(1);
                    }
                    data.categories.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = '● ' + cat.category_name;
                        option.dataset.color = cat.color || '#6c757d';
                        filterSelect.appendChild(option);
                    });
                    if (filterCurrentValue) {
                        filterSelect.value = filterCurrentValue;
                    }
                    colorCategorySelect(filterSelect);

                    // Aggiorna il select di "Aggiungi Domanda"
                    const newQuestionSelect = document.getElementById('new-category');
                    if (newQuestionSelect) {
                        const newCurrentValue = newQuestionSelect.value;
                        while (newQuestionSelect.options.length > 1) {
                            newQuestionSelect.remove(1);
                        }
                        data.categories.forEach(cat => {
                            const option = document.createElement('option');
                            option.value = cat.id;
                            option.textContent = cat.category_name;
                            newQuestionSelect.appendChild(option);
                        });
                        if (newCurrentValue) {
                            newQuestionSelect.value = newCurrentValue;
                        }
                    }

                    // Aggiorna il select di "Modifica Domanda"
                    const editQuestionSelect = document.getElementById('edit-category');
                    if (editQuestionSelect) {
                        const editCurrentValue = editQuestionSelect.value;
                        while (editQuestionSelect.options.length > 1) {
                            editQuestionSelect.remove(1);
                        }
                        data.categories.forEach(cat => {
                            const option = document.createElement('option');
                            option.value = cat.id;
                            option.textContent = cat.category_name;
                            editQuestionSelect.appendChild(option);
                        });
                        if (editCurrentValue) {
                            editQuestionSelect.value = editCurrentValue;
                        }
                    }
                }
            });
    }

    // Funzione per ri-attaccare gli event listener alle categorie
    function attachCategoryEventListeners() {
        // Gestione edit categorie
        document.querySelectorAll('.btn-edit-category').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const categoryCard = btn.closest('.category-card');
                const viewMode = categoryCard.querySelector('.category-view-mode');
                const editMode = categoryCard.querySelector('.category-edit-mode');

                viewMode.style.display = 'none';
                editMode.style.display = 'block';
            });
        });

        // Annulla edit
        document.querySelectorAll('.btn-cancel-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const categoryCard = btn.closest('.category-card');
                const viewMode = categoryCard.querySelector('.category-view-mode');
                const editMode = categoryCard.querySelector('.category-edit-mode');

                viewMode.style.display = 'flex';
                editMode.style.display = 'none';
            });
        });

        // Salva categoria (tracciamento locale)
        document.querySelectorAll('.btn-save-category').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const categoryCard = btn.closest('.category-card');
                const categoryId = parseInt(categoryCard.dataset.categoryId);
                const newName = categoryCard.querySelector('.edit-category-name').value.trim();
                const newColor = categoryCard.querySelector('.edit-category-color').value;

                if (!newName) {
                    showToast('Inserisci il nome della categoria', 'warning');
                    return;
                }

                // Traccia il cambio
                if (categoryId > 0) {
                    // Categoria esistente: aggiungi a updated
                    categoryChanges.updated.push({
                        id: categoryId,
                        name: newName,
                        color: newColor
                    });
                } else {
                    // Categoria nuova: aggiorna l'elemento in added
                    const addedIndex = categoryChanges.added.findIndex(c => c.name === categoryCard.querySelector('.category-view-mode .category-name').textContent.trim());
                    if (addedIndex >= 0) {
                        categoryChanges.added[addedIndex].name = newName;
                        categoryChanges.added[addedIndex].color = newColor;
                    }
                }

                // Aggiorna il DOM in view mode
                const viewMode = categoryCard.querySelector('.category-view-mode');
                const editMode = categoryCard.querySelector('.category-edit-mode');
                viewMode.querySelector('.category-name').textContent = newName;
                viewMode.querySelector('.category-color-preview').style.background = newColor;
                viewMode.style.display = 'flex';
                editMode.style.display = 'none';

                showToast('Categoria "' + newName + '" aggiornata. Salva per confermare.', 'info');
            });
        });

        // Elimina categoria (tracciamento locale)
        document.querySelectorAll('.btn-delete-category').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (confirm('Sei sicuro di voler eliminare questa categoria?')) {
                    const categoryCard = btn.closest('.category-card');
                    const categoryId = parseInt(categoryCard.dataset.categoryId);
                    const categoryName = categoryCard.querySelector('.category-name').textContent;

                    // Traccia l'eliminazione
                    if (categoryId > 0) {
                        // Categoria esistente: aggiungi a deleted
                        categoryChanges.deleted.push(categoryId);
                    } else {
                        // Categoria nuova non salvata: rimuovi da added
                        categoryChanges.added = categoryChanges.added.filter(c => c.name !== categoryName);
                    }

                    // Rimuovi dal DOM
                    categoryCard.remove();
                    showToast('Categoria "' + categoryName + '" eliminata. Salva per confermare.', 'info');
                }
            });
        });
    }

    // Gestione modal categorie
    const modalCategories = document.getElementById('modal-categories');
    const closeCategoryBtn = document.querySelector('#modal-categories .modal-close');
    const btnAddCategory = document.getElementById('btn-add-category');

    if (closeCategoryBtn) {
        closeCategoryBtn.addEventListener('click', () => {
            modalCategories.style.display = 'none';
        });
    }

    if (modalCategories) {
        modalCategories.addEventListener('click', (e) => {
            if (e.target === modalCategories) {
                modalCategories.style.display = 'none';
            }
        });
    }

    if (btnAddCategory) {
        btnAddCategory.addEventListener('click', () => {
            const categoryName = document.getElementById('new-category-name').value.trim();
            const categoryColor = document.getElementById('new-category-color').value;

            if (!categoryName) {
                showToast('Inserisci il nome della categoria', 'warning');
                return;
            }

            // Traccia la categoria aggiunta localmente (senza ID, sarà assegnato dal server)
            categoryChanges.added.push({
                name: categoryName,
                color: categoryColor
            });

            // Crea l'HTML per la nuova categoria (con ID temporaneo negativo)
            const tempId = -Math.floor(Math.random() * 10000);
            const categoriesList = document.getElementById('categories-list');
            const newCategoryHTML = `
                <div class="category-card" data-category-id="${tempId}" style="border-left-color: ${categoryColor};">
                    <!-- View Mode -->
                    <div class="category-view-mode">
                        <div>
                            <strong class="category-name">${categoryName}</strong>
                            <div class="category-info">
                                Colore: <span class="category-color-preview" style="background: ${categoryColor};"></span>
                            </div>
                        </div>
                        <div class="category-actions">
                            <button type="button" class="btn-icon btn-warning btn-edit-category" data-category-id="${tempId}" title="Modifica">✎</button>
                            <button type="button" class="btn-icon btn-danger btn-delete-category" data-category-id="${tempId}" title="Elimina">×</button>
                        </div>
                    </div>

                    <!-- Edit Mode -->
                    <div class="category-edit-mode">
                        <div class="category-edit-inputs">
                            <div class="form-group">
                                <label>Nome Categoria</label>
                                <input type="text" class="edit-category-name" value="${categoryName}" placeholder="Nome categoria">
                            </div>
                            <div class="form-group">
                                <label>Colore</label>
                                <input type="color" class="edit-category-color" value="${categoryColor}">
                            </div>
                        </div>
                        <div class="category-edit-actions">
                            <button type="button" class="btn btn-secondary btn-cancel-edit">Annulla</button>
                            <button type="button" class="btn btn-success btn-save-category">✓ Salva</button>
                        </div>
                    </div>
                </div>
            `;

            // Aggiungi la nuova categoria alla lista
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = newCategoryHTML;
            const newCard = tempDiv.firstElementChild;
            categoriesList.appendChild(newCard);

            // Rimuovi il messaggio "Nessuna categoria" se esiste
            const emptyMsg = categoriesList.querySelector('p');
            if (emptyMsg) {
                emptyMsg.remove();
            }

            // Reset form
            document.getElementById('new-category-name').value = '';
            document.getElementById('new-category-color').value = '#3498db';

            // Re-attach event listeners per i nuovi button
            attachCategoryEventListeners();

            showToast('Categoria "' + categoryName + '" aggiunta. Salva per confermare.', 'success');
        });
    }

    // ========== SALVA TUTTE LE MODIFICHE CATEGORIE ==========
    const btnSaveAllCategories = document.getElementById('btn-save-categories');
    if (btnSaveAllCategories) {
        btnSaveAllCategories.addEventListener('click', () => {
            // Se non ci sono cambiamenti
            if (categoryChanges.deleted.length === 0 && categoryChanges.updated.length === 0 && categoryChanges.added.length === 0) {
                document.getElementById('modal-categories').style.display = 'none';
                return;
            }

            // Invia i cambiamenti al server
            fetch('/src/api/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    endpoint: 'save_categories',
                    deleted: categoryChanges.deleted,
                    updated: categoryChanges.updated,
                    added: categoryChanges.added
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Chiudi il modal e ricarica
                    document.getElementById('modal-categories').style.display = 'none';
                    location.reload();
                } else {
                    showToast(data.message || 'Errore sconosciuto', 'error');
                }
            })
            .catch(err => {
                showToast('Errore di rete: ' + err.message, 'error');
            });
        });
    }

    // Gestione edit categorie
    // Attach event listeners alle categorie
    attachCategoryEventListeners();

    function resetAddForm() {
        const form = document.getElementById('form-new-question');
        if (form) form.reset();
        document.getElementById('add-question-message').innerHTML = '';
    }

    function resetEditForm() {
        const form = document.getElementById('form-edit-question');
        if (form) form.reset();
        document.getElementById('edit-question-message').innerHTML = '';
    }

    const closeBtn = document.querySelector('#modal-add-question .modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modalOverlay.style.display = 'none';
            resetAddForm();
        });
    }

    // Chiudi modal quando clicchi sul overlay
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                modalOverlay.style.display = 'none';
                resetAddForm();
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
                showToast('Seleziona il tipo di domanda', 'warning');
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
                // Mostra il messaggio di successo nella banda verde
                const messageDiv = document.getElementById('add-question-message');
                messageDiv.innerHTML = '<div class="alert-success">✓ Domanda aggiunta con successo!</div>';

                // Chiudi il modal dopo 1 secondo e ricarica
                setTimeout(() => {
                    modalOverlay.style.display = 'none';
                    location.reload();
                }, 1000);
            })
            .catch(error => {
                const messageDiv = document.getElementById('add-question-message');
                messageDiv.innerHTML = '<div class="alert-error">✗ Errore nell\'aggiunta della domanda</div>';
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
            const answerInputs = [document.getElementById('new-answer1'), document.getElementById('new-answer2'), document.getElementById('new-answer3'), document.getElementById('new-answer4')];

            // Mostra/nascondi sezione risposte basato sul tipo
            if (type === 'multiple') {
                answersContainer.style.display = 'block';
                answer3Group.style.display = 'block';
                answer4Group.style.display = 'block';
                // Mostra i campi input answer
                document.getElementById('new-answer1').parentElement.style.display = 'block';
                document.getElementById('new-answer2').parentElement.style.display = 'block';
                correctSelect.innerHTML = '<option value="1">Risposta 1</option><option value="2">Risposta 2</option><option value="3">Risposta 3</option><option value="4">Risposta 4</option>';
                answerInputs.forEach(input => input.required = true);
            } else if (type === 'truefalse') {
                answersContainer.style.display = 'block';
                answer3Group.style.display = 'none';
                answer4Group.style.display = 'none';
                // Nascondi i campi input answer1 e answer2 per vero/falso
                document.getElementById('new-answer1').parentElement.style.display = 'none';
                document.getElementById('new-answer2').parentElement.style.display = 'none';
                correctSelect.innerHTML = '<option value="1">Vero</option><option value="2">Falso</option>';
                answerInputs.forEach(input => input.required = false);
            } else {
                answersContainer.style.display = 'none';
                answerInputs.forEach(input => input.required = false);
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
            resetEditForm();
        });
    }

    if (modalEditQuestion) {
        modalEditQuestion.addEventListener('click', (e) => {
            if (e.target === modalEditQuestion) {
                modalEditQuestion.style.display = 'none';
                resetEditForm();
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
            const editAnswerInputs = [document.getElementById('edit-answer1'), document.getElementById('edit-answer2'), document.getElementById('edit-answer3'), document.getElementById('edit-answer4')];

            if (type === 'truefalse') {
                editAnswersContainer.style.display = 'block';
                editAnswer3Group.style.display = 'none';
                editAnswer4Group.style.display = 'none';
                // Nascondi i campi input answer1 e answer2 per vero/falso
                document.getElementById('edit-answer1').parentElement.style.display = 'none';
                document.getElementById('edit-answer2').parentElement.style.display = 'none';
                editCorrectSelect.innerHTML = '<option value="1">Vero</option><option value="2">Falso</option>';
                editAnswerInputs.forEach(input => input.required = false);
            } else if (type === 'multiple') {
                editAnswersContainer.style.display = 'block';
                editAnswer3Group.style.display = 'block';
                editAnswer4Group.style.display = 'block';
                // Mostra i campi input answer
                document.getElementById('edit-answer1').parentElement.style.display = 'block';
                document.getElementById('edit-answer2').parentElement.style.display = 'block';
                editCorrectSelect.innerHTML = '<option value="1">Risposta 1</option><option value="2">Risposta 2</option><option value="3">Risposta 3</option><option value="4">Risposta 4</option>';
                editAnswerInputs.forEach(input => input.required = true);
            } else if (type === 'clickfirst') {
                editAnswersContainer.style.display = 'none';
                editAnswerInputs.forEach(input => input.required = false);
            } else {
                editAnswersContainer.style.display = 'none';
                editAnswerInputs.forEach(input => input.required = false);
            }
        });
    }

    if (formEditQuestion) {
        formEditQuestion.addEventListener('submit', (e) => {
            // Validazione: controlla che sia selezionato un tipo valido
            const typeSelected = document.getElementById('edit-type').value;

            if (!typeSelected) {
                e.preventDefault();
                showToast('Seleziona il tipo di domanda', 'warning');
                return;
            }

            // Per "Vero/Falso" e "Clicca il Primo" non servono risposte

            e.preventDefault();
            const formData = new FormData(formEditQuestion);
            formData.append('action', 'update_question');

            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Mostra il messaggio di successo nella banda verde
                const messageDiv = document.getElementById('edit-question-message');
                messageDiv.innerHTML = '<div class="alert-success">✓ Domanda modificata con successo!</div>';

                // Chiudi il modal dopo 1 secondo e ricarica
                setTimeout(() => {
                    modalEditQuestion.style.display = 'none';
                    location.reload();
                }, 1000);
            })
            .catch(error => {
                const messageDiv = document.getElementById('edit-question-message');
                messageDiv.innerHTML = '<div class="alert-error">✗ Errore nella modifica della domanda</div>';
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
        <form id="form-new-question" class="settings-group">
            <div class="form-group">
                <label for="new-question">Domanda</label>
                <textarea id="new-question" name="question" required rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="new-type">Tipo</label>
                    <select id="new-type" name="question_type" required>
                        <option value="multiple">📋 Scelta multipla</option>
                        <option value="truefalse">✔️ Vero/Falso</option>
                        <option value="clickfirst">⚡ Clicca per primo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new-category">Categoria</label>
                    <select id="new-category" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="new-timer">Timer (secondi)</label>
                    <input type="number" id="new-timer" name="timer" required min="5" max="120" step="5" value="30">
                </div>
            </div>

            <div id="answers-container" style="display: none;">
                <h3>Risposte</h3>
                <div class="form-group">
                    <label for="new-answer1">Risposta 1</label>
                    <input type="text" id="new-answer1" name="answer1" required>
                </div>
                <div class="form-group">
                    <label for="new-answer2">Risposta 2</label>
                    <input type="text" id="new-answer2" name="answer2" required>
                </div>
                <div class="form-group" id="answer3-group" style="display: none;">
                    <label for="new-answer3">Risposta 3</label>
                    <input type="text" id="new-answer3" name="answer3" required>
                </div>
                <div class="form-group" id="answer4-group" style="display: none;">
                    <label for="new-answer4">Risposta 4</label>
                    <input type="text" id="new-answer4" name="answer4" required>
                </div>
                <div class="form-group">
                    <label for="new-correct">Risposta Corretta</label>
                    <select id="new-correct" name="correct_answer">
                        <option value="1">Risposta 1</option>
                        <option value="2">Risposta 2</option>
                        <option value="3">Risposta 3</option>
                        <option value="4">Risposta 4</option>
                    </select>
                </div>
            </div>

            <div id="add-question-message" class="form-message"></div>

            <div class="modal-actions">
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
        <form id="form-edit-question" class="settings-group">
            <input type="hidden" id="edit-question-id" name="question_id">
            <div class="form-group">
                <label for="edit-question">Domanda</label>
                <textarea id="edit-question" name="question" required rows="3" placeholder="Inserisci il testo della domanda..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit-type">Tipo</label>
                    <select id="edit-type" name="question_type" required>
                        <option value="multiple">📋 Scelta multipla</option>
                        <option value="truefalse">✔️ Vero/Falso</option>
                        <option value="clickfirst">⚡ Clicca per primo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-category">Categoria</label>
                    <select id="edit-category" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit-timer">Timer (secondi)</label>
                    <input type="number" id="edit-timer" name="timer" required min="5" max="120" step="5" value="30">
                </div>
            </div>

            <div id="edit-answers-container" style="display: none;">
                <h3>Risposte</h3>
                <div class="form-group">
                    <label for="edit-answer1">Risposta 1</label>
                    <input type="text" id="edit-answer1" name="answer1" placeholder="Risposta corretta" required>
                </div>
                <div class="form-group">
                    <label for="edit-answer2">Risposta 2</label>
                    <input type="text" id="edit-answer2" name="answer2" placeholder="Risposta sbagliata" required>
                </div>
                <div class="form-group" id="edit-answer3-group" style="display: none;">
                    <label for="edit-answer3">Risposta 3</label>
                    <input type="text" id="edit-answer3" name="answer3" placeholder="Risposta sbagliata" required>
                </div>
                <div class="form-group" id="edit-answer4-group" style="display: none;">
                    <label for="edit-answer4">Risposta 4</label>
                    <input type="text" id="edit-answer4" name="answer4" placeholder="Risposta sbagliata" required>
                </div>
                <div class="form-group">
                    <label for="edit-correct">Risposta Corretta</label>
                    <select id="edit-correct" name="correct_answer">
                        <option value="1">Risposta 1</option>
                        <option value="2">Risposta 2</option>
                        <option value="3">Risposta 3</option>
                        <option value="4">Risposta 4</option>
                    </select>
                </div>
            </div>

            <div id="edit-question-message" class="form-message"></div>

            <div class="modal-actions">
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

<!-- MODALE GESTISCI CATEGORIE -->
<style>

</style>

<div id="modal-categories" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>📁 Gestisci Categorie</h2>
            <button type="button" class="modal-close">✕</button>
        </div>
        <div class="settings-group">
            <!-- AGGIUNGI CATEGORIA -->
            <div class="cat-add-section">
                <h3>➕ Aggiungi Categoria</h3>
                <div class="cat-add-inputs">
                    <input type="text" id="newCatName" placeholder="Nome categoria">
                    <input type="color" id="newCatColor" value="#3498db">
                </div>
                <button id="btn-add-cat" class="btn btn-success btn-add-category" onclick="addCategoryUI()">✓ Aggiungi</button>
            </div>

            <!-- LISTA CATEGORIE -->
            <div class="cat-modify-section">
                <h3>✎ Modifica Categorie</h3>
                <div id="categoriesList" class="cat-list"></div>
            </div>

            <!-- SALVA -->
            <div id="categories-message" class="form-message"></div>

            <div class="modal-actions">
                <button class="btn btn-success" onclick="saveCategoriesAPI()">✓ Salva Modifiche</button>
            </div>
        </div>
    </div>
</div>

<script>
let categoryChanges = { deleted: [], updated: [], added: [] };

// Apri modale
document.getElementById('btn-new-category').addEventListener('click', function() {
    loadCategoriesUI();
    document.getElementById('modal-categories').style.display = 'flex';
});

// Chiudi modale
document.querySelectorAll('#modal-categories .modal-close').forEach(b => {
    b.addEventListener('click', () => document.getElementById('modal-categories').style.display = 'none');
});

document.getElementById('modal-categories').addEventListener('click', (e) => {
    if (e.target.id === 'modal-categories') document.getElementById('modal-categories').style.display = 'none';
});

// Carica categorie nel modale
function loadCategoriesUI() {
    const list = document.getElementById('categoriesList');
    list.innerHTML = '';

    <?php foreach ($categories as $cat): ?>
    createCategoryCard('<?php echo $cat['id']; ?>', '<?php echo htmlspecialchars($cat['category_name']); ?>', '<?php echo $cat['color']; ?>', true);
    <?php endforeach; ?>
}

function createCategoryCard(id, name, color, existing = false) {
    const list = document.getElementById('categoriesList');
    const card = document.createElement('div');
    card.className = 'cat-card';
    card.setAttribute('data-id', id);
    card.style.borderLeft = `5px solid ${color}`;

    const displayDiv = document.createElement('div');
    displayDiv.className = 'cat-display';

    // Contenitore inputs
    const inputsContainer = document.createElement('div');
    inputsContainer.style.display = 'flex';
    inputsContainer.style.gap = '10px';
    inputsContainer.style.alignItems = 'center';
    inputsContainer.style.flex = '1';

    const nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.className = 'cat-name-edit';
    nameInput.value = name;
    nameInput.placeholder = 'Nome categoria';
    nameInput.style.flex = '1';
    nameInput.style.padding = '8px';
    nameInput.style.border = '1px solid #ddd';
    nameInput.style.borderRadius = '4px';

    const colorInput = document.createElement('input');
    colorInput.type = 'color';
    colorInput.className = 'cat-color-edit';
    colorInput.value = color;

    // Pulsante elimina
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn-icon btn-danger';
    deleteBtn.textContent = '×';
    deleteBtn.type = 'button';
    deleteBtn.style.visibility = id === '1' ? 'hidden' : 'visible';
    deleteBtn.onclick = function() { deleteCategoryUI(this); };

    inputsContainer.appendChild(nameInput);
    inputsContainer.appendChild(colorInput);

    displayDiv.appendChild(inputsContainer);
    displayDiv.appendChild(deleteBtn);

    // Aggiungi event listener per update su change
    nameInput.addEventListener('change', function() {
        updateCategoryChange(card, id, nameInput.value.trim(), colorInput.value);
    });

    colorInput.addEventListener('change', function() {
        updateCategoryChange(card, id, nameInput.value.trim(), colorInput.value);
        card.style.borderLeft = `5px solid ${colorInput.value}`;
    });

    card.appendChild(displayDiv);
    list.appendChild(card);
}

function updateCategoryChange(card, id, name, color) {
    if (!name) {
        showToast('Nome categoria obbligatorio', 'warning');
        return;
    }

    // Track changes
    if (id && !id.startsWith('temp_')) {
        categoryChanges.updated = categoryChanges.updated.filter(c => c.id != id);
        categoryChanges.updated.push({ id: parseInt(id), name, color });
    } else {
        categoryChanges.added = categoryChanges.added.filter(c => c.name !== card.querySelector('.cat-name-edit').dataset.oldName);
        categoryChanges.added.push({ name, color });
    }
}

function deleteCategoryUI(btn) {
    const card = btn.closest('.cat-card');
    const id = card.getAttribute('data-id');

    if (confirm('Elimina categoria?')) {
        if (id && id !== 'temp') {
            categoryChanges.deleted.push(parseInt(id));
        } else {
            categoryChanges.added = categoryChanges.added.filter(c => c.name !== card.querySelector('.cat-display strong').textContent);
        }
        card.remove();
    }
}

function addCategoryUI() {
    const name = document.getElementById('newCatName').value.trim();
    const color = document.getElementById('newCatColor').value;

    if (!name) { showToast('Nome categoria obbligatorio', 'warning'); return; }

    categoryChanges.added.push({ name, color });
    createCategoryCard('temp_' + Date.now(), name, color, false);

    document.getElementById('newCatName').value = '';
    document.getElementById('newCatColor').value = '#3498db';
}

async function saveCategoriesAPI() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Salvataggio...';

    try {
        const res = await fetch('/src/api/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                endpoint: 'save_categories',
                deleted: categoryChanges.deleted,
                updated: categoryChanges.updated,
                added: categoryChanges.added
            })
        });

        const result = await res.json();
        const messageDiv = document.getElementById('categories-message');

        if (result.success) {
            messageDiv.innerHTML = '<div class="alert-success">✓ ' + result.message + '</div>';
            categoryChanges = { deleted: [], updated: [], added: [] };
            setTimeout(() => {
                document.getElementById('modal-categories').style.display = 'none';
                location.reload();
            }, 1000);
        } else {
            messageDiv.innerHTML = '<div class="alert-error">✗ Errore: ' + result.message + '</div>';
        }
    } catch (e) {
        const messageDiv = document.getElementById('categories-message');
        messageDiv.innerHTML = '<div class="alert-error">✗ Errore: ' + e.message + '</div>';
    } finally {
        btn.disabled = false;
        btn.textContent = '✓ Salva Modifiche';
    }
}
</script>

