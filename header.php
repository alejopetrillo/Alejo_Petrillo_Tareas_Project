<?php

$clase_body     = $clase_body ?? '';
$usuario_actual = $usuario_actual ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapar($titulo_pagina ?? NOMBRE_SITIO) ?> · <?= escapar(NOMBRE_SITIO) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= escapar($clase_body) ?>">
<?php if ($usuario_actual !== null): ?>
    <header class="topbar">
        <a class="marca" href="dashboard.php"> <?= escapar(NOMBRE_SITIO) ?></a>
        <nav>
            <a href="dashboard.php">Mi panel</a>
            <?php if ($usuario_actual['rol'] === 'admin'): ?>
                <a href="admin_users.php">Usuarios</a>
                <a href="archived_users.php">Archivados</a>
            <?php endif; ?>
            
            <form action="logout.php" method="POST" class="inline">
                <?= campo_csrf() ?>
                <button type="submit" class="btn btn-secundario btn-chico">Cerrar sesión</button>
            </form>
        </nav>
    </header>
<?php endif; ?>
    <main class="<?= $clase_body === 'auth' ? 'login-card' : 'contenedor' ?>">
