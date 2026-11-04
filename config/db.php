<?php
$local = is_file(__DIR__.'/db.local.php') ? require __DIR__.'/db.local.php' : [];
$host = getenv('FINANZAS_DB_HOST') ?: ($local['host'] ?? '');
$db = getenv('FINANZAS_DB_DB') ?: ($local['db'] ?? '');
$user = getenv('FINANZAS_DB_USER') ?: ($local['user'] ?? '');
$pass = getenv('FINANZAS_DB_PASS') ?: ($local['pass'] ?? '');
$charset = getenv('FINANZAS_DB_CHARSET') ?: ($local['charset'] ?? 'utf8mb4');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    error_log('Conexión Finanzas: '.$e->getMessage());
    http_response_code(503);exit('No se pudo conectar con el servicio. Inténtalo más tarde.');
}

require_once __DIR__.'/datos_compartidos.php';
resolverDatosFinanzas($pdo);

require_once __DIR__.'/filtros_navegacion.php';
conservarFiltrosFinanzas();
