<?php
// Mantiene separada la identidad que inició sesión del conjunto compartido de datos.
function resolverDatosFinanzas(PDO $pdo): void {
    if(session_status()!==PHP_SESSION_ACTIVE || empty($_SESSION['usuario_id']))return;
    $identidad=(int)($_SESSION['usuario_autenticado_id']??$_SESSION['usuario_id']);
    $consulta=$pdo->prepare('SELECT propietario_id FROM usuarios_datos_compartidos WHERE usuario_id=?');
    $consulta->execute([$identidad]);
    $propietario=$consulta->fetchColumn();
    $nombre=$pdo->prepare("SELECT COALESCE(NULLIF(TRIM(nombre), ''),usuario) FROM usuarios WHERE id=?");
    $nombre->execute([$identidad]);
    $visible=$nombre->fetchColumn();
    if($visible!==false)$_SESSION['usuario_nombre']=$visible;
    $_SESSION['usuario_autenticado_id']=$identidad;
    $_SESSION['usuario_id']=$propietario===false?$identidad:(int)$propietario;
}
