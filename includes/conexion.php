<?php

// dato extra la conexion al servidor esta hecha solo que esta oculta hay un codigo en el chat de kevin para
// quitar el oculto u olvidado



/*$servername = "localhost";
$username = "root"; 
$password = ""; 
$database = "parqueaderoautomatizado";*/

$servername = "localhost";
$username = "u648222299_eapp"; 
$password = "1233444JSCWE$estefany"; 
$database = "u648222299_parking_Grado";

// Crear conexión
$conn = mysqli_connect($servername, $username, $password, $database);

// Verificar la conexión
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}
