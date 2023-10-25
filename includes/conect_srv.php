<?php
if (isset($_POST['BaseDatos']) && $_POST['BaseDatos'] != "") {
	$database = $_POST['BaseDatos'];
} elseif (isset($_GET['bdcode']) && $_GET['bdcode'] != "") { // Para obtener la BD desde recuperar la clave
	$database = base64_decode($_GET['bdcode']);
} elseif (isset($_SESSION['BD']) && $_SESSION['BD'] != "") {
	$database = $_SESSION['BD'];
} else {
	echo "Base de datos invalida.";
	exit();
}
date_default_timezone_set('America/Bogota');
$usuario = 'portalone';
$password = 'N3dug4t3ch#2021*';
$servidor = '172.30.5.10\SQL1';
$connectionInfo = array("UID" => $usuario, "PWD" => $password, "Database" => $database, "CharacterSet" => "UTF-8");
$conexion = sqlsrv_connect($servidor, $connectionInfo);
if ($conexion === false) {
	echo "No es posible conectarse al servidor.</br>";
	exit(print_r(sqlsrv_errors(), true));
}
?>