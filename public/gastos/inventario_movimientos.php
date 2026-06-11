<?php
require '../config.php';
require '../auth.php';

$usuario_id = $_SESSION['usuario_id'];

// Obtener movimientos del usuario
$stmt = $pdo->prepare("
    SELECT m.*, i.nombre AS producto, i.unidad
    FROM inventario_movimientos m
    INNER JOIN inventario i ON m.inventario_id = i.id
    WHERE i.usuario_id = ?
    ORDER BY m.fecha DESC
");
$stmt->execute([$usuario_id]);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos del Inventario</title>
    <link rel="stylesheet" href="../assets/css/inventario.css">
</head>

<body>

<h2>Historial de Movimientos</h2>

<a class="btn" href="inventario.php">← Volver al Inventario</a>

<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Producto</th>
            <th>Tipo</th>
            <th>Cantidad</th>
            <th>Descripción</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($movimientos as $m): ?>
            <tr>
                <td><?= $m['fecha'] ?></td>
                <td><?= htmlspecialchars($m['producto']) ?></td>
                <td>
                    <?php if ($m['tipo'] == 'Salida'): ?>
                        <span style="color:red; font-weight:bold;">Salida</span>
                    <?php else: ?>
                        <span style="color:green; font-weight:bold;">Entrada</span>
                    <?php endif; ?>
                </td>
                <td><?= $m['cantidad'] . " " . $m['unidad'] ?></td>
                <td><?= htmlspecialchars($m['descripcion']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
