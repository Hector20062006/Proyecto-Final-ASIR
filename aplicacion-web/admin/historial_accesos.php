<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
require '../header2.php'; 
require '../conexion.php';

$filtro_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
?>

<div class="container">
    <h2>Historial de Entradas y Salidas</h2>
    <p>Registro de los movimientos del parking. Puedes filtrar para ver los fichajes de un usuario en específico.</p>

    <!-- Filtro sin JavaScript -->
    <form method="GET" action="historial_accesos.php" style="margin-bottom: 20px; background-color: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
        <label for="usuario" style="font-weight: bold;">Filtrar fichajes por Usuario:</label><br>
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <select name="usuario" id="usuario" style="padding: 10px; flex-grow: 1;">
                <option value="">-- Ver todos los usuarios --</option>
                <?php
                // Cargar la lista de usuarios para el filtro
                $sql_usuarios = "SELECT u.dni, u.nombre, u.apellidos, r.nombre_rol FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol ORDER BY u.nombre ASC";
                $res_usuarios = mysqli_query($conexion, $sql_usuarios);
                while ($u = mysqli_fetch_assoc($res_usuarios)) {
                    $selected = ($filtro_usuario == $u['dni']) ? 'selected' : '';
                    echo "<option value='{$u['dni']}' {$selected}>{$u['nombre']} {$u['apellidos']} (" . ucfirst($u['nombre_rol']) . ")</option>";
                }
                ?>
            </select>
            <button type="submit" style="padding: 10px 20px; background-color: #3498db;">🔍 Buscar</button>
            <?php if ($filtro_usuario !== ''): ?>
                <a href="historial_accesos.php"><button type="button" style="padding: 10px 20px; background-color: #e74c3c;">Limpiar Filtro</button></a>
            <?php endif; ?>
        </div>
    </form>

    <?php
    $where_clause = "";
    if ($filtro_usuario !== '') {
        // Aplicamos el filtro por el DNI seleccionado
        $where_clause = " WHERE u.dni = '" . mysqli_real_escape_string($conexion, $filtro_usuario) . "' ";
    }

    // Consulta con JOIN a roles para saber si es profesor o alumno
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
                <table>
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
            // Colores para entrada/salida
            $color_movimiento = ($fila['tipo_movimiento'] == 'ENTRADA') ? 'color: #27ae60; font-weight: bold;' : 'color: #c0392b; font-weight: bold;';
            
            $propietario = ($fila['nombre']) ? $fila['nombre'] . " " . $fila['apellidos'] : "<span style='color: #f39c12;'>No Registrado</span>";
            $rol = ($fila['nombre_rol']) ? ucfirst($fila['nombre_rol']) : "-";

            // Formato de fecha
            $fecha_formateada = date("d/m/Y H:i:s", strtotime($fila['fecha_hora']));

            echo "<tr>
                    <td>{$fecha_formateada}</td>
                    <td style='{$color_movimiento}'>{$fila['tipo_movimiento']}</td>
                    <td><strong>{$fila['matricula']}</strong></td>
                    <td>{$propietario}</td>
                    <td><strong>{$rol}</strong></td>
                  </tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<p style='text-align: center; font-weight: bold; padding: 20px;'>No hay registros de fichajes que coincidan con la búsqueda.</p>";
    }

    mysqli_close($conexion);
    ?>

    <br>
    <a href="index.php"><button type="button" style="background-color: #95a5a6;">Volver al Inicio</button></a>
</div>

<?php require '../footer2.php'; ?>