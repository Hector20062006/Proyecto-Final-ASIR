<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
?>

<?php include("../header2.php"); ?>
<?php include("../conexion.php"); ?>

<div class="container">
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Recogemos el DNI de la URL y los datos del usuario
    $dni = $_GET["dni"];
    $nombre = $_POST["nombre"];
    $apellidos = $_POST["apellidos"];
    $email = $_POST["email"];
    $telefono = $_POST["telefono"];
    $id_rol = $_POST["id_rol"];

    // 2. Recogemos los datos nuevos del vehículo
    $matricula = trim($_POST["matricula"]);
    $marca_modelo = trim($_POST["marca_modelo"]);

    // 3. Actualizamos los datos personales en la tabla usuarios
    $sql_usuarios = "UPDATE usuarios 
            SET nombre='$nombre', apellidos='$apellidos', email='$email', telefono='$telefono', id_rol='$id_rol' 
            WHERE dni='$dni'";

    if ($conexion->query($sql_usuarios) === TRUE) {
        
        // 4. LÓGICA DEL VEHÍCULO: Comprobamos si el usuario ya tenía un coche registrado
        $check_vehiculo = "SELECT matricula FROM vehiculos WHERE dni_usuario = '$dni'";
        $resultado_vehiculo = $conexion->query($check_vehiculo);

        if ($resultado_vehiculo->num_rows > 0) {
            // OPCIÓN A: El usuario ya tenía un vehículo en la base de datos
            if (!empty($matricula)) {
                // Si ha escrito una matrícula, la actualizamos
                $sql_vehiculo = "UPDATE vehiculos SET matricula='$matricula', marca_modelo='$marca_modelo' WHERE dni_usuario='$dni'";
                $conexion->query($sql_vehiculo);
            } else {
                // Si ha dejado la matrícula en blanco, significa que ya no tiene coche, lo borramos
                $sql_vehiculo = "DELETE FROM vehiculos WHERE dni_usuario='$dni'";
                $conexion->query($sql_vehiculo);
            }
        } else {
            // OPCIÓN B: El usuario NO tenía vehículo antes
            if (!empty($matricula)) {
                // Como ha escrito una matrícula nueva, la insertamos
                $sql_vehiculo = "INSERT INTO vehiculos (matricula, marca_modelo, dni_usuario) VALUES ('$matricula', '$marca_modelo', '$dni')";
                $conexion->query($sql_vehiculo);
            }
        }

        // Mensaje de éxito
        echo "<h3 style='color: #2c3e50;'>✅ El usuario y sus datos de acceso al parking se han actualizado correctamente.</h3>";
        echo "<br><a href='index.php'><button>Volver al panel principal</button></a>";

    } else {
        // Mensaje de error
        echo "<h3 style='color: var(--color-peligro);'>❌ Error al actualizar el usuario: " . $conexion->error . "</h3>";
        echo "<br><a href='actualizar_usuario.php'><button>Volver a intentarlo</button></a>";
    }
} else {
    // Si entran directamente copiando la URL sin usar el formulario, los echamos
    echo "<script>window.location.href='index.php';</script>";
}
?>
</div>
    
<?php include("../footer2.php"); ?>