<?php
require_once __DIR__.'/../../config/seguridad_web.php';require_once __DIR__.'/../../config/db.php';
if(empty($_SESSION['usuario_id'])){http_response_code(401);exit;}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);header('Allow: POST');exit;}
$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);if(!$id||$id<1){http_response_code(422);exit('Tarjeta inválida.');}
$pdo->beginTransaction();
$q=$pdo->prepare('SELECT saldo_actual FROM tarjetas WHERE id=? AND usuario_id=? FOR UPDATE');$q->execute([$id,$_SESSION['usuario_id']]);$tarjeta=$q->fetch(PDO::FETCH_ASSOC);
if(!$tarjeta){$pdo->rollBack();http_response_code(404);exit('Tarjeta no encontrada.');}
$q=$pdo->prepare('SELECT id FROM gastos WHERE tarjeta_id=? OR tarjeta_destino_id=? LIMIT 1');$q->execute([$id,$id]);
if($q->fetchColumn()||(float)$tarjeta['saldo_actual']!=0){$pdo->rollBack();http_response_code(409);exit('Esta tarjeta tiene saldo o movimientos asociados. Puedes marcarla como inactiva para conservar su historial.');}
$q=$pdo->prepare('DELETE FROM tarjetas WHERE id=? AND usuario_id=?');$q->execute([$id,$_SESSION['usuario_id']]);$pdo->commit();header('Location: /index.php?menu=tarjetas&msg=eliminada');exit;
