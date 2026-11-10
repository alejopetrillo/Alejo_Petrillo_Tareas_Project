<?php
/**
 * edit_user.php — Edición de un usuario activo (solo admin)
 *
 * Recibe el ID por la URL (?id=3), lo valida como entero y carga el registro.
 * Tiene dos formularios independientes:
 *   - accion=datos     → nombre, apellido, DNI y correo
 *   - accion=password  → cambio de contraseña (operación separada)
 * El rol NO se puede modificar desde acá, así nadie escala privilegios
 * manipulando los campos del formulario.
 */
require_once __DIR__ . '/helpers.php';

$usuario_actual = exigir_admin($conexion);

// 1 — Validar el identificador
$id = leer_id(INPUT_GET, 'id');
if ($id === null) {
    http_response_code(400);
    exit('Identificador inválido.');
}

// 2 — Cargar únicamente usuarios activos
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
        // ---------- Actualizar datos personales ----------
        $nombre   = leer_post('nombre');
        $apellido = leer_post('apellido');
        $dni      = leer_post('dni');
        $email    = strtolower(leer_post('email'));

        $erroresDatos = validar_datos_usuario($nombre, $apellido, $dni, $email);

        try {
            if (empty($erroresDatos)) {
                // Duplicados contra OTROS usuarios (se excluye el propio id)
                $erroresDatos = buscar_duplicados($conexion, $dni, $email, $id);
            }
            if (empty($erroresDatos)) {
                // WHERE estricto: solo este usuario, y solo si sigue activo
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
        // ---------- Cambiar contraseña ----------
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
<?php require __DIR__ . '/footer.php'; ?>
