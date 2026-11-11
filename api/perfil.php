<?php
/**
 * perfil.php - Datos del perfil de la persona con sesión iniciada (JSON).
 */

require_once __DIR__ . '/../aplicacion/arranque/db.php';

$user = require_login_json();
$row  = db_find_user_by_id($user['id']);
if (!$row) {
    http_response_code(401);
    json_response(['error' => 'auth']);
}

json_response([
    'profile' => [
        'id'             => (int)$row['id'],
        'name'           => $row['name'],
        'email'          => $row['email'],
        'role'           => $row['role'],
        'role_label'     => $row['role_label'],
        'specialty'      => $row['specialty'],
        'bio'            => $row['bio'],
        'tarifa_hora'    => tarifa_value($row['tarifa_hora']),
        'foto'           => $row['foto'],
        'whatsapp'       => $row['whatsapp'],
        'email_contacto' => $row['email_contacto'],
    ],
]);
