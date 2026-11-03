<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/db.php';
require __DIR__.'/../scripts/respaldar_formulas_saldos.php';
$originales=['trg_descuento_tarjeta_pago','trg_tarjeta_pago_insert','trg_gasto_credito_after_insert','trg_update_tarjeta_pago','trg_tarjeta_pago_update','trg_gasto_after_delete'];
$nuevos=['finanzas_saldo_gasto_insert','finanzas_saldo_gasto_update','finanzas_saldo_gasto_delete'];
$sentencias=[];
foreach(array_merge($originales,$nuevos) as $nombre){$q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?');$q->execute([$nombre]);if($q->fetchColumn()){$fila=$pdo->query('SHOW CREATE TRIGGER `'.$nombre.'`')->fetch(PDO::FETCH_ASSOC);$sentencias[$nombre]=$fila['SQL Original Statement'];}}
$columnas=$pdo->query('SHOW COLUMNS FROM gastos')->fetchAll(PDO::FETCH_COLUMN);
if(!in_array('tarjeta_destino_id',$columnas,true))$pdo->exec('ALTER TABLE gastos ADD COLUMN tarjeta_destino_id INT NULL DEFAULT NULL');
$antes=$pdo->query('SELECT id,saldo_actual,disponible FROM tarjetas ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
// Efecto neto de cada registro: compra confirmada en crédito suma deuda; abono confirmado la resta.
$efecto=function($lado){return "(CASE WHEN t.usuario_id=$lado.usuario_id AND t.id=$lado.tarjeta_id AND $lado.metodo_pago='credito' AND $lado.pagado='Si' THEN $lado.amount ELSE 0 END - CASE WHEN t.usuario_id=$lado.usuario_id AND t.id=$lado.tarjeta_destino_id AND $lado.pagado='Si' THEN $lado.amount ELSE 0 END)";};
$destinos=function($lado){return "(t.usuario_id=$lado.usuario_id AND (t.id=$lado.tarjeta_id OR t.id=$lado.tarjeta_destino_id))";};
$crear=[
 'insert'=>"UPDATE tarjetas t SET t.saldo_actual=COALESCE(t.saldo_actual,0)+".$efecto('NEW')." WHERE t.tipo='credito' AND ".$destinos('NEW').';',
 'update'=>"UPDATE tarjetas t SET t.saldo_actual=COALESCE(t.saldo_actual,0)-".$efecto('OLD').'+'.$efecto('NEW')." WHERE t.tipo='credito' AND (".$destinos('OLD').' OR '.$destinos('NEW').');',
 'delete'=>"UPDATE tarjetas t SET t.saldo_actual=COALESCE(t.saldo_actual,0)-".$efecto('OLD')." WHERE t.tipo='credito' AND ".$destinos('OLD').';'
];
try{
 foreach(array_merge($originales,$nuevos) as $nombre)$pdo->exec('DROP TRIGGER IF EXISTS `'.$nombre.'`');
 foreach($crear as $evento=>$cuerpo)$pdo->exec('CREATE TRIGGER finanzas_saldo_gasto_'.$evento.' AFTER '.strtoupper($evento).' ON gastos FOR EACH ROW BEGIN '.$cuerpo.' END');
 $despues=$pdo->query('SELECT id,saldo_actual,disponible FROM tarjetas ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
 if($antes!==$despues)throw new RuntimeException('Cambió un saldo durante la migración.');
}catch(Throwable $e){
 foreach(array_merge($originales,$nuevos) as $nombre)$pdo->exec('DROP TRIGGER IF EXISTS `'.$nombre.'`');
 foreach($sentencias as $sentencia)$pdo->exec($sentencia);
 throw $e;
}
echo "Triggers reemplazados. Los saldos actuales se conservaron exactamente; historial de movimientos intacto.\n";
