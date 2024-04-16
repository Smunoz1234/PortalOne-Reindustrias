<?php require("includes/conexion.php");
sqlsrv_close($conexion);
?>
<!DOCTYPE html>
<html>

<head>
<?php include("includes/cabecera.php"); ?>
<title><?php echo NOMBRE_PORTAL;?> | Error 404</title>
</head>

<body class="gray-bg">


    <div class="middle-box text-center animated fadeInDown">
        <h1 class="logo-name"><img src="img/img_logo.png" alt=""/></h1>
        <h1>404</h1>
        <h3 class="font-bold">Página no encontrada</h3>

        <div class="error-desc">
            Lo sentimos, pero la página que está buscando no ha sido encontrada. Prueba comprobando la URL, luego pulsa el botón de actualización en tu navegador o intenta encontrar algo más en nuestra aplicación.
        </div>
        <br><br>
         <a href="index1.php" class="btn btn-primary btn-outline"><i class="fa fa-home"></i> Volver al Inicio</a>
    </div>
</body>

</html>
