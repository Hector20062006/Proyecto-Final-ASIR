<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
include '../header2.php'; 
include '../conexion.php';

// Recoger filtros si existen
$filtro_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
?>

<div class="container container-lg">
    <h2>Historial de Entradas y Salidas</h2>
    <p>Registro de los movimientos del parking. Aplica filtros para analizar periodos de tiempo concretos o usuarios específicos.</p>

    <!-- Filtro Avanzado -->
    <form method="GET" action="historial_accesos.php" class="filtro-form">
        
        <div class="form-row">
            <div class="form-group-lg">
                <label for="usuario" class="form-label">👤 Usuario:</label><br>
                <select name="usuario" id="usuario" class="form-control">
                    <option value="">-- Todos los usuarios --</option>
                    <?php
                    $sql_usuarios = "SELECT u.dni, u.nombre, u.apellidos, r.nombre_rol FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol ORDER BY u.nombre ASC";
                    $res_usuarios = mysqli_query($conexion, $sql_usuarios);
                    while ($u = mysqli_fetch_assoc($res_usuarios)) {
                        $selected = ($filtro_usuario == $u['dni']) ? 'selected' : '';
                        echo "<option value='{$u['dni']}' {$selected}>{$u['nombre']} {$u['apellidos']} (" . ucfirst($u['nombre_rol']) . ")</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group-md">
                <label for="fecha_inicio" class="form-label">📅 Desde (Fecha y Hora):</label><br>
                <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" class="form-control">
            </div>
            
            <div class="form-group-md">
                <label for="fecha_fin" class="form-label">📅 Hasta (Fecha y Hora):</label><br>
                <input type="datetime-local" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" class="form-control">
            </div>
        </div>
        
        <div class="btn-group">
            <button type="submit" class="btn-blue btn-lg btn-grow">🔍 Aplicar Filtros</button>
            <?php if ($filtro_usuario !== '' || $fecha_inicio !== '' || $fecha_fin !== ''): ?>
                <a href="historial_accesos.php" class="link-grow">
                    <button type="button" class="btn-red btn-lg btn-w100">🗑️ Limpiar</button>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php
    $condiciones = [];

    // Filtro por usuario
    if ($filtro_usuario !== '') {
        $condiciones[] = "u.dni = '" . mysqli_real_escape_string($conexion, $filtro_usuario) . "'";
    }
    
    // Filtro por fecha inicial (reemplazamos la 'T' de HTML5 por un espacio para MySQL)
    if ($fecha_inicio !== '') {
        $fecha_inicio_sql = str_replace('T', ' ', $fecha_inicio) . ':00';
        $condiciones[] = "a.fecha_hora >= '" . mysqli_real_escape_string($conexion, $fecha_inicio_sql) . "'";
    }
    
    // Filtro por fecha final
    if ($fecha_fin !== '') {
        $fecha_fin_sql = str_replace('T', ' ', $fecha_fin) . ':59';
        $condiciones[] = "a.fecha_hora <= '" . mysqli_real_escape_string($conexion, $fecha_fin_sql) . "'";
    }

    $where_clause = "";
    if (count($condiciones) > 0) {
        $where_clause = " WHERE " . implode(" AND ", $condiciones);
    }

    // Consulta principal
    $sql = "SELECT a.fecha_hora, a.tipo_movimiento, a.matricula, u.nombre, u.apellidos, r.nombre_rol 
            FROM accesos a
            LEFT JOIN vehiculos v ON a.matricula = v.matricula
            LEFT JOIN usuarios u ON v.dni_usuario = u.dni
            LEFT JOIN roles r ON u.id_rol = r.id_rol
            $where_clause
            ORDER BY a.fecha_hora DESC";
            
    $resultado = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($resultado) > 0) {
        echo "<div class='tabla-responsive'>
                <table class='table-full'>
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Movimiento</th>
                            <th>Matrícula</th>
                            <th>Propietario</th>
                            <th>Cargo / Rol</th>
                        </tr>
                    </thead>
                    <tbody>";
            
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $clase_movimiento = ($fila['tipo_movimiento'] == 'ENTRADA') ? 'estado-entrada' : 'estado-salida';
            
            $propietario = ($fila['nombre']) ? $fila['nombre'] . " " . $fila['apellidos'] : "<span class='estado-no-registrado'>No Registrado</span>";
            $rol = ($fila['nombre_rol']) ? ucfirst($fila['nombre_rol']) : "-";

            $fecha_formateada = date("d/m/Y - H:i", strtotime($fila['fecha_hora']));

            echo "<tr>
                    <td>{$fecha_formateada}</td>
                    <td class='{$clase_movimiento}'>{$fila['tipo_movimiento']}</td>
                    <td><strong>{$fila['matricula']}</strong></td>
                    <td>{$propietario}</td>
                    <td><strong>{$rol}</strong></td>
                  </tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class='alerta-vacia'>
                <p class='alerta-vacia-texto'>No se encontraron accesos en ese periodo o con esos filtros.</p>
              </div>";
    }

    mysqli_close($conexion);
    ?>

    <br>
    <a href="index.php"><button type="button" class="btn-gray btn-gray-mt10">Volver al Inicio</button></a>
</div>

<?php include '../footer2.php'; ?>