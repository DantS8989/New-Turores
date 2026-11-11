<?php
/**
 * tutor_perfil.php - Perfil de un tutor (JSON). SOLO con una cuenta iniciada.
 *
 * Sin sesión responde 401 y tutor.html envía a la persona a iniciar sesión.
 * Aquí se incluyen los datos de contacto, por eso nunca se entregan a visitantes.
 */

require_once __DIR__ . '/../aplicacion/arranque/db.php';

$user = require_login_json();

$id = $_GET['id'] ?? '';
if (!is_string($id) || !ctype_digit($id) || (int)$id < 1) {
    http_response_code(404);
    json_response(['error' => 'not_found']);
}

$tutor = db_find_tutor($id);
if (!$tutor) {
    http_response_code(404);
    json_response(['error' => 'not_found']);
}

json_response([
    'tutor' => [
        'id'             => (int)$tutor['id'],
        'name'           => $tutor['name'],
        'specialty'      => $tutor['specialty'],
        'bio'            => $tutor['bio'],
        'tarifa_hora'    => tarifa_value($tutor['tarifa_hora']),
        'foto'           => $tutor['foto'],
        'whatsapp'       => $tutor['whatsapp'],
        'email_contacto' => $tutor['email_contacto'],
    ],
    'is_self' => ((int)$tutor['id'] === (int)$user['id']),
]);
