<?php
/**
 * Configuración de Telegram para notificaciones
 */

// Sustituye con el token de tu bot de Telegram
define('TELEGRAM_BOT_TOKEN', '8719535507:AAEeH0PJAhU3_AA9Bw3-Is4wWsKb_niB7Pw');

// Sustituye con el ID del canal o grupo común
define('TELEGRAM_CHAT_ID', '-1003926223594');

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
