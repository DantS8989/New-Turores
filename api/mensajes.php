<?php
/**
 * mensajes.php - Mensajes recibidos y enviados por la persona con sesión (JSON).
 */

require_once __DIR__ . '/../app/bootstrap/db.php';

$user = require_login_json();

function mensaje_a_json($row) {
    return [
        'id'    => (int)$row['id'],
        'other' => ['id' => (int)$row['otro_id'], 'name' => $row['nombre'], 'foto' => $row['foto']],
        'body'  => $row['cuerpo'],
        'date'  => $row['creado_en'],
    ];
}

json_response([
    'received' => array_map('mensaje_a_json', db_inbox($user['id'])),
    'sent'     => array_map('mensaje_a_json', db_sent_messages($user['id'])),
]);
