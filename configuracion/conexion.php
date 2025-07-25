<?php

$servername = "localhost";
$username = "root"; 
$password = ""; 
$database = "parqueaderoautomatizado"; 

// Crear conexión
$conn = mysqli_connect($servername, $username, $password, $database);

// Verificar la conexión
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}
