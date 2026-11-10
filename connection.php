<?php
/**
 * connection.php — Conexión a la base de datos con PDO
 *
 * Se incluye con require_once desde helpers.php, por lo que
 * todas las páginas obtienen la variable $conexion.
 *
 * Datos para XAMPP local: usuario root y contraseña vacía.
 * En un servidor real hay que usar credenciales propias.
 */
$host     = 'localhost';
$dbname   = 'sistema_usuarios';
$usuario  = 'root';
$password = '';

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $usuario,
        $password
    );
    // Si hay un error SQL, PDO lanza una excepción (la capturamos con try/catch).
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Cada fila vuelve como array asociativo: $fila['email'].
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Consultas preparadas reales en el servidor MySQL (no emuladas por PHP).
    $conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // El detalle técnico va al log; al usuario solo un mensaje genérico.
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos.');
}
