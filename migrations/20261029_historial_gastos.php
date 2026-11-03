<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/db.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS historial_gastos (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, gasto_id INT NOT NULL, propietario_id INT NOT NULL, actor_id INT NULL, actor_nombre VARCHAR(100) NOT NULL, accion VARCHAR(20) NOT NULL, antes JSON NULL, despues JSON NULL, creado_en DATETIME(6) NOT NULL, INDEX (propietario_id,creado_en), INDEX (gasto_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$campos=['id','usuario_id','descripcion','categoria','tipo','amount','due_date','payment_date','confirmation','pagado','banco_pago','metodo_pago','tarjeta_id','mantenimiento_id','notes','validado','banco'];
$columnasHistorial=$pdo->query('SHOW COLUMNS FROM gastos')->fetchAll(PDO::FETCH_COLUMN);if(in_array('tarjeta_destino_id',$columnasHistorial,true))$campos[]='tarjeta_destino_id';
$json=function(string $lado)use($campos){return 'JSON_OBJECT('.implode(',',array_map(fn($c)=>"'$c', $lado.`$c`",$campos)).')';};
$antes=$json('OLD');$despues=$json('NEW');
foreach(['insert','update','delete'] as $evento){
 $nombre='finanzas_historial_gastos_'.$evento;
 $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?');$q->execute([$nombre]);if($q->fetchColumn())continue;
 $lado=$evento==='delete'?'OLD':'NEW';
 $accion=$evento==='insert'?"'REGISTRO'":($evento==='delete'?"'ELIMINACIÓN'":"IF(NEW.pagado='Si' AND NOT(OLD.pagado <=> 'Si'),'PAGO','MODIFICACIÓN')");
 $insercion="INSERT INTO historial_gastos (gasto_id,propietario_id,actor_id,actor_nombre,accion,antes,despues,creado_en) VALUES ($lado.id,$lado.usuario_id,IF(@finanzas_propietario=$lado.usuario_id,@finanzas_actor,NULL),IF(@finanzas_propietario=$lado.usuario_id AND @finanzas_actor IS NOT NULL,COALESCE(@finanzas_actor_nombre,'Usuario'),'Sistema / sin sesión'),$accion,".($evento==='insert'?'NULL':$antes).','.($evento==='delete'?'NULL':$despues).",UTC_TIMESTAMP(6));";
 $cuerpo=$evento==='update'?"IF NOT(CAST($antes AS CHAR) <=> CAST($despues AS CHAR)) THEN $insercion END IF;":$insercion;
 $pdo->exec('CREATE TRIGGER '.$nombre.' AFTER '.strtoupper($evento).' ON gastos FOR EACH ROW BEGIN '.$cuerpo.' END');
}
echo "Historial de gastos activado. No se modificaron los registros existentes.\n";
