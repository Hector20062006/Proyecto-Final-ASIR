<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
require '../header2.php'; 
require '../conexion.php';
?>

<div class="container">
    <h2>Historial de Entradas y Salidas</h2>
    <p>Registro en tiempo real de los movimientos del parking.</p>

    <?php
    // Súper consulta cruzando las 3 tablas para tener toda la info
    // Ordenamos por fecha_hora DESC para que lo más reciente salga arriba del todo
    $sql = "SELECT a.fecha_hora, a.tipo_movimiento, a.matricula, u.nombre, u.apellidos 
            FROM accesos a
            LEFT JOIN vehiculos v ON a.matricula = v.matricula
            LEFT JOIN usuarios u ON v.dni_usuario = u.dni
            ORDER BY a.fecha_hora DESC";
            
    $resultado = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($resultado) > 0) {
        echo "<div class='tabla-responsive'>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Movimiento</th>
                            <th>Matrícula</th>
                            <th>Propietario</th>
                        </tr>
                    </thead>
                    <tbody>";
            
        while ($fila = mysqli_fetch_assoc($resultado)) {
            // Un pequeño truco visual para poner colores a ENTRADA y SALIDA
            $color_movimiento = ($fila['tipo_movimiento'] == 'ENTRADA') ? 'color: #27ae60; font-weight: bold;' : 'color: #c0392b; font-weight: bold;';
            
            // Si la matrícula no está registrada en el sistema, mostramos un aviso
            $propietario = ($fila['nombre']) ? $fila['nombre'] . " " . $fila['apellidos'] : "<span style='color: #f39c12;'>Vehículo No Registrado</span>";

            // Formateamos la fecha para que se lea mejor en España (Día/Mes/Año Hora:Minuto)
            $fecha_formateada = date("d/m/Y H:i:s", strtotime($fila['fecha_hora']));

            echo "<tr>
                    <td>{$fecha_formateada}</td>
                    <td style='{$color_movimiento}'>{$fila['tipo_movimiento']}</td>
                    <td><strong>{$fila['matricula']}</strong></td>
                    <td>{$propietario}</td>
                  </tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<p>Aún no hay registros de accesos en el parking.</p>";
    }

    mysqli_close($conexion);
    ?>

    <br>
    <a href="index.php"><button type="button" style="background-color: #95a5a6;">Volver al Inicio</button></a>
</div>

<?php require '../footer2.php'; ?>