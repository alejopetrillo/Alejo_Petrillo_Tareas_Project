<?php

require_once __DIR__ . '/helpers.php';

exigir_admin($conexion);
exigir_post();

if (!validar_csrf()) {
    http_response_code(403);
    exit('Token de seguridad inválido.');
}

$id = leer_id(INPUT_POST, 'id');
if ($id === null) {
    http_response_code(400);
    exit('Identificador inválido.');
}

try {
    $stmt = $conexion->prepare(
        'UPDATE usuarios
         SET archivado_en = NULL
         WHERE id = :id
           AND archivado_en IS NOT NULL'
    );
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 1) {
        poner_flash('exito', 'Usuario reactivado correctamente.');
    } else {
        poner_flash('error', 'El usuario no existe o no estaba archivado.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    poner_flash('error', 'Ocurrió un error al reactivar el usuario.');
}

redirigir('archived_users.php');
