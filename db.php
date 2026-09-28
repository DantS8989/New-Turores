<?php
/**
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
