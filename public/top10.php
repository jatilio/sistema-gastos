<?php
require_once __DIR__ . '/../config/seguridad_web.php';

require_once __DIR__.'/../config/conexion.php';

// Obtener año y mes seleccionados
$anio = $_GET['anio'] ?? date('Y');
$mes = $_GET['mes'] ?? date('m');

// Consulta con filtros
$sql = "
SELECT *
FROM (
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS mes,
        descripcion,
        categoria,
        COUNT(*) AS veces_en_el_mes,
        SUM(amount) AS total_mes,
        ROW_NUMBER() OVER (
            PARTITION BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY SUM(amount) DESC
        ) AS ranking
    FROM gastos
    WHERE tipo = 'Adicional' AND usuario_id = ?
      AND YEAR(created_at) = ?
      AND MONTH(created_at) = ?
    GROUP BY mes, descripcion, categoria
) AS t
WHERE ranking <= 10
ORDER BY total_mes DESC;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['usuario_id'],$anio,$mes]);
$rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Top 10 Gastos Adicionales por Mes</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/assets/css/finance-ui.css?v=1">


<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/assets/css/finance-responsive.css?v=1">
<link rel="stylesheet" href="/assets/css/top10.css?v=2">
<script src="/assets/js/finance-interface.js?v=1"></script>
<script src="/assets/js/finance-language.js?v=5"></script>
</head>
<body>
<main class="top-expenses-page">

<header class="top-expenses-heading"><h1>Top 10 Gastos Adicionales</h1>
<a class="btn-volver" href="index.php?menu=dashboard">← Volver al Menú Principal</a></header>


<!-- FILTROS -->
<form method="GET" class="filtros">
    <div class="top-filter-field"><label for="top-year">Año:</label>
    <select id="top-year" name="anio">
        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
            <option value="<?= $y ?>" <?= $anio == $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select></div>

    <div class="top-filter-field"><label for="top-month">Mes:</label>
    <select id="top-month" name="mes">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= sprintf('%02d', $m) ?>" <?= $mes == sprintf('%02d', $m) ? 'selected' : '' ?>>
                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
            </option>
        <?php endfor; ?>
    </select></div>

    <button type="submit">Filtrar</button>
</form>

<div class="finance-table-scroll top-expenses-table">
<table aria-label="Top 10 Gastos Adicionales">
    <thead><tr>
        <th>Mes</th>
        <th>Descripción</th>
        <th>Categoría</th>
        <th>Veces</th>
        <th>Total</th>
        <th>Detalle</th>
    </tr></thead><tbody>

    <?php foreach ($rows as $r): ?>
    <tr>
        <td class="top-expense-field"><span class="top-expense-label">Mes</span><span class="top-expense-value"><?= htmlspecialchars((string)($r['mes']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
        <td class="top-expense-field"><span class="top-expense-label">Descripción</span><span class="top-expense-value"><?= htmlspecialchars((string)($r['descripcion']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
        <td class="top-expense-field"><span class="top-expense-label">Categoría</span><span class="top-expense-value"><?= htmlspecialchars((string)($r['categoria']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
        <td class="top-expense-field"><span class="top-expense-label">Veces</span><span class="top-expense-value"><?= htmlspecialchars((string)($r['veces_en_el_mes']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
        <td class="top-expense-field"><span class="top-expense-label">Total</span><span class="top-expense-value">$<?= number_format($r['total_mes'], 2) ?></span></td>
        <td class="top-expense-field"><span class="top-expense-label">Detalle</span><span class="top-expense-value">
            <a href="detalle_gasto.php?descripcion=<?= urlencode($r['descripcion']) ?>&mes=<?= htmlspecialchars((string)($r['mes']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>">
                Ver detalle
            </a>
        </span></td>
    </tr>
    <?php endforeach; ?>

<?php if(!$rows): ?><tr><td colspan="6" class="top-expenses-empty">No hay gastos adicionales para este período.</td></tr><?php endif; ?>
</tbody></table></div>
</main>

</body>
</html>
