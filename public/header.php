<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gastos</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos globales -->
    <link rel="stylesheet" href="/assets/css/finance-ui.css?v=1">
<link rel="stylesheet" href="/assets/css/finance-responsive.css?v=1">
<script src="/assets/js/finance-interface.js?v=1"></script>
<script src="/assets/js/finance-language.js?v=2"></script>
</head>

<body class="finance-standalone">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/index.php">Gastos</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">

                <li class="nav-item">
                    <a class="nav-link" href="/gastos/adicionales.php">Adicionales</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/index.php?menu=mantenimiento_pagos">Pagos</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/index.php?menu=reportes">Reportes</a>
                </li>

            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
