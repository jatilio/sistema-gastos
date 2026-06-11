<?php
require_once __DIR__ . '/../../config/db.php';

// Obtener tarjetas del usuario
$stmt = $pdo->prepare("SELECT * FROM tarjetas WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$tarjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="assets/css/tarjetas_gastos.css">

<div class="tarjeta-gastos-container">

    <!-- MENSAJES -->
    <?php if (!empty($_GET['msg'])): ?>
        <div class="alerta-exito">
            ✔ 
            <?php 
                if ($_GET['msg'] === 'ok') echo "Tarjeta registrada correctamente";
                if ($_GET['msg'] === 'eliminada') echo "Tarjeta eliminada correctamente";
                if ($_GET['msg'] === 'actualizada') echo "Tarjeta actualizada correctamente";
            ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <h2 class="titulo-modulo">💳 Registrar Nueva Tarjeta</h2>

    <form method="POST" action="procesos/guardar_tarjeta.php" class="form-tarjeta-gasto" novalidate>

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
                <input type="number" step="0.01" name="limite" id="limite_input">
            </div>

            <div class="form-group">
                <label>Saldo inicial</label>
                <input type="number" step="0.01" name="saldo_actual" id="saldo_actual" value="0.00">
            </div>

            <div class="form-group">
                <label>Día de corte <span class="hint">dd/mm/yyyy</span></label>
                <input type="text" id="dia_corte_display" placeholder="dd/mm/yyyy" autocomplete="off">
                <input type="hidden" name="dia_corte" id="dia_corte">
            </div>

            <div class="form-group">
                <label>Día de pago <span class="hint">dd/mm/yyyy</span></label>
                <input type="text" id="dia_pago_display" placeholder="dd/mm/yyyy" autocomplete="off">
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
                <input type="text" name="ultimos_4" maxlength="4">
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="activa">Activa</option>
                    <option value="inactiva">Inactiva</option>
                </select>
            </div>
        </div>

        <button class="btn-guardar">Guardar tarjeta</button>
    </form>

    <!-- LISTADO -->
    <h2 class="titulo-modulo">📋 Tarjetas Registradas</h2>

    <div class="tabla-responsive">
        <table class="tabla-tarjetas">
            <thead>
                <tr>
                    <th>Banco</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Límite</th>
                    <th>Marca</th>
                    <th>Últimos 4</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($tarjetas as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['banco']) ?></td>
                        <td><?= htmlspecialchars($t['nombre_tarjeta']) ?></td>
                        <td><?= ucfirst($t['tipo']) ?></td>
                        <td><?= $t['tipo'] === 'credito' ? '$' . number_format($t['limite'], 2) : '-' ?></td>
                        <td><?= $t['marca'] ?: '-' ?></td>
                        <td><?= $t['ultimos_4'] ?: '-' ?></td>
                        <td><?= ucfirst($t['estado']) ?></td>

                        <td class="acciones">
                            <a href="index.php?menu=editar_tarjeta&id=<?= $t['id'] ?>" class="btn-editar">Editar</a>
                            <a href="procesos/eliminar_tarjeta.php?id=<?= $t['id'] ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar esta tarjeta?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
// Mostrar u ocultar campos según el tipo de tarjeta
document.getElementById('tipo_tarjeta').addEventListener('change', function() {
    const camposCredito = document.getElementById('campos_credito');
    camposCredito.style.display = this.value === 'credito' ? 'grid' : 'none';

    if (this.value !== 'credito') {
        document.getElementById('saldo_actual').value = "0.00";
    }
});

// Autoformato dd/mm/yyyy
function autoSlash(input) {
    input.addEventListener('input', function() {
        let v = input.value.replace(/[^\d]/g,'');
        if (v.length > 2 && v.length <= 4) v = v.slice(0,2)+'/'+v.slice(2);
        else if (v.length > 4) v = v.slice(0,2)+'/'+v.slice(2,4)+'/'+v.slice(4,8);
        input.value = v;
    });
}

autoSlash(document.getElementById('dia_corte_display'));
autoSlash(document.getElementById('dia_pago_display'));

// Convertir a ISO antes de enviar
document.querySelector('.form-tarjeta-gasto').addEventListener('submit', function(e) {

    const tipo = document.getElementById('tipo_tarjeta').value;

    if (tipo === 'credito') {

        const corte = document.getElementById('dia_corte_display').value.trim();
        const pago  = document.getElementById('dia_pago_display').value.trim();

        function toISO(val) {
            const m = val.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
            if (!m) return null;
            const d = m[1].padStart(2,'0');
            const mo = m[2].padStart(2,'0');
            const y = m[3];
            return `${y}-${mo}-${d}`;
        }

        const isoCorte = toISO(corte);
        const isoPago  = toISO(pago);

        if (!isoCorte) {
            alert("Día de corte inválido.");
            e.preventDefault();
            return;
        }

        if (!isoPago) {
            alert("Día de pago inválido.");
            e.preventDefault();
            return;
        }

        document.getElementById('dia_corte').value = isoCorte;
        document.getElementById('dia_pago').value  = isoPago;
    }
});
</script>