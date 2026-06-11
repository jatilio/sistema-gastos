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
// 4. GRÁFICO: OBTENER DATOS CRUDOS
// ===============================
$stmt = $pdo->prepare("
    SELECT 
        CONCAT(year, '-', LPAD(month,2,'0')) AS periodo,
        SUM(ingreso) AS total
    FROM quincenas
    WHERE usuario_id = ?
      AND CONCAT(year,'-',LPAD(month,2,'0')) >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 6 MONTH), '%Y-%m')
    GROUP BY periodo
    ORDER BY periodo
");
$stmt->execute([$usuario_id]);
$ingresos_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(
            CASE 
                WHEN tipo = 'Fijo' THEN due_date
                ELSE payment_date
            END,
        '%Y-%m') AS periodo,
        SUM(amount) AS total
    FROM gastos
    WHERE usuario_id = ?
      AND pagado = 'Si'
      AND (
            (tipo='Fijo'      AND due_date      >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH))
         OR (tipo='Adicional' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH))
      )
    GROUP BY periodo
    ORDER BY periodo
");
$stmt->execute([$usuario_id]);
$gastos_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// 5. PROCESAR DATOS PARA EL GRÁFICO (SOLO MESES EXISTENTES)
// ===============================

$labels = [];
$data_ing = [];
$data_gas = [];

// Convertir ingresos
foreach ($ingresos_grafico as $i) {
    $labels[] = date("M", strtotime($i['periodo'] . "-01"));
    $data_ing[] = (float)$i['total'];
}

// Convertir gastos
foreach ($gastos_grafico as $g) {
    $mes_graf = date("M", strtotime($g['periodo'] . "-01"));
    $index = array_search($mes_graf, $labels);

    if ($index !== false) {
        $data_gas[$index] = (float)$g['total'];
    }
}

// Rellenar gastos faltantes con 0
foreach ($labels as $k => $v) {
    if (!isset($data_gas[$k])) {
        $data_gas[$k] = 0;
    }
}

// ===============================
// 5B. GASTOS ADICIONALES MENSUALES
// ===============================

$stmt = $pdo->prepare("
    SELECT 
        MONTH(payment_date) AS mes,
        SUM(amount) AS total
    FROM gastos
    WHERE usuario_id = ?
      AND tipo = 'Adicional'
      AND pagado = 'Si'
      AND YEAR(payment_date) = ?
    GROUP BY MONTH(payment_date)
    ORDER BY mes ASC
");
$stmt->execute([$usuario_id, $anio]);
$adicionales_grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar datos
$labels_adic = [];
$totales_adic = [];

$meses_nombre = [1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic'];

foreach ($adicionales_grafico as $row) {
    $labels_adic[] = $meses_nombre[(int)$row['mes']];
    $totales_adic[] = (float)$row['total'];
}

// ===============================
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

$stmt = $pdo->prepare("
    SELECT categoria, SUM(amount) AS total
    FROM gastos
    WHERE usuario_id = ?
      AND pagado = 'Si'
      AND tipo = 'Adicional'
      AND categoria IS NOT NULL
      AND categoria != ''
      AND MONTH(payment_date) = ?
      AND YEAR(payment_date) = ?
    GROUP BY categoria
    ORDER BY total DESC
");

$stmt->execute([$usuario_id, $mes == 0 ? date("m") : $mes, $anio]);
$categorias_gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels_cat = [];
$totales_cat = [];

foreach ($categorias_gastos as $c) {
    $labels_cat[] = strtoupper($c['categoria']);
    $totales_cat[] = (float)$c['total'];
}

?>


<link rel="stylesheet" href="assets/css/filtros_dashboard_prueba.css">

<!-- ===============================
     CONTENIDO DEL DASHBOARD
=============================== -->


<div class="dashboard-wrapper">

    <!-- FILTROS -->
    <form method="GET" action="index.php" class="filtros-wrapper">
        <input type="hidden" name="menu" value="dashboard_prueba">

        <div class="filtro-group">
            <label>Mes</label>
            <select name="mes" class="filtro-select">
                <option value="0" <?= ($mes == 0 ? 'selected' : '') ?>>Todos</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($m == $mes) ? 'selected' : '' ?>>
                        <?= date("F", mktime(0,0,0,$m,1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="filtro-group">
            <label>Año</label>
            <select name="anio" class="filtro-select">
                <?php for ($y = date("Y") - 3; $y <= date("Y") + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= ($y == $anio) ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="filtro-group">
            <label>Banco</label>
            <select name="banco" class="filtro-select">
                <option value="" <?= ($banco == '' ? 'selected' : '') ?>>Todos</option>
                <option value="MIDFLORIDA" <?= ($banco=='MIDFLORIDA')?'selected':'' ?>>MIDFLORIDA</option>
                <option value="BANK OF AMERICA" <?= ($banco=='BANK OF AMERICA')?'selected':'' ?>>BANK OF AMERICA</option>
            </select>
        </div>

        <button type="submit" class="btn-filtrar">Filtrar</button>
    </form>

    <!-- TARJETAS PRINCIPALES -->
    <div class="main-cards">

        <div class="big-card white">
            <h3>Balance Total</h3>
            <div class="big-value"><?= ($balance_total >= 0 ? '' : '-') ?>$<?= number_format(abs($balance_total), 2) ?></div>
        </div>

        <div class="big-card white">
            <h3>Ingresos Totales</h3>
            <div class="big-value positivo">$<?= number_format($ingresos_mes, 2) ?></div>
        </div>

        <div class="big-card white">
            <h3>Gastos Totales</h3>
            <div class="big-value negativo">$<?= number_format($gastos_mes, 2) ?></div>
        </div>

    </div>

    <!-- GRÁFICO + PANEL DERECHO -->
    <div class="bottom-flex">

        <!-- IZQUIERDA -->
        <div class="left-column">

            <!-- GRÁFICO -->
            <div class="chart-box big-chart">
                <div class="section-header">
                    <h3>Ingresos vs Gastos</h3>
                </div>
                <canvas id="chartIngresosGastos"></canvas>
                
            </div> <!-- cierre del chart principal -->

<!-- NUEVO GRÁFICO DE ADICIONALES -->
<!-- AQUI AGREGAS EL NUEVO GRAFICO -->
<div class="chart-box big-chart" style="margin-top: 25px;">
    <div class="section-header">
        <h3>Gastos Adicionales Mensuales</h3>
    </div>
    <canvas id="chartAdicionales"></canvas>
</div> <!-- FIN DEL CAMBIO -->


            <!-- PRÓXIMOS PAGOS -->
            <div class="panel-section compact-pagos">
                <div class="section-header">
                    <h3>Próximos Pagos</h3>
                </div>

                <?php $proximos_5 = array_slice($proximos_pagos, 0, 5); ?>

                <?php if (empty($proximos_5)): ?>
                    <div class="list-item">
                        <div class="left-info">
                            <p class="t-desc">No hay pagos próximos</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($proximos_5 as $p): ?>
                        <div class="list-item">
                            <div class="left-info">
                                <p class="t-desc"><?= strtoupper($p['nombre']) ?></p>
                                <p class="t-meta"><?= date("m/d/Y", strtotime($p['fecha'])) ?></p>
                            </div>
                            <div class="t-monto negativo">
                                -$<?= number_format($p['amount'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- DERECHA -->
        <div class="right-panel">

<!-- CUENTAS -->
<div class="panel-section">
    <div class="section-header">
        <h3>Cuentas Vinculadas - Disponible</h3>
    </div>

    <?php foreach ($cuentas as $c): ?>
        <div class="list-item">
            <div class="left-info">
                <p class="t-desc"><?= strtoupper($c['banco']) ?></p>
            </div>

            <div class="t-monto <?= ($c['disponible'] >= 0 ? 'positivo' : 'negativo') ?>">
                $<?= number_format(floatval($c['disponible']), 2) ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>


        <!-- TRANSACCIONES -->
        <div class="panel-section">
            <div class="section-header">
                <h3>Transacciones Recientes</h3>
            </div>
            
        

<?php foreach (array_slice($transacciones, 0, 7) as $t): ?>

    <?php
        $tipo  = $t['tipo'] ?? 'gasto';
        $monto = (float)($t['monto'] ?? 0);

        // Si es ingreso (quincena)
        if ($tipo === 'ingreso') {

            // Si viene el número de quincena
            if (isset($t['quincena'])) {
                $nombre = 'ABONO (' . $t['quincena'] . ')';
            } else {
                // Si no viene quincena, usar ABONO sin monto
                $nombre = 'ABONO';
            }

        } else {
            // Para gastos, usar nombre normal
            $nombre = $t['nombre']
                ?? $t['descripcion']
                ?? $t['nombre_pago']
                ?? $t['descripcion_pago']
                ?? 'Movimiento';
        }

        // Fecha segura
        $fecha = !empty($t['fecha']) ? date("m/d/Y", strtotime($t['fecha'])) : '';
    ?>

    <div class="list-item">
        <div class="left-info">
            <p class="t-desc"><?= strtoupper($nombre) ?></p>
            <p class="t-meta"><?= $fecha ?></p>
        </div>

        <div class="t-monto <?= ($tipo == 'ingreso' ? 'positivo' : 'negativo') ?>">
            <?= ($tipo == 'ingreso' ? '+' : '-') ?>$<?= number_format(abs($monto), 2) ?>
        </div>
    </div>


    

<?php endforeach; ?>

</div> <!-- cierre del panel de transacciones -->
<!-- AQUI AGREGAS EL NUEVO GRAFICO -->
<div class="panel-section" style="
    margin-top: 25px;
    height: 360px;
    display: flex;
    flex-direction: column;
    align-items: center;
">
    <div class="section-header" style="margin-bottom: 8px;">
        <h3>Gastos por Categoría</h3>
    </div>

    <div style="height: 70%; width: 100%; display: flex; justify-content: center;">
        <canvas id="chartCategorias"></canvas>
    </div>

    <div id="legendCategorias" style="height: 30%; width: 100%;"></div>
</div>
<!-- FIN DEL NUEVO GRAFICO -->

</div>
</div>
</div>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Tus archivos JS externos -->
<script src="../assets/js/chart-categorias.js"></script>
<script src="../assets/js/chart-ingresos-gastos.js"></script>
<script src="../assets/js/chart-adicionales.js"></script>


