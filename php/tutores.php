<?php
/**
 * tutores.php - Lista pública de tutores registrados (JSON).
 * Solo expone nombre y especialidad: nunca correos ni identificadores.
 */

require_once __DIR__ . '/db.php';

$tutors = [];
foreach (db_list_tutors(60) as $row) {
    $tutors[] = [
        'name'      => $row['nombre'],
        'specialty' => $row['specialty'],
    ];
}

json_response(['tutors' => $tutors]);
