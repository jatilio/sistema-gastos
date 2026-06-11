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
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
        break;

    case 'dashboard_prueba':
        // IMPORTANTE: cargar al final para que tenga prioridad
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
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
        echo '<link rel="stylesheet" href="assets/css/mantenimiento.css">';
        break;

    case 'tarjetas':
    case 'tarjetas_gastos':
        echo '<link rel="stylesheet" href="assets/css/tarjetas_gastos.css">';
        break;
}
?>
</head>

<body>

<div class="dashboard">

<!-- ==================== SIDEBAR ==================== -->
<aside class="sidebar">
    <h2>💰 Finanzas</h2>
    <p class="saludo">Hola, <strong><?= htmlspecialchars($usuario_nombre) ?></strong></p>

    <nav class="menu-principal">
        <a href="index.php?menu=dashboard">📊 Dashboard</a>
        <a href="index.php?menu=dashboard_prueba">📈 Dashboard Prueba</a>
        <a href="index.php?menu=ingresos_salario">💵 Ingresos Mensuales</a>
        <a href="index.php?menu=gastos_ingresar">➕ Ingresar gasto</a>
        <a href="index.php?menu=gastos_cobrados">🧾 Gastos del mes</a>
        <a href="/gastos/adicionales.php">📝 Gastos Adicionales</a>
        <a href="index.php?menu=reportes">📑 Reportes</a>
        <a href="index.php?menu=reporte_metodo_pago">💳 Reporte por Método de Pago</a>
        <a href="index.php?menu=reporte_tarjetas">💳 Reporte de Tarjetas</a>
        <a href="index.php?menu=mantenimiento_pagos">🛠️ Mantenimiento</a>
        <a href="index.php?menu=tarjetas">💳 Mis Tarjetas</a>
        <a href="index.php?menu=tarjetas_gastos">🧾 Gastos de Tarjetas</a>
        <!-- <a href="inventario_dashboard.php">📊 Dashboard Inventario</a> -->

        <?php $active = basename($_SERVER['PHP_SELF']); ?>

<li class="nav-item has-submenu">
    <a href="#">Inventario</a>
    <ul class="submenu">
<li><a href="gastos/inventario.php">Inventario</a></li>
<li><a href="gastos/inventario_agregar.php">Agregar Producto</a></li>
<li><a href="gastos/inventario_dashboard.php">Dashboard Inventario</a></li>

    </ul>
</li>






        <hr class="separador">
        <a href="auth/logout.php" class="cerrar">🚪 Cerrar sesión</a>
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
    require_once __DIR__ . "/gastos/ingresar.php";
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
</body>
</html>