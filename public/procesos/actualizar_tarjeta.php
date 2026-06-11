<?php
require_once("../../config/db.php");

$id            = $_POST['id'];
$banco         = $_POST['banco'];
$nombre        = $_POST['nombre_tarjeta'];
$tipo          = $_POST['tipo'];
$limite        = $_POST['limite'] ?? 0;
$saldo_actual  = $_POST['saldo_actual'] ?? 0;
$dia_corte     = $_POST['dia_corte'] ?: null;
$dia_pago      = $_POST['dia_pago'] ?: null;
$marca         = $_POST['marca'];
$ultimos_4     = $_POST['ultimos_4'];
$estado        = $_POST['estado'];

$sql = "UPDATE tarjetas SET 
            banco = :banco,
            nombre_tarjeta = :nombre,
            tipo = :tipo,
            limite = :limite,
            saldo_actual = :saldo_actual,
            dia_corte = :dia_corte,
            dia_pago = :dia_pago,
            marca = :marca,
            ultimos_4 = :ultimos_4,
            estado = :estado
        WHERE id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':banco'        => $banco,
    ':nombre'       => $nombre,
    ':tipo'         => $tipo,
    ':limite'       => $limite,
    ':saldo_actual' => $saldo_actual,
    ':dia_corte'    => $dia_corte,
    ':dia_pago'     => $dia_pago,
    ':marca'        => $marca,
    ':ultimos_4'    => $ultimos_4,
    ':estado'       => $estado,
    ':id'           => $id
]);

header("Location: ../index.php?menu=tarjetas&msg=actualizada");
exit;