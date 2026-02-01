<?php
require_once __DIR__ . '/../../src/config/database.php';

$conn = getDBConnection();

// Ottieni lista di tutte le tabelle
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$tableData = [];

// Per ogni tabella, ottieni i dati (SENZA LIMITE)
foreach ($tables as $table) {
    $result = $conn->query("SELECT * FROM $table");

    $columns = [];
    $columnTypes = []; // Salva il tipo di ogni colonna
    $rows = [];
    $totalCount = 0;

    // Ottieni il numero totale di righe
    $countResult = $conn->query("SELECT COUNT(*) as total FROM $table");
    if ($countRow = $countResult->fetch_assoc()) {
        $totalCount = $countRow['total'];
    }

    if ($result && $result->num_rows > 0) {
        // Ottieni nomi colonne e i loro tipi
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            $columns[] = $field->name;
            $columnTypes[$field->name] = $field->type;
        }

        // Ottieni TUTTI i dati
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    $tableData[$table] = [
        'columns' => $columns,
        'columnTypes' => $columnTypes,
        'rows' => $rows,
        'count' => count($rows),
        'totalCount' => $totalCount
    ];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutte le Tabelle - Marriage Game</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #333;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .stats {
            background: white;
            padding: 15px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        .stat-card {
            padding: 15px;
            text-align: center;
            border-right: 1px solid #eee;
        }

        .stat-card:last-child {
            border-right: none;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tables-grid {
            display: block;
        }

        .table-card {
            background: white;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .table-header {
            background: #f8f8f8;
            color: #333;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }

        .table-header h2 {
            font-size: 16px;
            margin: 0;
            font-weight: 600;
        }

        .table-count {
            background: white;
            border: 1px solid #ddd;
            padding: 5px 10px;
            font-size: 12px;
            color: #666;
            border-radius: 3px;
        }

        .table-content {
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            background: #fafafa;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #ddd;
            border-right: 1px solid #ddd;
            position: sticky;
            top: 0;
            white-space: nowrap;
        }

        th:last-child {
            border-right: none;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            border-right: 1px solid #f0f0f0;
            word-break: break-word;
            max-width: 200px;
            font-size: 12px;
        }

        td:last-child {
            border-right: none;
        }

        tr:hover {
            background: #fafafa;
        }

        .null-value {
            color: #999;
            font-style: italic;
        }

        .bool-true {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 2px 6px;
            border-radius: 2px;
            font-weight: 600;
            display: inline-block;
            font-size: 11px;
        }

        .bool-false {
            background: #ffebee;
            color: #c62828;
            padding: 2px 6px;
            border-radius: 2px;
            font-weight: 600;
            display: inline-block;
            font-size: 11px;
        }

        .empty-table {
            padding: 30px;
            text-align: center;
            color: #999;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .stat-card {
                border-right: none;
                border-bottom: 1px solid #eee;
            }

            .stat-card:last-child {
                border-bottom: none;
            }

            table {
                font-size: 11px;
            }

            td, th {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Database Marriage Game</h1>
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

                                                        if (is_null($value)) {
                                                            echo '<span class="null-value">NULL</span>';
                                                        } elseif (strpos($type, 'tinyint') !== false || strpos($type, 'boolean') !== false) {
                                                            // Campo boolean/tinyint
                                                            if ($value === '0' || $value === 0) {
                                                                echo '<span class="bool-false">false</span>';
                                                            } elseif ($value === '1' || $value === 1) {
                                                                echo '<span class="bool-true">true</span>';
                                                            } else {
                                                                echo htmlspecialchars($value);
                                                            }
                                                        } else {
                                                            $text = htmlspecialchars($value);
                                                            if (strlen($text) > 50) {
                                                                echo substr($text, 0, 50) . '...';
                                                            } else {
                                                                echo $text;
                                                            }
                                                        }
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
