<?php

require_once __DIR__ . '/helpers.php';

$usuario_actual = exigir_admin($conexion);


$id = leer_id(INPUT_GET, 'id');
if ($id === null) {
    http_response_code(400);
    exit('Identificador inválido.');
}


try {
    $stmt = $conexion->prepare(
        'SELECT id, nombre, apellido, dni, email
         FROM usuarios
         WHERE id = :id AND archivado_en IS NULL
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $editado = $stmt->fetch();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Ocurrió un error. Intentá de nuevo más tarde.');
}

if ($editado === false) {
    http_response_code(404);
    exit('Usuario no encontrado o archivado.');
}

$erroresDatos = [];
$erroresPass  = [];
$nombre   = $editado['nombre'];
$apellido = $editado['apellido'];
$dni      = $editado['dni'];
$email    = $editado['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = leer_post('accion');

    if (!validar_csrf()) {
        $erroresDatos[] = 'El formulario expiró. Recargá la página e intentá de nuevo.';

    } elseif ($accion === 'datos') {
      
        $nombre   = leer_post('nombre');
        $apellido = leer_post('apellido');
        $dni      = leer_post('dni');
        $email    = strtolower(leer_post('email'));

        $erroresDatos = validar_datos_usuario($nombre, $apellido, $dni, $email);

        try {
            if (empty($erroresDatos)) {
                
                $erroresDatos = buscar_duplicados($conexion, $dni, $email, $id);
            }
            if (empty($erroresDatos)) {
               
                $sql = 'UPDATE usuarios
                        SET nombre = :nombre,
                            apellido = :apellido,
                            dni = :dni,
                            email = :email
                        WHERE id = :id
                          AND archivado_en IS NULL';
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    'nombre'   => $nombre,
                    'apellido' => $apellido,
                    'dni'      => $dni,
                    'email'    => $email,
                    'id'       => $id,
                ]);

                poner_flash('exito', 'Los datos del usuario se actualizaron correctamente.');
                redirigir('admin_users.php');
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erroresDatos[] = 'El DNI o el correo ya están registrados.';
            } else {
                error_log($e->getMessage());
                $erroresDatos[] = 'Ocurrió un error al guardar los cambios.';
            }
        }

    } elseif ($accion === 'password') {
        
        $password     = leer_post('password', false);
        $confirmacion = leer_post('password2', false);

        $erroresPass = validar_password($password, $confirmacion);

        try {
            if (empty($erroresPass)) {
                $stmt = $conexion->prepare(
                    'UPDATE usuarios
                     SET password_hash = :password_hash
                     WHERE id = :id AND archivado_en IS NULL'
                );
                $stmt->execute([
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'id'            => $id,
                ]);

                poner_flash('exito', 'La contraseña se cambió correctamente.');
                redirigir('admin_users.php');
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erroresPass[] = 'Ocurrió un error al cambiar la contraseña.';
        }

    } else {
        $erroresDatos[] = 'Acción no válida.';
    }
}

$titulo_pagina = 'Editar usuario';
require __DIR__ . '/header.php';
?>
        <div class="cabecera-pagina">
            <h1>Editar usuario</h1>
            <a class="btn btn-secundario btn-chico" href="admin_users.php">← Volver al listado</a>
        </div>

        <section class="tarjeta tarjeta-form">
            <h2>Datos personales</h2>
            <?php mostrar_errores($erroresDatos); ?>

            <form action="edit_user.php?id=<?= (int) $id ?>" method="POST" class="form-vertical">
                <?= campo_csrf() ?>
                <input type="hidden" name="accion" value="datos">

                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="80"
                       value="<?= escapar($nombre) ?>" required>

                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" maxlength="80"
                       value="<?= escapar($apellido) ?>" required>

                <label for="dni">DNI</label>
                <input type="text" id="dni" name="dni" inputmode="numeric"
                       pattern="[0-9]{7,8}" maxlength="8"
                       value="<?= escapar($dni) ?>" required>

                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" maxlength="254"
                       value="<?= escapar($email) ?>" required>

                <button type="submit" class="btn">Guardar cambios</button>
            </form>
        </section>

        <section class="tarjeta tarjeta-form">
            <h2>Cambiar contraseña</h2>
            <?php mostrar_errores($erroresPass); ?>

            <form action="edit_user.php?id=<?= (int) $id ?>" method="POST" class="form-vertical">
                <?= campo_csrf() ?>
                <input type="hidden" name="accion" value="password">

                <label for="password">Nueva contraseña (mínimo 8 caracteres)</label>
                <input type="password" id="password" name="password"
                       minlength="8" maxlength="72" required>

                <label for="password2">Repetir nueva contraseña</label>
                <input type="password" id="password2" name="password2"
                       minlength="8" maxlength="72" required>

                <button type="submit" class="btn btn-secundario">Cambiar contraseña</button>
            </form>
        </section>

