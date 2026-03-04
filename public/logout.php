<?php
require_once __DIR__ . '/../src/utils/auth.php';

svc('auth')->logout();
header('Location: ../index.html');
exit();
