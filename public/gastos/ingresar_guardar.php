<?php
require_once __DIR__ . '/../../config/seguridad_web.php';

if (session_status() === PHP_SESSION_NONE) {
    if(session_status()!==PHP_SESSION_ACTIVE)session_start();
}

if (empty($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
include __DIR__ . "/../../config/db.php";

$usuario_id = $_SESSION['usuario_id'];

// VALIDAR ID
if (!isset($_POST['id'])) {
    die("Error: No se recibió el ID.");
}

$id = intval($_POST['id']);

/* ============================================================
   1. DETECTAR SI SOLO SE ESTÁ GUARDANDO VALIDADO
============================================================ */
if (isset($_POST['validado'])) {

    $validado = ($_POST['validado'] == 1) ? 1 : 0;

    $sql = "
        UPDATE gastos
        SET validado = :validado
        WHERE id = :id AND usuario_id = :usuario_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'validado'   => $validado,
        'id'         => $id,
        'usuario_id' => $usuario_id
    ]);

    // REDIRIGIR SIN AFECTAR NADA MÁS
    header("Location: /gastos/ingresar.php");
    exit;
}

/* ============================================================
   2. SI NO ES VALIDADO → ES UN PAGO NORMAL
============================================================ */

// El monto es opcional para formularios anteriores. Se guarda con el pago en un único UPDATE.
$montoCorregido=null;
if(array_key_exists('amount',$_POST)){
 $valorMonto=$_POST['amount'];
 if(!is_string($valorMonto)||!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D',trim($valorMonto))||(float)$valorMonto<=0){http_response_code(422);exit('Ingresa un monto mayor que cero, con un máximo de dos decimales.');}
 $montoCorregido=trim($valorMonto);
}

// CAPTURAR CAMPOS
$payment_date = $_POST['payment_date'] ?? null;
$confirmation = $_POST['confirmation'] ?? null;
$banco_pago = $_POST['banco_pago'] ?? null;
$banco_pago = is_string($banco_pago)?strtoupper($banco_pago):''; // convertir a MAYÚSCULAS
$notes = $_POST['notes'] ?? null;

// Resolver el medio desde las tarjetas/cuentas del propietario, sin IDs fijos.
foreach(['payment_date','confirmation','banco_pago','notes','metodo_pago'] as $campo){if(isset($_POST[$campo])&&!is_string($_POST[$campo])){http_response_code(422);exit('Datos de pago inválidos.');}}
$payment_date=$payment_date?:date('Y-m-d');
$fechaValida=DateTimeImmutable::createFromFormat('!Y-m-d',$payment_date);
if(!$fechaValida||$fechaValida->format('Y-m-d')!==$payment_date||strlen((string)$confirmation)>100){http_response_code(422);exit('Revisa la fecha y la confirmación.');}
$metodoElegido=$_POST['metodo_pago']??'';$tarjeta_id=null;$metodo_pago=null;
$credito=filter_var($_POST['tarjeta_credito_id']??null,FILTER_VALIDATE_INT);
$debito=filter_var($_POST['tarjeta_debito_id']??null,FILTER_VALIDATE_INT);
// El selector actual envía origen e ID; no depende de nombres ni traducciones.
if(array_key_exists('medio_pago_id',$_POST)){
 $seleccionMedio=$_POST['medio_pago_id'];
 if(!is_string($seleccionMedio)||!preg_match('/^(cuenta|tarjeta):([1-9][0-9]*)$/D',$seleccionMedio,$partes)){http_response_code(422);exit('Selecciona una tarjeta o cuenta válida para registrar el pago.');}
 if($partes[1]==='cuenta'){
  $medio=$pdo->prepare("SELECT nombre FROM cuentas WHERE id=? AND usuario_id=? AND tipo='DEBITO'");$medio->execute([$partes[2],$usuario_id]);$nombreMedio=$medio->fetchColumn();
  if($nombreMedio!==false){$banco_pago=$nombreMedio;$metodo_pago='debito';}
 }else{
  $medio=$pdo->prepare("SELECT id,nombre_tarjeta,tipo FROM tarjetas WHERE id=? AND usuario_id=?");$medio->execute([$partes[2],$usuario_id]);$elegido=$medio->fetch(PDO::FETCH_ASSOC);
  if($elegido&&in_array($elegido['tipo'],['debito','credito'],true)){$tarjeta_id=(int)$elegido['id'];$banco_pago=$elegido['nombre_tarjeta'];$metodo_pago=$elegido['tipo'];}
 }
}elseif($metodoElegido==='Efectivo'){$metodo_pago='efectivo';$banco_pago='EFECTIVO';}
elseif($metodoElegido==='Tarjeta Crédito'){
 $medio=$pdo->prepare("SELECT id,nombre_tarjeta FROM tarjetas WHERE id=? AND usuario_id=? AND tipo='credito'");$medio->execute([$credito,$usuario_id]);$elegido=$medio->fetch(PDO::FETCH_ASSOC);
 if($elegido){$tarjeta_id=(int)$elegido['id'];$banco_pago=$elegido['nombre_tarjeta'];$metodo_pago='credito';}
}elseif(in_array($metodoElegido,['Tarjeta Débito','Transferencia','Zelle','ACH'],true)){
 $medio=$pdo->prepare('SELECT nombre FROM cuentas WHERE id=? AND usuario_id=?');$medio->execute([$debito,$usuario_id]);$elegido=$medio->fetchColumn();if($elegido!==false){$banco_pago=$elegido;$metodo_pago='debito';}
}elseif($metodoElegido===''){
 $medio=$pdo->prepare('SELECT nombre FROM cuentas WHERE usuario_id=? AND UPPER(nombre)=?');$medio->execute([$usuario_id,$banco_pago]);$cuenta=$medio->fetchColumn();
 if($cuenta!==false){$banco_pago=$cuenta;$metodo_pago='debito';}
 else{$medio=$pdo->prepare('SELECT id,nombre_tarjeta,tipo FROM tarjetas WHERE usuario_id=? AND UPPER(nombre_tarjeta)=?');$medio->execute([$usuario_id,$banco_pago]);$elegido=$medio->fetch(PDO::FETCH_ASSOC);if($elegido){$tarjeta_id=(int)$elegido['id'];$banco_pago=$elegido['nombre_tarjeta'];$metodo_pago=$elegido['tipo']==='debito'?'debito':'credito';}}
}
if($metodo_pago===null){http_response_code(422);exit('Selecciona una tarjeta o cuenta válida para registrar el pago.');}

require_once dirname(__DIR__,2).'/config/saldos_finanzas.php';
$consultaDeuda=$pdo->prepare('SELECT * FROM gastos WHERE id=? AND usuario_id=?');$consultaDeuda->execute([$id,$usuario_id]);$gastoDeuda=$consultaDeuda->fetch(PDO::FETCH_ASSOC);
if(!$gastoDeuda){http_response_code(404);exit('Gasto no encontrado.');}
$destinoSeleccion=null;
if(isset($_POST['tarjeta_destino_id'])&&$_POST['tarjeta_destino_id']!==''){$destinoSeleccion=filter_var($_POST['tarjeta_destino_id'],FILTER_VALIDATE_INT);if(!$destinoSeleccion||$destinoSeleccion<1){http_response_code(422);exit('Destino inválido.');}}
try{$tarjetaDestino=destinoPagoFinanzas($pdo,(int)$usuario_id,$gastoDeuda,$destinoSeleccion);}catch(InvalidArgumentException $e){http_response_code(422);exit($e->getMessage());}
if($tarjetaDestino!==null&&$tarjeta_id===$tarjetaDestino){http_response_code(422);exit('La tarjeta de origen y la tarjeta cuya deuda abonas deben ser diferentes.');}

// ACTUALIZAR REGISTRO (PAGO)
$sql = "
    UPDATE gastos
    SET 
        pagado = 'Si',
        amount = COALESCE(:amount, amount),
        payment_date = :payment_date,
        confirmation = :confirmation,
        banco_pago = :banco_pago,
        notes = :notes,
        metodo_pago = :metodo_pago,
        tarjeta_id = :tarjeta_id,
        tarjeta_destino_id = :tarjeta_destino_id
    WHERE id = :id
      AND usuario_id = :usuario_id
      AND (pagado IS NULL OR pagado <> 'Si')
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'amount' => $montoCorregido,
    'payment_date' => $payment_date,
    'confirmation' => $confirmation,
    'banco_pago'   => $banco_pago,
    'notes'        => $notes,
    'metodo_pago'  => $metodo_pago,
    'tarjeta_id'   => $tarjeta_id,
    'tarjeta_destino_id' => $tarjetaDestino,
    'id'           => $id,
    'usuario_id'   => $usuario_id
]);

// La condición del UPDATE evita duplicados incluso con solicitudes simultáneas.
if($stmt->rowCount()===0){
    $buscar=$pdo->prepare('SELECT pagado FROM gastos WHERE id=? AND usuario_id=?');
    $buscar->execute([$id,$usuario_id]);$existente=$buscar->fetch(PDO::FETCH_ASSOC);
    if(!$existente){http_response_code(404);exit('Gasto no encontrado.');}
    $_SESSION['aviso_pago']='Este gasto ya está pagado. No se registró otro pago ni se cambiaron sus datos.';
}else{
    $_SESSION['aviso_pago']='Pago registrado correctamente.';
}
// REDIRIGIR
header("Location: /gastos/ingresar.php");
exit;