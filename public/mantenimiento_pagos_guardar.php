<?php
session_start();
require_once("../config/db.php");

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: auth/login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Verificar que se enviaron datos
if (!isset($_POST['descripcion']) || !is_array($_POST['descripcion'])) {
    $_SESSION['mensaje_error'] = "No se enviaron datos válidos.";
    header("Location: mantenimiento_pagos.php");
    exit;
}

$descripciones = $_POST['descripcion'];
$due_dates     = $_POST['due_date'];
$frecuencias   = $_POST['frecuencia'];
$categorias    = $_POST['categoria'];
$amounts       = $_POST['amount'];

// Preparar statements
$stmtCheck = $pdo->prepare("SELECT id FROM mantenimiento_pagos WHERE usuario_id = ? AND descripcion = ?");
$stmtUpdate = $pdo->prepare("
    UPDATE mantenimiento_pagos 
    SET due_date = ?, frecuencia = ?, categoria = ?, amount = ?
    WHERE id = ?
");
$stmtInsert = $pdo->prepare("
    INSERT INTO mantenimiento_pagos
    (usuario_id, descripcion, due_date, frecuencia, categoria, amount)
    VALUES (?, ?, ?, ?, ?, ?)
");

$guardados = 0;

try {
    $pdo->beginTransaction();

    for ($i = 0; $i < count($descripciones); $i++) {

        $desc = trim($descripciones[$i]);
        if ($desc === '') continue;

        $fecha = $due_dates[$i] ?: null;
        $freq  = trim($frecuencias[$i]);
        $cat   = $categorias[$i];

        /* ============================================================
           🔥 NORMALIZAR FRECUENCIA
           ============================================================ */
        $freqUpper = strtoupper($freq);

        if (in_array($freqUpper, ['FIJO', 'F', 'TARJETA DE CRÉDITO'])) {
            $freqFinal = 'F';
        } elseif (in_array($freqUpper, ['M', 'MENSUAL'])) {
            $freqFinal = 'M';
        } elseif (in_array($freqUpper, ['A', 'ANUAL'])) {
            $freqFinal = 'A';
        } elseif (in_array($freqUpper, ['S', 'SEMESTRAL'])) {
            $freqFinal = 'S';
        } else {
            $freqFinal = 'F'; // fallback seguro
        }

        /* ============================================================
           🔥 LIMPIEZA TOTAL DEL MONTO
           ============================================================ */
        $montoOriginal = $amounts[$i];

        if ($montoOriginal === '' || $montoOriginal === null) {
            $montoNumero = null;
        } else {
            $montoLimpio = preg_replace('/[^0-9\.\,\-]/u', '', $montoOriginal);
            $montoLimpio = preg_replace('/\s+/u', '', $montoLimpio);
            $montoLimpio = str_replace(',', '.', $montoLimpio);
            $montoNumero = floatval($montoLimpio);
        }

        // Revisar si ya existe
        $stmtCheck->execute([$usuario_id, $desc]);
        $existe = $stmtCheck->fetchColumn();

        if ($existe) {
            $stmtUpdate->execute([$fecha, $freqFinal, $cat, $montoNumero, $existe]);
        } else {
            $stmtInsert->execute([$usuario_id, $desc, $fecha, $freqFinal, $cat, $montoNumero]);
        }

        $guardados++;
    }

    $pdo->commit();
    $_SESSION['mensaje_exito'] = "$guardados registro(s) procesado(s) correctamente.";

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['mensaje_error'] = "Error al guardar los registros: " . $e->getMessage();
}

header("Location: mantenimiento_pagos.php");
exit;
?>