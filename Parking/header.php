<?php
// Arrancamos la sesión de forma segura si no está iniciada ya
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PARKING ILIBERIS</title>
    <link rel="stylesheet" href="css/parking.css">
</head>
<body>
    <header>
        <h1> PARKING ILIBERIS </h1>
        <nav>
            <img src="img/logo_parking.png" width="700" height="300" alt="Logo Parking">
            <?php if(isset($_SESSION['username'])): ?>
                <div class="mensaje-usuario">
                    Bienvenido, <?php echo $_SESSION['username']; ?> 
                </div>
            <?php endif; ?>
        </nav>
        <hr>
    </header>
    <main>