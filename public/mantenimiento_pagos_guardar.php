<?php
if(session_status()===PHP_SESSION_NONE) session_start();
if(empty($_SESSION['usuario_id'])) { header('Location: /auth/login.php'); exit; }
if(($_SERVER['REQUEST_METHOD']??'')!=='POST') { http_response_code(405); header('Allow: POST'); exit; }
$token=$_POST['plantilla_token']??null;
if(!is_string($token) || !isset($_SESSION['plantilla_token']) || !hash_equals($_SESSION['plantilla_token'],$token)) { http_response_code(403); exit('Formulario inválido. Vuelve a abrir Pagos programados.'); }
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/pagos_programados.php';
$usuario=(int)$_SESSION['usuario_id'];
$locked=false;
try {
    foreach(['descripcion','due_date','frecuencia','categoria','amount','activo'] as $campo) {
        if(!isset($_POST[$campo]) || !is_string($_POST[$campo])) throw new InvalidArgumentException('Completa todos los campos del pago.');
    }
    $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
    $desc=trim($_POST['descripcion']);
    $fecha=$_POST['due_date'];
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);
    $freq=$_POST['frecuencia'];
    $amount=trim($_POST['amount']);
    $activo=$_POST['activo'];
    $categoria=trim($_POST['categoria']);
    if($id===false || $id===null || $id<0 || $desc==='' || mb_strlen($desc)>150 || !$date || $date->format('Y-m-d')!==$fecha || !in_array($freq,['M','S','A','F'],true) || !preg_match('/^\d{1,8}(\.\d{1,2})?$/D',$amount) || !in_array($activo,['0','1'],true)) throw new InvalidArgumentException('Revisa la descripción, fecha, frecuencia e importe.');
    if(!in_array($categoria,categoriasPagosProgramados($pdo,$usuario),true)) throw new InvalidArgumentException('Selecciona una categoría de la lista.');
    $lock=$pdo->prepare('SELECT GET_LOCK(?, 10)');
    $lock->execute(['plantillas_pagos_'.$usuario]);
    if((int)$lock->fetchColumn()!==1) throw new RuntimeException('No se pudo guardar el pago. Intenta nuevamente.');
    $locked=true;
    $pdo->beginTransaction();
    if($id>0) {
        $check=$pdo->prepare('SELECT id FROM mantenimiento_pagos WHERE id=? AND usuario_id=? FOR UPDATE');
        $check->execute([$id,$usuario]);
        if(!$check->fetchColumn()) throw new InvalidArgumentException('No se encontró el pago.');
        $stmt=$pdo->prepare('UPDATE mantenimiento_pagos SET descripcion=?, due_date=?, frecuencia=?, categoria=?, amount=?, activo=? WHERE id=? AND usuario_id=?');
        $stmt->execute([$desc,$fecha,$freq,$categoria,$amount,$activo,$id,$usuario]);
    } else {
        $check=$pdo->prepare('SELECT id FROM mantenimiento_pagos WHERE usuario_id=? AND descripcion=? LIMIT 1');
        $check->execute([$usuario,$desc]);
        if($check->fetchColumn()) throw new InvalidArgumentException('Ya existe un pago con esa descripción. Edítalo en la lista.');
        $stmt=$pdo->prepare('INSERT INTO mantenimiento_pagos (usuario_id,descripcion,due_date,frecuencia,categoria,amount,activo) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$usuario,$desc,$fecha,$freq,$categoria,$amount,$activo]);
    }
    $pdo->commit();
    $_SESSION['mensaje_exito']='Pago guardado correctamente. Los gastos ya generados no se modificaron.';
} catch(Throwable $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    if($e instanceof InvalidArgumentException) $_SESSION['mensaje_error']=$e->getMessage();
    else { error_log('Guardar plantilla: '.$e->getMessage()); $_SESSION['mensaje_error']='No se pudo guardar el pago. Intenta nuevamente.'; }
} finally {
    if($locked) { $release=$pdo->prepare('SELECT RELEASE_LOCK(?)'); $release->execute(['plantillas_pagos_'.$usuario]); }
}
header('Location: /index.php?menu=mantenimiento_pagos',true,303);
exit;