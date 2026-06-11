<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar autoload desde la carpeta correcta
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

// Crear instancia
$dompdf = new Dompdf();

// HTML simple
$html = "<h1>PDF funcionando ✔</h1>";

// Cargar HTML
$dompdf->loadHtml($html);

// Configurar tamaño
$dompdf->setPaper("A4", "portrait");

// Renderizar
$dompdf->render();

// Mostrar PDF en el navegador
$dompdf->stream("test.pdf", ["Attachment" => false]);