<!-- Tab: Gestione Set Domande -->
<div class="admin-section">
    <div class="tab-header-with-button">
        <h2>🎮 Gestione Set Domande</h2>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-success" id="btn-new-set">+ Aggiungi Set</button>
            <button class="btn btn-primary" id="btn-create-game" onclick="createNewGame()">🎮 Crea Partita</button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <form method="POST" action="admin.php" class="search-form">
            <input type="hidden" name="sets_page" id="sets_page" value="<?php echo (int)($_POST['sets_page'] ?? 1); ?>">
            <input type="text" name="set_search_query" id="search-sets" placeholder="Cerca set..." value="<?php echo htmlspecialchars($_POST['set_search_query'] ?? ''); ?>">
            <select name="set_search_type" id="search-type" class="search-filter">
                <option value="contains" <?php echo ($_POST['set_search_type'] ?? 'contains') === 'contains' ? 'selected' : ''; ?>>Contiene</option>
                <option value="starts_with" <?php echo ($_POST['set_search_type'] ?? '') === 'starts_with' ? 'selected' : ''; ?>>Inizia con</option>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questionSets as $set): ?>
                <tr data-set-id="<?php echo $set['id']; ?>">
                    <td><strong><?php echo htmlspecialchars($set['set_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($set['set_description'] ?? '-'); ?></td>
                    <td><span class="badge" style="background: #007bff; color: white; padding: 4px 8px; border-radius: 4px;"><?php echo $set['question_count'] ?? 0; ?></span></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($set['updated_at'])); ?></td>
                    <td>
                        <button class="btn-icon btn-warning btn-edit-set" data-set-id="<?php echo $set['id']; ?>" title="Modifica">✎</button>
                        <button class="btn-icon btn-danger btn-delete-set" data-set-id="<?php echo $set['id']; ?>" title="Elimina">×</button>
                        <button class="btn-icon btn-success btn-start-game" data-set-id="<?php echo $set['id']; ?>" title="Avvia Partita">▶️</button>
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

<!-- Modal: Aggiungi Set -->
<div id="modal-add-set" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2>Aggiungi Nuovo Set</h2>
            <button type="button" class="btn-close" onclick="document.getElementById('modal-add-set').style.display='none';">&times;</button>
        </div>
        <div class="modal-body">
            <form id="add-set-form" onsubmit="submitAddSet(event)">
                <input type="hidden" name="action" value="add_questionset">
                <div class="form-group">
                    <label for="add-set-name">Nome Set *</label>
                    <input type="text" id="add-set-name" name="set_name" placeholder="Es: Set di Matematica" required>
                </div>

                <div class="form-group">
                    <label for="add-set-description">Descrizione</label>
                    <textarea id="add-set-description" name="set_description" placeholder="Descrizione opzionale" rows="3"></textarea>
                </div>

                <div id="add-set-message" class="form-message"></div>

                <div class="button-container">
                    <button type="submit" class="btn btn-primary">Crea Set</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-add-set').style.display='none';">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Modifica Set -->
<div id="modal-edit-set" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2>Modifica Set</h2>
            <button type="button" class="btn-close" onclick="document.getElementById('modal-edit-set').style.display='none';">&times;</button>
        </div>
        <div class="modal-body">
            <form id="edit-set-form" onsubmit="submitEditSet(event)">
                <input type="hidden" name="action" value="update_questionset">
                <input type="hidden" id="edit-set-id" name="set_id">

                <div class="form-group">
                    <label for="edit-set-name">Nome Set *</label>
                    <input type="text" id="edit-set-name" name="set_name" required>
                </div>

                <div class="form-group">
                    <label for="edit-set-description">Descrizione</label>
                    <textarea id="edit-set-description" name="set_description" rows="3"></textarea>
                </div>

                <div id="edit-set-message" class="form-message"></div>

                <div class="button-container">
                    <button type="submit" class="btn btn-primary">Salva</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-edit-set').style.display='none';">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Eliminazione Set -->
<div id="modal-delete-set" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Elimina Set</h2>
            <button type="button" class="btn-close" onclick="document.getElementById('modal-delete-set').style.display='none';">&times;</button>
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
    let currentPage = <?php echo (int)($_POST['sets_page'] ?? 1); ?>;

    const pageInfo = document.getElementById('page-info');

    if (!pageInfo) {
        return;
    }

    function updateUI() {
        const start = (currentPage - 1) * ROWS_PER_PAGE + 1;
        const end = Math.min(currentPage * ROWS_PER_PAGE, total);
        pageInfo.textContent = `Set ${start}-${end} di ${total}`;

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


// ============================================================================
// GESTIONE MODALI E AZIONI SET
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Gestione popup Nuovo Set
    const btnNewSet = document.getElementById('btn-new-set');
    const modalAddSet = document.getElementById('modal-add-set');

    if (btnNewSet) {
        btnNewSet.addEventListener('click', () => {
            document.getElementById('add-set-form').reset();
            document.getElementById('add-set-message').innerHTML = '';
            modalAddSet.style.display = 'flex';
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
                        document.getElementById('edit-set-message').innerHTML = '';
                        document.getElementById('modal-edit-set').style.display = 'flex';
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
function submitAddSet(event) {
    event.preventDefault();

    const form = document.getElementById('add-set-form');
    const formData = new FormData(form);
    const messageDiv = document.getElementById('add-set-message');

    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert-success">✓ Set creato con successo!</div>';
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            messageDiv.innerHTML = `<div class="alert-error">✗ ${data.error || 'Errore'}</div>`;
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        messageDiv.innerHTML = '<div class="alert-error">✗ Errore durante la creazione del set</div>';
    });
}

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

function startGameWithSet(setId) {
    window.location.href = `game-room.php?set_id=${setId}`;
}

function createNewGame() {
    window.location.href = 'game-room.php';
}
</script>
