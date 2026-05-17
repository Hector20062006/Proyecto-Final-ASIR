<?php
// Datos de XAMPP
$servidor = getenv('DB_HOST') ?: "db";
$usuario = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD') ?: "root"; 
$base_datos = getenv('DB_NAME') ?: "parking_ASIR";

$conexion = new mysqli($servidor, $usuario, $password, $base_datos);

if ($conexion->connect_error) {
    die("La conexión ha fallado: " . $conexion->connect_error);
}
?>