<?php
// Solo tablas temporales; las cuentas y contraseñas existentes no cambian.
ob_start();ini_set('session.save_path',sys_get_temp_dir());session_id('acceso-prueba-'.bin2hex(random_bytes(8)));session_start();
require __DIR__.'/../config/db.php';require __DIR__.'/../config/control_acceso.php';
$pdo->exec('CREATE TEMPORARY TABLE intentos_acceso(clave CHAR(64) PRIMARY KEY,fallos INT,inicio INT)');
$pdo->exec('CREATE TEMPORARY TABLE usuarios(id INT PRIMARY KEY,usuario VARCHAR(50),nombre VARCHAR(50),password VARCHAR(255))');
$pdo->exec('CREATE TEMPORARY TABLE usuarios_datos_compartidos(usuario_id INT,propietario_id INT)');
$q=$pdo->prepare('INSERT INTO usuarios VALUES(2147483000,?,?,?)');$q->execute(['prueba_seguridad','Cuenta de prueba',password_hash('ClaveTemporalSoloPrueba!',PASSWORD_DEFAULT)]);
$claves=clavesIntentoFinanzas('127.0.0.1','prueba_seguridad');$ahora=time();
for($i=0;$i<5;$i++)registrarIntentoFallidoFinanzas($pdo,$claves,$ahora);
if(!accesoBloqueadoFinanzas($pdo,$claves,$ahora)||accesoBloqueadoFinanzas($pdo,$claves,$ahora+901))throw new RuntimeException('Fallo de límite o vencimiento');
$pdo->exec('DELETE FROM intentos_acceso');
$caso=$argv[1]??'correcto';if($caso==='bloqueado')for($i=0;$i<5;$i++)registrarIntentoFallidoFinanzas($pdo,$claves,$ahora);
$_SESSION=[];$_SERVER['REQUEST_METHOD']='POST';$_SERVER['REMOTE_ADDR']='127.0.0.1';$_POST=['usuario'=>'prueba_seguridad','password'=>$caso==='incorrecto'?'ClaveIncorrecta':'ClaveTemporalSoloPrueba!'];
$anterior=session_id();
register_shutdown_function(function()use($caso,$anterior,$pdo){
 $ok=$caso==='correcto'?($_SESSION['usuario_autenticado_id']??0)===2147483000&&session_id()!==$anterior&&strlen($_SESSION['csrf_finanzas']??'')===64:empty($_SESSION['usuario_id'])&&isset($_SESSION['error']);
 if($caso==='incorrecto')$ok=$ok&&(int)$pdo->query('SELECT MAX(fallos) FROM intentos_acceso')->fetchColumn()===1;
 session_destroy();ob_end_clean();if(!$ok){fwrite(STDERR,'FALLO acceso '.$caso.PHP_EOL);exit(1);}echo 'OK acceso: '.$caso.', limitación y vencimiento. Solo tablas temporales.'.PHP_EOL;
});
$codigo=file_get_contents(__DIR__.'/../public/auth/procesar_login.php');$codigo=preg_replace('/^<\?php\s*/','',$codigo);$codigo=preg_replace('/require_once[^;]+;/','',$codigo);eval($codigo);
