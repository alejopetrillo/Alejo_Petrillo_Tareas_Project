<?php

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validar_csrf()) {
    redirigir('index.php');
}


$_SESSION = [];


if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}


session_destroy();


session_start();
session_regenerate_id(true);
poner_flash('info', 'Cerraste sesión correctamente.');
redirigir('index.php');
