<?php
/**
 * perfil_guardar.php - Guarda el perfil (POST multipart desde perfil.html).
 *
 * Estudiantes: nombre, descripción y foto.
 * Tutores: además especialidad, tarifa por hora y datos de contacto.
 */

require_once __DIR__ . '/../app/bootstrap/db.php';
require_once __DIR__ . '/../app/services/fotos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('perfil.html');
}

$user = current_user();
if (!$user) {
    redirect('login.html?next=perfil.html');
}

function perfil_error($code) {
    flash_set('error', ['code' => $code]);
    redirect('perfil.html');
}

if (!csrf_valid()) {
    perfil_error('csrf');
}

$row = db_find_user_by_id($user['id']);
if (!$row) {
    redirect('login.html');
}
$isTutor = ($row['role'] === 'tutor');

// 1. Datos básicos
$name = trim(post_str('name'));
$bio  = trim(post_str('bio'));

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    perfil_error('invalid_name');
}
if (mb_strlen($bio) > 500) {
    perfil_error('bio_too_long');
}
$fields = ['nombre' => $name, 'bio' => $bio];

// 2. Datos solo para tutores
if ($isTutor) {
    $specialty = trim(post_str('specialty'));
    if (mb_strlen($specialty) > 255) {
        perfil_error('field_too_long');
    }

    $price = trim(post_str('tarifa_hora'));
    if ($price === '') {
        $price = null;
    } elseif (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $price)) {
        perfil_error('invalid_price');
    }

    $whatsapp = preg_replace('/[\s\-().]/', '', post_str('whatsapp'));
    if ($whatsapp === '') {
        $whatsapp = null;
    } elseif (!preg_match('/^\+?\d{7,15}$/', $whatsapp)) {
        perfil_error('invalid_whatsapp');
    }

    $contactEmail = normalize_email(post_str('email_contacto'));
    if ($contactEmail === '') {
        $contactEmail = null;
    } elseif (strlen($contactEmail) > 150 || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        perfil_error('invalid_contact_email');
    }

    $fields['specialty']      = $specialty;
    $fields['tarifa_hora']    = $price;
    $fields['whatsapp']       = $whatsapp;
    $fields['email_contacto'] = $contactEmail;
}

// 3. Foto de perfil (opcional)
$oldPhoto = $row['foto'];
$newPhoto = null;
$removePhoto = post_str('quitar_foto') === '1';
$upload = $_FILES['foto'] ?? null;

if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    list($newPhoto, $photoError) = foto_guardar($upload);
    if ($photoError) {
        perfil_error($photoError);
    }
    $fields['foto'] = $newPhoto;
} elseif ($removePhoto) {
    $fields['foto'] = null;
}

// 4. Guardar y limpiar la foto anterior
db_update_profile($row['id'], $fields);
if (array_key_exists('foto', $fields) && $oldPhoto && $oldPhoto !== $newPhoto) {
    foto_borrar($oldPhoto);
}

// 5. Refrescar los datos de la sesión
$_SESSION['user']['name'] = $name;
if ($isTutor) {
    $_SESSION['user']['specialty'] = $fields['specialty'];
}
if (array_key_exists('foto', $fields)) {
    $_SESSION['user']['foto'] = $fields['foto'];
}

flash_set('profile_saved');
redirect('perfil.html');
