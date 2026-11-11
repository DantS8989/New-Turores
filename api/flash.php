<?php
/**
 * flash.php - Entrega (una sola vez) el mensaje guardado en la sesión:
 * errores de formulario, aviso de bienvenida o de cierre de sesión.
 */

require_once __DIR__ . '/../aplicacion/arranque/db.php';

json_response(['flash' => flash_pull()]);
