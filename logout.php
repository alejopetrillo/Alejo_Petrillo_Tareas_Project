<?php

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validar_csrf()) {
    redirigir('index.php');
}

// 1 — Vaciar los datos de la sesión
$_SESSION = [];

// 2 — Borrar la cookie de sesión del navegador
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// 3 — Destruir la sesión en el servidor
session_destroy();

// Sesión nueva solo para poder mostrar el aviso en el login
session_start();
session_regenerate_id(true);
poner_flash('info', 'Cerraste sesión correctamente.');
redirigir('index.php');
