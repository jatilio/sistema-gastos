<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay sesión iniciada.");
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener productos con bajo stock
$stmt = $pdo->prepare("
    SELECT nombre, categoria, cantidad_actual, cantidad_minima, unidad
    FROM inventario
    WHERE usuario_id = ?
    AND cantidad_actual <= cantidad_minima
    ORDER BY cantidad_actual ASC
");
$stmt->execute([$usuario_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// HTML del PDF
$html = '
<h2 style="text-align:center; font-family:Segoe UI;">Productos con Bajo Stock</h2>
<p style="text-align:center; font-family:Segoe UI;">Listado generado para reposición de inventario</p>

<table width="100%" border="1" cellspacing="0" cellpadding="8" 
style="border-collapse:collapse; font-family:Segoe UI; font-size:14px;">
    <thead>
        <tr style="background:#f2f2f2;">
            <th>Producto</th>
            <th>Categoría</th>
            <th>Actual</th>
            <th>Mínimo</th>
            <th>Unidad</th>
        </tr>
    </thead>
    <tbody>';

foreach ($productos as $p) {
    $html .= "
        <tr>
            <td>{$p['nombre']}</td>
            <td>{$p['categoria']}</td>
            <td>{$p['cantidad_actual']}</td>
            <td>{$p['cantidad_minima']}</td>
            <td>{$p['unidad']}</td>
        </tr>
    ";
}

$html .= '
    </tbody>
</table>
';

// Configurar Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "portrait");
$dompdf->render();

// Descargar PDF
$dompdf->stream("productos_bajo_stock.pdf", ["Attachment" => true]);
