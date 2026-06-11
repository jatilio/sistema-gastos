<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay sesión iniciada.");
}

$usuario_id = $_SESSION['usuario_id'];

// Consulta
$stmt = $pdo->prepare("
    SELECT *
    FROM inventario
    WHERE usuario_id = ? 
      AND cantidad_actual <= cantidad_minima
    ORDER BY cantidad_actual ASC
");
$stmt->execute([$usuario_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

// HTML
$html = '
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size:12px; }
    h2 { color:#333; border-left:6px solid #ff4d4d; padding-left:10px; }
    table { width:100%; border-collapse:collapse; margin-top:20px; }
    th, td { border:1px solid #ccc; padding:8px; text-align:left; }
    th { background:#ff4d4d; color:white; }
    tr:nth-child(even) { background:#f9f9f9; }
</style>

<h2>Reporte de Productos con Bajo Stock</h2>

<table>
    <tr>
        <th>#</th>
        <th>Producto</th>
        <th>Categoría</th>
        <th>Cantidad actual</th>
        <th>Cantidad mínima</th>
        <th>% Stock</th>
        <th>Ubicación</th>
        <th>Vencimiento</th>
        <th>Estado</th>
    </tr>
';

$correlativo = 1;

foreach ($items as $row) {

    // Porcentaje
    $porcentaje = 0;
    if ($row["cantidad_minima"] > 0) {
        $porcentaje = ($row["cantidad_actual"] / $row["cantidad_minima"]) * 100;
    }

    // Estado
    if ($row["cantidad_actual"] == 0) {
        $estado = "Crítico";
    } elseif ($porcentaje <= 25) {
        $estado = "Muy bajo";
    } elseif ($porcentaje <= 50) {
        $estado = "Bajo";
    } else {
        $estado = "Advertencia";
    }

    // Vencimiento
    if (!$row["fecha_vencimiento"]) {
        $fecha = "Sin fecha";
    } else {
        $dias = (strtotime($row["fecha_vencimiento"]) - time()) / 86400;
        if ($dias < 0) {
            $fecha = "Vencido";
        } elseif ($dias <= 7) {
            $fecha = "Por vencer";
        } else {
            $fecha = $row["fecha_vencimiento"];
        }
    }

    // Agregar fila
    $html .= "
    <tr>
        <td>{$correlativo}</td>
        <td>{$row['nombre']}</td>
        <td>{$row['categoria']}</td>
        <td>{$row['cantidad_actual']}</td>
        <td>{$row['cantidad_minima']}</td>
        <td>" . round($porcentaje, 0) . "%</td>
        <td>{$row['ubicacion']}</td>
        <td>{$fecha}</td>
        <td>{$estado}</td>
    </tr>";

    $correlativo++;
}

$html .= "</table>";

// Render PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_bajo_stock.pdf", ["Attachment" => true]);
exit;
