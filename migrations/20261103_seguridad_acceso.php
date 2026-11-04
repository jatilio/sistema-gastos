<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/db.php';
$pdo->exec('CREATE TABLE IF NOT EXISTS intentos_acceso (clave CHAR(64) PRIMARY KEY,fallos INT UNSIGNED NOT NULL,inicio INT UNSIGNED NOT NULL,INDEX(inicio)) ENGINE=InnoDB');
echo "Protección de intentos de acceso preparada.\n";
