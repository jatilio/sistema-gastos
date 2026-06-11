<?php
session_start();
require_once("../config/db.php");

$usuario_id = $_SESSION['usuario_id'];

$year  = date("Y");
$month = date("m");

/* ================= OBTENER INGRESO TOTAL ================= */
$stmt = $pdo->prepare("
    SELECT SUM(ingreso)
    FROM quincenas
    WHERE usuario_id = ? AND year = ? AND month = ?
");
$stmt->execute([$usuario_id, $year, $month]);
$ingreso_total = (float)$stmt->fetchColumn();

/* ================= OBTENER GASTADO TOTAL ================= */
$stmt = $pdo->prepare("
    SELECT SUM(amount)
    FROM gastos
    WHERE usuario_id = ?
      AND pagado = 'SI'
      AND MONTH(due_date) = ?
      AND YEAR(due_date) = ?
");
$stmt->execute([$usuario_id, $month, $year]);
$gastado_total = (float)$stmt->fetchColumn();

$saldo = $ingreso_total - $gastado_total;

/* ================= RESPUESTA JSON ================= */
echo json_encode([
    "ingreso" => number_format($ingreso_total, 2, '.', ''),
    "gastado" => number_format($gastado_total, 2, '.', ''),
    "saldo"   => number_format($saldo, 2, '.', '')
]);