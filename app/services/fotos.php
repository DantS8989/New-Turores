<?php
/**
 * fotos.php - Subida segura de fotos de perfil (se incluye desde perfil_guardar.php).
 *
 * - Valida que sea una imagen real (JPG, PNG o WebP) de máximo 2 MB.
 * - Con la extensión GD la vuelve a codificar como JPG cuadrado de 512 px:
 *   elimina metadatos (GPS, EXIF) y cualquier contenido oculto en el archivo.
 * - Guarda con nombre aleatorio; el nombre original nunca se usa.
 */

define('FOTO_MAX_BYTES', 2 * 1024 * 1024);
define('FOTO_MAX_PIXELS', 4000);
define('FOTO_DIR', dirname(__DIR__, 2) . '/storage/uploads/fotos/');

function foto_nombre_valido($name) {
    return is_string($name) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) === 1;
}

function foto_borrar($name) {
    if (!foto_nombre_valido($name)) return;
    $path = FOTO_DIR . $name;
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * @param array $file Elemento de $_FILES
 * @return array [nombre_guardado|null, codigo_de_error|null]
 */
function foto_guardar($file) {
    if (!is_array($file) || !isset($file['error'])) {
        return [null, 'photo_invalid'];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return [null, 'photo_too_big'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return [null, 'photo_invalid'];
    }
    if ($file['size'] > FOTO_MAX_BYTES) {
        return [null, 'photo_too_big'];
    }

    $info = @getimagesize($file['tmp_name']);
    if (!$info) {
        return [null, 'photo_invalid'];
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($types[$info['mime']])) {
        return [null, 'photo_invalid'];
    }
    // Evita imágenes gigantes que agotan la memoria al procesarlas
    if ($info[0] > FOTO_MAX_PIXELS || $info[1] > FOTO_MAX_PIXELS || $info[0] < 1 || $info[1] < 1) {
        return [null, 'photo_invalid'];
    }

    if (!is_dir(FOTO_DIR) && !@mkdir(FOTO_DIR, 0755, true)) {
        error_log('[TutoresOn-Line] No se pudo crear ' . FOTO_DIR);
        return [null, 'photo_error'];
    }

    $base = bin2hex(random_bytes(16));

    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $source = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        if (!$source) {
            return [null, 'photo_invalid'];
        }
        $w = imagesx($source);
        $h = imagesy($source);
        $side = min($w, $h);                       // recorte cuadrado centrado
        $size = min(512, $side);
        $dest = imagecreatetruecolor($size, $size);
        imagefill($dest, 0, 0, imagecolorallocate($dest, 255, 255, 255));
        imagecopyresampled($dest, $source, 0, 0, (int)(($w - $side) / 2), (int)(($h - $side) / 2), $size, $size, $side, $side);

        $name = $base . '.jpg';
        $ok = imagejpeg($dest, FOTO_DIR . $name, 85);
        imagedestroy($source);
        imagedestroy($dest);
        return $ok ? [$name, null] : [null, 'photo_error'];
    }

    // Sin GD: se guarda el archivo ya validado, con la extensión según su tipo real
    $name = $base . '.' . $types[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], FOTO_DIR . $name)) {
        return [null, 'photo_error'];
    }
    return [$name, null];
}
