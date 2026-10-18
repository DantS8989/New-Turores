<?php
/**
<<<<<<< HEAD
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
=======
 * db.php - Capa de Simulación de Base de Datos y Sesiones
 * 
 * En este archivo se concentra todo lo que en el futuro se conectará
 * a una base de datos MySQL / PDO.
 * Por ahora gestiona los datos en data/users.json y la sesión PHP.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_FILE', __DIR__ . '/data/users.json');

/**
 * Obtener todos los registros de la tabla 'usuarios'
 */
function db_get_users() {
    if (file_exists(DB_FILE)) {
        $content = @file_get_contents(DB_FILE);
        if ($content) {
            $data = json_decode($content, true);
            if (is_array($data)) {
                return $data;
            }
        }
    }

    // Datos iniciales de la base de datos (Semilla / Mock)
    $defaultUsers = [
        [
            'id' => 1,
            'name' => 'Carlos Gómez',
            'email' => 'estudiante@ejemplo.com',
            'password' => '123456',
            'role' => 'student',
            'role_label' => 'Estudiante',
            'specialty' => '',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'name' => 'Dra. Ana Martínez',
            'email' => 'tutor@ejemplo.com',
            'password' => '123456',
            'role' => 'tutor',
            'role_label' => 'Tutor',
            'specialty' => 'Matemáticas & Física',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 3,
            'name' => 'Prof. Roberto Silva',
            'email' => 'roberto@ejemplo.com',
            'password' => '123456',
            'role' => 'tutor',
            'role_label' => 'Tutor',
            'specialty' => 'Programación Web',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];

    db_save_users($defaultUsers);
    return $defaultUsers;
}

/**
 * Guardar registros en la base de datos (data/users.json)
 */
function db_save_users($users) {
    $dir = dirname(DB_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @file_put_contents(DB_FILE, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Equivalente a: SELECT * FROM usuarios WHERE email = ? LIMIT 1
 */
function db_find_user_by_email($email) {
    $email = strtolower(trim($email));
    $users = db_get_users();
    foreach ($users as $user) {
        if (strtolower($user['email']) === $email) {
            return $user;
        }
    }
    return null;
}

/**
 * Equivalente a: INSERT INTO usuarios (name, email, password, role, specialty) VALUES (...)
 */
function db_insert_user($name, $email, $password, $role, $specialty = '') {
    $users = db_get_users();
    $newId = count($users) > 0 ? (max(array_column($users, 'id')) + 1) : 1;

    $roleLabel = ($role === 'tutor') ? 'Tutor' : 'Estudiante';

    $newUser = [
        'id' => $newId,
        'name' => trim($name),
        'email' => trim($email),
        'password' => trim($password),
        'role' => $role,
        'role_label' => $roleLabel,
        'specialty' => trim($specialty),
        'created_at' => date('Y-m-d H:i:s')
    ];

    $users[] = $newUser;
    db_save_users($users);
    return $newUser;
}
>>>>>>> 4b79d1eda0ab284548852dbbd6e24f7a4fd823f2
