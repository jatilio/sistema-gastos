<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay sesión iniciada.");
}

$usuario_id = $_SESSION['usuario_id'];

/* ===========================
   TARJETAS PRINCIPALES
   =========================== */

// Total productos
$total = $pdo->prepare("SELECT COUNT(*) FROM inventario WHERE usuario_id = ?");
$total->execute([$usuario_id]);
$total_productos = $total->fetchColumn();

// Bajo stock (CORREGIDO)
$bajo = $pdo->prepare("
    SELECT COUNT(*) 
    FROM inventario 
    WHERE usuario_id = ? 
    AND cantidad_actual <= cantidad_minima
");
$bajo->execute([$usuario_id]);
$bajo_stock = $bajo->fetchColumn();

// Vencidos
$vencidos = $pdo->prepare("
    SELECT COUNT(*) 
    FROM inventario 
    WHERE usuario_id = ? 
    AND fecha_vencimiento IS NOT NULL 
    AND fecha_vencimiento < CURDATE()
");
$vencidos->execute([$usuario_id]);
$total_vencidos = $vencidos->fetchColumn();

// Por vencer (7 días)
$por_vencer = $pdo->prepare("
    SELECT COUNT(*) 
    FROM inventario 
    WHERE usuario_id = ? 
    AND fecha_vencimiento IS NOT NULL 
    AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$por_vencer->execute([$usuario_id]);
$total_por_vencer = $por_vencer->fetchColumn();

/* ===========================
   GRÁFICO: Categorías
   =========================== */
$categorias = $pdo->prepare("
    SELECT categoria, COUNT(*) AS total
    FROM inventario
    WHERE usuario_id = ?
    GROUP BY categoria
");
$categorias->execute([$usuario_id]);
$cat_data = $categorias->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   GRÁFICO: Stock bajo (CORREGIDO)
   =========================== */
$stock = $pdo->prepare("
    SELECT nombre, cantidad_actual 
    FROM inventario 
    WHERE usuario_id = ?
    ORDER BY cantidad_actual ASC 
    LIMIT 10
");
$stock->execute([$usuario_id]);
$stock_data = $stock->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   GRÁFICO: Vencimiento por mes
   =========================== */
$vencer_mes = $pdo->prepare("
    SELECT DATE_FORMAT(fecha_vencimiento, '%Y-%m') AS mes, COUNT(*) AS total
    FROM inventario
    WHERE usuario_id = ? 
    AND fecha_vencimiento IS NOT NULL
    GROUP BY mes
    ORDER BY mes ASC
");
$vencer_mes->execute([$usuario_id]);
$vencer_data = $vencer_mes->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   FUNCIÓN PARA TARJETAS
   =========================== */
function estadoTarjeta($valor) {
    if ($valor == 0) return "ok";
    if ($valor <= 3) return "alerta";
    return "peligro";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Inventario</title>
    <link rel="stylesheet" href="../assets/css/inventario.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<h2>Dashboard del Inventario</h2>

<a class="btn" href="inventario.php">← Volver al Inventario</a>

<div class="dashboard">

    <div class="card <?= estadoTarjeta($total_productos) ?>">
        <h3>Total de Productos</h3>
        <p><?= $total_productos ?></p>
    </div>

    <div class="card <?= estadoTarjeta($bajo_stock) ?>">
        <h3>Bajo Stock</h3>
        <p><?= $bajo_stock ?></p>
    </div>

    <div class="card <?= estadoTarjeta($total_vencidos) ?>">
        <h3>Vencidos</h3>
        <p><?= $total_vencidos ?></p>
    </div>

    <div class="card <?= estadoTarjeta($total_por_vencer) ?>">
        <h3>Por Vencer (7 días)</h3>
        <p><?= $total_por_vencer ?></p>
    </div>

</div>

<!-- PASAR DATOS PHP → JS -->
<script>
    const categoriasLabels = [<?php foreach ($cat_data as $c) echo "'" . $c['categoria'] . "',"; ?>];
    const categoriasData = [<?php foreach ($cat_data as $c) echo $c['total'] . ","; ?>];

    const stockLabels = [<?php foreach ($stock_data as $s) echo "'" . $s['nombre'] . "',"; ?>];
    const stockData = [<?php foreach ($stock_data as $s) echo $s['cantidad_actual'] . ","; ?>];

    const vencerMesLabels = [<?php foreach ($vencer_data as $v) echo "'" . $v['mes'] . "',"; ?>];
    const vencerMesData = [<?php foreach ($vencer_data as $v) echo $v['total'] . ","; ?>];
</script>

<script src="../assets/js/inventario_dashboard.js"></script>

</body>
</html>
