<?php
require_once __DIR__ . '/../../config/seguridad_web.php';

echo '<link rel="stylesheet" href="assets/css/tarjetas_gastos.css">';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__.'/../../config/seguridad_formularios.php';
exigirSesionFinanzas();
$tokenTarjetas=htmlspecialchars(tokenFormularioFinanzas(),ENT_QUOTES,'UTF-8');

// Obtener tarjetas del usuario
$stmt = $pdo->prepare("SELECT id, banco, nombre_tarjeta FROM tarjetas WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$tarjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener gastos asociados a tarjetas
$stmt2 = $pdo->prepare("
    SELECT g.*, t.nombre_tarjeta, t.banco 
    FROM gastos g
    LEFT JOIN tarjetas t ON g.tarjeta_id = t.id
    WHERE g.usuario_id = ? AND g.tarjeta_id IS NOT NULL
    ORDER BY g.due_date DESC
");
$stmt2->execute([$_SESSION['usuario_id']]);
$gastos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="tarjeta-gastos-container">

    <!-- MENSAJES -->
    <?php if (!empty($_GET['msg'])): ?>
        <div class="alerta-exito">
            ✔
            <?php
                if ($_GET['msg'] === 'ok') echo "Gasto registrado correctamente";
                if ($_GET['msg'] === 'eliminado') echo "Gasto eliminado correctamente";
                if ($_GET['msg'] === 'actualizado') echo "Gasto actualizado correctamente";
            ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <h2 class="titulo-modulo">🧾 Registrar Gasto de Tarjeta</h2>

    <form method="POST" action="procesos/guardar_gasto_tarjeta.php" class="form-tarjeta-gasto">

        <input type="hidden" name="formulario_token" value="<?= $tokenTarjetas ?>">
        <div class="form-grid">

            <div class="form-group">
                <label>Tarjeta</label>
                <select name="tarjeta_id" required>
                    <option value="">Seleccione una tarjeta</option>
                    <?php foreach ($tarjetas as $t): ?>
                        <option value="<?= htmlspecialchars((string)($t['id']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>">
                            <?= htmlspecialchars($t['banco']) ?> — <?= htmlspecialchars($t['nombre_tarjeta']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Fecha del gasto</label>
                <input type="date" name="fecha" required>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <input type="text" name="descripcion" required placeholder="Ej: Supermercado, gasolina…">
            </div>

            <div class="form-group">
                <label>Monto</label>
                <input type="number" step="0.01" name="monto" required>
            </div>

            <div class="form-group">
                <label>Pagado</label>
                <select name="pagado" required>
                    <option value="No">No</option>
                    <option value="Si">Sí</option>
                </select>
            </div>

        </div>

        <button class="btn-guardar">Guardar gasto</button>
    </form>

    <!-- LISTADO -->
    <h2 class="titulo-modulo">📋 Gastos Registrados</h2>

    <div class="tabla-responsive">
        <table class="tabla-tarjetas">
            <thead>
                <tr>
                    <th>Tarjeta</th>
                    <th>Banco</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                    <th>Pagado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($gastos as $g): ?>
                    <tr>
                        <td><?= htmlspecialchars($g['nombre_tarjeta'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($g['banco'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($g['descripcion'] ?? '—') ?></td>
                        <td>$<?= number_format($g['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($g['due_date'] ?? '—') ?></td>
                        <td><?= $g['pagado'] === 'Si' ? '✔' : 'No' ?></td>

                        <td class="acciones">
                            <a href="index.php?menu=editar_gasto_tarjeta&id=<?= htmlspecialchars((string)($g['id']??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8') ?>" class="btn-editar">Editar</a>
                            <form method="post" action="/procesos/eliminar_gasto_tarjeta.php" onsubmit="return confirm('¿Eliminar este gasto?')" style="display:inline"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"><input type="hidden" name="formulario_token" value="<?= $tokenTarjetas ?>"><button type="submit" class="btn-eliminar">Eliminar</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>