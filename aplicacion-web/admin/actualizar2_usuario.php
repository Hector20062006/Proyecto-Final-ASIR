<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
?>

<?php
$dni_modificar = $_GET["dni"];
include "../conexion.php";

// Modificamos la consulta para traer también los datos del vehículo (LEFT JOIN)
$sql = "SELECT u.*, v.matricula, v.marca_modelo 
        FROM usuarios u 
        LEFT JOIN vehiculos v ON u.dni = v.dni_usuario 
        WHERE u.dni = '$dni_modificar'";
        
$resultado = mysqli_query($conexion, $sql);
$registro = mysqli_fetch_assoc($resultado);

// Asignar variables (incluyendo las del vehículo)
$nombre = $registro['nombre'];
$apellidos = $registro['apellidos'];
$email = $registro['email'];
$telefono = $registro['telefono'];
$id_rol_actual = $registro['id_rol'];
$matricula = $registro['matricula'];
$marca_modelo = $registro['marca_modelo'];
?>

<?php include("../header2.php"); ?>

<div class="container">
    <h2>MODIFICAR DATOS DEL USUARIO: <?php echo $dni_modificar; ?></h2>

    <form action="actualizar3_usuario.php?dni=<?php echo $dni_modificar; ?>" method="post">
      
        <h3>Datos Personales</h3>
        <p>
            <label for="nombre">Nombre:</label><br>
            <input type="text" id="nombre" name="nombre" value="<?php echo $nombre; ?>" required>
        </p>

        <p>
            <label for="apellidos">Apellidos:</label><br>
            <input type="text" id="apellidos" name="apellidos" value="<?php echo $apellidos; ?>" required>
        </p>

        <p>
            <label for="email">Email:</label><br>
            <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
        </p>

        <p>
            <label for="telefono">Teléfono:</label><br>
            <input type="text" id="telefono" name="telefono" value="<?php echo $telefono; ?>">
        </p>

        <p>
            <label for="id_rol">Rol:</label><br>
            <select name="id_rol" required>
                <option value="3" <?php if($id_rol_actual == 3) echo 'selected'; ?>>Alumnos</option>
                <option value="2" <?php if($id_rol_actual == 2) echo 'selected'; ?>>Profesor</option>
                <option value="1" <?php if($id_rol_actual == 1) echo 'selected'; ?>>Administrador</option>
            </select>
        </p>

        <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">

        <h3>Datos del Vehículo</h3>
        <p>
            <label for="matricula">Matrícula:</label><br>
            <input type="text" id="matricula" name="matricula" value="<?php echo $matricula; ?>" maxlength="10">
        </p>

        <p>
            <label for="marca_modelo">Marca y Modelo:</label><br>
            <input type="text" id="marca_modelo" name="marca_modelo" value="<?php echo $marca_modelo; ?>" maxlength="50">
        </p>

        <br>
        <button type="submit">Actualizar Guardar</button>
        <a href="actualizar_usuario.php"><button type="button" style="background-color: #95a5a6;">Cancelar y Volver</button></a>
    </form>
</div>

<?php include("../footer2.php"); ?>