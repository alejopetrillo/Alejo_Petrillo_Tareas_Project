<?php
/**
 * helpers.php — Funciones compartidas por todas las páginas
 *
 * Contiene: sesión segura, escapado HTML, protección CSRF,
 * control de acceso por rol, mensajes flash y validaciones.
 */
require_once __DIR__ . '/connection.php';

const NOMBRE_SITIO = 'Huellitas';

/* ==========================================================
   SESIÓN SEGURA
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) {
    // La sesión solo se acepta si el ID lo generó el servidor.
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,                              // vive hasta cerrar el navegador
        'path'     => '/',
        'httponly' => true,                           // JavaScript no puede leer la cookie
        'samesite' => 'Lax',                          // no se envía en peticiones cruzadas
        'secure'   => !empty($_SERVER['HTTPS']),      // solo por HTTPS si está disponible
    ]);
    session_start();
}

/* ==========================================================
   SALIDA SEGURA (anti XSS)
   ========================================================== */

/** Escapa un texto antes de mostrarlo en HTML. */
function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

/* ==========================================================
   ENTRADA DE DATOS
   ========================================================== */

/** Lee un campo de $_POST como texto (ignora arrays). Por defecto aplica trim(). */
function leer_post(string $campo, bool $recortar = true): string {
    $valor = $_POST[$campo] ?? '';
    if (!is_string($valor)) {
        return '';
    }
    return $recortar ? trim($valor) : $valor;
}

/** Lee un campo de $_GET como texto (ignora arrays) y le aplica trim(). */
function leer_get(string $campo): string {
    $valor = $_GET[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/**
 * Lee un ID entero positivo desde INPUT_GET o INPUT_POST.
 * Devuelve null si falta o no es válido.
 */
function leer_id(int $tipo, string $campo): ?int {
    $id = filter_input($tipo, $campo, FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id < 1) {
        return null;
    }
    return $id;
}

/* ==========================================================
   REDIRECCIONES Y MENSAJES FLASH
   ========================================================== */

function redirigir(string $url): never {
    header('Location: ' . $url);
    exit;
}

/** Guarda un mensaje para mostrarlo una sola vez en la próxima página. */
function poner_flash(string $tipo, string $mensaje): void {
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

/** Imprime (y borra) los mensajes flash pendientes. Tipos: exito, error, info. */
function mostrar_flash(): void {
    foreach ($_SESSION['flash'] ?? [] as $flash) {
        echo '<div class="alerta ' . escapar($flash['tipo']) . '">'
           . escapar($flash['mensaje']) . '</div>';
    }
    unset($_SESSION['flash']);
}

/** Imprime una lista de errores de validación. */
function mostrar_errores(array $errores): void {
    if (empty($errores)) {
        return;
    }
    echo '<div class="alerta error"><ul>';
    foreach ($errores as $error) {
        echo '<li>' . escapar($error) . '</li>';
    }
    echo '</ul></div>';
}

/* ==========================================================
   PROTECCIÓN CSRF
   ========================================================== */

/** Devuelve el token CSRF de la sesión (lo crea si no existe). */
function token_csrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Campo oculto con el token, para pegar dentro de cada formulario POST. */
function campo_csrf(): string {
    return '<input type="hidden" name="csrf_token" value="' . escapar(token_csrf()) . '">';
}

/** Comprueba que el token recibido por POST coincida con el de la sesión. */
function validar_csrf(): bool {
    $recibido = $_POST['csrf_token'] ?? '';
    return is_string($recibido)
        && $recibido !== ''
        && hash_equals(token_csrf(), $recibido);
}

/* ==========================================================
   CONTROL DE ACCESO
   ========================================================== */

/**
 * Devuelve el usuario de la sesión actual leyéndolo de la base,
 * o null si no hay sesión o la cuenta fue archivada.
 * El rol se toma SIEMPRE de la base de datos, no de lo que
 * quedó guardado en la sesión.
 */
function obtener_usuario_actual(PDO $conexion): ?array {
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }
    $stmt = $conexion->prepare(
        'SELECT id, nombre, apellido, dni, email, rol, creado_en
         FROM usuarios
         WHERE id = :id AND archivado_en IS NULL
         LIMIT 1'
    );
    $stmt->execute(['id' => (int) $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if ($usuario === false) {
        // Usuario archivado o inexistente: se invalida la sesión.
        unset($_SESSION['usuario_id'], $_SESSION['usuario_rol']);
        return null;
    }
    $_SESSION['usuario_rol'] = $usuario['rol'];
    return $usuario;
}

/** Página privada: si no hay sesión válida, vuelve al login. */
function exigir_login(PDO $conexion): array {
    $usuario = obtener_usuario_actual($conexion);
    if ($usuario === null) {
        redirigir('index.php');
    }
    return $usuario;
}

/** Función restringida: solo el rol admin puede continuar. */
function exigir_admin(PDO $conexion): array {
    $usuario = exigir_login($conexion);
    if ($usuario['rol'] !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }
    return $usuario;
}

/** Acciones que modifican datos: solo se aceptan por POST. */
function exigir_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Método no permitido.');
    }
}

/* ==========================================================
   VALIDACIONES (siempre en el servidor)
   ========================================================== */

/** Valida nombre, apellido, DNI y correo. Devuelve la lista de errores. */
function validar_datos_usuario(string $nombre, string $apellido, string $dni, string $email): array {
    $errores = [];
    // Letras (con acentos), espacios, apóstrofe, punto y guion. Máximo 80.
    $patronNombre = '/^[\p{L}\p{M}][\p{L}\p{M}\s\'.\-]{0,79}$/u';

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (!preg_match($patronNombre, $nombre)) {
        $errores[] = 'El nombre tiene caracteres no permitidos o supera los 80 caracteres.';
    }

    if ($apellido === '') {
        $errores[] = 'El apellido es obligatorio.';
    } elseif (!preg_match($patronNombre, $apellido)) {
        $errores[] = 'El apellido tiene caracteres no permitidos o supera los 80 caracteres.';
    }

    if ($dni === '' || !preg_match('/^[0-9]{7,8}$/', $dni)) {
        $errores[] = 'El DNI debe tener 7 u 8 números.';
    }

    if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresá un correo válido.';
    }

    return $errores;
}

/**
 * Valida contraseña y confirmación.
 * Máximo 72 bytes porque bcrypt ignora lo que pase de ese largo.
 */
function validar_password(string $password, string $confirmacion): array {
    $errores = [];
    if ($password === '') {
        $errores[] = 'La contraseña es obligatoria.';
    } elseif (strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (strlen($password) > 72) {
        $errores[] = 'La contraseña no puede superar los 72 caracteres.';
    } elseif ($password !== $confirmacion) {
        $errores[] = 'Las contraseñas no coinciden.';
    }
    return $errores;
}

/**
 * Busca si el DNI o el correo ya los usa otro usuario (activo o archivado,
 * porque las restricciones UNIQUE valen para toda la tabla).
 * $excluirId sirve al editar: no se compara contra el propio registro.
 */
function buscar_duplicados(PDO $conexion, string $dni, string $email, int $excluirId = 0): array {
    $stmt = $conexion->prepare(
        'SELECT dni, email FROM usuarios
         WHERE (dni = :dni OR email = :email) AND id <> :id'
    );
    $stmt->execute(['dni' => $dni, 'email' => $email, 'id' => $excluirId]);

    $errores = [];
    foreach ($stmt->fetchAll() as $fila) {
        if ($fila['dni'] === $dni) {
            $errores['dni'] = 'Ese DNI ya está registrado.';
        }
        if (strcasecmp($fila['email'], $email) === 0) {
            $errores['email'] = 'Ese correo ya está registrado.';
        }
    }
    return array_values($errores);
}
