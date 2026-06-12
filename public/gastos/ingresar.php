<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../../config/db.php";

$usuario_id = $_SESSION['usuario_id'];

/* ============================================================
   FILTRO DE MES Y AÑO
============================================================ */

$mes  = isset($_GET['mes'])  ? intval($_GET['mes'])  : intval(date("m"));
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date("Y"));

$mes_str = str_pad($mes, 2, "0", STR_PAD_LEFT);
$inicio  = "$anio-$mes_str-01";

/* ============================================================
   CONSULTA — FUNCIONA PARA DATE Y DATETIME
============================================================ */

$sql = "
    SELECT
        id,
        mantenimiento_id,
        descripcion,
        due_date,
        amount,
        pagado,
        payment_date,
        confirmation,
        tarjeta_id,
        banco_pago,
        metodo_pago,
        banco
    FROM gastos
    WHERE usuario_id = :usuario_id
      AND tipo = 'Fijo'
      AND DATE(due_date) >= :inicio
      AND DATE(due_date) <= LAST_DAY(:inicio)
    ORDER BY DATE(due_date) ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'usuario_id' => $usuario_id,
    'inicio'     => $inicio
]);

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ingresar Gastos</title>

<link rel="stylesheet" href="../assets/css/ingresar.css">

</head>
<body>

<h2>Ingresar Gastos</h2>

<a href="/index.php" class="btn btn-secondary" style="
    display:inline-block;
    padding:8px 14px;
    background:#6c757d;
    color:white;
    text-decoration:none;
    border-radius:5px;
    margin-bottom:15px;
">
    ⬅ Regresar al Inicio
</a>


<!-- Debug visual -->
<p style="font-size:12px;color:#555;margin-bottom:8px;">
    Filtrando: <?= $mes_str ?>/<?= $anio ?>  
    (<?= $inicio ?> → <?= date("Y-m-t", strtotime($inicio)) ?>)
</p>

<!-- ============================
     FORMULARIO DE FILTRO
============================ -->
<form method="GET" action="/gastos/ingresar.php" style="margin-bottom: 15px;">

    <label>Mes:</label>
    <select name="mes">
        <?php 
        for ($m = 1; $m <= 12; $m++) {
            $selected = ($m == $mes) ? "selected" : "";
            echo "<option value='$m' $selected>" . date("F", mktime(0,0,0,$m,1)) . "</option>";
        }
        ?>
    </select>

    <label style="margin-left:10px;">Año:</label>
    <select name="anio">
        <?php 
        for ($y = 2024; $y <= 2030; $y++) {
            $selected = ($y == $anio) ? "selected" : "";
            echo "<option value='$y' $selected>$y</option>";
        }
        ?>
    </select>

    <button type="submit" class="btn-pagar" style="margin-left:10px;">Filtrar</button>
</form>

<!-- ============================
     TABLA
============================ -->
<div class="table-container">
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

    <?php 
    $i = 1;
    foreach ($result as $row): 
        $clase = ($row['pagado'] == 'Si') ? "pagado" : "nopagado";
    ?>
        <tr class="<?= $clase ?>">
            <td><?= $i++ ?></td>
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
</div>

</body>
</html>
