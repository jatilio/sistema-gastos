<?php
require_once "config/db.php"; // tu conexión PDO

$usuario = "admin";
$email = "admin@finanzas.com";
$password_plain = "123456";

// Verificar si ya existe
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario]);
if ($stmt->fetch()) {
    die("El usuario '$usuario' ya existe.\n");
}

// Hashear la contraseña
$hash = password_hash($password_plain, PASSWORD_DEFAULT);

// Insertar usuario
$stmt = $pdo->prepare("INSERT INTO usuarios (usuario, email, password) VALUES (?, ?, ?)");
$stmt->execute([$usuario, $email, $hash]);

echo "Usuario '$usuario' creado correctamente. Contraseña: '$password_plain'";
