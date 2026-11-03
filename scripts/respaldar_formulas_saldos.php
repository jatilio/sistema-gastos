<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/db.php';
$carpeta=__DIR__.'/../backups';if(!is_dir($carpeta))mkdir($carpeta,0700,true);
$triggers=$pdo->query('SHOW TRIGGERS')->fetchAll(PDO::FETCH_ASSOC);
$vistas=$pdo->query('SELECT TABLE_NAME,VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_ASSOC);
$tarjetas=$pdo->query('SELECT id,usuario_id,limite,saldo_actual,disponible FROM tarjetas')->fetchAll(PDO::FETCH_ASSOC);
$cuentas=$pdo->query('SELECT id,usuario_id,saldo FROM cuentas')->fetchAll(PDO::FETCH_ASSOC);
$archivo=$carpeta.'/formulas_saldos_'.gmdate('Ymd_His').'.json';
file_put_contents($archivo,json_encode(['creado_utc'=>gmdate('c'),'triggers'=>$triggers,'vistas'=>$vistas,'tarjetas'=>$tarjetas,'cuentas'=>$cuentas],JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo 'Copia local creada: '.basename($archivo).'. No incluida en Git.'.PHP_EOL;
