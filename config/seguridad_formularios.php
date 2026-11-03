<?php
function exigirSesionFinanzas(): int {
 if(empty($_SESSION['usuario_id'])){header('Location: /auth/login.php');exit;}
 return (int)$_SESSION['usuario_id'];
}
function tokenFormularioFinanzas(): string {return $_SESSION['formulario_token']??=bin2hex(random_bytes(32));}
function validarPostFinanzas(): void {
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);header('Allow: POST');exit('Método no permitido.');}
 $token=$_POST['formulario_token']??'';
 if(!is_string($token)||!hash_equals(tokenFormularioFinanzas(),$token)){http_response_code(403);exit('Recarga la página y vuelve a intentarlo.');}
}
