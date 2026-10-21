<?php
/**
 * login.php - Procesador backend de Inicio de Sesión
 *
 * Recibe el formulario POST de login.html, valida la contraseña contra
 * db.php, abre la sesión y, si se marcó "Recordarme", crea la cookie
 * correspondiente antes de redirigir.
 */

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$remember = isset($_POST['remember']);

// 1. Validar campos no vacíos
if (empty($email) || empty($password)) {
    header('Location: login.html?error=empty_fields');
    exit;
}

// 2. Consultar en la base de datos (SELECT * FROM usuarios WHERE email = ?)
$user = db_find_user_by_email($email);

if (!$user) {
    header('Location: login.html?error=user_not_found&email=' . urlencode($email));
    exit;
}

// 3. Verificar contraseña.
//    Si el registro aún tiene la contraseña en texto plano (cuentas demo antiguas),
//    se acepta por única vez y se migra de inmediato a un hash seguro.
$isPasswordValid = password_verify($password, $user['password']);
if (!$isPasswordValid && hash_equals((string)$user['password'], $password)) {
    $isPasswordValid = true;
    db_update_user_password($user['id'], $password);
}

if (!$isPasswordValid) {
    header('Location: login.html?error=invalid_password&email=' . urlencode($email));
    exit;
}

// 4. Abrir la sesión del usuario
session_login($user);

// 5. "Recordarme": cookie de 30 días (solo se guarda el hash del token en la base de datos)
if ($remember) {
    remember_me_set($user['id']);
}

// 6. Redirigir a la pantalla principal
header('Location: index.html?msg=login_success&name=' . urlencode($user['name']) . '&role=' . urlencode($user['role_label'] ?? ''));
exit;
