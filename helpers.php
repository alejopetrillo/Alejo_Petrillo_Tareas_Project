<?php

require_once __DIR__ . '/connection.php';

const NOMBRE_SITIO = 'Huellitas';

if (session_status() === PHP_SESSION_NONE) {
  
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,                              
        'path'     => '/',
        'httponly' => true,                           
        'samesite' => 'Lax',                          
        'secure'   => !empty($_SERVER['HTTPS']),     
    ]);
    session_start();
}


function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function leer_post(string $campo, bool $recortar = true): string {
    $valor = $_POST[$campo] ?? '';
    if (!is_string($valor)) {
        return '';
    }
    return $recortar ? trim($valor) : $valor;
}


function leer_get(string $campo): string {
    $valor = $_GET[$campo] ?? '';
    return is_string($valor) ? trim($valor) : '';
}


function leer_id(int $tipo, string $campo): ?int {
    $id = filter_input($tipo, $campo, FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id < 1) {
        return null;
    }
    return $id;
}



function redirigir(string $url): never {
    header('Location: ' . $url);
    exit;
}


function poner_flash(string $tipo, string $mensaje): void {
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}


function mostrar_flash(): void {
    foreach ($_SESSION['flash'] ?? [] as $flash) {
        echo '<div class="alerta ' . escapar($flash['tipo']) . '">'
           . escapar($flash['mensaje']) . '</div>';
    }
    unset($_SESSION['flash']);
}


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


function token_csrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


function campo_csrf(): string {
    return '<input type="hidden" name="csrf_token" value="' . escapar(token_csrf()) . '">';
}


function validar_csrf(): bool {
    $recibido = $_POST['csrf_token'] ?? '';
    return is_string($recibido)
        && $recibido !== ''
        && hash_equals(token_csrf(), $recibido);
}


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
      
        unset($_SESSION['usuario_id'], $_SESSION['usuario_rol']);
        return null;
    }
    $_SESSION['usuario_rol'] = $usuario['rol'];
    return $usuario;
}


function exigir_login(PDO $conexion): array {
    $usuario = obtener_usuario_actual($conexion);
    if ($usuario === null) {
        redirigir('index.php');
    }
    return $usuario;
}


function exigir_admin(PDO $conexion): array {
    $usuario = exigir_login($conexion);
    if ($usuario['rol'] !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }
    return $usuario;
}


function exigir_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Método no permitido.');
    }
}


function validar_datos_usuario(string $nombre, string $apellido, string $dni, string $email): array {
    $errores = [];
    
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
