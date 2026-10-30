<?php
// ==================== SESSION ====================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== DB ====================
require_once dirname(__DIR__) . "/config/db.php";

$usuario_id     = $_SESSION['usuario_id'] ?? null;
$usuario_nombre = $_SESSION['usuario_nombre'] ?? '';

if (!$usuario_id) {
    header("Location: auth/login.php");
    exit;
}

// ==================== MENÚ ====================
$menu = $_GET['menu'] ?? $_GET['page'] ?? 'dashboard';

// Si vienen month/year y no viene menu, forzar reportes
if (!isset($_GET['menu']) && (isset($_GET['month']) || isset($_GET['year']))) {
    $menu = 'reportes';
}

$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date("Y");
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date("m");


?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Finanzas Personales</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- CSS GLOBAL (siempre primero) -->
<link rel="stylesheet" href="assets/css/styles.css">

<?php
// CSS y scripts según menú
switch($menu){

    case 'dashboard':
        echo '<link rel="stylesheet" href="assets/css/dashboard.css">';
        echo '<script src="/assets/js/lib/chart.umd.js"></script>';
        break;

    case 'dashboard_prueba':
        // IMPORTANTE: cargar al final para que tenga prioridad
        echo '<script src="/assets/js/lib/chart.umd.js"></script>';
        echo '<link rel="stylesheet" href="assets/css/dashboard_prueba.css">';
        break;

    case 'ingresos_salario':
        echo '<link rel="stylesheet" href="assets/css/ingreso.css">';
        break;

    case 'gastos_ingresar':
    case 'gastos_cobrados':
        echo '<link rel="stylesheet" href="assets/css/gastos_cobrados.css">';
        break;

    case 'reportes':
        echo '<link rel="stylesheet" href="assets/css/reportes.css">';
        break;

    case 'reporte_metodo_pago':
        echo '<link rel="stylesheet" href="assets/css/reporte_metodo_pago.css">';
        break;

    case 'mantenimiento_pagos':
        echo '<link rel="stylesheet" href="assets/css/app/mantenimiento.css">';
        break;

    case 'tarjetas':
    case 'tarjetas_gastos':
        echo '<link rel="stylesheet" href="assets/css/tarjetas_gastos.css">';
        break;
}
?>
<link rel="stylesheet" href="/assets/css/finance-ui.css?v=1">
<link rel="stylesheet" href="/assets/css/finance-responsive.css?v=1">
<script src="/assets/js/finance-interface.js?v=1"></script>
<script src="/assets/js/finance-language.js?v=2"></script>
</head>

<body class="finance-app">
<div class="mobile-navigation"><strong>Finanzas personales</strong><button type="button" id="menu-toggle" aria-expanded="false" aria-controls="main-sidebar">Menú</button></div>

<div class="dashboard">

<!-- ==================== SIDEBAR ==================== -->
<aside class="sidebar" id="main-sidebar">
    <h2>Finanzas<span style="color:#7dd6bd">.</span></h2>
    <p class="saludo">Hola, <strong><?= htmlspecialchars($usuario_nombre) ?></strong></p>

    <nav class="menu-principal" aria-label="Navegación principal">
    <?php
    $links = [
        'Inicio' => ['dashboard'=>'Resumen financiero','dashboard_prueba'=>'Análisis financiero'],
        'Registrar' => ['ingresos_salario'=>'Ingresos','gastos_ingresar'=>'Pagar gastos fijos'],
        'Organizar' => ['mantenimiento_pagos'=>'Pagos programados','tarjetas'=>'Mis tarjetas'],
        'Consultar' => ['reportes'=>'Reportes','reporte_tarjetas'=>'Reporte de tarjetas']
    ];
    foreach ($links as $grupo => $opciones): ?>
        <span class="menu-label"><?= $grupo ?></span>
        <?php foreach ($opciones as $clave => $etiqueta): ?>
        <a href="/index.php?menu=<?= $clave ?>" <?= ($menu === $clave || ($clave === 'tarjetas' && $menu === 'editar_tarjeta')) ? 'aria-current="page"' : '' ?>><?= $etiqueta ?></a>
        <?php endforeach; ?>
        <?php if ($grupo === 'Registrar'): ?><a href="/gastos/adicionales.php">Registrar gastos</a><?php endif; ?>
        <?php if ($grupo === 'Consultar'): ?><a href="/top10.php?anio=<?= date('Y') ?>&amp;mes=<?= date('m') ?>">Top 10 de gastos</a><?php endif; ?>
    <?php endforeach; ?>        <hr class="separador">
        <a href="auth/logout.php" class="cerrar">Cerrar sesión</a>
    </nav>
</aside>

<!-- ==================== CONTENIDO ==================== -->
<main class="main-content">

<?php
// ================= DASHBOARD =================
if ($menu === 'dashboard') {

    // Total ingresos
    $stmt = $pdo->prepare("SELECT SUM(ingreso) FROM quincenas WHERE usuario_id=? AND year=? AND month=?");
    $stmt->execute([$usuario_id,$year,$month]);
    $ingreso_total = (float)$stmt->fetchColumn();

    // Total gastado
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM gastos WHERE usuario_id=? AND pagado='Si' AND MONTH(due_date)=? AND YEAR(due_date)=?");
    $stmt->execute([$usuario_id,$month,$year]);
    $gastado_total = (float)$stmt->fetchColumn();

    $saldo = $ingreso_total - $gastado_total;

    // Próximos pagos
    $stmt = $pdo->prepare("SELECT descripcion, amount, due_date FROM gastos WHERE usuario_id=? AND pagado='No' AND due_date >= CURDATE() ORDER BY due_date ASC LIMIT 5");
    $stmt->execute([$usuario_id]);
    $proximos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include __DIR__ . "/dashboard.php";

}

// ================= DASHBOARD PRUEBA =================
elseif($menu === 'dashboard_prueba'){
    require_once __DIR__ . "/procesos/dashboard_prueba.php";
}

// ================= INGRESOS MENSUALES =================
elseif($menu==='ingresos_salario'){
    require_once __DIR__ . "/ingresos_salario.php";
}

// ================= INGRESAR GASTO =================
elseif($menu==='gastos_ingresar'){
    require_once __DIR__ . '/gastos/ingresar.php';
}


// ================= GASTOS DEL MES =================
elseif($menu==='gastos_cobrados'){
    require_once __DIR__ . "/gastos/gastos_cobrados.php";
}

// ================= REPORTES =================
elseif($menu==='reportes'){
    require_once __DIR__ . "/reportes.php";
}

elseif($menu==='reporte_tarjetas'){
    require_once __DIR__ . "/reportes/reportes_tarjetas.php";
}

elseif($menu==='reporte_metodo_pago'){
    require_once __DIR__ . "/reportes/reporte_metodo_pago.php";
}

// ================= MANTENIMIENTO =================
elseif($menu==='mantenimiento_pagos'){
    require_once __DIR__ . "/mantenimiento_pagos.php";
}

// ================= TARJETAS =================
elseif($menu==='tarjetas'){
    require_once __DIR__ . "/menus/tarjetas.php";
}

// ================= GASTOS DE TARJETAS =================
elseif($menu==='tarjetas_gastos'){
    require_once __DIR__ . "/menus/tarjetas_gastos.php";
}

// ================= EDITAR TARJETA =================
elseif($menu==='editar_tarjeta'){
    require_once __DIR__ . "/menus/editar_tarjeta.php";
}
?>

</main>

</div>
<script>
const menuToggle = document.getElementById('menu-toggle');
menuToggle.addEventListener('click', () => {
    const open = document.getElementById('main-sidebar').classList.toggle('is-open');
    menuToggle.setAttribute('aria-expanded', String(open));
    menuToggle.textContent = open ? 'Cerrar menú' : 'Menú';
});
document.querySelectorAll('.menu-principal a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth > 760) return;
        document.getElementById('main-sidebar').classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.textContent = 'Menú';
    });
});</script>
</body>
</html>