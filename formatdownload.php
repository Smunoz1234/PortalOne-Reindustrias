<?php
require_once("includes/conexion.php");

/**
 * Descarga un formato desde la API de Crystal Report.
 *
 * @param array  $param   Parámetros para la consulta.
 * @param string $method  Método de solicitud (GET por defecto).
 * @return object         Respuesta de la API en formato JSON.
 */
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
	curl_close($curl);

	if ($cod_http != 200) {
		// Ocurrió un error
		echo "Código $cod_http: ($method)";
		exit();
	}

	// Imprime la consulta a la API junto con el resultado (útil para depuración)
	// echo "<pre>" . print_r($array_res, true) . "</pre>";
	// echo "result: $result<br>";
	// exit();

	return json_decode($result);
}

$Parametros = array(
	'pIdObjeto' => $_REQUEST['ObType'] ?? "",
	'pIdFormato' => $_REQUEST['IdFrm'] ?? "",
	'pDockey' => $_REQUEST['DocKey'] ?? "",
	'pID' => $_REQUEST['IdReg'] ?? "",
	'pUsuario' => $_SESSION['User'] ?? "",
);

// print_r($Parametros);
// exit();

// Consulta a la API con los parámetros dados.
$Result = DescargarFormato($Parametros);

// Validación de respuesta de la API.
if ($Result->Success !== 1) {
	$error_msg = $Result->Mensaje;
	echo "Mensaje: $error_msg";
	exit();
} else {
	// Ruta local de archivos de SAP.
	if (isset($_SESSION['User']) && isset($_SESSION['BD'])) {
		$carp_archivos = ObtenerVariable("RutaArchivos");
		$RutaLocal = $_SESSION['BD'] . "/" . $carp_archivos . "/InformesSAP/";

		// Habilita la visualización de errores para facilitar la depuración
		// error_reporting(E_ALL);
		// ini_set('display_errors', 1);

		// Nombre del archivo a descargar.
		// $NombreArchivo = "OrdenVentaProductos_113710344_NDUARTEG.pdf";
		$NombreArchivo = $Result->Objeto->nombre_archivo;

		// Ruta completa al archivo.
		// $filename = "E:\\PortalOne\\htdocs\\sandbox\\PortalOneReindustrias_Pruebas\\archivos\\InformesSAP\\$NombreArchivo";
		$filename = $RutaLocal . $NombreArchivo;

		// Imprime el nombre del archivo y la ruta (útil para depuración)
		// echo "$NombreArchivo<br>";
		// echo $filename;
		// exit();

		// Elimina cualquier contenido almacenado en el búfer de salida.
		ob_clean();

		// Verifica si el archivo existe antes de intentar leerlo.
		if (file_exists($filename)) {
			// Configura las cabeceras para la descarga del archivo.
			header("Content-Transfer-Encoding: binary");
			header('Content-type: application/pdf');
			header('Content-Disposition: attachment; filename="' . $NombreArchivo . '"');
			header("Content-Length: " . filesize($filename));

			// Lee y envía el contenido del archivo al navegador.
			// echo file_get_contents($filename) or die("No se pudo leer el archivo.");
			readfile($filename) or die("No se pudo leer el archivo.");
		} else {
			// El archivo no existe, muestra un mensaje de error.
			die("El archivo no existe.");
		}
	} else {
		// La sesión no está correctamente configurada, muestra un mensaje de error.
		die("Error de sesión.");
	}
}
