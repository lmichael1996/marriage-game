<!-- Tab: Set di Domande e Gestione Partita -->
<div class="admin-section">
    <div class="tab-header-with-button">
        <h2>🎮 Set di Domande</h2>
        <button class="btn btn-success" id="btn-new-set">+ Nuovo Set</button>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <form method="GET" action="admin.php" class="search-form">
            <input type="hidden" name="tab" value="sets">
            <input type="text" name="search" id="search-sets" placeholder="Cerca set per nome..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button type="submit" class="btn btn-primary">🔍 Cerca</button>
            <?php if (!empty($_GET['search'])): ?>
                <a href="?tab=sets" class="btn btn-secondary">✖ Cancella</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table View -->
    <div id="table-view">
        <table class="sets-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrizione</th>
                    <th>Domande</th>
                    <th>Ultima Modifica</th>
                    <th>Azioni Gestione</th>
                    <th>Avvia Partita</th>
                </tr>
            </thead>
            <tbody id="sets-table-body">
                <?php foreach ($questionSets as $set): ?>
                <tr data-set-id="<?php echo $set['id']; ?>" data-set-name="<?php echo htmlspecialchars($set['set_name']); ?>">
                    <td>
                        <strong>
                            <?php
                            $setName = htmlspecialchars($set['set_name']);
                            // Evidenzia il testo cercato in giallo
                            if (!empty($_GET['search'])) {
                                $searchTerm = htmlspecialchars($_GET['search']);
                                $pattern = '/(' . preg_quote($searchTerm, '/') . ')/i';
                                $setName = preg_replace($pattern, '<mark>$1</mark>', $setName);
                            }
                            echo $setName;
                            ?>
                        </strong>
                    </td>
                    <td><?php echo $set['set_description'] ? htmlspecialchars($set['set_description']) : '<em class="empty-description">Nessuna</em>'; ?></td>
                    <td><span class="badge-small"><?php echo $set['total_rounds']; ?></span></td>
                    <td><?php echo isset($set['updated_at']) ? date('d/m/Y H:i', strtotime($set['updated_at'])) : '-'; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon btn-warning btn-edit-set" data-set-id="<?php echo $set['id']; ?>" data-set-name="<?php echo htmlspecialchars($set['set_name']); ?>" data-set-description="<?php echo htmlspecialchars($set['set_description']); ?>" title="Modifica">✎</button>
                            <button class="btn-icon btn-danger btn-delete-set" data-set-id="<?php echo $set['id']; ?>" data-set-name="<?php echo htmlspecialchars($set['set_name']); ?>" title="Elimina">×</button>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-primary btn-start-game" data-set-id="<?php echo $set['id']; ?>" data-set-name="<?php echo htmlspecialchars($set['set_name']); ?>" title="Avvia una nuova partita con questo set">
                            🎮 Avvia
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="pagination">
            <?php
            $searchParam = !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
            ?>
            <?php if ($pagination['page'] > 1): ?>
                <a href="?tab=sets&page=<?php echo $pagination['page'] - 1; ?><?php echo $searchParam; ?>" class="pagination-btn">
                    ← Precedente
                </a>
            <?php else: ?>
                <span class="pagination-btn disabled">← Precedente</span>
            <?php endif; ?>

            <span class="pagination-info">
                Pagina <?php echo $pagination['page']; ?> di <?php echo $pagination['totalPages']; ?>
                (<?php echo $pagination['total']; ?> set totali)
            </span>

            <?php if ($pagination['page'] < $pagination['totalPages']): ?>
                <a href="?tab=sets&page=<?php echo $pagination['page'] + 1; ?><?php echo $searchParam; ?>" class="pagination-btn">
                    Successivo →
                </a>
            <?php else: ?>
                <span class="pagination-btn disabled">Successivo →</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($questionSets)): ?>
        <div class="info-box loading-text">
            <?php if (!empty($_GET['search'])): ?>
                <h3>Nessun risultato trovato</h3>
                <p>Nessun set trovato per "<?php echo htmlspecialchars($_GET['search']); ?>"</p>
                <a href="?tab=sets" class="btn btn-primary">Torna ai set</a>
            <?php else: ?>
                <h3>Nessun set di domande</h3>
                <p>Clicca su "Nuovo Set" per iniziare.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Sets Tab - JavaScript
    let selectedSetId = <?php echo $selectedSetId ? $selectedSetId : 'null'; ?>;

    // Question Sets Management
    function editSet(id, name, description) {
        console.log('editSet called with:', { id, name, description });
        fetch(`admin.php?action=get_set_questions&set_id=${id}`)
            .then(response => response.json())
            .then(data => {
                console.log('Received data:', data);
                if (data.success) {
                    showEditSetModal(id, name, description, data.questions);
                } else {
                    alert('Errore nel caricamento delle domande');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Errore nel caricamento del set');
            });
    }

    function deleteSet(id, name) {
        if (!confirm(`⚠️ Sei sicuro di voler eliminare il set "${name}"?\nTutti i round associati verranno eliminati.`)) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_question_set">
            <input type="hidden" name="set_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // Start game - redirect to game settings page
    function startGameFlow(setId, setName) {
        window.location.href = `game-settings.php?set_id=${setId}`;
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Edit set buttons (event delegation)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit-set')) {
                e.stopPropagation();
                const btn = e.target.closest('.btn-edit-set');
                const setId = parseInt(btn.getAttribute('data-set-id'));
                const setName = btn.getAttribute('data-set-name');
                const setDescription = btn.getAttribute('data-set-description');
                editSet(setId, setName, setDescription);
            }
        });

        // Delete set buttons (event delegation)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete-set')) {
                e.stopPropagation();
                const btn = e.target.closest('.btn-delete-set');
                const setId = parseInt(btn.getAttribute('data-set-id'));
                const setName = btn.getAttribute('data-set-name');
                deleteSet(setId, setName);
            }
        });

        // Start game buttons (event delegation)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-start-game')) {
                e.stopPropagation();
                const btn = e.target.closest('.btn-start-game');
                const setId = parseInt(btn.getAttribute('data-set-id'));
                const setName = btn.getAttribute('data-set-name');
                startGameFlow(setId, setName);
            }
        });
    });
</script>

