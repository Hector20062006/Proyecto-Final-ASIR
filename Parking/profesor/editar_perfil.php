<?php
session_start();
// Seguridad para profesores
if (!isset($_SESSION['role']) || strpos(strtolower(trim($_SESSION['role'])), 'profesor') === false) {
    header("Location: ../login.php");
    exit;
}
require '../conexion.php';
require '../header2.php';

$dni = $_SESSION['dni'];
$sql = "SELECT * FROM usuarios WHERE dni = '$dni'";
$res = mysqli_query($conexion, $sql);
$user = mysqli_fetch_assoc($res);

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_email = trim($_POST['email']);
    $nuevo_tel = trim($_POST['telefono']);
    
    $update = "UPDATE usuarios SET email='$nuevo_email', telefono='$nuevo_tel' WHERE dni='$dni'";
    if (mysqli_query($conexion, $update)) {
        echo "<script>alert('Datos actualizados'); window.location.href='index.php';</script>";
    }
}
?>

<div class="container">
    <h2>Editar Mi Perfil</h2>
    <form method="POST">
        <p>DNI (No editable):<br>
        <input type="text" value="<?php echo $user['dni']; ?>" disabled></p>
        
        <p>Email:<br>
        <input type="email" name="email" value="<?php echo $user['email']; ?>" required></p>
        
        <p>Teléfono:<br>
        <input type="text" name="telefono" value="<?php echo $user['telefono']; ?>" required></p>
        
        <br>
        <button type="submit">Guardar Cambios</button>
        <a href="index.php"><button type="button" style="background-color: #95a5a6;">Cancelar</button></a>
    </form>
</div>

<?php require '../footer2.php'; ?>