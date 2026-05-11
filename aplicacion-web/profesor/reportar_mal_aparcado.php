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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['matricula'])) {
    $matricula = strtoupper(trim($_POST['matricula']));
    $matricula = mysqli_real_escape_string($conexion, $matricula);
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
        $sql_insert = "INSERT INTO reportes_mal_aparcado (matricula, dni_reportador) VALUES ('$matricula', '$dni_reportador')";
        if (mysqli_query($conexion, $sql_insert)) {
            
            // 3. Enviar notificación por Telegram
            $texto_telegram = "⚠️ <b>AVISO DE ESTACIONAMIENTO</b> ⚠️\n\n";
            $texto_telegram .= "El vehículo con matrícula <b>$matricula</b> está mal aparcado.\n";
            $texto_telegram .= "Propietario/a: <b>$nombre_dueno</b>\n\n";
            $texto_telegram .= "Por favor, retírelo lo antes posible para no obstruir el paso. Gracias.";

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
    <div class="card-reporte" style="max-width: 600px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h2 style="color: #c0392b; text-align: center; margin-bottom: 25px;">
            <i class="fas fa-exclamation-triangle"></i> Reportar Mal Aparcado
        </h2>
        
        <p style="text-align: center; color: #666; margin-bottom: 30px;">
            Introduce la matrícula del vehículo que está obstaculizando o mal estacionado para avisar al propietario.
        </p>

        <?php if ($mensaje_status): ?>
            <div class="alerta <?php echo $tipo_status; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center; <?php echo ($tipo_status == 'success') ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                <?php echo $mensaje_status; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
            <div class="form-group">
                <label for="matricula" style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">Matrícula del Vehículo</label>
                <input type="text" name="matricula" id="matricula" placeholder="Ej: 1234ABC" required 
                       style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 18px; text-transform: uppercase; text-align: center; letter-spacing: 2px;">
            </div>

            <button type="submit" style="background-color: #c0392b; color: white; padding: 15px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.3s;">
                <i class="fab fa-telegram-plane"></i> ENVIAR AVISO A TELEGRAM
            </button>
            
            <a href="index.php" style="text-align: center; color: #7f8c8d; text-decoration: none; font-size: 14px; margin-top: 10px;">
                <i class="fas fa-arrow-left"></i> Volver al Panel
            </a>
        </form>
    </div>
</div>

<style>
    .card-reporte button:hover {
        background-color: #a93226 !important;
    }
    input:focus {
        border-color: #c0392b !important;
        outline: none;
    }
</style>

<?php require '../footer2.php'; ?>
