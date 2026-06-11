<?php
// config.php
$conn = new mysqli("localhost", "root", "root", "finanzas");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
