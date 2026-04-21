<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

// Comprobamos que ha llegado un DNI por la URL
if (isset($_GET["dni"])) {
    $dni_borrar = $_GET["dni"];
    
    // ESCUDO: Evitamos que el Jefe se borre a sí mismo
    if ($dni_borrar === $_SESSION['dni']) {
        include "../header2.php";
        echo "<h3 style='color: red;'>Error: No puedes borrar tu propio usuario por seguridad.</h3>";
        echo "<a href='borrar_usuario.php'><button>Volver</button></a>";
        include "../footer2.php";
        exit; // Detenemos el código aquí
    }

    include "../conexion.php";

    // Preparamos la orden de borrado (¡Ojo, en esta tabla la clave primaria es dni, no id!)
    $sql = "DELETE FROM usuarios WHERE dni = '$dni_borrar'";

    include "../header2.php"; // Cargamos el diseño superior
    
    if ($conexion->query($sql) === TRUE) {
        echo "<h3 > El usuario se ha borrado correctamente.</h3>";
        echo "<br><a href='index.php'><button>Volver al Panel Principal</button></a>";
    } else {
        echo "<h3 style='color: red;'>Error al borrar el usuario: " . $conexion->error . "</h3>";
        echo "<br><a href='borrar_usuario.php'><button>Volver atrás</button></a>";
    }
    
    include "../footer2.php"; // Cargamos el diseño inferior
    
} else {
    // Si alguien entra aquí sin elegir a nadie, lo mandamos al panel
    header("Location: index.php");
}
?>