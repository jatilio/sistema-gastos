<?php
// Ruta antigua: usar las mismas validaciones y permisos que el formulario actual.
if(isset($_POST['id'])&&!isset($_POST['gasto_id']))$_POST['gasto_id']=$_POST['id'];
require __DIR__.'/editar_gasto_tarjeta.php';
