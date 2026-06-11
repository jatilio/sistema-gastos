<?php
// Determina si es primera o segunda quincena
function obtenerQuincena() {
    $dia = date('d');
    return ($dia <= 15) ? 1 : 2;
}

// Obtener saldo anterior de la quincena
function obtenerSaldoAnterior($conn, $year, $month, $quincena) {
    $res = $conn->query("SELECT saldo_final FROM quincenas WHERE year=$year AND month=$month AND quincena<$quincena ORDER BY year DESC, month DESC, quincena DESC LIMIT 1");
    if($res && $res->num_rows > 0){
        return $res->fetch_assoc()['saldo_final'];
    }
    return 0;
}

// Calcular saldo actual
function calcularSaldoActual($conn, $quincena_id) {
    $res = $conn->query("SELECT saldo_anterior FROM quincenas WHERE id=$quincena_id");
    $saldo = $res->fetch_assoc()['saldo_anterior'];

    $res2 = $conn->query("SELECT SUM(amount) as total_gastos FROM gastos WHERE quincena_id=$quincena_id AND pagado='Yes'");
    $total_gastos = $res2->fetch_assoc()['total_gastos'] ?? 0;

    return $saldo - $total_gastos;
}
?>
