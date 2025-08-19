<?php
require __DIR__ . '/includes/conexion.php';

// Verificación
if (!isset($conn)) {
    die("Error: \$conn no definida");
}

// Prueba real
$result = $conn->query("SELECT 1");
if ($result) {
    echo "✅ Conexión exitosa con \$conn";
} else {
    echo "❌ Error: " . $conn->error;
}