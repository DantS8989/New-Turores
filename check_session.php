<?php
/**
 * check_session.php - API ligera para verificar el estado de sesión en PHP
 *
 * Si no hay sesión activa, intenta restaurarla con la cookie "Recordarme"
 * antes de responder. Devuelve JSON con el estado de sesión.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

try_auto_login();

if (!empty($_SESSION['user'])) {
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
