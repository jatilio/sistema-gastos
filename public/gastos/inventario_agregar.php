<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Error: No hay sesión iniciada.");
}

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $pdo->prepare("
        INSERT INTO inventario (usuario_id, nombre, categoria, cantidad_actual, unidad, cantidad_minima, ubicacion, fecha_compra, fecha_vencimiento)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $usuario_id,
        $_POST['nombre'],
        $_POST['categoria'],
        $_POST['cantidad_actual'],
        $_POST['unidad'],
        $_POST['cantidad_minima'],
        $_POST['ubicacion'],
        $_POST['fecha_compra'],
        $_POST['fecha_vencimiento']
    ]);

    header("Location: inventario.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>

<body>

<h2>Agregar Producto al Inventario</h2>

<form method="POST">

    <label>Nombre:</label>
    <input type="text" name="nombre" required>

    <label>Categoría:</label>
    <input type="text" name="categoria" required>

    <label>Cantidad actual:</label>
    <input type="number" step="0.01" name="cantidad_actual" required>

    <label>Unidad:</label>
    <input type="text" name="unidad" required>

    <label>Cantidad mínima:</label>
    <input type="number" step="0.01" name="cantidad_minima" required>

    <label>Ubicación:</label>
    <input type="text" name="ubicacion">

    <label>Fecha de compra:</label>
    <input type="date" name="fecha_compra">

    <label>Fecha de vencimiento:</label>
    <input type="date" name="fecha_vencimiento">

    <button class="btn" type="submit">Guardar</button>
</form>

<a class="btn" href="inventario.php">Volver</a>

</body>
</html>
