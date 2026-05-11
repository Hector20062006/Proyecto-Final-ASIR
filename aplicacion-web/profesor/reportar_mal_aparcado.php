<?php
session_start();
require '../conexion.php';
require '../telegram_config.php';

// Seguridad: Solo profesores
if (!isset($_SESSION['role']) || strpos(strtolower($_SESSION['role']), 'profesor') === false) {
    header("Location: ../login.php");
    exit;
}

$mensaje_status = "";
$tipo_status = "";

// Asegurar que la tabla existe (fallback por si no se ha ejecutado la acción de deploy)
$sql_check = "CREATE TABLE IF NOT EXISTS `reportes_mal_aparcado` (
  `id_reporte` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(10) NOT NULL,
  `dni_reportador` varchar(9) NOT NULL,
  `motivo` text DEFAULT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_reporte`),
  FOREIGN KEY (`matricula`) REFERENCES `vehiculos` (`matricula`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`dni_reportador`) REFERENCES `usuarios` (`dni`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conexion, $sql_check);

// Asegurar que la columna 'motivo' existe (para instalaciones existentes en MySQL estándar)
$check_column = mysqli_query($conexion, "SHOW COLUMNS FROM `reportes_mal_aparcado` LIKE 'motivo'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($conexion, "ALTER TABLE `reportes_mal_aparcado` ADD COLUMN `motivo` text DEFAULT NULL AFTER `dni_reportador`");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['matricula'])) {
    $matricula = strtoupper(trim($_POST['matricula']));
    $matricula = mysqli_real_escape_string($conexion, $matricula);
    $motivo = isset($_POST['motivo']) ? mysqli_real_escape_string($conexion, trim($_POST['motivo'])) : "";
    $dni_reportador = $_SESSION['dni'];

    // 1. Verificar si la matrícula existe y quién es el dueño
    $sql = "SELECT v.matricula, u.nombre, u.apellidos, u.id_rol 
            FROM vehiculos v 
            JOIN usuarios u ON v.dni_usuario = u.dni 
            WHERE v.matricula = '$matricula'";
    
    $res = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($res) > 0) {
        $datos = mysqli_fetch_assoc($res);
        $nombre_dueno = $datos['nombre'] . " " . $datos['apellidos'];
        
        // 2. Registrar en la base de datos
        $sql_insert = "INSERT INTO reportes_mal_aparcado (matricula, dni_reportador, motivo) VALUES ('$matricula', '$dni_reportador', '$motivo')";
        if (mysqli_query($conexion, $sql_insert)) {
            
            // 3. Enviar notificación por Telegram
            $texto_telegram = "⚠️ <b>AVISO DE ESTACIONAMIENTO</b> ⚠️\n\n";
            $texto_telegram .= "El vehículo con matrícula <b>$matricula</b> está mal aparcado.\n";
            $texto_telegram .= "Propietario/a: <b>$nombre_dueno</b>\n";
            
            if (!empty($motivo)) {
                $texto_telegram .= "Motivo: <i>$motivo</i>\n";
            }
            
            $texto_telegram .= "\nPor favor, retírelo lo antes posible para no obstruir el paso. Gracias.";

            $resultado_tel = enviarMensajeTelegram($texto_telegram);
            
            $mensaje_status = "Reporte enviado correctamente. Se ha notificado al propietario por Telegram.";
            $tipo_status = "success";
        } else {
            $mensaje_status = "Error al registrar el reporte en la base de datos.";
            $tipo_status = "error";
        }
    } else {
        $mensaje_status = "La matrícula introducida no está registrada en el sistema.";
        $tipo_status = "error";
    }
}

require '../header2.php';
?>

<div class="container">
    <div class="card-reporte">
        <h2><i class="fas fa-exclamation-triangle"></i> Reportar Mal Aparcado</h2>
        
        <p>Introduce la matrícula del vehículo y opcionalmente un motivo para avisar al propietario.</p>

        <?php if ($mensaje_status): ?>
            <div class="alerta <?php echo $tipo_status; ?>">
                <?php echo $mensaje_status; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="form-reporte">
            <div class="form-group">
                <label for="matricula">Matrícula del Vehículo</label>
                <input type="text" name="matricula" id="matricula" placeholder="1234ABC" required>
            </div>

            <div class="form-group">
                <label for="motivo">Motivo o Mensaje (Opcional)</label>
                <textarea name="motivo" id="motivo" rows="3" placeholder="Ej: Está bloqueando la salida del parking..."></textarea>
            </div>

            <div style="margin-top: 10px;">
                <button type="submit" class="btn-reporte">
                    <i class="fab fa-telegram-plane"></i> ENVIAR AVISO A TELEGRAM
                </button>
            </div>
            
            <a href="index.php" class="link-volver">
                <i class="fas fa-arrow-left"></i> Volver al Panel de Control
            </a>
        </form>
    </div>
</div>

<?php require '../footer2.php'; ?>
