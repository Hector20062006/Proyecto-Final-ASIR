<?php
session_start();

// ── Seguridad: solo profesores ────────────────────────────────────────────────
if (!isset($_SESSION['role']) || strpos(strtolower(trim($_SESSION['role'])), 'profesor') === false) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require '../conexion.php';
require '../telegram_config.php';

// ── Recoger y limpiar la matrícula ───────────────────────────────────────────
$matricula  = strtoupper(trim($_POST['matricula'] ?? ''));
$comentario = trim($_POST['comentario'] ?? '');

if (empty($matricula)) {
    echo json_encode(['success' => false, 'message' => 'La matrícula no puede estar vacía.']);
    exit;
}

// Validación básica: letras y números, entre 4 y 10 caracteres
if (!preg_match('/^[A-Z0-9]{4,10}$/', $matricula)) {
    echo json_encode(['success' => false, 'message' => 'Formato de matrícula no válido.']);
    exit;
}

$matricula_esc  = mysqli_real_escape_string($conexion, $matricula);
$comentario_esc = mysqli_real_escape_string($conexion, $comentario);
$dni_profesor   = $_SESSION['dni'];

// ── Buscar propietario del vehículo ──────────────────────────────────────────
$sql_owner = "SELECT u.nombre, u.apellidos, u.email
              FROM vehiculos v
              INNER JOIN usuarios u ON v.dni_usuario = u.dni
              WHERE v.matricula = '$matricula_esc'
              LIMIT 1";
$res_owner = mysqli_query($conexion, $sql_owner);

$propietario_texto = '⚠️ *Propietario desconocido* (matrícula no registrada en el sistema)';
$propietario_nombre = 'Desconocido';

if ($res_owner && mysqli_num_rows($res_owner) > 0) {
    $owner = mysqli_fetch_assoc($res_owner);
    $propietario_nombre = $owner['nombre'] . ' ' . $owner['apellidos'];
    $propietario_texto  = "👤 *Propietario:* {$propietario_nombre} ({$owner['email']})";
}

// ── Obtener datos del profesor que reporta ───────────────────────────────────
$dni_esc    = mysqli_real_escape_string($conexion, $dni_profesor);
$sql_prof   = "SELECT nombre, apellidos FROM usuarios WHERE dni = '$dni_esc' LIMIT 1";
$res_prof   = mysqli_query($conexion, $sql_prof);
$datos_prof = mysqli_fetch_assoc($res_prof);
$nombre_prof = $datos_prof ? $datos_prof['nombre'] . ' ' . $datos_prof['apellidos'] : $dni_profesor;

// ── Guardar en la base de datos ───────────────────────────────────────────────
$sql_insert = "INSERT INTO mal_aparcado (matricula, reportado_por, comentario)
               VALUES ('$matricula_esc', '$dni_esc', '$comentario_esc')";

if (!mysqli_query($conexion, $sql_insert)) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el reporte en la base de datos.']);
    exit;
}

// ── Enviar notificación a Telegram ───────────────────────────────────────────
$fecha_hora = date('d/m/Y H:i:s');
$comentario_linea = !empty($comentario) ? "\n💬 *Comentario:* " . htmlspecialchars($comentario) : '';

$mensaje = "🚨 *MAL APARCAMIENTO REPORTADO* 🚨\n\n"
         . "🚗 *Matrícula:* `{$matricula}`\n"
         . "{$propietario_texto}\n"
         . "👮 *Reportado por:* {$nombre_prof}\n"
         . "🕐 *Fecha y hora:* {$fecha_hora}"
         . $comentario_linea;

$url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_POSTFIELDS     => [
        'chat_id'    => TELEGRAM_CHAT_ID,
        'text'       => $mensaje,
        'parse_mode' => 'Markdown',
    ],
]);
$tg_response = curl_exec($ch);
$tg_error    = curl_error($ch);
curl_close($ch);

$tg_data = json_decode($tg_response, true);

if (!empty($tg_error) || empty($tg_data['ok'])) {
    // El reporte se guardó en BD, pero el Telegram falló: avisamos pero no bloqueamos
    $tg_msg = !empty($tg_error) ? $tg_error : ($tg_data['description'] ?? 'Error desconocido');
    echo json_encode([
        'success' => true,
        'warning' => true,
        'message' => "Reporte guardado, pero el aviso de Telegram no se pudo enviar: {$tg_msg}. Verifica el token y el chat_id en telegram_config.php.",
        'propietario' => $propietario_nombre
    ]);
    exit;
}

echo json_encode([
    'success'     => true,
    'message'     => "✅ Reporte enviado correctamente. El aviso ha llegado al canal de Telegram.",
    'propietario' => $propietario_nombre
]);
