<?php
session_start();
require_once __DIR__ . "/../../config/db.php"; // conexión PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario  = trim(is_string($_POST['usuario'] ?? null) ? $_POST['usuario'] : '');
    $password = (is_string($_POST['password'] ?? null) ? $_POST['password'] : '');

    // Buscar usuario en la base de datos
    $stmt = $pdo->prepare("SELECT id, usuario, password FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        // Crear sesión
        $_SESSION['usuario_autenticado_id'] = (int)$user['id'];
        $_SESSION['usuario_id'] = (int)$user['id'];
        resolverDatosFinanzas($pdo);
        // El nombre visible lo establece resolverDatosFinanzas, separado del usuario de acceso.

        // 🔥 Redirección correcta al index con menú lateral
        header("Location: ../index.php?menu=dashboard");
        exit;

    } else {

        // Error de login
        $_SESSION['error'] = "❌ Usuario o contraseña incorrectos";
        header("Location: login.php");
        exit;
    }
}
?>