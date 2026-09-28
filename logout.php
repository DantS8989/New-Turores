<?php
/**
 * logout.php - Procesador backend de Cierre de Sesión
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destruir variables de sesión
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirigir a la pantalla principal
header('Location: index.html?msg=logout_success');
exit;
