<?php
// El flujo por quincenas fue reemplazado por las plantillas mensuales.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['usuario_id'])) {
    header('Location: /auth/login.php');
    exit;
}
header('Location: /mantenimiento_pagos.php', true, 303);
exit;