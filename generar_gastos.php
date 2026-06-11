<?php
session_start();
require_once "config/db.php";

$usuario_id = $_SESSION['usuario_id'];

$year = date('Y');
$month = date('m');
$day = date('d');
$quincena = ($day <= 15) ? 1 : 2;

/* Obtener quincena */
$stmt = $pdo->prepare("
    SELECT id FROM quincenas
    WHERE usuario_id=? AND year=? AND month=? AND quincena=?
");
$stmt->execute([$usuario_id,$year,$month,$quincena]);
$q = $stmt->fetch();

if(!$q){
    header("Location: quincena_ingreso.php");
    exit;
}

/* Insertar gastos desde plantillas */
$stmt = $pdo->prepare("
    INSERT INTO gastos 
    (quincena_id, usuario_id, descripcion, categoria, tipo, amount, pagado)
    SELECT ?, usuario_id, descripcion, categoria, tipo, monto_default, 'No'
    FROM pagos_plantilla
    WHERE usuario_id = ? AND activo = 1
");

$stmt->execute([$q['id'], $usuario_id]);

header("Location: gastos/gastos_cobrados.php");
exit;
