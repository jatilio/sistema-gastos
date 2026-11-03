<?php
require __DIR__.'/../config/db.php';
$antes=$pdo->query('SELECT id,saldo_actual,disponible FROM tarjetas ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$pdo->beginTransaction();
try{
 $q=$pdo->prepare("INSERT INTO tarjetas(usuario_id,banco,nombre_tarjeta,tipo,limite,saldo_actual) VALUES(3,'PRUEBA','DIAGNOSTICO SIN DATOS REALES','credito',1000,100)");$q->execute();$tarjeta=(int)$pdo->lastInsertId();
 $q=$pdo->prepare("INSERT INTO gastos(usuario_id,tarjeta_id,descripcion,tipo,categoria,amount,metodo_pago,pagado) VALUES(3,?,'PRUEBA REVERTIDA','Adicional','Tarjeta',50,'credito','Si')");$q->execute([$tarjeta]);$gasto=(int)$pdo->lastInsertId();
 $q=$pdo->prepare('SELECT saldo_actual FROM tarjetas WHERE id=?');$q->execute([$tarjeta]);$despuesInsert=$q->fetchColumn();
 $u=$pdo->prepare('UPDATE gastos SET amount=70 WHERE id=?');$u->execute([$gasto]);$q->execute([$tarjeta]);$despuesEdit=$q->fetchColumn();
 echo json_encode(['caso'=>'Tarjeta sintética: deuda 100; compra de crédito 50; luego monto 70','tras_compra'=>$despuesInsert,'tras_edicion'=>$despuesEdit],JSON_UNESCAPED_UNICODE).PHP_EOL;
}finally{if($pdo->inTransaction())$pdo->rollBack();}
$despues=$pdo->query('SELECT id,saldo_actual,disponible FROM tarjetas ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
if($antes!==$despues)throw new RuntimeException('Cambió un saldo real');
echo "Se revirtió toda la prueba. Saldos reales sin cambios.\n";
