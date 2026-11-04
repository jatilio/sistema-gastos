<?php
require_once __DIR__ . '/../config/seguridad_web.php';

date_default_timezone_set('America/New_York'); // Asegura que use tu zona horaria
echo date("Y-m-d H:i:s");