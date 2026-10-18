<?php
/**
 * registro.php - Procesador backend de Registro de Usuarios
<<<<<<< HEAD
 *
 * Recibe el formulario POST de registro.html, valida los datos, guarda
 * el nuevo usuario (con la contraseña ya hasheada, ver db.php) y abre
 * su sesión antes de redirigir.
=======
 * 
 * Recibe el formulario POST de registro.html, valida los datos,
 * ejecuta el guardado en la base de datos (db.php) e inicia la sesión.
>>>>>>> 4b79d1eda0ab284548852dbbd6e24f7a4fd823f2
 */

require_once __DIR__ . '/db.php';

<<<<<<< HEAD
=======
// Si no es petición POST, redirigir al formulario HTML
>>>>>>> 4b79d1eda0ab284548852dbbd6e24f7a4fd823f2
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.html');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');
$role = in_array($_POST['role'] ?? '', ['student', 'tutor']) ? $_POST['role'] : 'student';
$specialty = trim($_POST['specialty'] ?? '');

$baseRedirect = 'registro.html?name=' . urlencode($name) . '&email=' . urlencode($email) . '&role=' . urlencode($role);

// 1. Validar campos requeridos
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    header('Location: ' . $baseRedirect . '&error=empty_fields');
    exit;
}

// 2. Validar formato de correo
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $baseRedirect . '&error=invalid_email');
    exit;
}

// 3. Validar longitud de contraseña
if (strlen($password) < 4) {
    header('Location: ' . $baseRedirect . '&error=short_password');
    exit;
}

// 4. Validar confirmación de contraseña
if ($password !== $confirm_password) {
    header('Location: ' . $baseRedirect . '&error=passwords_mismatch');
    exit;
}

// 5. Verificar que el correo no esté registrado previamente
if (db_find_user_by_email($email)) {
    header('Location: ' . $baseRedirect . '&error=email_exists');
    exit;
}

<<<<<<< HEAD
// 6. Insertar en la base de datos (la contraseña se hashea dentro de db_insert_user)
$newUser = db_insert_user($name, $email, $password, $role, $specialty);

// 7. Abrir sesión automáticamente
session_login($newUser);
=======
// 6. Insertar en la base de datos (INSERT INTO usuarios ...)
$newUser = db_insert_user($name, $email, $password, $role, $specialty);

// 7. Iniciar sesión automática
$_SESSION['user'] = [
    'id' => $newUser['id'],
    'name' => $newUser['name'],
    'email' => $newUser['email'],
    'role' => $newUser['role'],
    'role_label' => $newUser['role_label'],
    'specialty' => $newUser['specialty'],
    'login_time' => time()
];
>>>>>>> 4b79d1eda0ab284548852dbbd6e24f7a4fd823f2

// 8. Redirigir a la pantalla principal
header('Location: index.html?msg=register_success&name=' . urlencode($newUser['name']) . '&role=' . urlencode($newUser['role_label']));
exit;
