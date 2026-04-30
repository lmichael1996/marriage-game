<?php
// Database configuration

/*
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'marriage_game');
*/

define('DB_HOST', '31.11.39.191');
define('DB_USER', 'Sql1925823');
define('DB_PASS', 'EmmaLu.2022');
define('DB_NAME', 'Sql1925823_1');

// Singleton database connection
function getDBConnection(): mysqli {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    }

    return $conn;
}
?>
