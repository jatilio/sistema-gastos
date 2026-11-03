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
$tarjeta_id  = $_POST['tarjeta_id'] ?? null;
$descripcion = is_string($_POST['descripcion']??null)?trim($_POST['descripcion']):'';
$monto       = $_POST['monto'] ?? null;
$fecha       = $_POST['fecha'] ?? null;
$pagado      = $_POST['pagado'] ?? 'No';

if (!$tarjeta_id || !$descripcion || !$monto || !$fecha) {
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

    if(!is_string($pagado)||!in_array($pagado,['Si','No'],true)||!is_string($fecha)||!($fechaObjeto=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha))||$fechaObjeto->format('Y-m-d')!==$fecha||!is_numeric($monto)||$montoFinal<=0||$montoFinal>99999999.99||mb_strlen($descripcion)>150){http_response_code(422);exit('Revisa el monto, la descripción y la fecha.');}
    // 4. INSERTAR GASTO DE TARJETA
    $sql = "INSERT INTO gastos 
            (usuario_id, tarjeta_id, mantenimiento_id, descripcion, tipo, categoria, due_date, amount, pagado, metodo_pago)
            VALUES 
            (:usuario_id, :tarjeta_id, NULL, :descripcion, 'Adicional', 'Tarjeta', :fecha, :monto, :pagado, :metodo_pago)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario_id'  => $usuario_id,
        ':tarjeta_id'  => $tarjeta_id,
        ':descripcion' => $descripcion,
        ':fecha'       => $fecha,
        ':monto'       => $montoFinal,
        ':pagado'      => $pagado,
        ':metodo_pago' => $metodo_pago
    ]);

    header("Location: ../index.php?menu=tarjetas_gastos&msg=ok");
    exit;

} catch (PDOException $e) {

    error_log('Registro gasto tarjeta: '.$e->getMessage());
    http_response_code(422);exit('No se pudo guardar el gasto. Revisa los datos.');
}
