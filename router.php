<?php
// Solo para desarrollo: php -S 127.0.0.1:8000 -t public router.php
$ruta = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$base = realpath(__DIR__ . '/public');
$archivo = realpath($base . $ruta);
if (preg_match('~(?:^|/)\.|\.(?:sql|log|txt|ini|bak|zip|env|json)$~i', $ruta) || preg_match('~^/assets/.*\.php$~i', $ruta)) {
 http_response_code(404); exit;
}
if ($archivo !== false && str_starts_with($archivo, $base . DIRECTORY_SEPARATOR) && is_file($archivo)) {
 if (strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) === 'php') { require $archivo; return true; }
 return false;
}
if ($ruta !== '/' && $ruta !== '/index.php') { http_response_code(404); exit; }
require $base . '/index.php';
