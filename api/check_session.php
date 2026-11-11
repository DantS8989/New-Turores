<?php
/**
 * check_session.php - Estado de la sesión para el frontend (JSON).
 *
 * Si no hay sesión, intenta restaurarla con la cookie "Recordarme".
 * También entrega el token CSRF que main.js coloca en los formularios.
 */

require_once __DIR__ . '/../app/bootstrap/db.php';

try_auto_login();

$response = [
    'logged_in'  => false,
    'csrf_token' => csrf_token(),
];

if (!empty($_SESSION['user'])) {
    $u = $_SESSION['user'];
    $response['logged_in'] = true;
    $response['user'] = [
        'id'         => (int)$u['id'],
        'name'       => $u['name'],
        'role'       => $u['role'],
        'role_label' => $u['role_label'],
        'specialty'  => $u['specialty'],
        'foto'       => $u['foto'] ?? null,
    ];
}

json_response($response);
