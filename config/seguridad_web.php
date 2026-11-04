<?php
function finanzasInsertarProteccion(string $html): string {
 if(!isset($_SESSION['csrf_finanzas']))return $html;
 $token=htmlspecialchars($_SESSION['csrf_finanzas'],ENT_QUOTES,'UTF-8');
 return preg_replace_callback('/<form\b[^>]*>/i',function($m)use($token){
  if(!preg_match('/\bmethod\s*=\s*[\x22\x27]?post\b/i',$m[0]))return $m[0];
  if(preg_match('/\baction\s*=\s*([\x22\x27])(.*?)\1/is',$m[0],$a)&&preg_match('~^(?:https?:)?//~i',$a[2]))return $m[0];
  return $m[0].'<input type="hidden" name="csrf_finanzas" value="'.$token.'">';
 },$html);
}
function finanzasJson($valor, int $flags=0, int $depth=512): string {
 return json_encode($valor,$flags|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR,$depth);
}
if(PHP_SAPI==='cli')return;
ini_set('display_errors','0');ini_set('display_startup_errors','0');ini_set('log_errors','1');
$host=strtolower(parse_url('http://'.($_SERVER['HTTP_HOST']??'localhost'),PHP_URL_HOST)??'');
$https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');
$local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)&&(in_array($host,['localhost','127.0.0.1','[::1]'],true)||str_ends_with($host,'.test'));
if(!$local&&!$https){http_response_code(403);exit('Accede al sitio mediante HTTPS.');}
header("Content-Security-Policy: base-uri 'self'; object-src 'none'; form-action 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');header('Referrer-Policy: same-origin');header('Cache-Control: no-store, private');
if($https)header('Strict-Transport-Security: max-age=31536000');
if(session_status()!==PHP_SESSION_ACTIVE){
 ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');
 session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);session_start();
}
// Normalizar también las rutas codificadas y PATH_INFO para aplicar las mismas reglas.
$ruta=rawurldecode(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)??'');
$segmentos=[];foreach(explode('/',str_replace('\\','/',$ruta)) as $parte){if($parte===''||$parte==='.')continue;if($parte==='..'){array_pop($segmentos);continue;}$segmentos[]=$parte;}
$ruta=preg_replace('~\.php/.*$~i','.php','/'.implode('/',$segmentos));
$publica=in_array($ruta,['/auth/login.php','/auth/procesar_login.php'],true);
$ahora=time();
if(!empty($_SESSION['usuario_id'])&&(($ahora-($_SESSION['ultima_actividad']??$ahora)>1800)||($ahora-($_SESSION['sesion_creada']??$ahora)>43200))){$_SESSION=[];session_regenerate_id(true);}
if(!$publica&&empty($_SESSION['usuario_id'])){header('Location: /auth/login.php');exit;}
$_SESSION['sesion_creada']??=$ahora;$_SESSION['ultima_actividad']=$ahora;$_SESSION['csrf_finanzas']??=bin2hex(random_bytes(32));
$metodo=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($metodo,['GET','POST','HEAD'],true)){http_response_code(405);header('Allow: GET, POST, HEAD');exit;}
$soloPost=['/sync_pagos.php','/gastos/pagar.php','/gastos/ingresar_guardar.php','/auth/guardar_quincena.php','/auth/procesar_login.php','/mantenimiento_pagos_guardar.php','/mantenimiento/generar_pagos_mes.php'];
if(($ruta!== '/procesos/tarjetas_movimientos.php'&&str_starts_with($ruta,'/procesos/')||in_array($ruta,$soloPost,true))&&$metodo!=='POST'){http_response_code(405);header('Allow: POST');exit('Método no permitido.');}
if($metodo==='POST'){
 $token=$_POST['csrf_finanzas']??'';
 if(!is_string($token)||!hash_equals($_SESSION['csrf_finanzas'],$token)){http_response_code(403);exit('El formulario venció. Recarga la página antes de guardar.');}
}
set_exception_handler(function(Throwable $e){error_log((string)$e);http_response_code(500);echo 'No se pudo completar la operación. Inténtalo nuevamente.';});
ob_start(function($contenido){foreach(headers_list() as $h){if(stripos($h,'Content-Type:')===0&&stripos($h,'text/html')===false)return $contenido;}return finanzasInsertarProteccion($contenido);});
