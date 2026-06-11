<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay sesión iniciada.");
}

$usuario_id = $_SESSION['usuario_id'];

// CONSULTA PRINCIPAL DEL INVENTARIO
$stmt = $pdo->prepare("
    SELECT *,
        CASE 
            WHEN cantidad_actual <= cantidad_minima THEN 'bajo_stock'
            WHEN fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE() THEN 'vencido'
            WHEN fecha_vencimiento IS NOT NULL AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'por_vencer'
            ELSE 'ok'
        END AS estado
    FROM inventario
    WHERE usuario_id = ?
    ORDER BY categoria, nombre
");
$stmt->execute([$usuario_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grafico de barras por Stock (10 productos con menor cantidad)
$stock = $pdo->prepare("
    SELECT nombre, cantidad_actual 
    FROM inventario 
    WHERE usuario_id = ?
    ORDER BY cantidad_actual ASC 
    LIMIT 10
");
$stock->execute([$usuario_id]);
$stock_data = $stock->fetchAll(PDO::FETCH_ASSOC);

// Grafico de productos por vencer por mes
$vencer_mes = $pdo->prepare("
    SELECT DATE_FORMAT(fecha_vencimiento, '%Y-%m') AS mes, COUNT(*) AS total
    FROM inventario
    WHERE usuario_id = ? AND fecha_vencimiento IS NOT NULL
    GROUP BY mes
    ORDER BY mes ASC
");
$vencer_mes->execute([$usuario_id]);
$vencer_data = $vencer_mes->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario</title>

    <!-- RUTA CORRECTA DESDE /public/gastos/ -->
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="../assets/css/inventario.css">
</head>

<body>

<h2>Inventario del Hogar</h2>
<a href="/index.php" 
   style="display:inline-block; padding:10px 15px; background:#0078ff; 
          color:white; text-decoration:none; border-radius:5px; 
          margin-bottom:20px; font-weight:bold;">
    ⬅ Volver al menú principal
</a>

<h2>Inventario de Productos</h2>

<a href="/gastos/reporte_bajo_stock.php" 
   style="display:inline-block; padding:10px 15px; background:#ff4d4d; 
          color:white; text-decoration:none; border-radius:5px; 
          margin-bottom:20px; font-weight:bold;">
    📉 Reporte de Productos con Bajo Stock
</a>



<a class="btn" href="inventario_agregar.php">+ Agregar Producto</a>

<h3>Productos con Menor Stock</h3>
<canvas id="stockChart" height="120"></canvas>

<h3>Productos por Vencer por Mes</h3>
<canvas id="vencerMesChart" height="120"></canvas>

<table>
    <thead>
        <tr>
            <th>Categoría</th>
            <th>Producto</th>
            <th>Cantidad actual</th>
            <th>Unidad</th>
            <th>Cantidad mínima</th>
            <th>Vencimiento</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($items as $item): ?>
            <tr class="<?= $item['estado'] ?>">
                <td><?= htmlspecialchars($item['categoria']) ?></td>
                <td><?= htmlspecialchars($item['nombre']) ?></td>
                <td><?= $item['cantidad_actual'] ?></td>
                <td><?= htmlspecialchars($item['unidad']) ?></td>
                <td><?= $item['cantidad_minima'] ?></td>
                <td><?= $item['fecha_vencimiento'] ?></td>

                <td>
                    <?php
                        if ($item['estado'] == 'bajo_stock') echo 'Bajo stock';
                        elseif ($item['estado'] == 'vencido') echo 'Vencido';
                        elseif ($item['estado'] == 'por_vencer') echo 'Por vencer';
                        else echo 'OK';
                    ?>
                </td>

                <td>
                    <a class="action" href="inventario_editar.php?id=<?= $item['id'] ?>">Editar</a>
                    <a class="action" href="inventario_consumo.php?id=<?= $item['id'] ?>">Consumo</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
