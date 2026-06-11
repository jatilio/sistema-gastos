<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    die("No hay sesión activa.");
}

// Filtros
$month = $_GET['month'] ?? date('m');
$year  = $_GET['year'] ?? date('Y');

// ===============================
//  TOTALES POR MÉTODO DE PAGO
// ===============================
$sqlTotales = "
    SELECT metodo_pago, SUM(amount) AS total
    FROM gastos
    WHERE usuario_id = :usuario_id
      AND MONTH(due_date) = :month
      AND YEAR(due_date) = :year
    GROUP BY metodo_pago
";

$stmt = $pdo->prepare($sqlTotales);
$stmt->execute([
    ':usuario_id' => $usuario_id,
    ':month' => $month,
    ':year' => $year
]);

$totales = [
    'debito'   => 0,
    'credito'  => 0,
    'efectivo' => 0
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $metodo = $row['metodo_pago'];

    if ($metodo === 'debito') {
        $totales['debito'] = (float)$row['total'];
    } elseif ($metodo === 'credito') {
        $totales['credito'] = (float)$row['total'];
    } elseif ($metodo === 'efectivo') {
        $totales['efectivo'] = (float)$row['total'];
    }
}

$totalGeneral = $totales['debito'] + $totales['credito'] + $totales['efectivo'];

// ===============================
//  DETALLE DE GASTOS
// ===============================
$sqlDetalle = "
    SELECT descripcion, amount, due_date, metodo_pago, tipo, pagado
    FROM gastos
    WHERE usuario_id = :usuario_id
      AND MONTH(due_date) = :month
      AND YEAR(due_date) = :year
    ORDER BY due_date ASC
";

$stmt2 = $pdo->prepare($sqlDetalle);
$stmt2->execute([
    ':usuario_id' => $usuario_id,
    ':month' => $month,
    ':year' => $year
]);

$gastos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="assets/css/reporte_metodo_pago.css">

<div class="reporte-container">

    <h2 class="titulo-reporte">📊 Reporte por Método de Pago</h2>

    <!-- SELECTOR DE MES / AÑO -->
     <div class="acciones-reporte">
    <a href="reportes/exportar_metodo_pago_pdf.php?month=<?= $month ?>&year=<?= $year ?>" 
       class="btn-pdf" target="_blank">
        📄 Exportar a PDF
    </a>
    </div>
    <form method="GET" class="selector-fecha">
        <input type="hidden" name="menu" value="reporte_metodo_pago">

        <label>Mes</label>
        <select name="month">
            <?php
            for ($m = 1; $m <= 12; $m++) {
                $selected = ($m == $month) ? 'selected' : '';
                echo "<option value='$m' $selected>" . date("F", mktime(0,0,0,$m,1)) . "</option>";
            }
            ?>
        </select>

        <label>Año</label>
        <select name="year">
            <?php
            $currentYear = date("Y");
            for ($y = $currentYear - 5; $y <= $currentYear + 1; $y++) {
                $selected = ($y == $year) ? 'selected' : '';
                echo "<option value='$y' $selected>$y</option>";
            }
            ?>
        </select>

        <button class="btn-filtrar">Filtrar</button>
    </form>

    <!-- TARJETAS RESUMEN -->
    <div class="tarjetas-resumen">

        <div class="tarjeta resumen-debito">
            <h3>Débito</h3>
            <p>$<?= number_format($totales['debito'], 2) ?></p>
        </div>

        <div class="tarjeta resumen-credito">
            <h3>Crédito</h3>
            <p>$<?= number_format($totales['credito'], 2) ?></p>
        </div>

        <div class="tarjeta resumen-efectivo">
            <h3>Efectivo</h3>
            <p>$<?= number_format($totales['efectivo'], 2) ?></p>
        </div>

        <div class="tarjeta resumen-total">
            <h3>Total General</h3>
            <p>$<?= number_format($totalGeneral, 2) ?></p>
        </div>

    </div>

    <!-- TABLA DETALLE -->
    <h3 class="subtitulo">Detalle de Gastos</h3>

    <div class="tabla-responsive">
        <table class="tabla-detalle">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Método</th>
                    <th>Tipo</th>
                    <th>Pagado</th>
                    <th>Monto</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($gastos as $g): ?>
                    <tr class="<?= !$g['metodo_pago'] ? 'sin-metodo' : '' ?>">
                        <td><?= htmlspecialchars($g['due_date']) ?></td>
                        <td><?= htmlspecialchars($g['descripcion']) ?></td>

                        <td>
                            <?php if ($g['metodo_pago']): ?>
                                <?= ucfirst($g['metodo_pago']) ?>
                            <?php else: ?>
                                <span class="badge-warning">Sin método</span>
                            <?php endif; ?>
                        </td>

                        <td><?= $g['tipo'] ?></td>
                        <td><?= $g['pagado'] === 'Si' ? '✔' : 'No' ?></td>
                        <td>$<?= number_format($g['amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>