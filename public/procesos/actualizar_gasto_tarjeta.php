<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

$id          = $_POST['id'] ?? null;
$tarjeta_id  = $_POST['tarjeta_id'] ?? null;
$descripcion = $_POST['descripcion'] ?? null;
$monto       = $_POST['monto'] ?? null;
$fecha       = $_POST['fecha'] ?? null;
$pagado      = $_POST['pagado'] ?? 'No';

if (!$id || !$tarjeta_id || !$descripcion || !$monto || !$fecha) {
    die("ERROR: Faltan datos obligatorios.");
}

try {
    $sql = "UPDATE gastos SET
                tarjeta_id = :tarjeta_id,
                descripcion = :descripcion,
                amount = :monto,
                due_date = :fecha,
                pagado = :pagado
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':tarjeta_id' => $tarjeta_id,
        ':descripcion' => $descripcion,
        ':monto' => $monto,
        ':fecha' => $fecha,
        ':pagado' => $pagado,
        ':id' => $id
    ]);

    header("Location: ../index.php?menu=tarjetas_gastos&msg=actualizado");
    exit;

} catch (PDOException $e) {
    die("ERROR SQL: " . $e->getMessage());
}