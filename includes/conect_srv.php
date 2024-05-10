<?php
// SMM, 10/05/2024
if (file_exists("includes/entorno.php")) {
	require_once "includes/entorno.php";
} else {
	require_once "entorno.php";
}

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

// SMM, 10/05/2024
$connectionInfo = array("UID" => $usuario_bd, "PWD" => $password_bd, "Database" => $database, "CharacterSet" => "UTF-8");
$conexion = sqlsrv_connect($servidor_bd, $connectionInfo);
if ($conexion === false) {
	echo "No es posible conectarse al servidor.</br>";
	exit(print_r(sqlsrv_errors(), true));
}