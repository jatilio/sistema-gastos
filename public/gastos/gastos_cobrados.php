<?php
require_once __DIR__ . '/../../config/seguridad_web.php';

if (session_status() === PHP_SESSION_NONE) {
    if(session_status()!==PHP_SESSION_ACTIVE)session_start();
}

include __DIR__ . "/../../config/db.php";

$usuario_id = $_SESSION['usuario_id'];

// -----------------------------
// FILTRO DE MES Y AÑO (AUTO MES ACTUAL)
// -----------------------------
if (!isset($_GET['month']) && !isset($_GET['year'])) {
    $month = (int)date('m');
    $year  = (int)date('Y');
} else {
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
    $year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
}

// -----------------------------
// CONSULTA FINAL
// -----------------------------
$sql = "
    SELECT 
        g.id AS id,
        mp.id AS mantenimiento_id,
        mp.descripcion,
        mp.due_date,
        mp.amount,

        COALESCE(g.pagado, 'No') AS pagado,
        g.payment_date,
        g.confirmation,
        g.tarjeta_id,
        g.banco_pago,
        g.metodo_pago,
        g.banco

    FROM mantenimiento_pagos mp

    LEFT JOIN (
        SELECT gasto.*
        FROM gastos gasto
        INNER JOIN (
            SELECT MAX(id) AS id
            FROM gastos
            WHERE usuario_id = :usuario_id
              AND (
                    (pagado = 'Si' AND MONTH(payment_date) = :month AND YEAR(payment_date) = :year)
                    OR pagado = 'No'
                  )
            GROUP BY mantenimiento_id
        ) ultimo ON ultimo.id = gasto.id
    ) g ON g.mantenimiento_id = mp.id

    WHERE mp.usuario_id = :usuario_id
      AND mp.activo = 1
      AND mp.frecuencia = 'M'
      AND (
            (MONTH(mp.due_date) = :month AND YEAR(mp.due_date) = :year)
            OR g.pagado = 'Si'
            OR g.pagado = 'No'
          )


    ORDER BY mp.due_date ASC
";


$stmt = $pdo->prepare($sql);
$stmt->execute([
    'usuario_id' => $usuario_id,
    'month'      => $month,
    'year'       => $year
]);

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pagos del Mes</title>

<style>
    body { font-family: Arial; background: #f4f4f4; padding: 20px; }
    table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
    th { background: #333; color: white; padding: 10px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #ddd; }
    .pagado { background-color: #d4edda; color: #155724; }
    .nopagado { background-color: #f8d7da; color: #721c24; }
    .btn-pagar { background: #007bff; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
    .btn-pagar:hover { background: #0056b3; }
    .filtro { margin-bottom: 20px; }
</style>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/assets/css/finance-responsive.css?v=1">
<script src="/assets/js/finance-interface.js?v=1"></script>
<script src="/assets/js/finance-language.js?v=1"></script>
</head>
<body>

<h2>Pagos del Mes</h2>

<!-- FILTRO -->
<form method="GET" action="index.php" class="filtro">
    <input type="hidden" name="menu" value="gastos_cobrados">

    <label>Mes:</label>
    <select name="month">
        <?php
        for ($m = 1; $m <= 12; $m++) {
            $selected = ($m == $month) ? "selected" : "";
            echo "<option value='$m' $selected>" . date("F", mktime(0,0,0,$m,1)) . "</option>";
        }
        ?>
    </select>

    <label>Año:</label>
    <select name="year">
        <?php
        for ($y = date('Y') - 5; $y <= date('Y') + 1; $y++) {
            $selected = ($y == $year) ? "selected" : "";
            echo "<option value='$y' $selected>$y</option>";
        }
        ?>
    </select>

    <button type="submit">Filtrar</button>
</form>

<table>
    <tr>
        <th>#</th>
        <th>Descripción</th>
        <th>Fecha</th>
        <th>Monto</th>
        <th>Pagado</th>
        <th>Fecha pago</th>
        <th>Confirmación</th>
        <th>Tarjeta ID</th>
        <th>Banco Pago</th>
        <th>Método</th>
        <th>Banco</th>
        <th>Acción</th>
    </tr>

    <?php foreach ($result as $row): ?>
        <?php $clase = ($row['pagado'] == 'Si') ? "pagado" : "nopagado"; ?>

        <tr class="<?= $clase ?>">
            <td><?= htmlspecialchars((string)($row['mantenimiento_id']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
            <td><?= htmlspecialchars($row['descripcion']) ?></td>
            <td><?= htmlspecialchars((string)($row['due_date']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
            <td>$<?= number_format($row['amount'], 2) ?></td>
            <td><?= htmlspecialchars((string)($row['pagado']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['payment_date'] ?: '-'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['confirmation'] ?: '-'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['tarjeta_id'] ?: '-'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['banco_pago'] ?: '-'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['metodo_pago'] ?: '-'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($row['banco'] ?: '-'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></td>

            <td>
                <?php if ($row['pagado'] == 'No' && !empty($row['id'])): ?>
                    <form action="/gastos/pagar.php" method="POST">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="mantenimiento_id" value="<?= htmlspecialchars((string)($row['mantenimiento_id']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>">
                        <input type="hidden" name="descripcion" value="<?= htmlspecialchars($row['descripcion']) ?>">
                        <input type="hidden" name="amount" value="<?= htmlspecialchars((string)($row['amount']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>">
                        <input type="hidden" name="due_date" value="<?= htmlspecialchars((string)($row['due_date']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>">
                        <button class="btn-pagar" type="submit">Pagar</button>
                    </form>
                <?php elseif ($row['pagado'] === 'Si'): ?>
                    ✔
                <?php else: ?>
                    Pendiente de generar
                <?php endif; ?>
            </td>
        </tr>

    <?php endforeach; ?>

</table>

</body>
</html>
