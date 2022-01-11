<?php
require "includes/conexion.php";
$temp = ObtenerVariable("CarpetaTmp");
sqlsrv_close($conexion);

$route = $temp . "/" . $_SESSION['CodUser'] . "/images/";

if (!file_exists($route)) {
    mkdir($route, 0777);
}

if (($_FILES["file"]["type"] == "image/pjpeg")
    || ($_FILES["file"]["type"] == "image/jpeg")
    || ($_FILES["file"]["type"] == "image/png")
    || ($_FILES["file"]["type"] == "image/gif")) {
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $route . $_FILES['file']['name'])) {
        //more code here...
        echo "images/" . $_FILES['file']['name'];
    } else {
        echo 0;
    }
} else {
    echo 0;
}
