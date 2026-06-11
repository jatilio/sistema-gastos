<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    die("ERROR: No hay sesión activa.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("ERROR: No es POST.");
}

// Recibir datos
$gasto_id    = $_POST['gasto_id'] ?? null;
$tarjeta_id  = $_POST['tarjeta_id'] ?? null;
$descripcion = trim($_POST['descripcion'] ?? '');
$monto       = $_POST['monto'] ?? null;
$fecha       = $_POST['fecha'] ?? null;
$pagado      = $_POST['pagado'] ?? 'No';

if (!$gasto_id || !$tarjeta_id || !$descripcion || !$monto || !$fecha) {
    die("ERROR: Faltan datos obligatorios.");
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. OBTENER TIPO DE TARJETA
    $stmtTipo = $pdo->prepare("
        SELECT tipo 
        FROM tarjetas 
        WHERE id = :id AND usuario_id = :usuario_id
    ");
    $stmtTipo->execute([
        ':id' => $tarjeta_id,
        ':usuario_id' => $usuario_id
    ]);

    $tarjeta = $stmtTipo->fetch(PDO::FETCH_ASSOC);

    if (!$tarjeta) {
        die("ERROR: Tarjeta no encontrada.");
    }

    // 2. DEFINIR METODO DE PAGO SEGÚN TIPO DE TARJETA
    if ($tarjeta['tipo'] === 'debito') {
        $metodo_pago = 'debito';
    } else {
        $metodo_pago = 'credito';
    }

    // 3. LIMPIEZA DE MONTO
    $montoLimpio = preg_replace('/[^\d.\-]/u', '', $monto);
    $montoFinal = ($montoLimpio === '' ? 0 : (float)$montoLimpio);

    // 4. ACTUALIZAR GASTO DE TARJETA
    $sql = "UPDATE gastos SET
                tarjeta_id     = :tarjeta_id,
                descripcion    = :descripcion,
                amount         = :amount,
                due_date       = :fecha,
                pagado         = :pagado,
                metodo_pago    = :metodo_pago,
                categoria      = 'Tarjeta',
                tipo           = 'Adicional'
            WHERE id = :gasto_id AND usuario_id = :usuario_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':tarjeta_id'  => $tarjeta_id,
        ':descripcion' => $descripcion,
        ':amount'      => $montoFinal,
        ':fecha'       => $fecha,
        ':pagado'      => $pagado,
        ':metodo_pago' => $metodo_pago,
        ':gasto_id'    => $gasto_id,
        ':usuario_id'  => $usuario_id
    ]);

    header("Location: ../index.php?menu=tarjetas_gastos&msg=actualizado");
    exit;

} catch (PDOException $e) {

    file_put_contents(
        __DIR__ . "/error_gasto_tarjeta.txt",
        date('Y-m-d H:i:s') . " ERROR SQL:\n" . $e->getMessage() . "\n\n",
        FILE_APPEND
    );

    die("ERROR SQL: " . $e->getMessage());
}