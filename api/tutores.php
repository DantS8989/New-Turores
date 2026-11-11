<?php
/**
 * tutores.php - Lista pública de tutores registrados (JSON).
 * Solo datos públicos: nunca correos, WhatsApp ni datos de contacto
 * (esos se ven solo con sesión iniciada, en tutor_perfil.php).
 */

require_once __DIR__ . '/../aplicacion/arranque/db.php';

$tutors = [];
foreach (db_list_tutors(60) as $row) {
    $bio = (string)$row['bio'];
    $tutors[] = [
        'id'          => (int)$row['id'],
        'name'        => $row['nombre'],
        'specialty'   => $row['specialty'],
        'bio'         => mb_strlen($bio) > 140 ? rtrim(mb_substr($bio, 0, 140)) . '…' : $bio,
        'tarifa_hora' => tarifa_value($row['tarifa_hora']),
        'foto'        => $row['foto'],
    ];
}

json_response(['tutors' => $tutors]);
