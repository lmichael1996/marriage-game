<?php
require_once __DIR__ . '/../config/database.php';

/**
 * TableRepository - Gestisce l'accesso ai dati di tutte le tabelle
 */
class TableRepository {
    private $conn;

    public function __construct() {
        $this->conn = getDBConnection();
    }

    /**
     * Ottiene la lista di tutte le tabelle
     * @return array Lista dei nomi delle tabelle
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
     * Ottiene tutti i dati di una tabella
     * @param string $table Nome della tabella
     * @return array Dati della tabella con colonne, tipi e righe
     */
    public function getTableData($table) {
        $result = $this->conn->query("SELECT * FROM $table");

        $columns = [];
        $columnTypes = [];
        $rows = [];
        $totalCount = 0;

        // Ottieni il numero totale di righe
        $countResult = $this->conn->query("SELECT COUNT(*) as total FROM $table");
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

        return [
            'columns' => $columns,
            'columnTypes' => $columnTypes,
            'rows' => $rows,
            'count' => count($rows),
            'totalCount' => $totalCount
        ];
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
