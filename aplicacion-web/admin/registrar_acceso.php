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

    // Validar si el vehículo ya está en el parking
    $sql_check = "SELECT matricula, tipo_movimiento FROM accesos
                  WHERE matricula = ? AND tipo_movimiento = 'ENTRADA' ORDER BY fecha_hora DESC LIMIT 1";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("s", $matricula);
    $stmt_check->execute();
    $stmt_check->store_result();

    $alerta_1 = ""; // Para entrada: "Vehículo ya dentro"
    $alerta_2 = ""; // Para salida: "Vehículo ya fuera"

    if ($stmt_check->num_rows > 0) {
        // El vehículo ya está dentro
        if ($tipo_movimiento !== 'SALIDA') {
            $alerta_1 = "El vehículo ya está dentro del parking. Se registrará su salida.";
            $tipo_movimiento = "SALIDA";
        }
    } else {
        // El vehículo no está dentro, intentar registrar una entrada
        $sql_check_salida = "SELECT matricula, tipo_movimiento FROM accesos
                             WHERE matricula = ? AND tipo_movimiento = 'SALIDA' ORDER BY fecha_hora DESC LIMIT 1";
        $stmt_check_salida = $conexion->prepare($sql_check_salida);
        $stmt_check_salida->bind_param("s", $matricula);
        $stmt_check_salida->execute();
        $stmt_check_salida->store_result();

        if ($stmt_check_salida->num_rows == 0) {
            // El vehículo no está ni dentro ni fuera, no registrar entrada
            $alerta_1 = "El vehículo no está registrado. No puede entrar.";
            // Corregir: registrar una salida por error para limpiar
            $tipo_movimiento = "SALIDA";
        } else {
            // El vehículo ya ha salido, permitir entrada
            $stmt_check_salida->close();
        }
    }
    $stmt_check->close();

    // Evitamos que intenten enviar el formulario vacío
    if (empty($matricula)) {
        header("Location: control_barrera.php");
        exit;
    }

    // Usamos la hora de Madrid en el servidor PHP
    $fecha_hora = date('Y-m-d H:i:s');
    $sql = "INSERT INTO accesos (matricula, fecha_hora, tipo_movimiento) 
            VALUES ('$matricula', '$fecha_hora', '$tipo_movimiento')";

    // Registrar alerta si hubo cambio de estado
    if ($alerta_1) {
        $sql_alerta = "INSERT INTO alertas (matricula, alerta_1, fecha_hora)
                       VALUES ('$matricula', '$alerta_1', '$fecha_hora')";
        mysqli_query($conexion, $sql_alerta);
    }

    require '../header2.php'; // Incluimos el header para que el mensaje de éxito se vea bonito

    echo "<div class='container' style='text-align: center;'>";

    if (mysqli_query($conexion, $sql)) {
        if ($tipo_movimiento === 'ENTRADA') {
            echo "<h2 style='color: #27ae60;'>🟢 ¡Acceso Permitido!</h2>";
            echo "<p>El vehículo con matrícula <strong>$matricula</strong> acaba de ENTRAR al parking.</p>";
            if ($alerta_1) {
                echo "<p style='color: orange;'>" . $alerta_1 . "</p>";
            }
        } else {
            echo "<h2 style='color: var(--color-peligro);'>🔴 ¡Salida Registrada!</h2>";
            echo "<p>El vehículo con matrícula <strong>$matricula</strong> acaba de SALIR del parking.</p>";
            if ($alerta_1) {
                echo "<p style='color: orange;'>" . $alerta_1 . "</p>";
            }
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