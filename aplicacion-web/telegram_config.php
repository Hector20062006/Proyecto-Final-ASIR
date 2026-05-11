<?php
/**
 * Configuración de Telegram para notificaciones
 */

// Se obtienen de las variables de entorno configuradas en Docker o .env
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN'));
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID'));

/**
 * Función para enviar mensajes a Telegram
 */
function enviarMensajeTelegram($mensaje) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $mensaje,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Opcional, dependiendo del entorno

    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>
