<?php
function conservarFiltrosFinanzas(): void {
 if(session_status()!==PHP_SESSION_ACTIVE||empty($_SESSION['usuario_id'])||($_SERVER['REQUEST_METHOD']??'')!=='GET')return;
 $ruta=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
 $permitidas=['/index.php','/gastos/ingresar.php','/gastos/gastos_cobrados.php','/reportes.php','/top10.php'];
 if(!in_array($ruta,$permitidas,true))return;
 $menu=is_string($_GET['menu']??null)?$_GET['menu']:'dashboard';
 if(in_array($menu,['mantenimiento_pagos','historial_movimientos','ingresos_salario'],true))return;
 $identidad=(int)($_SESSION['usuario_autenticado_id']??$_SESSION['usuario_id']);$clave=$identidad.'|'.$ruta.'|'.$menu;
 $guardados=$_SESSION['filtros_finanzas'][$clave]??[];
 foreach(['month','mes','year','anio','banco'] as $campo){
  if(array_key_exists($campo,$_GET)){
   $valor=$_GET[$campo];if(!is_string($valor))continue;
   if(in_array($campo,['month','mes'])&&(!ctype_digit($valor)||(int)$valor<1||(int)$valor>12))continue;
   if(in_array($campo,['year','anio'])&&(!ctype_digit($valor)||(int)$valor<1900||(int)$valor>9998))continue;
   if($campo==='banco'&&mb_strlen($valor)>100)continue;
   $guardados[$campo]=$valor;
  }elseif(isset($guardados[$campo]))$_GET[$campo]=$guardados[$campo];
 }
 $_SESSION['filtros_finanzas'][$clave]=$guardados;
}
