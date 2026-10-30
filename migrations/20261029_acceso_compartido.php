<?php
// Ejecutar una vez tras actualizar el código: php migrations/20261029_acceso_compartido.php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/db.php';
$columnas=$pdo->query('SHOW COLUMNS FROM usuarios')->fetchAll(PDO::FETCH_COLUMN);
if(!in_array('nombre',$columnas,true))$pdo->exec('ALTER TABLE usuarios ADD COLUMN nombre VARCHAR(100) NULL');
$pdo->exec('CREATE TABLE IF NOT EXISTS usuarios_datos_compartidos (usuario_id INT PRIMARY KEY, propietario_id INT NOT NULL, creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (usuario_id) REFERENCES usuarios(id), FOREIGN KEY (propietario_id) REFERENCES usuarios(id)) ENGINE=InnoDB');
echo "Estructura de nombres y acceso compartido preparada. Las cuentas y autorizaciones se configuran localmente.\n";
