<?php
require_once __DIR__ . '/../src/services/TokenService.php';

// Cancella cookie auth
TokenService::clearAll();

// Distruggi sessione (stato UI)
session_start();
session_destroy();

header('Location: ../index.php');
exit();
?>
