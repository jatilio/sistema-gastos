<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay sesión iniciada.");
}

$usuario_id = $_SESSION['usuario_id'];

// Consulta con PDO
$stmt = $pdo->prepare("
    SELECT *
    FROM inventario
    WHERE usuario_id = ? 
      AND cantidad_actual <= cantidad_minima
    ORDER BY cantidad_actual ASC
");
$stmt->execute([$usuario_id]);
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Bajo Stock</title>

<style>
    body { font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; }

    h2 {
        color:#333;
        font-size:26px;
        margin-bottom:20px;
        border-left:6px solid #ff4d4d;
        padding-left:10px;
    }

    table { width:100%; border-collapse:collapse; background:white; }
    th, td { padding:10px; border:1px solid #ccc; text-align:left; }
    th { background:#ff4d4d; color:white; }

    tr:nth-child(even) { background:#f9f9f9; }

    .btn-volver {
        display:inline-block; padding:10px 15px; background:#0078ff;
        color:white; text-decoration:none; border-radius:5px;
        margin-bottom:20px; font-weight:bold;
    }
</style>
</head>
<body>

<a href="/gastos/inventario.php" class="btn-volver">⬅ Volver al Inventario</a>

<h2>📉 Reporte de Productos con Bajo Stock</h2>

<a href="/gastos/reporte_bajo_stock_pdf.php" 
   style="display:inline-block; padding:10px 15px; background:#0078ff; 
          color:white; text-decoration:none; border-radius:5px; 
          margin-bottom:20px; font-weight:bold;">
    📄 Exportar a PDF
</a>


<table>
    <tr>
        <th>Producto</th>
        <th>Categoría</th>
        <th>Cantidad actual</th>
        <th>Cantidad mínima</th>
        <th>% Stock</th>
        <th>Ubicación</th>
        <th>Vencimiento</th>
        <th>Estado</th>
    </tr>

    <?php foreach ($resultado as $row): ?>

        <?php
        // Calcular porcentaje
        $porcentaje = 0;
        if ($row['cantidad_minima'] > 0) {
            $porcentaje = ($row['cantidad_actual'] / $row['cantidad_minima']) * 100;
        }

        // Determinar estado textual
        if ($row['cantidad_actual'] == 0) {
            $estado = "Crítico";
        } elseif ($porcentaje <= 25) {
            $estado = "Muy bajo";
        } elseif ($porcentaje <= 50) {
            $estado = "Bajo";
        } else {
            $estado = "Advertencia";
        }

        // Manejo de fecha de vencimiento
        if (!$row['fecha_vencimiento']) {
            $fecha = "Sin fecha";
        } else {
            $dias = (strtotime($row['fecha_vencimiento']) - time()) / 86400;

            if ($dias < 0) {
                $fecha = "Vencido";
            } elseif ($dias <= 7) {
                $fecha = "Por vencer";
            } else {
                $fecha = $row['fecha_vencimiento'];
            }
        }
        ?>

        <tr>
            <td><?= $row['nombre'] ?></td>
            <td><?= $row['categoria'] ?></td>
            <td><?= $row['cantidad_actual'] ?></td>
            <td><?= $row['cantidad_minima'] ?></td>
            <td><?= round($porcentaje, 0) ?>%</td>
            <td><?= $row['ubicacion'] ?></td>
            <td><?= $fecha ?></td>
            <td><?= $estado ?></td>
        </tr>

    <?php endforeach; ?>

</table>

</body>
</html>
