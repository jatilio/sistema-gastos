<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

require_once __DIR__.'/../../config/seguridad_formularios.php';
$usuario_id=exigirSesionFinanzas();
validarPostFinanzas();

if (!$usuario_id) {
    die("ERROR: No hay sesión activa.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("ERROR: No es POST.");
}

// Recibir datos
$gasto_id    = $_POST['gasto_id'] ?? null;
$tarjeta_id  = $_POST['tarjeta_id'] ?? null;
$descripcion = is_string($_POST['descripcion']??null)?trim($_POST['descripcion']):'';
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
    if(!is_scalar($monto)){http_response_code(422);exit("Monto inválido.");}
    $montoLimpio = preg_replace('/[^\d.\-]/u', '', $monto);
    $montoFinal = ($montoLimpio === '' ? 0 : (float)$montoLimpio);

    if(!is_string($pagado)||!in_array($pagado,['Si','No'],true)||!is_string($fecha)||!($fechaObjeto=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha))||$fechaObjeto->format('Y-m-d')!==$fecha||!is_scalar($monto)||!is_numeric($monto)||$montoFinal<=0||$montoFinal>99999999.99||mb_strlen($descripcion)>150){http_response_code(422);exit('Revisa el monto, la descripción y la fecha.');}
    $consultaGasto=$pdo->prepare('SELECT id FROM gastos WHERE id=? AND usuario_id=? AND tarjeta_id IS NOT NULL');$consultaGasto->execute([$gasto_id,$usuario_id]);if(!$consultaGasto->fetchColumn()){http_response_code(404);exit('Gasto no encontrado.');}

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
            WHERE id = :gasto_id AND usuario_id = :usuario_id AND tarjeta_id IS NOT NULL";

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

    error_log('Edición gasto tarjeta: '.$e->getMessage());
    http_response_code(422);exit('No se pudo guardar el gasto. Revisa los datos.');
}
