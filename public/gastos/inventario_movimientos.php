<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay sesión iniciada.");
}

$usuario_id = $_SESSION['usuario_id'];

if (!isset($_GET['id'])) {
    die("Error: Falta ID.");
}

$id = intval($_GET['id']);

// Obtener datos del producto
$stmt = $pdo->prepare("
    SELECT nombre, categoria
    FROM inventario
    WHERE id = ? AND usuario_id = ?
");
$stmt->execute([$id, $usuario_id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    die("Producto no encontrado.");
}

// Obtener movimientos
$mov = $pdo->prepare("
    SELECT tipo, cantidad, notas, fecha
    FROM inventario_movimientos
    WHERE inventario_id = ? AND usuario_id = ?
    ORDER BY fecha DESC
");
$mov->execute([$id, $usuario_id]);
$movimientos = $mov->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos del Producto</title>

    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="../assets/css/inventario.css">

    <style>
        .header-info {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        table td {
            vertical-align: middle;
        }

        .entrada {
            color: #28a745;
            font-weight: bold;
        }

        .salida {
            color: #f44336;
            font-weight: bold;
        }
    </style>
</head>

<body>

<h2>Movimientos del Producto</h2>

<div class="header-info">
    <p><strong>Producto:</strong> <?= htmlspecialchars($producto['nombre']) ?></p>
    <p><strong>Categoría:</strong> <?= htmlspecialchars($producto['categoria']) ?></p>
</div>

<a class="btn" href="inventario.php">⬅ Volver al Inventario</a>

<h3>Historial de Movimientos</h3>

<table>
    <thead>
        <tr>
            <th>Tipo</th>
            <th>Cantidad</th>
            <th>Notas</th>
            <th>Fecha</th>
        </tr>
    </thead>

    <tbody>
        <?php if (empty($movimientos)): ?>
            <tr>
                <td colspan="4" style="text-align:center; padding:20px;">
                    No hay movimientos registrados.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($movimientos as $m): ?>
                <tr>
                    <td>
                        <?php if ($m['tipo'] === 'ENTRADA'): ?>
                            <span class="entrada">ENTRADA</span>
                        <?php else: ?>
                            <span class="salida">SALIDA</span>
                        <?php endif; ?>
                    </td>

                    <td><?= $m['cantidad'] ?></td>
                    <td><?= htmlspecialchars($m['notas']) ?></td>
                    <td><?= $m['fecha'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
