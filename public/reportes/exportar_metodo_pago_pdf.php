<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../config/db.php");
require_once("../../vendor/autoload.php");

use Dompdf\Dompdf;
use Dompdf\Options;

$usuario_id = $_SESSION['usuario_id'] ?? null;

if (!$usuario_id) {
    die("No hay sesión activa.");
}

$month = $_GET['month'] ?? date('m');
$year  = $_GET['year'] ?? date('Y');

// ===============================
//  OBTENER DATOS
// ===============================
$sql = "
    SELECT descripcion, amount, due_date, metodo_pago, tipo, pagado
    FROM gastos
    WHERE usuario_id = :usuario_id
      AND MONTH(due_date) = :month
      AND YEAR(due_date) = :year
    ORDER BY due_date ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':usuario_id' => $usuario_id,
    ':month' => $month,
    ':year' => $year
]);

$gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================
//  HTML DEL PDF
// ===============================
ob_start();
?>

<style>
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
}

h2 {
    text-align: center;
    margin-bottom: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    border: 1px solid #ccc;
    padding: 6px;
}

th {
    background: #f0f0f0;
}

.sin-metodo {
    background: #fff3cd;
}
</style>

<h2>Reporte por Método de Pago — <?= "$month/$year" ?></h2>

<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Descripción</th>
            <th>Método</th>
            <th>Tipo</th>
            <th>Pagado</th>
            <th>Monto</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($gastos as $g): ?>
            <tr class="<?= !$g['metodo_pago'] ? 'sin-metodo' : '' ?>">
                <td><?= htmlspecialchars($g['due_date']) ?></td>
                <td><?= htmlspecialchars($g['descripcion']) ?></td>
                <td><?= $g['metodo_pago'] ? ucfirst($g['metodo_pago']) : 'Sin método' ?></td>
                <td><?= $g['tipo'] ?></td>
                <td><?= $g['pagado'] === 'Si' ? '✔' : 'No' ?></td>
                <td>$<?= number_format($g['amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$html = ob_get_clean();

// ===============================
//  GENERAR PDF
// ===============================
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$dompdf->stream("reporte_metodo_pago_$month-$year.pdf", ["Attachment" => true]);
exit;