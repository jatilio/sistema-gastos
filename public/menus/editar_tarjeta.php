<?php
require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID de tarjeta no proporcionado.");
}

// Obtener tarjeta
$stmt = $pdo->prepare("SELECT * FROM tarjetas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $_SESSION['usuario_id']]);
$tarjeta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tarjeta) {
    die("Tarjeta no encontrada.");
}
?>

<link rel="stylesheet" href="assets/css/tarjetas_gastos.css">

<div class="tarjeta-gastos-container">

    <h2 class="titulo-modulo">✏️ Editar Tarjeta</h2>

    <form method="POST" action="procesos/actualizar_tarjeta.php" class="form-tarjeta-gasto">

        <input type="hidden" name="id" value="<?= $tarjeta['id'] ?>">

        <div class="form-grid">
            <div class="form-group">
                <label>Banco</label>
                <input type="text" name="banco" value="<?= htmlspecialchars($tarjeta['banco']) ?>" required>
            </div>

            <div class="form-group">
                <label>Nombre de la tarjeta</label>
                <input type="text" name="nombre_tarjeta" value="<?= htmlspecialchars($tarjeta['nombre_tarjeta']) ?>" required>
            </div>

            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo" id="tipo_tarjeta" required>
                    <option value="credito" <?= $tarjeta['tipo'] === 'credito' ? 'selected' : '' ?>>Crédito</option>
                    <option value="debito" <?= $tarjeta['tipo'] === 'debito' ? 'selected' : '' ?>>Débito</option>
                </select>
            </div>
        </div>

        <!-- CAMPOS SOLO PARA CRÉDITO -->
        <div id="campos_credito" class="form-grid" style="<?= $tarjeta['tipo'] === 'credito' ? 'display:grid;' : 'display:none;' ?>">

            <div class="form-group">
                <label>Límite de crédito</label>
                <input type="number" step="0.01" name="limite" value="<?= $tarjeta['limite'] ?>">
            </div>

            <div class="form-group">
                <label>Saldo inicial</label>
                <input type="number" step="0.01" name="saldo_actual" id="saldo_actual" value="<?= $tarjeta['saldo_actual'] ?>">
            </div>

            <div class="form-group">
                <label>Día de corte</label>
                <input type="date" name="dia_corte" value="<?= $tarjeta['dia_corte'] ?>">
            </div>

            <div class="form-group">
                <label>Día de pago</label>
                <input type="date" name="dia_pago" value="<?= $tarjeta['dia_pago'] ?>">
            </div>

        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>Marca</label>
                <select name="marca">
                    <option value="">Seleccione marca</option>
                    <option value="Visa"       <?= $tarjeta['marca'] === 'Visa' ? 'selected' : '' ?>>Visa</option>
                    <option value="Mastercard" <?= $tarjeta['marca'] === 'Mastercard' ? 'selected' : '' ?>>Mastercard</option>
                    <option value="Amex"       <?= $tarjeta['marca'] === 'Amex' ? 'selected' : '' ?>>American Express</option>
                    <option value="Discover"   <?= $tarjeta['marca'] === 'Discover' ? 'selected' : '' ?>>Discover</option>
                </select>
            </div>

            <div class="form-group">
                <label>Últimos 4 dígitos</label>
                <input type="text" name="ultimos_4" maxlength="4" value="<?= $tarjeta['ultimos_4'] ?>">
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="activa"   <?= $tarjeta['estado'] === 'activa' ? 'selected' : '' ?>>Activa</option>
                    <option value="inactiva" <?= $tarjeta['estado'] === 'inactiva' ? 'selected' : '' ?>>Inactiva</option>
                </select>
            </div>
        </div>

        <button class="btn-guardar">Actualizar tarjeta</button>
    </form>

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
</script>