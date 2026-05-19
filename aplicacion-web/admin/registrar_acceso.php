<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $matricula = trim($_POST['matricula']);
    $tipo_movimiento = $_POST['tipo_movimiento']; // Guardará la palabra 'ENTRADA' o 'SALIDA'

    // Evitamos que intenten enviar el formulario vacío
    if (empty($matricula)) {
        header("Location: control_barrera.php");
        exit;
    }

    // Usamos la hora de Madrid en el servidor PHP
    $fecha_hora = date('Y-m-d H:i:s');
    $sql = "INSERT INTO accesos (matricula, fecha_hora, tipo_movimiento) 
            VALUES ('$matricula', '$fecha_hora', '$tipo_movimiento')";

    require '../header2.php'; // Incluimos el header para que el mensaje de éxito se vea bonito

    echo "<div class='container' style='text-align: center;'>";
    
    if (mysqli_query($conexion, $sql)) {
        if ($tipo_movimiento === 'ENTRADA') {
            echo "<h2 style='color: #27ae60;'>🟢 ¡Acceso Permitido!</h2>";
            echo "<p>El vehículo con matrícula <strong>$matricula</strong> acaba de ENTRAR al parking.</p>";
        } else {
            echo "<h2 style='color: var(--color-peligro);'>🔴 ¡Salida Registrada!</h2>";
            echo "<p>El vehículo con matrícula <strong>$matricula</strong> acaba de SALIR del parking.</p>";
        }
    } else {
        echo "<h2 style='color: red;'>Error en la barrera</h2>";
        echo "<p>" . mysqli_error($conexion) . "</p>";
    }

    echo "<br><a href='control_barrera.php'><button>Volver a la barrera</button></a>";
    echo "</div>";

    require '../footer2.php';
    
} else {
    // Si entran por URL en vez de pulsando el botón, los expulsamos a la barrera
    header("Location: control_barrera.php");
}
?>