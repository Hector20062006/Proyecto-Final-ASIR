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
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS `intentos_denegados` (
  `id_intento` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(10) NOT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  `camara` varchar(50) DEFAULT NULL,
  `motivo` varchar(50) DEFAULT 'NO_AUTORIZADO',
  PRIMARY KEY (`id_intento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Inicializar variables de filtro
$filtro_matricula = isset($_GET['matricula']) ? mysqli_real_escape_string($conexion, trim($_GET['matricula'])) : '';
$filtro_fecha     = isset($_GET['fecha'])     ? mysqli_real_escape_string($conexion, trim($_GET['fecha']))     : '';
$filtro_hora      = isset($_GET['hora'])      ? mysqli_real_escape_string($conexion, trim($_GET['hora']))      : '';
$filtro_motivo    = isset($_GET['motivo'])    ? mysqli_real_escape_string($conexion, trim($_GET['motivo']))    : '';

// Construir WHERE
$where_clauses = [];
if (!empty($filtro_matricula)) $where_clauses[] = "matricula LIKE '%$filtro_matricula%'";
if (!empty($filtro_fecha))     $where_clauses[] = "DATE(fecha_hora) = '$filtro_fecha'";
if (!empty($filtro_hora))      $where_clauses[] = "HOUR(fecha_hora) = '" . (int)$filtro_hora . "'";
if (!empty($filtro_motivo))    $where_clauses[] = "motivo = '$filtro_motivo'";

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$resultado = mysqli_query($conexion, "SELECT * FROM intentos_denegados $where_sql ORDER BY fecha_hora DESC");

// Estadísticas globales (una consulta por tarjeta)
$total        = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS n FROM intentos_denegados"))['n'];
$no_autorizados = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS n FROM intentos_denegados WHERE motivo = 'NO_AUTORIZADO'"))['n'];
$duplicados   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS n FROM intentos_denegados WHERE motivo = 'ENTRADA_DUPLICADA'"))['n'];
$salidas_inv  = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS n FROM intentos_denegados WHERE motivo = 'SALIDA_SIN_ENTRADA'"))['n'];

$hay_filtros = ($filtro_matricula || $filtro_fecha || $filtro_hora || $filtro_motivo);
?>

<div class="container container-lg">
    <h2>Intentos de Acceso Denegados</h2>
    <p>Registro completo de accesos rechazados: matrículas no autorizadas, vehículos que ya están dentro intentando entrar de nuevo, y salidas sin entrada previa.</p>

    <!-- Tarjetas de resumen -->
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-num"><?php echo (int)$total; ?></div>
            <div class="stat-lbl">Total intentos</div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-num"><?php echo (int)$no_autorizados; ?></div>
            <div class="stat-lbl">No autorizados</div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-num"><?php echo (int)$duplicados; ?></div>
            <div class="stat-lbl">Entradas duplicadas</div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-num"><?php echo (int)$salidas_inv; ?></div>
            <div class="stat-lbl">Salidas sin entrada</div>
        </div>
    </div>

    <!-- Filtros -->
    <form action="" method="GET">
        <div class="filtro-form-grid">
            <div class="filtro-field">
                <label for="matricula">Matrícula</label>
                <input type="text" name="matricula" id="matricula"
                    value="<?php echo htmlspecialchars($filtro_matricula); ?>"
                    placeholder="Ej: 1234ABC">
            </div>
            <div class="filtro-field">
                <label for="motivo">Tipo de incidencia</label>
                <select name="motivo" id="motivo">
                    <option value="">Todos los tipos</option>
                    <option value="NO_AUTORIZADO"     <?php echo $filtro_motivo === 'NO_AUTORIZADO'     ? 'selected' : ''; ?>>No autorizado</option>
                    <option value="ENTRADA_DUPLICADA"  <?php echo $filtro_motivo === 'ENTRADA_DUPLICADA'  ? 'selected' : ''; ?>>Entrada duplicada</option>
                    <option value="SALIDA_SIN_ENTRADA" <?php echo $filtro_motivo === 'SALIDA_SIN_ENTRADA' ? 'selected' : ''; ?>>Salida sin entrada</option>
                </select>
            </div>
            <div class="filtro-field">
                <label for="fecha">Fecha</label>
                <input type="date" name="fecha" id="fecha"
                    value="<?php echo htmlspecialchars($filtro_fecha); ?>">
            </div>
            <div class="filtro-field">
                <label for="hora">Hora (0–23)</label>
                <input type="number" name="hora" id="hora" min="0" max="23"
                    value="<?php echo htmlspecialchars($filtro_hora); ?>"
                    placeholder="Ej: 8">
            </div>
            <div class="filtro-actions">
                <button type="submit" class="btn-blue">Filtrar</button>
                <?php if ($hay_filtros): ?>
                    <a href="ver_intentos.php"><button type="button" class="btn-gray">Limpiar</button></a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Tabla de resultados -->
    <div class="tabla-responsive">
        <table class="table-full">
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
                if ($resultado && mysqli_num_rows($resultado) > 0):
                    while ($fila = mysqli_fetch_assoc($resultado)):
                        $fecha_hora  = date("d/m/Y · H:i:s", strtotime($fila['fecha_hora']));
                        $camara      = htmlspecialchars($fila['camara'] ?? '-');
                        $motivo_raw  = $fila['motivo'] ?? 'NO_AUTORIZADO';
                        $matricula   = htmlspecialchars($fila['matricula']);

                        switch ($motivo_raw) {
                            case 'ENTRADA_DUPLICADA':
                                $badge     = "<span class='badge-duplicado'>Entrada duplicada</span>";
                                $row_class = "row-duplicado";
                                break;
                            case 'SALIDA_SIN_ENTRADA':
                                $badge     = "<span class='badge-salida-inv'>Salida sin entrada</span>";
                                $row_class = "row-salida-inv";
                                break;
                            default:
                                $badge     = "<span class='badge-no-auth'>No autorizado</span>";
                                $row_class = "";
                                break;
                        }
                ?>
                <tr class="<?php echo $row_class; ?>">
                    <td><?php echo $fecha_hora; ?></td>
                    <td><strong style="color:var(--color-peligro);"><?php echo $matricula; ?></strong></td>
                    <td><?php echo $camara; ?></td>
                    <td><?php echo $badge; ?></td>
                    <td><span class="estado-salida">DENEGADO</span></td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:28px; color:#718096;">
                        No se han registrado intentos denegados con los filtros seleccionados.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 24px;">
        <a href="index.php"><button type="button" class="btn-gray btn-gray-mt10">Volver al Panel</button></a>
    </div>
</div>

<?php require '../footer2.php'; ?>
