<?php
$usuario_id = $_SESSION['usuario_id'];

$month = isset($_GET['month']) ? intval($_GET['month']) : date("m");
$year  = isset($_GET['year'])  ? intval($_GET['year'])  : date("Y");
$tipo  = $_GET['tipo'] ?? '';

/* ============================================================
   CONSULTA BASE PARA ADICIONALES AGRUPADOS (COMPATIBLE FULL_GROUP_BY)
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
   CONSULTA FINAL CON TARJETA (SUBQUERY WRAP)
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
    FROM (
        $sql_adicionales_base
    ) AS a
    ORDER BY a.fecha ASC
";

/* ============================================================
   CONSULTAS SEGÚN TIPO
   ============================================================ */

if ($tipo === 'Adicional') {

    $stmt = $pdo->prepare($sql_adicionales);
    $stmt->execute([':uid' => $usuario_id, ':month' => $month, ':year' => $year]);
    $adicionales = $stmt->fetchAll();
    $fijos = [];

} elseif ($tipo === 'Fijo') {

    $stmt = $pdo->prepare("
        SELECT descripcion, due_date, amount, pagado, payment_date, confirmation, banco_pago
        FROM gastos
        WHERE usuario_id = ?
          AND tipo = 'Fijo'
          AND MONTH(due_date) = ?
          AND YEAR(due_date) = ?
        ORDER BY due_date ASC
    ");
    $stmt->execute([$usuario_id, $month, $year]);
    $fijos = $stmt->fetchAll();
    $adicionales = [];

} else {

    // FIJOS
    $stmt = $pdo->prepare("
        SELECT descripcion, due_date, amount, pagado, payment_date, confirmation, banco_pago
        FROM gastos
        WHERE usuario_id = ?
          AND tipo = 'Fijo'
          AND MONTH(due_date) = ?
          AND YEAR(due_date) = ?
        ORDER BY due_date ASC
    ");
    $stmt->execute([$usuario_id, $month, $year]);
    $fijos = $stmt->fetchAll();

    // ADICIONALES AGRUPADOS
    $stmt = $pdo->prepare($sql_adicionales);
    $stmt->execute([':uid' => $usuario_id, ':month' => $month, ':year' => $year]);
    $adicionales = $stmt->fetchAll();
}

/* ============================================================
   TOTAL FIJOS
   ============================================================ */

$total_mes = 0;
foreach ($fijos as $g) {
    if (strtoupper(trim($g['pagado'])) === 'SI') {
        $total_mes += (float)$g['amount'];
    }
}

/* ============================================================
   TOTAL ADICIONALES
   ============================================================ */

$total_adicionales = array_sum(array_column($adicionales, 'total'));
?>

<div class="reportes-container">

    <div class="acciones-reporte">
        <a href="index.php?menu=dashboard" class="btn-volver-menu">← Volver al menú principal</a>
    </div>

    <div class="encabezado-pdf">
        <h2>REPORTE FINANCIERO MENSUAL</h2>
        <div class="info-reporte">
            <span><strong>Usuario:</strong> <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
            <span><strong>Mes:</strong> <?= date("F", mktime(0,0,0,$month,1)) . " " . $year ?></span>
            <span><strong>Generado:</strong> <?= date("d/m/Y") ?></span>
        </div>
    </div>

    <!-- FORMULARIO DE FILTRO -->
    <form method="GET" action="index.php" class="selector-mes">
        <input type="hidden" name="menu" value="reportes">

        <select name="month">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= ($m == $month ? "selected" : "") ?>>
                    <?= date("F", mktime(0,0,0,$m,1)) ?>
                </option>
            <?php endfor; ?>
        </select>

        <select name="year">
            <?php for ($y = date("Y") - 5; $y <= date("Y") + 1; $y++): ?>
                <option value="<?= $y ?>" <?= ($y == $year ? "selected" : "") ?>>
                    <?= $y ?>
                </option>
            <?php endfor; ?>
        </select>

        <select name="tipo">
            <option value="">Todos</option>
            <option value="Fijo" <?= ($tipo == 'Fijo' ? 'selected' : '') ?>>Fijo</option>
            <option value="Adicional" <?= ($tipo == 'Adicional' ? 'selected' : '') ?>>Adicional</option>
        </select>

        <button type="submit">Filtrar</button>

        <button type="submit" formaction="reporte_pdf.php" formtarget="_blank">
            ⬇ Descargar PDF
        </button>
    </form>

    <div class="reporte-tabla">

        <!-- ============================
             TABLA FIJOS
             ============================ -->
        <?php if (!empty($fijos)): ?>
        <h3 style="margin-top:25px; font-weight:bold; text-align:center;">
            ---------- FIJOS ----------
        </h3>

        <table class="tabla-reporte">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Pagado</th>
                    <th>Fecha pago</th>
                    <th>Confirmación</th>
                    <th>Tarjeta</th>
                </tr>
            </thead>

            <tbody>
                <?php 
                $i = 1;
                foreach ($fijos as $gasto):
                    $no_pagado = (strtoupper(trim($gasto['pagado'])) === 'NO');
                ?>
                <tr class="<?= $no_pagado ? 'no-pagado' : '' ?>">
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($gasto['descripcion']) ?></td>
                    <td><?= date("d/m/Y", strtotime($gasto['due_date'])) ?></td>
                    <td>$<?= number_format($gasto['amount'], 2) ?></td>
                    <td><?= $gasto['pagado'] ?></td>
                    <td><?= $gasto['payment_date'] ? date("d/m/Y", strtotime($gasto['payment_date'])) : "-" ?></td>
                    <td><?= $gasto['confirmation'] ?: "-" ?></td>
                    <td><?= $gasto['banco_pago'] ?: "-" ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-final" style="margin-top:20px; text-align:left;">
            <span>TOTAL FIJOS</span>
            <h2>$<?= number_format($total_mes, 2) ?></h2>
        </div>
        <?php endif; ?>

        <!-- ============================
             TABLA ADICIONALES AGRUPADOS
             ============================ -->
        <?php if (!empty($adicionales)): ?>
        <h3 style="margin-top:35px; font-weight:bold; text-align:center;">
            -------- ADICIONALES (AGRUPADOS) --------
        </h3>

        <table class="tabla-reporte">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Categoría</th>
                    <th>Cantidad</th>
                    <th>Total</th>
                    <th>Tarjeta</th>
                </tr>
            </thead>

            <tbody>
                <?php 
                $i = 1;
                foreach ($adicionales as $g):
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($g['descripcion']) ?></td>
                    <td><?= date("d/m/Y", strtotime($g['fecha'])) ?></td>
                    <td><?= htmlspecialchars($g['categoria']) ?></td>
                    <td><?= $g['cantidad'] ?></td>
                    <td>$<?= number_format($g['total'], 2) ?></td>
                    <td><?= htmlspecialchars($g['tarjeta']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-final" style="margin-top:20px; text-align:left;">
            <span>TOTAL ADICIONALES</span>
            <h2>$<?= number_format($total_adicionales, 2) ?></h2>
        </div>
        <?php endif; ?>

        <!-- ============================
             TOTAL GENERAL
             ============================ -->
        <?php if (!empty($fijos) || !empty($adicionales)): ?>
            <div style="
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
            ">
                <div style="font-size:14px; letter-spacing:0.5px;">
                    TOTAL GENERAL (FIJOS + ADICIONALES)
                </div>
                <div style="font-size:26px; margin-top:5px;">
                    $<?= number_format($total_mes + $total_adicionales, 2) ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

</div>