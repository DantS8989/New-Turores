<?php
/**
 * logout.php - Procesador backend de Cierre de Sesión
 *
 * Destruye la sesión PHP y, si existía, la cookie y el token de "Recordarme".
 */

require_once __DIR__ . '/db.php';

$userId = $_SESSION['user']['id'] ?? null;

// Destruir la sesión
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();

// Borrar también el "Recordarme" (cookie + token guardado)
remember_me_clear($userId);

// Redirigir a la pantalla principal
header('Location: index.html?msg=logout_success');
exit;
