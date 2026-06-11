<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: auth/login.php");
    exit;
}

require_once "config/db.php";

$usuario_id = $_SESSION['usuario_id'];

$year = $_POST['year'];
$month = $_POST['month'];
$quincena = $_POST['quincena'];
$ingreso = $_POST['ingreso'];
$saldo_anterior = $_POST['saldo_anterior'] ?? 0;

// Verificar si ya existe (SEGURIDAD)
$stmt = $pdo->prepare("
    SELECT id FROM quincenas 
    WHERE year=? AND month=? AND quincena=? AND usuario_id=?
");
$stmt->execute([$year, $month, $quincena, $usuario_id]);

if ($stmt->fetch()) {
    die("❌ Esta quincena ya fue registrada.");
}

// Insertar quincena
$stmt = $pdo->prepare("
    INSERT INTO quincenas 
    (year, month, quincena, ingreso, saldo_anterior, saldo_final, usuario_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $year,
    $month,
    $quincena,
    $ingreso,
    $saldo_anterior,
    $ingreso + $saldo_anterior,
    $usuario_id
]);

// Volver al dashboard
header("Location: index.php");
exit;
