<?php
session_start();

// Seguridad: Solo administrador
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

require '../conexion.php';
require '../header2.php';

// --- AUTOCREACIÓN DE TABLA SI NO EXISTE ---
$query_create = "CREATE TABLE IF NOT EXISTS `intentos_denegados` (
  `id_intento` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(10) NOT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  `camara` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_intento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conexion, $query_create);
// ------------------------------------------

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
if (!empty($filtro_hora)) {
    $where_clauses[] = "TIME(fecha_hora) = '$filtro_hora'";
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
        <form action="" method="GET" class="filtro-form">
            <div class="filtro-input">
                <label for="matricula" class="filtro-label">Matrícula:</label>
                <input type="text" name="matricula" id="matricula" value="<?php echo htmlspecialchars($filtro_matricula); ?>" placeholder="Ej: 1234ABC" class="filtro-input">
            </div>
            <div class="filtro-input">
                <label for="fecha" class="filtro-label">Fecha:</label>
                <input type="date" name="fecha" id="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>" class="filtro-input">
            </div>
            <div class="filtro-input">
                <label for="hora" class="filtro-label">Hora:</label>
                <input type="time" name="hora" id="hora" value="<?php echo isset($_GET['hora']) ? htmlspecialchars($_GET['hora']) : ''; ?>" class="filtro-input">
            </div>
            <div class="filtro-buttons">
                <button type="submit" class="btn-blue">Filtrar</button>
                <a href="ver_intentos.php" class="btn-gray">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="tabla-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Matrícula Detectada</th>
                    <th>Cámara / Origen</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($resultado && mysqli_num_rows($resultado) > 0) {
                    while ($fila = mysqli_fetch_assoc($resultado)) {
                        $fecha = date("d/m/Y", strtotime($fila['fecha_hora']));
                        $hora = date("H:i:s", strtotime($fila['fecha_hora']));
                        $camara = htmlspecialchars($fila['camara']);

                        echo "<tr>
                          <td>$fecha</td>
                          <td>$hora</td>
                          <td><strong style='color:var(--color-peligro);'>{$fila['matricula']}</strong></td>
                          <td>$camara</td>
                          <td><span class='estado-salida'>DENEGADO</span></td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center;'>No se han registrado intentos no autorizados.</td></tr>";
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
