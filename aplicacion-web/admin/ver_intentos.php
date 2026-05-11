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
$filtro_fecha = isset($_GET['fecha']) ? mysqli_real_escape_string($conexion, trim($_GET['fecha'])) : '';

// Construir la consulta con filtros
$where_clauses = [];
if (!empty($filtro_matricula)) {
    $where_clauses[] = "matricula LIKE '%$filtro_matricula%'";
}
if (!empty($filtro_fecha)) {
    $where_clauses[] = "DATE(fecha_hora) = '$filtro_fecha'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Consultar intentos denegados
$sql = "SELECT * FROM intentos_denegados $where_sql ORDER BY fecha_hora DESC";
$resultado = mysqli_query($conexion, $sql);
?>

<div class="container">
    <h2>Intentos de Acceso No Autorizados</h2>
    <p>Historial de vehículos detectados por las cámaras que no están registrados en el sistema.</p>

    <!-- Formulario de Filtros -->
    <div class="filtro-form">
        <form action="" method="GET" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; font-size:14px;">Matrícula:</label>
                <input type="text" name="matricula" value="<?php echo htmlspecialchars($filtro_matricula); ?>" placeholder="Ej: 1234ABC" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; font-size:14px;">Fecha:</label>
                <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>" style="width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-blue" style="padding: 9px 20px;">Filtrar</button>
                <a href="ver_intentos.php" class="btn-gray" style="padding: 9px 20px; text-decoration:none; display:inline-block; font-size:14px; text-align:center;">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="tabla-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Matrícula Detectada</th>
                    <th>Cámara / Origen</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($resultado && mysqli_num_rows($resultado) > 0) {
                    while ($fila = mysqli_fetch_assoc($resultado)) {
                        $fecha = date("d/m/Y H:i:s", strtotime($fila['fecha_hora']));
                        $camara = htmlspecialchars($fila['camara']);
                        
                        echo "<tr>
                                <td>$fecha</td>
                                <td><strong style='color:var(--color-peligro);'>{$fila['matricula']}</strong></td>
                                <td>$camara</td>
                                <td><span class='estado-salida'>DENEGADO</span></td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center;'>No se han registrado intentos no autorizados.</td></tr>";
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
