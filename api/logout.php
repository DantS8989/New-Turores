<?php
// Cierra la sesión mediante POST protegido con CSRF.

require_once __DIR__ . '/../aplicacion/arranque/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect('index.html');
}

$userId = $_SESSION['user']['id'] ?? null;

remember_me_clear($userId);

$_SESSION = [];
session_destroy();
session_start();
session_regenerate_id(true);

flash_set('logout_success');
redirect('index.html');
