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
            <button type="submit" class="btn btn-primary">🔍 Cerca</button>
            <?php if (!empty($_GET['search'])): ?>
                <a href="?tab=questions" class="btn btn-secondary">✖ Cancella</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table View -->
    <div id="table-view">
        <table class="questions-table">
            <thead>
                <tr>
                    <th>Domanda</th>
                    <th>Tipo</th>
                    <th>Risposta Corretta</th>
                    <th>Timer</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                <tr data-question-id="<?php echo $q['id']; ?>">
                    <td><strong><?php echo htmlspecialchars($q['question']); ?></strong></td>
                    <td><span class="badge-type"><?php echo ucfirst($q['round_type']); ?></span></td>
                    <td><?php echo $q['correct_answer']; ?></td>
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
    </div>
</div>

<script>
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit-question')) {
            const questionId = e.target.closest('.btn-edit-question').getAttribute('data-question-id');
            console.log('Edit question:', questionId);
        }
        if (e.target.closest('.btn-delete-question')) {
            const questionId = e.target.closest('.btn-delete-question').getAttribute('data-question-id');
            if (confirm('Sei sicuro di voler eliminare questa domanda?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_question">
                    <input type="hidden" name="question_id" value="${questionId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    });
</script>
