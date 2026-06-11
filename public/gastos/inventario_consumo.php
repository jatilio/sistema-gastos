<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay sesión iniciada.");
}

$usuario_id = $_SESSION['usuario_id'];

if (!isset($_GET['id'])) {
    die("Error: Falta ID.");
}

$id = $_GET['id'];

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT * FROM inventario WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Producto no encontrado.");
}

// Procesar consumo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $consumo = floatval($_POST['consumo']);
    $nuevo_total = $item['cantidad_actual'] - $consumo;

    if ($nuevo_total < 0) {
        $nuevo_total = 0;
    }

    $update = $pdo->prepare("
        UPDATE inventario
        SET cantidad_actual = ?
        WHERE id = ? AND usuario_id = ?
    ");

    $update->execute([$nuevo_total, $id, $usuario_id]);

    header("Location: inventario.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Consumo</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>

<body>

<h2>Registrar Consumo de: <?= htmlspecialchars($item['nombre']) ?></h2>

<form method="POST">

    <label>Cantidad a consumir:</label>
    <input type="number" step="0.01" name="consumo" required>

    <button class="btn" type="submit">Registrar</button>
</form>

<a class="btn" href="inventario.php">Volver</a>

</body>
</html>
