<?php
require_once __DIR__ . '/../../config/seguridad_web.php';

if(session_status()!==PHP_SESSION_ACTIVE)session_start();
session_unset();
session_destroy();
setcookie(session_name(),'', ['expires'=>time()-42000,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
header("Location: login.php");
exit;
?>
