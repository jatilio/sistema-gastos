<?php
require '../config/conexion.php';

$descripcion = $_GET['descripcion'];
$mes = $_GET['mes'];

$sql = "
SELECT 
    id,
    descripcion,
    categoria,
    amount,
    created_at,
    due_date,
    payment_date,
    metodo_pago,
    banco,
    banco_pago,
    notes
FROM gastos
WHERE tipo = 'Adicional'
  AND descripcion = ?
  AND DATE_FORMAT(created_at, '%Y-%m') = ?
ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$descripcion, $mes]);
$detalles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detalle del gasto</title>
    <link rel="stylesheet" href="assets/css/detalle.css">
</head>
<body>

<h2>Detalle de: <?= htmlspecialchars($descripcion) ?> (<?= htmlspecialchars($mes) ?>)</h2>

<a href="top10.php?anio=<?= substr($mes,0,4) ?>&mes=<?= substr($mes,5,2) ?>">← Volver al Top 10</a>

<table>
    <tr>
        <th>Fecha</th>
        <th>Monto</th>
        <th>Categoría</th>
        <th>Método Pago</th>
        <th>Banco</th>
        <th>Banco Pago</th>
        <th>Notas</th>
    </tr>

    <?php foreach ($detalles as $d): ?>
    <tr>
        <td><?= $d['created_at'] ?></td>
        <td>$<?= number_format($d['amount'], 2) ?></td>
        <td><?= $d['categoria'] ?></td>
        <td><?= $d['metodo_pago'] ?></td>
        <td><?= $d['banco'] ?></td>
        <td><?= $d['banco_pago'] ?></td>
        <td class="notes"><?= $d['notes'] ?></td>
    </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
