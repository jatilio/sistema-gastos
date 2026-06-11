<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['usuario_id'])) {
    header("Location: auth/login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

/* ============================================================
   1. DETECTAR MESES YA GENERADOS POR AÑO
============================================================ */
$stmt = $pdo->prepare("
    SELECT MONTH(due_date) AS mes, YEAR(due_date) AS anio
    FROM gastos
    WHERE usuario_id = ?
");
$stmt->execute([$usuario_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$meses_generados = [];
foreach ($rows as $r) {
    $meses_generados[$r['anio']][$r['mes']] = true;
}

/* ============================================================
   2. AÑO SELECCIONADO
============================================================ */
$anio_seleccionado = isset($_GET['year']) ? intval($_GET['year']) : 2026;

/* ============================================================
   3. OBTENER PLANTILLA DE PAGOS
============================================================ */
$stmt = $pdo->prepare("SELECT * FROM mantenimiento_pagos WHERE usuario_id = ? ORDER BY id ASC");
$stmt->execute([$usuario_id]);
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   4. NOMBRES DE MESES
============================================================ */
$nombres_meses = [
    1=>"Enero",2=>"Febrero",3=>"Marzo",4=>"Abril",
    5=>"Mayo",6=>"Junio",7=>"Julio",8=>"Agosto",
    9=>"Septiembre",10=>"Octubre",11=>"Noviembre",12=>"Diciembre"
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mantenimiento de Pagos</title>

<style>
body { font-family: Arial; background:#f4f4f4; margin:0; padding:0; }
.container { max-width:1100px; margin:30px auto; background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h1 { text-align:center; margin-bottom:25px; }

.alert {
    padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold;
}
.alert-success { background:#d4edda; color:#155724; }
.alert-error { background:#f8d7da; color:#721c24; }

.form-generar { display:flex; gap:12px; align-items:center; margin-bottom:25px; }
.form-generar select, .form-generar button {
    padding:8px 12px; font-size:14px; border-radius:6px; border:1px solid #ccc;
}
.form-generar button { background:#007bff; color:#fff; border:none; cursor:pointer; }
.form-generar button:hover { background:#0056b3; }

table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ddd; padding:10px; text-align:center; }
th { background:#f0f0f0; }

input, select { width:100%; padding:6px; border-radius:4px; border:1px solid #ccc; }

.botones { display:flex; gap:12px; margin-top:20px; }
.btn-guardar { background:#28a745; color:#fff; padding:10px 18px; border:none; border-radius:6px; cursor:pointer; }
.btn-guardar:hover { background:#1e7e34; }
.btn-dashboard { background:#6c757d; color:#fff; padding:10px 18px; border:none; border-radius:6px; text-decoration:none; display:inline-block; }
.btn-dashboard:hover { background:#5a6268; }
</style>
</head>
<body>

<div class="container">
    <h1>Mantenimiento de Pagos</h1>

    <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success">Pagos generados exitosamente.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php
            switch ($_GET['error']) {
                case 1: echo "Los pagos de ese mes ya existen."; break;
                case 2: echo "No existe plantilla de pagos activa."; break;
                case 3: echo "Mes o año inválido."; break;
                default: echo "Error desconocido.";
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO GENERAR PAGOS (CORREGIDO) -->
    <form action="/mantenimiento/generar_pagos_mes.php" method="GET" class="form-generar">

        <label>Mes:</label>
        <select name="month" required>
            <?php foreach ($nombres_meses as $num => $nombre): ?>
                <?php $ya = isset($meses_generados[$anio_seleccionado][$num]); ?>
                <option value="<?= $num ?>" <?= $ya ? "disabled" : "" ?>>
                    <?= $nombre ?> <?= $ya ? "(ya generado)" : "" ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Año:</label>
        <select name="year" required>
            <?php for ($a = 2026; $a <= 2030; $a++): ?>
                <option value="<?= $a ?>" <?= $a == $anio_seleccionado ? "selected" : "" ?>>
                    <?= $a ?>
                </option>
            <?php endfor; ?>
        </select>

        <button type="submit">Generar Pagos</button>
    </form>

    <!-- TABLA DE PLANTILLA -->
    <form action="mantenimiento_pagos_guardar.php" method="post">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Fecha límite</th>
                    <th>Frecuencia</th>
                    <th>Categoría</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $i => $p): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><input type="text" name="descripcion[]" value="<?= htmlspecialchars($p['descripcion']) ?>"></td>
                    <td><input type="date" name="due_date[]" value="<?= $p['due_date'] ?>"></td>
                    <td>
                        <select name="frecuencia[]">
                            <option value="M" <?= $p['frecuencia']=='M'?'selected':'' ?>>Mensual</option>
                            <option value="S" <?= $p['frecuencia']=='S'?'selected':'' ?>>Semestral</option>
                            <option value="A" <?= $p['frecuencia']=='A'?'selected':'' ?>>Anual</option>
                        </select>
                    </td>
                    <td><input type="text" name="categoria[]" value="<?= htmlspecialchars($p['categoria']) ?>"></td>
                    <td><input type="number" step="0.01" name="amount[]" value="<?= number_format($p['amount'],2,'.','') ?>"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="botones">
            <button type="submit" class="btn-guardar">Guardar cambios</button>
            <a href="../index.php" class="btn-dashboard">Volver al Dashboard</a>
        </div>
    </form>

</div>

</body>
</html>