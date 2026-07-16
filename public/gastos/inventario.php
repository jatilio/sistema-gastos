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

// Contadores para tarjetas
$total_bajo_stock = 0;
$total_por_vencer = 0;

foreach ($items as $i) {
    if ($i['estado'] === 'bajo_stock') $total_bajo_stock++;
    if ($i['estado'] === 'por_vencer') $total_por_vencer++;
}

// Datos para gráficos
$stock = $pdo->prepare("
    SELECT categoria, nombre, cantidad_actual 
    FROM inventario 
    WHERE usuario_id = ?
    ORDER BY cantidad_actual ASC 
    LIMIT 10
");
$stock->execute([$usuario_id]);
$stock_data = $stock->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Inventario del Hogar</title>

    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="../assets/css/inventario.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<a href="inventario_bajo_stock_pdf.php" class="btn-guardar">
    📄 Descargar Bajo Stock
</a>


<h2>Inventario del Hogar</h2>
<a class="btn" href="/index.php">⬅ Volver al menú principal</a>

<!-- TARJETAS SUPERIORES -->
<div class="cards">
    <div class="card red">
        <h4>Productos con Bajo Stock</h4>
        <p><?= $total_bajo_stock ?> Productos</p>
    </div>

    <div class="card green">
        <h4>Próximos a Vencer</h4>
        <p><?= $total_por_vencer ?> Productos</p>
    </div>

    <div class="card blue">
        <h4>+ Agregar Producto</h4>
        <p><a href="inventario_agregar.php" style="color:white;">Agregar</a></p>
    </div>
</div>

<!-- GRÁFICOS -->
<div class="chart-container">
    <div class="chart">
        <h3>Menor Stock</h3>
        <canvas id="stockChart" height="120"></canvas>
    </div>

    <div class="chart">
        <h3>Por Vencer por Mes</h3>
        <canvas id="vencerMesChart" height="120"></canvas>
    </div>
</div>

<!-- TABLA PRINCIPAL -->
<h3>Inventario de Productos</h3>

<table>
    <thead>
        <tr>
            <th>Categoría</th>
            <th>Producto</th>
            <th>Cantidad actual</th>
            <th>Unidad</th>
            <th>Cantidad mínima</th>
            <th>Ubicación</th>
            <th>Vencimiento</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['categoria']) ?></td>
                <td><?= htmlspecialchars($item['nombre']) ?></td>
                <td><?= $item['cantidad_actual'] ?></td>
                <td><?= htmlspecialchars($item['unidad']) ?></td>
                <td><?= $item['cantidad_minima'] ?></td>
                <td><?= htmlspecialchars($item['ubicacion']) ?></td>
                <td><?= $item['fecha_vencimiento'] ?></td>

                <td>
                    <span class="estado <?= $item['estado'] ?>">
                        <?php
                            if ($item['estado'] == 'bajo_stock') echo 'Bajo stock';
                            elseif ($item['estado'] == 'vencido') echo 'Vencido';
                            elseif ($item['estado'] == 'por_vencer') echo 'Por vencer';
                            else echo 'OK';
                        ?>
                    </span>
                </td>

                <td>
                    <a class="action editar" href="inventario_editar.php?id=<?= $item['id'] ?>">Editar</a>
                    <a class="action consumo" href="inventario_consumo.php?id=<?= $item['id'] ?>">Consumo</a>
                    <a class="action movimientos" href="inventario_movimientos.php?id=<?= $item['id'] ?>">Movimientos</a>

                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- SCRIPTS PARA GRÁFICOS -->
<script>
/* === FIX PARA EVITAR DUPLICADOS === */
const stockLabels = <?= json_encode(array_map(
    fn($i) => $i['categoria'] . ' - ' . $i['nombre'],
    $stock_data
)) ?>;

const stockValues = <?= json_encode(array_column($stock_data, 'cantidad_actual')) ?>;

new Chart(document.getElementById('stockChart'), {
    type: 'bar',
    data: {
        labels: stockLabels,
        datasets: [{
            label: 'Cantidad',
            data: stockValues,
            backgroundColor: '#28a745'
        }]
    }
});

const vencerLabels = <?= json_encode(array_column($vencer_data, 'mes')) ?>;
const vencerValues = <?= json_encode(array_column($vencer_data, 'total')) ?>;

new Chart(document.getElementById('vencerMesChart'), {
    type: 'bar',
    data: {
        labels: vencerLabels,
        datasets: [{
            label: 'Productos',
            data: vencerValues,
            backgroundColor: '#ff9800'
        }]
    }
});
</script>

</body>
</html>
