<?php

$servername = " mysql.hostinger.com";
$username = "u648222299_eapp"; 
$password = "1233444JSCWE$estefany"; 
$database = "u648222299_parking_Grado";

// Crear conexión
$conn = mysqli_connect($servername, $username, $password, $database);

// Verificar la conexión
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}
