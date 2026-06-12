<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
        SELECT *
        FROM gastos
        WHERE usuario_id = :usuario_id
          AND (
                (pagado = 'Si' AND MONTH(payment_date) = :month AND YEAR(payment_date) = :year)
                OR pagado = 'No'
              )
        GROUP BY mantenimiento_id
    ) g ON g.mantenimiento_id = mp.id

    WHERE mp.usuario_id = :usuario_id
      AND mp.activo = 1
      AND mp.frecuencia = 'M'
      AND (
            (MONTH(mp.due_date) = :month AND YEAR(mp.due_date) = :year)
            OR g.pagado = 'Si'
            OR g.pagado = 'No'
          )

    GROUP BY mp.id
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
            <td><?= $row['mantenimiento_id'] ?></td>
            <td><?= htmlspecialchars($row['descripcion']) ?></td>
            <td><?= $row['due_date'] ?></td>
            <td>$<?= number_format($row['amount'], 2) ?></td>
            <td><?= $row['pagado'] ?></td>
            <td><?= $row['payment_date'] ?: '-' ?></td>
            <td><?= $row['confirmation'] ?: '-' ?></td>
            <td><?= $row['tarjeta_id'] ?: '-' ?></td>
            <td><?= $row['banco_pago'] ?: '-' ?></td>
            <td><?= $row['metodo_pago'] ?: '-' ?></td>
            <td><?= $row['banco'] ?: '-' ?></td>

            <td>
                <?php if ($row['pagado'] == 'No'): ?>
                    <form action="ingresar_guardar.php" method="POST">
                        <input type="hidden" name="mantenimiento_id" value="<?= $row['mantenimiento_id'] ?>">
                        <input type="hidden" name="descripcion" value="<?= htmlspecialchars($row['descripcion']) ?>">
                        <input type="hidden" name="amount" value="<?= $row['amount'] ?>">
                        <input type="hidden" name="due_date" value="<?= $row['due_date'] ?>">
                        <button class="btn-pagar" type="submit">Pagar</button>
                    </form>
                <?php else: ?>
                    ✔
                <?php endif; ?>
            </td>
        </tr>

    <?php endforeach; ?>

</table>

</body>
</html>
