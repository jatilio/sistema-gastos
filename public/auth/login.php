<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login | Finanzas</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* --- Reset básico --- */
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

/* --- Fondo degradado animado --- */
body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(-45deg, #667eea, #764ba2, #6b8dd6, #5f72be);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;
    overflow: hidden;
}

@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* --- Caja login --- */
.login-box {
    width: 360px;
    padding: 40px 30px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.2);
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateY(-50px);
    animation: fadeInUp 0.8s forwards;
}

@keyframes fadeInUp {
    to { opacity: 1; transform: translateY(0); }
}

/* --- Título --- */
.login-box h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #333;
    font-size: 26px;
    letter-spacing: 1px;
}

/* --- Inputs con icono --- */
.input-group {
    position: relative;
    margin-bottom: 20px;
}

.input-group i {
    position: absolute;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    color: #999;
    transition: all 0.3s ease;
}

.input-group input {
    width: 100%;
    padding: 12px 15px 12px 40px;
    border-radius: 8px;
    border: 1px solid #ccc;
    transition: all 0.3s ease;
}

.input-group input:focus {
    border-color: #667eea;
    box-shadow: 0 0 8px rgba(102,126,234,0.5);
    outline: none;
}

.input-group input:focus + i {
    color: #667eea;
}

/* --- Botón animado --- */
.login-box button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: #667eea;
    color: #fff;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}

.login-box button:hover {
    background: #5563d6;
    transform: scale(1.03);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* --- Subtítulo --- */
.login-box p {
    margin-top: 15px;
    font-size: 14px;
    color: #666;
    text-align: center;
}
</style>
</head>
<body>

<div class="login-box">
    <h2>Iniciar sesión</h2>
    <form method="POST" action="procesar_login.php">
        <div class="input-group">
            <input type="text" name="usuario" placeholder="Usuario" required>
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="input-group">
            <input type="password" name="password" placeholder="Contraseña" required>
            <i class="fa-solid fa-lock"></i>
        </div>
        <button type="submit">Entrar</button>
    </form>
    <p>Finanzas Personales</p>
</div>

</body>
</html>
