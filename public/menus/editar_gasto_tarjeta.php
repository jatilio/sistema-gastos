<?php
require_once __DIR__ . '/../../config/seguridad_web.php';

require_once __DIR__.'/../../config/seguridad_formularios.php';
$propietario=exigirSesionFinanzas();$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
$q=$pdo->prepare('SELECT * FROM gastos WHERE id=? AND usuario_id=? AND tarjeta_id IS NOT NULL');$q->execute([$id,$propietario]);$gasto=$q->fetch(PDO::FETCH_ASSOC);
if(!$gasto){http_response_code(404);echo '<p>Gasto no encontrado.</p>';return;}
$q=$pdo->prepare('SELECT id,nombre_tarjeta,banco FROM tarjetas WHERE usuario_id=? ORDER BY nombre_tarjeta');$q->execute([$propietario]);$tarjetasEditar=$q->fetchAll(PDO::FETCH_ASSOC);
$esc=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<section class="tarjeta-gastos-container"><h1>Editar gasto de tarjeta</h1><form method="post" action="/procesos/editar_gasto_tarjeta.php" class="form-tarjeta-gasto"><input type="hidden" name="formulario_token" value="<?= $esc(tokenFormularioFinanzas()) ?>"><input type="hidden" name="gasto_id" value="<?= (int)$gasto['id'] ?>"><div class="form-grid"><label>Tarjeta<select name="tarjeta_id" required><?php foreach($tarjetasEditar as $t): ?><option value="<?= (int)$t['id'] ?>" <?= $t['id']==$gasto['tarjeta_id']?'selected':'' ?>><?= $esc($t['nombre_tarjeta'].' · '.$t['banco']) ?></option><?php endforeach; ?></select></label><label>Descripción<input name="descripcion" maxlength="150" required value="<?= $esc($gasto['descripcion']) ?>"></label><label>Monto<input name="monto" type="number" min="0.01" max="99999999.99" step="0.01" required value="<?= $esc($gasto['amount']) ?>"></label><label>Fecha<input name="fecha" type="date" required value="<?= $esc($gasto['due_date']) ?>"></label><label>Pagado<select name="pagado"><option value="No" <?= $gasto['pagado']!=='Si'?'selected':'' ?>>No</option><option value="Si" <?= $gasto['pagado']==='Si'?'selected':'' ?>>Sí</option></select></label></div><button type="submit" class="btn-guardar">Guardar cambios</button> <a href="/index.php?menu=tarjetas_gastos">Cancelar</a></form></section>
