<?php
/**
 * registro.php - Procesa el formulario de registro.html (POST).
 *
 * Valida los datos, guarda el usuario con la contraseña hasheada
 * y abre su sesión. Los errores se devuelven por mensaje flash (sesión),
 * no por la URL.
 */

require_once __DIR__ . '/../aplicacion/arranque/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('registro.html');
}

if (!csrf_valid()) {
    flash_set('error', ['code' => 'csrf']);
    redirect('registro.html');
}

$name      = trim(post_str('name'));
$email     = normalize_email(post_str('email'));
$password  = post_str('password');          // sin trim: se respeta lo que escribió la persona
$confirm   = post_str('confirm_password');
$role      = post_str('role') === 'tutor' ? 'tutor' : 'student';
$specialty = $role === 'tutor' ? trim(post_str('specialty')) : '';

function registro_error($code, $name, $email, $role, $specialty) {
    flash_set('error', [
        'code'      => $code,
        'name'      => $name,
        'email'     => $email,
        'role'      => $role,
        'specialty' => $specialty,
    ]);
    redirect('registro.html');
}


if ($name === '' || $email === '' || $password === '' || $confirm === '') {
    registro_error('empty_fields', $name, $email, $role, $specialty);
}


if (mb_strlen($name) > 100 || strlen($email) > 150 || mb_strlen($specialty) > 255) {
    registro_error('field_too_long', $name, $email, $role, $specialty);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    registro_error('invalid_email', $name, $email, $role, $specialty);
}

if (strlen($password) < 8) {
    registro_error('short_password', $name, $email, $role, $specialty);
}
if (strlen($password) > 72) {
    registro_error('long_password', $name, $email, $role, $specialty);
}


if ($password !== $confirm) {
    registro_error('passwords_mismatch', $name, $email, $role, $specialty);
}

if (db_find_user_by_email($email)) {
    registro_error('email_exists', $name, $email, $role, $specialty);
}

try {
    $newUser = db_insert_user($name, $email, $password, $role, $specialty);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        registro_error('email_exists', $name, $email, $role, $specialty);
    }
    throw $e;
}


session_login($newUser);
flash_set('register_success', ['name' => $newUser['name']]);
redirect('index.html');
