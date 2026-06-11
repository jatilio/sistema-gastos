<?php

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require_once(__DIR__ . "/../config/db.php");

$usuario = $_SESSION['usuario_id'];
$mes = date("m");
$anio = date("Y");

/* ============================================================
   1. INGRESO TOTAL DEL MES
============================================================ */
$stmt = $pdo->prepare("
    SELECT SUM(ingreso)
    FROM quincenas
    WHERE usuario_id=? AND year=? AND month=?
");
$stmt->execute([$usuario, $anio, $mes]);
$ingreso_total = (float)$stmt->fetchColumn();

/* ============================================================
   2. GASTADO TOTAL (FIJO + ADICIONAL)
============================================================ */
$stmt = $pdo->prepare("
    SELECT SUM(amount)
    FROM gastos
    WHERE usuario_id = ?
      AND pagado = 'Si'
      AND (
            (tipo = 'Fijo' AND MONTH(due_date) = ? AND YEAR(due_date) = ?)
         OR (tipo = 'Adicional' AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?)
      )
");
$stmt->execute([$usuario, $mes, $anio, $mes, $anio]);
$gastado_total = (float)$stmt->fetchColumn();

/* ============================================================
   3. SALDO DISPONIBLE
============================================================ */
$saldo = $ingreso_total - $gastado_total;

/* ============================================================
   4. PENDIENTES POR PAGAR
============================================================ */
$stmt = $pdo->prepare("
    SELECT SUM(amount)
    FROM gastos
    WHERE usuario_id = ?
      AND pagado = 'No'
      AND (
            (tipo = 'Fijo' AND MONTH(due_date) = ? AND YEAR(due_date) = ?)
         OR (tipo = 'Adicional' AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?)
      )
");
$stmt->execute([$usuario, $mes, $anio, $mes, $anio]);
$total_pendientes = (float)$stmt->fetchColumn();

/* Normalizar valores nulos */
$ingreso_total     = $ingreso_total     ?? 0;
$gastado_total     = $gastado_total     ?? 0;
$saldo             = $saldo             ?? 0;
$total_pendientes  = $total_pendientes  ?? 0;

/* ============================================================
   FUNCIÓN SQL PARA CALCULAR DUE_DATE REAL (ANUALES)
============================================================ */
$due_date_real_sql = "
    CASE 
        WHEN mp.frecuencia = 'A' 
            THEN mp.due_date
        WHEN DAY(mp.due_date) > DAY(LAST_DAY(CURDATE()))
            THEN LAST_DAY(CURDATE())
        ELSE DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-', DAY(mp.due_date)))
    END
";

/* ============================================================
   5. PRÓXIMOS PAGOS (10 DÍAS)
============================================================ */
$stmt = $pdo->prepare("
    SELECT 
        mp.id,
        mp.descripcion,
        mp.amount,
        mp.categoria,
        $due_date_real_sql AS due_date_real
    FROM mantenimiento_pagos mp
    LEFT JOIN gastos g
        ON g.mantenimiento_id = mp.id
        AND g.usuario_id = mp.usuario_id
        AND g.pagado = 'Si'
        AND MONTH(g.due_date)=MONTH(CURDATE())
        AND YEAR(g.due_date)=YEAR(CURDATE())
    WHERE mp.usuario_id = ?
      AND mp.activo = 1
      AND g.id IS NULL
      AND (
            mp.frecuencia = 'M'
            AND $due_date_real_sql BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 10 DAY)
          )
    ORDER BY due_date_real ASC
");
$stmt->execute([$usuario]);
$proximos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   6. PAGOS VENCIDOS
============================================================ */
$stmt = $pdo->prepare("
    SELECT 
        mp.id,
        mp.descripcion,
        mp.amount,
        mp.categoria,
        $due_date_real_sql AS due_date_real
    FROM mantenimiento_pagos mp
    LEFT JOIN gastos g
        ON g.mantenimiento_id = mp.id
        AND g.usuario_id = mp.usuario_id
        AND g.pagado = 'Si'
        AND MONTH(g.due_date)=MONTH(CURDATE())
        AND YEAR(g.due_date)=YEAR(CURDATE())
    WHERE mp.usuario_id = ?
      AND mp.activo = 1
      AND g.id IS NULL
      AND (
            mp.frecuencia = 'M' AND $due_date_real_sql < CURDATE()
          )
    ORDER BY due_date_real ASC
");
$stmt->execute([$usuario]);
$vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h1>Dashboard financiero</h1>

<!-- ============================================================
     TARJETAS PRINCIPALES (4 TARJETAS)
============================================================ -->
<div class="dashboard-cards">

    <div class="card card-ingresos">
        <h3>Ingreso del mes</h3>
        <p>$<?= number_format($ingreso_total,2) ?></p>
    </div>

    <div class="card card-gastos">
        <h3>Gastado (Fijo + Adicional)</h3>
        <p>$<?= number_format($gastado_total,2) ?></p>
    </div>

    <div class="card card-balance">
        <h3>Saldo disponible</h3>
        <p>$<?= number_format($saldo,2) ?></p>
    </div>

    <div class="card card-pendientes">
        <h3>Pendientes por pagar</h3>
        <p>$<?= number_format($total_pendientes,2) ?></p>
    </div>

</div>

<!-- ============================================================
     PRÓXIMOS PAGOS
============================================================ -->
<div class="alert-section proximos">
    <h2>Próximos pagos (10 días)</h2>

    <?php if (empty($proximos)): ?>
        <p class="no-alerts">No hay pagos próximos.</p>
    <?php else: ?>
        <ul class="alert-list">
            <?php foreach ($proximos as $p): ?>
                <li class="alert-item proximo">
                    <strong><?= htmlspecialchars($p['descripcion']) ?></strong>
                    <span>$<?= number_format($p['amount'],2) ?> — vence el <?= date("d/m/Y", strtotime($p['due_date_real'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- ============================================================
     PAGOS VENCIDOS
============================================================ -->
<div class="alert-section vencidos">
    <h2>Pagos vencidos</h2>

    <?php if (empty($vencidos)): ?>
        <p class="no-alerts">No hay pagos vencidos.</p>
    <?php else: ?>
        <ul class="alert-list">
            <?php foreach ($vencidos as $v): ?>
                <li class="alert-item vencido">
                    <strong><?= htmlspecialchars($v['descripcion']) ?></strong>
                    <span>$<?= number_format($v['amount'],2) ?> — venció el <?= date("d/m/Y", strtotime($v['due_date_real'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>