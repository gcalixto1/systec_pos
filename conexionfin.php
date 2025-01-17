<?php
$host = "localhost";
$user = "root";
$clave = "";
$bd = "sis_venta";

// Establecer la conexión a la base de datos
$conexion = new mysqli($host, $user, $clave, $bd);

// Verificar si hay errores de conexión
if ($conexion->connect_error) {
    die("No se pudo conectar a la base de datos: " . $conexion->connect_error);
}
mysqli_set_charset($conexion, "utf8");
?>