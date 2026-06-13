<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../../config/db.php";

$usuario_id = $_SESSION['usuario_id'];

$mes  = isset($_GET['mes'])  ? intval($_GET['mes'])  : intval(date("m"));
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date("Y"));

$mes_str = str_pad($mes, 2, "0", STR_PAD_LEFT);
$inicio  = "$anio-$mes_str-01";

/* ============================================================
   CONSULTA PRINCIPAL
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
        notes
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

<link rel="stylesheet" href="../assets/css/ingresar.css">

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


<h2>Ingresar Gastos</h2>



<form method="GET" action="/gastos/ingresar.php"
      style="margin-bottom: 20px; display:flex; gap:10px; align-items:center;">


    <label>Mes:</label>
    <select name="mes">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($m == $mes) ? 'selected' : '' ?>>
                <?= date("F", mktime(0,0,0,$m,1)) ?>
            </option>
        <?php endfor; ?>
    </select>

    <label>Año:</label>
    <select name="anio">
        <?php for ($a = date("Y") - 5; $a <= date("Y") + 1; $a++): ?>
            <option value="<?= $a ?>" <?= ($a == $anio) ? 'selected' : '' ?>>
                <?= $a ?>
            </option>
        <?php endfor; ?>
    </select>

    <button type="submit">Filtrar</button>

</form>



<div class="table-container">
<table>
    <tr>
        <th>#</th>
        <th>Descripción</th>
        <th>Fecha Vencimiento</th>
        <th>Monto</th>
        <th>Pagado</th>
        <th>Fecha Pago</th>
        <th>Confirmación</th>
        <th>Tarjeta</th>
        <th>Notes</th>
        <th>Acción</th>
    </tr>

<?php 
$i = 1;
foreach ($result as $row): 
    $clase = ($row['pagado'] == 'Si') ? "pagado" : "nopagado";
?>

<form id="form<?= $row['id'] ?>" action="/gastos/ingresar_guardar.php" method="POST">
<tr id="fila<?= $row['id'] ?>" class="<?= $clase ?>">

    <input type="hidden" name="id" value="<?= $row['id'] ?>">

    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($row['descripcion']) ?></td>
    <td><?= $row['due_date'] ?></td>
    <td>$<?= number_format($row['amount'], 2) ?></td>
    <td><?= $row['pagado'] ?></td>

    <!-- FECHA PAGO -->
    <td id="fp<?= $row['id'] ?>">
        <?= $row['payment_date'] ?>
    </td>

    <!-- CONFIRMACION -->
    <td id="cf<?= $row['id'] ?>">
        <?= $row['confirmation'] ?>
    </td>

    <!-- TARJETA -->
    <td id="tp<?= $row['id'] ?>">
        <?= $row['banco_pago'] ?>
    </td>

    <!-- NOTES -->
    <td id="nt<?= $row['id'] ?>">
        <?= $row['notes'] ?>
    </td>

    <td id="ac<?= $row['id'] ?>">
        <?php if ($row['pagado'] == 'No'): ?>
            <button type="button" class="btn-pagar" onclick="habilitar(<?= $row['id'] ?>)">Pagar</button>
        <?php else: ?>
            ✔
        <?php endif; ?>
    </td>

</tr>
</form>

<?php endforeach; ?>
</table>
</div>

<script>
function habilitar(id) {

    const form = document.getElementById("form" + id);

    // LIMPIAR LA FILA
    document.getElementById("fp" + id).innerHTML = "";
    document.getElementById("cf" + id).innerHTML = "";
    document.getElementById("tp" + id).innerHTML = "";
    document.getElementById("nt" + id).innerHTML = "";

    // CREAR INPUTS DENTRO DEL FORM
    form.innerHTML += `
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="row" value="${id}">

        <input type="date" name="payment_date" id="payment_date_${id}" style="display:none;">
        <input type="text" name="confirmation" id="confirmation_${id}" style="display:none;">
        <select name="banco_pago" id="banco_pago_${id}" style="display:none;">
            <option value="">Seleccione...</option>
            <?php foreach ($tarjetas as $t): ?>
                <option value="<?= $t['nombre_tarjeta'] ?>"><?= $t['nombre_tarjeta'] ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="notes" id="notes_${id}" style="display:none;">
    `;

    // MOSTRAR INPUTS EN LA TABLA (VISUAL)
    document.getElementById("fp" + id).innerHTML =
        '<input class="inline-input" type="date" onchange="document.getElementById(\'payment_date_'+id+'\').value=this.value">';

    document.getElementById("cf" + id).innerHTML =
        '<input class="inline-input" type="text" onchange="document.getElementById(\'confirmation_'+id+'\').value=this.value">';

    let html = `<select class="inline-input" onchange="document.getElementById('banco_pago_${id}').value=this.value">
                    <option value="">Seleccione...</option>`;
    <?php foreach ($tarjetas as $t): ?>
        html += `<option value="<?= $t['nombre_tarjeta'] ?>"><?= $t['nombre_tarjeta'] ?></option>`;
    <?php endforeach; ?>
    html += `</select>`;
    document.getElementById("tp" + id).innerHTML = html;

    document.getElementById("nt" + id).innerHTML =
        '<input class="inline-input" type="text" onchange="document.getElementById(\'notes_'+id+'\').value=this.value">';

    // BOTÓN GUARDAR
    document.getElementById("ac" + id).innerHTML =
        '<button class="btn-guardar" type="button" onclick="document.getElementById(\'form'+id+'\').submit()">Guardar</button>';
}


</script>

</body>
</html>
