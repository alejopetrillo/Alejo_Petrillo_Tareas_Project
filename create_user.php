<?php

require_once __DIR__ . '/helpers.php';

$usuario_actual = exigir_admin($conexion);

$rolesPermitidos = ['usuario', 'admin'];

$errores  = [];
$nombre   = '';
$apellido = '';
$dni      = '';
$email    = '';
$rol      = 'usuario';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = leer_post('nombre');
    $apellido     = leer_post('apellido');
    $dni          = leer_post('dni');
    $email        = strtolower(leer_post('email'));
    $rol          = leer_post('rol');
    $password     = leer_post('password', false);
    $confirmacion = leer_post('password2', false);

    if (!validar_csrf()) {
        $errores[] = 'El formulario expiró. Recargá la página e intentá de nuevo.';
    } else {
        $errores = array_merge(
            validar_datos_usuario($nombre, $apellido, $dni, $email),
            validar_password($password, $confirmacion)
        );
        if (!in_array($rol, $rolesPermitidos, true)) {
            $errores[] = 'El rol seleccionado no es válido.';
        }

        try {
            if (empty($errores)) {
                $errores = buscar_duplicados($conexion, $dni, $email);
            }

            if (empty($errores)) {
                $sql = 'INSERT INTO usuarios (nombre, apellido, dni, email, password_hash, rol)
                        VALUES (:nombre, :apellido, :dni, :email, :password_hash, :rol)';
                $stmt = $conexion->prepare($sql);
                $stmt->execute([
                    'nombre'        => $nombre,
                    'apellido'      => $apellido,
                    'dni'           => $dni,
                    'email'         => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'rol'           => $rol,
                ]);

                poner_flash('exito', 'Usuario registrado correctamente.');
                redirigir('admin_users.php');
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'El DNI o el correo ya están registrados.';
            } else {
                error_log($e->getMessage());
                $errores[] = 'Ocurrió un error al guardar los datos.';
            }
        }
    }
}

$titulo_pagina = 'Registrar usuario';
require __DIR__ . '/header.php';
?>
        <div class="cabecera-pagina">
            <h1>Registrar usuario</h1>
            <a class="btn btn-secundario btn-chico" href="admin_users.php">← Volver al listado</a>
        </div>

        <?php mostrar_errores($errores); ?>

        <section class="tarjeta tarjeta-form">
            <form action="create_user.php" method="POST" class="form-vertical">
                <?= campo_csrf() ?>

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

                <label for="rol">Rol</label>
                <select id="rol" name="rol">
                    <?php foreach ($rolesPermitidos as $opcion): ?>
                        <option value="<?= escapar($opcion) ?>"
                            <?= $rol === $opcion ? 'selected' : '' ?>>
                            <?= escapar($opcion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="password">Contraseña (mínimo 8 caracteres)</label>
                <input type="password" id="password" name="password"
                       minlength="8" maxlength="72" required>

                <label for="password2">Repetir contraseña</label>
                <input type="password" id="password2" name="password2"
                       minlength="8" maxlength="72" required>

                <button type="submit" class="btn">Registrar usuario</button>
            </form>
        </section>

