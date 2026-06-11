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

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

/* ============================================================
   1. OBTENER PLANTILLA mantenimiento_pagos
   ============================================================ */
$stmt = $pdo->prepare("
    SELECT *
    FROM mantenimiento_pagos
    WHERE usuario_id = ?
      AND activo = 1
    ORDER BY id ASC
");
$stmt->execute([$usuario_id]);
$plantilla = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   2. PREPARAR INSERT (CORREGIDO)
   ============================================================ */
$insert = $pdo->prepare("
    INSERT INTO gastos (
        usuario_id,
        mantenimiento_id,
        descripcion,
        categoria,
        due_date,
        amount,
        pagado,
        tipo
    )
    VALUES (?, ?, ?, ?, ?, ?, 'No', 'Fijo')
");

/* ============================================================
   3. GENERAR PAGOS DEL MES
   ============================================================ */
foreach ($plantilla as $item) {

    $frecuencia = strtoupper(trim($item['frecuencia'])); // M, A, S, F
    $due_date   = $item['due_date'];
    $dia_orig   = (int)date('d', strtotime($due_date));
    $mes_orig   = (int)date('m', strtotime($due_date));
    $anio_orig  = (int)date('Y', strtotime($due_date));

    /* ============================================================
       FRECUENCIA ANUAL (A)
       ============================================================ */
    if ($frecuencia === 'A') {
        if ($mes_orig !== $month) {
            continue;
        }
    }

    /* ============================================================
       FRECUENCIA SEMESTRAL (S)
       ============================================================ */
    if ($frecuencia === 'S') {
        $diff_meses = (($year - $anio_orig) * 12) + ($month - $mes_orig);
        if ($diff_meses < 0 || $diff_meses % 6 !== 0) {
            continue;
        }
    }

    /* ============================================================
       FRECUENCIA FIJO (F o FIJO)
       ============================================================ */
    if ($frecuencia === 'F' || $frecuencia === 'FIJO') {
        // Siempre genera
    }

    /* ============================================================
       FRECUENCIA MENSUAL (M)
       ============================================================ */
    if ($frecuencia === 'M') {
        // Siempre genera
    }

    /* ============================================================
       AJUSTAR FECHA AL ÚLTIMO DÍA VÁLIDO DEL MES
       ============================================================ */
    $ultimo_dia_mes = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $dia_final = min($dia_orig, $ultimo_dia_mes);

    $fecha_generada = sprintf('%04d-%02d-%02d', $year, $month, $dia_final);

    /* ============================================================
       INSERTAR REGISTRO (CORREGIDO)
       ============================================================ */
    $insert->execute([
        $usuario_id,
        $item['id'],
        $item['descripcion'],
        $item['categoria'],
        $fecha_generada,
        $item['amount']
    ]);
}

/* ============================================================
   4. REDIRECCIÓN
   ============================================================ */
header("Location: /gastos/ingresar.php?month=$month&year=$year&success=1");
exit;
