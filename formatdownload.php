<?php
require_once("includes/conexion.php");
function DescargarFormato($param, $method = 'GET')
{
	$Url = ObtenerVariable('URLCrystalReportAPI');
	$cadenaParametros = implode('/', $param);
	$apiUrl = $Url . "FormatosSAPB1/$cadenaParametros";

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $apiUrl);

	$ApiKey = "ApiKey: 9be50b83-sebf-8818-q9ap-f772f4fca42b";
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $ApiKey));

	if ($method != "GET") {
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	}

	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($curl);

	$cod_http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$array_res = curl_getinfo($curl);

	//echo "Codigo HTTP:" . $cod_http;
	if ($cod_http != 200) { // Ocurrio un error
		echo "Codigo " . $cod_http . ": (" . $method . ") " . $array_res['content_type'];
	}
	curl_close($curl);

	// echo "<pre>" . print_r($array_res, true) . "</pre>";
	// echo "result: $result<br>";
	// exit();

	return json_decode($result);
}

$Parametros = array(
	'pIdObjeto' => $_REQUEST['ObType'] ?? "", // Tipo de documento
	'pIdFormato' => $_REQUEST['IdFrm'] ?? "", // Formato o Serie del documento
	'pDockey' => $_REQUEST['DocKey'] ?? "", // DocEntry del documento
	'pID' => $_REQUEST['IdReg'] ?? "", // Id de la tabla de formatos
	'pUsuario' => $_SESSION['User'] ?? "",
);

// print_r($Parametros);
// exit();

// Consulta a la API con los parametros dados.
$Result = DescargarFormato($Parametros);

// Validación de respuesta de la API.
if ($Result->Success !== 1) {
	echo $Result->Mensaje;
	exit();
} else {
	// Ruta local de archivos de SAP	
	$carp_archivos = ObtenerVariable("RutaArchivos");
	$RutaLocal = $_SESSION['BD'] . "/" . $carp_archivos . "/InformesSAP/";

	// Habilita la visualización de errores para facilitar la depuración
	// error_reporting(E_ALL);
	// ini_set('display_errors', 1);

	// Nombre del archivo a descargar
	// $NombreArchivo = "OrdenVentaProductos_113710344_NDUARTEG.pdf";
	$NombreArchivo = $Result->Objeto->nombre_archivo;

	// Ruta completa al archivo
	// $filename = "E:\\PortalOne\\htdocs\\sandbox\\PortalOneReindustrias_Pruebas\\archivos\\InformesSAP\\$NombreArchivo";
	$filename = $RutaLocal . $NombreArchivo;

	// Imprime el nombre del archivo y la ruta (útil para depuración)
	// echo "$NombreArchivo<br>";
	// echo $filename;
	// exit();

	// Elimina cualquier contenido almacenado en el búfer de salida
	ob_clean();

	// Verifica si el archivo existe antes de intentar leerlo
	if (file_exists($filename)) {
		// Configura las cabeceras para la descarga del archivo
		header("Content-Transfer-Encoding: binary");
		header('Content-type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $NombreArchivo . '"');
		header("Content-Length: " . filesize($filename));

		// Lee y envía el contenido del archivo al navegador
		readfile($filename) or die("No se pudo leer el archivo.");
	} else {
		// El archivo no existe, muestra un mensaje de error
		die("El archivo no existe.");
	}
}