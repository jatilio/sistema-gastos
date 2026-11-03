<?php
// Crea un gasto de prueba sin tarjeta dentro de una transacción y revierte todo.
ob_start();ini_set('session.save_path',sys_get_temp_dir());session_id('historial-test-'.bin2hex(random_bytes(8)));session_start();
require __DIR__.'/../config/db.php';
function exigirHistorial($ok,$mensaje){if(!$ok)throw new RuntimeException($mensaje);}
$alberto=$pdo->query("SELECT id FROM usuarios WHERE usuario='alberto'")->fetchColumn();
if(!$alberto)throw new RuntimeException('Se necesita la cuenta de prueba Alberto existente.');
$_SESSION=['usuario_id'=>(int)$alberto];resolverDatosFinanzas($pdo);$owner=(int)$_SESSION['usuario_id'];
$conteo=(int)$pdo->query('SELECT COUNT(*) FROM historial_gastos')->fetchColumn();
$pdo->beginTransaction();
try{
 $q=$pdo->prepare("INSERT INTO gastos (usuario_id,descripcion,amount,pagado,notes) VALUES (?, ?, 10, 'No', ?)");
 $marca='TEST-HISTORIAL-'.bin2hex(random_bytes(6));$q->execute([$owner,$marca,'<script>prueba</script>']);$gasto=(int)$pdo->lastInsertId();
 $consultaLogs=$pdo->prepare('SELECT * FROM historial_gastos WHERE gasto_id=? ORDER BY id');$consultaLogs->execute([$gasto]);$logs=$consultaLogs->fetchAll();
 exigirHistorial(count($logs)===1&&$logs[0]['accion']==='REGISTRO'&&(int)$logs[0]['actor_id']===(int)$alberto,'Registro e identidad de Alberto');
 exigirHistorial($logs[0]['actor_nombre']==='Alberto Lopez','Nombre visible');
 $pdo->exec("UPDATE gastos SET pagado='Si',payment_date='2026-10-29' WHERE id=$gasto");
 $pdo->exec("UPDATE gastos SET amount=12 WHERE id=$gasto");
 $pdo->exec("UPDATE gastos SET amount=12 WHERE id=$gasto");
 $consultaLogs->execute([$gasto]);$logs=$consultaLogs->fetchAll();exigirHistorial(count($logs)===3,'No auditar guardados sin cambios');
 exigirHistorial($logs[1]['accion']==='PAGO'&&$logs[2]['accion']==='MODIFICACIÓN','Tipos de movimiento');
 exigirHistorial(json_decode($logs[2]['antes'],true)['amount']===10.0&&json_decode($logs[2]['despues'],true)['amount']===12.0,'Valores antes y después');
 $_GET=['buscar'=>$marca];ob_start();require __DIR__.'/../public/historial_movimientos.php';$html=ob_get_clean();
 exigirHistorial(str_contains($html,'Alberto Lopez')&&!str_contains($html,'<script>prueba</script>')&&str_contains($html,'&lt;script&gt;'),'Vista y escape HTML');
 $_SESSION['usuario_id']=999999;$_GET=['buscar'=>$marca];ob_start();require __DIR__.'/../public/historial_movimientos.php';$html=ob_get_clean();exigirHistorial(!str_contains($html,$marca.'<'),'Aislamiento del historial');
 $pdo->exec("DELETE FROM gastos WHERE id=$gasto");$consultaLogs->execute([$gasto]);$logs=$consultaLogs->fetchAll();exigirHistorial(count($logs)===4&&$logs[3]['accion']==='ELIMINACIÓN','Auditar eliminación');
}finally{if($pdo->inTransaction())$pdo->rollBack();session_destroy();}
exigirHistorial((int)$pdo->query('SELECT COUNT(*) FROM historial_gastos')->fetchColumn()===$conteo,'Rollback completo');
ob_end_clean();echo "OK: registro, pago, edición, eliminación, identidad real, valores anteriores, no-op, aislamiento y rollback. Sin cambios persistentes.\n";
