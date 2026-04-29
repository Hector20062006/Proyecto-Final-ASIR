<?php
// api_camara.php

// 1. Conectamos a la base de datos (asegúrate de que la ruta es correcta)
require 'conexion.php'; 

// 2. Comprobamos que los datos nos llegan por el método POST (el que usan los programas para enviar info oculta)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recogemos los datos que mande la cámara. Si no manda nada, ponemos un texto vacío
    $matricula = isset($_POST['matricula']) ? trim($_POST['matricula']) : '';
    $tipo_movimiento = isset($_POST['tipo_movimiento']) ? trim($_POST['tipo_movimiento']) : '';

    // 3. Comprobación de seguridad básica
    if (empty($matricula) || empty($tipo_movimiento)) {
        echo "ERROR: Faltan datos. Necesito 'matricula' y 'tipo_movimiento'.";
        exit;
    }

    // Asegurarnos de que el movimiento es correcto
    $tipo_movimiento = strtoupper($tipo_movimiento);
    if ($tipo_movimiento !== 'ENTRADA' && $tipo_movimiento !== 'SALIDA') {
        echo "ERROR: El tipo_movimiento solo puede ser ENTRADA o SALIDA.";
        exit;
    }

    // 4. Guardamos en la base de datos (NOW() pone la hora exacta automáticamente)
    $sql = "INSERT INTO accesos (matricula, fecha_hora, tipo_movimiento) 
            VALUES ('$matricula', NOW(), '$tipo_movimiento')";

    if (mysqli_query($conexion, $sql)) {
        // Le devolvemos un "OK" a la cámara de tu compañero para que sepa que ha funcionado
        echo "OK";
    } else {
        // Si hay un fallo en la base de datos, se lo decimos
        echo "ERROR DB: " . mysqli_error($conexion);
    }

} else {
    // Si alguien intenta abrir este archivo desde el navegador (GET), le damos un aviso
    echo "ERROR: Este archivo solo acepta peticiones POST desde la cámara.";
}

mysqli_close($conexion);
?>