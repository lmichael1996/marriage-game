<?php
require_once __DIR__ . '/../../src/services/TableService.php';

$tableService = new TableService();
$tableData = $tableService->getAllTablesData();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database - MVquiz</title>
    <link rel="stylesheet" href="../../assets/css/database.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Database MVquiz</h1>
            <p>Contenuto di tutte le tabelle</p>
        </div>

        <div class="stats">
            <?php foreach ($tableData as $tableName => $data): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $data['totalCount']; ?></div>
                    <div class="stat-label"><?php echo ucfirst(str_replace('_', ' ', $tableName)); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="tables-grid">
            <?php foreach ($tableData as $tableName => $data): ?>
                <div class="table-card">
                    <div class="table-header">
                        <h2><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $tableName))); ?></h2>
                        <div class="table-count"><?php echo count($data['rows']); ?> righe visualizzate (totale: <?php echo $data['totalCount']; ?>)</div>
                    </div>

                    <div class="table-content">
                        <?php if (empty($data['rows'])): ?>
                            <div class="empty-table">
                                Nessun dato in questa tabella
                            </div>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <?php foreach ($data['columns'] as $col): ?>
                                            <th><?php echo htmlspecialchars($col); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['rows'] as $row): ?>
                                        <tr>
                                            <?php foreach ($data['columns'] as $col): ?>
                                                <td>
                                                    <?php
                                                        $value = $row[$col];
                                                        $type = $data['columnTypes'][$col] ?? '';
                                                        echo $tableService->formatCellValue($value, $type, $col);
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
