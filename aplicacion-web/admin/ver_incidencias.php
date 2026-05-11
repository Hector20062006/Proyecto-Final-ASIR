<?php
session_start();

// Seguridad: Solo administrador
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

require '../conexion.php';
require '../header2.php';

// Consultar todas las incidencias/reportes
$sql = "SELECT r.*, u_rep.nombre AS nombre_reportador, u_rep.apellidos AS apellidos_reportador, 
               u_due.nombre AS nombre_dueno, u_due.apellidos AS apellidos_dueno
        FROM reportes_mal_aparcado r
        JOIN usuarios u_rep ON r.dni_reportador = u_rep.dni
        JOIN vehiculos v ON r.matricula = v.matricula
        JOIN usuarios u_due ON v.dni_usuario = u_due.dni
        ORDER BY r.fecha_hora DESC";

$resultado = mysqli_query($conexion, $sql);
?>

<div class="container">
    <h2>Gestión de Incidencias de Parking</h2>
    <p>Listado de vehículos reportados por mal estacionamiento.</p>

    <div class="tabla-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Matrícula</th>
                    <th>Propietario del Vehículo</th>
                    <th>Reportado por</th>
                    <th>Motivo / Mensaje</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($resultado) > 0) {
                    while ($fila = mysqli_fetch_assoc($resultado)) {
                        $fecha = date("d/m/Y H:i", strtotime($fila['fecha_hora']));
                        $dueno = $fila['nombre_dueno'] . " " . $fila['apellidos_dueno'];
                        $reportador = $fila['nombre_reportador'] . " " . $fila['apellidos_reportador'];
                        $motivo = !empty($fila['motivo']) ? htmlspecialchars($fila['motivo']) : "<i>Sin mensaje adicional</i>";
                        
                        echo "<tr>
                                <td>$fecha</td>
                                <td><strong>{$fila['matricula']}</strong></td>
                                <td>$dueno</td>
                                <td>$reportador</td>
                                <td>$motivo</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center;'>No hay incidencias registradas.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 30px; text-align: center;">
        <a href="index.php"><button class="btn-gray">Volver al Panel</button></a>
    </div>
</div>

<?php require '../footer2.php'; ?>
