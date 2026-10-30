<?php
// Ejecutar: php tests/pagos_programados.php. Solo tablas temporales.
ob_start(); session_start();
require dirname(__DIR__).'/config/db.php';
require_once dirname(__DIR__).'/config/pagos_programados.php';
function verificarPlan(bool $ok,string $mensaje):void { if(!$ok) throw new RuntimeException($mensaje); }
verificarPlan(fechaPagoProgramado(['due_date'=>'2026-01-31','frecuencia'=>'M'],2,2026)==='2026-02-28','Ajustar fin de mes');
verificarPlan(fechaPagoProgramado(['due_date'=>'2024-01-31','frecuencia'=>'M'],2,2024)==='2024-02-29','Febrero bisiesto');
verificarPlan(fechaPagoProgramado(['due_date'=>'2026-03-05','frecuencia'=>'A'],2,2026)===null,'Omitir anual fuera de mes');
verificarPlan(fechaPagoProgramado(['due_date'=>'2026-03-05','frecuencia'=>'A'],3,2026)==='2026-03-05','Fecha anual');
verificarPlan(fechaPagoProgramado(['due_date'=>'2026-01-15','frecuencia'=>'S'],7,2026)==='2026-07-15','Fecha semestral');
verificarPlan(fechaPagoProgramado(['due_date'=>'2026-01-15','frecuencia'=>'S'],6,2026)===null,'Omitir semestre incorrecto');
verificarPlan(fechaPagoProgramado(['due_date'=>'2026-02-30','frecuencia'=>'M'],2,2026)===null,'Rechazar fecha inválida');
$pdo->exec('CREATE TEMPORARY TABLE mantenimiento_pagos (id INT AUTO_INCREMENT PRIMARY KEY,usuario_id INT,descripcion VARCHAR(150),categoria VARCHAR(50),due_date DATE,frecuencia VARCHAR(10),amount DECIMAL(10,2),activo INT) ENGINE=InnoDB');
$pdo->exec("INSERT INTO mantenimiento_pagos VALUES (1,1,'Original','Seguro','2026-01-31','M',10,1),(2,2,'Ajeno','Seguro','2026-01-15','M',20,1)");
final class FinSolicitudPlan extends Error {}
function guardarPruebaPlan(PDO $pdo):void {
 $source=file_get_contents(dirname(__DIR__).'/public/mantenimiento_pagos_guardar.php');
 $source=preg_replace('/^<\?php\s*/','',$source);
 $source=str_replace(["require_once __DIR__.'/../config/db.php';","require_once __DIR__.'/../config/pagos_programados.php';"],'',$source);
 $source=preg_replace('/\bexit\s*(\([^;]*\))?\s*;/','throw new FinSolicitudPlan();',$source);
 try {eval($source);} catch(FinSolicitudPlan $e) {}
}
$_SERVER['REQUEST_METHOD']='POST'; $_SESSION=['usuario_id'=>1,'plantilla_token'=>'prueba'];
$_POST=['plantilla_token'=>'prueba','id'=>'1','descripcion'=>'Nombre corregido','due_date'=>'2026-01-31','frecuencia'=>'F','categoria'=>'Seguro','amount'=>'15.50','activo'=>'0'];
guardarPruebaPlan($pdo);
verificarPlan((int)$pdo->query('SELECT COUNT(*) FROM mantenimiento_pagos')->fetchColumn()===2,'Renombrar no duplica');
$row=$pdo->query('SELECT * FROM mantenimiento_pagos WHERE id=1')->fetch();
verificarPlan($row['descripcion']==='Nombre corregido' && (int)$row['activo']===0 && $row['frecuencia']==='F','Guardar por id, pausar y conservar frecuencia');
$_POST['id']='2'; $_POST['descripcion']='Intento ajeno'; guardarPruebaPlan($pdo);
verificarPlan($pdo->query('SELECT descripcion FROM mantenimiento_pagos WHERE id=2')->fetchColumn()==='Ajeno','Aislar usuarios');
$_POST['id']='0'; $_POST['descripcion']='Nombre corregido'; guardarPruebaPlan($pdo);
verificarPlan((int)$pdo->query('SELECT COUNT(*) FROM mantenimiento_pagos')->fetchColumn()===2,'Rechazar alta duplicada');
$_POST['descripcion']='Nuevo'; $_POST['activo']='1'; guardarPruebaPlan($pdo);
verificarPlan((int)$pdo->query('SELECT COUNT(*) FROM mantenimiento_pagos')->fetchColumn()===3,'Crear pago nuevo');
$_POST['id']='1'; $_POST['amount']='-10'; guardarPruebaPlan($pdo);
verificarPlan((float)$pdo->query('SELECT amount FROM mantenimiento_pagos WHERE id=1')->fetchColumn()===15.5,'Importe inválido no cambia el pago');
$_POST['amount']='10'; $_POST['plantilla_token']='incorrecto'; guardarPruebaPlan($pdo);
verificarPlan((float)$pdo->query('SELECT amount FROM mantenimiento_pagos WHERE id=1')->fetchColumn()===15.5,'Rechazar token inválido');
ob_end_clean(); echo "OK: fechas mensuales, bisiesto, anual, semestral, fecha inválida, edición por id, pausas, frecuencia fija, aislamiento de usuarios, alta sin duplicados, validación e integridad. Solo tablas temporales.\n";