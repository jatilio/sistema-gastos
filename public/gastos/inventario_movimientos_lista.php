<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay sesión iniciada.");
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener todos los productos
$stmt = $pdo->prepare("
    SELECT id, nombre, categoria
    FROM inventario
    WHERE usuario_id = ?
    ORDER BY nombre ASC
");
$stmt->execute([$usuario_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener último movimiento por producto
$mov = $pdo->prepare("
    SELECT tipo, cantidad, fecha
    FROM inventario_movimientos
    WHERE inventario_id = ? AND usuario_id = ?
    ORDER BY fecha DESC
    LIMIT 1
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos del Inventario</title>

    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="../assets/css/inventario.css">

    <style>
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .entrada { color: green; font-weight: bold; }
        .salida { color: red; font-weight: bold; }
    </style>
</head>

<body>

<h2>Movimientos del Inventario</h2>
<a class="btn" href="inventario.php">⬅ Volver al Inventario</a>

<div class="card">
    <h3>Resumen de Movimientos</h3>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Último Movimiento</th>
                <th>Fecha</th>
                <th>Ver</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($productos as $p): ?>
                <?php
                    $mov->execute([$p['id'], $usuario_id]);
                    $ultimo = $mov->fetch(PDO::FETCH_ASSOC);
                ?>

                <tr>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['categoria']) ?></td>

                    <td>
                        <?php if ($ultimo): ?>
                            <?php if ($ultimo['tipo'] === 'ENTRADA'): ?>
                                <span class="entrada">ENTRADA (<?= $ultimo['cantidad'] ?>)</span>
                            <?php else: ?>
                                <span class="salida">SALIDA (<?= $ultimo['cantidad'] ?>)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <em>Sin movimientos</em>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= $ultimo ? $ultimo['fecha'] : '-' ?>
                    </td>

                    <td>
                        <a class="action movimientos" href="inventario_movimientos.php?id=<?= $p['id'] ?>">Ver Historial</a>
                    </td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
