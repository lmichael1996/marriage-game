<!-- Tab: Management section -->
<div class="admin-section">
    <div class="tab-header-with-button">
        <h2>🎮 Gestione Set Domande</h2>
        <div class="button-group">
            <button class="btn btn-success" id="btn-new-set">+ Aggiungi Set</button>
            <button class="btn btn-primary" id="btn-create-game">🎮 Crea Partita</button>
        </div>
    </div>

    <!-- Search Bar -->
    <form method="POST" action="admin.php" class="search-toolbar">
        <input type="hidden" name="sets_page" id="sets_page" value="<?php echo (int)($_SESSION['sets_page'] ?? 1); ?>">
        <div class="search-input-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" name="set_search_query" id="search-sets" placeholder="Cerca set..." value="<?php echo htmlspecialchars($_POST['set_search_query'] ?? ''); ?>">
        </div>
        <select name="set_search_type" id="search-type" class="search-select">
            <option value="starts_with" <?php echo ($_POST['set_search_type'] ?? '') === 'starts_with' ? 'selected' : ''; ?>>Inizia con</option>
            <option value="contains" <?php echo ($_POST['set_search_type'] ?? 'contains') === 'contains' ? 'selected' : ''; ?>>Contiene</option>
            <option value="ends_with" <?php echo ($_POST['set_search_type'] ?? '') === 'ends_with' ? 'selected' : ''; ?>>Finisce con</option>
            <option value="exact" <?php echo ($_POST['set_search_type'] ?? '') === 'exact' ? 'selected' : ''; ?>>Esattamente</option>
        </select>
        <button type="submit" class="search-btn">Cerca</button>
        <?php if (!empty($_POST['set_search_query'])): ?>
            <button type="button" class="search-btn search-btn-clear" id="clear-sets-search">✖</button>
        <?php endif; ?>
    </form>

    <!-- Table View -->
    <div class="settings-group">
        <table class="questions-table sets-list-table">
            <thead>
                <tr>
                    <th>Nome Set</th>
                    <th>Descrizione</th>
                    <th>Domande</th>
                    <th>Ultimo Aggiornamento</th>
                    <th>Azioni</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questionSets as $set): ?>
                <tr data-set-id="<?php echo $set['id']; ?>">
                    <td><strong><?php
                        $setName = htmlspecialchars($set['set_name']);
                        if (!empty($_POST['set_search_query'])) {
                            $search = htmlspecialchars($_POST['set_search_query']);
                            $searchType = $_POST['set_search_type'] ?? 'contains';
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
                                $setName
                            );
                            echo $highlighted;
                        } else {
                            echo $setName;
                        }
                    ?></strong></td>
                    <td><?php echo htmlspecialchars($set['set_description'] ?? '-'); ?></td>
                    <td><?php echo $set['question_count'] ?? 0; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($set['updated_at'])); ?></td>
                    <td>
                        <button class="btn-icon btn-warning btn-edit-set" data-set-id="<?php echo $set['id']; ?>" title="Modifica">✎</button>
                        <button class="btn-icon btn-danger btn-delete-set" data-set-id="<?php echo $set['id']; ?>" title="Elimina">×</button>
                    </td>
                    <td>
                        <button class="btn-icon btn-success btn-start-game" data-set-id="<?php echo $set['id']; ?>" title="Avvia Partita">Avvia partita</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($questionSets)): ?>
        <div class="info-box loading-text">
            <h3>Nessun set disponibile</h3>
            <p>Clicca su "Aggiungi Set" per creare un nuovo set di domande.</p>
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

<!-- Modal: Edit Set -->
<div id="modal-edit-set" class="modal-overlay">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>Modifica Set</h2>
            <button type="button" class="modal-close" id="btn-close-edit-set">✕</button>
        </div>
        <div class="settings-group modal-scroll-content">
            <form id="edit-set-form">
                <input type="hidden" id="edit-set-id" name="set_id">

                <div class="form-group">
                    <label for="edit-set-name">Nome Set</label>
                    <input type="text" id="edit-set-name" name="set_name" required>
                </div>

                <div class="form-group">
                    <label for="edit-set-description">Descrizione</label>
                    <textarea id="edit-set-description" name="set_description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit-search-questions">Cerca e Aggiungi Domande</label>
                    <div class="search-row">
                        <input type="text" id="edit-search-questions" placeholder="Cerca domande..." class="search-row-input">
                        <select id="edit-search-type" class="search-row-select">
                            <option value="starts_with">Inizia con</option>
                            <option value="contains">Contiene</option>
                            <option value="ends_with">Finisce con</option>
                            <option value="exact">Esattamente</option>
                        </select>
                        <select id="edit-category-filter" class="search-row-select">
                            <option value="">🌐 Tutte Categorie</option>
                        </select>
                        <button type="button" class="btn btn-info search-row-btn" onclick="searchAvailableQuestions()">Cerca</button>
                    </div>
                    <div id="edit-available-questions" class="questions-list">
                        <p class="placeholder-text">Inserisci un termine di ricerca e clicca Cerca</p>
                    </div>
                </div>

                <hr class="modal-divider">

                <div class="form-group">
                    <label for="edit-set-questions">Domande Associate</label>
                    <div id="edit-set-questions" class="form-settings">
                        <p class="placeholder-text">Caricamento domande...</p>
                    </div>
                </div>

                <div id="edit-set-message" class="form-message"></div>
            </form>
            <div class="modal-actions-footer">
                <button type="button" class="btn btn-success" id="btn-save-set-changes">✓ Salva Modifiche</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Set -->
<div id="modal-add-set" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-content-large">
        <div class="modal-header">
            <h2>Aggiungi Set</h2>
            <button type="button" class="modal-close" id="btn-close-add-set" onclick="document.getElementById('modal-add-set').style.display='none'; cleanupAddSetModal();">✕</button>
        </div>
        <div class="settings-group modal-scroll-content">
            <form id="add-set-form">
                <div class="form-group">
                    <label for="add-set-name">Nome Set</label>
                    <input type="text" id="add-set-name" name="set_name" required>
                </div>

                <div class="form-group">
                    <label for="add-set-description">Descrizione</label>
                    <textarea id="add-set-description" name="set_description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="add-search-questions">Cerca e Aggiungi Domande</label>
                    <div class="search-row">
                        <input type="text" id="add-search-questions" placeholder="Cerca domande..." class="search-row-input">
                        <select id="add-search-type" class="search-row-select">
                            <option value="starts_with">Inizia con</option>
                            <option value="contains">Contiene</option>
                            <option value="ends_with">Finisce con</option>
                            <option value="exact">Esattamente</option>
                        </select>
                        <select id="add-category-filter" class="search-row-select">
                            <option value="">🌐 Tutte Categorie</option>
                        </select>
                        <button type="button" class="btn btn-info search-row-btn" onclick="searchAvailableQuestionsForNewSet()">Cerca</button>
                    </div>
                    <div id="add-available-questions" class="questions-list">
                        <p class="placeholder-text">Inserisci un termine di ricerca e clicca Cerca</p>
                    </div>
                </div>

                <hr class="modal-divider">

                <div class="form-group">
                    <label for="add-set-associated-questions">Domande Associate</label>
                    <div id="add-set-associated-questions" class="form-settings">
                        <p class="placeholder-text">Nessuna domanda associata</p>
                    </div>
                </div>

                <div id="add-set-message" class="form-message"></div>
            </form>
            <div class="modal-actions-footer">
                <button type="button" class="btn btn-success" id="btn-save-add-set" onclick="saveAddSetChanges()">✓ Salva Set</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Delete Set -->
<div id="modal-delete-set" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-content-medium">
        <div class="modal-header">
            <h2>Elimina Set</h2>
        </div>
        <div class="modal-body">
            <p>Sei sicuro di voler eliminare questo set? <strong>Questa azione non può essere annullata.</strong></p>
            <div class="button-container">
                <button type="button" class="btn btn-danger" id="btn-confirm-delete-set">Elimina</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-delete-set').style.display='none';">Annulla</button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================================
// PAGINATION SET
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    const ROWS_PER_PAGE = 10;
    const total = <?php echo (int)($pagination['total'] ?? 0); ?>;
    let totalPages = total > 0 ? Math.ceil(total / ROWS_PER_PAGE) : 1;
    let currentPage = <?php echo (int)($_SESSION['sets_page'] ?? 1); ?>;

    const pageInfo = document.getElementById('page-info');

    if (!pageInfo) {
        return;
    }

    function updateUI() {
        // If there are no sets, show 'No sets available' instead of 'Set 0-0 of 0'
        if (total === 0) {
            pageInfo.textContent = `Nessun set disponibile`;
        } else {
            const start = (currentPage - 1) * ROWS_PER_PAGE + 1;
            const end = Math.min(currentPage * ROWS_PER_PAGE, total);
            pageInfo.textContent = `Set ${start}-${end} di ${total}`;
        }

        const btnPrev = document.getElementById('btn-prev-page');
        const btnNext = document.getElementById('btn-next-page');

        if (btnPrev) btnPrev.disabled = currentPage === 1;
        if (btnNext) btnNext.disabled = currentPage >= totalPages;
    }

    function submitPage(page) {
        const form = document.querySelector('form[action="admin.php"]');
        const pageInput = document.getElementById('sets_page');
        if (form && pageInput) {
            pageInput.value = page;
            form.submit();
        }
    }

    updateUI();

    // Pagination buttons
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

    // Handle clear search
    const clearBtn = document.getElementById('clear-sets-search');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            const form = document.querySelector('form[action="admin.php"]');
            if (form) {
                const searchInput = document.querySelector('input[name="set_search_query"]');
                const pageInput = document.getElementById('sets_page');
                if (searchInput) searchInput.value = '';
                if (pageInput) pageInput.value = 1;
                form.submit();
            }
        });
    }
});

let editSetOriginalData = {
    id: null,
    name: null,
    description: null
};

// Flag to track if we are creating a new set (not yet saved)
let isNewSet = false;

// Tracks questions marked for removal from the set (only on final save)
let questionsToRemove = [];

// Closes the "Edit Set" modal without saving
function closeEditSetModal() {
    // Simple closure of the modal without saving
    // Saving will only occur by clicking "Save Changes"
    document.getElementById('modal-edit-set').style.display = 'none';
    questionsToRemove = []; // Reset the questions to be removed
}

// Saves the changes to the set when clicking the "Save Changes" button
function saveSetChanges() {
    const setId = document.getElementById('edit-set-id').value;
    const currentName = document.getElementById('edit-set-name').value.trim();
    const currentDescription = document.getElementById('edit-set-description').value.trim();
    const messageDiv = document.getElementById('edit-set-message');

    // Validation: If it's a new set, name is required and at least one question must be added
    if (isNewSet) {
        if (!currentName) {
            messageDiv.innerHTML = '<div class="alert-error">✗ Il nome del set è obbligatorio</div>';
            return;
        }

        // Count the questions in the set
        const questionItems = document.querySelectorAll('#edit-set-questions .question-item');
        if (questionItems.length === 0) {
            messageDiv.innerHTML = '<div class="alert-error">✗ Il set deve contenere almeno una domanda</div>';
            return;
        }

        // If it's a new set without an ID, create the set in the database first
        if (!setId) {
            api('add_questionset', {
                set_name: currentName,
                set_description: currentDescription
            })
            .then(data => {
                if (data.success && data.set_id) {
                    // Update the set ID in the form
                    document.getElementById('edit-set-id').value = data.set_id;

                    // Now add the questions associated with the newly created set
                    addQuestionsToNewSet(data.set_id, messageDiv);
                } else {
                    messageDiv.innerHTML = '<div class="alert-error">✗ Errore nella creazione del set</div>';
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante la creazione del set</div>';
            });
        } else {
            messageDiv.innerHTML = '<div class="alert-success">✓ Set salvato con successo!</div>';
            isNewSet = false;
            setTimeout(() => {
                document.getElementById('modal-edit-set').style.display = 'none';
                window.location.reload();
            }, 1000);
        }
    } else {
        // Existing set: always save
        // First, remove the questions marked for deletion
        if (questionsToRemove.length > 0) {
            // Remove all questions in the tracking array
            Promise.all(questionsToRemove.map(questionId =>
                api('remove_question_from_set', {
                    set_id: setId,
                    question_id: questionId
                })
            )).then(results => {
                // Check if all deletions were successful
                const allSuccess = results.every(r => r.success);
                if (allSuccess) {
                    // Now save the metadata via API
                    saveSetMetadata(setId, currentName, currentDescription, messageDiv);
                } else {
                    messageDiv.innerHTML = '<div class="alert-error">✗ Errore nell\'eliminazione delle domande</div>';
                }
            }).catch(error => {
                console.error('Errore:', error);
                messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante l\'eliminazione delle domande</div>';
            });
        } else {
            // No questions to remove, save only the metadata
            saveSetMetadata(setId, currentName, currentDescription, messageDiv);
        }
    }
}

// Helper function to save the set metadata
function saveSetMetadata(setId, currentName, currentDescription, messageDiv) {
    // First, save the order of the questions
    const setContainer = document.getElementById('questions-list-' + setId) || document.getElementById('edit-set-questions');
    if (setContainer) {
        const questionItems = setContainer.querySelectorAll('.question-item');
        const questionOrder = Array.from(questionItems).map((item, index) => {
            const questionId = parseInt(item.getAttribute('data-question-id'));
            return {
                question_id: questionId,
                order: index + 1
            };
        });

        // If there's an order to save, save it first
        if (questionOrder.length > 0) {
            api('update_question_order', {
                set_id: setId,
                questions: questionOrder
            }).catch(error => console.error('Errore aggiornamento ordine:', error));
        }
    }

    // Then save the metadata via API
    api('update_questionset', {
        set_id: setId,
        set_name: currentName,
        set_description: currentDescription
    })
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert-success">✓ Modifiche salvate!</div>';
            questionsToRemove = []; // Reset the array
            setTimeout(() => {
                document.getElementById('modal-edit-set').style.display = 'none';
                window.location.reload();
            }, 1000);
        } else {
            messageDiv.innerHTML = '<div class="alert-error">✗ Errore nel salvataggio</div>';
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante il salvataggio</div>';
    });
}

// Helper function to add questions to the newly created set
function addQuestionsToNewSet(setId, messageDiv) {
    const questionItems = document.querySelectorAll('#edit-set-questions .question-item');
    const questionIds = Array.from(questionItems).map(item => parseInt(item.getAttribute('data-question-id')));

    if (questionIds.length === 0) {
        messageDiv.innerHTML = '<div class="alert-success">✓ Set salvato con successo!</div>';
        isNewSet = false;
        setTimeout(() => {
            document.getElementById('modal-edit-set').style.display = 'none';
            window.location.reload();
        }, 1000);
        return;
    }

    // Add all questions to the set
    Promise.all(questionIds.map((questionId, index) =>
        api('add_question_to_set', {
            set_id: setId,
            question_id: questionId
        })
    )).then(results => {
        const allSuccess = results.every(r => r.success);
        if (allSuccess) {
            messageDiv.innerHTML = '<div class="alert-success">✓ Set salvato con successo!</div>';
            // Reset the localStorage for the next time "Crea Partita" is clicked
            localStorage.removeItem('addSetQuestions');
            isNewSet = false;
            setTimeout(() => {
                document.getElementById('modal-edit-set').style.display = 'none';
                window.location.reload();
            }, 1000);
        } else {
            messageDiv.innerHTML = '<div class="alert-error">✗ Errore nell\'aggiunta delle domande</div>';
        }
    }).catch(error => {
        console.error('Errore:', error);
        messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante l\'aggiunta delle domande</div>';
    });
}

// Helper function to clean up the search fields in the Edit Set modal
function cleanupEditSetModal() {
    document.getElementById('edit-search-questions').value = '';
    document.getElementById('add-category-filter').value = '';
    document.getElementById('edit-available-questions').innerHTML = '<p class="placeholder-text">Ricerca domande...</p>';

    // If it's "Crea Partita", reset the localStorage as well
    const modalTitle = document.querySelector('#modal-edit-set .modal-header h2').textContent;
    if (modalTitle.includes('Crea Partita')) {
        localStorage.removeItem('addSetQuestions');
    }
}

// Helper function to clean up the search fields in the Add Set modal
function cleanupAddSetModal() {
    document.getElementById('add-search-questions').value = '';
    document.getElementById('add-category-filter').value = '';
    document.getElementById('add-available-questions').innerHTML = '<p class="placeholder-text">Inserisci un termine di ricerca e clicca Cerca</p>';
    localStorage.removeItem('addSetQuestions');
}

// Helper function to save the changes to the new set
function saveAddSetChanges() {
    const currentName = document.getElementById('add-set-name').value.trim();
    const currentDescription = document.getElementById('add-set-description').value.trim();
    const messageDiv = document.getElementById('add-set-message');

    if (!currentName) {
        messageDiv.innerHTML = '<div class="alert-error">✗ Il nome del set è obbligatorio</div>';
        return;
    }

    // Count the questions in the set
    const questionItems = document.querySelectorAll('#add-set-associated-questions .question-item');
    if (questionItems.length === 0) {
        messageDiv.innerHTML = '<div class="alert-error">✗ Il set deve contenere almeno una domanda</div>';
        return;
    }

    // Create the set in the database
    api('add_questionset', {
        set_name: currentName,
        set_description: currentDescription
    })
    .then(data => {
        if (data.success && data.set_id) {
            // Get the IDs of the questions from localStorage
            const questionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

            // Add all questions to the set
            Promise.all(questionIds.map(questionId =>
                api('add_question_to_set', {
                    set_id: data.set_id,
                    question_id: questionId
                })
            )).then(results => {
                const allSuccess = results.every(r => r.success);
                if (allSuccess) {
                    messageDiv.innerHTML = '<div class="alert-success">✓ Set creato con successo!</div>';
                    localStorage.removeItem('addSetQuestions');
                    setTimeout(() => {
                        document.getElementById('modal-add-set').style.display = 'none';
                        window.location.reload();
                    }, 1000);
                } else {
                    messageDiv.innerHTML = '<div class="alert-error">✗ Errore nell\'aggiunta delle domande</div>';
                }
            }).catch(error => {
                console.error('Errore:', error);
                messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante l\'aggiunta delle domande</div>';
            });
        } else {
            messageDiv.innerHTML = '<div class="alert-error">✗ Errore nella creazione del set</div>';
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante la creazione del set</div>';
    });
}

// Helper function to close the "Add Set" modal and clean up the localStorage
// ============================================================================
// MODAL AND SET ACTIONS MANAGEMENT
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Event listener for the "Close/Save Set" button in the Edit Set modal
    const btnCloseEditSet = document.getElementById('btn-close-edit-set');
    if (btnCloseEditSet) {
        btnCloseEditSet.addEventListener('click', function() {
            closeEditSetModal();
            cleanupEditSetModal();
        });
    }

    // Event listener for the "Close" button in the Add Set modal
    const btnCloseAddSet = document.getElementById('btn-close-add-set');
    if (btnCloseAddSet) {
        btnCloseAddSet.addEventListener('click', function() {
            document.getElementById('modal-add-set').style.display = 'none';
            cleanupAddSetModal();
        });
    }

    // Event listener for the "Save Changes" / "Start Game" button
    const btnSaveSetChanges = document.getElementById('btn-save-set-changes');
    if (btnSaveSetChanges) {
        btnSaveSetChanges.addEventListener('click', function() {
            const modalTitle = document.querySelector('#modal-edit-set .modal-header h2').textContent;

            if (modalTitle.includes('Crea Partita')) {
                // For "Crea Partita", start the game instead of saving
                startGameFromModal();
            } else {
                // For "Modifica Set", save the changes
                saveSetChanges();
            }
        });
    }

    // Event listener for the "New Set" button
    const btnNewSet = document.getElementById('btn-new-set');
    const modalAddSet = document.getElementById('modal-add-set');

    if (btnNewSet) {
        btnNewSet.addEventListener('click', () => {
            // Reset the form for the new set
            document.getElementById('add-set-name').value = '';
            document.getElementById('add-set-description').value = '';
            document.getElementById('add-set-message').innerHTML = '';
            document.getElementById('add-set-associated-questions').innerHTML = '<p class="placeholder-text">Nessuna domanda associata</p>';

            // Reset the localStorage for questions
            localStorage.removeItem('addSetQuestions');

            // Open the modal
            document.getElementById('modal-add-set').style.display = 'flex';

            // Load categories into the filter
            loadCategoriesForAddSetFilter();
        });
    }

    // Event listener for the "Create Game" button
    const btnCreateGame = document.getElementById('btn-create-game');
    if (btnCreateGame) {
        btnCreateGame.addEventListener('click', createNewGame);
    }

    // Event listener for edit, delete, start-game buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-start-game')) {
            const setId = e.target.closest('.btn-start-game').getAttribute('data-set-id');
            startGameWithSet(setId);
            return;
        }

        if (e.target.closest('.btn-edit-set')) {
            const setId = e.target.closest('.btn-edit-set').getAttribute('data-set-id');

            // Load set data via API
            api('get_questionset&id=' + setId)
                .then(data => {
                    if (data.success && data.set) {
                        const s = data.set;
                        document.getElementById('edit-set-id').value = s.id;
                        document.getElementById('edit-set-name').value = s.set_name;
                        document.getElementById('edit-set-description').value = s.set_description || '';

                        // Show name and description fields for "Edit Set"
                        document.querySelector('#edit-set-name').parentElement.style.display = 'block';
                        document.querySelector('#edit-set-description').parentElement.style.display = 'block';

                        editSetOriginalData = {
                            id: s.id,
                            name: s.set_name,
                            description: s.set_description || ''
                        };

                        document.querySelector('.modal-header h2').textContent = 'Modifica Set';

                        // Reset the button to its original text "Save Changes"
                        const btnSaveSetChanges = document.getElementById('btn-save-set-changes');
                        if (btnSaveSetChanges) {
                            btnSaveSetChanges.textContent = '✓ Salva Modifiche';
                            btnSaveSetChanges.className = 'btn btn-success'; // Cambia colore a verde
                        }

                        // Reset flag for new set
                        isNewSet = false;

                        document.getElementById('edit-set-message').innerHTML = '';
                        document.getElementById('modal-edit-set').style.display = 'flex';

                        // Load categories into the filter
                        loadCategoriesForFilter();

                        // Load questions associated with the set
                        loadSetQuestions(s.id);
                    } else {
                        showToast('Errore nel caricamento del set', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Errore nel caricamento del set', 'error');
                });
            return;
        }

        if (e.target.closest('.btn-delete-set')) {
            const setId = e.target.closest('.btn-delete-set').getAttribute('data-set-id');
            const modal = document.getElementById('modal-delete-set');
            const btnConfirmDelete = document.getElementById('btn-confirm-delete-set');

            modal.style.display = 'flex';

            btnConfirmDelete.onclick = function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_questionset">
                    <input type="hidden" name="set_id" value="${setId}">
                `;
                document.body.appendChild(form);
                form.submit();
            };
        }
    });
});

// ============================================================================
// FUNCTIONS FOR SEARCHING AND MANAGING SETS
// ============================================================================

// Load categories into the filter dropdown
function loadCategoriesForFilter() {
    const select = document.getElementById('edit-category-filter');
    if (!select || select.options.length > 1) {
        return; // Già caricate
    }

    api('get_categories')
        .then(data => {
            if (data.success && data.categories) {
                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.category_name;
                    select.appendChild(option);
                });

                // Add event listener to trigger search on category change
                select.addEventListener('change', function() {
                    searchAvailableQuestions();
                });
            }
        })
        .catch(error => console.error('Errore nel caricamento categorie:', error));
}

// Load categories for the filter in the "Add Set" modal
function loadCategoriesForAddSetFilter() {
    const select = document.getElementById('add-category-filter');
    if (!select || select.options.length > 1) {
        return; // Già caricate
    }

    api('get_categories')
        .then(data => {
            if (data.success && data.categories) {
                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.category_name;
                    select.appendChild(option);
                });

                select.addEventListener('change', function() {
                    searchAvailableQuestionsForNewSet();
                });
            }
        })
        .catch(error => console.error('Errore nel caricamento categorie:', error));
}

// Load questions associated with a set and display them in the Edit Set modal
function loadSetQuestions(setId) {
    const containerAssociated = document.getElementById('edit-set-questions');

    api('get_set_questions&set_id=' + setId)
        .then(data => {
            if (data.success && data.questions && data.questions.length > 0) {
                let html = '<div class="questions-associated" id="questions-list-' + setId + '">';
                const total = data.questions.length;

                // Filter questions excluding those marked for removal
                const filteredQuestions = data.questions.filter(q => !questionsToRemove.includes(q.id));

                if (filteredQuestions.length === 0) {
                    containerAssociated.innerHTML = '<p class="text-muted-center">Nessuna domanda associata</p>';
                    return;
                }

                filteredQuestions.forEach((q, index) => {
                    const categoryBadge = q.category_name ? `<span class="badge-category"><span class="color-dot" style="background: ${q.color || '#6c757d'};"></span>${q.category_name}</span>` : '';

                    const typeMap = {
                        'multiple': '📋 Scelta multipla',
                        'truefalse': '✔️ Vero/Falso',
                        'clickfirst': '⚡ Clicca per primo'
                    };
                    const typeBadge = q.question_type ? `<span class="badge-type">${typeMap[q.question_type] || q.question_type}</span>` : '';

                    html += `
                        <div class="question-item" data-question-id="${q.id}" data-set-id="${setId}" draggable="true">
                            <div class="question-item-content">
                                ${q.question}
                            </div>
                            <div class="question-item-center">
                                ${categoryBadge}
                            </div>
                            <div class="question-item-center">
                                ${typeBadge}
                            </div>
                            <div class="question-item-actions">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestionFromSet(${setId}, ${q.id})">
                                    Elimina
                                </button>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                containerAssociated.innerHTML = html;

                // Setup drag and drop per riordinamento
                setupDragAndDrop(setId);
            } else {
                containerAssociated.innerHTML = '<p class="text-muted-center">Nessuna domanda associata</p>';
            }
        })
        .catch(error => {
            console.error('Errore nel caricamento domande:', error);
            containerAssociated.innerHTML = '<p class="text-error-center">Errore nel caricamento</p>';
        });
}

// Load questions for "Create Game" (from localStorage)
function loadGameQuestions() {
    const containerAssociated = document.getElementById('edit-set-questions');
    const addedQuestionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

    if (addedQuestionIds.length === 0) {
        containerAssociated.innerHTML = '<p class="text-muted-center">Nessuna domanda associata</p>';
        return;
    }

    // Fetch all questions to get their details
    api('get_questions')
        .then(data => {
            if (data.success && data.questions) {
                // Normalize IDs in localStorage to integers
                const normalizedIds = addedQuestionIds.map(id => parseInt(id));

                const associatedQuestions = data.questions.filter(q => {
                    const qId = parseInt(q.id);
                    return normalizedIds.includes(qId);
                });

                let html = '<div class="questions-associated" id="game-questions-list">';

                associatedQuestions.forEach((q, index) => {
                    const categoryBadge = q.category_name ? `<span class="badge-category"><span class="color-dot" style="background: ${q.color || '#6c757d'};"></span>${q.category_name}</span>` : '';

                    const typeMap = {
                        'multiple': '📋 Scelta multipla',
                        'truefalse': '✔️ Vero/Falso',
                        'clickfirst': '⚡ Clicca per primo'
                    };
                    const typeBadge = q.question_type ? `<span class="badge-type">${typeMap[q.question_type] || q.question_type}</span>` : '';

                    html += `
                        <div class="question-item" data-question-id="${q.id}" draggable="true">
                            <div class="question-item-content">
                                ${q.question}
                            </div>
                            <div class="question-item-center">
                                ${categoryBadge}
                            </div>
                            <div class="question-item-center">
                                ${typeBadge}
                            </div>
                            <div class="question-item-actions">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeGameQuestion(${q.id})">
                                    Elimina
                                </button>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                containerAssociated.innerHTML = html;

                // Setup drag and drop for reordering
                setupDragAndDropForGameQuestions();
            } else {
                containerAssociated.innerHTML = '<p class="text-muted-center">Nessuna domanda associata</p>';
            }
        })
        .catch(error => {
            console.error('Errore nel caricamento domande:', error);
            containerAssociated.innerHTML = '<p class="text-error-center">Errore nel caricamento</p>';
        });
}

// Setup drag and drop for "Create Game"
function setupDragAndDropForGameQuestions() {
    const container = document.getElementById('game-questions-list');
    if (!container) return;

    let draggedElement = null;
    let dragStarted = false;

    // Use event delegation on the container
    container.addEventListener('dragstart', (e) => {
        if (e.target.draggable === true || e.target.hasAttribute('draggable')) {
            draggedElement = e.target;
            dragStarted = true;
            draggedElement.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        }
    }, false);

    container.addEventListener('dragend', (e) => {
        if (draggedElement) {
            draggedElement.style.opacity = '1';
            draggedElement = null;
            dragStarted = false;
        }
        // Remove borders from all elements
        const items = container.querySelectorAll('[draggable="true"]');
        items.forEach(item => {
            item.style.borderTop = 'none';
        });
    }, false);

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        if (dragStarted && e.target.draggable === true) {
            if (draggedElement && e.target !== draggedElement) {
                e.target.style.borderTop = '3px solid #0066cc';
            }
        }
    }, false);

    container.addEventListener('dragleave', (e) => {
        if (e.target.draggable === true && dragStarted) {
            e.target.style.borderTop = 'none';
        }
    }, false);

    container.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (dragStarted && draggedElement && e.target.draggable === true && e.target !== draggedElement) {
            e.target.style.borderTop = 'none';

            // Reset opacity immediately
            if (draggedElement) {
                draggedElement.style.opacity = '1';
            }
            // Remove borders from all elements
            const items = container.querySelectorAll('[draggable="true"]');
            items.forEach(item => {
                item.style.borderTop = 'none';
            });

            // Insert the dragged element before the target element
            const allItems = Array.from(container.querySelectorAll('[draggable="true"]'));
            const draggedIndex = allItems.indexOf(draggedElement);
            const targetIndex = allItems.indexOf(e.target);

            if (draggedIndex >= 0 && targetIndex >= 0) {
                if (draggedIndex < targetIndex) {
                    // Dragging down: insert after the target
                    if (e.target.nextSibling) {
                        e.target.parentNode.insertBefore(draggedElement, e.target.nextSibling);
                    } else {
                        e.target.parentNode.appendChild(draggedElement);
                    }
                } else {
                    // Dragging up: insert before the target
                    e.target.parentNode.insertBefore(draggedElement, e.target);
                }

                // Update the order in localStorage
                updateGameQuestionOrderInLocalStorage();
            }
        }

        dragStarted = false;
        draggedElement = null;
    }, false);
}

// Update the order of questions in localStorage for "Create Game"
function updateGameQuestionOrderInLocalStorage() {
    const container = document.getElementById('game-questions-list');
    if (!container) return;

    const items = container.querySelectorAll('[draggable="true"]');
    const questionIds = Array.from(items).map(item => parseInt(item.getAttribute('data-question-id')));

    // Save the order in localStorage
    localStorage.setItem('addSetQuestions', JSON.stringify(questionIds));
}

// Add a question to "Create Game" (localStorage)
function addQuestionToGameSet(questionId) {
    // Get the questions already added from localStorage
    let addedQuestions = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

    // Add the new ID if it doesn't already exist
    if (!addedQuestions.includes(questionId)) {
        addedQuestions.push(questionId);
        localStorage.setItem('addSetQuestions', JSON.stringify(addedQuestions));

        // Reload the list and search to reflect the changes
        loadGameQuestions();
        setTimeout(() => {
            highlightGameQuestion(questionId);
        }, 100);
        highlightSearchResultForGameSet(questionId);
        searchAvailableQuestions();
        showToast('Domanda aggiunta al set', 'success');
    } else {
        // If the question is already in the set, just highlight it
        showAlreadyPresentError(questionId);
    }
}

// Remove a question from "Create Game"
function removeGameQuestion(questionId) {
    let addedQuestions = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');
    addedQuestions = addedQuestions.filter(id => id !== questionId);
    localStorage.setItem('addSetQuestions', JSON.stringify(addedQuestions));

    loadGameQuestions();
    searchAvailableQuestions();
    showToast('Domanda rimossa dal set', 'error');
}

// Highlight a question in "Create Game"
function highlightGameQuestion(questionId) {
    const container = document.getElementById('game-questions-list');
    if (!container) return;

    const questionItem = container.querySelector(`[data-question-id="${questionId}"]`);
    if (questionItem) {
        questionItem.classList.add('highlight');
        setTimeout(() => {
            questionItem.classList.remove('highlight');
        }, 1500);
    }
}

// Highlight a question in the search for "Create Game"
function highlightSearchResultForGameSet(questionId) {
    const container = document.getElementById('edit-available-questions');
    if (!container) return;

    const questionItem = container.querySelector(`[data-question-id="${questionId}"]`);
    if (questionItem) {
        questionItem.classList.add('highlight');
        setTimeout(() => {
            questionItem.classList.remove('highlight');
        }, 1500);
    }
}

// Setup drag and drop for reordering questions
function setupDragAndDrop(setId) {
    const container = document.getElementById('questions-list-' + setId);
    if (!container) return;

    let draggedElement = null;
    let dragStarted = false;

    // Use event delegation on the container
    container.addEventListener('dragstart', (e) => {
        if (e.target.draggable === true || e.target.hasAttribute('draggable')) {
            draggedElement = e.target;
            dragStarted = true;
            draggedElement.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        }
    }, false);

    container.addEventListener('dragend', (e) => {
        if (draggedElement) {
            draggedElement.style.opacity = '1';
            draggedElement = null;
            dragStarted = false;
        }
        // Remove borders from all elements
        const items = container.querySelectorAll('[draggable="true"]');
        items.forEach(item => {
            item.style.borderTop = 'none';
        });
    }, false);

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        if (dragStarted && e.target.draggable === true) {
            if (draggedElement && e.target !== draggedElement) {
                e.target.style.borderTop = '3px solid #0066cc';
            }
        }
    }, false);

    container.addEventListener('dragleave', (e) => {
        if (e.target.draggable === true && dragStarted) {
            e.target.style.borderTop = 'none';
        }
    }, false);

    container.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (dragStarted && draggedElement && e.target.draggable === true && e.target !== draggedElement) {
            e.target.style.borderTop = 'none';

            // Reset opacity immediately
            if (draggedElement) {
                draggedElement.style.opacity = '1';
            }
            // Remove borders from all elements
            const items = container.querySelectorAll('[draggable="true"]');
            items.forEach(item => {
                item.style.borderTop = 'none';
            });

            // Insert the dragged element before the target element
            // If dragging down, insert after; if dragging up, insert before
            const allItems = Array.from(container.querySelectorAll('[draggable="true"]'));
            const draggedIndex = allItems.indexOf(draggedElement);
            const targetIndex = allItems.indexOf(e.target);

            if (draggedIndex >= 0 && targetIndex >= 0) {
                if (draggedIndex < targetIndex) {
                    // Dragging down: insert after the target
                    if (e.target.nextSibling) {
                        e.target.parentNode.insertBefore(draggedElement, e.target.nextSibling);
                    } else {
                        e.target.parentNode.appendChild(draggedElement);
                    }
                } else {
                    // Dragging up: insert before the target
                    e.target.parentNode.insertBefore(draggedElement, e.target);
                }

                // Update the order in the database
                updateOrderInDatabase(setId);
            }
        }

        dragStarted = false;
        draggedElement = null;
    }, false);
}

// Update the order in the database
function updateOrderInDatabase(setId) {
    const container = document.getElementById('questions-list-' + setId);
    if (!container) {
        console.error('Container non trovato per setId:', setId);
        return;
    }

    const items = container.querySelectorAll('[draggable="true"]');

    if (items.length === 0) {
        console.warn('Nessuna domanda trovata nel container');
        return;
    }

    const questionOrder = Array.from(items).map((item, index) => {
        const questionId = parseInt(item.getAttribute('data-question-id'));
        if (isNaN(questionId)) {
            console.error('ID domanda non valido:', item.getAttribute('data-question-id'));
            return null;
        }
        return {
            question_id: questionId,
            order: index + 1
        };
    }).filter(item => item !== null);

    if (questionOrder.length === 0) {
        console.error('Nessun ordine valido da salvare');
        return;
    }

    api('update_question_order', {
        set_id: setId,
        questions: questionOrder
    })
    .then(data => {
        if (!data.success) {
            console.error('Errore nell\'aggiornamento ordine:', data.error);
            // Load with delay to avoid race condition
            setTimeout(() => {
                loadSetQuestions(setId);
            }, 300);
        }
    })
    .catch(error => {
        console.error('Errore nel salvataggio ordine:', error);
        // Reload with delay to avoid race condition
        setTimeout(() => {
            loadSetQuestions(setId);
        }, 300);
    });
}

// Setup drag and drop per il nuovo set (localStorage)
function setupDragAndDropForNewSet() {
    const container = document.getElementById('new-set-questions-list');
    if (!container) return;

    let draggedElement = null;
    let dragStarted = false;

    // Use event delegation on the container
    container.addEventListener('dragstart', (e) => {
        if (e.target.draggable === true || e.target.hasAttribute('draggable')) {
            draggedElement = e.target;
            dragStarted = true;
            draggedElement.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        }
    }, false);

    container.addEventListener('dragend', (e) => {
        if (draggedElement) {
            draggedElement.style.opacity = '1';
            draggedElement = null;
            dragStarted = false;
        }
        // Remove borders from all elements
        const items = container.querySelectorAll('[draggable="true"]');
        items.forEach(item => {
            item.style.borderTop = 'none';
        });
    }, false);

    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        if (dragStarted && e.target.draggable === true) {
            if (draggedElement && e.target !== draggedElement) {
                e.target.style.borderTop = '3px solid #0066cc';
            }
        }
    }, false);

    container.addEventListener('dragleave', (e) => {
        if (e.target.draggable === true && dragStarted) {
            e.target.style.borderTop = 'none';
        }
    }, false);

    container.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (dragStarted && draggedElement && e.target.draggable === true && e.target !== draggedElement) {
            e.target.style.borderTop = 'none';

            // Reset opacity immediately
            if (draggedElement) {
                draggedElement.style.opacity = '1';
            }
            // Remove borders from all elements
            const items = container.querySelectorAll('[draggable="true"]');
            items.forEach(item => {
                item.style.borderTop = 'none';
            });

            // Insert the dragged element before the target element
            const allItems = Array.from(container.querySelectorAll('[draggable="true"]'));
            const draggedIndex = allItems.indexOf(draggedElement);
            const targetIndex = allItems.indexOf(e.target);

            if (draggedIndex >= 0 && targetIndex >= 0) {
                if (draggedIndex < targetIndex) {
                    // Dragging down: insert after the target
                    if (e.target.nextSibling) {
                        e.target.parentNode.insertBefore(draggedElement, e.target.nextSibling);
                    } else {
                        e.target.parentNode.appendChild(draggedElement);
                    }
                } else {
                    // Dragging up: insert before the target
                    e.target.parentNode.insertBefore(draggedElement, e.target);
                }

                // Update the order in localStorage
                updateNewSetOrderInLocalStorage();
            }
        }

        dragStarted = false;
        draggedElement = null;
    }, false);
}

// Update the order of questions in localStorage for the new set
function updateNewSetOrderInLocalStorage() {
    const container = document.getElementById('new-set-questions-list');
    if (!container) return;

    const items = container.querySelectorAll('[draggable="true"]');
    const questionIds = Array.from(items).map(item => parseInt(item.getAttribute('data-question-id')));

    // Save the order in localStorage
    localStorage.setItem('addSetQuestions', JSON.stringify(questionIds));
}

// Remove a question from a set (track only, save on "Save Changes" click)
function removeQuestionFromSet(setId, questionId) {
    // Add to the list of questions to remove
    if (!questionsToRemove.includes(questionId)) {
        questionsToRemove.push(questionId);
    }

    // Remove the element from the list immediately
    const questionElement = document.querySelector(`[data-question-id="${questionId}"]`);
    if (questionElement) {
        questionElement.remove();
    }

    showToast('Domanda rimossa dal set', 'error');
}

// Ricerca domande disponibili
function searchAvailableQuestions() {
    const searchTerm = document.getElementById('edit-search-questions').value.trim();
    const searchType = document.getElementById('edit-search-type').value;
    const categoryId = document.getElementById('edit-category-filter').value;
    const container = document.getElementById('edit-available-questions');
    const setId = document.getElementById('edit-set-id').value;

    container.innerHTML = '<p class="text-muted-center">Ricerca in corso...</p>';

    // Fetch tutte le domande
    api('get_questions')
        .then(data => {
            if (data.success && data.questions) {
                // Applica filtro per tipo di ricerca
                let pattern = null;
                if (searchTerm) {
                    switch(searchType) {
                        case 'starts_with':
                            pattern = new RegExp('^' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
                            break;
                        case 'ends_with':
                            pattern = new RegExp(searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$', 'i');
                            break;
                        case 'exact':
                            pattern = new RegExp('^' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$', 'i');
                            break;
                        default:
                            pattern = new RegExp(searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
                    }
                }

                // Filtra le domande in base al termine di ricerca e alla categoria
                const filtered = data.questions.filter(q => {
                    const matchesSearch = !pattern || (q.question && pattern.test(q.question));
                    const matchesCategory = !categoryId || (q.category_id && q.category_id.toString() === categoryId);
                    return matchesSearch && matchesCategory;
                });

                if (filtered.length === 0) {
                    container.innerHTML = '<p class="text-muted-center">Nessuna domanda trovata</p>';
                    return;
                }

                // Se è un nuovo set (setId vuoto), non fare fetch delle domande associate
                if (!setId) {
                    // Per "Crea Partita", ottieni le domande già aggiunte dal localStorage
                    const addedQuestionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]').map(id => parseInt(id));

                    // Per nuovi set, mostra tutte le domande filtrate escludendo quelle già aggiunte
                    let html = '';
                    filtered.forEach(q => {
                        // Salta le domande già aggiunte
                        if (addedQuestionIds.includes(parseInt(q.id))) {
                            return;
                        }

                        const categoryBadge = q.category_name ? `<span class="badge-category"><span class="color-dot" style="background: ${q.color || '#6c757d'};"></span>${q.category_name}</span>` : '';
                        const typeMap = { 'multiple': '📋 Scelta multipla', 'truefalse': '✔️ Vero/Falso', 'clickfirst': '⚡ Clicca per primo' };
                        const typeBadge = q.question_type ? `<span class="badge-type">${typeMap[q.question_type] || q.question_type}</span>` : '';

                        let questionText = q.question;
                        if (searchTerm && pattern) {
                            questionText = q.question.replace(pattern, '<mark>$&</mark>');
                        }

                        // Per "Crea Partita", aggiungi a localStorage; per nuovi set da salvare in DB, usa addQuestionToSet
                        const functionCall = isNewSet && document.querySelector('#modal-edit-set .modal-header h2').textContent.includes('Crea Partita') ? `addQuestionToGameSet(${q.id})` : `addQuestionToSet(${setId}, ${q.id})`;

                        html += `
                            <div class="question-item" data-question-id="${q.id}">
                                <div class="question-item-content">${questionText}</div>
                                <div class="question-item-center">${categoryBadge}</div>
                                <div class="question-item-center">${typeBadge}</div>
                                <div class="question-item-actions">
                                    <button type="button" class="btn btn-success btn-sm" onclick="${functionCall}" title="Aggiungi al set">+ Aggiungi</button>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                    return;
                }

                // Ottieni le domande già associate al set dal DOM
                const associatedItems = document.querySelectorAll('#edit-set-questions .question-item');
                const setQuestionIds = Array.from(associatedItems).map(item => parseInt(item.getAttribute('data-question-id')));

                // Escludi le domande già associate dal risultato della ricerca
                const availableQuestions = filtered.filter(q => !setQuestionIds.includes(parseInt(q.id)));

                if (availableQuestions.length === 0) {
                    container.innerHTML = '<p class="text-muted-center">Tutte le domande trovate sono già associate</p>';
                    return;
                }

                let html = '';
                availableQuestions.forEach(q => {
                    const categoryBadge = q.category_name ? `<span class="badge-category"><span class="color-dot" style="background: ${q.color || '#6c757d'};"></span>${q.category_name}</span>` : '';
                    const typeMap = { 'multiple': '📋 Scelta multipla', 'truefalse': '✔️ Vero/Falso', 'clickfirst': '⚡ Clicca per primo' };
                    const typeBadge = q.question_type ? `<span class="badge-type">${typeMap[q.question_type] || q.question_type}</span>` : '';

                    let questionText = q.question;
                    if (searchTerm && pattern) {
                        questionText = q.question.replace(pattern, '<mark>$&</mark>');
                    }

                    html += `
                        <div class="question-item" data-question-id="${q.id}">
                            <div class="question-item-content">${questionText}</div>
                            <div class="question-item-center">${categoryBadge}</div>
                            <div class="question-item-center">${typeBadge}</div>
                            <div class="question-item-actions">
                                <button type="button" class="btn btn-success btn-sm" onclick="addQuestionToSet(${setId}, ${q.id})" title="Aggiungi al set">+ Aggiungi</button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-error-center">Errore nel caricamento domande</p>';
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            container.innerHTML = '<p class="text-error-center">Errore durante la ricerca</p>';
        });
}

// Ricerca domande per il nuovo set (senza ID set)
function searchAvailableQuestionsForNewSet() {
    const searchTerm = document.getElementById('add-search-questions').value.trim();
    const searchType = document.getElementById('add-search-type').value;
    const categoryId = document.getElementById('add-category-filter').value;
    const container = document.getElementById('add-available-questions');
    const associatedContainer = document.getElementById('add-set-associated-questions');

    // Fetch tutte le domande (sempre, per aggiornare l'elenco escludendo quelle appena aggiunte)
    api('get_questions')
        .then(data => {
            if (data.success && data.questions) {
                // Applica filtro per tipo di ricerca
                let pattern = null;
                if (searchTerm) {
                    switch(searchType) {
                        case 'starts_with':
                            pattern = new RegExp('^' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
                            break;
                        case 'ends_with':
                            pattern = new RegExp(searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$', 'i');
                            break;
                        case 'exact':
                            pattern = new RegExp('^' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$', 'i');
                            break;
                        default:
                            pattern = new RegExp(searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
                    }
                }

                // Filtra le domande in base al termine di ricerca e alla categoria
                const filtered = data.questions.filter(q => {
                    const matchesSearch = !pattern || (q.question && pattern.test(q.question));
                    const matchesCategory = !categoryId || (q.category_id && q.category_id.toString() === categoryId);
                    return matchesSearch && matchesCategory;
                });

                if (filtered.length === 0) {
                    container.innerHTML = '<p class="text-muted-center">Nessuna domanda trovata</p>';
                    return;
                }

                // Ottieni le domande già associate al nuovo set (da localStorage)
                const addedQuestionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]').map(id => parseInt(id));

                // Escludi le domande già associate dal risultato della ricerca
                const availableQuestions = filtered.filter(q => !addedQuestionIds.includes(parseInt(q.id)));

                if (availableQuestions.length === 0) {
                    container.innerHTML = '<p class="text-muted-center">Tutte le domande trovate sono già associate</p>';
                    return;
                }

                let html = '';
                availableQuestions.forEach(q => {
                    const categoryBadge = q.category_name ? `<span class="badge-category"><span class="color-dot" style="background: ${q.color || '#6c757d'};"></span>${q.category_name}</span>` : '';
                    const typeMap = { 'multiple': '📋 Scelta multipla', 'truefalse': '✔️ Vero/Falso', 'clickfirst': '⚡ Clicca per primo' };
                    const typeBadge = q.question_type ? `<span class="badge-type">${typeMap[q.question_type] || q.question_type}</span>` : '';

                    let questionText = q.question;
                    if (searchTerm && pattern) {
                        questionText = q.question.replace(pattern, '<mark>$&</mark>');
                    }

                    html += `
                        <div class="question-item" data-question-id="${q.id}">
                            <div class="question-item-content">${questionText}</div>
                            <div class="question-item-center">${categoryBadge}</div>
                            <div class="question-item-center">${typeBadge}</div>
                            <div class="question-item-actions">
                                <button type="button" class="btn btn-success btn-sm" onclick="addQuestionToNewSet(${q.id})" title="Aggiungi al set">+ Aggiungi</button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-error-center">Errore nel caricamento domande</p>';
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            container.innerHTML = '<p class="text-error-center">Errore durante la ricerca</p>';
        });
}

// Evidenzia una domanda temporaneamente
function highlightQuestion(questionId, setId) {
    const container = document.getElementById('questions-list-' + setId);
    if (!container) return;

    const questionItem = container.querySelector(`[data-question-id="${questionId}"]`);
    if (questionItem) {
        questionItem.classList.add('highlight');
        setTimeout(() => {
            questionItem.classList.remove('highlight');
        }, 1500);
    }
}

// Evidenzia una domanda nella sezione di ricerca
function highlightSearchResult(questionId) {
    const container = document.getElementById('edit-available-questions');
    if (!container) return;

    const questionItem = container.querySelector(`[data-question-id="${questionId}"]`);
    if (questionItem) {
        questionItem.classList.add('highlight');
        setTimeout(() => {
            questionItem.classList.remove('highlight');
        }, 1500);
    }
}

// Aggiunge una domanda al set
function addQuestionToSet(setId, questionId) {
    api('add_question_to_set', {
        set_id: setId,
        question_id: questionId
    })
    .then(data => {
        if (data.success) {
            showToast('Domanda aggiunta con successo!', 'success');
            // Ricarica le domande associate
            loadSetQuestions(setId);
            setTimeout(() => {
                highlightQuestion(questionId, setId);
            }, 100);
            // Aggiorna la ricerca per escludere la domanda appena aggiunta
            searchAvailableQuestions();
        } else {
            // Estrai il messaggio di errore specifico
            const errorMsg = data.error || data.message || 'Non è stato possibile aggiungere la domanda';
            if (errorMsg.includes('already')) {
                // Mostra banda rossa sotto la domanda al posto dell'alert
                showAlreadyPresentError(questionId);
            } else {
                showToast(errorMsg, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        showToast('Errore durante l\'aggiunta della domanda', 'error');
    });
}

// Mostra una banda rossa nel messaggio quando la domanda è già presente nel set (Modifica Set / Crea Partita)
function showAlreadyPresentError(questionId) {
    const messageDiv = document.getElementById('edit-set-message');
    if (!messageDiv) return;

    messageDiv.innerHTML = '<div class="duplicate-warning">⚠ Questa domanda è già presente nel set!</div>';

    // Rimuovi il messaggio dopo 3 secondi
    setTimeout(() => {
        messageDiv.innerHTML = '';
    }, 3000);
}

// Mostra una banda rossa nel messaggio quando la domanda è già presente nel set (Aggiungi Set)
function showAlreadyPresentErrorForNewSet(questionId) {
    const messageDiv = document.getElementById('add-set-message');
    if (!messageDiv) return;

    messageDiv.innerHTML = '<div class="duplicate-warning">⚠ Questa domanda è già presente nel set!</div>';

    // Rimuovi il messaggio dopo 3 secondi
    setTimeout(() => {
        messageDiv.innerHTML = '';
    }, 3000);
}

// Aggiunge una domanda al nuovo set (prima della creazione)
function addQuestionToNewSet(questionId) {
    // Ottieni le domande già aggiunte dal localStorage
    let addedQuestions = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

    // Aggiungi il nuovo ID se non esiste già
    if (!addedQuestions.includes(questionId)) {
        addedQuestions.push(questionId);
        localStorage.setItem('addSetQuestions', JSON.stringify(addedQuestions));

        // Ricarica la lista e la ricerca per riflettere i cambiamenti
        loadNewSetQuestions();
        setTimeout(() => {
            highlightNewSetQuestion(questionId);
        }, 100);
        highlightSearchResultForNewSet(questionId);
        searchAvailableQuestionsForNewSet();
    } else {
        // Mostra banda rossa se la domanda è già presente
        showAlreadyPresentErrorForNewSet(questionId);
    }
}

// Carica le domande associate al nuovo set (dal localStorage)
function loadNewSetQuestions(highlightId = null) {
    const container = document.getElementById('add-set-associated-questions');
    const addedQuestionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

    if (addedQuestionIds.length === 0) {
        container.innerHTML = '<p class="text-muted-center">Nessuna domanda associata</p>';
        return;
    }

    // Fetch tutte le domande per ottenere i dettagli
    api('get_questions')
        .then(data => {
            if (data.success && data.questions) {
                const associatedQuestions = data.questions.filter(q => {
                    const qId = parseInt(q.id);
                    const isAdded = addedQuestionIds.includes(qId) || addedQuestionIds.includes(q.id);
                    return isAdded;
                });

                let html = '<div class="questions-associated" id="new-set-questions-list">';
                const total = associatedQuestions.length;
                associatedQuestions.forEach((q, index) => {
                    const categoryBadge = q.category_name ? `<span class="badge-category"><span class="color-dot" style="background: ${q.color || '#6c757d'};"></span>${q.category_name}</span>` : '';
                    const typeMap = { 'multiple': '📋 Scelta multipla', 'truefalse': '✔️ Vero/Falso', 'clickfirst': '⚡ Clicca per primo' };
                    const typeBadge = q.question_type ? `<span class="badge-type">${typeMap[q.question_type] || q.question_type}</span>` : '';

                    html += `
                        <div class="question-item" data-question-id="${q.id}" draggable="true">
                            <div class="question-item-content">${q.question}</div>
                            <div class="question-item-center">${categoryBadge}</div>
                            <div class="question-item-center">${typeBadge}</div>
                            <div class="question-item-actions">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestionFromNewSet(${q.id})">
                                    Elimina
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;

                // Setup drag and drop per riordinamento
                setupDragAndDropForNewSet();

                // Se c'è un ID da evidenziare, fallo dopo che il DOM è aggiornato
                if (highlightId) {
                    setTimeout(() => {
                        highlightNewSetQuestion(highlightId);
                    }, 0);
                }
            }
        })
        .catch(error => {
            console.error('Errore nel caricamento domande:', error);
            container.innerHTML = '<p class="text-error-center">Errore nel caricamento</p>';
        });
}

// Rimuove una domanda dal nuovo set
function removeQuestionFromNewSet(questionId) {
    let addedQuestions = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');
    addedQuestions = addedQuestions.filter(id => id !== questionId);
    localStorage.setItem('addSetQuestions', JSON.stringify(addedQuestions));

    loadNewSetQuestions();
    searchAvailableQuestionsForNewSet();
}

// Evidenzia una domanda nel nuovo set
function highlightNewSetQuestion(questionId) {
    const container = document.getElementById('new-set-questions-list');
    if (!container) return;

    const questionItem = container.querySelector(`[data-question-id="${questionId}"]`);
    if (questionItem) {
        questionItem.classList.add('highlight');
        setTimeout(() => {
            questionItem.classList.remove('highlight');
        }, 1500);
    }
}

// Evidenzia una domanda nella ricerca del nuovo set
function highlightSearchResultForNewSet(questionId) {
    const container = document.getElementById('add-available-questions');
    if (!container) return;

    const questionItem = container.querySelector(`[data-question-id="${questionId}"]`);
    if (questionItem) {
        questionItem.classList.add('highlight');
        setTimeout(() => {
            questionItem.classList.remove('highlight');
        }, 1500);
    }
}

// Sposta una domanda su nel nuovo set (solo grafico + localStorage, niente DB)
function moveNewSetQuestionUp(index) {
    if (index === 0) return; // Non puoi spostare il primo elemento su

    const addedQuestionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');
    if (index <= 0 || index >= addedQuestionIds.length) return;

    // Scambia nel localStorage PRIMA
    [addedQuestionIds[index - 1], addedQuestionIds[index]] = [addedQuestionIds[index], addedQuestionIds[index - 1]];
    localStorage.setItem('addSetQuestions', JSON.stringify(addedQuestionIds));

    // Ottieni l'ID della domanda spostata
    const questionId = addedQuestionIds[index - 1];

    // Ricarica la lista (come fa Modifica Set)
    loadNewSetQuestions();

    // Ricarica anche la ricerca per aggiornare le domande disponibili
    searchAvailableQuestionsForNewSet();

    // Evidenzia la domanda mossa dopo un breve delay
    setTimeout(() => {
        highlightNewSetQuestion(questionId);
    }, 100);
}

// Sposta una domanda giù nel nuovo set (solo grafico + localStorage, niente DB)
function moveNewSetQuestionDown(index) {
    const addedQuestionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');
    if (index < 0 || index >= addedQuestionIds.length - 1) return;

    // Scambia nel localStorage PRIMA
    [addedQuestionIds[index], addedQuestionIds[index + 1]] = [addedQuestionIds[index + 1], addedQuestionIds[index]];
    localStorage.setItem('addSetQuestions', JSON.stringify(addedQuestionIds));

    // Ottieni l'ID della domanda spostata
    const questionId = addedQuestionIds[index];

    // Ricarica la lista (come fa Modifica Set)
    loadNewSetQuestions();

    // Ricarica anche la ricerca per aggiornare le domande disponibili
    searchAvailableQuestionsForNewSet();

    // Evidenzia la domanda mossa dopo un breve delay
    setTimeout(() => {
        highlightNewSetQuestion(questionId);
    }, 100);
}

// Apri modal per aggiungere domanda sotto una specifica
function openAddBelowModal(setId, positionIndex) {
    // Salva il setId e l'indice in variabili globali per usarle successivamente
    window.selectedSetId = setId;
    window.selectedPositionIndex = positionIndex;

    // Mostra il modal di ricerca
    const modal = document.getElementById('modal-edit-set');
    const container = document.getElementById('edit-available-questions');
    // Resetta la ricerca
    document.getElementById('edit-search-questions').value = '';
    document.getElementById('edit-category-filter').value = '';
}

// Aggiunge una domanda al set in una posizione specifica
function addQuestionBelowInSet(setId, questionId, positionIndex) {
    api('add_question_to_set_at_position', {
        set_id: setId,
        question_id: questionId,
        position: positionIndex + 1
    })
    .then(data => {
        if (data.success) {
            loadSetQuestions(setId);
            searchAvailableQuestions();
        } else {
            showToast(data.error || 'Non è stato possibile aggiungere la domanda', 'error');
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        showToast('Errore durante l\'aggiunta della domanda', 'error');
    });
}

function startGameWithSet(setId) {
    // Usa l'API endpoint per reindirizzare a game-room.php mantenendo la sessione
    console.log('startGameWithSet called with setId:', setId);
    const gameRoomUrl = `/public/game-room.php?set_id=${setId}`;
    console.log('Redirecting to:', gameRoomUrl);

    // Reindirizza direttamente - il cookie è stato preservato dalle fetch precedenti
    window.location.href = gameRoomUrl;
}

// Avvia un gioco dal modal "Crea Partita"
function startGameFromModal() {
    const currentName = document.getElementById('edit-set-name').value || 'set temporaneo';
    const currentDescription = document.getElementById('edit-set-description').value || '';
    const messageDiv = document.getElementById('edit-set-message');

    // Conta le domande nel set
    const questionItems = document.querySelectorAll('#edit-set-questions .question-item');
    if (questionItems.length === 0) {
        messageDiv.innerHTML = '<div class="alert-error">✗ Il set deve contenere almeno una domanda</div>';
        return;
    }

    // Crea il set nel database
    api('add_questionset', {
        set_name: currentName,
        set_description: currentDescription
    })
    .then(data => {
        if (data.success && data.set_id) {
            // Ottieni le IDs delle domande dal localStorage
            const questionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

            // Aggiungi tutte le domande al set
            Promise.all(questionIds.map(questionId =>
                api('add_question_to_set', {
                    set_id: data.set_id,
                    question_id: questionId
                })
            )).then(results => {
                const allSuccess = results.every(r => r.success);
                if (allSuccess) {
                    messageDiv.innerHTML = '<div class="alert-success">✓ Partita avviata!</div>';
                    localStorage.removeItem('addSetQuestions');
                    setTimeout(() => {
                        // Avvia il gioco
                        startGameWithSet(data.set_id);
                    }, 500);
                } else {
                    messageDiv.innerHTML = '<div class="alert-error">✗ Errore nell\'aggiunta delle domande</div>';
                }
            }).catch(error => {
                console.error('Errore:', error);
                messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante l\'avvio della partita</div>';
            });
        } else {
            messageDiv.innerHTML = '<div class="alert-error">✗ Errore nella creazione del set</div>';
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante l\'avvio della partita</div>';
    });
}

function createNewGame() {
    // Marca come nuovo set (non ancora salvato nel DB)
    isNewSet = true;
    questionsToRemove = []; // Resetta array eliminazioni

    // Inizializza il form senza creare il set nel database
    document.getElementById('edit-set-id').value = '';

    // Genera nome automatico con timestamp
    const timestamp = new Date().getTime();
    document.getElementById('edit-set-name').value = '#Temporary set ' + timestamp;
    document.getElementById('edit-set-description').value = '';

    // Nascondi i campi nome e descrizione per "Crea Partita"
    document.querySelector('#edit-set-name').parentElement.style.display = 'none';
    document.querySelector('#edit-set-description').parentElement.style.display = 'none';

    editSetOriginalData = {
        id: null,
        name: '',
        description: ''
    };

    document.querySelector('#modal-edit-set .modal-header h2').textContent = '🎮 Crea Partita';
    document.getElementById('edit-set-message').innerHTML = '';

    // Aggiorna il bottone per mostrare "Avvia partita"
    const btnSaveSetChanges = document.getElementById('btn-save-set-changes');
    if (btnSaveSetChanges) {
        btnSaveSetChanges.textContent = '▶ Avvia partita';
        btnSaveSetChanges.className = 'btn btn-primary'; // Cambia colore a blu
    }

    // Resetta il localStorage delle domande (come Aggiungi Set)
    localStorage.removeItem('addSetQuestions');

    // Pulisci le domande associate
    document.getElementById('edit-set-questions').innerHTML = '<p class="text-muted-center">Nessuna domanda associata</p>';

    // Resetta campi di ricerca
    document.getElementById('edit-search-questions').value = '';
    document.getElementById('edit-category-filter').value = '';
    // Mostra il messaggio iniziale come "Aggiungi Set"
    document.getElementById('edit-available-questions').innerHTML = '<p class="text-muted-center">Ricerca domande...</p>';

    document.getElementById('modal-edit-set').style.display = 'flex';

    // Carica categorie nel filtro
    loadCategoriesForFilter();

    // Carica solo le domande associate dal localStorage (che sarà vuoto)
    setTimeout(() => {
        loadGameQuestions();
    }, 100);
}
</script>
