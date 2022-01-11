<?php
require "includes/conexion.php";
$temp = ObtenerVariable("CarpetaTmp");

// print_r($_FILES);
$imgID = date("Ymd_His");

$path = $_FILES['image']['name'];
$imgFN = pathinfo($path, PATHINFO_FILENAME);
$imgEXT = pathinfo($path, PATHINFO_EXTENSION);

$nombreArchivo = NormalizarNombreImagen($imgID, $imgFN, $imgEXT);
sqlsrv_close($conexion);

$route = $temp . "/" . $_SESSION['CodUser'] . "/images/";

if (!file_exists($route)) {
    mkdir($route, 0777);
}

if (($_FILES["image"]["type"] == "image/pjpeg")
    || ($_FILES["image"]["type"] == "image/jpeg")
    || ($_FILES["image"]["type"] == "image/png")
    || ($_FILES["image"]["type"] == "image/gif")) {
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $route . $nombreArchivo)) {
        echo $route . $nombreArchivo;
    } else {
        echo 0;
    }
} else {
    echo 0;
}
