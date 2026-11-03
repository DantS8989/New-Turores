<?php
/**
 * login.php - Procesa el formulario de login.html (POST).
 *
 * Protecciones: token CSRF, límite de intentos, mensaje de error único
 * (no revela si el correo existe) y comparación en tiempo similar.
 */

require_once __DIR__ . '/db.php';

// Hash válido de relleno: se verifica cuando el correo no existe para que
// la respuesta tarde lo mismo y no se pueda averiguar qué correos existen.
const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../login.html');
}

if (!csrf_valid()) {
    flash_set('error', ['code' => 'csrf']);
    redirect('../login.html');
}

$email    = normalize_email(post_str('email'));
$password = post_str('password');
$remember = post_str('remember') === '1';
$ip       = client_ip();

// 1. Campos válidos
if ($email === '' || $password === '' || strlen($email) > 150 || strlen($password) > 1024
    || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('error', ['code' => 'invalid_credentials', 'email' => substr($email, 0, 150)]);
    redirect('../login.html');
}

// 2. Demasiados intentos fallidos
if (login_is_locked($email, $ip)) {
    flash_set('error', ['code' => 'too_many_attempts', 'email' => $email]);
    redirect('../login.html');
}

// 3. Verificar credenciales
$user  = db_find_user_by_email($email);
$hash  = $user ? $user['password'] : DUMMY_HASH;
$valid = password_verify($password, $hash) && $user !== null;

if (!$valid) {
    login_record_failure($email, $ip);
    flash_set('error', ['code' => 'invalid_credentials', 'email' => $email]);
    redirect('../login.html');
}

// 4. Actualizar el hash si el algoritmo por defecto de PHP cambió
if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    db_update_user_password($user['id'], $password);
}

// 5. Abrir sesión
login_clear_failures($email);
session_login($user);

if ($remember) {
    remember_me_set($user['id']);
}

flash_set('login_success', ['name' => $user['name']]);
redirect('../index.html');
