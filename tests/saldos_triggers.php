<?php
require __DIR__.'/../config/db.php';require __DIR__.'/../config/saldos_finanzas.php';
$antes=$pdo->query('SELECT id,saldo_actual,disponible FROM tarjetas ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$conteoHistorial=(int)$pdo->query('SELECT COUNT(*) FROM historial_gastos')->fetchColumn();
$pdo->beginTransaction();
try{
 $tarjetasPrueba=[];foreach([3,3,4] as $owner){$q=$pdo->prepare("INSERT INTO tarjetas(usuario_id,banco,nombre_tarjeta,tipo,limite,saldo_actual) VALUES(?,'PRUEBA','TEST-SALDO','credito',1000,100)");$q->execute([$owner]);$tarjetasPrueba[]=(int)$pdo->lastInsertId();}[$a,$b,$ajena]=$tarjetasPrueba;
 $saldo=function($id,$esperado)use($pdo){$q=$pdo->prepare('SELECT saldo_actual,disponible FROM tarjetas WHERE id=?');$q->execute([$id]);$r=$q->fetch();if((float)$r['saldo_actual']!==($esperado*1.0)||(float)$r['disponible']!==(float)(1000-$esperado))throw new RuntimeException('Saldo o disponible incorrecto: '.$id.' esperado '.$esperado);};
 $insertar=function($origen,$destino,$metodo,$pagado,$monto)use($pdo){$q=$pdo->prepare("INSERT INTO gastos(usuario_id,tarjeta_id,tarjeta_destino_id,descripcion,amount,metodo_pago,pagado) VALUES(3,?,?,'TEST REVERTIDO',?,?,?)");$q->execute([$origen,$destino,$monto,$metodo,$pagado]);return (int)$pdo->lastInsertId();};
 $g=$insertar($a,null,'credito','No',50);$saldo($a,100);
 $pdo->exec("UPDATE gastos SET pagado='Si' WHERE id=$g");$saldo($a,150);
 $pdo->exec("UPDATE gastos SET amount=70 WHERE id=$g");$saldo($a,170);
 $pdo->exec("UPDATE gastos SET notes='Nota solamente' WHERE id=$g");$saldo($a,170);
 $pdo->exec("UPDATE gastos SET tarjeta_id=$b WHERE id=$g");$saldo($a,100);$saldo($b,170);
 $pdo->exec("UPDATE gastos SET pagado='No' WHERE id=$g");$saldo($b,100);
 $pdo->exec("DELETE FROM gastos WHERE id=$g");$saldo($b,100);
 $g=$insertar($a,null,'credito','Si',50);$saldo($a,150);$pdo->exec("DELETE FROM gastos WHERE id=$g");$saldo($a,100);
 $g=$insertar(null,$a,'debito','Si',40);$saldo($a,60);
 $pdo->exec("UPDATE gastos SET amount=60 WHERE id=$g");$saldo($a,40);
 $pdo->exec("UPDATE gastos SET tarjeta_destino_id=$b WHERE id=$g");$saldo($a,100);$saldo($b,40);
 $pdo->exec("DELETE FROM gastos WHERE id=$g");$saldo($b,100);
 $g=$insertar($b,$a,'credito','Si',20);$saldo($a,80);$saldo($b,120);
 $pdo->exec("DELETE FROM gastos WHERE id=$g");$saldo($a,100);$saldo($b,100);
 $g=$insertar($ajena,null,'credito','Si',10);$saldo($ajena,100);$pdo->exec("DELETE FROM gastos WHERE id=$g");$saldo($ajena,100);
 $cuentas=cuentasVinculadasFinanzas($pdo,3);if(in_array($ajena,array_map('intval',array_column(array_filter($cuentas,fn($c)=>$c['tipo_cuenta']==='credito'),'cuenta_id')),true))throw new RuntimeException('Se mostró una tarjeta ajena');
 $g=$insertar(null,$a,'debito','Si',1);$q=$pdo->prepare('SELECT despues FROM historial_gastos WHERE gasto_id=? ORDER BY id DESC LIMIT 1');$q->execute([$g]);$log=json_decode($q->fetchColumn(),true);if((int)$log['tarjeta_destino_id']!==$a)throw new RuntimeException('Falta destino en auditoría');
}finally{if($pdo->inTransaction())$pdo->rollBack();}
if($antes!==$pdo->query('SELECT id,saldo_actual,disponible FROM tarjetas ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)||(int)$pdo->query('SELECT COUNT(*) FROM historial_gastos')->fetchColumn()!==$conteoHistorial)throw new RuntimeException('Cambios persistentes tras la prueba');
echo "OK: compras pendientes y confirmadas, pagos de deuda, edición de monto, cambio de tarjeta, transferencia de deuda, reversión, eliminación, propiedad y auditoría. Todos los saldos reales intactos.\n";
