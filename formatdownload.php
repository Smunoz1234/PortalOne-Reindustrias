<?php
if (isset($_GET['file']) && $_GET['file'] != "") {
	require_once("includes/conexion.php");

	$Parametros = array(
		'pIdObjeto' => base64_decode($_REQUEST['ObType']), //Codigo del objeto
		'pIdFormato' => base64_decode($_REQUEST['IdFrm']), //Id del formato (Serie)
		'pDockey' => base64_decode($_REQUEST['DocKey']), //DocEntry del documento
		'pID' => (isset($_REQUEST['IdReg'])) ? base64_decode($_REQUEST['IdReg']) : '', //Id de la tabla de formatos (para cuando hay varios formatos de la misma serie)
		'pUsuario' => $_SESSION['User']
	);

	print_r($Parametros);
	exit();

	$file = base64_decode($_GET['file']);
	
	$Result = DescargarFileAPI($file);
	$dir_temp = CrearObtenerDirTemp();
	$filename = $dir_temp . $_SESSION['User'] . '.pdf';

	// echo "$file<br>";
	// echo $filename;
	// exit();

	file_put_contents($filename, $Result);
	$NombreArchivo = $_SESSION['User'] . "_" . date('YmdHi') . '.pdf';

	$size = filesize($filename);

	header("Content-Transfer-Encoding: binary");
	//header("Content-type: application/octet-stream");
	header('Content-type: application/pdf', true);
	header("Content-Type: application/force-download");
	header('Content-Disposition: attachment; filename="' . $NombreArchivo . '"');
	header("Content-Length: $size");
	readfile($filename);

	//echo $filename;
}
