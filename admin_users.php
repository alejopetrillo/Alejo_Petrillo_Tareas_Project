<?php

require_once __DIR__ . '/helpers.php';

$usuario_actual = exigir_admin($conexion);

// Criterio de búsqueda: se limpia y se limita a 100 caracteres.
$buscar = mb_substr(leer_get('buscar'), 0, 100);

$usuarios = [];
$errorConsulta = '';

try {
    $sql = 'SELECT id, nombre, apellido, dni, email, rol, creado_en
            FROM usuarios
            WHERE archivado_en IS NULL';
    $parametros = [];

    if ($buscar !== '') {
        $sql .= ' AND (nombre LIKE :nombre OR apellido LIKE :apellido
                  OR dni LIKE :dni OR email LIKE :email)';
        // addcslashes: los % y _ que escriba el usuario se toman como texto literal.
        $termino = '%' . addcslashes($buscar, '%_\\') . '%';
        $parametros = [
            'nombre'   => $termino,
            'apellido' => $termino,
            'dni'      => $termino,
            'email'    => $termino,
        ];
    }
    $sql .= ' ORDER BY apellido, nombre';

    $stmt = $conexion->prepare($sql);
    $stmt->execute($parametros);
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorConsulta = 'No se pudo obtener el listado de usuarios.';
}

$titulo_pagina = 'Usuarios';
require __DIR__ . '/header.php';
?>
        <div class="cabecera-pagina">
            <h1>Usuarios activos</h1>
            <div>
                <a class="btn btn-chico" href="create_user.php">+ Registrar usuario</a>
                <a class="btn btn-secundario btn-chico" href="archived_users.php">Ver archivados</a>
            </div>
        </div>

        <?php mostrar_flash(); ?>
        <?php if ($errorConsulta !== ''): ?>
            <div class="alerta error"><?= escapar($errorConsulta) ?></div>
        <?php endif; ?>

        <form method="GET" action="admin_users.php" class="busqueda">
            <input type="search" name="buscar" maxlength="100"
                   placeholder="Nombre, apellido, DNI o correo"
                   value="<?= escapar($buscar) ?>">
            <button type="submit" class="btn">Buscar</button>
            <?php if ($buscar !== ''): ?>
                <a class="btn btn-secundario" href="admin_users.php">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (empty($usuarios)): ?>
            <p class="vacio">No se encontraron usuarios.</p>
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
                            <th>Rol</th>
                            <th>Estado</th>
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
                            <td>
                                <span class="badge <?= $usuario['rol'] === 'admin' ? 'badge-admin' : '' ?>">
                                    <?= escapar($usuario['rol']) ?>
                                </span>
                            </td>
                            <td><span class="estado-activo">● Activo</span></td>
                            <td class="acciones">
                                <a class="btn btn-secundario btn-chico"
                                   href="edit_user.php?id=<?= (int) $usuario['id'] ?>">Editar</a>

                                <?php if ((int) $usuario['id'] !== (int) $usuario_actual['id']): ?>
                                    <!-- Archivar: POST + CSRF + confirmación previa -->
                                    <form action="archive_user.php" method="POST" class="inline"
                                          onsubmit="return confirm('¿Archivar a este usuario? Podrás reactivarlo más tarde.');">
                                        <?= campo_csrf() ?>
                                        <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                        <button type="submit" class="btn btn-peligro btn-chico">Archivar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="nota">(tu cuenta)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="nota"><?= count($usuarios) ?> usuario(s) mostrado(s).</p>
        <?php endif; ?>

