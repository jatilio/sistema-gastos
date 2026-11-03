<?php
require_once __DIR__.'/pagos_programados.php';
function proximosPagosFinanzas(array $planes,array $gastos,DateTimeImmutable $hoy): array {
 $hoy=$hoy->setTime(0,0);$fin=$hoy->modify('+7 days');$salida=[];$existentes=[];$pausados=[];
 foreach($planes as $p){if(empty($p['activo']))$pausados[(int)$p['id']]=true;}
 foreach($gastos as $g){if(!empty($g['mantenimiento_id'])&&!empty($g['due_date']))$existentes[(int)$g['mantenimiento_id'].'|'.$g['due_date']]=true;
  if(!empty($g['mantenimiento_id'])&&isset($pausados[(int)$g['mantenimiento_id']]))continue;
  if(($g['pagado']??'No')==='Si'||empty($g['due_date'])||$g['due_date']<$hoy->format('Y-m-d')||$g['due_date']>$fin->format('Y-m-d'))continue;
  $salida[]=['id'=>$g['id'],'descripcion'=>$g['descripcion'],'amount'=>$g['amount'],'due_date_real'=>$g['due_date'],'generado'=>true];
 }
 foreach($planes as $p){if(empty($p['activo']))continue;
  for($mes=$hoy->modify('first day of this month');$mes<=$fin;$mes=$mes->modify('+1 month')){
   $fecha=fechaPagoProgramado($p,(int)$mes->format('n'),(int)$mes->format('Y'));
   if(!$fecha||$fecha<$hoy->format('Y-m-d')||$fecha>$fin->format('Y-m-d')||isset($existentes[(int)$p['id'].'|'.$fecha]))continue;
   $salida[]=['id'=>$p['id'],'descripcion'=>$p['descripcion'],'amount'=>$p['amount'],'due_date_real'=>$fecha,'generado'=>false];
  }
 }
 usort($salida,fn($a,$b)=>strcmp($a['due_date_real'],$b['due_date_real'])?:strcasecmp($a['descripcion'],$b['descripcion']));return $salida;
}
