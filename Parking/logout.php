<?php
// 1. Reanudamos la sesión actual
session_start();

// 2. Vaciamos todas las variables de sesión ($_SESSION)
session_unset();

// 3. Destruimos la sesión por completo en el servidor
session_destroy();

// 4. Redirigimos al usuario a la portada
header("Location: login.php");
exit; // Siempre hay que poner exit después de un header()
?>