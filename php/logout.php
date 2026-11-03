<?php
/**
 * logout.php - Cierra la sesión (solo POST con token CSRF).
 *
 * Con un enlace GET cualquier página externa podría cerrar la sesión
 * de la persona, por eso se exige POST + CSRF.
 */

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect('../index.html');
}

$userId = $_SESSION['user']['id'] ?? null;

// Borra la cookie "Recordarme" y el token guardado
remember_me_clear($userId);

// Destruye la sesión y abre una nueva (con ID distinto) solo para el mensaje
$_SESSION = [];
session_destroy();
session_start();
session_regenerate_id(true);

flash_set('logout_success');
redirect('../index.html');
