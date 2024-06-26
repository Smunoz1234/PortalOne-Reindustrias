<?php
// SMM, 10/05/2024
if (file_exists("includes/entorno.php")) {
	require_once "includes/entorno.php";
} else {
	require_once "entorno.php";
}

//Datos del portal
if (!isset($_SESSION)) {
	define("NOMBRE_PORTAL", 'PortalOne');
	define("NOMBRE_EMPRESA", 'NEDUGA TECH S.A.S');
} else {
	$Cons_Datos = "EXEC sp_ConsultarDatosPortal";
	$SQL_Datos = sqlsrv_query($conexion, $Cons_Datos);
	$row_Datos = sqlsrv_fetch_array($SQL_Datos);
	define("NOMBRE_PORTAL", $row_Datos['NombrePortal']);
	define("NOMBRE_EMPRESA", $row_Datos['NombreEmpresa']);
	define("NIT_EMPRESA", $row_Datos['NIT']);
	define("SUCURSAL_EMPRESA", $row_Datos['SucursalEmpresa']);

	//Credenciales del servidor de Anexos Windows, para acceder desde Linux
	//define("DOMINIO_WIN","DIALNETCO;");//Debe terminar en ";"
	//define("USER_WIN","Administrador:");//Debe terminar en ":"
	//define("PASS_WIN","Asdf1234$@");//Debe terminar en "@"
	//define("PATH_WIN","192.168.5.200/ReportesPortalOne/");//Debe terminar con el "/"

	define("SO", php_uname('s'));
}

// SMM, 10/05/2024
define("VERSION", "2.1.0");
define("BDPRO", $nombre_bd);
define("BDPRUEBAS", "");