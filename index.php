<?php

require_once __DIR__ . '/helpers.php';


$usuario_actual = obtener_usuario_actual($conexion);
if ($usuario_actual !== null) {
    redirigir($usuario_actual['rol'] === 'admin' ? 'admin_users.php' : 'dashboard.php');
}
$usuario_actual = null;

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(leer_post('email'));
    $password = leer_post('password', false); // la contraseña no se recorta

    if (!validar_csrf()) {
        $error = 'El formulario expiró. Recargá la página e intentá de nuevo.';
    } else {
        try {
            // 1 — Consulta preparada para buscar al usuario (solo activos)
            $sql = 'SELECT id, nombre, email, password_hash, rol
                    FROM usuarios
                    WHERE email = :email
                    AND archivado_en IS NULL
                    LIMIT 1';
            $stmt = $conexion->prepare($sql);
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch();

           
            $hash = $usuario !== false
                ? $usuario['password_hash']
                : password_hash('relleno', PASSWORD_DEFAULT);

            if (password_verify($password, $hash) && $usuario !== false) {
                session_regenerate_id(true); 
                $_SESSION['usuario_id']  = (int) $usuario['id'];
                $_SESSION['usuario_rol'] = $usuario['rol'];

               
                redirigir($usuario['rol'] === 'admin' ? 'admin_users.php' : 'dashboard.php');
            }

          
            $error = 'Correo o contraseña incorrectos. Intentá de nuevo.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Ocurrió un error. Intentá de nuevo más tarde.';
        }
    }
}

$titulo_pagina = 'Iniciar sesión';
$clase_body    = 'auth';
require __DIR__ . '/header.php';
?>
        <p class="marca-grande"> <?= escapar(NOMBRE_SITIO) ?></p>
        <h1>Iniciar sesión</h1>
        <p class="subtitulo">Ingresá con tu cuenta para continuar</p>

        <?php mostrar_flash(); ?>
        <?php if ($error !== ''): ?>
            <div class="alerta error"><?= escapar($error) ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST" class="form-vertical">
            <?= campo_csrf() ?>

            <label for="email">Correo electrónico</label>
            <input type="email"
                   id="email"
                   name="email"
                   maxlength="254"
                   value="<?= escapar($email) ?>"
                   required>

            <label for="password">Contraseña</label>
            <input type="password"
                   id="password"
                   name="password"
                   required>

            <button type="submit" class="btn">Iniciar sesión</button>
        </form>

        <p class="enlace-pie">¿No tenés cuenta? <a href="register.php">Registrate</a></p>

