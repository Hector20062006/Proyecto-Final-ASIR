<?php
// Datos de XAMPP
$servidor = "db";
$usuario = "root";
$password = "root"; 
$base_datos = "parking_ASIR";

$conexion = new mysqli($servidor, $usuario, $password, $base_datos);

if ($conexion->connect_error) {
    die("La conexión ha fallado: " . $conexion->connect_error);
}
?>