<?php
require_once __DIR__ . '/../config/database.php';

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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(800px, 1fr));
            gap: 30px;
        }

        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 {
            font-size: 20px;
            margin: 0;
        }

        .table-count {
            background: rgba(255,255,255,0.3);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
        }

        .table-content {
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
            position: sticky;
            top: 0;
            white-space: nowrap;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            word-break: break-word;
            max-width: 200px;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .null-value {
            color: #999;
            font-style: italic;
        }

        .bool-true {
            background: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 600;
            display: inline-block;
        }

        .bool-false {
            background: #f8d7da;
            color: #721c24;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 600;
            display: inline-block;
        }

        .empty-table {
            padding: 30px;
            text-align: center;
            color: #999;
        }

        .stats {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        @media (max-width: 1200px) {
            .tables-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Database Marriage Game</h1>
            <p>Visualizza il contenuto di tutte le tabelle</p>
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
