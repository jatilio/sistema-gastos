<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    die("No hay sesión activa.");
}

// Obtener todas las tarjetas activas del usuario
$sqlTarjetas = "
    SELECT id, banco, nombre_tarjeta, tipo, limite, saldo_actual
    FROM tarjetas
    WHERE usuario_id = :usuario_id
      AND estado = 'activa'
    ORDER BY banco, nombre_tarjeta
";

$stmt = $pdo->prepare($sqlTarjetas);
$stmt->execute([':usuario_id' => $usuario_id]);
$tarjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado = [];

foreach ($tarjetas as $t) {

    $tarjeta_id = $t['id'];
    $nombre_tarjeta = $t['nombre_tarjeta'];
    $saldo_inicial = (float)$t['saldo_actual'];

    // ============================
    // COMPRAS NUEVAS
    // ============================
    $sqlCompras = "
        SELECT SUM(amount)
        FROM gastos
        WHERE usuario_id = :usuario_id
          AND tarjeta_id = :tarjeta_id
          AND metodo_pago IN ('credito','debito')
          AND categoria IN ('Tarjeta','Adicional')
    ";

    $stmtC = $pdo->prepare($sqlCompras);
    $stmtC->execute([
        ':usuario_id' => $usuario_id,
        ':tarjeta_id' => $tarjeta_id
    ]);

    $compras = (float)$stmtC->fetchColumn();

    // ============================
    // PAGOS NUEVOS
    // ============================
    $sqlPagos = "
        SELECT SUM(amount)
        FROM gastos
        WHERE usuario_id = :usuario_id
          AND categoria = 'Tarjeta de crédito'
          AND pagado = 'Si'
          AND descripcion LIKE CONCAT('%', :nombre_tarjeta, '%')
    ";

    $stmtP = $pdo->prepare($sqlPagos);
    $stmtP->execute([
        ':usuario_id' => $usuario_id,
        ':nombre_tarjeta' => $nombre_tarjeta
    ]);

    $pagos = (float)$stmtP->fetchColumn();

    // ============================
    // SALDO ACTUAL FINAL
    // ============================
    if ($t['tipo'] === 'credito') {
        $saldo_actual = $saldo_inicial + $compras - $pagos;
    } else {
        $saldo_actual = $saldo_inicial + $compras;
    }

    // ============================
    // DISPONIBLE
    // ============================
    $disponible = (float)$t['limite'] - (float)$saldo_actual;

    $resultado[] = [
        'banco' => $t['banco'],
        'tarjeta' => $t['nombre_tarjeta'],
        'limite' => (float)$t['limite'],
        'saldo_actual' => (float)$saldo_actual,
        'disponible' => (float)$disponible
    ];
}

?>

<link rel="stylesheet" href="assets/css/reporte_tarjetas.css">

<div class="reporte-container">

    <h2 class="titulo-reporte">📊 Control de Tarjetas</h2>

    <table class="tabla-tarjetas">
        <thead>
            <tr>
                <th>Banco</th>
                <th>Tarjeta</th>
                <th>Límite</th>
                <th>Saldo Actual</th>
                <th>Disponible</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($resultado as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['banco']) ?></td>
                    <td><?= htmlspecialchars($r['tarjeta']) ?></td>

                    <td>$<?= number_format((float)$r['limite'], 2) ?></td>
                    <td>$<?= number_format((float)$r['saldo_actual'], 2) ?></td>

                    <td class="<?= $r['disponible'] < 0 ? 'negativo' : '' ?>">
                        $<?= number_format((float)$r['disponible'], 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>