<?php
session_start();

// 1. Comprobación de seguridad actualizada
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recogemos los datos del usuario
    $dni = trim($_POST['dni']);
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password_plana = $_POST['password'];
    $id_rol = $_POST['id_rol'];

    // Recogemos los datos del vehículo (NUEVOS)
    $matricula = trim($_POST['matricula']);
    $marca_modelo = trim($_POST['marca_modelo']);

    // Encriptamos la clave
    $password_encriptada = password_hash($password_plana, PASSWORD_DEFAULT);

    // PASO 1: Guardar el usuario en la base de datos
    $sql_usuario = "INSERT INTO usuarios (dni, nombre, apellidos, email, telefono, password, id_rol) 
            VALUES ('$dni', '$nombre', '$apellidos', '$email', '$telefono', '$password_encriptada', '$id_rol')";

    if (mysqli_query($conexion, $sql_usuario)) {
        
        // PASO 2: Si el usuario se ha guardado bien, comprobamos si rellenó la matrícula
        if (!empty($matricula)) {
            $sql_vehiculo = "INSERT INTO vehiculos (matricula, marca_modelo, dni_usuario) 
                             VALUES ('$matricula', '$marca_modelo', '$dni')";
            
            // Si hay un error al guardar el coche, lo mostramos
            if (!mysqli_query($conexion, $sql_vehiculo)) {
                echo "Usuario guardado, pero hubo un error al guardar el vehículo: " . mysqli_error($conexion);
                echo "<br><a href='index.php'>Volver al inicio</a>";
                exit; // Detenemos la ejecución aquí
            }
        }

        // Si se guardó el usuario (y el coche si lo había) sin errores, volvemos al inicio
        header("Location: index.php");
        exit;

    } else {
        echo "Error al guardar el usuario: " . mysqli_error($conexion);
        echo "<br><a href='formulario_usuario.php'>Volver</a>";
    }
}
?>