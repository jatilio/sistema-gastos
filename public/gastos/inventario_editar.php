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

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $update = $pdo->prepare("
        UPDATE inventario
        SET nombre = ?, categoria = ?, cantidad_actual = ?, unidad = ?, cantidad_minima = ?, ubicacion = ?, fecha_compra = ?, fecha_vencimiento = ?
        WHERE id = ? AND usuario_id = ?
    ");

    $update->execute([
        $_POST['nombre'],
        $_POST['categoria'],
        $_POST['cantidad_actual'],
        $_POST['unidad'],
        $_POST['cantidad_minima'],
        $_POST['ubicacion'],
        $_POST['fecha_compra'],
        $_POST['fecha_vencimiento'],
        $id,
        $usuario_id
    ]);

    header("Location: inventario.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>

<body>

<h2>Editar Producto</h2>

<form method="POST">

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= $item['nombre'] ?>" required>

    <label>Categoría:</label>
    <input type="text" name="categoria" value="<?= $item['categoria'] ?>" required>

    <label>Cantidad actual:</label>
    <input type="number" step="0.01" name="cantidad_actual" value="<?= $item['cantidad_actual'] ?>" required>

    <label>Unidad:</label>
    <input type="text" name="unidad" value="<?= $item['unidad'] ?>" required>

    <label>Cantidad mínima:</label>
    <input type="number" step="0.01" name="cantidad_minima" value="<?= $item['cantidad_minima'] ?>" required>

    <label>Ubicación:</label>
    <input type="text" name="ubicacion" value="<?= $item['ubicacion'] ?>">

    <label>Fecha de compra:</label>
    <input type="date" name="fecha_compra" value="<?= $item['fecha_compra'] ?>">

    <label>Fecha de vencimiento:</label>
    <input type="date" name="fecha_vencimiento" value="<?= $item['fecha_vencimiento'] ?>">

    <button class="btn" type="submit">Guardar Cambios</button>
</form>

<a class="btn" href="inventario.php">Volver</a>

</body>
</html>
