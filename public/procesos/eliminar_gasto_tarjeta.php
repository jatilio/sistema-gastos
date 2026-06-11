<?php
require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID inválido.");
}

$stmt = $pdo->prepare("DELETE FROM gastos WHERE id = ? AND tarjeta_id IS NOT NULL");
$stmt->execute([$id]);

header("Location: ../index.php?menu=tarjetas_gastos&msg=eliminado");
exit;