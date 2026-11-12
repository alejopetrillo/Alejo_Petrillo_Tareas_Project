<?php

require_once __DIR__ . '/helpers.php';

$usuario_actual = exigir_admin($conexion);
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


if ($id === (int) $usuario_actual['id']) {
    poner_flash('error', 'No podés archivar tu propia cuenta.');
    redirigir('admin_users.php');
}

try {
    $stmt = $conexion->prepare(
        'UPDATE usuarios
         SET archivado_en = CURRENT_TIMESTAMP
         WHERE id = :id
           AND archivado_en IS NULL'
    );
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 1) {
        poner_flash('exito', 'Usuario archivado. Podés reactivarlo desde "Archivados".');
    } else {
        poner_flash('error', 'El usuario no existe o ya estaba archivado.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    poner_flash('error', 'Ocurrió un error al archivar el usuario.');
}

redirigir('admin_users.php');
