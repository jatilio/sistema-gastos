<?php
require_once __DIR__ . '/../../config/seguridad_web.php';

if (session_status() === PHP_SESSION_NONE) {
    if(session_status()!==PHP_SESSION_ACTIVE)session_start();
}

include __DIR__ . "/../../config/db.php";

$usuario_id = $_SESSION['usuario_id'];

$mes  = isset($_GET['mes'])  ? intval($_GET['mes'])  : intval(date("m"));
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date("Y"));

$mes_str = str_pad($mes, 2, "0", STR_PAD_LEFT);
$inicio  = "$anio-$mes_str-01";

/* ============================================================
   CONSULTA PRINCIPAL (AQUÍ SOLO AGREGO validado)
============================================================ */
$sql = "
    SELECT
        id,
        descripcion,
        due_date,
        amount,
        pagado,
        payment_date,
        confirmation,
        banco_pago,
        notes,
        validado
    FROM gastos
    WHERE usuario_id = :usuario_id
      AND tipo = 'Fijo'
      AND due_date BETWEEN :inicio AND LAST_DAY(:inicio)
    ORDER BY due_date ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'usuario_id' => $usuario_id,
    'inicio'     => $inicio
]);

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   CONSULTA TARJETAS DEL USUARIO
============================================================ */
$sqlTarjetas = "
    SELECT UPPER(nombre) AS nombre_tarjeta
    FROM cuentas
    WHERE usuario_id = :usuario_id

    UNION

    SELECT UPPER(nombre_tarjeta)
    FROM tarjetas
    WHERE usuario_id = :usuario_id

    ORDER BY nombre_tarjeta ASC
";

$stmtTar = $pdo->prepare($sqlTarjetas);
$stmtTar->execute(['usuario_id' => $usuario_id]);
$tarjetas = $stmtTar->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ingresar Gastos</title>

<link rel="stylesheet" href="/assets/css/ingresar.css">

<style>
.inline-input {
    width: 130px;
    padding: 4px;
    font-size: 12px;
    border: 1px solid #b8c4d6;
    border-radius: 4px;
}
.btn-guardar {
    background: #198754;
    color: white;
    padding: 5px 12px;
    border: 1px solid #146c43;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
}
.btn-guardar:hover {
    background: #146c43;
}
</style>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/assets/css/finance-responsive.css?v=1">
<link rel="stylesheet" href="/assets/css/ingresar-mobile.css?v=2">
<script src="/assets/js/finance-interface.js?v=1"></script>
<script src="/assets/js/finance-language.js?v=1"></script>
</head>
<body>

<a href="/index.php" 
   style="
       display:inline-block;
       padding:8px 14px;
       background:#4a90e2;
       color:white;
       text-decoration:none;
       border-radius:6px;
       font-weight:bold;
   ">
    ← Regresar al Inicio
</a>

<?php if(isset($_SESSION['aviso_pago'])): ?><p role="status" style="padding:14px;border:1px solid #91badd;border-radius:8px;background:#edf6ff;color:#17496f"><?= htmlspecialchars($_SESSION['aviso_pago'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></p><?php unset($_SESSION['aviso_pago']);endif; ?>
<h2>Ingresar Gastos</h2>
<section class="payment-page" aria-label="Ingresar gastos">

<form class="payment-filters" method="GET" action="/gastos/ingresar.php"
      style="margin-bottom: 20px; display:flex; gap:10px; align-items:center;">

    <label for="payment-month">Mes:</label>
    <select id="payment-month" name="mes">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($m == $mes) ? 'selected' : '' ?>>
                <?= date("F", mktime(0,0,0,$m,1)) ?>
            </option>
        <?php endfor; ?>
    </select>

    <label for="payment-year">Año:</label>
    <select id="payment-year" name="anio">
        <?php for ($a = date("Y") - 5; $a <= date("Y") + 1; $a++): ?>
            <option value="<?= $a ?>" <?= ($a == $anio) ? 'selected' : '' ?>>
                <?= $a ?>
            </option>
        <?php endfor; ?>
    </select>

    <button type="submit">Filtrar</button>

</form>

<div class="finance-table-scroll payment-list">
<table class="payments-table" aria-label="Pagos del mes">
<thead><tr><th>#</th><th>Descripción</th><th>Fecha Vencimiento</th><th>Monto</th><th>Pagado</th><th>Fecha Pago</th><th>Confirmación</th><th>Tarjeta</th><th>Notes</th><th>Acción</th><th>Validado</th></tr></thead>
<tbody>
<?php $i=1; foreach($result as $row): $id=(int)$row['id']; $pendiente=$row['pagado']==='No'; ?>
<tr id="fila<?= $id ?>" class="<?= $pendiente?'nopagado':'pagado' ?>">
<td class="payment-number"><?= $i++ ?></td>
<td class="payment-name"><strong><?= htmlspecialchars($row['descripcion'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></strong></td>
<td><span class="payment-label">Fecha Vencimiento</span><span class="payment-value"><?= htmlspecialchars($row['due_date'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
<td class="payment-amount"><span class="payment-label">Monto</span><strong class="payment-value" id="monto<?= $id ?>" data-amount="<?= htmlspecialchars((string)$row['amount'],ENT_QUOTES,'UTF-8') ?>">$<?= number_format($row['amount'],2) ?></strong></td>
<td><span class="payment-label">Pagado</span><span class="payment-value"><?= htmlspecialchars($row['pagado'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
<?php foreach(['fp'=>['Fecha Pago','payment_date'],'cf'=>['Confirmación','confirmation'],'tp'=>['Tarjeta','banco_pago'],'nt'=>['Notes','notes']] as $clave=>$campo): ?>
<td><span class="payment-label"><?= $campo[0] ?></span><span class="payment-value" id="<?= $clave.$id ?>"><?= htmlspecialchars((string)($row[$campo[1]]??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?></span></td>
<?php endforeach; ?>
<td class="payment-action">
<form id="form<?= $id ?>" action="/gastos/ingresar_guardar.php" method="POST">
<input type="hidden" name="id" value="<?= $id ?>">
<input type="hidden" name="mes" value="<?= $mes ?>"><input type="hidden" name="anio" value="<?= $anio ?>">
<?php if($pendiente): ?><button type="button" class="btn-pagar" onclick="event.preventDefault(); habilitar(<?= $id ?>)">Pagar</button><?php else: ?><span>✔</span><?php endif; ?>
</form>
</td>
<td class="payment-validation"><form action="/gastos/ingresar_guardar.php" method="POST">
<input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="mes" value="<?= $mes ?>"><input type="hidden" name="anio" value="<?= $anio ?>">
<input type="hidden" name="validado" value="0">
<label><input type="checkbox" name="validado" value="1" <?= $row['validado']==1?'checked':'' ?> onchange="this.form.requestSubmit()"><span>Validado</span></label>
</form></td>
</tr>
<?php endforeach; ?>
<?php if(!$result): ?><tr class="payment-empty"><td colspan="11">No hay gastos para este período.</td></tr><?php endif; ?>
</tbody></table>
</div>
</section>

<script>
const tarjetasPagoNombres = <?= finanzasJson(array_column($tarjetas, 'nombre_tarjeta')) ?>;
function habilitar(id) {
 const form = document.getElementById('form'+id);
 if (!form || form.dataset.editando) return;
 form.dataset.editando = '1';
 const campos = [['monto','amount','number','Monto'],['fp','payment_date','date','Fecha Pago'],['cf','confirmation','text','Confirmación'],['tp','banco_pago','select','Tarjeta'],['nt','notes','text','Notes']];
 campos.forEach(([prefijo,nombre,tipo,etiqueta]) => {
  const control = document.createElement(tipo === 'select' ? 'select' : 'input');
  if(tipo !== 'select') control.type = tipo;
  else { control.add(new Option('Seleccione...','')); tarjetasPagoNombres.forEach(n => control.add(new Option(n,n))); }
  control.name=nombre; control.className='inline-input'; control.setAttribute('form',form.id);
  control.setAttribute('aria-label',etiqueta); control.autocomplete='off';
  if(nombre==='payment_date') control.required=true;
  if(nombre==='amount') { control.value=document.getElementById('monto'+id).dataset.amount; control.min='0.01'; control.max='99999999.99'; control.step='0.01'; control.required=true; control.inputMode='decimal'; }
  document.getElementById(prefijo+id).replaceChildren(control);
 });
 const boton=form.querySelector('button');
 boton.textContent='Guardar'; boton.className='btn-guardar'; boton.onclick=null; boton.type='submit';
 document.getElementById('fp'+id).querySelector('input').focus();
}
</script>

</body>
</html>
