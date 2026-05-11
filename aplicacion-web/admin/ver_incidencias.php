<?php
session_start();

// Seguridad: Solo administrador
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

require '../conexion.php';
require '../header2.php';

// Inicializar variables de filtro
$filtro_matricula = isset($_GET['matricula']) ? mysqli_real_escape_string($conexion, trim($_GET['matricula'])) : '';
$filtro_dueno = isset($_GET['dueno']) ? mysqli_real_escape_string($conexion, trim($_GET['dueno'])) : '';
$filtro_fecha = isset($_GET['fecha']) ? mysqli_real_escape_string($conexion, trim($_GET['fecha'])) : '';

// Construir la consulta con filtros
$where_clauses = [];
if (!empty($filtro_matricula)) {
    $where_clauses[] = "r.matricula LIKE '%$filtro_matricula%'";
}
if (!empty($filtro_dueno)) {
    $where_clauses[] = "(u_due.nombre LIKE '%$filtro_dueno%' OR u_due.apellidos LIKE '%$filtro_dueno%')";
}
if (!empty($filtro_fecha)) {
    $where_clauses[] = "DATE(r.fecha_hora) = '$filtro_fecha'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Consultar todas las incidencias/reportes con filtros
$sql = "SELECT r.*, u_rep.nombre AS nombre_reportador, u_rep.apellidos AS apellidos_reportador, 
               u_due.nombre AS nombre_dueno, u_due.apellidos AS apellidos_dueno
        FROM reportes_mal_aparcado r
        JOIN usuarios u_rep ON r.dni_reportador = u_rep.dni
        JOIN vehiculos v ON r.matricula = v.matricula
        JOIN usuarios u_due ON v.dni_usuario = u_due.dni
        $where_sql
        ORDER BY r.fecha_hora DESC";

$resultado = mysqli_query($conexion, $sql);
?>

<div class="container">
    <h2>Gestión de Incidencias de Parking</h2>
    <p>Listado de vehículos reportados por mal estacionamiento.</p>

    <!-- Formulario de Filtros -->
    <div class="filtro-form" style="margin-bottom: 30px; background: #f8f9fa; padding: 20px; border-radius: 12px; border: 1px solid #dee2e6;">
        <form action="" method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; font-size:14px;">Matrícula:</label>
                <input type="text" name="matricula" value="<?php echo htmlspecialchars($filtro_matricula); ?>" placeholder="Ej: 1234ABC" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; font-size:14px;">Propietario:</label>
                <input type="text" name="dueno" value="<?php echo htmlspecialchars($filtro_dueno); ?>" placeholder="Nombre o apellidos" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; font-size:14px;">Fecha:</label>
                <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-blue" style="padding: 9px 20px;">Filtrar</button>
                <a href="ver_incidencias.php" class="btn-gray" style="padding: 9px 20px; text-decoration:none; display:inline-block; font-size:14px; text-align:center;">Limpiar</a>
            </div>
        </form>
    </div>

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
