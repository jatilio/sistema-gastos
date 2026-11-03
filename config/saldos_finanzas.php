<?php
function cuentasVinculadasFinanzas(PDO $pdo,int $usuario): array {
 $sql="SELECT c.id AS cuenta_id,c.nombre AS banco,'debito' AS tipo_cuenta,
 COALESCE((SELECT SUM(q.ingreso) FROM quincenas q WHERE q.usuario_id=c.usuario_id AND UPPER(TRIM(q.banco_origen))=UPPER(TRIM(c.nombre))),0) AS ingresos,
 COALESCE((SELECT SUM(g.amount) FROM gastos g WHERE g.usuario_id=c.usuario_id AND UPPER(TRIM(g.banco_pago))=UPPER(TRIM(c.nombre)) AND g.pagado='Si'),0) AS egresos
 FROM cuentas c WHERE c.usuario_id=? AND c.tipo='DEBITO' ORDER BY c.nombre";
 $q=$pdo->prepare($sql);$q->execute([$usuario]);$resultado=[];
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $c){$c['disponible']=round((float)$c['ingresos']-(float)$c['egresos'],2);$resultado[]=$c;}
 $q=$pdo->prepare("SELECT id AS cuenta_id,nombre_tarjeta AS banco,'credito' AS tipo_cuenta,limite,saldo_actual,disponible FROM tarjetas WHERE usuario_id=? AND tipo='credito' AND estado='activa' ORDER BY nombre_tarjeta");$q->execute([$usuario]);return array_merge($resultado,$q->fetchAll(PDO::FETCH_ASSOC));
}
function destinoPagoFinanzas(PDO $pdo,int $usuario,array $gasto,?int $seleccion=null): ?int {
 $esDeuda=mb_strtolower(trim((string)($gasto['categoria']??'')),'UTF-8')==='tarjeta de crédito';
 if($seleccion!==null&&!$esDeuda)throw new InvalidArgumentException('Solo un pago de tarjeta puede indicar una tarjeta de destino.');
 if($seleccion!==null){$q=$pdo->prepare("SELECT id FROM tarjetas WHERE id=? AND usuario_id=? AND tipo='credito'");$q->execute([$seleccion,$usuario]);$id=$q->fetchColumn();if(!$id)throw new InvalidArgumentException('La tarjeta de destino no pertenece a tus cuentas.');return (int)$id;}
 if(mb_strtolower(trim((string)($gasto['categoria']??'')),'UTF-8')!=='tarjeta de crédito')return null;
 $normalizar=fn($s)=>preg_replace('/[^A-Z0-9]/','',strtoupper($s));$nombre=$normalizar((string)$gasto['descripcion']);
 $alias=['CHASEDISNEY'=>'DISNEY','CHASESOUTHWEST'=>'SOUTHWEST'];$nombre=$alias[$nombre]??$nombre;
 $q=$pdo->prepare("SELECT id,banco,nombre_tarjeta FROM tarjetas WHERE usuario_id=? AND tipo='credito'");$q->execute([$usuario]);$coinciden=[];
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $t){if(in_array($nombre,[$normalizar($t['nombre_tarjeta']),$normalizar($t['banco'].$t['nombre_tarjeta'])],true))$coinciden[]=(int)$t['id'];}
 if(count($coinciden)>1)throw new InvalidArgumentException('Hay más de una tarjeta con ese nombre. Selecciona la tarjeta de destino.');
 return $coinciden[0]??null;
}

function medioGastoFinanzas(PDO $pdo,int $usuario,string $metodo,string $banco): array {
 $metodo=strtolower(trim($metodo));if($metodo==='transferencia')$metodo='debito';
 if($metodo==='efectivo')return ['metodo'=>'efectivo','banco'=>'EFECTIVO','tarjeta'=>null];
 $norm=fn($s)=>preg_replace('/[^A-Z0-9]/','',strtoupper($s));
 if($metodo==='credito'){
  $q=$pdo->prepare("SELECT id,nombre_tarjeta FROM tarjetas WHERE usuario_id=? AND tipo='credito' AND estado='activa'");$q->execute([$usuario]);$coinciden=[];
  foreach($q->fetchAll(PDO::FETCH_ASSOC) as $t)if($norm($t['nombre_tarjeta'])===$norm($banco))$coinciden[]=$t;
  if(count($coinciden)===1)return ['metodo'=>'credito','banco'=>$coinciden[0]['nombre_tarjeta'],'tarjeta'=>(int)$coinciden[0]['id']];
 }elseif($metodo==='debito'){
  $q=$pdo->prepare("SELECT nombre FROM cuentas WHERE usuario_id=? AND tipo='DEBITO'");$q->execute([$usuario]);foreach($q->fetchAll(PDO::FETCH_COLUMN) as $nombre)if($norm($nombre)===$norm($banco))return ['metodo'=>'debito','banco'=>$nombre,'tarjeta'=>null];
 }
 throw new InvalidArgumentException('Selecciona la cuenta de débito o tarjeta de crédito correspondiente al gasto.');
}
