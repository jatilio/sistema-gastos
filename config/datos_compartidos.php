<?php
// Mantiene separada la identidad que inició sesión del conjunto compartido de datos.
function resolverDatosFinanzas(PDO $pdo): void {
    $pdo->exec('SET @finanzas_actor=NULL, @finanzas_actor_nombre=NULL, @finanzas_propietario=NULL');
    if(session_status()!==PHP_SESSION_ACTIVE || empty($_SESSION['usuario_id']))return;
    $identidad=(int)($_SESSION['usuario_autenticado_id']??$_SESSION['usuario_id']);
    $consulta=$pdo->prepare('SELECT propietario_id FROM usuarios_datos_compartidos WHERE usuario_id=?');
    $consulta->execute([$identidad]);
    $propietario=$consulta->fetchColumn();
    $nombre=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(nombre), ''),usuario) FROM usuarios WHERE id=?");
    $nombre->execute([$identidad]);
    $visible=$nombre->fetchColumn();
    if($visible===false){$_SESSION=[];http_response_code(401);exit('La sesión ya no es válida. Vuelve a ingresar.');}
    $_SESSION['usuario_nombre']=$visible;
    $_SESSION['usuario_autenticado_id']=$identidad;
    $_SESSION['usuario_id']=$propietario===false?$identidad:(int)$propietario;
    $contexto=$pdo->prepare('SET @finanzas_actor=?, @finanzas_actor_nombre=?, @finanzas_propietario=?');
    $contexto->execute([$identidad,(string)($_SESSION['usuario_nombre']??'Usuario'),$_SESSION['usuario_id']]);
}
