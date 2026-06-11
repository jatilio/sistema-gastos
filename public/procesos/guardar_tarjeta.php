<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    die("ERROR: No hay sesión activa.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("ERROR: No es POST.");
}

// Recibir datos
$banco        = $_POST['banco'] ?? null;
$nombre       = $_POST['nombre_tarjeta'] ?? null;
$tipo         = $_POST['tipo'] ?? null;
$limite       = $_POST['limite'] ?? null;
$dia_corte    = $_POST['dia_corte'] ?? null;
$dia_pago     = $_POST['dia_pago'] ?? null;
$marca        = $_POST['marca'] ?? null;
$ultimos_4    = $_POST['ultimos_4'] ?? null;
$estado       = $_POST['estado'] ?? 'activa';

// Normalizar últimos 4
if ($ultimos_4 !== null) {
    $ultimos_4 = preg_replace('/\D/', '', $ultimos_4);
    if ($ultimos_4 === '') $ultimos_4 = null;
}

// Si no es crédito, limpiar campos
if ($tipo !== 'credito') {
    $limite = null;
    $dia_corte = null;
    $dia_pago = null;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "INSERT INTO tarjetas 
            (usuario_id, banco, nombre_tarjeta, tipo, limite, dia_corte, dia_pago, marca, ultimos_4, estado)
            VALUES 
            (:usuario_id, :banco, :nombre, :tipo, :limite, :dia_corte, :dia_pago, :marca, :ultimos_4, :estado)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':usuario_id' => $usuario_id,
        ':banco'      => $banco,
        ':nombre'     => $nombre,
        ':tipo'       => $tipo,
        ':limite'     => $limite,
        ':dia_corte'  => $dia_corte,
        ':dia_pago'   => $dia_pago,
        ':marca'      => $marca,
        ':ultimos_4'  => $ultimos_4,
        ':estado'     => $estado
    ]);

    header("Location: ../index.php?menu=tarjetas&msg=ok");
    exit;

} catch (PDOException $e) {

    // Guardar error en archivo
    file_put_contents(
        __DIR__ . "/error_sql.txt",
        date('Y-m-d H:i:s') . " ERROR SQL:\n" . $e->getMessage() . "\n\n",
        FILE_APPEND
    );

    // Mostrar error en pantalla
    die("ERROR SQL: " . $e->getMessage());
}