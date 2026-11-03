<?php
require __DIR__.'/../config/proximos_pagos.php';require __DIR__.'/../config/filtros_navegacion.php';
function verificarVF($ok,$mensaje){if(!$ok)throw new RuntimeException($mensaje);}
$planes=[['id'=>1,'descripcion'=>'Mensual','amount'=>10,'activo'=>1,'frecuencia'=>'M','due_date'=>'2026-01-02'],['id'=>2,'descripcion'=>'Anual','amount'=>20,'activo'=>1,'frecuencia'=>'A','due_date'=>'2020-01-03'],['id'=>3,'descripcion'=>'Inactivo','amount'=>100,'activo'=>0,'frecuencia'=>'M','due_date'=>'2026-01-04']];
$gastos=[['id'=>1,'mantenimiento_id'=>1,'descripcion'=>'Mensual','amount'=>10,'due_date'=>'2027-01-02','pagado'=>'Si'],['id'=>2,'mantenimiento_id'=>null,'descripcion'=>'Pendiente','amount'=>5,'due_date'=>'2026-12-31','pagado'=>'No']];
$r=proximosPagosFinanzas($planes,$gastos,new DateTimeImmutable('2026-12-29',new DateTimeZone('America/New_York')));
verificarVF(array_column($r,'descripcion')===['Pendiente','Anual'],'Cambio de año, anual, pagado e inactivo');
$planes[0]['due_date']='2026-01-31';$r=proximosPagosFinanzas([$planes[0]],[],new DateTimeImmutable('2026-02-25'));verificarVF($r[0]['due_date_real']==='2026-02-28','Fin de mes');
$pausado=['id'=>32,'descripcion'=>'UNITY','amount'=>0,'activo'=>0,'frecuencia'=>'M','due_date'=>'2026-01-01'];
$generado=['id'=>99,'mantenimiento_id'=>32,'descripcion'=>'UNITY','amount'=>0,'due_date'=>'2026-11-01','pagado'=>'No'];
$r=proximosPagosFinanzas([$pausado],[$generado],new DateTimeImmutable('2026-10-29'));
verificarVF($r===[],'Excluir gastos ya generados de un plan pausado');
$pausado['activo']=1;$r=proximosPagosFinanzas([$pausado],[$generado],new DateTimeImmutable('2026-10-29'));
verificarVF(count($r)===1,'Reactivar sin duplicar el gasto generado');
ini_set('session.save_path',sys_get_temp_dir());session_id('filtros-test-'.bin2hex(random_bytes(8)));session_start();
$_SESSION=['usuario_id'=>3,'usuario_autenticado_id'=>4];$_SERVER=['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/index.php'];$_GET=['menu'=>'reportes','month'=>'11','year'=>'2026','banco'=>'BANK'];conservarFiltrosFinanzas();
$_GET=['menu'=>'reportes'];conservarFiltrosFinanzas();verificarVF($_GET['month']==='11'&&$_GET['banco']==='BANK','Restaurar filtros');
$_GET=['menu'=>'dashboard'];conservarFiltrosFinanzas();verificarVF(!isset($_GET['month']),'Separar pantallas');
$_SESSION['usuario_autenticado_id']=3;$_GET=['menu'=>'reportes'];conservarFiltrosFinanzas();verificarVF(!isset($_GET['month']),'Separar cuentas');
$_SESSION['usuario_autenticado_id']=4;$_SERVER['REQUEST_METHOD']='POST';$_GET=['menu'=>'reportes'];conservarFiltrosFinanzas();verificarVF(!isset($_GET['month']),'No modificar operaciones POST');
session_destroy();echo "OK: próximos 7 días, cambio de año, fin de mes, pagos realizados, inactivos y filtros aislados por cuenta y pantalla.\n";
