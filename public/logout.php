<?php
require_once __DIR__ . '/../src/services/ServiceLoader.php';

// Cancella cookie auth
svc('auth')->clearAll();

// Distruggi sessione (stato UI)
session_start();
session_destroy();

header('Location: ../index.php');
exit();
?>
