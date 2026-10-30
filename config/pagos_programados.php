<?php
// Compartir fechas entre la vista previa y la generación evita diferencias entre ambas.
function fechaPagoProgramado(array $pago, int $mes, int $anio): ?string {
    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', (string)($pago['due_date'] ?? ''));
    if (!$fecha || $fecha->format('Y-m-d') !== ($pago['due_date'] ?? '') || $mes < 1 || $mes > 12 || $anio < 1900 || $anio > 9998) return null;
    $frecuencia = strtoupper(trim($pago['frecuencia'] ?? ''));
    if (!in_array($frecuencia, ['M','S','A','F','FIJO'], true)) return null;
    if ($frecuencia === 'A' && (int)$fecha->format('n') !== $mes) return null;
    if ($frecuencia === 'S') {
        $distancia = ($anio - (int)$fecha->format('Y')) * 12 + $mes - (int)$fecha->format('n');
        if ($distancia < 0 || $distancia % 6 !== 0) return null;
    }
    $inicio = new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mes));
    return sprintf('%04d-%02d-%02d', $anio, $mes, min((int)$fecha->format('j'), (int)$inicio->format('t')));
}
function categoriasPagosProgramados(PDO $pdo, int $usuario): array {
    $stmt=$pdo->prepare("SELECT DISTINCT categoria FROM mantenimiento_pagos WHERE usuario_id=? AND categoria IS NOT NULL AND TRIM(categoria)<>'' ORDER BY categoria");
    $stmt->execute([$usuario]);
    $valores=array_merge($stmt->fetchAll(PDO::FETCH_COLUMN), ['Vivienda','Servicios','Seguro','Supermercado','Transporte','Salud','Entretenimiento','Membresías','Tarjeta de crédito','Otros']);
    $categorias=[];
    foreach($valores as $valor) $categorias[mb_strtolower(trim($valor))]=trim($valor);
    natcasesort($categorias);
    return array_values($categorias);
}