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
    SELECT id, nombre, cantidad_actual, unidad
    FROM inventario
    WHERE id = ? AND usuario_id = ?
");
$stmt->execute([$id, $usuario_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Producto no encontrado.");
}

// Procesar consumo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cantidad_consumida = floatval($_POST['cantidad']);
    $notas = trim($_POST['notas']);

    if ($cantidad_consumida <= 0) {
        die("Cantidad inválida.");
    }

    if ($cantidad_consumida > $item['cantidad_actual']) {
        die("No puedes consumir más de lo que tienes.");
    }

    // Nueva cantidad
    $nueva_cantidad = $item['cantidad_actual'] - $cantidad_consumida;

    // Actualizar inventario
    $update = $pdo->prepare("
        UPDATE inventario
        SET cantidad_actual = ?
        WHERE id = ? AND usuario_id = ?
    ");
    $update->execute([$nueva_cantidad, $id, $usuario_id]);

    // Registrar movimiento
    $mov = $pdo->prepare("
        INSERT INTO inventario_movimientos (inventario_id, usuario_id, tipo, cantidad, notas)
        VALUES (?, ?, 'SALIDA', ?, ?)
    ");
    $mov->execute([$id, $usuario_id, $cantidad_consumida, $notas]);

    // Redirigir
    header("Location: inventario.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consumo de Producto</title>

    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="../assets/css/inventario.css">

    <style>
        .form-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            max-width: 450px;
            margin: auto;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            margin-top: 15px;
            padding: 10px 18px;
            background: #ff9800;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #e68900;
        }
    </style>
</head>

<body>

<h2>Consumo de Producto</h2>
<a class="btn" href="inventario.php">⬅ Volver al Inventario</a>

<div class="form-card">
    <h3><?= htmlspecialchars($item['nombre']) ?></h3>
    <p><strong>Cantidad actual:</strong> <?= $item['cantidad_actual'] . " " . $item['unidad'] ?></p>

    <form method="POST">
        <label>Cantidad a consumir:</label>
        <input type="number" step="0.01" name="cantidad" required>

        <label>Notas (opcional):</label>
        <textarea name="notas" rows="3"></textarea>

        <button type="submit">Registrar Consumo</button>
    </form>
</div>

</body>
</html>
