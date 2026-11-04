<?php
require_once __DIR__.'/../../config/seguridad_web.php';require_once __DIR__.'/../../config/db.php';
if(empty($_SESSION['usuario_id'])){http_response_code(401);exit;}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit;}
$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$tipo=$_POST['tipo']??'';$estado=$_POST['estado']??'';
$banco=is_string($_POST['banco']??null)?trim($_POST['banco']):'';$nombre=is_string($_POST['nombre_tarjeta']??null)?trim($_POST['nombre_tarjeta']):'';
$limite=$_POST['limite']??'0';$saldo=$_POST['saldo_actual']??'0';
if(!$id||!in_array($tipo,['credito','debito'],true)||!in_array($estado,['activa','inactiva'],true)||$banco===''||$nombre===''||mb_strlen($banco)>100||mb_strlen($nombre)>100||!is_scalar($limite)||!is_numeric($limite)||!is_scalar($saldo)||!is_numeric($saldo)||abs((float)$saldo)>99999999.99||(float)$limite<0||(float)$limite>99999999.99){http_response_code(422);exit('Revisa los datos de la tarjeta.');}
$fechas=[];foreach(['dia_corte','dia_pago'] as $campo){$valor=$_POST[$campo]??'';if(!is_string($valor)){http_response_code(422);exit('Fecha inválida.');}if($valor!==''){$fecha=DateTimeImmutable::createFromFormat('!Y-m-d',$valor);if(!$fecha||$fecha->format('Y-m-d')!==$valor){http_response_code(422);exit('Fecha inválida.');}}$fechas[$campo]=$valor?:null;}
$marca=is_string($_POST['marca']??null)?$_POST['marca']:'';$ultimos=is_string($_POST['ultimos_4']??null)?$_POST['ultimos_4']:'';
if(mb_strlen($marca)>20||($ultimos!==''&&!preg_match('/^[0-9]{4}$/D',$ultimos))){http_response_code(422);exit('Revisa la marca y los últimos cuatro dígitos.');}
$q=$pdo->prepare('SELECT id FROM tarjetas WHERE id=? AND usuario_id=?');$q->execute([$id,$_SESSION['usuario_id']]);if(!$q->fetchColumn()){http_response_code(404);exit('Tarjeta no encontrada.');}
$q=$pdo->prepare('UPDATE tarjetas SET banco=?,nombre_tarjeta=?,tipo=?,limite=?,saldo_actual=?,dia_corte=?,dia_pago=?,marca=?,ultimos_4=?,estado=? WHERE id=? AND usuario_id=?');
$q->execute([$banco,$nombre,$tipo,$limite,$saldo,$fechas['dia_corte'],$fechas['dia_pago'],$marca,$ultimos?:null,$estado,$id,$_SESSION['usuario_id']]);header('Location: /index.php?menu=tarjetas&msg=actualizada');exit;
