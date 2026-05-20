<?php
session_start();

// 🚨 EL ARREGLO ESTÁ AQUÍ: Comprobamos 'administrador' (todo en minúsculas para que coincida con tu foto)
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
?>

<?php include("../header2.php"); ?>
<?php include("../conexion.php"); ?>

<h2>Lista de Usuarios</h2>

<form method="GET" style="margin-bottom: 20px; background: #f4f4f4; padding: 15px; border-radius: 5px;">
    <input type="text" name="buscar" placeholder="Buscar por nombre o DNI..." value="<?php echo isset($_GET['buscar']) ? $_GET['buscar'] : ''; ?>" style="padding: 8px;">
    
    <select name="rol" style="padding: 8px;">
        <option value="">Todos los roles</option>
        <option value="1">Administrador</option>
        <option value="2">Profesor</option>
        <option value="3">Alumnos</option>
    </select>
    
    <button type="submit" style="background-color: #34495e; color: white; padding: 8px 15px; border: none; cursor: pointer;">Filtrar</button>
    <a href="index.php" style="margin-left: 10px; font-size: 14px; color: #666;">Limpiar filtros</a>
</form>

<?php
// Recogemos datos de búsqueda
$buscar = isset($_GET['buscar']) ? mysqli_real_escape_string($conexion, $_GET['buscar']) : '';
$rol = isset($_GET['rol']) ? mysqli_real_escape_string($conexion, $_GET['rol']) : '';

// NUEVA CONSULTA: Añadimos la matrícula y aplicamos los filtros si existen
$sql = "SELECT u.dni, u.nombre, u.apellidos, u.email, u.telefono, r.nombre_rol, v.matricula 
        FROM usuarios u 
        INNER JOIN roles r ON u.id_rol = r.id_rol
        LEFT JOIN vehiculos v ON u.dni = v.dni_usuario
        WHERE 1=1";

if ($buscar != '') {
    $sql .= " AND (u.nombre LIKE '%$buscar%' OR u.dni LIKE '%$buscar%' OR u.apellidos LIKE '%$buscar%')";
}
if ($rol != '') {
    $sql .= " AND u.id_rol = '$rol'";
}

$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0) {
    echo "<div class='tabla-responsive'>
            <table>
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Nombre</th>
                        <th>Apellidos</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Matrícula</th> 
                    </tr>
                </thead>
                <tbody>";
        
    // Recorremos los resultados (Mantenemos tus índices $registro[0...6])
    while ($registro = mysqli_fetch_row($resultado)) {
        $matricula = $registro[6] ? $registro[6] : "Sin vehículo";

        echo "<tr>
                <td>$registro[0]</td>
                <td>$registro[1]</td>
                <td>$registro[2]</td>
                <td>$registro[3]</td>
                <td>$registro[4]</td>
                <td>$registro[5]</td>
                <td><strong>$matricula</strong></td> 
              </tr>";
    }
    echo "</tbody></table></div><br>";
} else {
    echo "No hay registros que coincidan con la búsqueda.";
}

mysqli_close($conexion);
?>

<div class="botones-accion">
    <a href="formulario_usuario.php"><button style=" background-color: #34495e;">Añadir usuarios</button></a>
    <a href="borrar_usuario.php"><button style=" background-color: #34495e;">Borrar usuarios</button></a>
    <a href="actualizar_usuario.php"><button style=" background-color: #34495e;">Actualizar usuarios</button></a>
    <a href="historial_accesos.php"><button style=" background-color: #34495e;">Ver Historial</button></a>
    <a href="ver_incidencias.php"><button style=" background-color: #34495e;">Ver Incidencias</button></a>
    <a href="ver_intentos.php"><button style="background-color: #34495e; color: white;">Ver Intentos No Autorizados</button></a>
    <a href="control_barrera.php"><button style=" background-color: #34495e;">Fichar en Barrera</button></a>
    <a href="../logout.php"><button>Cerrar Sesión y Salir</button></a>
</div>

<?php include("../footer2.php"); ?>