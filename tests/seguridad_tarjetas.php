<?php
ob_start();ini_set('session.save_path',sys_get_temp_dir());session_id('seguridad-'.bin2hex(random_bytes(6)));session_start();
require __DIR__.'/../config/db.php';require __DIR__.'/../config/seguridad_formularios.php';
$pdo->exec('CREATE TEMPORARY TABLE tarjetas (id INT PRIMARY KEY,usuario_id INT,tipo VARCHAR(20))');$pdo->exec("INSERT INTO tarjetas VALUES(1,3,'credito')");
$pdo->exec('CREATE TEMPORARY TABLE gastos (id INT PRIMARY KEY,usuario_id INT,tarjeta_id INT,descripcion VARCHAR(150),amount DECIMAL(10,2),due_date DATE,pagado VARCHAR(5),metodo_pago VARCHAR(20),categoria VARCHAR(50),tipo VARCHAR(20))');
$pdo->exec("INSERT INTO gastos(id,usuario_id,tarjeta_id,descripcion) VALUES(1,3,1,'Propio'),(2,9,2,'Ajeno')");
$caso=$argv[1]??'ajeno';$_SESSION=['usuario_id'=>3,'formulario_token'=>str_repeat('a',64)];$_SERVER['REQUEST_METHOD']='POST';$_POST=['id'=>2,'formulario_token'=>str_repeat('a',64)];$esperado=404;$archivo='eliminar_gasto_tarjeta.php';
if($caso==='get'){$_SERVER['REQUEST_METHOD']='GET';$esperado=405;}
if($caso==='token'){$_POST['formulario_token']='incorrecto';$esperado=403;}
if(str_starts_with($caso,'editar')){$archivo='editar_gasto_tarjeta.php';$_POST+=['gasto_id'=>$caso==='editar-propio'?1:2,'tarjeta_id'=>1,'descripcion'=>'Actualizado','monto'=>'12.50','fecha'=>'2026-01-02','pagado'=>'No'];$esperado=$caso==='editar-propio'?302:404;}
register_shutdown_function(function()use($pdo,$caso,$esperado){$codigo=http_response_code();$ajeno=$pdo->query('SELECT descripcion FROM gastos WHERE id=2')->fetchColumn();$propio=$pdo->query('SELECT descripcion FROM gastos WHERE id=1')->fetchColumn();session_destroy();ob_end_clean();if($codigo!==$esperado||$ajeno!=='Ajeno'||($caso==='editar-propio'?$propio!=='Actualizado':$propio!=='Propio')){fwrite(STDERR,'FALLO '.$caso.' HTTP '.$codigo.PHP_EOL);exit(1);}echo 'OK permisos tarjetas: '.$caso.PHP_EOL;});
$fuente=file_get_contents(__DIR__.'/../public/procesos/'.$archivo);$fuente=preg_replace('/^<\?php\s*/','',$fuente);
 $fuente=preg_replace("~require_once __DIR__\s*\.\s*['\"][^'\"]*seguridad_web\.php['\"];~",'', $fuente);$fuente=preg_replace('/require_once[^;]+;/','',$fuente);eval($fuente);
