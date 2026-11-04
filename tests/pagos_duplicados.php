<?php
ob_start();ini_set('session.save_path',sys_get_temp_dir());session_id('pagos-test-'.bin2hex(random_bytes(8)));session_start();
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/saldos_finanzas.php';
$pdo->exec('CREATE TEMPORARY TABLE cuentas(id INT PRIMARY KEY,usuario_id INT,nombre VARCHAR(100),tipo VARCHAR(20))');
$pdo->exec("INSERT INTO cuentas VALUES(101,3,'MIDFLORIDA','DEBITO'),(102,9,'MIDFLORIDA','DEBITO')");
$pdo->exec('CREATE TEMPORARY TABLE tarjetas(id INT PRIMARY KEY,usuario_id INT,nombre_tarjeta VARCHAR(100),tipo VARCHAR(20),banco VARCHAR(100))');
$pdo->exec("INSERT INTO tarjetas VALUES(201,3,'Tarjeta de prueba','credito','Banco'),(202,9,'Tarjeta de prueba','credito','Banco')");
$pdo->exec('CREATE TEMPORARY TABLE gastos (id INT PRIMARY KEY,usuario_id INT,descripcion VARCHAR(150),amount DECIMAL(10,2),pagado VARCHAR(5),payment_date DATE,confirmation VARCHAR(100),banco_pago VARCHAR(100),notes TEXT,metodo_pago VARCHAR(30),tarjeta_id INT,tarjeta_destino_id INT,validado INT DEFAULT 0) ENGINE=InnoDB');
$pdo->exec("INSERT INTO gastos (id,usuario_id,descripcion,amount,pagado) VALUES (1,3,'Prueba',10,'No'),(2,9,'Ajeno',10,'No')");
class FinPruebaPago extends Error{}
function enviarPagoPrueba(PDO $pdo,array $datos):string{
 $_SESSION=['usuario_id'=>3];$_POST=$datos;$_SERVER['REQUEST_METHOD']='POST';
 $codigo=file_get_contents(__DIR__.'/../public/gastos/ingresar_guardar.php');
 $codigo=preg_replace('/^<\?php\s*/','',$codigo);
 $codigo=preg_replace("~require_once __DIR__\s*\.\s*['\"][^'\"]*seguridad_web\.php['\"];~",'', $codigo);
 $codigo=str_replace('include __DIR__ . "/../../config/db.php";','',$codigo);
 $codigo=str_replace("require_once dirname(__DIR__,2).'/config/saldos_finanzas.php';",'',$codigo);
 $codigo=preg_replace('/\bexit\s*(\([^;]*\))?\s*;/','throw new FinPruebaPago();',$codigo);
 http_response_code(200);try{eval($codigo);}catch(FinPruebaPago $e){}
 return $_SESSION['aviso_pago']??'';
}
$datos=['id'=>1,'amount'=>'12.75','payment_date'=>'2026-10-29','confirmation'=>'PRIMERA','banco_pago'=>'MIDFLORIDA','notes'=>'Original'];
$aviso=enviarPagoPrueba($pdo,$datos);if(!str_contains($aviso,'correctamente'))throw new RuntimeException('Primer pago no guardado');
$primero=$pdo->query('SELECT * FROM gastos WHERE id=1')->fetch();
if($primero['amount']!=='12.75')throw new RuntimeException('No se guardó el monto corregido');
$datos['amount']='99.00';$datos['confirmation']='SEGUNDA';$datos['payment_date']='2026-10-30';$datos['notes']='No debe reemplazar';
$aviso=enviarPagoPrueba($pdo,$datos);$segundo=$pdo->query('SELECT * FROM gastos WHERE id=1')->fetch();
if($primero!==$segundo||!str_contains($aviso,'ya está pagado'))throw new RuntimeException('Segundo pago modificó datos');
$pdo->exec("INSERT INTO gastos (id,usuario_id,descripcion,amount,pagado) VALUES (3,3,'Efectivo',10,'No'),(4,3,'Inválido',10,'No')");
enviarPagoPrueba($pdo,['id'=>3,'metodo_pago'=>'Efectivo','payment_date'=>'2026-10-29']);
if($pdo->query('SELECT metodo_pago FROM gastos WHERE id=3')->fetchColumn()!=='efectivo')throw new RuntimeException('Efectivo ignorado');
enviarPagoPrueba($pdo,['id'=>4,'metodo_pago'=>'Tarjeta Crédito','tarjeta_credito_id'=>999999,'payment_date'=>'2026-10-29']);
if(http_response_code()!==422||$pdo->query('SELECT pagado FROM gastos WHERE id=4')->fetchColumn()!=='No')throw new RuntimeException('Medio inválido aceptado');
foreach(['-1','0','abc','1.234','1e3','100000000',[]] as $invalido){
 enviarPagoPrueba($pdo,['id'=>4,'metodo_pago'=>'Efectivo','payment_date'=>'2026-10-29','amount'=>$invalido]);
 $sinCambios=$pdo->query('SELECT amount,pagado FROM gastos WHERE id=4')->fetch();
 if(http_response_code()!==422||$sinCambios['amount']!=='10.00'||$sinCambios['pagado']!=='No')throw new RuntimeException('Monto inválido aceptado o gasto modificado');
}
enviarPagoPrueba($pdo,['id'=>4,'metodo_pago'=>'Efectivo','payment_date'=>'2026-02-30']);
if(http_response_code()!==422)throw new RuntimeException('Fecha inválida aceptada');
$datos['id']=2;enviarPagoPrueba($pdo,$datos);
if(http_response_code()!==404||$pdo->query('SELECT pagado FROM gastos WHERE id=2')->fetchColumn()!=='No')throw new RuntimeException('Pago ajeno permitido');
$pdo->exec("INSERT INTO gastos(id,usuario_id,descripcion,amount,pagado) VALUES(5,3,'Cuenta por ID',10,'No'),(6,3,'Tarjeta por ID',10,'No'),(7,3,'Rechazo',10,'No')");
foreach([['id'=>5,'medio_pago_id'=>'cuenta:101'],['id'=>6,'medio_pago_id'=>'tarjeta:201']] as $pago){
 enviarPagoPrueba($pdo,$pago+['payment_date'=>'2026-10-29','amount'=>'13.50']);
 $guardado=$pdo->query('SELECT * FROM gastos WHERE id='.(int)$pago['id'])->fetch();
 if($guardado['pagado']!=='Si'||$guardado['amount']!=='13.50'||($pago['id']===5?$guardado['banco_pago']!=='MIDFLORIDA':(int)$guardado['tarjeta_id']!==201))throw new RuntimeException('Selección por ID no guardada');
}
foreach(['cuenta:102','tarjeta:202','tarjeta:999999','',[]] as $invalido){
 enviarPagoPrueba($pdo,['id'=>7,'medio_pago_id'=>$invalido,'amount'=>'15.00']);
 if(http_response_code()!==422||$pdo->query('SELECT pagado FROM gastos WHERE id=7')->fetchColumn()!=='No')throw new RuntimeException('Medio ajeno o vacío aceptado');
}
enviarPagoPrueba($pdo,['id'=>1,'validado'=>1]);if($pdo->query('SELECT validado FROM gastos WHERE id=1')->fetchColumn()!=1)throw new RuntimeException('Validación bloqueada');
session_destroy();ob_end_clean();echo "OK: cuentas y tarjetas por ID, rechazo de medios ajenos/vacíos, monto corregido, rechazo de montos inválidos, formularios anteriores compatibles, segundo pago bloqueado sin sobrescribir monto, fecha ni confirmación, propietario y validación. Solo tabla temporal.\n";
