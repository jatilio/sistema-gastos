<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/config/pagos_programados.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Usa el formulario de mantenimiento para generar los pagos.');
}
$token = $_POST['generar_pagos_token'] ?? null;
if (!is_string($token) || !isset($_SESSION['generar_pagos_token']) ||
    !hash_equals($_SESSION['generar_pagos_token'], $token)) {
    http_response_code(403);
    exit('Formulario inválido. Vuelve a abrir Mantenimiento de Pagos.');
}
$month = filter_var($_POST['month'] ?? null, FILTER_VALIDATE_INT);
$year = filter_var($_POST['year'] ?? null, FILTER_VALIDATE_INT);

if ($month < 1 || $month > 12 || $year < 1900 || $year > 9998) {
    http_response_code(400);
    exit('Mes o año inválido.');
}

// Serializar las generaciones del mismo usuario, incluso desde sesiones distintas.
$lockName = 'gastos_mensuales_' . $usuario_id;
$lock = $pdo->prepare('SELECT GET_LOCK(?, 10)');
$lock->execute([$lockName]);
if ((int)$lock->fetchColumn() !== 1) {
    http_response_code(409);
    exit('Ya hay una generación en curso. Intenta nuevamente.');
}
try {
$pdo->beginTransaction();
$existe = $pdo->prepare('SELECT id FROM gastos WHERE usuario_id = ? AND mantenimiento_id = ? AND due_date >= ? AND due_date < ? LIMIT 1');
$inicioMes = sprintf('%04d-%02d-01', $year, $month);
$finMes = (new DateTimeImmutable($inicioMes))->modify('+1 month')->format('Y-m-d');
// Un mes con pagos generados está cerrado a nuevas generaciones.
$mesExistente = $pdo->prepare('SELECT id FROM gastos WHERE usuario_id=? AND mantenimiento_id IS NOT NULL AND due_date>=? AND due_date<? LIMIT 1');
$mesExistente->execute([$usuario_id,$inicioMes,$finMes]);
if ($mesExistente->fetchColumn() !== false) {
    throw new DomainException('Los pagos de este mes ya fueron generados.');
}

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

    $fecha_generada = fechaPagoProgramado($item, $month, $year);
    if ($fecha_generada === null) continue;

    // Una plantilla solo debe producir un gasto por mes y usuario.
    $existe->execute([$usuario_id, $item['id'], $inicioMes, $finMes]);
    if ($existe->fetchColumn() !== false) {
        continue;
    }
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
$pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $generationBlocked = $e instanceof DomainException;
    if (!$generationBlocked) error_log('Error al generar pagos mensuales: ' . $e->getMessage());
    http_response_code($generationBlocked ? 409 : 500);
    $generationFailed = true;
} finally {
    $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $release->execute([$lockName]);
}
if (!empty($generationFailed)) {
    exit(!empty($generationBlocked) ? 'Los pagos de este mes ya fueron generados. El mes está bloqueado.' : 'No se generaron los pagos. Intenta nuevamente.');
}
header("Location: /gastos/ingresar.php?mes=$month&anio=$year&success=1");
exit;
