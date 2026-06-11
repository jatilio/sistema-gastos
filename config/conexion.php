<?php
$host = "localhost";
$usuario = "root";
$clave = "root";
$bd = "finanzas";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$bd;charset=$charset";

try {
    $pdo = new PDO($dsn, $usuario, $clave, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>