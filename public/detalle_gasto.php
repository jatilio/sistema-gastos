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
<html lang="es">
<head>
    <title>Detalle del gasto</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/assets/css/finance-ui.css?v=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/assets/css/finance-responsive.css?v=1">
<link rel="stylesheet" href="/assets/css/detalle.css?v=2">
<script src="/assets/js/finance-interface.js?v=1"></script>
<script src="/assets/js/finance-language.js?v=5"></script>
</head>
<body>
<main class="expense-detail-page">

<header class="expense-detail-heading"><div><h1>Detalle del gasto</h1><p><strong data-no-translate><?= htmlspecialchars($descripcion,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></strong> · <?= htmlspecialchars($mes) ?></p></div>

<a class="btn-volver" href="top10.php?anio=<?= substr($mes,0,4) ?>&mes=<?= substr($mes,5,2) ?>">← Volver al Top 10</a></header>

<div class="finance-table-scroll expense-detail-table"><table aria-label="Detalle del gasto">
    <thead><tr>
        <th>Fecha</th>
        <th>Monto</th>
        <th>Categoría</th>
        <th>Método Pago</th>
        <th>Banco</th>
        <th>Banco Pago</th>
        <th>Notas</th>
    </tr></thead><tbody>

    <?php foreach ($detalles as $d): ?>
    <tr>
        <td class="detail-field"><span class="detail-label">Fecha</span><span class="detail-value"><?= htmlspecialchars((string)($d['created_at']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
        <td class="detail-field"><span class="detail-label">Monto</span><span class="detail-value">$<?= number_format($d['amount'], 2) ?></span></td>
        <td class="detail-field"><span class="detail-label">Categoría</span><span class="detail-value"><?= htmlspecialchars((string)($d['categoria']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
        <td class="detail-field"><span class="detail-label">Método Pago</span><span class="detail-value"><?= htmlspecialchars((string)($d['metodo_pago']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
        <td class="detail-field"><span class="detail-label">Banco</span><span class="detail-value"><?= htmlspecialchars((string)($d['banco']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
        <td class="detail-field"><span class="detail-label">Banco Pago</span><span class="detail-value"><?= htmlspecialchars((string)($d['banco_pago']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
        <td class="detail-field detail-notes"><span class="detail-label">Notas</span><span class="detail-value"><?= htmlspecialchars((string)($d['notes']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
    </tr>
    <?php endforeach; ?>

<?php if(!$detalles): ?><tr><td colspan="7" class="detail-empty">No hay registros para este período.</td></tr><?php endif; ?>
</tbody></table></div></main>

</body>
</html>
