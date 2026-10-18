<?php
/**
<<<<<<< HEAD
 * check_session.php - API ligera para verificar el estado de sesión en PHP
 *
 * Si no hay sesión activa, intenta restaurarla con la cookie "Recordarme"
 * antes de responder. Devuelve JSON con el estado de sesión.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

try_auto_login();

if (!empty($_SESSION['user'])) {
=======
 * check_session.php - API ligera para verificar estado de sesión en PHP
 * Devuelve JSON indicando si el usuario tiene sesión activa o no.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
>>>>>>> 4b79d1eda0ab284548852dbbd6e24f7a4fd823f2
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'name' => $_SESSION['user']['name'],
            'email' => $_SESSION['user']['email'],
            'role' => $_SESSION['user']['role'],
            'role_label' => $_SESSION['user']['role_label'] ?? ucfirst($_SESSION['user']['role']),
            'specialty' => $_SESSION['user']['specialty'] ?? ''
        ]
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}
exit;
