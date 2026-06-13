<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../../config/db.php";

$usuario_id = $_SESSION['usuario_id'];

/* ============================================================
   VALIDAR DATOS RECIBIDOS
============================================================ */

if (!isset($_POST['id'])) {
    die("Error: No se recibió el ID del gasto.");
}

$id               = $_POST['id'];
$mantenimiento_id = $_POST['mantenimiento_id'];
$descripcion      = $_POST['descripcion'];
$amount           = $_POST['amount'];
$due_date         = $_POST['due_date'];

/* ============================================================
   TARJETAS DE CRÉDITO (tabla tarjetas)
============================================================ */

$sql = "SELECT id, nombre_tarjeta AS nombre, banco 
        FROM tarjetas 
        WHERE usuario_id = :usuario_id 
        ORDER BY nombre_tarjeta ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id]);
$tarjetas_credito = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   TARJETAS DE DÉBITO (tabla cuentas)
============================================================ */

$sql = "SELECT id, nombre AS nombre 
        FROM cuentas 
        WHERE usuario_id = :usuario_id 
        ORDER BY nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id]);
$tarjetas_debito = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   BANCOS (solo existen en tarjetas)
============================================================ */

$sql = "SELECT DISTINCT banco 
        FROM tarjetas 
        WHERE usuario_id = :usuario_id 
        ORDER BY banco ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario_id' => $usuario_id]);
$bancos = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pagar Gasto</title>

<link rel="stylesheet" href="../assets/css/ingresar.css">

<style>
.form-box {
    max-width: 450px;
    margin: auto;
    background: #ffffff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 0 10px #ccc;
}
.form-box h2 {
    margin-bottom: 15px;
}
.form-box label {
    font-weight: bold;
    margin-top: 10px;
    display: block;
}
.form-box input, .form-box select {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
}
.btn-guardar {
    margin-top: 15px;
    width: 100%;
    padding: 10px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn-guardar:hover {
    background: #218838;
}
</style>

</head>
<body>

<a href="/gastos/ingresar.php" style="
    display:inline-block;
    padding:8px 14px;
    background:#6c757d;
    color:white;
    text-decoration:none;
    border-radius:5px;
    margin-bottom:15px;
">
⬅ Regresar
</a>

<div class="form-box">

<h2>Pagar Gasto</h2>

<p><strong>Descripción:</strong> <?= htmlspecialchars($descripcion) ?></p>
<p><strong>Monto:</strong> $<?= number_format($amount, 2) ?></p>
<p><strong>Fecha:</strong> <?= $due_date ?></p>

<form action="/gastos/ingresar_guardar.php" method="POST">

    <!-- DATOS OCULTOS -->
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="mantenimiento_id" value="<?= $mantenimiento_id ?>">
    <input type="hidden" name="descripcion" value="<?= htmlspecialchars($descripcion) ?>">
    <input type="hidden" name="amount" value="<?= $amount ?>">
    <input type="hidden" name="due_date" value="<?= $due_date ?>">

    <!-- MÉTODO DE PAGO -->
    <label>Método de Pago</label>
    <select name="metodo_pago" required>
        <option value="">Seleccione...</option>
        <option value="Tarjeta Crédito">Tarjeta Crédito</option>
        <option value="Tarjeta Débito">Tarjeta Débito</option>
        <option value="Transferencia">Transferencia</option>
        <option value="Efectivo">Efectivo</option>
        <option value="Zelle">Zelle</option>
        <option value="ACH">ACH</option>
    </select>

    <!-- TARJETAS DE CRÉDITO -->
    <label>Tarjeta de Crédito</label>
    <select name="tarjeta_credito_id">
        <option value="">Ninguna</option>
        <?php foreach ($tarjetas_credito as $t): ?>
            <option value="<?= $t['id'] ?>">
                <?= htmlspecialchars($t['nombre']) ?> (<?= $t['banco'] ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <!-- TARJETAS DE DÉBITO -->
    <label>Tarjeta de Débito</label>
    <select name="tarjeta_debito_id">
        <option value="">Ninguna</option>
        <?php foreach ($tarjetas_debito as $d): ?>
            <option value="<?= $d['id'] ?>">
                <?= htmlspecialchars($d['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- BANCOS -->
    <label>Banco</label>
    <select name="banco_pago">
        <option value="">Seleccione...</option>
        <?php foreach ($bancos as $b): ?>
            <option value="<?= $b ?>"><?= htmlspecialchars($b) ?></option>
        <?php endforeach; ?>
    </select>

    <!-- CONFIRMACIÓN -->
    <label>Número de Confirmación</label>
    <input type="text" name="confirmation" placeholder="Opcional">

    <button type="submit" class="btn-guardar">Guardar Pago</button>

</form>

</div>

</body>
</html>
