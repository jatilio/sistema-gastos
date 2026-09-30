<?php
require '../config/conexion.php';

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
    WHERE tipo = 'Adicional'
      AND YEAR(created_at) = ?
      AND MONTH(created_at) = ?
    GROUP BY mes, descripcion, categoria
) AS t
WHERE ranking <= 10
ORDER BY total_mes DESC;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$anio, $mes]);
$rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Top 10 Gastos Adicionales por Mes</title>
    <link rel="stylesheet" href="assets/css/top10.css">


</head>
<body>

<h2>Top 10 Gastos Adicionales</h2>
<a class="btn-volver" href="index.php?menu=dashboard">← Volver al Menú Principal</a>


<!-- FILTROS -->
<form method="GET" class="filtros">
    <label>Año:</label>
    <select name="anio">
        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
            <option value="<?= $y ?>" <?= $anio == $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>

    <label>Mes:</label>
    <select name="mes">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= sprintf('%02d', $m) ?>" <?= $mes == sprintf('%02d', $m) ? 'selected' : '' ?>>
                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
            </option>
        <?php endfor; ?>
    </select>

    <button type="submit">Filtrar</button>
</form>

<table>
    <tr>
        <th>Mes</th>
        <th>Descripción</th>
        <th>Categoría</th>
        <th>Veces</th>
        <th>Total</th>
        <th>Detalle</th>
    </tr>

    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= $r['mes'] ?></td>
        <td><?= $r['descripcion'] ?></td>
        <td><?= $r['categoria'] ?></td>
        <td><?= $r['veces_en_el_mes'] ?></td>
        <td>$<?= number_format($r['total_mes'], 2) ?></td>
        <td>
            <a href="detalle_gasto.php?descripcion=<?= urlencode($r['descripcion']) ?>&mes=<?= $r['mes'] ?>">
                Ver detalle
            </a>
        </td>
    </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
