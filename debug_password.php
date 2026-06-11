<?php
require_once "config/db.php"; // tu conexión PDO

$usuario = "admin";
$password_input = "123456"; // la que estás escribiendo en el login

// Traer hash desde la DB
$stmt = $pdo->prepare("SELECT password FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario]);
$user = $stmt->fetch();

if (!$user) {
    die("Usuario no encontrado");
}

echo "Hash almacenado en DB: " . $user['password'] . "<br>";

// Probar password_verify
if (password_verify($password_input, $user['password'])) {
    echo "✅ La contraseña coincide";
} else {
    echo "❌ La contraseña NO coincide";
}
