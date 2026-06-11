<?php

$request = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// 1. Buscar dentro de /public (incluye /auth, /assets, etc.)
$publicPath = __DIR__ . '/public' . $request;
if (is_file($publicPath)) {
    return false; // PHP sirve el archivo directamente
}

// 2. Buscar archivos fuera de public (como /mantenimiento)
$rootPath = __DIR__ . $request;
if (is_file($rootPath)) {
    return false;
}

// 3. Si no existe, cargar index.php desde public
require __DIR__ . '/public/index.php';