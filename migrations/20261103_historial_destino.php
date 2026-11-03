<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/db.php';
foreach(['insert','update','delete'] as $evento){
 $nombre='finanzas_historial_gastos_'.$evento;
 $q=$pdo->query('SHOW CREATE TRIGGER `'.$nombre.'`');$original=$q->fetch(PDO::FETCH_ASSOC)['SQL Original Statement'];
 if(str_contains($original,"'tarjeta_destino_id'"))continue;
 $nueva=$original;
 foreach(['OLD','NEW'] as $lado)$nueva=str_replace("'tarjeta_id', $lado.`tarjeta_id`","'tarjeta_id', $lado.`tarjeta_id`,'tarjeta_destino_id', $lado.`tarjeta_destino_id`",$nueva);
 if($original===$nueva)throw new RuntimeException('No se reconoció la definición del historial.');
 $pdo->exec('DROP TRIGGER `'.$nombre.'`');try{$pdo->exec($nueva);}catch(Throwable $e){$pdo->exec($original);throw $e;}
}
echo "El historial también registra la tarjeta abonada.\n";
