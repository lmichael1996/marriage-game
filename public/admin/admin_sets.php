<!-- Tab: Set di Domande -->
<div class="admin-section">
    <div class="header-actions">
        <h2>Set di Domande</h2>
        <button class="btn btn-success" id="btn-new-set">+ Nuovo Set</button>
    </div>
    
    <!-- Search Bar -->
    <div class="search-bar">
        <input type="text" id="search-sets" placeholder="Cerca set...">
        <select id="search-criteria">
            <option value="contains">Contiene</option>
            <option value="exact">Esattamente</option>
            <option value="starts">Inizia con</option>
            <option value="ends">Finisce con</option>
        </select>
        <button class="btn btn-primary" id="btn-search-sets">Cerca</button>
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
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody id="sets-table-body">
                <?php foreach ($questionSets as $set): ?>
                <tr data-set-id="<?php echo $set['id']; ?>" data-set-name="<?php echo htmlspecialchars($set['set_name']); ?>">
                    <td><strong><?php echo htmlspecialchars($set['set_name']); ?></strong></td>
                    <td><?php echo $set['set_description'] ? htmlspecialchars($set['set_description']) : '<em class="empty-description">Nessuna</em>'; ?></td>
                    <td><span class="badge-small"><?php echo $set['total_rounds']; ?></span></td>
                    <td><?php echo isset($set['updated_at']) ? date('d/m/Y H:i', strtotime($set['updated_at'])) : '-'; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon btn-warning btn-edit-set" data-set-id="<?php echo $set['id']; ?>" data-set-name="<?php echo htmlspecialchars($set['set_name']); ?>" data-set-description="<?php echo htmlspecialchars($set['set_description']); ?>" title="Modifica">✎</button>
                            <button class="btn-icon btn-danger btn-delete-set" data-set-id="<?php echo $set['id']; ?>" data-set-name="<?php echo htmlspecialchars($set['set_name']); ?>" title="Elimina">×</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($questionSets)): ?>
        <div class="info-box loading-text">
            <h3>Nessun set di domande</h3>
            <p>Clicca su "Nuovo Set" per iniziare.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Sets Tab - JavaScript
    let selectedSetId = <?php echo $selectedSetId ? $selectedSetId : 'null'; ?>;
    
    /* Row selection functionality disabled
    // Select set row
    function selectSetRow(row, setId, setName) {
        // Se la riga è già selezionata, deseleziona
        if (row.classList.contains('selected-row')) {
            deselectAllRows();
            return;
        }
        
        // Deseleziona tutte le altre righe
        deselectAllRows();
        
        // Seleziona questa riga
        row.classList.add('selected-row');
        selectedSetId = setId;
        
        // Nascondi i bottoni modifica/elimina originali
        const actionButtons = row.querySelector('.action-buttons');
        if (actionButtons) {
            actionButtons.style.display = 'none';
        }
        
        // Aggiungi pulsante X per eliminare
        const deleteCell = row.cells[4];
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn-icon btn-danger';
        deleteBtn.title = 'Elimina';
        deleteBtn.style.cssText = 'font-size: 1.5em; padding: 5px 15px;';
        deleteBtn.textContent = '×';
        deleteBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            deleteSelectedSet(setId, setName);
            return false;
        });
        deleteCell.innerHTML = '';
        deleteCell.appendChild(deleteBtn);
        
        // Disabilita search bar
        const searchInput = document.getElementById('search-sets');
        const searchCriteria = document.getElementById('search-criteria');
        const searchButton = document.querySelector('.search-bar .btn-primary');
        
        if (searchInput) searchInput.disabled = true;
        if (searchCriteria) searchCriteria.disabled = true;
        if (searchButton) searchButton.disabled = true;
        
        // Nascondi tutte le altre righe
        document.querySelectorAll('#sets-table-body tr').forEach(tr => {
            if (tr !== row) {
                tr.style.display = 'none';
            }
        });
    }
    
    function deselectAllRows() {
        selectedSetId = null;
        
        // Rimuovi selezione
        document.querySelectorAll('#sets-table-body tr').forEach(row => {
            row.classList.remove('selected-row');
            row.style.display = '';
            
            // Ripristina i bottoni originali
            const setId = row.dataset.setId;
            const setName = row.cells[0].textContent.trim();
            const setDescription = row.cells[1].textContent.trim();
            
            const actionButtons = row.querySelector('.action-buttons');
            if (actionButtons) {
                actionButtons.style.display = 'flex';
            }
            
            // Ripristina cell azioni originali se modificata
            const deleteCell = row.cells[4];
            if (!deleteCell.querySelector('.action-buttons')) {
                const actionButtonsDiv = document.createElement('div');
                actionButtonsDiv.className = 'action-buttons';
                
                const editBtn = document.createElement('button');
                editBtn.className = 'btn-icon btn-warning';
                editBtn.title = 'Modifica';
                editBtn.textContent = '✎';
                editBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    editSet(setId, setName, setDescription);
                });
                
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn-icon btn-danger';
                deleteBtn.title = 'Elimina';
                deleteBtn.textContent = '×';
                deleteBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    deleteSet(setId, setName);
                });
                
                actionButtonsDiv.appendChild(editBtn);
                actionButtonsDiv.appendChild(deleteBtn);
                deleteCell.innerHTML = '';
                deleteCell.appendChild(actionButtonsDiv);
            }
        });
        
        // Riabilita search bar
        const searchInput = document.getElementById('search-sets');
        const searchCriteria = document.getElementById('search-criteria');
        const searchButton = document.querySelector('.search-bar .btn-primary');
        
        if (searchInput) searchInput.disabled = false;
        if (searchCriteria) searchCriteria.disabled = false;
        if (searchButton) searchButton.disabled = false;
    }
    
    function deleteSelectedSet(id, name) {
        if (confirm(`Sei sicuro di voler eliminare il set "${name}"?`)) {
            deleteSet(id, name);
        }
    }
    */ // End of disabled row selection functionality

    
    // Question Sets Management
    function editSet(id, name, description) {
        console.log('editSet called with:', { id, name, description });
        // Carica il set con le sue domande nel modal
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
    
    // Search in table view
    function searchSetsTable() {
        const input = document.getElementById('search-sets').value.toLowerCase();
        const criteria = document.getElementById('search-criteria').value;
        const rows = document.querySelectorAll('#sets-table-body tr');
        
        // Remove previous highlights
        rows.forEach(row => {
            row.querySelectorAll('td').forEach(td => {
                if (td.dataset.originalText) {
                    td.innerHTML = td.dataset.originalText;
                }
            });
        });
        
        if (!input) {
            rows.forEach(row => row.style.display = '');
            return;
        }
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            let matches = false;
            
            switch(criteria) {
                case 'exact':
                    // Cerca esattamente nel nome del set (prima colonna)
                    const setName = row.cells[0].textContent.toLowerCase().trim();
                    matches = setName === input;
                    break;
                case 'starts':
                    matches = text.startsWith(input);
                    break;
                case 'ends':
                    matches = text.endsWith(input);
                    break;
                case 'contains':
                default:
                    matches = text.includes(input);
                    break;
            }
            
            row.style.display = matches ? '' : 'none';
            
            // Highlight matching text
            if (matches && input) {
                row.querySelectorAll('td').forEach((td, index) => {
                    // Skip action column (last column)
                    if (index === row.cells.length - 1) return;
                    
                    if (!td.dataset.originalText) {
                        td.dataset.originalText = td.innerHTML;
                    }
                    
                    const cellText = td.textContent;
                    const cellTextLower = cellText.toLowerCase();
                    const regex = new RegExp(`(${input.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    
                    let shouldHighlight = false;
                    switch(criteria) {
                        case 'exact':
                            shouldHighlight = index === 0 && cellTextLower.trim() === input;
                            break;
                        case 'starts':
                            shouldHighlight = cellTextLower.startsWith(input);
                            break;
                        case 'ends':
                            shouldHighlight = cellTextLower.endsWith(input);
                            break;
                        case 'contains':
                        default:
                            shouldHighlight = cellTextLower.includes(input);
                            break;
                    }
                    
                    if (shouldHighlight) {
                        td.innerHTML = cellText.replace(regex, '<mark>$1</mark>');
                    }
                });
            }
        });
    }
    
    // Search on Enter key
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-sets');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchSetsTable();
                }
            });
        }
        
        // Search button
        const btnSearchSets = document.getElementById('btn-search-sets');
        if (btnSearchSets) {
            btnSearchSets.addEventListener('click', searchSetsTable);
        }
        
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
        
        // Removed row click selection functionality
        // Rows are no longer selectable - only edit/delete buttons work
    });
</script>
