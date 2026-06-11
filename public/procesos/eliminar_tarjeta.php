<?php
session_start();
require_once("../../config/db.php");

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM tarjetas WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $_SESSION['usuario_id']]);

header("Location: ../index.php?menu=tarjetas&msg=eliminada");
exit;