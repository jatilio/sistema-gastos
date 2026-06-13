<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../../config/db.php";

$usuario_id = $_SESSION['usuario_id'];

// VALIDAR ID
if (!isset($_POST['id'])) {
    die("Error: No se recibió el ID.");
}

$id = intval($_POST['id']);

// CAPTURAR CAMPOS
$payment_date = $_POST['payment_date'] ?? null;
$confirmation = $_POST['confirmation'] ?? null;
$banco_pago = $_POST['banco_pago'] ?? null;
$banco_pago = strtoupper($banco_pago); // convertir a MAYÚSCULAS
$notes = $_POST['notes'] ?? null;

// MAPEO COMPLETO DE TARJETAS Y CUENTAS
$debito_ids = [
    'MIDFLORIDA' => 6,
    'BANK OF AMERICA' => 7
];

$credito_ids = [
    'BEST BUY'        => 4,
    'UNIVERSAL VISA'  => 8,
    'APPLE CARD'      => 9,
    'UNITY'           => 10,
    'SOUTHWEST'       => 11,
    'SAMS CLUB'       => 12,
    'MIDFLORIDA-VISA' => 13,
    'DISNEY'          => 14
];

// ASIGNAR TARJETA_ID Y MÉTODO DE PAGO
if (isset($debito_ids[$banco_pago])) {
    $tarjeta_id = $debito_ids[$banco_pago];
    $metodo_pago = 'DEBITO';
} elseif (isset($credito_ids[$banco_pago])) {
    $tarjeta_id = $credito_ids[$banco_pago];
    $metodo_pago = 'CREDITO';
} else {
    $tarjeta_id = null;
    $metodo_pago = 'DESCONOCIDO';
}

// SI NO HAY FECHA, USAR HOY
if (empty($payment_date)) {
    $payment_date = date("Y-m-d");
}

// ACTUALIZAR REGISTRO
$sql = "
    UPDATE gastos
    SET 
        pagado = 'Si',
        payment_date = :payment_date,
        confirmation = :confirmation,
        banco_pago = :banco_pago,
        notes = :notes,
        metodo_pago = :metodo_pago,
        tarjeta_id = :tarjeta_id
    WHERE id = :id
      AND usuario_id = :usuario_id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'payment_date' => $payment_date,
    'confirmation' => $confirmation,
    'banco_pago'   => $banco_pago,
    'notes'        => $notes,
    'metodo_pago'  => $metodo_pago,
    'tarjeta_id'   => $tarjeta_id,
    'id'           => $id,
    'usuario_id'   => $usuario_id
]);

// REDIRIGIR
header("Location: /gastos/ingresar.php");
exit;
