<?php
session_start();

if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

require '../conexion.php';
require '../header2.php';
?>

<div class="container">
    <h2 style="color: var(--color-principal);"><i class="fas fa-exclamation-triangle"></i> Historial de Mal Aparcamiento</h2>
    <p>Registro de todos los vehículos reportados como mal aparcados.</p>

    <!-- Filtros -->
    <form method="GET" style="margin: 20px 0; background: #f4f4f4; padding: 15px; border-radius: 8px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
        <input type="text" name="matricula" placeholder="Filtrar por matrícula..." value="<?php echo isset($_GET['matricula']) ? htmlspecialchars($_GET['matricula']) : ''; ?>" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
        <input type="date" name="fecha_desde" value="<?php echo isset($_GET['fecha_desde']) ? htmlspecialchars($_GET['fecha_desde']) : ''; ?>" title="Desde" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
        <input type="date" name="fecha_hasta" value="<?php echo isset($_GET['fecha_hasta']) ? htmlspecialchars($_GET['fecha_hasta']) : ''; ?>" title="Hasta" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
        <button type="submit" style="background-color: #c0392b; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;"><i class="fas fa-filter"></i> Filtrar</button>
        <a href="mal_aparcado.php" style="font-size: 14px; color: #666; text-decoration: none;">Limpiar filtros</a>
    </form>

    <?php
    // Construir la consulta con filtros opcionales
    $matricula_f  = isset($_GET['matricula'])   ? mysqli_real_escape_string($conexion, trim($_GET['matricula']))   : '';
    $fecha_desde  = isset($_GET['fecha_desde']) ? mysqli_real_escape_string($conexion, trim($_GET['fecha_desde'])) : '';
    $fecha_hasta  = isset($_GET['fecha_hasta']) ? mysqli_real_escape_string($conexion, trim($_GET['fecha_hasta'])) : '';

    $sql = "SELECT 
                ma.id,
                ma.matricula,
                COALESCE(CONCAT(u_prop.nombre, ' ', u_prop.apellidos), 'Desconocido') AS propietario,
                CONCAT(u_rep.nombre, ' ', u_rep.apellidos) AS reportado_por,
                ma.fecha_hora,
                ma.comentario
            FROM mal_aparcado ma
            LEFT JOIN vehiculos v        ON ma.matricula     = v.matricula
            LEFT JOIN usuarios  u_prop   ON v.dni_usuario    = u_prop.dni
            INNER JOIN usuarios u_rep    ON ma.reportado_por = u_rep.dni
            WHERE 1=1";

    if ($matricula_f !== '') {
        $sql .= " AND ma.matricula LIKE '%$matricula_f%'";
    }
    if ($fecha_desde !== '') {
        $sql .= " AND DATE(ma.fecha_hora) >= '$fecha_desde'";
    }
    if ($fecha_hasta !== '') {
        $sql .= " AND DATE(ma.fecha_hora) <= '$fecha_hasta'";
    }

    $sql .= " ORDER BY ma.fecha_hora DESC";
    $resultado = mysqli_query($conexion, $sql);
    $total = mysqli_num_rows($resultado);
    ?>

    <p style="color: #555; margin-bottom: 10px;">
        <strong><?php echo $total; ?></strong> reporte(s) encontrado(s).
    </p>

    <div class="tabla-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Matrícula</th>
                    <th>Propietario del Vehículo</th>
                    <th>Reportado por</th>
                    <th>Fecha y Hora</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total > 0): ?>
                    <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td><?php echo $fila['id']; ?></td>
                        <td><strong style="color: #c0392b;"><?php echo htmlspecialchars($fila['matricula']); ?></strong></td>
                        <td><?php echo htmlspecialchars($fila['propietario']); ?></td>
                        <td><?php echo htmlspecialchars($fila['reportado_por']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($fila['fecha_hora'])); ?></td>
                        <td><?php echo !empty($fila['comentario']) ? htmlspecialchars($fila['comentario']) : '<em style="color:#aaa;">Sin comentario</em>'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 20px; color: #888;">No se han encontrado reportes con los filtros aplicados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 30px;">
        <a href="index.php"><button style="background-color: #34495e;">← Volver al Panel Admin</button></a>
    </div>
</div>

<?php
mysqli_close($conexion);
require '../footer2.php';
?>
