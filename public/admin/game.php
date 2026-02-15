<!-- Tab: Gestione Set Domande -->
<div class="admin-section">
    <div class="tab-header-with-button">
        <h2>🎮 Gestione Set Domande</h2>
        <div class="button-group">
            <button class="btn btn-success" id="btn-new-set">+ Aggiungi Set</button>
            <button class="btn btn-primary" id="btn-create-game" onclick="createNewGame()">🎮 Crea Partita</button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <form method="POST" action="admin.php" class="search-form">
            <input type="hidden" name="sets_page" id="sets_page" value="<?php echo (int)($_SESSION['sets_page'] ?? 1); ?>">
            <input type="text" name="set_search_query" id="search-sets" placeholder="Cerca set..." value="<?php echo htmlspecialchars($_POST['set_search_query'] ?? ''); ?>">
            <select name="set_search_type" id="search-type" class="search-filter">
                <option value="starts_with" <?php echo ($_POST['set_search_type'] ?? '') === 'starts_with' ? 'selected' : ''; ?>>Inizia con</option>
                <option value="contains" <?php echo ($_POST['set_search_type'] ?? 'contains') === 'contains' ? 'selected' : ''; ?>>Contiene</option>
                <option value="ends_with" <?php echo ($_POST['set_search_type'] ?? '') === 'ends_with' ? 'selected' : ''; ?>>Finisce con</option>
                <option value="exact" <?php echo ($_POST['set_search_type'] ?? '') === 'exact' ? 'selected' : ''; ?>>Esattamente</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Cerca</button>
            <?php if (!empty($_POST['set_search_query'])): ?>
                <button type="button" class="btn btn-secondary" id="clear-sets-search">✖ Cancella</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table View -->
    <div class="settings-group">
        <table class="questions-table">
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

<!-- Modal: Modifica Set -->
<div id="modal-edit-set" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 1400px; width: 99%;">
        <div class="modal-header">
            <h2>Modifica Set</h2>
            <button type="button" class="modal-close" id="btn-close-edit-set" onclick="document.getElementById('modal-edit-set').style.display='none'; cleanupEditSetModal();">✕</button>
        </div>
        <div class="settings-group" style="max-height: 80vh;">
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
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <input type="text" id="edit-search-questions" placeholder="Cerca domande..." style="flex: 2; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <select id="edit-search-type" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                            <option value="starts_with">Inizia con</option>
                            <option value="contains">Contiene</option>
                            <option value="ends_with">Finisce con</option>
                            <option value="exact">Esattamente</option>
                        </select>
                        <select id="edit-category-filter" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                            <option value="">🌐 Tutte Categorie</option>
                        </select>
                        <button type="button" class="btn btn-info" style="flex-shrink: 0;" onclick="searchAvailableQuestions()">Cerca</button>
                    </div>
                    <div id="edit-available-questions" class="questions-list" style="max-height: 350px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #fff;">
                        <p style="text-align: center; color: #999;">Inserisci un termine di ricerca e clicca Cerca</p>
                    </div>
                </div>

                <hr style="margin: 20px 0; border: none; border-top: 2px solid #ddd;">

                <div class="form-group">
                    <label for="edit-set-questions">Domande Associate</label>
                    <div id="edit-set-questions" class="questions-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 4px; background-color: #f9f9f9;">
                        <p style="text-align: center; color: #999;">Caricamento domande...</p>
                    </div>
                </div>

                <div id="edit-set-message" class="form-message"></div>
            </form>
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <button type="button" class="btn btn-success" id="btn-save-set-changes" style="min-width: 200px;">✓ Salva Modifiche</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Aggiungi Set -->
<div id="modal-add-set" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 1400px; width: 99%;">
        <div class="modal-header">
            <h2>Aggiungi Set</h2>
            <button type="button" class="modal-close" id="btn-close-add-set" onclick="document.getElementById('modal-add-set').style.display='none'; cleanupAddSetModal();">✕</button>
        </div>
        <div class="settings-group" style="max-height: 80vh;">
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
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <input type="text" id="add-search-questions" placeholder="Cerca domande..." style="flex: 2; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <select id="add-search-type" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                            <option value="starts_with">Inizia con</option>
                            <option value="contains">Contiene</option>
                            <option value="ends_with">Finisce con</option>
                            <option value="exact">Esattamente</option>
                        </select>
                        <select id="add-category-filter" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                            <option value="">🌐 Tutte Categorie</option>
                        </select>
                        <button type="button" class="btn btn-info" style="flex-shrink: 0;" onclick="searchAvailableQuestionsForNewSet()">Cerca</button>
                    </div>
                    <div id="add-available-questions" class="questions-list" style="max-height: 350px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #fff;">
                        <p style="text-align: center; color: #999;">Inserisci un termine di ricerca e clicca Cerca</p>
                    </div>
                </div>

                <hr style="margin: 20px 0; border: none; border-top: 2px solid #ddd;">

                <div class="form-group">
                    <label for="add-set-associated-questions">Domande Associate</label>
                    <div id="add-set-associated-questions" class="questions-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 4px; background-color: #f9f9f9;">
                        <p style="text-align: center; color: #999;">Nessuna domanda associata</p>
                    </div>
                </div>

                <div id="add-set-message" class="form-message"></div>
            </form>
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <button type="button" class="btn btn-success" id="btn-save-add-set" onclick="saveAddSetChanges()" style="min-width: 200px;">✓ Salva Set</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Eliminazione Set -->
<div id="modal-delete-set" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
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
// PAGINAZIONE SET
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
        // Se non ci sono set, mostra 'Nessun set disponibile' invece di 'Set 0-0 di 0'
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


// Variabili globali per tracciare i valori originali del set in modifica
let editSetOriginalData = {
    id: null,
    name: null,
    description: null
};

// Flag per tracciare se stiamo creando un nuovo set (non ancora salvato)
let isNewSet = false;

// Traccia le domande da eliminare dal set (solo al salvataggio finale)
let questionsToRemove = [];

// Funzione helper per parsare JSON da API con error handling
async function safeFetchJSON(url, options = {}) {
    try {
        // Assicura che i cookie di sessione vengano inviati
        if (!options.credentials) {
            options.credentials = 'include';
        }

        const response = await fetch(url, options);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const text = await response.text();

        // Verifica che la risposta sia JSON
        if (!text || !text.trim().startsWith('{')) {
            console.error('Non-JSON response from', url, ':', text.substring(0, 200));
            throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
        }

        try {
            return JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error from', url, ':', parseError);
            console.error('Response text:', text.substring(0, 500));
            throw new Error('Invalid JSON response');
        }
    } catch (error) {
        console.error('safeFetchJSON error:', error);
        throw error;
    }
}

// Chiude il modal "Modifica Set" senza salvare
function closeEditSetModal() {
    // Semplice chiusura del modal senza salvataggio
    // Il salvataggio avverrà solo cliccando "Salva Modifiche"
    document.getElementById('modal-edit-set').style.display = 'none';
    questionsToRemove = []; // Resetta le domande da eliminare
}

// Salva i cambiamenti al set quando clicchi il bottone "Salva Modifiche"
function saveSetChanges() {
    const setId = document.getElementById('edit-set-id').value;
    const currentName = document.getElementById('edit-set-name').value.trim();
    const currentDescription = document.getElementById('edit-set-description').value.trim();
    const messageDiv = document.getElementById('edit-set-message');

    // Se è un nuovo set, salva sempre
    if (isNewSet) {
        if (!currentName) {
            messageDiv.innerHTML = '<div class="alert-error">✗ Il nome del set è obbligatorio</div>';
            return;
        }

        // Conta le domande nel set
        const questionItems = document.querySelectorAll('#edit-set-questions .question-item');
        if (questionItems.length === 0) {
            messageDiv.innerHTML = '<div class="alert-error">✗ Il set deve contenere almeno una domanda</div>';
            return;
        }

        // Se è un nuovo set senza ID, crea il set nel database prima
        if (!setId) {
            safeFetchJSON('/src/api/api.php?endpoint=add_questionset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    set_name: currentName,
                    set_description: currentDescription
                })
            })
            .then(data => {
                if (data.success && data.set_id) {
                    // Aggiorna l'ID del set nel form
                    document.getElementById('edit-set-id').value = data.set_id;

                    // Ora aggiungi le domande associate al set appena creato
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
        // Set esistente: salva sempre
        // Prima elimina le domande marcate per l'eliminazione
        if (questionsToRemove.length > 0) {
            // Elimina tutte le domande nel tracking array
            Promise.all(questionsToRemove.map(questionId =>
                fetch('/src/api/api.php?endpoint=remove_question_from_set', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        set_id: setId,
                        question_id: questionId
                    })
                }).then(response => response.json())
            )).then(results => {
                // Verifica che tutte le eliminazioni siano riuscite
                const allSuccess = results.every(r => r.success);
                if (allSuccess) {
                    // Ora salva i metadati via API
                    saveSetMetadata(setId, currentName, currentDescription, messageDiv);
                } else {
                    messageDiv.innerHTML = '<div class="alert-error">✗ Errore nell\'eliminazione delle domande</div>';
                }
            }).catch(error => {
                console.error('Errore:', error);
                messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante l\'eliminazione delle domande</div>';
            });
        } else {
            // Nessuna domanda da eliminare, salva solo i metadati
            saveSetMetadata(setId, currentName, currentDescription, messageDiv);
        }
    }
}

// Funzione helper per salvare i metadati del set
function saveSetMetadata(setId, currentName, currentDescription, messageDiv) {
        // Salva i metadati via API
        fetch('/src/api/api.php?endpoint=update_questionset_metadata', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                set_id: setId,
                set_name: currentName,
                set_description: currentDescription
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.innerHTML = '<div class="alert-success">✓ Modifiche salvate!</div>';
                questionsToRemove = []; // Resetta l'array
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

// Funzione helper per aggiungere domande al nuovo set appena creato
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

    // Aggiungi tutte le domande al set
    Promise.all(questionIds.map((questionId, index) =>
        fetch('/src/api/api.php?endpoint=add_question_to_set', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                set_id: setId,
                question_id: questionId
            })
        }).then(response => response.json())
    )).then(results => {
        const allSuccess = results.every(r => r.success);
        if (allSuccess) {
            messageDiv.innerHTML = '<div class="alert-success">✓ Set salvato con successo!</div>';
            // Resetta il localStorage per la prossima volta che si clicca "Crea Partita"
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

// Pulisce i campi di ricerca del modal Modifica Set
function cleanupEditSetModal() {
    document.getElementById('edit-search-questions').value = '';
    document.getElementById('edit-category-filter').value = '';
    document.getElementById('edit-available-questions').innerHTML = '<p style="text-align: center; color: #999;">Ricerca domande...</p>';

    // Se è "Crea Partita", resetta anche il localStorage
    const modalTitle = document.querySelector('#modal-edit-set .modal-header h2').textContent;
    if (modalTitle.includes('Crea Partita')) {
        localStorage.removeItem('addSetQuestions');
    }
}

// Pulisce i campi di ricerca del modal Aggiungi Set
function cleanupAddSetModal() {
    document.getElementById('add-search-questions').value = '';
    document.getElementById('add-category-filter').value = '';
    document.getElementById('add-available-questions').innerHTML = '<p style="text-align: center; color: #999;">Inserisci un termine di ricerca e clicca Cerca</p>';
    localStorage.removeItem('addSetQuestions');
}

// Salva i cambiamenti al nuovo set
function saveAddSetChanges() {
    const currentName = document.getElementById('add-set-name').value.trim();
    const currentDescription = document.getElementById('add-set-description').value.trim();
    const messageDiv = document.getElementById('add-set-message');

    if (!currentName) {
        messageDiv.innerHTML = '<div class="alert-error">✗ Il nome del set è obbligatorio</div>';
        return;
    }

    // Conta le domande nel set
    const questionItems = document.querySelectorAll('#add-set-associated-questions .question-item');
    if (questionItems.length === 0) {
        messageDiv.innerHTML = '<div class="alert-error">✗ Il set deve contenere almeno una domanda</div>';
        return;
    }

    // Crea il set nel database
    safeFetchJSON('/src/api/api.php?endpoint=add_questionset', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            set_name: currentName,
            set_description: currentDescription
        })
    })
    .then(data => {
        if (data.success && data.set_id) {
            // Ottieni le IDs delle domande dal localStorage
            const questionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

            // Aggiungi tutte le domande al set
            Promise.all(questionIds.map(questionId =>
                fetch('/src/api/api.php?endpoint=add_question_to_set', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        set_id: data.set_id,
                        question_id: questionId
                    })
                }).then(response => response.json())
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

// Chiude il modal "Aggiungi Set" e pulisce il localStorage
// ============================================================================
// GESTIONE MODALI E AZIONI SET
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Event listener per il bottone "Chiudi/Salva Set"
    const btnCloseEditSet = document.getElementById('btn-close-edit-set');
    if (btnCloseEditSet) {
        btnCloseEditSet.addEventListener('click', function() {
            closeEditSetModal();
            cleanupEditSetModal();
        });
    }

    // Event listener per il bottone "Chiudi" del modal Aggiungi Set
    const btnCloseAddSet = document.getElementById('btn-close-add-set');
    if (btnCloseAddSet) {
        btnCloseAddSet.addEventListener('click', function() {
            document.getElementById('modal-add-set').style.display = 'none';
            cleanupAddSetModal();
        });
    }

    // Event listener per il bottone "Salva Modifiche" / "Avvia partita"
    const btnSaveSetChanges = document.getElementById('btn-save-set-changes');
    if (btnSaveSetChanges) {
        btnSaveSetChanges.addEventListener('click', function() {
            const modalTitle = document.querySelector('#modal-edit-set .modal-header h2').textContent;

            if (modalTitle.includes('Crea Partita')) {
                // Per "Crea Partita", avvia il gioco invece di salvare
                startGameFromModal();
            } else {
                // Per "Modifica Set", salva le modifiche
                saveSetChanges();
            }
        });
    }

    // Gestione popup Nuovo Set
    const btnNewSet = document.getElementById('btn-new-set');
    const modalAddSet = document.getElementById('modal-add-set');

    if (btnNewSet) {
        btnNewSet.addEventListener('click', () => {
            // Resetta il form del nuovo set
            document.getElementById('add-set-name').value = '';
            document.getElementById('add-set-description').value = '';
            document.getElementById('add-set-message').innerHTML = '';
            document.getElementById('add-set-associated-questions').innerHTML = '<p style="text-align: center; color: #999;">Nessuna domanda associata</p>';

            // Resetta il localStorage delle domande
            localStorage.removeItem('addSetQuestions');

            // Apri il modal
            document.getElementById('modal-add-set').style.display = 'flex';

            // Carica categorie nel filtro
            loadCategoriesForAddSetFilter();
        });
    }

    // Gestione click edit, delete, start-game
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-start-game')) {
            const setId = e.target.closest('.btn-start-game').getAttribute('data-set-id');
            startGameWithSet(setId);
            return;
        }

        if (e.target.closest('.btn-edit-set')) {
            const setId = e.target.closest('.btn-edit-set').getAttribute('data-set-id');

            // Carica dati set tramite API
            fetch(`/src/api/api.php?endpoint=get_questionset&id=${setId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.set) {
                        const s = data.set;
                        document.getElementById('edit-set-id').value = s.id;
                        document.getElementById('edit-set-name').value = s.set_name;
                        document.getElementById('edit-set-description').value = s.set_description || '';

                        // Mostra i campi nome e descrizione per "Modifica Set"
                        document.querySelector('#edit-set-name').parentElement.style.display = 'block';
                        document.querySelector('#edit-set-description').parentElement.style.display = 'block';

                        editSetOriginalData = {
                            id: s.id,
                            name: s.set_name,
                            description: s.set_description || ''
                        };

                        document.querySelector('.modal-header h2').textContent = 'Modifica Set';

                        // Ripristina il bottone al testo originale "Salva Modifiche"
                        const btnSaveSetChanges = document.getElementById('btn-save-set-changes');
                        if (btnSaveSetChanges) {
                            btnSaveSetChanges.textContent = '✓ Salva Modifiche';
                            btnSaveSetChanges.className = 'btn btn-success'; // Cambia colore a verde
                        }

                        // Reset flag nuovo set
                        isNewSet = false;

                        document.getElementById('edit-set-message').innerHTML = '';
                        document.getElementById('modal-edit-set').style.display = 'flex';

                        // Carica categorie nel filtro
                        loadCategoriesForFilter();

                        // Carica domande associate al set
                        loadSetQuestions(s.id);
                    } else {
                        alert('Errore nel caricamento del set');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Errore nel caricamento del set');
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
// FORM SUBMISSION
// ============================================================================


function submitEditSet(event) {
    event.preventDefault();

    const form = document.getElementById('edit-set-form');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('edit-set-message');

    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert-success">✓ Set aggiornato con successo!</div>';
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            messageDiv.innerHTML = `<div class="alert-error">✗ ${data.error || 'Errore'}</div>`;
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante l\'aggiornamento del set</div>';
    });
}

// Carica le categorie nel dropdown dei filtri
function loadCategoriesForFilter() {
    const select = document.getElementById('edit-category-filter');
    if (!select || select.options.length > 1) {
        return; // Già caricate
    }

    fetch(`/src/api/api.php?endpoint=get_categories`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error in get_categories:', e, 'Response:', text);
                    throw e;
                }
            });
        })
        .then(data => {
            if (data.success && data.categories) {
                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.category_name;
                    select.appendChild(option);
                });

                // Aggiungi event listener per trigghernare la ricerca al cambio di categoria
                select.addEventListener('change', function() {
                    searchAvailableQuestions();
                });
            }
        })
        .catch(error => console.error('Errore nel caricamento categorie:', error));
}

// Carica categorie per il filtro nel modal di aggiunta set
function loadCategoriesForAddSetFilter() {
    const select = document.getElementById('add-category-filter');
    if (!select || select.options.length > 1) {
        return; // Già caricate
    }

    fetch(`/src/api/api.php?endpoint=get_categories`)
        .then(response => response.json())
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

// Carica domande associate a un set
function loadSetQuestions(setId) {
    const containerAssociated = document.getElementById('edit-set-questions');

    fetch(`/src/api/api.php?endpoint=get_set_questions&set_id=${setId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.questions && data.questions.length > 0) {
                let html = '<div class="questions-associated" id="questions-list-' + setId + '">';
                const total = data.questions.length;

                data.questions.forEach((q, index) => {
                    const categoryBadge = q.category_name ? `<span style="display: inline-block; background-color: ${q.color || '#6c757d'}; color: black; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; margin-left: 15px; font-weight: 600; border: 2px solid ${q.color || '#6c757d'}; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">${q.category_name}</span>` : '';

                    html += `
                        <div class="question-item" data-question-id="${q.id}" data-set-id="${setId}" draggable="true" style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; gap: 10px; background-color: #fff; cursor: move;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1;">
                                    ${q.question}${categoryBadge}
                                </div>
                            </div>
                            <div style="display: flex; gap: 5px; flex-shrink: 0;">
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
                containerAssociated.innerHTML = '<p style="text-align: center; color: #999;">Nessuna domanda associata</p>';
            }
        })
        .catch(error => {
            console.error('Errore nel caricamento domande:', error);
            containerAssociated.innerHTML = '<p style="text-align: center; color: #d9534f;">Errore nel caricamento</p>';
        });
}

// Carica domande per "Crea Partita" (dal localStorage)
function loadGameQuestions() {
    const containerAssociated = document.getElementById('edit-set-questions');
    const addedQuestionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

    if (addedQuestionIds.length === 0) {
        containerAssociated.innerHTML = '<p style="text-align: center; color: #999;">Nessuna domanda associata</p>';
        return;
    }

    // Fetch tutte le domande per ottenere i dettagli
    fetch(`/src/api/api.php?endpoint=get_questions`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.questions) {
                // Normalizza gli ID nel localStorage a numeri interi
                const normalizedIds = addedQuestionIds.map(id => parseInt(id));

                const associatedQuestions = data.questions.filter(q => {
                    const qId = parseInt(q.id);
                    return normalizedIds.includes(qId);
                });

                let html = '<div class="questions-associated" id="game-questions-list">';

                associatedQuestions.forEach((q, index) => {
                    const categoryBadge = q.category_name ? `<span style="display: inline-block; background-color: ${q.color || '#6c757d'}; color: black; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; margin-left: 15px; font-weight: 600; border: 2px solid ${q.color || '#6c757d'}; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">${q.category_name}</span>` : '';

                    html += `
                        <div class="question-item" data-question-id="${q.id}" draggable="true" style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; gap: 10px; background-color: #fff; cursor: move;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1;">
                                    ${q.question}${categoryBadge}
                                </div>
                            </div>
                            <div style="display: flex; gap: 5px; flex-shrink: 0;">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeGameQuestion(${q.id})">
                                    Elimina
                                </button>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                containerAssociated.innerHTML = html;

                // Setup drag and drop per riordinamento
                setupDragAndDropForGameQuestions();
            } else {
                containerAssociated.innerHTML = '<p style="text-align: center; color: #999;">Nessuna domanda associata</p>';
            }
        })
        .catch(error => {
            console.error('Errore nel caricamento domande:', error);
            containerAssociated.innerHTML = '<p style="text-align: center; color: #d9534f;">Errore nel caricamento</p>';
        });
}

// Setup drag and drop per "Crea Partita"
function setupDragAndDropForGameQuestions() {
    const container = document.getElementById('game-questions-list');
    if (!container) return;

    let draggedElement = null;
    let dragStarted = false;

    // Usa event delegation sul container
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
        // Rimuovi i border da tutti gli elementi
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

            // Ripulisci l'opacity immediatamente
            if (draggedElement) {
                draggedElement.style.opacity = '1';
            }
            // Rimuovi i border da tutti gli elementi
            const items = container.querySelectorAll('[draggable="true"]');
            items.forEach(item => {
                item.style.borderTop = 'none';
            });

            // Inserisci l'elemento trascinato prima dell'elemento target
            const allItems = Array.from(container.querySelectorAll('[draggable="true"]'));
            const draggedIndex = allItems.indexOf(draggedElement);
            const targetIndex = allItems.indexOf(e.target);

            if (draggedIndex >= 0 && targetIndex >= 0) {
                if (draggedIndex < targetIndex) {
                    // Trascini verso il basso: inserisci dopo il target
                    if (e.target.nextSibling) {
                        e.target.parentNode.insertBefore(draggedElement, e.target.nextSibling);
                    } else {
                        e.target.parentNode.appendChild(draggedElement);
                    }
                } else {
                    // Trascini verso l'alto: inserisci prima del target
                    e.target.parentNode.insertBefore(draggedElement, e.target);
                }

                // Aggiorna l'ordine nel localStorage
                updateGameQuestionOrderInLocalStorage();
            }
        }

        dragStarted = false;
        draggedElement = null;
    }, false);
}

// Aggiorna l'ordine delle domande nel localStorage per "Crea Partita"
function updateGameQuestionOrderInLocalStorage() {
    const container = document.getElementById('game-questions-list');
    if (!container) return;

    const items = container.querySelectorAll('[draggable="true"]');
    const questionIds = Array.from(items).map(item => parseInt(item.getAttribute('data-question-id')));

    // Salva l'ordine nel localStorage
    localStorage.setItem('addSetQuestions', JSON.stringify(questionIds));
}

// Aggiunge una domanda a "Crea Partita" (localStorage)
function addQuestionToGameSet(questionId) {
    // Ottieni le domande già aggiunte dal localStorage
    let addedQuestions = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

    // Aggiungi il nuovo ID se non esiste già
    if (!addedQuestions.includes(questionId)) {
        addedQuestions.push(questionId);
        localStorage.setItem('addSetQuestions', JSON.stringify(addedQuestions));

        // Ricarica la lista e la ricerca per riflettere i cambiamenti
        loadGameQuestions();
        setTimeout(() => {
            highlightGameQuestion(questionId);
        }, 100);
        highlightSearchResultForGameSet(questionId);
        searchAvailableQuestions();
    } else {
        // Mostra banda rossa se la domanda è già presente
        showAlreadyPresentError(questionId);
    }
}

// Rimuove una domanda da "Crea Partita"
function removeGameQuestion(questionId) {
    if (confirm('Vuoi eliminare questa domanda dal set?')) {
        let addedQuestions = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');
        addedQuestions = addedQuestions.filter(id => id !== questionId);
        localStorage.setItem('addSetQuestions', JSON.stringify(addedQuestions));

        loadGameQuestions();
        searchAvailableQuestions();
    }
}

// Evidenzia una domanda in "Crea Partita"
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

// Evidenzia una domanda nella ricerca per "Crea Partita"
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

// Setup drag and drop per riordinamento domande
function setupDragAndDrop(setId) {
    const container = document.getElementById('questions-list-' + setId);
    if (!container) return;

    let draggedElement = null;
    let dragStarted = false;

    // Usa event delegation sul container
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
        // Rimuovi i border da tutti gli elementi
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

            // Ripulisci l'opacity immediatamente
            if (draggedElement) {
                draggedElement.style.opacity = '1';
            }
            // Rimuovi i border da tutti gli elementi
            const items = container.querySelectorAll('[draggable="true"]');
            items.forEach(item => {
                item.style.borderTop = 'none';
            });

            // Inserisci l'elemento trascinato prima dell'elemento target
            // Se lo trascini giù, inserisci dopo; se lo trascini su, inserisci prima
            const allItems = Array.from(container.querySelectorAll('[draggable="true"]'));
            const draggedIndex = allItems.indexOf(draggedElement);
            const targetIndex = allItems.indexOf(e.target);

            if (draggedIndex >= 0 && targetIndex >= 0) {
                if (draggedIndex < targetIndex) {
                    // Trascini verso il basso: inserisci dopo il target
                    if (e.target.nextSibling) {
                        e.target.parentNode.insertBefore(draggedElement, e.target.nextSibling);
                    } else {
                        e.target.parentNode.appendChild(draggedElement);
                    }
                } else {
                    // Trascini verso l'alto: inserisci prima del target
                    e.target.parentNode.insertBefore(draggedElement, e.target);
                }

                // Aggiorna l'ordine nel database
                updateOrderInDatabase(setId);
            }
        }

        dragStarted = false;
        draggedElement = null;
    }, false);
}

// Aggiorna l'ordine nel database
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

    fetch('/src/api/api.php?endpoint=update_question_order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            set_id: setId,
            questions: questionOrder
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Errore HTTP: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            console.error('Errore nell\'aggiornamento ordine:', data.error);
            // Ricarica solo dopo un ritardo per evitare race condition
            setTimeout(() => {
                loadSetQuestions(setId);
            }, 300);
        }
    })
    .catch(error => {
        console.error('Errore nel salvataggio ordine:', error);
        // Ricarica con ritardo per evitare race condition
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

    // Usa event delegation sul container
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
        // Rimuovi i border da tutti gli elementi
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

            // Ripulisci l'opacity immediatamente
            if (draggedElement) {
                draggedElement.style.opacity = '1';
            }
            // Rimuovi i border da tutti gli elementi
            const items = container.querySelectorAll('[draggable="true"]');
            items.forEach(item => {
                item.style.borderTop = 'none';
            });

            // Inserisci l'elemento trascinato prima dell'elemento target
            const allItems = Array.from(container.querySelectorAll('[draggable="true"]'));
            const draggedIndex = allItems.indexOf(draggedElement);
            const targetIndex = allItems.indexOf(e.target);

            if (draggedIndex >= 0 && targetIndex >= 0) {
                if (draggedIndex < targetIndex) {
                    // Trascini verso il basso: inserisci dopo il target
                    if (e.target.nextSibling) {
                        e.target.parentNode.insertBefore(draggedElement, e.target.nextSibling);
                    } else {
                        e.target.parentNode.appendChild(draggedElement);
                    }
                } else {
                    // Trascini verso l'alto: inserisci prima del target
                    e.target.parentNode.insertBefore(draggedElement, e.target);
                }

                // Aggiorna l'ordine nel localStorage
                updateNewSetOrderInLocalStorage();
            }
        }

        dragStarted = false;
        draggedElement = null;
    }, false);
}

// Aggiorna l'ordine delle domande nel localStorage
function updateNewSetOrderInLocalStorage() {
    const container = document.getElementById('new-set-questions-list');
    if (!container) return;

    const items = container.querySelectorAll('[draggable="true"]');
    const questionIds = Array.from(items).map(item => parseInt(item.getAttribute('data-question-id')));

    // Salva l'ordine nel localStorage
    localStorage.setItem('addSetQuestions', JSON.stringify(questionIds));
}

// Rimuove una domanda da un set (traccia solo, salva al click di "Salva Modifiche")
function removeQuestionFromSet(setId, questionId) {
    if (confirm('Vuoi eliminare questa domanda dal set?')) {
        // Aggiungi alla lista di domande da eliminare
        if (!questionsToRemove.includes(questionId)) {
            questionsToRemove.push(questionId);
        }

        // Rimuove immediatamente l'elemento dalla lista
        const questionElement = document.querySelector(`[data-question-id="${questionId}"]`);
        if (questionElement) {
            questionElement.remove();
        }
    }
}

// Sposta una domanda su nella lista
function moveQuestionUp(setId, currentIndex) {
    if (currentIndex === 0) return; // Non puoi spostare il primo elemento su

    const container = document.getElementById('questions-list-' + setId);
    if (!container) return;

    const items = Array.from(container.querySelectorAll('.question-item'));
    if (currentIndex <= 0 || currentIndex >= items.length) return;

    // Scambia gli elementi nel DOM
    const currentItem = items[currentIndex];
    const previousItem = items[currentIndex - 1];
    previousItem.parentNode.insertBefore(currentItem, previousItem);

    // Ottieni l'ID della domanda spostata
    const questionId = parseInt(currentItem.getAttribute('data-question-id'));

    // Aggiorna l'ordine nel database
    updateOrderAfterMove(setId, questionId);
}

// Sposta una domanda giù nella lista
function moveQuestionDown(setId, currentIndex) {
    const container = document.getElementById('questions-list-' + setId);
    if (!container) return;

    const items = Array.from(container.querySelectorAll('.question-item'));
    if (currentIndex < 0 || currentIndex >= items.length - 1) return;

    // Scambia gli elementi nel DOM
    const currentItem = items[currentIndex];
    const nextItem = items[currentIndex + 1];
    currentItem.parentNode.insertBefore(nextItem, currentItem);

    // Ottieni l'ID della domanda spostata
    const questionId = parseInt(currentItem.getAttribute('data-question-id'));

    // Aggiorna l'ordine nel database
    updateOrderAfterMove(setId, questionId);
}

// Aggiorna l'ordine dopo lo spostamento con frecce
function updateOrderAfterMove(setId, questionId) {
    const container = document.getElementById('questions-list-' + setId);
    if (!container) return;

    const items = Array.from(container.querySelectorAll('.question-item'));
    const questionOrder = items.map((item, index) => ({
        question_id: parseInt(item.getAttribute('data-question-id')),
        order: index + 1
    }));

    fetch('/src/api/api.php?endpoint=update_question_order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            set_id: setId,
            questions: questionOrder
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Errore nell\'aggiornamento ordine:', data.error);
            alert('✗ Errore nell\'aggiornamento dell\'ordine');
            setTimeout(() => {
                loadSetQuestions(setId);
            }, 300);
        } else {
            // Ricarica gli elementi per rigenerare i bottoni freccia
            loadSetQuestions(setId);
            if (questionId) {
                setTimeout(() => {
                    highlightQuestion(questionId, setId);
                }, 100);
            }
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('✗ Errore durante l\'aggiornamento dell\'ordine');
        setTimeout(() => {
            loadSetQuestions(setId);
        }, 300);
    });
}

// Ricerca domande disponibili
function searchAvailableQuestions() {
    const searchTerm = document.getElementById('edit-search-questions').value.trim();
    const searchType = document.getElementById('edit-search-type').value;
    const categoryId = document.getElementById('edit-category-filter').value;
    const container = document.getElementById('edit-available-questions');
    const setId = document.getElementById('edit-set-id').value;

    container.innerHTML = '<p style="text-align: center; color: #999;">Ricerca in corso...</p>';

    // Fetch tutte le domande
    fetch(`/src/api/api.php?endpoint=get_questions`)
        .then(response => response.json())
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
                    container.innerHTML = '<p style="text-align: center; color: #999;">Nessuna domanda trovata</p>';
                    return;
                }

                // Se è un nuovo set (setId vuoto), non fare fetch delle domande associate
                if (!setId) {
                    // Per nuovi set, mostra tutte le domande filtrate
                    let html = '';
                    filtered.forEach(q => {
                        const categoryBadge = q.category_name ? `<span style="display: inline-block; background-color: ${q.color || '#6c757d'}; color: black; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; margin-left: 15px; font-weight: 600; border: 2px solid ${q.color || '#6c757d'}; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">${q.category_name}</span>` : '';

                        let questionText = q.question;
                        if (searchTerm && pattern) {
                            questionText = q.question.replace(pattern, '<mark style="background-color: #ffff00; font-weight: bold;">$&</mark>');
                        }

                        // Per "Crea Partita", aggiungi a localStorage; per nuovi set da salvare in DB, usa addQuestionToSet
                        const addFunctionName = isNewSet && document.querySelector('#modal-edit-set .modal-header h2').textContent.includes('Crea Partita') ? 'addQuestionToGameSet' : 'addQuestionToSet';
                        const functionCall = isNewSet && document.querySelector('#modal-edit-set .modal-header h2').textContent.includes('Crea Partita') ? `addQuestionToGameSet(${q.id})` : `addQuestionToSet(${setId}, ${q.id})`;

                        html += `
                            <div class="question-item" data-question-id="${q.id}" style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; gap: 10px; background-color: #fff;">
                                <div style="flex: 1;">
                                    ${questionText}${categoryBadge}
                                </div>
                                <button type="button" class="btn btn-success btn-sm" onclick="${functionCall}" title="Aggiungi al set">+ Aggiungi</button>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                    return;
                }

                // Carica domande già nel set per escluderle
                fetch(`/src/api/api.php?endpoint=get_set_questions&set_id=${setId}`)
                    .then(r => r.json())
                    .then(setData => {
                        const setQuestionIds = setData.questions ? setData.questions.map(q => q.id) : [];

                        // Escludi le domande già associate dal risultato della ricerca
                        const availableQuestions = filtered.filter(q => !setQuestionIds.includes(q.id));

                        if (availableQuestions.length === 0) {
                            container.innerHTML = '<p style="text-align: center; color: #999;">Tutte le domande trovate sono già associate</p>';
                            return;
                        }

                        let html = '';
                        availableQuestions.forEach(q => {
                            const categoryBadge = q.category_name ? `<span style="display: inline-block; background-color: ${q.color || '#6c757d'}; color: black; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; margin-left: 15px; font-weight: 600; border: 2px solid ${q.color || '#6c757d'}; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">${q.category_name}</span>` : '';

                            // Evidenzia il testo della ricerca nella domanda
                            let questionText = q.question;
                            if (searchTerm && pattern) {
                                questionText = q.question.replace(pattern, '<mark style="background-color: #ffff00; font-weight: bold;">$&</mark>');
                            }

                            html += `
                                <div class="question-item" data-question-id="${q.id}" style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; gap: 10px; background-color: #fff;">
                                    <div style="flex: 1;">
                                        ${questionText}${categoryBadge}
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm" onclick="addQuestionToSet(${setId}, ${q.id})" title="Aggiungi al set">+ Aggiungi</button>
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                    });
            } else {
                container.innerHTML = '<p style="text-align: center; color: #d9534f;">Errore nel caricamento domande</p>';
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            container.innerHTML = '<p style="text-align: center; color: #d9534f;">Errore durante la ricerca</p>';
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
    fetch(`/src/api/api.php?endpoint=get_questions`)
        .then(response => response.json())
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
                    container.innerHTML = '<p style="text-align: center; color: #999;">Nessuna domanda trovata</p>';
                    return;
                }

                // Ottieni le domande già associate al nuovo set (da localStorage)
                const addedQuestionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

                // Escludi le domande già associate dal risultato della ricerca
                const availableQuestions = filtered.filter(q => !addedQuestionIds.includes(q.id));

                if (availableQuestions.length === 0) {
                    container.innerHTML = '<p style="text-align: center; color: #999;">Tutte le domande trovate sono già associate</p>';
                    return;
                }

                let html = '';
                availableQuestions.forEach(q => {
                    const categoryBadge = q.category_name ? `<span style="display: inline-block; background-color: ${q.color || '#6c757d'}; color: black; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; margin-left: 15px; font-weight: 600; border: 2px solid ${q.color || '#6c757d'}; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">${q.category_name}</span>` : '';

                    // Evidenzia il testo della ricerca nella domanda
                    let questionText = q.question;
                    if (searchTerm && pattern) {
                        questionText = q.question.replace(pattern, '<mark style="background-color: #ffff00; font-weight: bold;">$&</mark>');
                    }

                    html += `
                        <div class="question-item" data-question-id="${q.id}" style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; gap: 10px; background-color: #fff;">
                            <div style="flex: 1;">
                                ${questionText}${categoryBadge}
                            </div>
                            <button type="button" class="btn btn-success btn-sm" onclick="addQuestionToNewSet(${q.id})" title="Aggiungi al set">+ Aggiungi</button>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p style="text-align: center; color: #d9534f;">Errore nel caricamento domande</p>';
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            container.innerHTML = '<p style="text-align: center; color: #d9534f;">Errore durante la ricerca</p>';
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
    fetch('/src/api/api.php?endpoint=add_question_to_set', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            set_id: setId,
            question_id: questionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Domanda aggiunta con successo!');
            // Ricarica sia le domande associate che la ricerca
            loadSetQuestions(setId);
            setTimeout(() => {
                highlightQuestion(questionId, setId);
            }, 100);
            // Evidenzia la domanda nella ricerca
            highlightSearchResult(questionId);
            searchAvailableQuestions();
        } else {
            // Estrai il messaggio di errore specifico
            const errorMsg = data.error || data.message || 'Non è stato possibile aggiungere la domanda';
            if (errorMsg.includes('already')) {
                // Mostra banda rossa sotto la domanda al posto dell'alert
                showAlreadyPresentError(questionId);
            } else {
                alert('Errore: ' + errorMsg);
            }
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Errore durante l\'aggiunta della domanda');
    });
}

// Mostra una banda rossa nel messaggio quando la domanda è già presente nel set (Modifica Set / Crea Partita)
function showAlreadyPresentError(questionId) {
    const messageDiv = document.getElementById('edit-set-message');
    if (!messageDiv) return;

    messageDiv.innerHTML = '<div style="background-color: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 10px; border-radius: 4px; font-weight: 500;">⚠ Questa domanda è già presente nel set!</div>';

    // Rimuovi il messaggio dopo 3 secondi
    setTimeout(() => {
        messageDiv.innerHTML = '';
    }, 3000);
}

// Mostra una banda rossa nel messaggio quando la domanda è già presente nel set (Aggiungi Set)
function showAlreadyPresentErrorForNewSet(questionId) {
    const messageDiv = document.getElementById('add-set-message');
    if (!messageDiv) return;

    messageDiv.innerHTML = '<div style="background-color: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 10px; border-radius: 4px; font-weight: 500;">⚠ Questa domanda è già presente nel set!</div>';

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
        container.innerHTML = '<p style="text-align: center; color: #999;">Nessuna domanda associata</p>';
        return;
    }

    // Fetch tutte le domande per ottenere i dettagli
    fetch(`/src/api/api.php?endpoint=get_questions`)
        .then(response => response.json())
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
                    const categoryBadge = q.category_name ? `<span style="display: inline-block; background-color: ${q.color || '#6c757d'}; color: black; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; margin-left: 15px; font-weight: 600; border: 2px solid ${q.color || '#6c757d'}; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">${q.category_name}</span>` : '';

                    html += `
                        <div class="question-item" data-question-id="${q.id}" draggable="true" style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; gap: 10px; background-color: #fff; cursor: move;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1;">
                                    ${q.question}${categoryBadge}
                                </div>
                            </div>
                            <div style="display: flex; gap: 5px; flex-shrink: 0;">
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
            container.innerHTML = '<p style="text-align: center; color: #d9534f;">Errore nel caricamento</p>';
        });
}

// Rimuove una domanda dal nuovo set
function removeQuestionFromNewSet(questionId) {
    if (confirm('Vuoi eliminare questa domanda dal set?')) {
        let addedQuestions = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');
        addedQuestions = addedQuestions.filter(id => id !== questionId);
        localStorage.setItem('addSetQuestions', JSON.stringify(addedQuestions));

        loadNewSetQuestions();
        searchAvailableQuestionsForNewSet();
    }
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
    fetch('/src/api/api.php?endpoint=add_question_to_set_at_position', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            set_id: setId,
            question_id: questionId,
            position: positionIndex + 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadSetQuestions(setId);
            searchAvailableQuestions();
        } else {
            alert('Errore: ' + (data.error || 'Non è stato possibile aggiungere la domanda'));
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Errore durante l\'aggiunta della domanda');
    });
}

function startGameWithSet(setId) {
    // Usa l'API endpoint per reindirizzare a game-room.php mantenendo la sessione
    console.log('startGameWithSet called with setId:', setId);
    const gameRoomUrl = `/public/game-room.php?set_id=${setId}`;
    console.log('Redirecting to:', gameRoomUrl);

    // Reindirizza direttamente - il cookie è stato preservato dalle fetch precedenti
    window.location.href = gameRoomUrl;
}// Avvia un gioco dal modal "Crea Partita"
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
    safeFetchJSON('/src/api/api.php?endpoint=add_questionset', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            set_name: currentName,
            set_description: currentDescription
        })
    })
    .then(data => {
        if (data.success && data.set_id) {
            // Ottieni le IDs delle domande dal localStorage
            const questionIds = JSON.parse(localStorage.getItem('addSetQuestions') || '[]');

            // Aggiungi tutte le domande al set
            Promise.all(questionIds.map(questionId =>
                fetch('/src/api/api.php?endpoint=add_question_to_set', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        set_id: data.set_id,
                        question_id: questionId
                    })
                }).then(response => response.json())
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
    document.getElementById('edit-set-name').value = 'set temporaneo ' + timestamp;
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
    document.getElementById('edit-set-questions').innerHTML = '<p style="text-align: center; color: #999;">Nessuna domanda associata</p>';

    // Resetta campi di ricerca
    document.getElementById('edit-search-questions').value = '';
    document.getElementById('edit-category-filter').value = '';
    // Mostra il messaggio iniziale come "Aggiungi Set"
    document.getElementById('edit-available-questions').innerHTML = '<p style="text-align: center; color: #999;">Ricerca domande...</p>';

    document.getElementById('modal-edit-set').style.display = 'flex';

    // Carica categorie nel filtro
    loadCategoriesForFilter();

    // Carica solo le domande associate dal localStorage (che sarà vuoto)
    setTimeout(() => {
        loadGameQuestions();
    }, 100);
}
</script>
