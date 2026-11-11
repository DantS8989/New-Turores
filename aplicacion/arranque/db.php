<?php
// Conexión, sesión, autenticación y acceso a datos compartidos por la API.

/* Los errores se registran en el servidor y no se muestran al usuario. */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header_remove('X-Powered-By');

function fail_server($message = 'Servicio no disponible. Inténtalo más tarde.') {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit($message);
}

set_exception_handler(function ($e) {
    error_log('[TutoresOn-Line] ' . get_class($e) . ': ' . $e->getMessage());
    fail_server();
});

define('REMEMBER_COOKIE', 'remember_token');
define('REMEMBER_DAYS', 30);
define('LOGIN_MAX_FAILS_EMAIL', 5);    // intentos fallidos por correo
define('LOGIN_MAX_FAILS_IP', 30);      // intentos fallidos por IP
define('LOGIN_WINDOW_SECONDS', 900);   // ventana de 15 minutos

function request_is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('TUTORESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => request_is_https(),
    ]);
    session_start();
}

$configFile = dirname(__DIR__, 2) . '/configuracion/config.php';
if (!is_file($configFile)) {
    error_log('[TutoresOn-Line] Falta config.php (copia configuracion/config.example.php).');
    fail_server();
}
$config = require $configFile;

try {
    $pdo = new PDO(
        'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'] . ';charset=utf8mb4',
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[TutoresOn-Line] Conexión a la base de datos fallida: ' . $e->getMessage());
    fail_server();
}
unset($config);

function redirect($url) {
    if ($url !== '' && $url[0] !== '/' && !preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
        $url = '../' . $url;
    }
    header('Location: ' . $url);
    exit;
}

function json_response($data) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Lee un campo POST como texto. */
function post_str($key) {
    $v = $_POST[$key] ?? '';
    return is_string($v) ? $v : '';
}

// Permite contar caracteres aunque mbstring no esté instalada.
if (!function_exists('mb_strlen')) {
    function mb_strlen($text) {
        return (int)preg_match_all('/./us', $text);
    }
}

function normalize_email($email) {
    return strtolower(trim($email));
}

function client_ip() {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid() {
    $sent = post_str('csrf_token');
    return $sent !== '' && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

function flash_set($type, array $data = []) {
    $_SESSION['flash'] = ['type' => $type, 'data' => $data];
}

function flash_pull() {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function map_user_fields($user) {
    if (!$user) return null;
    $user['name'] = $user['nombre'] ?? '';
    $user['role'] = $user['rol'] ?? '';
    return $user;
}

function db_find_user_by_email($email) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([normalize_email($email)]);
    return map_user_fields($stmt->fetch());
}

function db_find_user_by_id($id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$id]);
    return map_user_fields($stmt->fetch());
}

/** Inserta un usuario. Puede lanzar PDOException (código 23000 si el correo ya existe). */
function db_insert_user($name, $email, $password, $role, $specialty = '') {
    global $pdo;
    $roleLabel = ($role === 'tutor') ? 'Tutor' : 'Estudiante';
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, email, password, rol, specialty, role_label, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        trim($name),
        normalize_email($email),
        password_hash($password, PASSWORD_DEFAULT),
        $role,
        trim($specialty),
        $roleLabel,
        date('Y-m-d H:i:s'),
    ]);
    return db_find_user_by_id($pdo->lastInsertId());
}

function db_update_user_password($id, $plainPassword) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
    $stmt->execute([password_hash($plainPassword, PASSWORD_DEFAULT), (int)$id]);
}

function db_set_remember_token($id, $tokenHash, $expiresAt) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE usuarios SET remember_token_hash = ?, remember_expires = ? WHERE id = ?');
    $stmt->execute([$tokenHash, (int)$expiresAt, (int)$id]);
}

function db_clear_remember_token($id) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE usuarios SET remember_token_hash = NULL, remember_expires = NULL WHERE id = ?');
    $stmt->execute([(int)$id]);
}

/** Tutores registrados (solo datos públicos: nunca correo ni contacto). */
function db_list_tutors($limit = 60) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, nombre, specialty, bio, tarifa_hora, foto FROM usuarios WHERE rol = 'tutor' ORDER BY created_at DESC, id DESC LIMIT " . (int)$limit);
    $stmt->execute();
    return $stmt->fetchAll();
}

function login_is_locked($email, $ip) {
    global $pdo;
    $since = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_SECONDS);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM intentos_login WHERE email = ? AND intentado_en > ?');
    $stmt->execute([$email, $since]);
    if ((int)$stmt->fetchColumn() >= LOGIN_MAX_FAILS_EMAIL) return true;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM intentos_login WHERE ip = ? AND intentado_en > ?');
    $stmt->execute([$ip, $since]);
    return (int)$stmt->fetchColumn() >= LOGIN_MAX_FAILS_IP;
}

function login_record_failure($email, $ip) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO intentos_login (email, ip, intentado_en) VALUES (?, ?, ?)');
    $stmt->execute([substr($email, 0, 150), $ip, date('Y-m-d H:i:s')]);

    if (random_int(1, 50) === 1) {
        $old = $pdo->prepare('DELETE FROM intentos_login WHERE intentado_en < ?');
        $old->execute([date('Y-m-d H:i:s', time() - 86400)]);
    }
}

function login_clear_failures($email) {
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM intentos_login WHERE email = ?');
    $stmt->execute([$email]);
}

function session_login($user) {
    session_regenerate_id(true);
    unset($_SESSION['csrf']); // token nuevo después de autenticar
    $_SESSION['user'] = [
        'id'         => (int)$user['id'],
        'name'       => $user['name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'role_label' => $user['role_label'] ?? ($user['role'] === 'tutor' ? 'Tutor' : 'Estudiante'),
        'specialty'  => $user['specialty'] ?? '',
        'foto'       => $user['foto'] ?? null,
        'login_time' => time(),
    ];
}

function remember_cookie_options($expires) {
    return [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => request_is_https(),
    ];
}

function remember_me_set($userId) {
    $token   = bin2hex(random_bytes(32));
    $expires = time() + 60 * 60 * 24 * REMEMBER_DAYS;
    db_set_remember_token($userId, hash('sha256', $token), $expires);
    setcookie(REMEMBER_COOKIE, $userId . ':' . $token, remember_cookie_options($expires));
}

/** Borra solo la cookie del navegador (no toca la base de datos). */
function remember_cookie_forget() {
    setcookie(REMEMBER_COOKIE, '', remember_cookie_options(time() - 3600));
    unset($_COOKIE[REMEMBER_COOKIE]);
}

/** Borra la cookie y el token guardado de un usuario ya verificado. */
function remember_me_clear($userId = null) {
    if ($userId) {
        db_clear_remember_token($userId);
    }
    remember_cookie_forget();
}

function try_auto_login() {
    if (!empty($_SESSION['user'])) {
        return true;
    }
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (!is_string($cookie) || strpos($cookie, ':') === false) {
        return false;
    }

    list($id, $token) = explode(':', $cookie, 2);
    if (!ctype_digit($id) || $token === '') {
        remember_cookie_forget();
        return false;
    }

    $user = db_find_user_by_id($id);
    if (!$user || empty($user['remember_token_hash']) || empty($user['remember_expires'])) {
        remember_cookie_forget();
        return false;
    }

    // Token vencido de un usuario real: se limpia también en la base de datos
    if ((int)$user['remember_expires'] < time()) {
        remember_me_clear($user['id']);
        return false;
    }

    // Token incorrecto: solo se olvida la cookie (no se toca el token del dueño real)
    if (!hash_equals($user['remember_token_hash'], hash('sha256', $token))) {
        remember_cookie_forget();
        return false;
    }

    session_login($user);
    remember_me_set($user['id']); // rotación del token en cada uso
    return true;
}


function current_user() {
    try_auto_login();
    return $_SESSION['user'] ?? null;
}

/** Para endpoints JSON: corta con 401 si no hay una cuenta iniciada. */
function require_login_json() {
    $user = current_user();
    if (!$user) {
        http_response_code(401);
        json_response(['error' => 'auth']);
    }
    return $user;
}

function db_update_profile($id, array $fields) {
    global $pdo;
    $allowed = ['nombre', 'specialty', 'bio', 'tarifa_hora', 'foto', 'whatsapp', 'email_contacto'];
    $sets = [];
    $values = [];
    foreach ($fields as $column => $value) {
        if (!in_array($column, $allowed, true)) continue;   // lista blanca de columnas
        $sets[] = $column . ' = ?';
        $values[] = $value;
    }
    if (!$sets) return;
    $values[] = (int)$id;
    $stmt = $pdo->prepare('UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->execute($values);
}

function db_find_tutor($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND rol = 'tutor' LIMIT 1");
    $stmt->execute([(int)$id]);
    return map_user_fields($stmt->fetch());
}

/** Convierte la tarifa de la base de datos a número (o null). */
function tarifa_value($value) {
    return ($value === null || $value === '') ? null : (float)$value;
}

define('MESSAGE_MAX_PER_HOUR', 10);
define('MESSAGE_MAX_LENGTH', 1000);

function db_insert_message($fromId, $toId, $body) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO mensajes (remitente_id, destinatario_id, cuerpo, creado_en) VALUES (?, ?, ?, ?)');
    $stmt->execute([(int)$fromId, (int)$toId, $body, date('Y-m-d H:i:s')]);
}

function db_count_recent_messages($fromId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mensajes WHERE remitente_id = ? AND creado_en > ?');
    $stmt->execute([(int)$fromId, date('Y-m-d H:i:s', time() - 3600)]);
    return (int)$stmt->fetchColumn();
}

function db_has_message_from($fromId, $toId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT 1 FROM mensajes WHERE remitente_id = ? AND destinatario_id = ? LIMIT 1');
    $stmt->execute([(int)$fromId, (int)$toId]);
    return (bool)$stmt->fetchColumn();
}

function db_inbox($userId, $limit = 50) {
    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT m.id, m.remitente_id AS otro_id, m.cuerpo, m.creado_en, u.nombre, u.foto
         FROM mensajes m JOIN usuarios u ON u.id = m.remitente_id
         WHERE m.destinatario_id = ?
         ORDER BY m.creado_en DESC, m.id DESC LIMIT ' . (int)$limit
    );
    $stmt->execute([(int)$userId]);
    return $stmt->fetchAll();
}

function db_sent_messages($userId, $limit = 50) {
    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT m.id, m.destinatario_id AS otro_id, m.cuerpo, m.creado_en, u.nombre, u.foto
         FROM mensajes m JOIN usuarios u ON u.id = m.destinatario_id
         WHERE m.remitente_id = ?
         ORDER BY m.creado_en DESC, m.id DESC LIMIT ' . (int)$limit
    );
    $stmt->execute([(int)$userId]);
    return $stmt->fetchAll();
}
