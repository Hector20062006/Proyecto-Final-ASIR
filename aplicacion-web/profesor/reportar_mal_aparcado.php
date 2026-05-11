<?php
// Versión BLINDADA para evitar salida de basura
ob_start();
session_start();

// ── Seguridad ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['role']) || strpos(strtolower(trim($_SESSION['role'])), 'profesor') === false) {
    header('Content-Type: application/json; charset=utf-8');
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Sesión no válida o caducada. Reintenta login.']));
}

header('Content-Type: application/json; charset=utf-8');

require_once '../conexion.php';
require_once '../telegram_config.php';

$matricula  = strtoupper(trim($_POST['matricula'] ?? ''));
$comentario = trim($_POST['comentario'] ?? '');

if (empty($matricula)) {
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Falta la matrícula.']));
}

$matricula_esc  = mysqli_real_escape_string($conexion, $matricula);
$comentario_esc = mysqli_real_escape_string($conexion, $comentario);
$dni_profesor   = $_SESSION['dni'];

// ── Buscar propietario ───────────────────────────────────────────────────────
$sql_owner = "SELECT u.nombre, u.apellidos, u.email FROM vehiculos v INNER JOIN usuarios u ON v.dni_usuario = u.dni WHERE v.matricula = '$matricula_esc' LIMIT 1";
$res_owner = mysqli_query($conexion, $sql_owner);
$propietario_texto = '⚠️ Propietario desconocido';
$propietario_nombre = 'Desconocido';

if ($res_owner && mysqli_num_rows($res_owner) > 0) {
    $owner = mysqli_fetch_assoc($res_owner);
    $propietario_nombre = $owner['nombre'] . ' ' . $owner['apellidos'];
    $propietario_texto = "👤 Propietario: {$propietario_nombre}";
}

// ── Datos del reportador ─────────────────────────────────────────────────────
$dni_esc = mysqli_real_escape_string($conexion, $dni_profesor);
$sql_prof = "SELECT nombre, apellidos FROM usuarios WHERE dni = '$dni_esc' LIMIT 1";
$res_prof = mysqli_query($conexion, $sql_prof);
$datos_prof = mysqli_fetch_assoc($res_prof);
$nombre_prof = $datos_prof ? $datos_prof['nombre'] . ' ' . $datos_prof['apellidos'] : $dni_profesor;

// ── Guardar ──────────────────────────────────────────────────────────────────
$sql_insert = "INSERT INTO mal_aparcado (matricula, reportado_por, comentario) VALUES ('$matricula_esc', '$dni_esc', '$comentario_esc')";
if (!mysqli_query($conexion, $sql_insert)) {
    $err = mysqli_error($conexion);
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Error BD: ' . $err]));
}

// ── Telegram ─────────────────────────────────────────────────────────────────
$fecha_hora = date('d/m/Y H:i');
$msg = "🚨 MAL APARCAMIENTO 🚨\n\nMatrícula: {$matricula}\n{$propietario_texto}\nReportado por: {$nombre_prof}\nFecha: {$fecha_hora}";
if(!empty($comentario)) $msg .= "\nNota: {$comentario}";

$url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
$ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => http_build_query(['chat_id' => TELEGRAM_CHAT_ID, 'text' => $msg]), 'timeout' => 5], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$resp = @file_get_contents($url, false, $ctx);
$data_tg = json_decode($resp, true);

// ── RESPUESTA FINAL ──────────────────────────────────────────────────────────
// Limpiamos CUALQUIER cosa que se haya podido colar (espacios, warnings...)
ob_end_clean();

if (!$data_tg || !$data_tg['ok']) {
    $error_tg = $data_tg['description'] ?? 'Error de conexión con Telegram';
    die(json_encode([
        'success' => true, 
        'warning' => true, 
        'message' => 'Reporte guardado, pero Telegram falló: ' . $error_tg,
        'propietario' => $propietario_nombre
    ]));
}

die(json_encode([
    'success' => true, 
    'message' => '¡Reporte enviado y aviso de Telegram enviado!',
    'propietario' => $propietario_nombre
]));
