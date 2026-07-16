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

    <!-- CSS general -->
    <link rel="stylesheet" href="../assets/css/estilos.css">

    <!-- CSS exclusivo del formulario -->
    <link rel="stylesheet" href="../assets/css/inventario_agregar.css">
</head>

<body>
    

<h2>Agregar Producto al Inventario</h2>

<div class="form-card">

    <form method="POST">

        <div class="form-grid">

            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" name="nombre" required>
            </div>

            <div class="form-group">
                <label>Categoría:</label>
                <input type="text" name="categoria" required>
            </div>

            <div class="form-group">
                <label>Cantidad actual:</label>
                <input type="number" step="0.01" name="cantidad_actual" required>
            </div>

            <div class="form-group">
                <label>Unidad:</label>
                <input type="text" name="unidad" required>
            </div>

            <div class="form-group">
                <label>Cantidad mínima:</label>
                <input type="number" step="0.01" name="cantidad_minima" required>
            </div>

            <div class="form-group">
                <label>Ubicación:</label>
                <input type="text" name="ubicacion">
            </div>

            <div class="form-group">
                <label>Fecha de compra:</label>
                <input type="date" name="fecha_compra">
            </div>

            <div class="form-group">
                <label>Fecha de vencimiento:</label>
                <input type="date" name="fecha_vencimiento">
            </div>

        </div>

        <button class="btn-guardar" type="submit">Guardar</button>
        <a class="btn-volver" href="inventario.php">Volver</a>

    </form>

</div>

</body>






</html>
