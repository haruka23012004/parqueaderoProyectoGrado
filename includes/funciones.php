<?php
function generateQRCode($data, $path) {
    try {
        // Verificar y cargar autoload si es necesario
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        if (!class_exists('Endroid\QrCode\QrCode') && file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
        
        // Verificar que las clases existen después de cargar autoload
        if (!class_exists('Endroid\QrCode\QrCode')) {
            throw new Exception("Clase QrCode no disponible");
        }
        
        // Endroid QR Code 6.x 
        $qrCode = new \Endroid\QrCode\QrCode($data);
        
            
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);
        
        // Guardar el archivo
        $result->saveToFile($path);
        
        // Verificar que el archivo se creó
        if (!file_exists($path)) {
            throw new Exception("El archivo QR no se creó en: " . $path);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error generando QR: " . $e->getMessage());
        return false;
    }
}