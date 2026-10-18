<?php
/**
 * db.php - Capa de acceso a datos y manejo de sesiones (Actualizado para MySQL/PDO)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

define('REMEMBER_COOKIE', 'remember_token');
define('REMEMBER_DAYS', 30);

// Configuración de base de datos
$host = 'localhost';
$dbname = 'tutores_online';
$username = 'root';
$password = ''; // Ajusta la contraseña según tu entorno local

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

/* ──────────────────────────────────────────────────────────
 * Funciones Auxiliares
 * ────────────────────────────────────────────────────────── */
function map_user_fields($user) {
    if (!$user) return null;
    $user['name'] = $user['nombre'] ?? '';
    $user['role'] = $user['rol'] ?? '';
    return $user;
}

/* ──────────────────────────────────────────────────────────
 * Acceso a datos (MySQL/PDO)
 * ────────────────────────────────────────────────────────── */

function db_find_user_by_email($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([strtolower(trim($email))]);
    return map_user_fields($stmt->fetch());
}

function db_find_user_by_id($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$id]);
    return map_user_fields($stmt->fetch());
}

function db_insert_user($name, $email, $password, $role, $specialty = '') {
    global $pdo;
    $roleLabel = ($role === 'tutor') ? 'Tutor' : 'Estudiante';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, specialty, role_label, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $createdAt = date('Y-m-d H:i:s');
    $stmt->execute([trim($name), trim($email), $hashedPassword, $role, trim($specialty), $roleLabel, $createdAt]);
    
    $newId = $pdo->lastInsertId();
    return db_find_user_by_id($newId);
}

function db_update_user_password($id, $plainPassword) {
    global $pdo;
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, (int)$id]);
}

function db_set_remember_token($id, $tokenHash, $expiresAt) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE usuarios SET remember_token_hash = ?, remember_expires = ? WHERE id = ?");
    $stmt->execute([$tokenHash, $expiresAt, (int)$id]);
}

function db_clear_remember_token($id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE usuarios SET remember_token_hash = NULL, remember_expires = NULL WHERE id = ?");
    $stmt->execute([(int)$id]);
}

/* ──────────────────────────────────────────────────────────
 * Sesiones y cookie "Recordarme"
 * ────────────────────────────────────────────────────────── */

function session_login($user) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'role_label' => $user['role_label'] ?? ($user['role'] === 'tutor' ? 'Tutor' : 'Estudiante'),
        'specialty' => $user['specialty'] ?? '',
        'login_time' => time(),
    ];
}

function remember_me_set($userId) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + 60 * 60 * 24 * REMEMBER_DAYS;
    db_set_remember_token($userId, hash('sha256', $token), $expires);
    setcookie(REMEMBER_COOKIE, $userId . ':' . $token, [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

function remember_me_clear($userId = null) {
    if ($userId) {
        db_clear_remember_token($userId);
    }
    setcookie(REMEMBER_COOKIE, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[REMEMBER_COOKIE]);
}

function try_auto_login() {
    if (!empty($_SESSION['user'])) {
        return true;
    }
    if (empty($_COOKIE[REMEMBER_COOKIE]) || strpos($_COOKIE[REMEMBER_COOKIE], ':') === false) {
        return false;
    }

    [$id, $token] = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
    $user = db_find_user_by_id($id);

    if (!$user || empty($user['remember_token_hash']) || empty($user['remember_expires'])
        || $user['remember_expires'] < time()
        || !hash_equals($user['remember_token_hash'], hash('sha256', $token))
    ) {
        remember_me_clear($id);
        return false;
    }

    session_login($user);
    remember_me_set($user['id']);
    return true;
}
?>