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
$filtro_matricula  = isset($_GET['matricula'])   ? mysqli_real_escape_string($conexion, trim($_GET['matricula']))   : '';
$filtro_motivo     = isset($_GET['motivo'])      ? mysqli_real_escape_string($conexion, trim($_GET['motivo']))      : '';
$filtro_fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$filtro_fecha_fin    = isset($_GET['fecha_fin'])    ? trim($_GET['fecha_fin'])    : '';

// Construir WHERE
$where_clauses = [];
if (!empty($filtro_matricula))   $where_clauses[] = "matricula LIKE '%$filtro_matricula%'";
if (!empty($filtro_motivo))      $where_clauses[] = "motivo = '$filtro_motivo'";
if (!empty($filtro_fecha_inicio)) {
    $fi_sql = mysqli_real_escape_string($conexion, str_replace('T', ' ', $filtro_fecha_inicio) . ':00');
    $where_clauses[] = "fecha_hora >= '$fi_sql'";
}
if (!empty($filtro_fecha_fin)) {
    $ff_sql = mysqli_real_escape_string($conexion, str_replace('T', ' ', $filtro_fecha_fin) . ':59');
    $where_clauses[] = "fecha_hora <= '$ff_sql'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$resultado = mysqli_query($conexion, "SELECT * FROM intentos_denegados $where_sql ORDER BY fecha_hora DESC");

// Estadísticas globales (una consulta por fila de resumen)
$total          = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS n FROM intentos_denegados"))['n'];
$no_autorizados = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS n FROM intentos_denegados WHERE motivo = 'NO_AUTORIZADO'"))['n'];
$duplicados     = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS n FROM intentos_denegados WHERE motivo = 'ENTRADA_DUPLICADA'"))['n'];
$salidas_inv    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS n FROM intentos_denegados WHERE motivo = 'SALIDA_SIN_ENTRADA'"))['n'];

$hay_filtros = ($filtro_matricula || $filtro_motivo || $filtro_fecha_inicio || $filtro_fecha_fin);

?>

<div class="container container-lg">
    <h2>Intentos de Acceso Denegados</h2>
    <p>Registro completo de accesos rechazados: matrículas no autorizadas, vehículos ya dentro intentando entrar de nuevo, y salidas sin entrada previa.</p>

    <!-- Tabla de resumen de totales (cada fila filtra al hacer clic) -->
    <table class="tabla-resumen">
        <thead>
            <tr>
                <th>Tipo de incidencia</th>
                <th>Total</th>
                <th>Filtrar</th>
            </tr>
        </thead>
        <tbody>
            <tr class="fila-filtro <?php echo $filtro_motivo === 'NO_AUTORIZADO' ? 'fila-filtro-activa' : ''; ?>">
                <td><a href="ver_intentos.php?motivo=NO_AUTORIZADO" class="enlace-filtro-fila"><span class="badge-no-auth">No autorizado</span> &nbsp;Matrícula no registrada en el sistema</a></td>
                <td><span class="num-danger"><?php echo (int)$no_autorizados; ?></span></td>
                <td><?php echo $filtro_motivo === 'NO_AUTORIZADO' ? '<span class="filtro-activo-badge">✔ Activo</span>' : '<a href="ver_intentos.php?motivo=NO_AUTORIZADO" class="btn-filtro-rapido">Filtrar</a>'; ?></td>
            </tr>
            <tr class="fila-filtro <?php echo $filtro_motivo === 'ENTRADA_DUPLICADA' ? 'fila-filtro-activa' : ''; ?>">
                <td><a href="ver_intentos.php?motivo=ENTRADA_DUPLICADA" class="enlace-filtro-fila"><span class="badge-duplicado">Entrada duplicada</span> &nbsp;Vehículo ya dentro intentando volver a entrar</a></td>
                <td><span class="num-warning"><?php echo (int)$duplicados; ?></span></td>
                <td><?php echo $filtro_motivo === 'ENTRADA_DUPLICADA' ? '<span class="filtro-activo-badge">✔ Activo</span>' : '<a href="ver_intentos.php?motivo=ENTRADA_DUPLICADA" class="btn-filtro-rapido">Filtrar</a>'; ?></td>
            </tr>
            <tr class="fila-filtro <?php echo $filtro_motivo === 'SALIDA_SIN_ENTRADA' ? 'fila-filtro-activa' : ''; ?>">
                <td><a href="ver_intentos.php?motivo=SALIDA_SIN_ENTRADA" class="enlace-filtro-fila"><span class="badge-salida-inv">Salida sin entrada</span> &nbsp;Vehículo sin entrada previa registrada</a></td>
                <td><span class="num-info"><?php echo (int)$salidas_inv; ?></span></td>
                <td><?php echo $filtro_motivo === 'SALIDA_SIN_ENTRADA' ? '<span class="filtro-activo-badge">✔ Activo</span>' : '<a href="ver_intentos.php?motivo=SALIDA_SIN_ENTRADA" class="btn-filtro-rapido">Filtrar</a>'; ?></td>
            </tr>
            <tr>
                <td><a href="ver_intentos.php" class="enlace-filtro-fila">Total de intentos denegados</a></td>
                <td><span class="num-total"><?php echo (int)$total; ?></span></td>
                <td><?php echo $filtro_motivo ? '<a href="ver_intentos.php" class="btn-filtro-rapido">Ver todos</a>' : ''; ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Filtros -->
    <form method="GET" action="ver_intentos.php" class="filtro-form">

        <div class="form-row">
            <div class="form-group-md">
                <label for="matricula" class="form-label">🔍 Matrícula:</label><br>
                <input type="text" name="matricula" id="matricula" class="form-control"
                    value="<?php echo htmlspecialchars($filtro_matricula); ?>"
                    placeholder="Ej: 1234ABC">
            </div>

            <div class="form-group-md">
                <label for="motivo" class="form-label">⚠️ Tipo de incidencia:</label><br>
                <select name="motivo" id="motivo" class="form-control">
                    <option value="">-- Todos los tipos --</option>
                    <option value="NO_AUTORIZADO"     <?php echo $filtro_motivo === 'NO_AUTORIZADO'     ? 'selected' : ''; ?>>No autorizado</option>
                    <option value="ENTRADA_DUPLICADA"  <?php echo $filtro_motivo === 'ENTRADA_DUPLICADA'  ? 'selected' : ''; ?>>Entrada duplicada</option>
                    <option value="SALIDA_SIN_ENTRADA" <?php echo $filtro_motivo === 'SALIDA_SIN_ENTRADA' ? 'selected' : ''; ?>>Salida sin entrada</option>
                </select>
            </div>

            <div class="form-group-md">
                <label for="fecha_inicio" class="form-label">📅 Desde (Fecha y Hora):</label><br>
                <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" class="form-control"
                    value="<?php echo htmlspecialchars($filtro_fecha_inicio); ?>">
            </div>

            <div class="form-group-md">
                <label for="fecha_fin" class="form-label">📅 Hasta (Fecha y Hora):</label><br>
                <input type="datetime-local" name="fecha_fin" id="fecha_fin" class="form-control"
                    value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>">
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn-blue btn-lg btn-grow">🔍 Aplicar Filtros</button>
            <?php if ($hay_filtros): ?>
                <a href="ver_intentos.php" class="link-grow">
                    <button type="button" class="btn-red btn-lg btn-w100">🗑️ Limpiar</button>
                </a>
            <?php endif; ?>
        </div>

    </form>

    <?php
    if ($resultado && mysqli_num_rows($resultado) > 0):
    ?>
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
                <?php while ($fila = mysqli_fetch_assoc($resultado)):
                    $fecha_hora = date("d/m/Y - H:i", strtotime($fila['fecha_hora']));
                    $camara     = htmlspecialchars($fila['camara'] ?? '-');
                    $motivo_raw = $fila['motivo'] ?? 'NO_AUTORIZADO';
                    $matricula  = htmlspecialchars($fila['matricula']);

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
                    <td><strong class="matricula-denegada"><?php echo $matricula; ?></strong></td>
                    <td><?php echo $camara; ?></td>
                    <td><?php echo $badge; ?></td>
                    <td><span class="estado-salida">DENEGADO</span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alerta-vacia">
        <p class="alerta-vacia-texto">No se encontraron intentos denegados en ese periodo o con esos filtros.</p>
    </div>
    <?php endif; ?>

    <br>
    <a href="index.php"><button type="button" class="btn-gray btn-gray-mt10">Volver al Inicio</button></a>
</div>

<?php require '../footer2.php'; ?>

