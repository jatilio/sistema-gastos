<?php
session_start(); // ← ESTO ES OBLIGATORIO

if (!isset($_SESSION['usuario_id'])) {
    die("No hay sesión activa. No se puede sincronizar.");
}

require_once(__DIR__ . "/../config/db.php");


$usuario = $_SESSION['usuario_id'];
$mes = date("m");
$anio = date("Y");

// 1. Obtener pagos programados del mes
$stmt = $pdo->prepare("
    SELECT id, descripcion, amount, due_date
    FROM mantenimiento_pagos
    WHERE usuario_id = ?
      AND MONTH(due_date) = ?
      AND YEAR(due_date) = ?
      AND activo = 1
");
$stmt->execute([$usuario, $mes, $anio]);
$programados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener pagos reales del mes
$stmt = $pdo->prepare("
    SELECT id, descripcion, amount, payment_date
    FROM gastos
    WHERE usuario_id = ?
      AND pagado = 'Si'
      AND MONTH(payment_date) = ?
      AND YEAR(payment_date) = ?
");
$stmt->execute([$usuario, $mes, $anio]);
$reales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Sincronizar
foreach ($programados as $p) {
    foreach ($reales as $g) {

        // Coincidencia por descripción (flexible)
        if (trim(strtoupper($p['descripcion'])) == trim(strtoupper($g['descripcion']))) {

            // Coincidencia por monto (tolerancia de 1 dólar)
            if (abs($p['amount'] - $g['amount']) <= 1) {

                // Actualizar gastos → asignar mantenimiento_id
                $updateG = $pdo->prepare("
                    UPDATE gastos
                    SET mantenimiento_id = ?
                    WHERE id = ?
                ");
                $updateG->execute([$p['id'], $g['id']]);

                // Actualizar mantenimiento_pagos → marcar como pagado
                $updateP = $pdo->prepare("
                    UPDATE mantenimiento_pagos
                    SET pago = 'Si', payment_date = ?
                    WHERE id = ?
                ");
                $updateP->execute([$g['payment_date'], $p['id']]);
            }
        }
    }
}

echo "Sincronización completada correctamente.";