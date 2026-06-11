<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario_id'])) {
    exit("No hay sesión activa");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

use Dompdf\Dompdf;

$month = $_GET['month'] ?? date("m");
$year  = $_GET['year'] ?? date("Y");
$tipo  = $_GET['tipo'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

/* ============================================================
   CONSULTA BASE PARA ADICIONALES AGRUPADOS
   ============================================================ */

$sql_adicionales_base = "
    SELECT 
        descripcion,
        categoria,
        COUNT(*) AS cantidad,
        SUM(amount) AS total,
        MAX(due_date) AS fecha
    FROM gastos
    WHERE usuario_id = :uid
      AND tipo = 'Adicional'
      AND MONTH(due_date) = :month
      AND YEAR(due_date) = :year
    GROUP BY descripcion, categoria
";

/* ============================================================
   CONSULTA FINAL CON TARJETA
   ============================================================ */

$sql_adicionales = "
    SELECT 
        a.descripcion,
        a.categoria,
        a.cantidad,
        a.total,
        a.fecha,
        (
            SELECT g2.banco_pago
            FROM gastos g2
            WHERE g2.usuario_id = :uid
              AND g2.descripcion = a.descripcion
              AND g2.categoria = a.categoria
              AND g2.due_date = a.fecha
            LIMIT 1
        ) AS tarjeta
    FROM ($sql_adicionales_base) AS a
    ORDER BY a.fecha ASC
";

/* ============================================================
   CONSULTAS SEGÚN TIPO
   ============================================================ */

if ($tipo === 'Adicional') {

    $stmt = $pdo->prepare($sql_adicionales);
    $stmt->execute([':uid'=>$usuario_id,':month'=>$month,':year'=>$year]);
    $adicionales = $stmt->fetchAll();
    $fijos = [];

} elseif ($tipo === 'Fijo') {

    $sql_fijos = "
        SELECT descripcion, amount, due_date, payment_date, confirmation, pagado, banco_pago
        FROM gastos
        WHERE usuario_id = :uid
          AND tipo = 'Fijo'
          AND MONTH(due_date)=:month
          AND YEAR(due_date)=:year
        ORDER BY due_date ASC
    ";
    $stmt = $pdo->prepare($sql_fijos);
    $stmt->execute([':uid'=>$usuario_id,':month'=>$month,':year'=>$year]);
    $fijos = $stmt->fetchAll();
    $adicionales = [];

} else {

    $sql_fijos = "
        SELECT descripcion, amount, due_date, payment_date, confirmation, pagado, banco_pago
        FROM gastos
        WHERE usuario_id = :uid
          AND tipo='Fijo'
          AND MONTH(due_date)=:month
          AND YEAR(due_date)=:year
        ORDER BY due_date ASC
    ";
    $stmt = $pdo->prepare($sql_fijos);
    $stmt->execute([':uid'=>$usuario_id,':month'=>$month,':year'=>$year]);
    $fijos = $stmt->fetchAll();

    $stmt = $pdo->prepare($sql_adicionales);
    $stmt->execute([':uid'=>$usuario_id,':month'=>$month,':year'=>$year]);
    $adicionales = $stmt->fetchAll();
}

/* ============================================================
   CSS
   ============================================================ */

$css = <<<CSS
@page { margin: 45px 35px; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #2b2b2b; }
.section-title { text-align:center; font-weight:bold; margin-top:25px; }
table { width:100%; border-collapse:collapse; margin-top:12px; }
th { background:#d7e3f4; padding:6px; border:1px solid #a8bdd8; }
td { padding:5px; border:1px solid #c3d2e5; }
.unpaid td { background:#ffe0e0; font-weight:bold; }
.total-left { margin-top:15px; text-align:left; font-weight:bold; }
.total-box {
    margin-top:25px;
    padding:15px 20px;
    background:#004aad;
    color:white;
    border-radius:8px;
    width: 100%;
    max-width: 420px;
    text-align:center;
    font-weight:bold;
    margin-left:auto;
    margin-right:auto;
}
CSS;

ob_clean();
ob_start();
?>

<!DOCTYPE html>
<html>
<body>

<h2 style="text-align:center;">Reporte Financiero Mensual</h2>

<?php
$total_fijos = 0;
$total_adicionales = 0;
?>

<!-- ============================
     FIJOS
     ============================ -->
<?php if (!empty($fijos)): ?>
<h3 class="section-title">---------- FIJOS ----------</h3>

<table>
<tr>
    <th>#</th>
    <th>Descripción</th>
    <th>Fecha</th>
    <th>Monto</th>
    <th>Fecha Pago</th>
    <th>Pagado</th>
    <th>Confirmación</th>
    <th>Tarjeta</th>
</tr>

<?php $i=1; foreach ($fijos as $f): ?>
<tr class="<?= strtoupper($f['pagado']) === 'NO' ? 'unpaid' : '' ?>">
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($f['descripcion']) ?></td>
    <td><?= date("d/m/Y", strtotime($f['due_date'])) ?></td>
    <td>$<?= number_format($f['amount'],2) ?></td>
    <td><?= $f['payment_date'] ?: '-' ?></td>
    <td><?= $f['pagado'] ?></td>
    <td><?= $f['confirmation'] ?: '-' ?></td>
    <td><?= $f['banco_pago'] ?: '-' ?></td>
</tr>
<?php 
if (strtoupper($f['pagado']) === 'SI') {
    $total_fijos += $f['amount'];
}
endforeach; ?>
</table>

<div class="total-left">
    <span>TOTAL FIJOS</span><br>
    <h2>$<?= number_format($total_fijos,2) ?></h2>
</div>
<?php endif; ?>

<!-- ============================
     ADICIONALES
     ============================ -->
<?php if (!empty($adicionales)): ?>
<h3 class="section-title">-------- ADICIONALES (AGRUPADOS) --------</h3>

<table>
<tr>
    <th>#</th>
    <th>Descripción</th>
    <th>Fecha</th>
    <th>Categoría</th>
    <th>Cantidad</th>
    <th>Total</th>
    <th>Tarjeta</th>
</tr>

<?php $i=1; foreach ($adicionales as $a): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($a['descripcion']) ?></td>
    <td><?= date("d/m/Y", strtotime($a['fecha'])) ?></td>
    <td><?= htmlspecialchars($a['categoria']) ?></td>
    <td><?= $a['cantidad'] ?></td>
    <td>$<?= number_format($a['total'],2) ?></td>
    <td><?= htmlspecialchars($a['tarjeta']) ?></td>
</tr>
<?php 
$total_adicionales += $a['total'];
endforeach; ?>
</table>

<div class="total-left">
    <span>TOTAL ADICIONALES</span><br>
    <h2>$<?= number_format($total_adicionales,2) ?></h2>
</div>
<?php endif; ?>

<!-- ============================
     TOTAL GENERAL
     ============================ -->
<?php if (!empty($fijos) || !empty($adicionales)): ?>
<div class="total-box">
    <div style="font-size:14px;">TOTAL GENERAL (FIJOS + ADICIONALES)</div>
    <div style="font-size:26px; margin-top:5px;">
        $<?= number_format($total_fijos + $total_adicionales, 2) ?>
    </div>
</div>
<?php endif; ?>

</body>
</html>

<?php
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->loadHtml("<style>$css</style>".$html);
$dompdf->setPaper("A4","portrait");
$dompdf->render();
$dompdf->stream("reporte_$month-$year.pdf", ["Attachment"=>false]);