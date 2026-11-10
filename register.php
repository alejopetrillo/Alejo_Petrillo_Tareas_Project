<?php
/**
 * register.php — Registro de nuevos usuarios
 *
 * Validación en el servidor → control de duplicados (DNI y correo) →
 * password_hash() → INSERT con consulta preparada.
 * El rol siempre es 'usuario': nadie puede registrarse como admin.
 */
require_once __DIR__ . '/helpers.php';

// Quien ya inició sesión no necesita registrarse.
if (obtener_usuario_actual($conexion) !== null) {
    redirigir('dashboard.php');
}

$errores  = [];
$nombre   = '';
$apellido = '';
$dni      = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = leer_post('nombre');
    $apellido     = leer_post('apellido');
    $dni          = leer_post('dni');
    $email        = strtolower(leer_post('email'));
    $password     = leer_post('password', false);
    $confirmacion = leer_post('password2', false);

    if (!validar_csrf()) {
        $errores[] = 'El formulario expiró. Recargá la página e intentá de nuevo.';
    } else {
        $errores = array_merge(
            validar_datos_usuario($nombre, $apellido, $dni, $email),
            validar_password($password, $confirmacion)
        );

        try {
            // Solo se consulta a la base si los datos tienen formato válido.
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
                    'rol'           => 'usuario',
                ]);

                poner_flash('exito', 'Cuenta creada correctamente. Ya podés iniciar sesión.');
                redirigir('index.php');
            }
        } catch (PDOException $e) {
            // 23000 = violación de UNIQUE (por si dos registros llegan a la vez)
            if ($e->getCode() === '23000') {
                $errores[] = 'El DNI o el correo ya están registrados.';
            } else {
                error_log($e->getMessage());
                $errores[] = 'Ocurrió un error al guardar los datos.';
            }
        }
    }
}

$titulo_pagina = 'Crear cuenta';
$clase_body    = 'auth';
require __DIR__ . '/header.php';
?>
        <p class="marca-grande"> <?= escapar(NOMBRE_SITIO) ?></p>
        <h1>Crear cuenta</h1>
        <p class="subtitulo">Registrate para ayudar a un perro a encontrar hogar</p>

        <?php mostrar_errores($errores); ?>

        <form action="register.php" method="POST" class="form-vertical">
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

            <label for="password">Contraseña (mínimo 8 caracteres)</label>
            <input type="password" id="password" name="password"
                   minlength="8" maxlength="72" required>

            <label for="password2">Repetir contraseña</label>
            <input type="password" id="password2" name="password2"
                   minlength="8" maxlength="72" required>

            <button type="submit" class="btn">Registrarme</button>
        </form>

        <p class="enlace-pie">¿Ya tenés cuenta? <a href="index.php">Iniciá sesión</a></p>

