<?php
require_once __DIR__.'/../../config/seguridad_web.php';
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);header('Allow: POST');exit;}
require_once __DIR__.'/../../config/db.php';require_once __DIR__.'/../../config/control_acceso.php';
$usuario=is_string($_POST['usuario']??null)?trim($_POST['usuario']):'';$password=is_string($_POST['password']??null)?$_POST['password']:'';
if($usuario===''||mb_strlen($usuario)>50||strlen($password)>4096){$_SESSION['error']='Usuario o contraseña incorrectos.';header('Location: /auth/login.php');exit;}
$claves=clavesIntentoFinanzas($_SERVER['REMOTE_ADDR']??'local',$usuario);$ahora=time();
if(accesoBloqueadoFinanzas($pdo,$claves,$ahora)){$_SESSION['error']='Demasiados intentos de acceso. Inténtalo de nuevo en 15 minutos.';header('Location: /auth/login.php');exit;}
$q=$pdo->prepare('SELECT id,usuario,password FROM usuarios WHERE usuario=?');$q->execute([$usuario]);$cuenta=$q->fetch(PDO::FETCH_ASSOC);
$hash=$cuenta['password']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
if(!$cuenta||!password_verify($password,$hash)){if(!$cuenta)password_verify($password,$hash);registrarIntentoFallidoFinanzas($pdo,$claves,$ahora);$_SESSION['error']='Usuario o contraseña incorrectos.';header('Location: /auth/login.php');exit;}
$_SESSION=[];session_regenerate_id(true);$_SESSION['usuario_autenticado_id']=(int)$cuenta['id'];$_SESSION['usuario_id']=(int)$cuenta['id'];$_SESSION['sesion_creada']=$ahora;$_SESSION['ultima_actividad']=$ahora;$_SESSION['csrf_finanzas']=bin2hex(random_bytes(32));
resolverDatosFinanzas($pdo);$q=$pdo->prepare('DELETE FROM intentos_acceso WHERE clave=?');$q->execute([$claves[1]]);
header('Location: /index.php?menu=dashboard');exit;
