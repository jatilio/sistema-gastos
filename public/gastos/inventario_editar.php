<?php
require_once __DIR__ . '/../../config/seguridad_web.php';

if(session_status()!==PHP_SESSION_ACTIVE)session_start();
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
    <link rel="stylesheet" href="/assets/css/finance-ui.css?v=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/assets/css/finance-responsive.css?v=1">
<script src="/assets/js/finance-interface.js?v=1"></script>
<script src="/assets/js/finance-language.js?v=1"></script>
</head>

<body>

<h2>Editar Producto</h2>

<form method="POST">

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars((string)($item['nombre']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>" required>

    <label>Categoría:</label>
    <input type="text" name="categoria" value="<?= htmlspecialchars((string)($item['categoria']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>" required>

    <label>Cantidad actual:</label>
    <input type="number" step="0.01" name="cantidad_actual" value="<?= htmlspecialchars((string)($item['cantidad_actual']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>" required>

    <label>Unidad:</label>
    <input type="text" name="unidad" value="<?= htmlspecialchars((string)($item['unidad']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>" required>

    <label>Cantidad mínima:</label>
    <input type="number" step="0.01" name="cantidad_minima" value="<?= htmlspecialchars((string)($item['cantidad_minima']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>" required>

    <label>Ubicación:</label>
    <input type="text" name="ubicacion" value="<?= htmlspecialchars((string)($item['ubicacion']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>">

    <label>Fecha de compra:</label>
    <input type="date" name="fecha_compra" value="<?= htmlspecialchars((string)($item['fecha_compra']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>">

    <label>Fecha de vencimiento:</label>
    <input type="date" name="fecha_vencimiento" value="<?= htmlspecialchars((string)($item['fecha_vencimiento']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>">

    <button class="btn" type="submit">Guardar Cambios</button>
</form>

<a class="btn" href="inventario.php">Volver</a>

</body>
</html>
