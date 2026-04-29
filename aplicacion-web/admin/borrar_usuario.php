<?php
session_start();
// Nuestra seguridad anti-bucles actualizada
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
?>

<?php include("../header2.php"); ?>
<?php include("../conexion.php"); ?>

<h2>Selecciona el Usuario a Borrar</h2>

<?php
// Consulta uniendo usuarios y roles
$sql = "SELECT u.dni, u.nombre, u.apellidos, u.email, u.telefono, r.nombre_rol 
        FROM usuarios u 
        INNER JOIN roles r ON u.id_rol = r.id_rol";
        
$resultado = mysqli_query($conexion, $sql);

if (mysqli_num_rows($resultado) > 0) {
    echo "<table border='1' cellpadding='10'>
        <thead>
            <tr>
                <th>DNI</th>
                <th>Nombre</th>
                <th>Apellidos</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Rol</th>
                <th>Eliminar</th>
            </tr>
        </thead>
        <tbody>";
        
    while ($registro = mysqli_fetch_row($resultado)) {
        echo "<tr>
                <td>$registro[0]</td>
                <td>$registro[1]</td>
                <td>$registro[2]</td>
                <td>$registro[3]</td>
                <td>$registro[4]</td>
                <td>$registro[5]</td>
                <td style='text-align: center;'>
                    <a href='borrar2_usuario.php?dni=$registro[0]' onclick='return confirm(\"¿Estás seguro de borrar a $registro[1]?\");'>
                       🗑️
                    </a>
                </td>
              </tr>";
    }
    echo "</tbody></table><br>";
} else {
    echo "No hay registros en la base de datos.";
}

mysqli_close($conexion);
?>

<a href="index.php"><button>Volver al panel</button></a>

<?php include("../footer2.php"); ?>