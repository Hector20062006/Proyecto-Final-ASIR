<?php
session_start();

// Comprobación de seguridad actualizada
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
require '../header2.php'; 
?>

<div class="container">
    <h2>Añadir Nuevo Usuario</h2>
    
    <form action="guardar_usuario.php" method="POST">
        
        <h3>Datos Personales</h3>
        <p>DNI: <br><input type="text" name="dni" required maxlength="9" placeholder="Ej: 12345678A"></p>
        <p>Nombre: <br><input type="text" name="nombre" placeholder="Ej: Pepito" required></p>
        <p>Apellidos: <br><input type="text" name="apellidos" placeholder="Ej: Fernandez Heredia" required></p>
        <p>Email: <br><input type="email" name="email" placeholder="Ej: usuario@iesiliberis.es" required></p>
        <p>Teléfono: <br><input type="text" name="telefono" placeholder="Ej: 123456789"></p>
        <p>Contraseña: <br><input type="password" name="password" required></p>
        <p>Rol: <br>
            <select name="id_rol" required>
                <option value="2">Profesor</option>
                <option value="3">Alumnos</option>
                <option value="1">Administrador</option>
            </select>
        </p>

        <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">

        <h3>Datos del Vehículo</h3>
        <p><i>(Dejar en blanco si el usuario no tiene vehículo)</i></p>
        <p>Matrícula: <br><input type="text" name="matricula" maxlength="10" placeholder="Ej: 1234ABC"></p>
        <p>Marca y Modelo: <br><input type="text" name="marca_modelo" maxlength="50" placeholder="Ej: Seat Leon"></p>

        <br>
        <button type="submit">Guardar Usuario</button>
        <a href="index.php"><button type="button" style="background-color: #95a5a6;">Cancelar</button></a>
    </form>
</div>

<?php require '../footer2.php'; ?>