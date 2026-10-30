<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
$token = $_POST['adicional_token'] ?? null;
if (!is_string($token) || !isset($_SESSION['adicional_token']) ||
    !hash_equals($_SESSION['adicional_token'], $token)) {
    http_response_code(409);
    exit('Este formulario ya se guardó o venció. Vuelve a abrir Registrar gastos.');
}
$descripciones  = $_POST['descripcion'] ?? [];
$montos         = $_POST['monto'] ?? [];
$categorias     = $_POST['categoria'] ?? [];
$metodos_pago   = $_POST['metodo_pago'] ?? [];
$bancos_pago    = $_POST['banco_pago'] ?? [];
$payment_dates  = $_POST['payment_date'] ?? [];
$notes          = $_POST['notes'] ?? [];

try {
    $pdo->beginTransaction();

    foreach ($descripciones as $index => $descripcion) {

        if (trim($descripcion) === '') continue;

        $monto         = (float)$montos[$index];
        $categoria     = $categorias[$index] ?? null;
        $metodo_pago   = $metodos_pago[$index] ?? null;
        $banco_pago    = $bancos_pago[$index] ?? null;
        $payment_date  = $payment_dates[$index] ?? null;
        $nota          = trim($notes[$index] ?? '');

        $stmt = $pdo->prepare("
            INSERT INTO gastos (
                usuario_id,
                descripcion,
                categoria,
                amount,
                payment_date,
                pagado,
                metodo_pago,
                banco_pago,
                notes,
                tipo
            ) VALUES (
                :usuario_id,
                :descripcion,
                :categoria,
                :amount,
                :payment_date,
                'Si',
                :metodo_pago,
                :banco_pago,
                :notes,
                'Adicional'
            )
        ");

        $stmt->execute([
            ':usuario_id'   => $usuario_id,
            ':descripcion'  => $descripcion,
            ':categoria'    => $categoria,
            ':amount'       => $monto,
            ':payment_date' => $payment_date,
            ':metodo_pago'  => $metodo_pago,
            ':banco_pago'   => $banco_pago,
            ':notes'        => $nota
        ]);
    }

    $pdo->commit();
    unset($_SESSION['adicional_token']);

    header("Location: ../gastos/adicionales.php?success=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo "Error al guardar los gastos adicionales: " . $e->getMessage();
}
