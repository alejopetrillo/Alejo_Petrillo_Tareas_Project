<?php

require_once __DIR__ . '/helpers.php';

$usuario_actual = exigir_admin($conexion);

$usuarios = [];
$errorConsulta = '';

try {
    $stmt = $conexion->query(
        'SELECT id, nombre, apellido, dni, email, archivado_en
         FROM usuarios
         WHERE archivado_en IS NOT NULL
         ORDER BY archivado_en DESC'
    );
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorConsulta = 'No se pudo obtener el listado de archivados.';
}

$titulo_pagina = 'Usuarios archivados';
require __DIR__ . '/header.php';
?>
        <div class="cabecera-pagina">
            <h1>Usuarios archivados</h1>
            <a class="btn btn-secundario btn-chico" href="admin_users.php">← Volver a activos</a>
        </div>

        <?php mostrar_flash(); ?>
        <?php if ($errorConsulta !== ''): ?>
            <div class="alerta error"><?= escapar($errorConsulta) ?></div>
        <?php endif; ?>

        <?php if (empty($usuarios)): ?>
            <p class="vacio">No hay usuarios archivados.</p>
        <?php else: ?>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>DNI</th>
                            <th>Correo</th>
                            <th>Archivado el</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= escapar((string) $usuario['id']) ?></td>
                            <td><?= escapar($usuario['nombre']) ?></td>
                            <td><?= escapar($usuario['apellido']) ?></td>
                            <td><?= escapar($usuario['dni']) ?></td>
                            <td><?= escapar($usuario['email']) ?></td>
                            <td><?= escapar(date('d/m/Y H:i', strtotime($usuario['archivado_en']))) ?></td>
                            <td class="acciones">
                                <form action="reactivate_user.php" method="POST" class="inline"
                                      onsubmit="return confirm('¿Reactivar a este usuario?');">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                    <button type="submit" class="btn btn-chico">Reactivar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="nota"><?= count($usuarios) ?> usuario(s) archivado(s).</p>
        <?php endif; ?>

