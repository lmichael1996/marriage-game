<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Repository for generic table access (admin database viewer)
 */
class TableRepository {
    private mysqli $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Get all table names in the database
     *
     * @return array  List of table names
     */
    public function getAllTables() {
        $result = $this->conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        return $tables;
    }

    /**
     * Get all data from a table
     *
     * @param string $table  Table name
     * @return array         Columns, types and rows
     */
    public function getTableData(string $table) {
        $result = $this->conn->query("SELECT * FROM `$table`");

        if (!$result || $result->num_rows === 0) {
            return ['columns' => [], 'columnTypes' => [], 'rows' => [], 'totalCount' => 0];
        }

        $columns = [];
        $columnTypes = [];
        foreach ($result->fetch_fields() as $field) {
            $columns[] = $field->name;
            $columnTypes[$field->name] = $field->type;
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);

        return [
            'columns' => $columns,
            'columnTypes' => $columnTypes,
            'rows' => $rows,
            'totalCount' => count($rows)
        ];
    }
}
