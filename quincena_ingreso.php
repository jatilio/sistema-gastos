<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: auth/login.php");
    exit;
}

require_once "config/db.php";

$usuario_id = $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['usuario_nombre'];

$year = date("Y");
$month = date("m");
$day = date("d");
$quincena = ($day <= 15) ? 1 : 2;

$mensaje = "";

/* 🔒 Si ya existe quincena → NO permitir entrar */
$stmt = $pdo->prepare("
    SELECT id
    FROM quincenas
    WHERE usuario_id = ?
      AND year = ?
      AND month = ?
      AND quincena = ?
    LIMIT 1
");
$stmt->execute([$usuario_id, $year, $month, $quincena]);

if ($stmt->fetch()) {
    header("Location: index.php");
    exit;
}

/* Guardar ingreso */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingreso = floatval($_POST['ingreso']);

    if ($ingreso <= 0) {
        $mensaje = "⚠️ El ingreso debe ser mayor a 0";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO quincenas (usuario_id, year, month, quincena, ingreso)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$usuario_id, $year, $month, $quincena, $ingreso]);

        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ingreso Quincenal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-body">

<div class="login-container">
    <h2>💵 Ingreso Quincenal</h2>
    <p>
        <?= $quincena === 1 ? 'Primera' : 'Segunda' ?> quincena
        de <?= date("F Y") ?>
    </p>

    <?php if ($mensaje): ?>
        <p class="error"><?= $mensaje ?></p>
    <?php endif; ?>

    <form method="POST">
        <input
            type="number"
            step="0.01"
            name="ingreso"
            placeholder="Monto ganado"
            required
        >
        <button type="submit">Guardar ingreso</button>
    </form>
</div>

</body>
</html>
