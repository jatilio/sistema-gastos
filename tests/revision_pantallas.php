<?php
ob_start();ini_set('session.save_path',sys_get_temp_dir());session_id('pantalla-test-'.bin2hex(random_bytes(8)));session_start();
$_SESSION=['usuario_id'=>4,'usuario_autenticado_id'=>4,'usuario_nombre'=>'Alberto Lopez'];
$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REQUEST_URI']='/index.php';
$_GET=['menu'=>$argv[1]??'dashboard'];if(isset($argv[2]))$_GET['mes']=$argv[2];if(isset($argv[3]))$_GET['anio']=$argv[3];
$avisos=[];set_error_handler(function($nivel,$mensaje)use(&$avisos){$avisos[]=$mensaje;return true;});
try{require __DIR__.'/../public/index.php';$html=ob_get_clean();restore_error_handler();session_destroy();if($avisos)throw new RuntimeException(implode(' | ',$avisos));if(($argv[2]??'')==='11'&&!str_contains($html,'No hay gastos adicionales pagados con débito')&&!str_contains($html,'id="chartCategorias"'))throw new RuntimeException('Falta gráfica o explicación');if(str_contains($html,'Fatal error')||str_contains($html,'ERROR SQL'))throw new RuntimeException('Error mostrado en pantalla');echo 'OK pantalla: '.($argv[1]??'dashboard').PHP_EOL;}catch(Throwable $e){if(ob_get_level())ob_end_clean();if(session_status()===PHP_SESSION_ACTIVE)session_destroy();fwrite(STDERR,'FALLO '.($argv[1]??'dashboard').': '.$e->getMessage().PHP_EOL);exit(1);}
