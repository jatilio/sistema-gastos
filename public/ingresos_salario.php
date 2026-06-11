<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

$usuario_id     = $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['usuario_nombre'];

$mensaje_exito = '';
$mensaje_error = '';

// ======================================
// REGLA REAL: HOY -> SE APLICA AL MES SIGUIENTE
// ======================================
$hoy = new DateTime();

// Fecha exacta del mes al que se aplicará (ej: febrero)
$fecha_aporte = new DateTime('first day of next month');

// Mes y año al que se aplicará
$mes_aporte = $fecha_aporte->format('m');
$year       = $fecha_aporte->format('Y');

// El ingreso pertenece al mes siguiente, NO al mes actual
$month_real = $mes_aporte;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $quincena1     = $_POST['quincena1'] ?: 0;
    $quincena2     = $_POST['quincena2'] ?: 0;
    $extra         = $_POST['extra'] ?: 0;

    // Bancos independientes
    $banco_origen_1     = $_POST['banco_origen_1'] ?? null;
    $banco_origen_2     = $_POST['banco_origen_2'] ?? null;
    $banco_origen_extra = $_POST['banco_origen_extra'] ?? null;

    try {

        $stmt = $pdo->prepare("
            INSERT INTO quincenas
            (usuario_id, year, month, mes_aporte, quincena, ingreso, banco_origen, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        // QUINCENA 1
        if ($quincena1 > 0) {
            $stmt->execute([$usuario_id, $year, $month_real, $mes_aporte, 1, $quincena1, $banco_origen_1]);
        }

        // QUINCENA 2
        if ($quincena2 > 0) {
            $stmt->execute([$usuario_id, $year, $month_real, $mes_aporte, 2, $quincena2, $banco_origen_2]);
        }

        // EXTRA — quincena NULL (permite múltiples extras)
        if ($extra > 0) {
            $stmt->execute([$usuario_id, $year, $month_real, $mes_aporte, null, $extra, $banco_origen_extra]);
        }

        $mensaje_exito = "Ingresos guardados para " . $fecha_aporte->format('F Y');

    } catch (PDOException $e) {
        $mensaje_error = "Error al guardar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ingresos Mensuales</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/ingresos_salario.css?v=2">
</head>

<body>
<div class="container">
    <h1>Registrar Ingresos</h1>

<?php if ($mensaje_exito): ?>
    <div class="success"><?= $mensaje_exito ?></div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
    <div class="error"><?= $mensaje_error ?></div>
<?php endif; ?>

<div class="info-mes">
    Este ingreso se aplicará al mes:
    <strong><?= $fecha_aporte->format('F Y'); ?></strong>
</div>

<form method="POST" id="form-ingresos" class="ingresos-form">

    <!-- BANCO ORIGEN QUINCENA 1 -->
    <div class="form-group">
        <label>Banco origen – 1ª Quincena:</label>
        <select name="banco_origen_1">
            <option value="">Seleccione...</option>
            <option value="MIDFLORIDA">MIDFLORIDA</option>
            <option value="BANK OF AMERICA">BANK OF AMERICA</option>
            <option value="EFECTIVO">EFECTIVO</option>
            <option value="OTRO">OTRO</option>
        </select>
    </div>

    <div class="form-group">
        <label>1ª Quincena:</label>
        <input type="number" step="0.01" name="quincena1">
    </div>

    <!-- BANCO ORIGEN QUINCENA 2 -->
    <div class="form-group">
        <label>Banco origen – 2ª Quincena:</label>
        <select name="banco_origen_2">
            <option value="">Seleccione...</option>
            <option value="MIDFLORIDA">MIDFLORIDA</option>
            <option value="BANK OF AMERICA">BANK OF AMERICA</option>
            <option value="EFECTIVO">EFECTIVO</option>
            <option value="OTRO">OTRO</option>
        </select>
    </div>

    <div class="form-group">
        <label>2ª Quincena:</label>
        <input type="number" step="0.01" name="quincena2">
    </div>

    <!-- BANCO ORIGEN EXTRA -->
    <div class="form-group">
        <label>Banco origen – Extra:</label>
        <select name="banco_origen_extra">
            <option value="">Seleccione...</option>
            <option value="MIDFLORIDA">MIDFLORIDA</option>
            <option value="BANK OF AMERICA">BANK OF AMERICA</option>
            <option value="EFECTIVO">EFECTIVO</option>
            <option value="OTRO">OTRO</option>
        </select>
    </div>

    <div class="form-group">
        <label>Extra (opcional):</label>
        <input type="number" step="0.01" name="extra">
    </div>

    <div class="form-buttons">
        <button type="submit">Guardar Ingresos</button>
        <a href="index.php" class="btn-volver">Volver al Dashboard</a>
    </div>

</form>

</div>

<script>
const form = document.getElementById('form-ingresos');
form.addEventListener('submit', () => {
    setTimeout(() => form.reset(), 100);
});
</script>

</body>
</html>