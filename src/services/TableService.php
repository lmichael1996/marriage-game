<?php
require_once __DIR__ . '/../repository/TableRepository.php';

/**
 * TableService - Gestisce la logica di business per visualizzare le tabelle
 */
class TableService {
    private $tableRepo;

    public function __construct() {
        $this->tableRepo = new TableRepository();
    }

    /**
     * Ottiene tutti i dati di tutte le tabelle
     * @return array Dati organizzati per ogni tabella
     */
    public function getAllTablesData() {
        $tables = $this->tableRepo->getAllTables();
        $tableData = [];

        foreach ($tables as $table) {
            $tableData[$table] = $this->tableRepo->getTableData($table);
        }

        return $tableData;
    }

    /**
     * Formatta il valore di una cella in base al tipo
     * @param mixed $value Il valore da formattare
     * @param string $type Il tipo di dato
     * @param string $columnName Nome della colonna
     * @return string HTML formattato
     */
    public function formatCellValue($value, $type, $columnName = '') {
        if (is_null($value)) {
            return '<span class="null-value">NULL</span>';
        }

        // Nascondi password
        if ($this->isPasswordColumn($columnName)) {
            return '<span class="password-hidden">••••••••</span>';
        }

        // JSON: mostra formattato
        if ($this->isJsonColumn($type, $columnName, $value)) {
            $decoded = json_decode($value, true);
            if ($decoded !== null) {
                $pretty = htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return '<pre class="json-value">' . $pretty . '</pre>';
            }
        }

        // Gestione booleani/tinyint
        if (strpos($type, 'tinyint') !== false || strpos($type, 'boolean') !== false) {
            if ($value === '0' || $value === 0) {
                return '<span class="bool-false">false</span>';
            } elseif ($value === '1' || $value === 1) {
                return '<span class="bool-true">true</span>';
            }
        }

        // Troncamento testo lungo
        $text = htmlspecialchars($value);
        if (strlen($text) > 50) {
            return substr($text, 0, 50) . '...';
        }

        return $text;
    }

    /**
     * Verifica se una colonna contiene JSON
     */
    private function isJsonColumn($type, $columnName, $value) {
        if (stripos($type, 'json') !== false) return true;
        if (in_array(strtolower($columnName), ['ranking', 'metadata', 'data', 'config'])) return true;
        if (is_string($value) && strlen($value) > 1 && ($value[0] === '[' || $value[0] === '{')) {
            return json_decode($value) !== null;
        }
        return false;
    }

    /**
     * Verifica se una colonna è una password
     * @param string $columnName Nome della colonna
     * @return bool
     */
    private function isPasswordColumn($columnName) {
        $passwordColumns = ['password', 'pwd', 'pass', 'secret'];
        $lowerName = strtolower($columnName);

        foreach ($passwordColumns as $col) {
            if (strpos($lowerName, $col) !== false) {
                return true;
            }
        }

        return false;
    }
}
