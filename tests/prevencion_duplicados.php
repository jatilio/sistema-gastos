<?php
// Ejecutar: php tests/prevencion_duplicados.php
// Las tablas TEMPORARY ocultan las reales solo dentro de esta conexión.
ob_start();
session_start();
require dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/pagos_programados.php';
final class FinDeSolicitud extends Error {}
function comprobar(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
}
function ejecutar(PDO $pdo, string $archivo): int {
    http_response_code(200);
    $source = file_get_contents(dirname(__DIR__) . '/' . $archivo);
    $source = preg_replace('/^<\?php\s*/', '', $source);
    $source = str_replace("require_once dirname(__DIR__, 2) . '/config/db.php';", '', $source);
    $source = str_replace("require_once dirname(__DIR__, 2) . '/config/pagos_programados.php';", '', $source);
    $source = preg_replace('/\bexit\s*(\([^;]*\))?\s*;/', 'throw new FinDeSolicitud();', $source);
    try { eval($source); } catch (FinDeSolicitud $e) {}
    return http_response_code();
}
function cantidad(PDO $pdo): int {
    return (int)$pdo->query('SELECT COUNT(*) FROM gastos')->fetchColumn();
}
$pdo->exec('CREATE TEMPORARY TABLE mantenimiento_pagos (id INT PRIMARY KEY, usuario_id INT, activo INT, frecuencia VARCHAR(10), due_date DATE, descripcion VARCHAR(80), categoria VARCHAR(80), amount DECIMAL(10,2)) ENGINE=InnoDB');
$pdo->exec("CREATE TEMPORARY TABLE gastos (id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT, mantenimiento_id INT, descripcion VARCHAR(150), categoria VARCHAR(80), due_date DATE, payment_date DATE, amount DECIMAL(10,2) CHECK (amount >= 0), pagado VARCHAR(5), tipo VARCHAR(20), metodo_pago VARCHAR(30), banco_pago VARCHAR(100), notes TEXT) ENGINE=InnoDB");
$pdo->exec("INSERT INTO mantenimiento_pagos VALUES (1,1,1,'M','2026-01-31','Prueba','Otros',10),(2,1,1,'M','2026-01-15','Prueba 2','Otros',20),(3,2,1,'M','2026-01-15','Otro usuario','Otros',30)");
$_SESSION = ['usuario_id'=>1, 'generar_pagos_token'=>'token-prueba'];
$_SERVER['REQUEST_METHOD']='POST';
$_POST=['month'=>2,'year'=>2026,'generar_pagos_token'=>'token-prueba'];
$monthly = 'public/mantenimiento/generar_pagos_mes.php';
comprobar(ejecutar($pdo,$monthly)===302 && cantidad($pdo)===2,'Primera generación');
ejecutar($pdo,$monthly);
comprobar(cantidad($pdo)===2,'Repetir no duplica');
comprobar($pdo->query('SELECT due_date FROM gastos WHERE mantenimiento_id=1')->fetchColumn()==='2026-02-28','Último día de febrero');
$pdo->exec("UPDATE gastos SET pagado='Si' WHERE mantenimiento_id=1");
$pdo->exec("INSERT INTO mantenimiento_pagos VALUES (4,1,1,'M','2026-01-20','Nueva plantilla','Otros',40)");
ejecutar($pdo,$monthly);
comprobar(http_response_code()===409 && cantidad($pdo)===2,'Bloquear mes con registros existentes');
comprobar($pdo->query('SELECT pagado FROM gastos WHERE mantenimiento_id=1')->fetchColumn()==='Si','Conservar pago existente');
$_SESSION['usuario_id']=2;
ejecutar($pdo,$monthly);
comprobar(cantidad($pdo)===3,'Separar usuarios');
$_SESSION['usuario_id']=1;
$_POST['month']=3;
ejecutar($pdo,$monthly);
comprobar(cantidad($pdo)===6,'Permitir otro mes');
$_POST['month']=13;
comprobar(ejecutar($pdo,$monthly)===400 && cantidad($pdo)===6,'Rechazar mes inválido');
$_POST['month']=4;
$_POST['generar_pagos_token']='incorrecto';
comprobar(ejecutar($pdo,$monthly)===403 && cantidad($pdo)===6,'Rechazar token inválido');
$_SERVER['REQUEST_METHOD']='GET';
comprobar(ejecutar($pdo,$monthly)===405 && cantidad($pdo)===6,'GET no genera pagos');
$_SERVER['REQUEST_METHOD']='POST';
$_SESSION['adicional_token']='envio-unico';
$_POST=['adicional_token'=>'envio-unico','descripcion'=>['Compra de prueba'],'monto'=>[12],'categoria'=>['Otros'],'metodo_pago'=>['efectivo'],'banco_pago'=>[''],'payment_date'=>['2026-02-01'],'notes'=>['']];
$additional='public/procesos/guardar_adicional.php';
comprobar(ejecutar($pdo,$additional)===302 && cantidad($pdo)===7,'Guardar adicionales');
comprobar(ejecutar($pdo,$additional)===409 && cantidad($pdo)===7,'Reenvío no duplica adicionales');
$_SESSION['adicional_token']='otro-envio';
$_POST['adicional_token']='otro-envio';
comprobar(ejecutar($pdo,$additional)===302 && cantidad($pdo)===8,'Permitir compra legítima idéntica con nuevo formulario');
// Otro proceso mantiene el bloqueo: esta solicitud no debe insertar nada.
$otraConexion = new PDO($dsn, $user, $pass, $options);
$lockPrueba = $otraConexion->prepare('SELECT GET_LOCK(?, 0)');
$lockPrueba->execute(['gastos_mensuales_1']);
comprobar((int)$lockPrueba->fetchColumn()===1,'Preparar bloqueo simultáneo');
$_POST=['month'=>4,'year'=>2026,'generar_pagos_token'=>'token-prueba'];
try {
    comprobar(ejecutar($pdo,$monthly)===409 && cantidad($pdo)===8,'Rechazar generación simultánea');
} finally {
    $releasePrueba=$otraConexion->prepare('SELECT RELEASE_LOCK(?)');
    $releasePrueba->execute(['gastos_mensuales_1']);
}
// Forzar un error en el último elemento: ninguno de los anteriores debe quedar guardado.
$pdo->exec("INSERT INTO mantenimiento_pagos VALUES (5,1,1,'M','2026-01-20','Fallo controlado','Otros',-1)");
comprobar(ejecutar($pdo,$monthly)===500 && cantidad($pdo)===8,'Revertir lote completo');
comprobar((int)$pdo->query("SELECT IS_FREE_LOCK('gastos_mensuales_1')")->fetchColumn()===1,'Liberar bloqueo tras error');
$pdo->exec('DELETE FROM mantenimiento_pagos WHERE id=5');
comprobar(ejecutar($pdo,$monthly)===302 && cantidad($pdo)===11,'Reintentar después del error');
ob_end_clean();
echo "OK: generación repetida, mes cerrado, pagos existentes, usuarios, otro mes, fin de mes, mes inválido, token, GET, reenvío de adicionales compra idéntica legítima, bloqueo entre conexiones, reversión y reintento después de un error. Solo tablas temporales.\n";