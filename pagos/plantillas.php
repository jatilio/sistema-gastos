<?php
session_start();
require_once "../config/db.php";

$usuario_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT * FROM pagos_plantilla
    WHERE usuario_id = ?
    ORDER BY descripcion
");
$stmt->execute([$usuario_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Plantillas de Pagos</title>
<link rel="stylesheet" href="../assets/css/plantillas.css">
</head>
<body>

<h2>📋 Plantillas de Pagos</h2>

<a href="plantilla_nueva.php" class="btn">➕ Nuevo ítem</a>
<button onclick="location.href='../generar_gastos.php'" class="btn btn-primary">
⚙️ Generar gastos del mes
</button>

<table class="excel">
<thead>
<tr>
    <th>#</th>
    <th>Descripción</th>
    <th>Categoría</th>
    <th>Monto</th>
    <th>Día pago</th>
    <th>Estado</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $i => $p): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td contenteditable><?= htmlspecialchars($p['descripcion']) ?></td>
    <td><?= htmlspecialchars($p['categoria']) ?></td>
    <td contenteditable>$<?= number_format($p['monto_default'],2) ?></td>
    <td><?= $p['dia_pago'] ?></td>
    <td><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</body>
</html>
