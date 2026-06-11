<?php
// public/procesos/tarjetas_movimientos.php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Localizar config/db.php
$possibleDb = [
    __DIR__ . '/../../config/db.php',
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../config/database.php'
];

$dbPath = null;
foreach ($possibleDb as $p) {
    if (is_file($p)) { $dbPath = $p; break; }
}

if ($dbPath === null) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error: archivo de configuración db.php no encontrado.\n";
    exit;
}

require_once $dbPath;

// Comprobar usuario logueado
if (empty($_SESSION['usuario_id'])) {
    echo "<p>Debes iniciar sesión para registrar tarjetas.</p>";
    exit;
}

$userId = (int) $_SESSION['usuario_id'];

// Obtener tarjetas
try {
    $stmt = $pdo->prepare("
        SELECT id, banco, nombre_tarjeta, limite, tipo, marca, ultimos_4, estado, dia_corte, dia_pago
        FROM tarjetas 
        WHERE usuario_id = ?
        ORDER BY banco, nombre_tarjeta
    ");
    $stmt->execute([$userId]);
    $tarjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    error_log('Error al obtener tarjetas: ' . $ex->getMessage());
    $tarjetas = [];
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Registrar Nueva Tarjeta</title>

<style>
body{font-family:Arial,Helvetica,sans-serif;padding:18px;background:#f7f7f7}
.container{max-width:980px;margin:0 auto;background:#fff;padding:18px;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:12px}
.form-group{display:flex;flex-direction:column}
label{font-weight:600;margin-bottom:6px}
input[type=text], input[type=number], select{padding:8px;border:1px solid #ccc;border-radius:4px}
.btn-guardar{background:#007bff;color:#fff;padding:10px 14px;border:none;border-radius:4px;cursor:pointer}
.table{width:100%;border-collapse:collapse;margin-top:18px}
.table th,.table td{border:1px solid #e6e6e6;padding:8px;text-align:left}
.hint{font-size:12px;color:#666}
.alert{padding:8px;background:#e9f7ef;border:1px solid #cdebd6;margin-bottom:12px;border-radius:4px}
.small{font-size:13px;color:#666}
</style>
</head>

<body>
<div class="container">

    <h2>💳 Registrar Nueva Tarjeta</h2>

    <div style="color:red;font-weight:bold;">VERSION 7</div>

    <form method="POST" action="guardar_tarjeta.php" class="form-tarjeta-gasto" novalidate>

        <div class="form-grid">
            <div class="form-group">
                <label>Banco</label>
                <input type="text" name="banco" required>
            </div>

            <div class="form-group">
                <label>Nombre de la tarjeta</label>
                <input type="text" name="nombre_tarjeta" required>
            </div>

            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo" id="tipo_tarjeta" required>
                    <option value="">Seleccione tipo</option>
                    <option value="credito">Crédito</option>
                    <option value="debito">Débito</option>
                </select>
            </div>
        </div>

        <!-- CAMPOS SOLO PARA CRÉDITO -->
        <div id="campos_credito" class="form-grid" style="display:none;">

            <div class="form-group">
                <label>Límite de crédito</label>
                <input type="number" step="0.01" name="limite" id="limite_input" placeholder="0.00">
            </div>

            <div class="form-group">
                <label>Día de corte <span class="hint">dd/mm/yyyy</span></label>
                    <input type="text" id="dia_corte_display" placeholder="dd/mm/yyyy" autocomplete="off">
                    <input type="text" id="dia_pago_display" placeholder="dd/mm/yyyy" autocomplete="off">
            </div>

            <div class="form-group">
                <label>Día de pago <span class="hint">dd/mm/yyyy</span></label>
                <input type="text" id="dia_pago_display" placeholder="dd/mm/yyyy" autocomplete="off" inputmode="numeric">
                <input type="hidden" name="dia_pago" id="dia_pago">
            </div>

        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>Marca</label>
                <select name="marca">
                    <option value="">Seleccione marca</option>
                    <option value="Visa">Visa</option>
                    <option value="Mastercard">Mastercard</option>
                    <option value="Amex">American Express</option>
                    <option value="Discover">Discover</option>
                </select>
            </div>

            <div class="form-group">
                <label>Últimos 4 dígitos</label>
                <input type="text" name="ultimos_4" maxlength="4" inputmode="numeric">
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="activa">Activa</option>
                    <option value="inactiva">Inactiva</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn-guardar">Guardar tarjeta</button>
    </form>

    <h2>📋 Tarjetas Registradas</h2>

    <?php if (!empty($tarjetas)): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Banco</th><th>Nombre</th><th>Tipo</th><th>Límite</th><th>Marca</th><th>Últimos 4</th><th>Estado</th><th>Día corte</th><th>Día pago</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tarjetas as $t):
            $display_dia_corte = $t['dia_corte'] ? date('d/m/Y', strtotime($t['dia_corte'])) : '-';
            $display_dia_pago  = $t['dia_pago']  ? date('d/m/Y', strtotime($t['dia_pago']))  : '-';
        ?>
            <tr>
                <td><?= htmlspecialchars($t['banco']) ?></td>
                <td><?= htmlspecialchars($t['nombre_tarjeta']) ?></td>
                <td><?= htmlspecialchars($t['tipo']) ?></td>
                <td><?= $t['tipo']==='credito' && $t['limite']!==null ? '$'.number_format($t['limite'],2) : '-' ?></td>
                <td><?= htmlspecialchars($t['marca'] ?: '-') ?></td>
                <td><?= htmlspecialchars($t['ultimos_4'] ?: '-') ?></td>
                <td><?= htmlspecialchars($t['estado']) ?></td>
                <td><?= $display_dia_corte ?></td>
                <td><?= $display_dia_pago ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="alert">No hay tarjetas registradas.</div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const tipoSelect = document.getElementById('tipo_tarjeta');
    const limiteInput = document.getElementById('limite_input');
    const camposCredito = document.getElementById('campos_credito');
    const diaCorteDisplay = document.getElementById('dia_corte_display');
    const diaPagoDisplay = document.getElementById('dia_pago_display');
    const diaCorteHidden = document.getElementById('dia_corte');
    const diaPagoHidden = document.getElementById('dia_pago');
    const form = document.querySelector('.form-tarjeta-gasto');

    function actualizarTipo() {
        const esCredito = tipoSelect.value === 'credito';
        camposCredito.style.display = esCredito ? 'grid' : 'none';
        if (esCredito) limiteInput.setAttribute('required','required');
        else limiteInput.removeAttribute('required');
    }
    tipoSelect.addEventListener('change', actualizarTipo);
    actualizarTipo();

    function autoSlash(input) {
        input.addEventListener('input', function() {
            let v = input.value.replace(/[^\d]/g,'');
            if (v.length > 2 && v.length <= 4) v = v.slice(0,2)+'/'+v.slice(2);
            else if (v.length > 4) v = v.slice(0,2)+'/'+v.slice(2,4)+'/'+v.slice(4,8);
            input.value = v;
        });
    }
    autoSlash(diaCorteDisplay);
    autoSlash(diaPagoDisplay);

    function toISO(display, hidden) {
        const val = display.value.trim();
        if (!val) { hidden.value = ''; return true; }
        const m = val.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (!m) return false;
        const d = parseInt(m[1],10), mth = parseInt(m[2],10), y = parseInt(m[3],10);
        const days = new Date(y, mth, 0).getDate();
        if (mth<1 || mth>12 || d<1 || d>days) return false;
        hidden.value = `${y}-${String(mth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        return true;
    }

    form.addEventListener('submit', function(e) {
        if (tipoSelect.value === 'credito') {

            if (!limiteInput.value || Number(limiteInput.value) <= 0) {
                e.preventDefault();
                alert("Ingrese un límite válido.");
                limiteInput.focus();
                return;
            }

            if (!toISO(diaCorteDisplay, diaCorteHidden)) {
                e.preventDefault();
                alert("Día de corte inválido.");
                diaCorteDisplay.focus();
                return;
            }

            if (!toISO(diaPagoDisplay, diaPagoHidden)) {
                e.preventDefault();
                alert("Día de pago inválido.");
                diaPagoDisplay.focus();
                return;
            }
        } else {
            diaCorteHidden.value = "";
            diaPagoHidden.value = "";
        }
    });

});
</script>

</body>
</html>