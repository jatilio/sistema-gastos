<?php
session_start();
require_once("../../config/db.php"); // conexión PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // Buscar usuario en la base de datos
    $stmt = $pdo->prepare("SELECT id, usuario, password FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        // Crear sesión
        $_SESSION['usuario_id']     = $user['id'];
        $_SESSION['usuario_nombre'] = $user['usuario'];

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