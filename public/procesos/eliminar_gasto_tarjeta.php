<?php
require_once __DIR__ . '/../../config/seguridad_web.php';

if(session_status()!==PHP_SESSION_ACTIVE)session_start();
require_once __DIR__.'/../../config/db.php';require_once __DIR__.'/../../config/seguridad_formularios.php';
$usuario=exigirSesionFinanzas();validarPostFinanzas();
$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);if(!$id||$id<1){http_response_code(422);exit('Gasto inválido.');}
$q=$pdo->prepare('DELETE FROM gastos WHERE id=? AND usuario_id=? AND tarjeta_id IS NOT NULL');$q->execute([$id,$usuario]);
if(!$q->rowCount()){http_response_code(404);exit('Gasto no encontrado.');}
header('Location: /index.php?menu=tarjetas_gastos&msg=eliminado');exit;
