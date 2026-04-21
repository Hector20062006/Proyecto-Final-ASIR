<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}
require '../header2.php'; 
require '../conexion.php';
?>

<div class="container">
    <h2>Control de Barrera (Accesos)</h2>
    <p>Registra la entrada o salida de un vehículo del parking del instituto.</p>

    <form action="registrar_acceso.php" method="POST">
        
        <p>
            <label for="matricula" style="font-weight: bold;">Selecciona el Vehículo en la barrera:</label><br>
            <select name="matricula" id="matricula" style="padding: 10px; width: 100%; margin-top: 10px;" required>
                <option value="">-- Elige una matrícula --</option>
                <?php
                // Hacemos un INNER JOIN para mostrar la matrícula junto al nombre del dueño para que sea más fácil
                $sql = "SELECT v.matricula, u.nombre, u.apellidos 
                        FROM vehiculos v 
                        INNER JOIN usuarios u ON v.dni_usuario = u.dni";
                $resultado = mysqli_query($conexion, $sql);
                
                while($fila = mysqli_fetch_assoc($resultado)){
                    echo "<option value='" . $fila['matricula'] . "'>" . $fila['matricula'] . " - " . $fila['nombre'] . " " . $fila['apellidos'] . "</option>";
                }
                ?>
            </select>
        </p>

        <br>
        <div style="display: flex; gap: 15px;">
            <button type="submit" name="tipo_movimiento" value="ENTRADA" style="background-color: #27ae60; width: 100%; font-size: 16px;">🟢 Registrar ENTRADA</button>
            <button type="submit" name="tipo_movimiento" value="SALIDA" style="background-color: var(--color-peligro); width: 100%; font-size: 16px;">🔴 Registrar SALIDA</button>
        </div>

    </form>
    
    <br><hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
    <a href="index.php"><button type="button" style="background-color: #95a5a6;">Volver al Inicio</button></a>
</div>

<?php require '../footer2.php'; ?>