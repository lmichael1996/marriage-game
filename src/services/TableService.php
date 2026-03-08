<?php
require_once __DIR__ . '/../repository/TableRepository.php';

/**
 * Handles business logic for displaying database tables (debug/admin view).
 */
class TableService {
    private TableRepository $tableRepo;

    private const PASSWORD_KEYWORDS = ['password', 'pwd', 'pass', 'secret'];
    private const JSON_COLUMN_NAMES = ['ranking', 'metadata', 'data', 'config'];

    public function __construct() {
        $this->tableRepo = new TableRepository();
    }

    /**
     * Get all data from all tables.
     *
     * @return array  Data organized by table name
     */
    public function getAllTablesData(): array {
        $tables = $this->tableRepo->getAllTables();
        $tableData = [];

        foreach ($tables as $table) {
            $tableData[$table] = $this->tableRepo->getTableData($table);
        }

        return $tableData;
    }

    /**
     * Format a cell value for HTML display based on its type.
     *
     * @param mixed  $value       Cell value
     * @param string $type        Column data type
     * @param string $columnName  Column name
     * @return string  Formatted HTML
     */
    public function formatCellValue(mixed $value, string $type, string $columnName = ''): string {
        if (is_null($value)) {
            return '<span class="null-value">NULL</span>';
        }

        if ($this->isPasswordColumn($columnName)) {
            return '<span class="password-hidden">••••••••</span>';
        }

        if ($this->isJsonColumn($type, $columnName, $value)) {
            $decoded = json_decode($value, true);
            if ($decoded !== null) {
                $pretty = htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return '<pre class="json-value">' . $pretty . '</pre>';
            }
        }

        if (str_contains($type, 'tinyint') || str_contains($type, 'boolean')) {
            return match ($value) {
                '0', 0 => '<span class="bool-false">false</span>',
                '1', 1 => '<span class="bool-true">true</span>',
                default => htmlspecialchars($value),
            };
        }

        $text = htmlspecialchars($value);
        return strlen($text) > 50 ? substr($text, 0, 50) . '...' : $text;
    }

    /**
     * Check if a column contains JSON data.
     *
     * @param string $type        Column data type
     * @param string $columnName  Column name
     * @param mixed  $value       Cell value
     * @return bool
     */
    private function isJsonColumn(string $type, string $columnName, mixed $value): bool {
        if (stripos($type, 'json') !== false) return true;
        if (in_array(strtolower($columnName), self::JSON_COLUMN_NAMES)) return true;
        return is_string($value) && strlen($value) > 1
            && ($value[0] === '[' || $value[0] === '{')
            && json_decode($value) !== null;
    }

    /**
     * Check if a column is a password field.
     *
     * @param string $columnName  Column name
     * @return bool
     */
    private function isPasswordColumn(string $columnName): bool {
        $lowerName = strtolower($columnName);
        foreach (self::PASSWORD_KEYWORDS as $col) {
            if (str_contains($lowerName, $col)) return true;
        }
        return false;
    }
}
