<?php
session_start();

// Seguridad: Solo administrador
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

require '../conexion.php';
require '../header2.php';

// --- MIGRACIÓN AUTOMÁTICA: añadir columna 'motivo' si no existe (compatible MariaDB 10.4) ---
$col_check = mysqli_query($conexion,
    "SELECT COUNT(*) AS existe
     FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'intentos_denegados'
       AND COLUMN_NAME  = 'motivo'"
);
$col_row = mysqli_fetch_assoc($col_check);
if ((int)$col_row['existe'] === 0) {
    mysqli_query($conexion,
        "ALTER TABLE intentos_denegados ADD COLUMN motivo VARCHAR(50) DEFAULT 'NO_AUTORIZADO'"
    );
}

// --- AUTOCREACIÓN DE TABLA SI NO EXISTE ---
$query_create = "CREATE TABLE IF NOT EXISTS `intentos_denegados` (
  `id_intento` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(10) NOT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  `camara` varchar(50) DEFAULT NULL,
  `motivo` varchar(50) DEFAULT 'NO_AUTORIZADO',
  PRIMARY KEY (`id_intento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conexion, $query_create);
// ------------------------------------------

// Inicializar variables de filtro
$filtro_matricula = isset($_GET['matricula']) ? mysqli_real_escape_string($conexion, trim($_GET['matricula'])) : '';
$filtro_fecha     = isset($_GET['fecha'])     ? mysqli_real_escape_string($conexion, trim($_GET['fecha']))     : '';
$filtro_hora      = isset($_GET['hora'])      ? mysqli_real_escape_string($conexion, trim($_GET['hora']))      : '';
$filtro_motivo    = isset($_GET['motivo'])    ? mysqli_real_escape_string($conexion, trim($_GET['motivo']))    : '';

// Construir la consulta con filtros
$where_clauses = [];
if (!empty($filtro_matricula)) {
    $where_clauses[] = "matricula LIKE '%$filtro_matricula%'";
}
if (!empty($filtro_fecha)) {
    $where_clauses[] = "DATE(fecha_hora) = '$filtro_fecha'";
}
if (!empty($filtro_hora)) {
    $where_clauses[] = "HOUR(fecha_hora) = '" . (int)$filtro_hora . "'";
}
if (!empty($filtro_motivo)) {
    $where_clauses[] = "motivo = '$filtro_motivo'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Consultar intentos denegados
$sql = "SELECT * FROM intentos_denegados $where_sql ORDER BY fecha_hora DESC";
$resultado = mysqli_query($conexion, $sql);

// Contadores por tipo (sin filtros de usuario para los stats globales)
$stats = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT
    COUNT(*) AS total,
    SUM(motivo = 'NO_AUTORIZADO')    AS no_autorizados,
    SUM(motivo = 'ENTRADA_DUPLICADA') AS duplicados,
    SUM(motivo = 'SALIDA_SIN_ENTRADA') AS salidas_inv
    FROM intentos_denegados"));
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.stat-card {
    background: var(--color-fondo-card, #fff);
    border-radius: 10px;
    padding: 18px 20px;
    text-align: center;
    border-left: 5px solid transparent;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.stat-card .stat-num { font-size: 2rem; font-weight: 700; line-height: 1; }
.stat-card .stat-lbl { font-size: .82rem; margin-top: 4px; color: #666; }
.stat-total   { border-color: #6c757d; }
.stat-total   .stat-num { color: #495057; }
.stat-danger  { border-color: #e74c3c; }
.stat-danger  .stat-num { color: #e74c3c; }
.stat-warning { border-color: #f39c12; }
.stat-warning .stat-num { color: #f39c12; }
.stat-info    { border-color: #3498db; }
.stat-info    .stat-num { color: #3498db; }

/* Badges de motivo */
.badge-no-auth   { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; background:#fde8e8; color:#c0392b; }
.badge-duplicado { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; background:#fff3cd; color:#856404; }
.badge-salida-inv{ display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; background:#d1ecf1; color:#0c5460; }
.badge-unknown   { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; background:#e2e3e5; color:#383d41; }

/* Fila resaltada por tipo */
tr.row-duplicado { background: #fffbeb !important; }
tr.row-salida-inv { background: #ebf5fb !important; }
</style>

<div class="container">
    <h2>🚫 Intentos de Acceso Denegados</h2>
    <p>Registro completo de accesos rechazados: matrículas no autorizadas, vehículos que ya están dentro intentando entrar de nuevo, y salidas sin entrada previa.</p>

    <!-- Tarjetas de resumen -->
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-num"><?php echo (int)$stats['total']; ?></div>
            <div class="stat-lbl">Total intentos</div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-num"><?php echo (int)$stats['no_autorizados']; ?></div>
            <div class="stat-lbl">No autorizados</div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-num"><?php echo (int)$stats['duplicados']; ?></div>
            <div class="stat-lbl">Entradas duplicadas</div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-num"><?php echo (int)$stats['salidas_inv']; ?></div>
            <div class="stat-lbl">Salidas sin entrada</div>
        </div>
    </div>

    <!-- Formulario de Filtros -->
    <div class="filtro-form">
        <form action="" method="GET" class="filtro-form">
            <div class="filtro-input">
                <label for="matricula" class="filtro-label">Matrícula:</label>
                <input type="text" name="matricula" id="matricula"
                    value="<?php echo htmlspecialchars($filtro_matricula); ?>"
                    placeholder="Ej: 1234ABC" class="filtro-input">
            </div>
            <div class="filtro-input">
                <label for="motivo" class="filtro-label">Tipo de incidencia:</label>
                <select name="motivo" id="motivo" class="filtro-input">
                    <option value="">-- Todos los tipos --</option>
                    <option value="NO_AUTORIZADO"    <?php echo $filtro_motivo === 'NO_AUTORIZADO'    ? 'selected' : ''; ?>>🔴 No autorizado</option>
                    <option value="ENTRADA_DUPLICADA" <?php echo $filtro_motivo === 'ENTRADA_DUPLICADA' ? 'selected' : ''; ?>>🟡 Entrada duplicada (ya dentro)</option>
                    <option value="SALIDA_SIN_ENTRADA" <?php echo $filtro_motivo === 'SALIDA_SIN_ENTRADA' ? 'selected' : ''; ?>>🔵 Salida sin entrada</option>
                </select>
            </div>
            <div class="filtro-input">
                <label for="fecha" class="filtro-label">Fecha:</label>
                <input type="date" name="fecha" id="fecha"
                    value="<?php echo htmlspecialchars($filtro_fecha); ?>" class="filtro-input">
            </div>
            <div class="filtro-input">
                <label for="hora" class="filtro-label">Hora (HH):</label>
                <input type="number" name="hora" id="hora" min="0" max="23"
                    value="<?php echo isset($_GET['hora']) ? htmlspecialchars($_GET['hora']) : ''; ?>"
                    placeholder="Ej: 8" class="filtro-input">
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
                    <th>Fecha y Hora</th>
                    <th>Matrícula</th>
                    <th>Cámara / Origen</th>
                    <th>Tipo de Incidencia</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($resultado && mysqli_num_rows($resultado) > 0) {
                    while ($fila = mysqli_fetch_assoc($resultado)) {
                        $fecha_hora = date("d/m/Y · H:i:s", strtotime($fila['fecha_hora']));
                        $camara     = htmlspecialchars($fila['camara'] ?? '-');
                        $motivo_raw = $fila['motivo'] ?? 'NO_AUTORIZADO';

                        // Badge y clase de fila según motivo
                        switch ($motivo_raw) {
                            case 'ENTRADA_DUPLICADA':
                                $badge     = "<span class='badge-duplicado'>🟡 Entrada duplicada</span>";
                                $row_class = "row-duplicado";
                                $descripcion = "Vehículo ya registrado como <strong>dentro</strong> del parking";
                                break;
                            case 'SALIDA_SIN_ENTRADA':
                                $badge     = "<span class='badge-salida-inv'>🔵 Salida sin entrada</span>";
                                $row_class = "row-salida-inv";
                                $descripcion = "Vehículo sin entrada previa registrada";
                                break;
                            case 'NO_AUTORIZADO':
                            default:
                                $badge     = "<span class='badge-no-auth'>🔴 No autorizado</span>";
                                $row_class = "";
                                $descripcion = "Matrícula no registrada en el sistema";
                                break;
                        }

                        $matricula_html = "<strong style='color:var(--color-peligro, #e74c3c);'>" . htmlspecialchars($fila['matricula']) . "</strong>";

                        echo "<tr class='$row_class' title='$descripcion'>
                            <td>$fecha_hora</td>
                            <td>$matricula_html</td>
                            <td>$camara</td>
                            <td>$badge</td>
                            <td><span class='estado-salida'>DENEGADO</span></td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding:24px;'>
                            ✅ No se han registrado intentos denegados con los filtros seleccionados.
                          </td></tr>";
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
