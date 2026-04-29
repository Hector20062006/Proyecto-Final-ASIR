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
            <label for="matricula" class="form-label">Escribe el nombre o matrícula del Vehículo en la barrera:</label><br>
            <input list="lista_matriculas" name="matricula" id="matricula" placeholder="-- Escribe para buscar matrícula o nombre --" class="form-control-mt10" required autocomplete="off">
            <datalist id="lista_matriculas">
                <?php
                // Hacemos un INNER JOIN para mostrar la matrícula junto al nombre del dueño para que sea más fácil
                $sql = "SELECT v.matricula, u.nombre, u.apellidos 
                        FROM vehiculos v 
                        INNER JOIN usuarios u ON v.dni_usuario = u.dni";
                $resultado = mysqli_query($conexion, $sql);

                while ($fila = mysqli_fetch_assoc($resultado)) {
                    // Ponemos la matrícula como valor a enviar y el nombre como etiqueta de búsqueda
                    echo "<option value='" . $fila['matricula'] . "'>" . $fila['nombre'] . " " . $fila['apellidos'] . "</option>";
                }
                ?>
            </datalist>
        </p>

        <br>
        <div class="btn-group">
            <button type="submit" name="tipo_movimiento" value="ENTRADA" class="btn-green btn-w100 btn-lg">🟢 Registrar ENTRADA</button>
            <button type="submit" name="tipo_movimiento" value="SALIDA" class="btn-red btn-w100 btn-lg">🔴 Registrar SALIDA</button>
        </div>

    </form>

    <br>
    <hr class="hr-separador">
    <a href="index.php"><button type="button" class="btn-gray">Volver al Inicio</button></a>
</div>

<?php require '../footer2.php'; ?>