<?php
/**
 * check_session.php - API ligera para verificar estado de sesión en PHP
 * Devuelve JSON indicando si el usuario tiene sesión activa o no.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
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
