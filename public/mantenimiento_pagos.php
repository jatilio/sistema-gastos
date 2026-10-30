<?php
if(session_status()===PHP_SESSION_NONE) session_start();
if(empty($_SESSION['usuario_id'])) { header('Location: /auth/login.php'); exit; }
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/pagos_programados.php';
$usuario_id=(int)$_SESSION['usuario_id'];
$ahora=new DateTimeImmutable('now',new DateTimeZone('America/New_York'));
$mesPlan=filter_var($_GET['month']??$ahora->format('n'),FILTER_VALIDATE_INT);
$anioPlan=filter_var($_GET['year']??$ahora->format('Y'),FILTER_VALIDATE_INT);
if(!$mesPlan || $mesPlan<1 || $mesPlan>12) $mesPlan=(int)$ahora->format('n');
if(!$anioPlan || $anioPlan<1900 || $anioPlan>9998) $anioPlan=(int)$ahora->format('Y');
$mesesPlan=[1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$frecuenciasPlan=['M'=>'Mensual','S'=>'Semestral','A'=>'Anual','F'=>'Fijo'];
$_SESSION['plantilla_token']??=bin2hex(random_bytes(32));
$_SESSION['generar_pagos_token']??=bin2hex(random_bytes(32));
$stmt=$pdo->prepare('SELECT * FROM mantenimiento_pagos WHERE usuario_id=? ORDER BY DAY(due_date), descripcion, id');
$stmt->execute([$usuario_id]);
$pagos=$stmt->fetchAll(PDO::FETCH_ASSOC);
$categoriasPlan=categoriasPagosProgramados($pdo,$usuario_id);
$inicioPlan=sprintf('%04d-%02d-01',$anioPlan,$mesPlan);
$finPlan=(new DateTimeImmutable($inicioPlan))->modify('+1 month')->format('Y-m-d');
$stmt=$pdo->prepare('SELECT DISTINCT mantenimiento_id FROM gastos WHERE usuario_id=? AND due_date>=? AND due_date<? AND mantenimiento_id IS NOT NULL');
$stmt->execute([$usuario_id,$inicioPlan,$finPlan]);
$existentesPlan=array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN),true);
$bloqueadoPlan=count($existentesPlan)>0;
$stmt=$pdo->prepare('SELECT DISTINCT MONTH(due_date) FROM gastos WHERE usuario_id=? AND mantenimiento_id IS NOT NULL AND YEAR(due_date)=?');
$stmt->execute([$usuario_id,$anioPlan]);
$mesesCerradosPlan=array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN));
$vistaPlan=[];$activosPlan=0;$pendientesPlan=0;$importePlan=0;$importePendientePlan=0;
foreach($pagos as $pago) {
    if(!(int)$pago['activo']) continue;
    $activosPlan++;
    $fecha=fechaPagoProgramado($pago,$mesPlan,$anioPlan);
    if($fecha===null) continue;
    $existe=isset($existentesPlan[$pago['id']]);
    $vistaPlan[]=['pago'=>$pago,'fecha'=>$fecha,'existe'=>$existe];
    $importePlan+=(float)$pago['amount'];
    if(!$existe) { $pendientesPlan++;$importePendientePlan+=(float)$pago['amount']; }
}
$mensajePlan=$_SESSION['mensaje_exito']??'';$errorPlan=$_SESSION['mensaje_error']??'';
unset($_SESSION['mensaje_exito'],$_SESSION['mensaje_error']);
// Formularios individuales: editar un nombre actualiza su registro, no crea otro.
$editorPlan=static function(array $pago) use($categoriasPlan,$frecuenciasPlan) {
    $id=(int)($pago['id']??0);
    $categoriaActual=mb_strtolower(trim($pago['categoria']??'Otros'));
    ?>
    <form action="/mantenimiento_pagos_guardar.php" method="POST" class="scheduled-editor">
        <input type="hidden" name="plantilla_token" value="<?= htmlspecialchars($_SESSION['plantilla_token'],ENT_QUOTES) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <label>Descripción<input name="descripcion" maxlength="150" required value="<?= htmlspecialchars($pago['descripcion']??'',ENT_QUOTES) ?>"></label>
        <label>Fecha límite<input type="date" name="due_date" required value="<?= htmlspecialchars($pago['due_date']??'',ENT_QUOTES) ?>"></label>
        <label>Frecuencia<select name="frecuencia"><?php foreach($frecuenciasPlan as $valor=>$nombre): ?><option value="<?= $valor ?>" <?= ($pago['frecuencia']??'M')===$valor?'selected':'' ?>><?= $nombre ?></option><?php endforeach; ?></select></label>
        <label>Categoría<select name="categoria" required><?php foreach($categoriasPlan as $categoria): ?><option value="<?= htmlspecialchars($categoria,ENT_QUOTES) ?>" <?= mb_strtolower($categoria)===$categoriaActual?'selected':'' ?>><?= htmlspecialchars($categoria) ?></option><?php endforeach; ?></select></label>
        <label>Monto ($)<input type="number" name="amount" step="0.01" min="0" max="99999999.99" required value="<?= isset($pago['amount'])?number_format((float)$pago['amount'],2,'.',''):'' ?>"></label>
        <label>Estado<select name="activo"><option value="1" <?= (int)($pago['activo']??1)===1?'selected':'' ?>>Activo</option><option value="0" <?= (int)($pago['activo']??1)===0?'selected':'' ?>>Pausado</option></select></label>
        <p class="scheduled-help">Los cambios se aplican a pagos futuros. Los gastos ya generados conservan sus datos.</p>
        <button type="submit">Guardar pago</button>
    </form>
    <?php
};
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pagos programados</title>
<link rel="stylesheet" href="/assets/css/finance-responsive.css?v=1">
<link rel="stylesheet" href="/assets/css/scheduled-payments.css?v=1">
<script src="/assets/js/finance-interface.js?v=1"></script>
<script src="/assets/js/finance-language.js?v=1"></script>
</head><body>
<div class="scheduled-payments">
    <header class="scheduled-heading"><div><h1>Pagos programados</h1><p>Organiza tus compromisos y genera únicamente los pagos que faltan.</p></div><a href="#nuevo-pago" class="scheduled-link">+ Nuevo pago</a></header>
    <?php if($mensajePlan): ?><p class="scheduled-message success" role="status"><?= htmlspecialchars($mensajePlan) ?></p><?php endif; ?>
    <?php if($errorPlan): ?><p class="scheduled-message error" role="alert"><?= htmlspecialchars($errorPlan) ?></p><?php endif; ?>
    <div class="scheduled-stats">
        <section><span>Pagos activos</span><strong><?= $activosPlan ?></strong></section>
        <section><span>Importe previsto del mes</span><strong>$<?= number_format($importePlan,2) ?></strong></section>
        <section><span>Pagos por generar</span><strong><?= $pendientesPlan ?></strong></section>
        <section><span>Pagos pausados</span><strong><?= count($pagos)-$activosPlan ?></strong></section>
    </div>
    <section class="scheduled-panel">
        <h2>Vista previa del mes</h2>
        <form action="/index.php" method="GET" class="scheduled-period">
            <input type="hidden" name="menu" value="mantenimiento_pagos">
            <label>Mes<select name="month"><?php foreach($mesesPlan as $num=>$nombre): ?><option value="<?= $num ?>" <?= $mesPlan===$num?'selected':'' ?> <?= in_array($num,$mesesCerradosPlan,true)?'disabled':'' ?>><?= $nombre ?><?= in_array($num,$mesesCerradosPlan,true)?' · Ya generado':'' ?></option><?php endforeach; ?></select></label>
            <label>Año<input name="year" type="number" min="1900" max="9998" value="<?= $anioPlan ?>" required></label>
            <button type="submit">Ver vista previa</button>
        </form>
        <p class="scheduled-help"><strong><?= $mesesPlan[$mesPlan] ?> <?= $anioPlan ?></strong> · <span>La vista previa no crea registros. Se respetan las frecuencias y los pagos existentes.</span></p>
                <?php if($bloqueadoPlan): ?><p class="scheduled-locked" role="status">Mes bloqueado: sus pagos ya fueron generados.</p><?php endif; ?>
        <details class="scheduled-preview"><summary>Ver detalle de pagos</summary>
            <?php if(!$vistaPlan): ?><p>No hay pagos activos para este mes.</p><?php endif; ?>
            <?php foreach($vistaPlan as $fila): ?><div class="scheduled-preview-row"><div><strong><?= htmlspecialchars($fila['pago']['descripcion']) ?></strong><small><?= date('d/m/Y',strtotime($fila['fecha'])) ?></small></div><span>$<?= number_format((float)$fila['pago']['amount'],2) ?></span><span class="scheduled-badge <?= $fila['existe']?'exists':'pending' ?>"><?= $fila['existe']?'Ya generado':'Por generar' ?></span></div><?php endforeach; ?>
        </details>
        <form action="/mantenimiento/generar_pagos_mes.php" method="POST" class="scheduled-generate">
            <input type="hidden" name="generar_pagos_token" value="<?= htmlspecialchars($_SESSION['generar_pagos_token'],ENT_QUOTES) ?>"><input type="hidden" name="month" value="<?= $mesPlan ?>"><input type="hidden" name="year" value="<?= $anioPlan ?>">
            <div><strong><span>Importe por generar</span>: $<?= number_format($importePendientePlan,2) ?></strong><p class="scheduled-help">Los pagos existentes y los registros ya pagados no se modifican.</p></div>
            <button type="submit" <?= ($bloqueadoPlan || $pendientesPlan===0)?'disabled':'' ?>><?= $bloqueadoPlan ? 'Mes ya generado' : 'Generar pagos' ?></button>
        </form>
    </section>
    <section class="scheduled-panel">
        <h2>Plantillas de pagos</h2>
        <div class="scheduled-filters"><label>Buscar<input type="search" id="scheduled-search" placeholder="Buscar por descripción o categoría"></label><label>Frecuencia<select id="scheduled-frequency"><option value="">Todas</option><?php foreach($frecuenciasPlan as $valor=>$nombre): ?><option value="<?= $valor ?>"><?= $nombre ?></option><?php endforeach; ?></select></label><label>Estado<select id="scheduled-status"><option value="">Todos</option><option value="1">Activo</option><option value="0">Pausado</option></select></label></div>
        <p class="scheduled-help"><span id="scheduled-count"><?= count($pagos) ?></span> <span>pagos visibles</span></p>
        <div class="scheduled-grid">
        <?php foreach($pagos as $pago): ?><article class="scheduled-card" data-payment data-search="<?= htmlspecialchars($pago['descripcion'].' '.$pago['categoria'],ENT_QUOTES) ?>" data-frequency="<?= htmlspecialchars($pago['frecuencia'],ENT_QUOTES) ?>" data-status="<?= (int)$pago['activo'] ?>">
            <header><h3><?= htmlspecialchars($pago['descripcion']) ?></h3><span class="scheduled-badge <?= (int)$pago['activo']?'exists':'paused' ?>"><?= (int)$pago['activo']?'Activo':'Pausado' ?></span></header>
            <strong class="scheduled-amount">$<?= number_format((float)$pago['amount'],2) ?></strong>
            <dl><div><dt>Categoría</dt><dd><?= htmlspecialchars($pago['categoria']??'Otros') ?></dd></div><div><dt>Frecuencia</dt><dd><?= $frecuenciasPlan[$pago['frecuencia']]??htmlspecialchars($pago['frecuencia']) ?></dd></div><div><dt>Fecha límite</dt><dd><?= $pago['due_date']?date('d/m/Y',strtotime($pago['due_date'])):'—' ?></dd></div></dl>
            <details><summary>Editar pago</summary><?php $editorPlan($pago); ?></details>
        </article><?php endforeach; ?>
        </div><p id="scheduled-empty" hidden>No hay pagos que coincidan con los filtros.</p>
    </section>
    <section class="scheduled-panel" id="nuevo-pago"><details id="scheduled-new"><summary>Nuevo pago</summary><?php $editorPlan([]); ?></details></section>
</div>
<script src="/assets/js/scheduled-payments.js?v=1"></script>
</body></html>