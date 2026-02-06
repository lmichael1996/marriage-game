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
    // Auto-submit form quando cambia la categoria
    document.getElementById('filter-category').addEventListener('change', function() {
        const form = this.closest('form');
        form.submit();
    });

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
</script>
