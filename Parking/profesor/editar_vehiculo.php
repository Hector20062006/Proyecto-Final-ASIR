<?php
session_start();
if (!isset($_SESSION['role']) || strpos(strtolower(trim($_SESSION['role'])), 'profesor') === false) {
    header("Location: ../login.php");
    exit;
}
require '../conexion.php';
require '../header2.php';

$dni = $_SESSION['dni'];
$sql = "SELECT * FROM vehiculos WHERE dni_usuario = '$dni'";
$res = mysqli_query($conexion, $sql);
$vehi = mysqli_fetch_assoc($res);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mat = strtoupper(trim($_POST['matricula']));
    $mod = trim($_POST['marca_modelo']);
    
    // Si ya tenía coche hacemos UPDATE, si no tenía (aunque es raro) hacemos INSERT
    if ($vehi) {
        $sql_v = "UPDATE vehiculos SET matricula='$mat', marca_modelo='$mod' WHERE dni_usuario='$dni'";
    } else {
        $sql_v = "INSERT INTO vehiculos (matricula, marca_modelo, dni_usuario) VALUES ('$mat', '$mod', '$dni')";
    }

    if (mysqli_query($conexion, $sql_v)) {
        echo "<script>alert('Vehículo actualizado'); window.location.href='index.php';</script>";
    }
}
?>

<div class="container">
    <h2>Actualizar Mi Vehículo</h2>
    <form method="POST">
        <p>Matrícula:<br>
        <input type="text" name="matricula" value="<?php echo $vehi['matricula'] ?? ''; ?>" required maxlength="10"></p>
        
        <p>Marca y Modelo:<br>
        <input type="text" name="marca_modelo" value="<?php echo $vehi['marca_modelo'] ?? ''; ?>" required></p>
        
        <br>
        <button type="submit">Actualizar Vehículo</button>
        <a href="index.php"><button type="button" style="background-color: #95a5a6;">Cancelar</button></a>
    </form>
</div>

<?php require '../footer2.php'; ?>