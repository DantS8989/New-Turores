<?php
// Envía mensajes entre usuarios autenticados.

require_once __DIR__ . '/../aplicacion/arranque/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('mensajes.html');
}

$user = current_user();
$toId = ctype_digit(post_str('destinatario_id')) ? (int)post_str('destinatario_id') : 0;
$fromMessages = post_str('origen') === 'mensajes';

if (!$user) {
    redirect($toId > 0 ? 'login.html?next=' . rawurlencode('tutor.html?id=' . $toId) : 'login.html');
}

// El origen del formulario determina un destino interno permitido.
$backUrl = $fromMessages ? 'mensajes.html' : 'tutor.html?id=' . $toId;

function mensaje_error($code, $backUrl) {
    flash_set('error', ['code' => $code]);
    redirect($backUrl);
}

if (!csrf_valid()) {
    mensaje_error('csrf', $backUrl);
}

$body = trim(post_str('mensaje'));
if ($body === '') {
    mensaje_error('message_empty', $backUrl);
}
if (mb_strlen($body) > MESSAGE_MAX_LENGTH) {
    mensaje_error('message_too_long', $backUrl);
}

$selfId = (int)$user['id'];
$dest = ($toId > 0 && $toId !== $selfId) ? db_find_user_by_id($toId) : null;
if (!$dest || ($dest['role'] !== 'tutor' && !db_has_message_from($toId, $selfId))) {
    mensaje_error('message_forbidden', $backUrl);
}

if (db_count_recent_messages($selfId) >= MESSAGE_MAX_PER_HOUR) {
    mensaje_error('message_rate', $backUrl);
}

db_insert_message($selfId, $toId, $body);
flash_set('message_sent');
redirect($backUrl);
