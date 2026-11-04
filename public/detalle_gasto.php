<?php
require_once __DIR__ . '/../config/seguridad_web.php';

require_once __DIR__.'/../config/conexion.php';

$descripcion = is_string($_GET['descripcion']??null)?$_GET['descripcion']:'';
$mes = is_string($_GET['mes']??null)?$_GET['mes']:date('Y-m');
if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$mes)){http_response_code(422);exit('Mes inválido.');}

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
WHERE tipo = 'Adicional' AND usuario_id = ?
  AND descripcion = ?
  AND DATE_FORMAT(created_at, '%Y-%m') = ?
ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['usuario_id'],$descripcion,$mes]);
$detalles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detalle del gasto</title>
    <link rel="stylesheet" href="assets/css/detalle.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/assets/css/finance-responsive.css?v=1">
<script src="/assets/js/finance-interface.js?v=1"></script>
<script src="/assets/js/finance-language.js?v=1"></script>
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
        <td><?= htmlspecialchars((string)($d['created_at']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
        <td>$<?= number_format($d['amount'], 2) ?></td>
        <td><?= htmlspecialchars((string)($d['categoria']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($d['metodo_pago']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($d['banco']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($d['banco_pago']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
        <td class="notes"><?= htmlspecialchars((string)($d['notes']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
    </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
