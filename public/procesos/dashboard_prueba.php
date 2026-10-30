<?php


// ===============================
// 1. VALIDAR SESIÓN
// ===============================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    header("Location: ../auth/login.php");
    exit;
}

// ===============================
// 2. RECIBIR FILTROS (MES, AÑO, BANCO)
// ===============================
$mes   = isset($_GET['mes']) ? (int)$_GET['mes'] : 0;
$anio  = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date("Y");
$banco = $_GET['banco'] ?? '';

// ===============================
// 2. RECIBIR FILTROS (MES, AÑO, BANCO)
// ===============================
$mes   = isset($_GET['mes']) ? (int)$_GET['mes'] : 0;
$anio  = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date("Y");
$banco = $_GET['banco'] ?? '';

$mes = max(0, min(12, $mes));
$anio = max(1900, min(9998, $anio));
$banco = is_string($banco) ? $banco : '';

$cond_banco_ing = ($banco !== '') ? " AND banco_origen = ?" : "";
$cond_banco_gas = ($banco !== '') ? " AND banco_pago = ?"   : "";

// ===============================
// 3. INGRESOS Y GASTOS (ACUMULADO O MENSUAL)
// ===============================

if ($mes == 0) {

    // INGRESOS ANUALES (solo débito)
    $sql_ingresos = "
        SELECT IFNULL(SUM(ingreso),0)
        FROM quincenas
        WHERE usuario_id = ?
          AND year = ?
          $cond_banco_ing
    ";

    $params_ing = [$usuario_id, $anio];
    if ($banco !== '') $params_ing[] = $banco;

    $stmt = $pdo->prepare($sql_ingresos);
    $stmt->execute($params_ing);
    $ingresos_mes = (float)$stmt->fetchColumn();

    // GASTOS ANUALES (solo débito)
    $sql_gastos = "
        SELECT IFNULL(SUM(amount),0)
        FROM gastos
        WHERE usuario_id = ?
          AND pagado = 'Si'
          AND metodo_pago = 'debito'
          $cond_banco_gas
          AND (
                (tipo='Fijo'      AND YEAR(due_date) = ?)
             OR (tipo='Adicional' AND YEAR(payment_date) = ?)
          )
    ";

    $params_gas = [$usuario_id];
    if ($banco !== '') $params_gas[] = $banco;
    $params_gas[] = $anio;
    $params_gas[] = $anio;

    $stmt = $pdo->prepare($sql_gastos);
    $stmt->execute($params_gas);
    $gastos_mes = (float)$stmt->fetchColumn();

} else {

    // INGRESOS MENSUALES (solo débito)
    $sql_ingresos = "
        SELECT IFNULL(SUM(ingreso),0)
        FROM quincenas
        WHERE usuario_id = ?
          AND year = ?
          AND month = ?
          $cond_banco_ing
    ";

    $params_ing = [$usuario_id, $anio, $mes];
    if ($banco !== '') $params_ing[] = $banco;

    $stmt = $pdo->prepare($sql_ingresos);
    $stmt->execute($params_ing);
    $ingresos_mes = (float)$stmt->fetchColumn();

    // GASTOS MENSUALES (solo débito)
    $sql_gastos = "
        SELECT IFNULL(SUM(amount),0)
        FROM gastos
        WHERE usuario_id = ?
          AND pagado = 'Si'
          AND metodo_pago = 'debito'
          $cond_banco_gas
          AND (
                (tipo='Fijo'      AND MONTH(due_date) = ? AND YEAR(due_date) = ?)
             OR (tipo='Adicional' AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?)
          )
    ";

$params_gas = [$usuario_id];

if ($banco !== '') $params_gas[] = $banco;

$params_gas[] = $mes;
$params_gas[] = $anio;
$params_gas[] = $mes;
$params_gas[] = $anio;

    $stmt = $pdo->prepare($sql_gastos);
    $stmt->execute($params_gas);
    $gastos_mes = (float)$stmt->fetchColumn();
}

// BALANCE
$balance_total = $ingresos_mes - $gastos_mes;
// ===============================
// 4. GRÁFICO: DESDE ENERO, INCLUYENDO MESES CON PAGOS ADELANTADOS
// ===============================
$hoyGrafico = new DateTimeImmutable('now', new DateTimeZone('America/New_York'));
$anioGrafico = $anio;
$mesGrafico = $anio < (int)$hoyGrafico->format('Y') ? 12 : ($anio === (int)$hoyGrafico->format('Y') ? (int)$hoyGrafico->format('n') : 1);
$inicioGrafico = $anioGrafico . '-01-01';
$finGrafico = ($anioGrafico + 1) . '-01-01';

$stmt = $pdo->prepare("
    SELECT month AS mes, SUM(ingreso) AS total
    FROM quincenas
    WHERE usuario_id = ? AND year = ? AND month BETWEEN 1 AND ? $cond_banco_ing
    GROUP BY month ORDER BY month
");
$paramsGraficoIng = [$usuario_id, $anioGrafico, 12]; if ($banco !== '') $paramsGraficoIng[] = $banco;
$stmt->execute($paramsGraficoIng);
$ingresos_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT MONTH(CASE WHEN tipo = 'Fijo' THEN due_date ELSE payment_date END) AS mes,
           SUM(amount) AS total
    FROM gastos
    WHERE usuario_id = ? AND pagado = 'Si' AND metodo_pago = 'debito'
      AND ((tipo = 'Fijo' AND due_date >= ? AND due_date < ?)
        OR (tipo = 'Adicional' AND payment_date >= ? AND payment_date < ?))
      $cond_banco_gas
    GROUP BY mes ORDER BY mes
");
$paramsGraficoGas = [$usuario_id, $inicioGrafico, $finGrafico, $inicioGrafico, $finGrafico]; if ($banco !== '') $paramsGraficoGas[] = $banco;
$stmt->execute($paramsGraficoGas);
$gastos_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Extender el eje hasta el último mes registrado, incluidos los pagos adelantados.
foreach (array_merge($ingresos_grafico, $gastos_grafico) as $filaGrafico) {
    $mesGrafico = max($mesGrafico, (int)$filaGrafico['mes']);
}
// Incluir también los meses sin movimientos, con valor cero.
$nombresMesGrafico = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$labels = array_slice($nombresMesGrafico, 0, $mesGrafico);
$data_ing = array_fill(0, $mesGrafico, 0.0);
$data_gas = array_fill(0, $mesGrafico, 0.0);
foreach ($ingresos_grafico as $filaGrafico) {
    $data_ing[(int)$filaGrafico['mes'] - 1] = (float)$filaGrafico['total'];
}
foreach ($gastos_grafico as $filaGrafico) {
    $data_gas[(int)$filaGrafico['mes'] - 1] = (float)$filaGrafico['total'];
}

// ===============================
// Serie mensual de adicionales: debe existir antes de preparar los tres gráficos.
$stmt = $pdo->prepare("
    SELECT MONTH(payment_date) AS mes, SUM(amount) AS total
    FROM gastos
    WHERE usuario_id = ? AND pagado = 'Si' AND tipo = 'Adicional' AND metodo_pago = 'debito'
      AND payment_date >= ? AND payment_date < ?
      $cond_banco_gas
    GROUP BY mes ORDER BY mes
");
$paramsGraficoAdic = [$usuario_id, $inicioGrafico, $finGrafico]; if ($banco !== '') $paramsGraficoAdic[] = $banco;
$stmt->execute($paramsGraficoAdic);
$labels_adic = [];
$totales_adic = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $filaGrafico) {
    $labels_adic[] = $nombresMesGrafico[(int)$filaGrafico['mes'] - 1];
    $totales_adic[] = (float)$filaGrafico['total'];
}
// 6. CUENTAS VINCULADAS (SALDO DISPONIBLE POR BANCO / TARJETA)

$stmt = $pdo->prepare("
    SELECT 
        banco,
        disponible,
        tipo_cuenta
    FROM vista_totales_banco_globales
    ORDER BY tipo_cuenta, banco
");
$stmt->execute();
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);




// ===============================
// 7. TRANSACCIONES RECIENTES
// ===============================

$stmt = $pdo->prepare("
    SELECT * FROM (
        SELECT 
            descripcion AS nombre,
            amount AS monto,
            payment_date AS fecha,
            'gasto' AS tipo
        FROM gastos
        WHERE usuario_id = ? 
          AND pagado = 'Si'
          AND amount > 0

        UNION ALL

        SELECT 
            CONCAT('Quincena ', quincena) AS nombre,
            ingreso AS monto,
            created_at AS fecha,
            'ingreso' AS tipo
        FROM quincenas
        WHERE usuario_id = ?
          AND ingreso > 0
    ) AS t
    ORDER BY fecha DESC
    LIMIT 7
");
$stmt->execute([$usuario_id, $usuario_id]);
$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);



// ===============================
// 8. PRÓXIMOS PAGOS (10 DÍAS O PROYECCIÓN) SIN MONTOS 0.00
// ===============================
$stmt = $pdo->prepare("
    SELECT 
        descripcion AS nombre,
        COALESCE(due_date, payment_date) AS fecha,
        amount
    FROM gastos
    WHERE usuario_id = ?
      AND pagado != 'Si'
      AND amount > 0
      AND COALESCE(due_date, payment_date) >= CURDATE()
    ORDER BY fecha ASC
    LIMIT 3
");
$stmt->execute([$usuario_id]);
$proximos_pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);



// ===============================
// 9. GASTOS POR CATEGORÍA (MES ACTUAL)
// ===============================

$stmt = $pdo->prepare(str_replace('SELECT IFNULL(SUM(amount),0)', "SELECT categoria, SUM(amount) AS total", $sql_gastos) . " AND tipo = 'Adicional' GROUP BY categoria ORDER BY total DESC");
$stmt->execute($params_gas);
$categorias_gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$labels_cat = [];
$totales_cat = [];

foreach ($categorias_gastos as $c) {
    $labels_cat[] = strtoupper($c['categoria'] ?? 'Sin categoría');
    $totales_cat[] = (float)$c['total'];
}

// Indicadores del diseño renovado: mismo criterio de débito y filtros que el total.
$nombresAnalisis = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$periodoAnalisis = $mes ? $nombresAnalisis[$mes - 1] . ' ' . $anio : 'Acumulado ' . $anio;
$desgloseStmt = $pdo->prepare(str_replace('SELECT IFNULL(SUM(amount),0)', 'SELECT tipo, IFNULL(SUM(amount),0) AS total', $sql_gastos) . ' GROUP BY tipo');
$desgloseStmt->execute($params_gas);
$desgloseAnalisis = ['Fijo'=>0.0, 'Adicional'=>0.0];
foreach ($desgloseStmt->fetchAll(PDO::FETCH_ASSOC) as $filaAnalisis) {
    $desgloseAnalisis[$filaAnalisis['tipo']] = (float)$filaAnalisis['total'];
}
$ahorroAnalisis = $ingresos_mes > 0 ? ($balance_total / $ingresos_mes) * 100 : null;
$inicioAnalisis = new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mes ?: 1));
$finAnalisis = $inicioAnalisis->modify($mes ? '+1 month' : '+1 year');
$inicioAnterior = $inicioAnalisis->modify($mes ? '-1 month' : '-1 year');
$condPeriodoAnalisis = "usuario_id = ? AND pagado = 'Si' AND metodo_pago = 'debito'
    AND ((tipo = 'Fijo' AND due_date >= ? AND due_date < ?)
      OR (tipo = 'Adicional' AND payment_date >= ? AND payment_date < ?)) $cond_banco_gas";
$paramsAnterior = [$usuario_id, $inicioAnterior->format('Y-m-d'), $inicioAnalisis->format('Y-m-d'), $inicioAnterior->format('Y-m-d'), $inicioAnalisis->format('Y-m-d')];
if ($banco !== '') $paramsAnterior[] = $banco;
$stmt = $pdo->prepare("SELECT COUNT(*) AS cantidad, COALESCE(SUM(amount),0) AS total FROM gastos WHERE $condPeriodoAnalisis");
$stmt->execute($paramsAnterior);
$anteriorAnalisis = $stmt->fetch(PDO::FETCH_ASSOC);
$diferenciaAnalisis = $gastos_mes - (float)$anteriorAnalisis['total'];
$variacionAnalisis = (float)$anteriorAnalisis['total'] > 0 ? $diferenciaAnalisis / (float)$anteriorAnalisis['total'] * 100 : null;
$etiquetaAnterior = $mes ? $nombresAnalisis[(int)$inicioAnterior->format('n') - 1] . ' ' . $inicioAnterior->format('Y') : (string)($anio - 1);

$mesReferencia = $mes ?: ($anio === (int)$hoyGrafico->format('Y') ? (int)$hoyGrafico->format('n') : 12);
$corteAdelantos = new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mesReferencia));
$corteAdelantos = $corteAdelantos->modify('+1 month')->format('Y-m-d');
$stmt = $pdo->prepare("SELECT DATE_FORMAT(due_date, '%Y-%m') AS periodo, SUM(amount) AS total
    FROM gastos WHERE usuario_id = ? AND pagado = 'Si' AND metodo_pago = 'debito'
      AND tipo = 'Fijo' AND due_date >= ? AND payment_date < ? $cond_banco_gas
    GROUP BY periodo ORDER BY periodo");
$paramsAdelantos = [$usuario_id, $corteAdelantos, $corteAdelantos];
if ($banco !== '') $paramsAdelantos[] = $banco;
$stmt->execute($paramsAdelantos);
$adelantosAnalisis = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalAdelantos = array_sum(array_map(static fn($fila) => (float)$fila['total'], $adelantosAnalisis));

// Distribución real: no se inventan límites de presupuesto.
$stmt = $pdo->prepare(str_replace('SELECT IFNULL(SUM(amount),0)', "SELECT COALESCE(NULLIF(TRIM(categoria), ''), 'Sin categoría') AS categoria, SUM(amount) AS total", $sql_gastos) . ' GROUP BY categoria ORDER BY total DESC');
$stmt->execute($params_gas);
$distribucionAnalisis = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<link rel="stylesheet" href="assets/css/filtros_dashboard_prueba.css">

<!-- ===============================
     CONTENIDO DEL DASHBOARD
=============================== -->


<link rel="stylesheet" href="/assets/css/analysis-premium.css?v=1">
<div class="dashboard-wrapper analysis-premium">
    <header class="analysis-heading"><div><span class="analysis-kicker">TUS FINANZAS, EN PERSPECTIVA</span><h1>Análisis financiero</h1><p>Entiende tus gastos y planifica los próximos meses.</p></div><span class="analysis-period"><?= htmlspecialchars($periodoAnalisis) ?></span></header>
    <form method="GET" action="index.php" class="analysis-filters">
        <input type="hidden" name="menu" value="dashboard_prueba">
        <label>Mes<select name="mes"><option value="0" <?= !$mes ? 'selected' : '' ?>>Todos los meses</option><?php foreach ($nombresAnalisis as $i => $nombre): ?><option value="<?= $i + 1 ?>" <?= $mes === $i + 1 ? 'selected' : '' ?>><?= $nombre ?></option><?php endforeach; ?></select></label>
        <label>Año<select name="anio"><?php for ($y = (int)date('Y') - 3; $y <= (int)date('Y') + 1; $y++): ?><option <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?></select></label>
        <label>Banco<select name="banco"><?php foreach ([''=>'Todos','MIDFLORIDA'=>'MidFlorida','BANK OF AMERICA'=>'Bank of America'] as $valor => $nombre): ?><option value="<?= $valor ?>" <?= $banco === $valor ? 'selected' : '' ?>><?= $nombre ?></option><?php endforeach; ?></select></label>
        <button type="submit">Aplicar filtros</button>
    </form>
    <p class="analysis-scope">Indicadores de <?= htmlspecialchars(mb_strtolower($periodoAnalisis)) ?> · Gastos pagados con débito · Fijos por vencimiento y adicionales por fecha de pago.</p>
    <div class="analysis-kpis">
        <section class="analysis-kpi"><span class="analysis-icon income">↗</span><div><h2>Ingresos</h2><strong class="analysis-value">$<?= number_format($ingresos_mes,2) ?></strong><span class="analysis-caption">En el período seleccionado</span></div></section>
        <section class="analysis-kpi"><span class="analysis-icon expense">↘</span><div><h2>Gastos pagados</h2><strong class="analysis-value">$<?= number_format($gastos_mes,2) ?></strong><dl class="analysis-breakdown"><div><dt>Fijos</dt><dd>$<?= number_format($desgloseAnalisis['Fijo'],2) ?></dd></div><div><dt>Adicionales</dt><dd>$<?= number_format($desgloseAnalisis['Adicional'],2) ?></dd></div></dl></div></section>
        <section class="analysis-kpi"><span class="analysis-icon balance">$</span><div><h2>Saldo disponible</h2><strong class="analysis-value <?= $balance_total < 0 ? 'negative' : '' ?>">$<?= number_format($balance_total,2) ?></strong><span class="analysis-caption">Ingresos menos gastos del período</span></div></section>
        <section class="analysis-kpi"><span class="analysis-icon saving">▥</span><div><h2><?= $mes ? 'Ahorro del mes' : 'Ahorro del período' ?></h2><strong class="analysis-value <?= $balance_total < 0 ? 'negative' : '' ?>"><?= $ahorroAnalisis === null ? '—' : number_format($ahorroAnalisis,1) . '%' ?></strong><span class="analysis-caption"><?= $ahorroAnalisis === null ? 'Sin ingresos para calcular el porcentaje' : 'Porcentaje de ingresos disponible' ?></span></div></section>
    </div>
    <section class="analysis-panel analysis-line"><div class="analysis-panel-heading"><div><h2>Ingresos vs gastos por mes</h2><p>Enero a <?= $nombresMesGrafico[$mesGrafico - 1] ?> de <?= $anioGrafico ?> · Incluye pagos adelantados</p></div></div><div class="analysis-canvas"><canvas id="chartIngresosGastos" aria-label="Ingresos y gastos mensuales" role="img"></canvas></div></section>
    <aside class="analysis-advances"><span class="analysis-icon balance">▣</span><div><strong>Pagos adelantados: $<?= number_format($totalAdelantos,2) ?></strong><p>Pagados antes del cierre de <?= $nombresAnalisis[$mesReferencia - 1] ?> <?= $anio ?>, con vencimiento en meses posteriores. Se muestran en su mes de vencimiento.</p><?php if ($adelantosAnalisis): ?><div class="analysis-advance-months"><?php foreach ($adelantosAnalisis as $adelanto): ?><span><?= $nombresAnalisis[(int)substr($adelanto['periodo'],5,2)-1] ?> <?= substr($adelanto['periodo'],0,4) ?>: $<?= number_format($adelanto['total'],2) ?></span><?php endforeach; ?></div><?php endif; ?></div></aside>
    <div class="analysis-insights">
        <section class="analysis-panel"><h2><?= $mes ? 'Comparación mensual' : 'Comparación anual' ?></h2><p class="analysis-muted">Gastos vs <?= htmlspecialchars($etiquetaAnterior) ?></p><?php if (!$anteriorAnalisis['cantidad']): ?><p class="analysis-empty">No hay registros del período anterior para comparar.</p><?php else: ?><div class="analysis-comparison <?= $diferenciaAnalisis <= 0 ? 'favorable' : 'unfavorable' ?>"><strong><?= $variacionAnalisis === null ? '—' : ($variacionAnalisis > 0 ? '+' : '') . number_format($variacionAnalisis,1) . '%' ?></strong><p><?= $diferenciaAnalisis == 0 ? 'El gasto se mantuvo igual.' : 'Gastaste $' . number_format(abs($diferenciaAnalisis),2) . ($diferenciaAnalisis < 0 ? ' menos.' : ' más.') ?></p></div><?php endif; ?></section>
        <section class="analysis-panel"><h2>Distribución por categoría</h2><p class="analysis-muted">Participación en los gastos del período. Sin límites de presupuesto definidos.</p><?php if (!$distribucionAnalisis): ?><p class="analysis-empty">No hay gastos en este período.</p><?php else: ?><?php foreach (array_slice($distribucionAnalisis,0,5) as $categoria): $porcentaje = $gastos_mes > 0 ? max(0,min(100,(float)$categoria['total']/$gastos_mes*100)) : 0; ?><div class="analysis-category"><div><span><?= htmlspecialchars($categoria['categoria']) ?></span><strong>$<?= number_format($categoria['total'],2) ?></strong></div><progress max="100" value="<?= $porcentaje ?>" aria-label="<?= htmlspecialchars($categoria['categoria'],ENT_QUOTES) ?>"></progress><small><?= number_format($porcentaje,1) ?>% del total</small></div><?php endforeach; ?><?php endif; ?></section>
    </div>
    <section class="analysis-panel analysis-bars"><div class="analysis-panel-heading"><div><h2>Gastos adicionales mensuales</h2><p><?= $anioGrafico ?> · Solo meses con registros · Pagos con débito</p></div></div><div class="analysis-canvas"><canvas id="chartAdicionales" aria-label="Gastos adicionales por mes" role="img"></canvas></div></section>
    <div class="analysis-support">
        <section class="analysis-panel"><h2>Cuentas vinculadas · Disponible</h2><p class="analysis-muted">Saldos actuales de tus cuentas.</p><?php foreach ($cuentas as $cuenta): ?><div class="analysis-list-row"><span><?= htmlspecialchars($cuenta['banco'] ?? '') ?></span><strong>$<?= number_format((float)$cuenta['disponible'],2) ?></strong></div><?php endforeach; ?></section>
        <section class="analysis-panel"><h2>Transacciones recientes</h2><p class="analysis-muted">Últimos movimientos, independientemente del período.</p><?php foreach ($transacciones as $movimiento): ?><div class="analysis-list-row"><div><span><?= htmlspecialchars($movimiento['descripcion'] ?? $movimiento['nombre'] ?? 'Movimiento') ?></span><small><?= !empty($movimiento['fecha']) ? date('d/m/Y', strtotime($movimiento['fecha'])) : 'Sin fecha' ?></small></div><strong class="<?= ($movimiento['tipo'] ?? 'gasto') === 'ingreso' ? 'analysis-incoming' : 'analysis-outgoing' ?>"><?= ($movimiento['tipo'] ?? 'gasto') === 'ingreso' ? '+' : '−' ?>$<?= number_format((float)($movimiento['monto'] ?? $movimiento['amount'] ?? 0),2) ?></strong></div><?php endforeach; ?></section>
        <section class="analysis-panel"><h2>Próximos pagos</h2><?php if (!$proximos_pagos): ?><p class="analysis-empty">No hay pagos próximos.</p><?php else: ?><?php foreach ($proximos_pagos as $pago): ?><div class="analysis-list-row"><div><span><?= htmlspecialchars($pago['nombre']) ?></span><small><?= date('d/m/Y',strtotime($pago['fecha'])) ?></small></div><strong>$<?= number_format($pago['amount'],2) ?></strong></div><?php endforeach; ?><?php endif; ?></section>
        <section class="analysis-panel analysis-category-chart"><h2>Gastos adicionales por categoría</h2><div class="analysis-canvas"><canvas id="chartCategorias" role="img" aria-label="Distribución de gastos adicionales"></canvas></div></section>
    </div>
</div>
<!-- 1. Variables PHP convertidas a JS -->
<script>
    const labelsCategorias = <?= json_encode($labels_cat) ?>;
    const totalesCategorias = <?= json_encode($totales_cat) ?>;

    const labelsLine = <?= json_encode($labels) ?>;
    const dataIngresos = <?= json_encode($data_ing) ?>;
    const dataGastos = <?= json_encode($data_gas) ?>;

    const labelsAdic = <?= json_encode($labels_adic) ?>;
    const totalesAdic = <?= json_encode($totales_adic) ?>;
</script>

<!-- 2. Chart.js -->
 <!-- CARGA Chart.js SOLO UNA VEZ -->
<!-- Chart.js -->


<!-- Tus archivos JS externos -->
<script src="/assets/js/chart-categorias.js"></script>
<script src="/assets/js/chart-ingresos-gastos.js"></script>
<script src="/assets/js/chart-adicionales.js"></script>


