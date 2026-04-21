<?php
session_start();
require 'conexion.php';

// BOTÓN DE EMERGENCIA: Rompe el bucle si nos quedamos atascados
if (isset($_GET['reset'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Función inteligente para redirigir
function redireccionar_segun_rol($rol) {
    $rol = strtolower(trim($rol)); 
    
    if ($rol === 'administrador') {
        header("Location: admin/index.php");
        exit;
    } elseif (strpos($rol, 'profesor') !== false) { 
        header("Location: profesor/index.php");
        exit;
    } elseif (strpos($rol, 'alumno') !== false) {
        header("Location: alumno/index.php");
    }else {
        session_destroy();
        header("Location: login.php?error=rol_invalido");
        exit;
    }
}

// Comprobamos si ya hay sesión
if (isset($_SESSION['role'])) {
    redireccionar_segun_rol($_SESSION['role']);
}

$mensaje_error = "";

if (isset($_GET['error']) && $_GET['error'] == 'rol_invalido') {
    $mensaje_error = "<p style='color: var(--color-peligro); font-weight: bold;'>Tu rol no tiene acceso a ningún panel todavía.</p>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT u.dni, u.nombre, u.email, u.password, r.nombre_rol 
            FROM usuarios u 
            JOIN roles r ON u.id_rol = r.id_rol 
            WHERE u.email='$username' OR u.dni='$username'";
            
    $res = mysqli_query($conexion, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        
        if (password_verify($password, $row['password'])) {
            
            // EL ARREGLO ESTÁ AQUÍ: Guardamos todo en la sesión limpio y en minúsculas
            $_SESSION['username'] = $row['nombre'];
            $_SESSION['dni'] = $row['dni']; 
            $_SESSION['role'] = strtolower(trim($row['nombre_rol'])); 
            
            redireccionar_segun_rol($_SESSION['role']);
            
        } else {
            $mensaje_error = "<p style='color: #e74c3c; font-weight: bold;'>Contraseña incorrecta.</p>";
        }
    } else {
        $mensaje_error = "<p style='color: #e74c3c; font-weight: bold;'>Usuario no encontrado.</p>";
    }
}

require 'header.php'; 
?>

<div class="container" style="max-width: 400px; margin: 40px auto; text-align: center;">
    <h2>Login Parking Iliberis</h2>

    <?php echo $mensaje_error; ?>

    <form method="post" style="margin-top: 20px;">
        <p style="text-align: left; font-weight: bold; margin-bottom: 5px;">DNI o Email:</p>
        <input type="text" name="username" required style="width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;">
        
        <p style="text-align: left; font-weight: bold; margin-bottom: 5px;">Contraseña:</p>
        <input type="password" name="password" required style="width: 100%; padding: 10px; margin-bottom: 25px; border: 1px solid #ccc; border-radius: 4px;">
        
        <button type="submit" style="width: 100%; padding: 12px; font-size: 16px;">🔑 Entrar al Sistema</button>
    </form>
</div>

<?php include 'footer.php'; ?>