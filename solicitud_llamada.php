<?php require_once "includes/conexion.php";
PermitirAcceso(334);
$IdSolicitud = "";
$MostrarTodosRecursos = true; // SMM, 02/10/2023
$IncluirCamposAdicionales = PermitirFuncion(332); // SMM, 30/06/2023

// SMM, 09/11/2023
$CrearSolicitud = PermitirFuncion(335);
$ActualizarSolicitud = PermitirFuncion(343);

$msg = ""; // Mensaje OK, 14/09/2022
$msg_error = ""; //Mensaje del error

if (isset($_GET['msg']) && ($_GET['msg'] != "")) {
	$msg = base64_decode($_GET['msg']);
}

$dt_LS = 0; //sw para saber si vienen datos del SN. 0 no vienen. 1 si vienen.
$sw_valDir = 0; //Validar si el nombre de la direccion cambio
$TituloLlamada = ""; //Titulo por defecto cuando se está creando la llamada de servicio

// SMM, 02/10/2023
$testMode = false;

// Inicio, copiar firma a la ruta log y main.
$FirmaContactoResponsable = ""; // Eliminado. SMM, 02/10/2023
// Fin, copiar firma a la ruta log y main.

if (isset($_GET['id']) && ($_GET['id'] != "")) {
	$IdSolicitud = base64_decode($_GET['id']);
}

if (isset($_GET['tl']) && ($_GET['tl'] != "")) { // 0 Creando una llamada de servicio. 1 Editando llamada de servicio.
	$edit = $_GET['tl'];
} elseif (isset($_POST['tl']) && ($_POST['tl'] != "")) {
	$edit = $_POST['tl'];
} else {
	$edit = 0;
}

// Stiven Muñoz Murillo
if (isset($_GET['ext']) && ($_GET['ext'] == 1)) {
	$sw_ext = 1; //Se está abriendo como pop-up
} elseif (isset($_POST['ext']) && ($_POST['ext'] == 1)) {
	$sw_ext = 1; //Se está abriendo como pop-up
} else {
	$sw_ext = 0;
}
// 12/01/2022

if (isset($_POST['swError']) && ($_POST['swError'] != "")) { // Para saber si ha ocurrido un error.
	$sw_error = $_POST['swError'];
} else {
	$sw_error = 0;
}

if ($edit == 0) {
	$Title = "Crear Solicitud de Llamada de servicio (Agenda)";

	//Origen de llamada
	$SQL_OrigenLlamada = Seleccionar('uvw_Sap_tbl_LlamadasServiciosOrigen', '*', "Activo = 'Y'", 'DeOrigenLlamada');

	//Tipo de llamada
	$SQL_TipoLlamadas = Seleccionar('uvw_Sap_tbl_TipoLlamadas', '*', "Activo = 'Y'", 'DeTipoLlamada');

	//Tipo problema llamadas
	$SQL_TipoProblema = Seleccionar('uvw_Sap_tbl_TipoProblemasLlamadas', '*', "Activo = 'Y'", 'DeTipoProblemaLlamada');

	//SubTipo problema llamadas
	$SQL_SubTipoProblema = Seleccionar('uvw_Sap_tbl_SubTipoProblemasLlamadas', '*', "Activo = 'Y'", 'DeSubTipoProblemaLlamada');

	// SMM, 12/02/2022
} else {
	$Title = "Editar Solicitud de Llamada de servicio (Agenda)";

	//Origen de llamada
	$SQL_OrigenLlamada = Seleccionar('uvw_Sap_tbl_LlamadasServiciosOrigen', '*', '', 'DeOrigenLlamada');

	//Tipo de llamada
	$SQL_TipoLlamadas = Seleccionar('uvw_Sap_tbl_TipoLlamadas', '*', '', 'DeTipoLlamada');

	//Tipo problema llamadas
	$SQL_TipoProblema = Seleccionar('uvw_Sap_tbl_TipoProblemasLlamadas', '*', '', 'DeTipoProblemaLlamada');

	//SubTipo problema llamadas
	$SQL_SubTipoProblema = Seleccionar('uvw_Sap_tbl_SubTipoProblemasLlamadas', '*', '', 'DeSubTipoProblemaLlamada');

	// SMM, 12/02/2022
}


// Variables de anexos. SMM, 03/10/2023
$dir = CrearObtenerDirTemp();
$dir_new = CrearObtenerDirAnx("solicitudes_llamadas");

// Crear o Actualizar una Solicitud de Llamada de servicio.
if (isset($_POST['P'])) {
	try {
		// Inicio, carpeta temporal anexos. SMM, 02/10/2023
		$DocFiles = array();

		foreach (glob($dir . "/*") as $archivo) {
			if (is_file($archivo)) {
				$DocFiles[] = basename($archivo);

				// Ruta completa del archivo.
				// $DocFiles[] = $archivo;
			}
		}

		$CantFiles = count($DocFiles);

		// Anexos en SAP.
		// $RutaAttachSAP = ObtenerDirAttach();

		// print_r($DocFiles);
		// exit();
		// Fin, carpeta temporal anexos.

		// Campañas asociadas. SMM, 15/09/2023
		$Campanas = "''";
		if (isset($_POST['Campanas'])) {
			$Campanas = implode(";", $_POST['Campanas']);
			$Campanas = count($_POST['Campanas']) > 0 ? "'$Campanas'" : "''";
		}

		$Metodo = 2; //Actualizar en el web services
		$Type = 2; //Ejecutar actualizar en el SP

		if (($sw_error == 0) && (base64_decode($_POST['IdLlamadaPortal']) == "")) {
			$Metodo = 2; //Actualizar en el web services
			$Type = 1; //Ejecutar insertar en el SP
		} elseif (($edit == 0) && ($sw_error == 1) && (base64_decode($_POST['IdLlamadaPortal']) != "")) {
			$Metodo = 1; //Insertar en el web services
			$Type = 2; //Ejecutar Actualizar en el SP
		}

		$ParamLlamada = array(
			($_POST['P'] == 32) ? "NULL" : ("'" . base64_decode($_POST['IdLlamadaPortal'] ?? "") . "'"),
			($_POST['P'] == 32) ? "NULL" : ("'" . base64_decode($_POST['DocEntry'] ?? "") . "'"),
			($_POST['P'] == 32) ? "NULL" : ("'" . base64_decode($_POST['DocNum'] ?? "") . "'"),
			"'Externa'", // @TipoTarea
			"'" . ($_POST['AsuntoLlamada'] ?? "") . "'",
			isset($_POST['Series']) && ($_POST['Series'] != "") ? $_POST['Series'] : "NULL", // @IdSeries
			"'" . ($_POST['EstadoLlamada'] ?? "") . "'",
			isset($_POST['OrigenLlamada']) && ($_POST['OrigenLlamada'] != "") ? $_POST['OrigenLlamada'] : "NULL",
			isset($_POST['TipoLlamada']) && ($_POST['TipoLlamada'] != "") ? $_POST['TipoLlamada'] : "NULL",
			isset($_POST['TipoProblema']) && ($_POST['TipoProblema'] != "") ? $_POST['TipoProblema'] : "NULL",
			isset($_POST['SubTipoProblema']) && ($_POST['SubTipoProblema'] != "") ? $_POST['SubTipoProblema'] : "NULL",
			isset($_POST['ContratoServicio']) && ($_POST['ContratoServicio'] != "") ? $_POST['ContratoServicio'] : "NULL",
			isset($_POST['Tecnico']) && ($_POST['Tecnico'] != "") ? $_POST['Tecnico'] : "NULL",
			"'" . ($_POST['ClienteLlamada'] ?? "") . "'",
			isset($_POST['ContactoCliente']) && ($_POST['ContactoCliente'] != "") ? $_POST['ContactoCliente'] : "NULL", // @ID_ContactoCliente
			"'" . ($_POST['TelefonoLlamada'] ?? "") . "'",
			"'" . ($_POST['CorreoLlamada'] ?? "") . "'",
			"'" . ($_POST['IdArticuloLlamada'] ?? "") . "'",
			"'" . ($_POST['SerialInterno'] ?? "") . "'",
			"'" . ($_POST['SucursalCliente'] ?? "") . "'",
			isset($_POST['IdSucursalCliente']) && ($_POST['IdSucursalCliente'] != "") ? $_POST['IdSucursalCliente'] : "NULL", // @IdNombreSucursal
			"'" . ($_POST['DireccionLlamada'] ?? "") . "'",
			"'" . ($_POST['CiudadLlamada'] ?? "") . "'",
			"'" . ($_POST['BarrioDireccionLlamada'] ?? "") . "'",
			isset($_POST['EmpleadoLlamada']) && ($_POST['EmpleadoLlamada'] != "") ? $_POST['EmpleadoLlamada'] : "NULL", // @ID_EmpleadoLlamada
			"'" . ($_POST['Proyecto'] ?? "") . "'",
			"'" . LSiqmlObs($_POST['ComentarioLlamada'] ?? "") . "'", // @RequerimientoLlamada
			"''", // @ResolucionLlamada
			"'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'", // @FechaActualizacion
			"'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'", // @FechaCierreLlamada
			isset($_POST['IdAnexos']) && ($_POST['IdAnexos'] != "") ? $_POST['IdAnexos'] : "NULL", // @IdAnexoLlamada
			($_POST['P'] == 32) ? "1" : "$Metodo", // @Metodo
			$_SESSION['CodUser'] ?? "NULL", // @Usuario
			$_SESSION['CodUser'] ?? "NULL", // @UsuarioCierre (Actualización)
			"'" . $_POST['CDU_EstadoServicio'] . "'",
			"'" . LSiqmlObs($_POST['CDU_Servicios'] ?? "") . "'",
			"'" . LSiqmlObs($_POST['CDU_Areas'] ?? "") . "'",
			"'" . LSiqmlObs($_POST['CDU_NombreContacto'] ?? "") . "'",
			"'" . LSiqmlObs($_POST['CDU_TelefonoContacto'] ?? "") . "'",
			"'" . LSiqmlObs($_POST['CDU_CargoContacto'] ?? "") . "'",
			"'" . LSiqmlObs($_POST['CDU_CorreoContacto'] ?? "") . "'",
			"NULL", // @CDU_Reprogramacion1
			"NULL", // @CDU_Reprogramacion2
			"NULL", // @CDU_Reprogramacion3
			"NULL", // @CDU_CausaReprogramacion1
			"NULL", // @CDU_CausaReprogramacion2
			"NULL", // @CDU_CausaReprogramacion3
			"'" . ($_POST['CDU_CanceladoPor'] ?? "") . "'",
			(isset($_POST['CantArticulo']) && ($_POST['CantArticulo'] != "")) ? LSiqmlValorDecimal($_POST['CantArticulo']) : 0, // ¿nvarchar?
			(isset($_POST['PrecioArticulo']) && ($_POST['PrecioArticulo'] != "")) ? LSiqmlValorDecimal($_POST['PrecioArticulo']) : 0, // ¿nvarchar?
			($_POST['P'] == 32) ? "1" : "$Type", // INT, Tipo de SP
			"'" . ($_POST['CDU_Marca'] ?? "") . "'",
			"'" . ($_POST['CDU_Linea'] ?? "") . "'",
			"'" . ($_POST['CDU_Ano'] ?? "") . "'",
			"'" . ($_POST['CDU_Concesionario'] ?? "") . "'",
			"'" . ($_POST['CDU_Aseguradora'] ?? "") . "'",
			"'" . ($_POST['CDU_TipoPreventivo'] ?? "") . "'",
			"'" . ($_POST['CDU_TipoServicio'] ?? "") . "'",
			$_POST['CDU_Kilometros'] ?? "0",
			"'" . ($_POST['CDU_Contrato'] ?? "") . "'",
			"''", // @CDU_Asesor
			"'" . ($_POST['CDU_ListaMateriales'] ?? "") . "'",
			$_POST['CDU_TiempoTarea'] ?? "0",
			isset($_POST['CDU_IdTecnicoAdicional']) && ($_POST['CDU_IdTecnicoAdicional'] != "") ? $_POST['CDU_IdTecnicoAdicional'] : "NULL",
			"'" . FormatoFecha($_POST['FechaCreacion'], $_POST['HoraCreacion']) . "'",
			"'" . FormatoFecha($_POST['FechaFinCreacion'], $_POST['HoraFinCreacion']) . "'",
			"'" . FormatoFecha($_POST['FechaAgenda'], $_POST['HoraAgenda']) . "'",
			"'" . FormatoFecha($_POST['FechaFinAgenda'], $_POST['HoraFinAgenda']) . "'",
			"0", // @CreacionActividad
			"0", // @EnvioCorreo
			"'" . ($_POST['NombreContactoFirma'] ?? "") . "'",
			"'" . ($_POST['CedulaContactoFirma'] ?? "") . "'",
			"'" . ($_POST['TelefonosContactosFirma'] ?? "") . "'",
			"'" . ($_POST['CorreosContactosFirma'] ?? "") . "'",
			"'$FirmaContactoResponsable'",
			"0", // @FormatoCierreLlamada
			"'" . ($_POST['NumeroSerie'] ?? "") . "'", // SMM, 04/12/2023
			$Campanas, // SMM, 15/09/2023
		);

		$SQL_Llamada = EjecutarSP('sp_tbl_SolicitudLlamadaServicios', $ParamLlamada, $_POST['P']);
		if ($SQL_Llamada || $testMode) {
			if (base64_decode($_POST['IdLlamadaPortal']) == "") {
				$row_NewIdLlamada = ($SQL_Llamada) ? sqlsrv_fetch_array($SQL_Llamada) : [];
				$IdSolicitud = ($row_NewIdLlamada[0] ?? "");
			} else {
				$IdSolicitud = base64_decode($_POST['IdLlamadaPortal']);
			}

			// Copiar anexos a la ruta correspondiente. SMM, 02/10/2023
			try {
				// Limpiar los anexos ya cargados en caso de error.
				if ($sw_error == 1) {
					// SMM, 03/10/2023.
					LimpiarDirTemp();

					// Limpiar anexos en SAP.
					$ParamDelAnex = array(
						"'$IdSolicitud'",
						"NULL",
						"NULL",
						"NULL",
						"'" . ($_SESSION['CodUser'] ?? "") . "'",
						"2",
					);
					$SQL_DelAnex = EjecutarSP("sp_tbl_SolicitudLlamadasServicios_Anexos", $ParamDelAnex, $_POST['P']);
				}

				// Mover los anexos a la carpeta de archivos de SAP
				// echo $CantFiles;
				// echo exit();

				for ($j = 0; $j < $CantFiles; $j++) {
					// Normaliza el nombre, el 2do parámetro agrega o no la fecha.
					$ArchivoInfo = FormatoNombreAnexo($DocFiles[$j], false);

					$NuevoNombre = $ArchivoInfo[0];
					$OnlyName = $ArchivoInfo[1];
					$Ext = $ArchivoInfo[2];

					if (file_exists($dir_new)) {
						$origenArchivo = $dir . $DocFiles[$j];
						$nuevoArchivo = $dir_new . $NuevoNombre;

						// echo "$origenArchivo<br>";
						// echo "$nuevoArchivo<br>";
						// exit();

						copy($origenArchivo, $nuevoArchivo);

						// Registrar archivos en SAP.
						// $rutaDestinoSAP = $RutaAttachSAP[0] . $NuevoNombre;
						// copy($nuevoArchivo, $rutaDestinoSAP);

						// Registrar archivos en la BD.
						$ParamInsAnex = array(
							"'$IdSolicitud'",
							"'$OnlyName'",
							"'$Ext'",
							"1",
							"'" . ($_SESSION['CodUser'] ?? "") . "'",
							"1",
						);
						$SQL_InsAnex = EjecutarSP("sp_tbl_SolicitudLlamadasServicios_Anexos", $ParamInsAnex, $_POST['P']);

						if (!$SQL_InsAnex) {
							$sw_error = 1;
							$msg_error = "Error al insertar los anexos";
						}
					}
				}
			} catch (Exception $e) {
				// Manejar excepciones si es necesario
				$sw_error = 1;
				$msg_error = $e->getMessage();
			}
			// Fin, copiar anexos a la ruta correspondiente.	

			try {
				if ($_POST['P'] == 32) { // Creando 
					sqlsrv_close($conexion);
					
					$encode_a=base64_encode("OK_OTSolAdd");
					header("Location:gestionar_solicitudes_llamadas.php?a=$encode_a");
				} else { // Actualizando solicitud
					sqlsrv_close($conexion);

					$encode_id=base64_encode($IdSolicitud);
					$encode_a=base64_encode("OK_OTSolUpd");
					header("Location:solicitud_llamada.php?&id=$encode_id&tl=1&a=$encode_a");
				}
			} catch (Exception $e) {
				echo 'Excepcion capturada: ', $e->getMessage(), "\n";
			}
		} else {
			$sw_error = 1;

			$tipo = ($_POST['P'] == 32) ? "Creación" : "Actualización";
			$msg_error = "Error en la $tipo de la Solicitud de Llamada de servicio";
		}
	} catch (Exception $e) {
		echo 'Excepcion capturada: ', $e->getMessage(), "\n";
	}
}

if ($edit == 1 && $sw_error == 0) {
	$SQL = Seleccionar('uvw_tbl_SolicitudLlamadasServicios', '*', "ID_SolicitudLlamadaServicio='$IdSolicitud'");
	$row = sqlsrv_fetch_array($SQL);

	// SMM, 02/10/2023
	$ID_CodigoCliente = $row['ID_CodigoCliente'] ?? "";

	// Anexos. SMM, 02/10/2023
	// $IdAnexoLlamada = ($row['IdAnexoLlamada'] ?? "");
	// echo "SELECT * FROM uvw_Sap_tbl_DocumentosSAP_Anexos WHERE AbsEntry = '$IdAnexoLlamada'";

	// echo "SELECT * FROM tbl_SolicitudLlamadasServicios_Anexos WHERE ID_SolicitudLlamadaServicio='$IdSolicitud'";
	$SQL_AnexoSolicitudLlamada = Seleccionar("tbl_SolicitudLlamadasServicios_Anexos", '*', "ID_SolicitudLlamadaServicio='$IdSolicitud'");

	//Clientes
	$SQL_Cliente = Seleccionar("uvw_Sap_tbl_Clientes", "CodigoCliente, NombreCliente", "CodigoCliente='$ID_CodigoCliente'", 'NombreCliente');

	//Contactos clientes
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', 'CodigoContacto, ID_Contacto', "CodigoCliente='$ID_CodigoCliente'", 'NombreContacto');

	//Sucursales
	$SQL_SucursalCliente = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', 'NombreSucursal, NumeroLinea, TipoDireccion', "CodigoCliente='$ID_CodigoCliente' and TipoDireccion='S'", 'TipoDireccion, NombreSucursal');

	//Articulos del cliente (ID servicio)
	$ParamArt = array(
		"'$ID_CodigoCliente'",
		"'" . $row['NombreSucursal'] . "'",
		"'0'",
	);
	$SQL_Articulos = EjecutarSP('sp_ConsultarArticulosLlamadas', $ParamArt);

	// SMM, 01/03/2022
	$CDU_IdMarca_TarjetaEquipo = $row['CDU_IdMarca_TarjetaEquipo'] ?? '';
	$CDU_IdLinea_TarjetaEquipo = $row['CDU_IdLinea_TarjetaEquipo'] ?? '';

	//Lista de materiales
	$SQL_ListaMateriales = Seleccionar('uvw_Sap_tbl_ListaMateriales', '*', "CDU_IdMarca='" . $CDU_IdMarca_TarjetaEquipo . "' AND CDU_IdLinea='" . $CDU_IdLinea_TarjetaEquipo . "'");

	// Documentos relacionados. SMM, 27/09/2023
	$SQL_DocRel = Seleccionar('uvw_tbl_SolicitudLlamadasServiciosDocRelacionados', '*', "ID_SolicitudLlamadaServicio='$IdSolicitud'");

	//Formularios de llamadas de servicios
	$SQL_Formularios = Seleccionar('uvw_tbl_LlamadasServicios_Formularios', '*', "docentry_llamada_servicio='$IdSolicitud'");

	//Contratos de servicio
	$SQL_Contrato = Seleccionar('uvw_Sap_tbl_Contratos', '*', "CodigoCliente='$ID_CodigoCliente'", 'ID_Contrato');

	// Stiven Muñoz Murillo, 24/01/2022
	$SQL_Articulo = Seleccionar('uvw_Sap_tbl_ArticulosLlamadas', '*', "ItemCode='" . $row['IdArticuloLlamada'] . "'");
	$row_Articulo = sqlsrv_fetch_array($SQL_Articulo);
}

if ($sw_error == 1) {
	//Si ocurre un error, vuelvo a consultar los datos insertados desde la base de datos.
	$SQL = Seleccionar('uvw_tbl_SolicitudLlamadasServicios', '*', "ID_SolicitudLlamadaServicio='$IdSolicitud'");
	$row = sqlsrv_fetch_array($SQL);

	// SMM, 02/10/2023
	$ID_CodigoCliente = $row['ID_CodigoCliente'] ?? "";

	//Clientes
	$SQL_Cliente = Seleccionar("uvw_Sap_tbl_Clientes", "CodigoCliente, NombreCliente", "CodigoCliente='$ID_CodigoCliente'", 'NombreCliente');

	//Contactos clientes
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', 'CodigoContacto, ID_Contacto', "CodigoCliente='$ID_CodigoCliente'", 'NombreContacto');

	//Sucursales
	$SQL_SucursalCliente = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', 'NombreSucursal, NumeroLinea, TipoDireccion', "CodigoCliente='$ID_CodigoCliente' and TipoDireccion='S'", 'TipoDireccion, NombreSucursal');

	//Articulos del cliente (ID servicio)
	$ParamArt = array(
		"'$ID_CodigoCliente'",
		"'" . ($row['NombreSucursal'] ?? "") . "'",
		"'0'",
	);
	$SQL_Articulos = EjecutarSP('sp_ConsultarArticulosLlamadas', $ParamArt);

	// Documentos relacionados. SMM, 27/09/2023
	$SQL_DocRel = Seleccionar('uvw_tbl_SolicitudLlamadasServiciosDocRelacionados', '*', "ID_SolicitudLlamadaServicio='$IdSolicitud'");

	//Formularios de llamadas de servicios
	$SQL_Formularios = Seleccionar('uvw_tbl_LlamadasServicios_Formularios', '*', "docentry_llamada_servicio='" . ($row['DocEntry'] ?? "") . "'");

	//Contratos de servicio
	$SQL_Contrato = Seleccionar('uvw_Sap_tbl_Contratos', '*', "CodigoCliente='$ID_CodigoCliente'", 'ID_Contrato');

	// SMM, 01/03/2022
	$CDU_IdMarca_TarjetaEquipo = $row['CDU_IdMarca_TarjetaEquipo'] ?? '';
	$CDU_IdLinea_TarjetaEquipo = $row['CDU_IdLinea_TarjetaEquipo'] ?? '';

	// Lista de materiales
	$SQL_ListaMateriales = Seleccionar('uvw_Sap_tbl_ListaMateriales', '*', "CDU_IdMarca='$CDU_IdMarca_TarjetaEquipo' AND CDU_IdLinea='$CDU_IdLinea_TarjetaEquipo'");

	// Stiven Muñoz Murillo, 02/06/2022
	$SQL_Articulo = Seleccionar('uvw_Sap_tbl_ArticulosLlamadas', '*', "ItemCode='" . ($row['IdArticuloLlamada'] ?? "") . "'");
	$row_Articulo = sqlsrv_fetch_array($SQL_Articulo);
}

// Serie de Llamada
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'191'",
	($edit == 0) ? 2 : 1,
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Estado servicio de la Solicitud de Llamada de servicio. SMM, 29/08/2023
$SQL_EstServLlamada = Seleccionar('tbl_SolicitudLlamadasServiciosEstadoServicios', '*');

//Cancelado por llamada
$SQL_CanceladoPorLlamada = Seleccionar('uvw_Sap_tbl_LlamadasServiciosCanceladoPor', '*', '', 'DeCanceladoPor');

//Causa reprogramacion llamada
$SQL_CausaReprog = Seleccionar('uvw_Sap_tbl_LlamadasServiciosReprogramacion', '*', '', 'DeReprogramacion');

// Cola llamada. SMM, 29/06/2023
// $SQL_ColaLlamada=Seleccionar('uvw_Sap_tbl_ColaLlamadas','*','','DeColaLlamada');

//Empleados
$SQL_EmpleadoLlamada = Seleccionar('uvw_Sap_tbl_Empleados', '*', "UsuarioSAP <> ''", 'NombreEmpleado');

//Proyectos
$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', '', 'DeProyecto');


// SMM, 10/08/2023
$SQL_GruposUsuario = Seleccionar("uvw_tbl_UsuariosGruposEmpleados", "*", "[ID_Usuario]='" . $_SESSION['CodUser'] . "'", 'DeCargo');

$ids_grupos = array();
while ($row_GruposUsuario = sqlsrv_fetch_array($SQL_GruposUsuario)) {
	$ids_grupos[] = $row_GruposUsuario['IdCargo'];
}

$SQL_Tecnicos = Seleccionar('uvw_Sap_tbl_Recursos', '*', '', 'NombreEmpleado');
$SQL_TecnicosAdicionales = Seleccionar('uvw_Sap_tbl_Recursos', '*', '', 'NombreEmpleado');

//Estado llamada
$SQL_EstadoLlamada = Seleccionar('uvw_tbl_EstadoLlamada', '*');

// @author Stiven Muñoz Murillo
// @version 02/12/2021

// Marcas de vehiculo en la llamada de servicio
$SQL_MarcaVehiculo = Seleccionar('uvw_Sap_tbl_LlamadasServicios_MarcaVehiculo', '*');

// Lineas de vehiculo en la llamada de servicio
$SQL_LineaVehiculo = Seleccionar('uvw_Sap_tbl_LlamadasServicios_LineaVehiculo', '*');

// Modelo o año de fabricación de vehiculo en la llamada de servicio
$SQL_ModeloVehiculo = Seleccionar('uvw_Sap_tbl_LlamadasServicios_AñoModeloVehiculo', '*');

// Concesionarios en la llamada de servicio
$SQL_Concesionario = Seleccionar('uvw_Sap_tbl_LlamadasServicios_Concesionario', '*');

// Aseguradoras en la llamada de servicio
$SQL_Aseguradora = Seleccionar('uvw_Sap_tbl_LlamadasServicios_Aseguradoras', '*');

// Tipos preventivos en la llamada de servicio
$SQL_TipoPreventivo = Seleccionar('uvw_Sap_tbl_LlamadasServicios_TipoPreventivo', '*');

// Tipos de servicio en la llamada de servicio
$SQL_TipoServicio = Seleccionar('uvw_Sap_tbl_LlamadasServicios_TipoServicio', '*');

// Contratos en la llamada de servicio
$SQL_ContratosLlamada = Seleccionar('uvw_Sap_tbl_LlamadasServicios_Contratos_TBUsuario', '*');

// Asesores (Empleados de venta) en la llamada de servicio
// $SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*');

// Stiven Muñoz Murillo, 04/03/2022
if ($testMode) {
	$row_encode = isset($row) ? json_encode($row) : "";
	$cadena = isset($row) ? "JSON.parse('$row_encode'.replace(/\\n|\\r/g, ''))" : "'Not Found'";
	echo "<script> console.log($cadena); </script>";
}

// SMM, 30/06/2023
$OrigenLlamada = ObtenerValorDefecto(191, "IdOrigenLlamada", false);
$SubtipoProblema = ObtenerValorDefecto(191, "IdSubtipoProblema", false);
$TipoLlamada = ObtenerValorDefecto(191, "IdTipoLlamada", false);
$TipoProblema = ObtenerValorDefecto(191, "IdTipoProblema", false);

// SMM, 14/09/2023
$SQL_Campanas = Seleccionar("uvw_tbl_SolicitudLlamadasServicios_Campanas", "*", "[id_solicitud_llamada_servicio]='$IdSolicitud'");
// echo "SELECT * FROM uvw_tbl_SolicitudLlamadasServicios_Campanas WHERE [id_solicitud_llamada_servicio]='$IdSolicitud'";
$hasRowsCampanas = ($SQL_Campanas) ? sqlsrv_has_rows($SQL_Campanas) : false;

// SMM, 14/09/2023
$SQL_Anotaciones = Seleccionar("uvw_tbl_SolicitudLlamadasServicios_Anotaciones", "*", "[id_solicitud_llamada_servicio]='$IdSolicitud'");
$hasRowsAnotaciones = ($SQL_Anotaciones) ? sqlsrv_has_rows($SQL_Anotaciones) : false;

// Fechas. SMM, 27/10/2023
$ValorFechaCreacion = (isset($row["FechaCreacion"]) && ($row["FechaCreacion"] instanceof DateTime)) ? $row["FechaCreacion"]->format("Y-m-d") : date("Y-m-d");
$ValorFechaFinCreacion = (isset($row["FechaFinCreacion"]) && ($row["FechaFinCreacion"] instanceof DateTime)) ? $row["FechaFinCreacion"]->format("Y-m-d") : date("Y-m-d");
$ValorFechaAgenda = (isset($row["FechaAgenda"]) && ($row["FechaAgenda"] instanceof DateTime)) ? $row["FechaAgenda"]->format("Y-m-d") : date("Y-m-d");
$ValorFechaFinAgenda = (isset($row["FechaFinAgenda"]) && ($row["FechaFinAgenda"] instanceof DateTime)) ? $row["FechaFinAgenda"]->format("Y-m-d") : date("Y-m-d");
$ValorHoraCreacion = (isset($row["HoraCreacion"]) && ($row["HoraCreacion"] instanceof DateTime)) ? $row["HoraCreacion"]->format("H:i") : date("H:i");
$ValorHoraFinCreacion = (isset($row["HoraFinCreacion"]) && ($row["HoraFinCreacion"] instanceof DateTime)) ? $row["HoraFinCreacion"]->format("H:i") : date("H:i");
$ValorHoraAgenda = (isset($row["HoraAgenda"]) && ($row["HoraAgenda"] instanceof DateTime)) ? $row["HoraAgenda"]->format("H:i") : date("H:i");
$ValorHoraFinAgenda = (isset($row["HoraFinAgenda"]) && ($row["HoraFinAgenda"] instanceof DateTime)) ? $row["HoraFinAgenda"]->format("H:i") : date("H:i");

// SMM, 07/12/2023
$IdTarjetaEquipo = ($_GET["IdTE"] ?? ($row['IdTarjetaEquipo'] ?? ""));
$SQL_NumeroSerie = Seleccionar("uvw_Sap_tbl_TarjetasEquipos", "*", "IdTarjetaEquipo='$IdTarjetaEquipo'");
$row_NumeroSerie = sqlsrv_fetch_array($SQL_NumeroSerie);
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include "includes/cabecera.php"; ?>
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo $Title; ?> | <?php echo NOMBRE_PORTAL; ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
<?php
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_OTSolAdd"))) {
	echo "<script>
	$(document).ready(function() {
		Swal.fire({
			title: '¡Listo!',
			text: 'La Solicitud de Llamada de servicio ha sido creada exitosamente.',
			icon: 'success'
		});
	});
	</script>";
}
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_OTSolUpd"))) {
	echo "<script>
	$(document).ready(function() {
		Swal.fire({
			title: '¡Listo!',
			text: 'La Solicitud de Llamada de servicio ha sido actualizada exitosamente.',
			icon: 'success'
		});
	});
	</script>";
}
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_LlamAdd"))) {
	echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: '" . LSiqmlObs($msg) . "',
                icon: 'success'
            });
		});
		</script>";
}
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_UpdAdd"))) {
	echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: '" . LSiqmlObs($msg) . "',
                icon: 'success'
            });
		});
		</script>";
}
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_OpenAct"))) {
	echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La actividad ha sido abierta nuevamente.',
                icon: 'success'
            });
		});
		</script>";
}
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_FrmAdd"))) {
	echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'El hallazgo ha sido registrado exitosamente.',
                icon: 'success'
            });
		});
		</script>";
}
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_FrmUpd"))) {
	echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'El hallazgo ha sido actualizado exitosamente.',
                icon: 'success'
            });
		});
		</script>";
}
if (isset($sw_error) && ($sw_error == 1)) {
	echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Advertencia!',
                text: '" . LSiqmlObs($msg_error) . "',
                icon: 'warning'
            });
		});
		</script>";
}
?>

<style>
	.ibox-title a{
		color: inherit !important;
	}
	.collapse-link:hover{
		cursor: pointer;
	}
	.select2-container{
		width: 100% !important;
	}
	.swal2-container {
		z-index: 9000;
	}

	/** SMM, 16/09/2022 */
	.cierre-span {
		display: none;
	}

	.badge-secondary {
		margin: 10px;
		cursor: pointer;
	}
</style>

<script type="text/javascript">
function ActualizarAsunto() {
	let f333 = <?php echo PermitirFuncion(333) ? 'true' : 'false'; ?>;
	if (f333) {
		let AsuntoLlamada = $('#AsuntoLlamada').val();
		AsuntoLlamada = AsuntoLlamada.replace(/\([^)]+\)|\(\s*\)/g, '').trim();

		let OrigenLlamada = ($("#OrigenLlamada").val() != "") ? trim($("#OrigenLlamada option:selected").text()) : "";
		let TipoProblema = ($("#TipoProblema").val() != "") ? trim($("#TipoProblema option:selected").text()) : "";

		AsuntoLlamada = `${AsuntoLlamada} (${OrigenLlamada}) (${TipoProblema})`;
		$('#AsuntoLlamada').val(AsuntoLlamada);
	}
}

$(document).ready(function () {
	$("#OrigenLlamada").change(function () {
		$.ajax({
			type: "POST",
			url: `ajx_cbo_select.php?type=46&id=${$(this).val()}&serie=${$("#Series").val()}`,
			success: function (response) {
				$('#TipoProblema').html(response).fadeIn();
				$('#TipoProblema').trigger('change');
			}
		});

		// SMM, 21/07/2023
		ActualizarAsunto();
	});

	// SMM, 12/07/2023
	$("#TipoProblema").change(function () {
		$('.ibox-content').toggleClass('sk-loading', true);

		$.ajax({
			url: "ajx_buscar_datos_json.php",
			data: {
				type: 48,
				id: $(this).val()
			},
			dataType: 'json',
			success: function (data) {
				console.log(data);
				$("#CDU_TiempoTarea").val(data.tiempoTarea || '""');

				$('.ibox-content').toggleClass('sk-loading', false);
			},
			error: function (error) {
				console.log("AJAX error:", error.responseText);

				$('.ibox-content').toggleClass('sk-loading', false);
			}
		});

		// SMM, 13/07/2023
		$.ajax({
			type: "POST",
			url: `ajx_cbo_select.php?type=47&id=${$(this).val()}&serie=${$("#Series").val()}`,
			success: function (response) {
				$('#TipoLlamada').html(response).fadeIn();
				$('#TipoLlamada').trigger('change');
			}
		});

		// SMM, 21/07/2023
		ActualizarAsunto();
	});

	// Revisado. SMM, 21/11/2023
	var borrarLineaModeloVehiculo = true;

	//Cargar los combos dependiendo de otros
	$("#ClienteLlamada").change(function () {
		$('.ibox-content').toggleClass('sk-loading', true);
		var Cliente = document.getElementById('ClienteLlamada').value;
		$.ajax({
			type: "POST",
			url: "ajx_cbo_select.php?type=2&id=" + Cliente,
			success: function (response) {
				$('#ContactoCliente').html(response).fadeIn();
				$('#ContactoCliente').trigger('change');
				$('.ibox-content').toggleClass('sk-loading', false);
			}
		});
		$.ajax({
			type: "POST",
			url: "ajx_cbo_select.php?type=3&id=" + Cliente,
			success: function (response) {
				$('#SucursalCliente').html(response).fadeIn();
				$('#SucursalCliente').trigger('change');
				$('.ibox-content').toggleClass('sk-loading', false);
			}
		});

		<?php if ($IncluirCamposAdicionales) { ?>
				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=29&id=" + Cliente,
					success: function (response) {
						$('#ContratoServicio').html(response).fadeIn();
						$('#ContratoServicio').trigger('change');
						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});

				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=30&id=" + Cliente,
					success: function (response) {
						$('#Proyecto').html(response).fadeIn();
						$('#Proyecto').trigger('change');

						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
		<?php } ?>
	});
	$("#SucursalCliente").change(function () {
		$('.ibox-content').toggleClass('sk-loading', true);

		var Cliente = document.getElementById('ClienteLlamada').value;
		var Sucursal = document.getElementById('SucursalCliente').value;

		if (Sucursal != -1 && Sucursal != '') {
			$.ajax({
				url: "ajx_buscar_datos_json.php",
				data: { type: 1, CardCode: Cliente, Sucursal: Sucursal },
				dataType: 'json',
				success: function (data) {
					document.getElementById('DireccionLlamada').value = data.Direccion;
					document.getElementById('BarrioDireccionLlamada').value = data.Barrio;
					document.getElementById('CiudadLlamada').value = data.Ciudad;
					document.getElementById('CDU_NombreContacto').value = data.NombreContacto;
					document.getElementById('CDU_TelefonoContacto').value = data.TelefonoContacto;
					document.getElementById('CDU_CargoContacto').value = data.CargoContacto;
					document.getElementById('CDU_CorreoContacto').value = data.CorreoContacto;

					// Stiven Muñoz Murillo, 22/01/2022
					document.getElementById('TelefonoLlamada').value = data.TelefonoContacto;
				},
				error: function (error) {
					$('.ibox-content').toggleClass('sk-loading', false);
					console.error("SucursalCliente", error.responseText);
				}
			});
		} else {
			$('.ibox-content').toggleClass('sk-loading', false);
		}
		// TODO, filtrar los resultados por cliente y sucursal
		/*
		$.ajax({
			type: "POST",
			url: "ajx_cbo_select.php?type=11&id="+Cliente+"&suc="+Sucursal,
			success: function(response){
				$('#ArticuloLlamada').html(response).fadeIn();
				$('#ArticuloLlamada').trigger('change');
				$('.ibox-content').toggleClass('sk-loading',false);
			}
		});
		*/
		// TODO, abajo en el buscar
		$.ajax({
			url: "ajx_buscar_datos_json.php",
			data: {
				type: 39,
				clt: Cliente,
				suc: Sucursal
			},
			dataType: 'json',
			success: function (data) {
				document.getElementById('IdSucursalCliente').value = data.IdSucursal;
				$('.ibox-content').toggleClass('sk-loading', false);
			}
		});
	});
	$("#ContactoCliente").change(function () {
		$('.ibox-content').toggleClass('sk-loading', true);
		var Contacto = document.getElementById('ContactoCliente').value;
		$.ajax({
			url: "ajx_buscar_datos_json.php",
			data: { type: 5, Contacto: Contacto },
			dataType: 'json',
			success: function (data) {
				document.getElementById('TelefonoLlamada').value = data.Telefono;
				document.getElementById('CorreoLlamada').value = data.Correo;
				$('.ibox-content').toggleClass('sk-loading', false);
			}
		});
	});

	// Stiven Muñoz Murillo, 24/01/2022
	$("#IdArticuloLlamada").change(function () {
		$('.ibox-content').toggleClass('sk-loading', true);
		
		var ID = document.getElementById('IdArticuloLlamada').value;
		var Cliente = document.getElementById('ClienteLlamada').value;
		
		if (ID != "") {
			$.ajax({
				url: "ajx_buscar_datos_json.php",
				data: { type: 6, id: ID },
				dataType: 'json',
				success: function (data) {
					document.getElementById('CDU_Servicios').value = data.Servicios;
					document.getElementById('CDU_Areas').value = data.Areas;

					$('.ibox-content').toggleClass('sk-loading', false);
				}
			});

			// SMM, 11/12/2023
			$("#SerialInterno").val("");
			$("#NumeroSerie").val("");
			$("#Desc_NumeroSerie").val("");
		} else {
			document.getElementById('CDU_Servicios').value = '';
			document.getElementById('CDU_Areas').value = '';
			
			/*
			document.getElementById('CDU_NombreContacto').value='';
			document.getElementById('CDU_TelefonoContacto').value='';
			document.getElementById('CDU_CargoContacto').value='';
			document.getElementById('CDU_CorreoContacto').value='';
			*/
			
			$('.ibox-content').toggleClass('sk-loading', false);
		}
		$('.ibox-content').toggleClass('sk-loading', false);
	});
	$("#TipoTarea").change(function () {
		$('.ibox-content').toggleClass('sk-loading', true);
		var TipoTarea = document.getElementById('TipoTarea').value;
		if (TipoTarea == "Interna") {
			document.getElementById('ClienteLlamada').value = '<?php echo NIT_EMPRESA; ?>';
			document.getElementById('NombreClienteLlamada').value = '<?php echo NOMBRE_EMPRESA; ?>';
			document.getElementById('NombreClienteLlamada').readOnly = true;
			$('#ClienteLlamada').trigger('change');
			$('.ibox-content').toggleClass('sk-loading', false);
			//HabilitarCampos(0);
		} else {
			document.getElementById('ClienteLlamada').value = '';
			document.getElementById('NombreClienteLlamada').value = '';
			document.getElementById('NombreClienteLlamada').readOnly = false;
			$('#ClienteLlamada').trigger('change');
			$('.ibox-content').toggleClass('sk-loading', false);
			//HabilitarCampos(1);
		}
	});

	// Para impedir que se borre la información que esta en el $row. SMM, 22/09/2023
	<?php if (($edit == 0) && ($sw_error == 0)) { ?>
			// SMM, 12/07/2023
			$("#Series").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);

				let Series = document.getElementById('Series').value;
				if (Series !== "") {
					$.ajax({
						url: "ajx_buscar_datos_json.php",
						data: {
							type: 30,
							id: Series
						},
						dataType: 'json',
						success: function (data) {
							console.log("ajx_buscar_datos_json(30)", data);

							$('#OrigenLlamada').val(data.OrigenLlamada || '""');
							$('#OrigenLlamada').trigger('change');

							$('#TipoProblema').val(data.TipoProblemaLlamada || '""');
							$('#TipoProblema').trigger('change');
							
							$('#TipoLlamada').val(data.TipoLlamada || '""');
							$('#TipoLlamada').trigger('change');
							
							let AsuntoLlamada = (data.AsuntoLlamada || '""');
							let f333 = <?php echo PermitirFuncion(333) ? 'true' : 'false'; ?>;
							if (f333) {
								let OrigenLlamada = trim($("#OrigenLlamada option:selected").text());
								let TipoProblema = trim($("#TipoProblema option:selected").text());

								AsuntoLlamada = `${AsuntoLlamada} (${OrigenLlamada}) (${TipoProblema})`;
							}
							$('#AsuntoLlamada').val(AsuntoLlamada);
			
							$('.ibox-content').toggleClass('sk-loading', false);
						},
						error: function (error) {
							console.log("AJAX error:", error);

							$('.ibox-content').toggleClass('sk-loading', false);
						}
					});
				} else {
					$('.ibox-content').toggleClass('sk-loading', false);
				}
			});

			// Stiven Muñoz Murillo, 07/02/2022
			$("#CDU_ListaMateriales").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);
				let listaMaterial = document.getElementById('CDU_ListaMateriales').value;

				if (listaMaterial != "") {
					$.ajax({
						url: "ajx_buscar_datos_json.php",
						data: {
							type: 47,
							id: listaMaterial
						},
						dataType: 'json',
						success: function (data) {
							// console.log(data);

							document.getElementById('CDU_TiempoTarea').value = data.tiempoTarea;
							$('.ibox-content').toggleClass('sk-loading', false);
						},
						error: function (error) {
							console.error(error.responseText);
							$('.ibox-content').toggleClass('sk-loading', false);
						}
					});
				}
			});

			// Stiven Muñoz Murillo, 20/12/2021
			$("#CDU_Marca").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);
				
				var marcaVehiculo = document.getElementById('CDU_Marca').value;
				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=39&id=" + marcaVehiculo,
					success: function (response) {
						// console.log(response);

						if (borrarLineaModeloVehiculo) {
							$('#CDU_Linea').html(response).fadeIn();
							$('#CDU_Linea').trigger('change');
						} else {
							borrarLineaModeloVehiculo = true;
						}

						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
			});

			// SMM, 27/11/2023
			$("#NumeroSerie").on("change", function () {
				$('.ibox-content').toggleClass('sk-loading', true);

				let Cliente = $("#ClienteLlamada").val();
				let IdTarjetaEquipo = $("#NumeroSerie").val() || "";

				if (IdTarjetaEquipo != "") {
					$.ajax({
						url: "ajx_buscar_datos_json.php",
						data: {
							type: 44,
							id: IdTarjetaEquipo,
							clt: Cliente
						},
						dataType: 'json',
						success: function (data) {
							console.log("ajx_buscar_datos_json(44)", data);

							document.getElementById('IdArticuloLlamada').value = data.IdArticuloLlamada;
							document.getElementById('DeArticuloLlamada').value = data.DeArticuloLlamada;
							// $('#IdArticuloLlamada').trigger('change');
							// $('#DeArticuloLlamada').trigger('change');

							document.getElementById('CDU_Marca').value = data.CDU_IdMarca;
							$('#CDU_Marca').trigger('change');

							borrarLineaModeloVehiculo = false;
							document.getElementById('CDU_Linea').value = data.CDU_IdLinea;
							$('#CDU_Linea').trigger('change');

							document.getElementById('CDU_Ano').value = data.CDU_Ano;
							$('#CDU_Ano').trigger('change');

							document.getElementById('CDU_Concesionario').value = data.CDU_Concesionario;
							$('#CDU_Concesionario').trigger('change');

							document.getElementById('CDU_TipoServicio').value = (data.CDU_TipoServicio != null) ? data.CDU_TipoServicio : "";
							$('#CDU_TipoServicio').trigger('change');

							$('.ibox-content').toggleClass('sk-loading', false);
						},
						error: function (data) {
							console.error("Line 1530", data.responseText);
						}
					});
					$.ajax({
						type: "POST",
						url: `ajx_cbo_select.php?type=40&id=${IdTarjetaEquipo}`,
						success: function (response) {
							console.log("ajx_cbo_select(40)", response);

							$('#CDU_ListaMateriales').html(response).fadeIn();
							$('#CDU_ListaMateriales').trigger('change');

							$('.ibox-content').toggleClass('sk-loading', false);
						}
					});
				}
				$('.ibox-content').toggleClass('sk-loading', false);
			});

			$('#Series').trigger('change');
	<?php } ?>

		$("#EstadoLlamada").on("change", function () {
			let estado = $(this).val();

			if (estado == "-1") {
				console.log("el estado de la llamada cambio a cerrado.");

				$(".cierre-span").css("display", "initial");
				$(".cierre-input").prop("readonly", false);
				$(".cierre-input").prop("disabled", false);

			// SMM, 14/10/2022
			<?php if ($sw_error == 0) { ?>
						$("#NombreContactoFirma").val($("#CDU_NombreContacto").val());
						$("#CorreosDestinatarios").html("");
						$("#TelefonosDestinatarios").html("");

						AgregarEsto("CorreosDestinatarios", $("#CDU_CorreoContacto").val());
						AgregarEsto("TelefonosDestinatarios", $("#CDU_TelefonoContacto").val());
			<?php } ?>

					// SMM, 28/06/2023
					$("#ContactoCierreContainer").removeClass('collapsed');
			} else {
				console.log("cambio el estado de la llamada, diferente a cerrado.");

				$(".cierre-span").css("display", "none");
				$(".cierre-input").prop("readonly", true);
				$(".cierre-input").prop("disabled", true);

				// SMM, 28/06/2023
				$("#ContactoCierreContainer").addClass('collapsed');
			}
		});
});

function HabilitarCampos(type = 1) {
	if (type == 0) {//Deshabilitar
		document.getElementById('DatosCliente').style.display = 'none';
		document.getElementById('swTipo').value = "1";
	} else {//Habilitar
		document.getElementById('DatosCliente').style.display = 'block';
		document.getElementById('swTipo').value = "0";
	}
}
function ConsultarDatosCliente() {
	var Cliente = document.getElementById('ClienteLlamada');
	if (Cliente.value != "") {
		self.name = 'opener';
		remote = open('socios_negocios.php?id=' + Base64.encode(Cliente.value) + '&ext=1&tl=1', 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
		remote.focus();
	}
}
function ConsultarArticulo(componente = false) {
	let IdArticulo = $("#IdArticuloLlamada").val() || "";
	let IdArticuloComponente = $("#IdArticuloComponente").val() || "";

	let selectedIndex = (componente) ? IdArticuloComponente : IdArticulo;
	if (selectedIndex != "") {
		self.name = 'opener';
		remote = open('articulos.php?id=' + Base64.encode(selectedIndex) + '&ext=1&tl=1', 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
		remote.focus();
	}
}

// SMM, 22/11/2023
function ConsultarEquipo(componente = false) {
	let IdTarjetaEquipo = $("#NumeroSerie").val() || "";
	let IdTarjetaEquipoComponente = $("#IdTarjetaEquipoComponente").val() || "";

	let selectedIndex = (componente) ? IdTarjetaEquipoComponente : IdTarjetaEquipo;
	if (selectedIndex != "") {
		self.name = 'opener';
		remote = open(`tarjeta_equipo.php?id='${Base64.encode(selectedIndex)}'&ext=1&tl=1`, 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
		remote.focus();
	}
}

<?php if ($IncluirCamposAdicionales) { ?>
		function ConsultarContrato() {
			var Contrato = document.getElementById('ContratoServicio');
			if (Contrato.value != "") {
				self.name = 'opener';
				remote = open('contratos.php?id=' + btoa(Contrato.value) + '&ext=1&tl=1', 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
				remote.focus();
			}
		}
	<?php } ?>

		// Stiven Muñoz Murillo, 30/12/2021
		function ConsultarMateriales() {
			var Materiales = document.getElementById('CDU_ListaMateriales');
			if (Materiales.value != "") {
				self.name = 'opener';
				remote = open('lista_materiales.php?id=' + Base64.encode(Materiales.value) + '&ext=1&tl=1', 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
				remote.focus();
			}
		}
function CrearLead() {
	self.name = 'opener';
	var altura = 720;
	var anchura = 1240;
	var posicion_y = parseInt((window.screen.height / 2) - (altura / 2));
	var posicion_x = parseInt((window.screen.width / 2) - (anchura / 2));
	remote = open('popup_crear_lead.php', 'remote', 'width=' + anchura + ',height=' + altura + ',location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=no,status=yes,left=' + posicion_x + ',top=' + posicion_y);
	remote.focus();
}

// SMM, 16/09/2022
function ValidarCorreo(evento, entrada, contenedorID = "CorreosDestinatarios") {
	if (event.code === 'Space') {
		let re = /\S+@\S+\.\S+/;
		let correo = entrada.value.trim();

		entrada.value = "";
		if (re.test(correo)) {
			AgregarEsto(contenedorID, correo);
		} else {
			alert("El correo no paso la validación.");
		}
	}
}

function LlenarCorreos() {
	let badges = document.getElementById("CorreosContactosFirma");
	badges.value = "";

	$("#CorreosDestinatarios .badge").each(function () {
		let badge = $(this).text().trim();
		console.log(`|${badge}|`);

		badges.value += `${badge};`;
	});
}

function ValidarTelefono(evento, entrada) {
	if (event.code === 'Space') {
		let re = /\d{5,}/;
		let telefono = entrada.value.trim();

		entrada.value = "";
		if (re.test(telefono)) {
			AgregarEsto("TelefonosDestinatarios", telefono);
		} else {
			alert("El télefono no paso la validación.");
		}
	}
}

function LlenarTelefonos() {
	let badges = document.getElementById("TelefonosContactosFirma");
	badges.value = "";

	$("#TelefonosDestinatarios .badge").each(function () {
		let badge = $(this).text().trim();
		console.log(`|${badge}|`);

		badges.value += `${badge};`;
	});
}

function EliminarEsto(elemento) {
	elemento.remove();

	LlenarCorreos();
	LlenarTelefonos();
}

function AgregarEsto(contenedorID, valorElemento) {
	let contenedorElementos = document.getElementById(contenedorID);
	contenedorElementos.innerHTML += `<span onclick="EliminarEsto(this)" class="badge badge-secondary"><i class="fa fa-trash"></i> ${valorElemento}</span>`;

	LlenarCorreos();
	LlenarTelefonos();
}
</script>

<!-- InstanceEndEditable -->
</head>

<!-- Stiven Muñoz Murillo -->
<body <?php if ($sw_ext == 1) {
	echo "class='mini-navbar'";
} ?>>
<div id="wrapper">
	<?php if ($sw_ext != 1) {
		include "includes/menu.php";
	} ?>
	<div id="page-wrapper" class="gray-bg">
		<?php if ($sw_ext != 1) {
			include "includes/menu_superior.php";
		} ?>
<!-- 12/01/2022 -->

		<!-- InstanceBeginEditable name="Contenido" -->
		<div class="row wrapper border-bottom white-bg page-heading">
				<div class="col-sm-8">
					<h2><?php echo $Title; ?></h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">Gestión de tareas</a>
						</li>
						<li>
							<a href="gestionar_solicitudes_llamadas.php">Gestionar Solicitudes de Llamadas de servicios</a>
						</li>
						<li class="active">
							<strong><?php echo $Title; ?></strong>
						</li>
					</ol>
				</div>
			</div>

		<div class="wrapper wrapper-content">
			<!-- Inicio, myModal -->
			<div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-lg" style="width: 70% !important;">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="TituloModal"></h4>
						</div>
						<div class="modal-body" id="ContenidoModal"></div>
						<div class="modal-footer">
							<button type="button" class="btn btn-success m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
						</div>
					</div>
				</div>
			</div>
			<!-- Fin, myModal -->

			<div class="modal inmodal fade" id="myModal2" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-lg" style="width: 70% !important;">
					<div class="modal-content" id="ContenidoModal2">
						<!-- Contenido generado por JS -->
					</div>
				</div>
			</div>
			<!-- /#MyModal2 -->

			<!-- SMM, 27/11/2023 -->
			<?php include_once 'md_consultar_tarjetas_equipos.php'; ?>

			<!-- SMM, 11/03/2024 -->
			<?php include_once 'md_consultar_tarjetas_componentes.php'; ?>

			<!-- SMM, 18/01/2024 -->
			<?php include_once 'md_consultar_llamadas_servicios.php'; ?>

			<!-- SMM, 18/01/2024 -->
			<?php include_once 'md_consultar_solicitudes_llamadas.php'; ?>

			<!-- Inicio, modalSN -->
			<div class="modal inmodal fade" id="modalSN" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-lg" style="width: 70% !important;">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title">Cambiar Socio de Negocio en el Nuevo Documento</h4>
						</div>

						<form id="formCambiarSN">
							<div class="modal-body">
								<div class="row">
									<div class="col-lg-1"></div>
									<div class="col-lg-5">
										<label class="control-label">
											<i onClick="ConsultarDatosClienteSN();" title="Consultar cliente" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Cliente <span class="text-danger">*</span>
										</label>
										<input type="hidden" id="ClienteSN" name="ClienteSN" >
										<input type="text" class="form-control" id="NombreClienteSN" name="NombreClienteSN"  placeholder="Digite para buscar..." required>
									</div>
									<div class="col-lg-5">
										<label class="control-label">Contacto</label>
										<select class="form-control" id="ContactoSN" name="ContactoSN">
											<option value="">Seleccione...</option>
										</select>
									</div>
									<div class="col-lg-1"></div>
								</div>
								<br><br>
								<div class="row">
									<div class="col-lg-1"></div>
									<div class="col-lg-5">
										<label class="control-label">Sucursal</label>
										<select class="form-control" id="SucursalSN" name="SucursalSN">
											<option value="">Seleccione...</option>
										</select>
									</div>
									<div class="col-lg-5">
										<label class="control-label">Dirección</label>
										<input type="text" class="form-control" id="DireccionSN" name="DireccionSN" maxlength="100">
									</div>
									<div class="col-lg-1"></div>
								</div>
							</div>

							<div class="modal-footer">
								<button type="submit" class="btn btn-success m-t-md"><i class="fa fa-check"></i> Aceptar</button>
								<button type="button" class="btn btn-secondary m-t-md CancelarSN" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
							</div>
						</form>
					</div>
				</div>
			</div>
			<!-- Fin, modalSN -->

			<!-- Inicio, modalFactSN -->
			<div class="modal inmodal fade" id="modalFactSN" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-lg" style="width: 70% !important;">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title">Cambiar Socio de Negocio en el Nuevo Documento</h4>
						</div>

						<form id="formCambiarFactSN">
							<div class="modal-body">
								<div class="row">
									<div class="col-lg-1"></div>
									<div class="col-lg-5">
										<label class="control-label">
											<i onClick="ConsultarDatosFactSN();" title="Consultar cliente" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Cliente <span class="text-danger">*</span>
										</label>
										<select class="form-control" id="ClienteFactSN" name="ClienteFactSN" required>
											<option value="">Seleccione...</option>
										</select>
										<small class="form-text text-muted">Sólo se listan los clientes con entregas abiertas.</small>
									</div>
									<div class="col-lg-5">
										<label class="control-label">Contacto</label>
										<select class="form-control" id="ContactoFactSN" name="ContactoFactSN">
											<option value="">Seleccione...</option>
										</select>
									</div>
									<div class="col-lg-1"></div>
								</div>
								<br><br>
								<div class="row">
									<div class="col-lg-1"></div>
									<div class="col-lg-5">
										<label class="control-label">Sucursal</label>
										<select class="form-control" id="SucursalFactSN" name="SucursalFactSN">
											<option value="">Seleccione...</option>
										</select>
									</div>
									<div class="col-lg-5">
										<label class="control-label">Dirección</label>
										<input type="text" class="form-control" id="DireccionFactSN" name="DireccionFactSN" maxlength="100">
									</div>
									<div class="col-lg-1"></div>
								</div>
							</div>

							<div class="modal-footer">
								<button type="submit" class="btn btn-success m-t-md"><i class="fa fa-check"></i> Aceptar</button>
								<button type="button" class="btn btn-secondary m-t-md CancelarSN" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
							</div>
						</form>
					</div>
				</div>
			</div>
			<!-- Fin, modalFactSN -->

			<!-- Inicio, modalCorreo. SMM, 13/10/2022 -->
			<?php if (isset($row['IdEstadoLlamada']) && ($row['IdEstadoLlamada'] == '-1')) { ?>
							<div class="modal inmodal fade" id="modalCorreo" tabindex="-1" role="dialog" aria-hidden="true">
								<div class="modal-dialog modal-lg" style="width: 70% !important;">
									<div class="modal-content">
										<div class="modal-header">
											<h4 class="modal-title">Envío de llamada de servicio No.<?php echo $row['DocNum']; ?></h4>
										</div>

										<!-- form id="formCambiarSN" -->
											<div class="modal-body">
												<div class="row">
													<div class="col-lg-1"></div>
													<div class="col-lg-10">
														<div class="form-group">
															<label class="control-label">Para</label>
															<input placeholder="Ingrese un nuevo correo y utilice la tecla [ESP] para agregar" onKeyUp="ValidarCorreo(event, this, 'CorreosPara')" autocomplete="off" name="EmailPara" type="text" class="form-control" id="EmailPara" maxlength="50" value="">
															<input type="hidden" id="CorreosContactosFirma" name="CorreosContactosFirma">

															<div id="CorreosPara"></div>
														</div>
													</div>
													<div class="col-lg-1"></div>
												</div>
												<div class="row">
													<div class="col-lg-1"></div>
													<div class="col-lg-10">
														<div class="form-group">
															<label class="control-label">Con copia a</label>
															<input placeholder="Ingrese un nuevo correo y utilice la tecla [ESP] para agregar" onKeyUp="ValidarCorreo(event, this, 'CorreosCC')" autocomplete="off" name="EmailCC" type="text" class="form-control" id="CorreoContactoFirma" maxlength="50" value="">
															<input type="hidden" id="CorreosContactosFirma" name="CorreosContactosFirma">

															<div id="CorreosCC"></div>
														</div>
													</div>
													<div class="col-lg-1"></div>
												</div>
											</div>

											<div class="modal-footer">
												<button type="submit" class="btn btn-success m-t-md"><i class="fa fa-check"></i> Aceptar</button>
												<button type="button" class="btn btn-secondary m-t-md CancelarSN" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
											</div>
										<!-- /form -->
									</div>
								</div>
							</div>
			<?php } ?>
			<!-- Fin, modalCorreo -->

			<?php if ($edit == 1) { ?>
				<div class="row">
						<div class="col-lg-4">
							<div class="ibox ">
								<div class="ibox-title">
									<h5><span class="font-normal">ID Agenda</span></h5>
								</div>
								<div class="ibox-content">
									<h3 class="no-margins"><?php echo $row['ID_SolicitudLlamadaServicio']; ?></h3>
								</div>
							</div>
						</div>
						<!-- col-lg-4 -->

						<div class="col-lg-4">
							<div class="ibox ">
								<div class="ibox-title">
									<h5>
										<span class="font-normal">Creada por: <b><?php echo $row['UsuarioCreacion'] ?? "&nbsp;"; ?></b></span>
									</h5>
								</div>
								<div class="ibox-content">
									<h3 class="no-margins">
										<?php echo $row['FechaRegistro']->format("Y-m-d H:i:s") ?? "&nbsp;"; ?>
									</h3>
								</div>
							</div>
						</div>
						<!-- col-lg-4 -->

						<div class="col-lg-4">
							<div class="ibox ">
								<div class="ibox-title">
									<h5>
										<span class="font-normal">Actualizado por: <b><?php echo $row['UsuarioActualizacion'] ?? "&nbsp;"; ?></b></span>
									</h5>
								</div>
								<div class="ibox-content">
									<h3 class="no-margins">
										<?php echo (isset($row['FechaActualizacion']) && ($row['FechaActualizacion'] != "")) ? $row['FechaActualizacion']->format("Y-m-d H:i:s") : "&nbsp;"; ?>
									</h3>
								</div>
							</div>
						</div>	
						<!-- col-lg-4 -->

					</div>
					<!-- /.row -->
			<?php } ?>

				<div class="ibox-content">
				<?php include "includes/spinner.php"; ?>
					<div class="row">
						<div class="col-lg-12">
							<div class="ibox">
								<div class="ibox-title bg-success">
									<h5 class="collapse-link"><i class="fa fa-play-circle"></i> Acciones</h5>
									 <a class="collapse-link pull-right">
										<i class="fa fa-chevron-up"></i>
									</a>
								</div>
								<div class="ibox-content">
									<div class="form-group">
										<div class="col-lg-6">
											<?php if ($edit == 1) { ?>

													<div class="btn-group">
														<!-- SMM, 23/01/2024 -->
														<div class="btn-group">
															<button data-toggle="dropdown"
																class="btn btn-outline btn-success dropdown-toggle"><i
																	class="fa fa-download"></i> Descargar formato <i
																	class="fa fa-caret-down"></i></button>
															<ul class="dropdown-menu">
																<?php $SQL_Formato = Seleccionar('uvw_tbl_FormatosSAP', '*', "ID_Objeto=20008 AND VerEnDocumento='Y'"); ?>
																
																<?php while ($row_Formato = sqlsrv_fetch_array($SQL_Formato)) { ?>
																	<li>
																		<a class="dropdown-item" target="_blank"
																			href="formatdownload.php?DocKey=<?php echo $row['ID_LlamadaServicio'] ?? ""; ?>&ObType=<?php echo $row_Formato['ID_Objeto'] ?? ""; ?>&IdFrm=<?php echo $row_Formato['IdFormato'] ?? ""; ?>&IdReg=<?php echo $row_Formato['ID'] ?? ""; ?>">
																			<?php echo $row_Formato['NombreVisualizar'] ?? ""; ?>
																		</a>
																	</li>
																<?php } ?>
															</ul>
														</div>
														<!-- Hasta aquí, 23/01/2024 -->

														<!-- Espacio para más botones -->
													</div>
											<?php } elseif (PermitirFuncion(508)) { ?>

														<button onclick="CrearLead();" class="btn btn-outline btn-primary"><i class="fa fa-user-circle"></i> Crear Prospecto</button>
														<a href="tarjeta_equipo.php" class="btn btn-outline btn-info" target="_blank"><i class="fa fa-plus-circle"></i> Crear nueva tarjeta de equipo</a>
														
											<?php } ?>
										</div>
										<!-- /.col-lg-6 -->

										<div class="col-lg-6">
											<?php if (isset($row['DocDestinoDocEntry']) && $row['DocDestinoDocEntry'] != "") { ?>
													<a href="llamada_servicio.php?id=<?php echo base64_encode($row['DocDestinoDocEntry']); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('gestionar_solicitudes_llamadas.php'); ?>&tl=1"
														target="_blank" class="btn btn-outline btn-primary pull-right">
														Ir a documento destino <i class="fa fa-external-link"></i>
													</a>
											<?php } ?>
										</div>
										<!-- /.col-lg-6 -->
									</div>
									<!-- /.form-group -->
								</div>
								<!-- /.ibox-content -->
							</div>
							<!-- /.ibox -->
						</div>
					</div>
				</div>
			<br>
			 <div class="ibox-content">
				  <?php include "includes/spinner.php"; ?>
		  <div class="row">
		   <div class="col-lg-12">
			  <form action="solicitud_llamada.php" method="post" class="form-horizontal" enctype="multipart/form-data" id="CrearLlamada">
				<div id="DatosCliente" <?php //if($row['TipoTarea']=='Interna'){ echo 'style="display: none;"';}?>>
					<div class="ibox">
						<div class="ibox-title bg-success">
							<h5 class="collapse-link"><i class="fa fa-group"></i> Información de cliente</h5>
							<a class="collapse-link pull-right">
								<i class="fa fa-chevron-up"></i>
							</a>
						</div>
						<div class="ibox-content">
							<div class="form-group">
								<div class="col-lg-4">
									<label class="control-label"><i onClick="ConsultarDatosCliente();" title="Consultar cliente" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Cliente <span class="text-danger">*</span></label>
									<input name="ClienteLlamada" type="hidden" id="ClienteLlamada" value="<?php if (($edit == 1) || ($sw_error == 1)) {
										echo $row['ID_CodigoCliente'];
									} elseif ($dt_LS == 1) {
										echo $row_Cliente['CodigoCliente'];
									} ?>">
									<input name="NombreClienteLlamada" type="text" required class="form-control" id="NombreClienteLlamada" placeholder="Digite para buscar..." <?php if (($edit == 1) && ((!$ActualizarSolicitud) || ($row['IdEstadoLlamada'] == '-1') || ($row['TipoTarea'] == 'Interna')) || ($dt_LS == 1) || ($edit == 1)) {
										echo "readonly";
									} ?> value="<?php if (($edit == 1) || ($sw_error == 1)) {
										echo $row['NombreClienteLlamada'];
									} elseif ($dt_LS == 1) {
										echo $row_Cliente['NombreCliente'];
									} ?>">
								</div>
								<div class="col-lg-4">
									<label class="control-label">Contacto</label>
									<select name="ContactoCliente" class="form-control" id="ContactoCliente">
										<?php if (($edit == 0) || ($sw_error == 1)) { ?>
												<option value="">Seleccione...</option>
										<?php } ?>
										
										<?php if (($edit == 1) || ($sw_error == 1)) { ?>
												<?php while ($row_ContactoCliente = sqlsrv_fetch_array($SQL_ContactoCliente)) { ?>
														<option value="<?php echo $row_ContactoCliente['CodigoContacto']; ?>" <?php if ((isset($row['IdContactoLLamada'])) && (strcmp($row_ContactoCliente['CodigoContacto'], $row['IdContactoLLamada']) == 0)) {
															echo "selected";
														} ?>><?php echo $row_ContactoCliente['ID_Contacto']; ?></option>
												<?php } ?>
										<?php } ?>
									</select>
								</div>
								<div class="col-lg-4">
									<label class="control-label">Sucursal <span class="text-danger">*</span></label>
									<select name="SucursalCliente" class="form-control select2" id="SucursalCliente" required>
										<?php if (($edit == 0) || ($sw_error == 1)) { ?><option value="">Seleccione...</option><?php } ?>
										
										<?php if (($edit == 1) || ($sw_error == 1)) { ?>
												<?php while ($row_SucursalCliente = sqlsrv_fetch_array($SQL_SucursalCliente)) { ?>
														<option value="<?php echo $row_SucursalCliente['NombreSucursal']; ?>" <?php if (isset($row['NombreSucursal']) && (strcmp($row_SucursalCliente['NombreSucursal'], $row['NombreSucursal']) == 0)) {
															echo "selected";
														} elseif (isset($row['NombreSucursal']) && (strcmp($row_SucursalCliente['NumeroLinea'], $row['IdNombreSucursal']) == 0)) {
															echo "selected";
															$sw_valDir = 1;
														} ?>><?php echo $row_SucursalCliente['NombreSucursal']; ?></option>
												<?php } ?>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Dirección <span class="text-danger">*</span></label>
								<input name="DireccionLlamada" type="text" required class="form-control" id="DireccionLlamada" maxlength="100" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['DireccionLlamada'];
								} ?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">Barrio</label>
								<input name="BarrioDireccionLlamada" type="text" class="form-control" id="BarrioDireccionLlamada" maxlength="50" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['BarrioDireccionLlamada'];
								} ?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">Teléfono <span class="text-danger">*</span></label>
								<input name="TelefonoLlamada" type="text" class="form-control" required id="TelefonoLlamada" maxlength="50" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['TelefonoContactoLlamada'];
								} ?>">
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Ciudad</label>
								<input name="CiudadLlamada" type="text" class="form-control" id="CiudadLlamada" maxlength="100" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['CiudadLlamada'];
								} ?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">Correo</label>
								<input name="CorreoLlamada" type="email" class="form-control" id="CorreoLlamada" maxlength="100" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['CorreoContactoLlamada'];
								} ?>">
							</div>
						</div>
							
							<div class="form-group">
								<div class="col-lg-8 border-bottom">
									<label class="control-label text-danger">Información del servicio/equipo/articulo padre</label>
								</div>
							</div>

							<div class="form-group">
								<div class="col-lg-8">
									<label class="control-label">
										<i onclick="ConsultarArticulo();" title="Consultar ID Servicio" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> ID servicio padre <span class="text-danger">*</span>
									</label>

									<input name="IdArticuloLlamada" type="hidden" id="IdArticuloLlamada" value="<?php if (($edit == 1) || ($sw_error == 1)) {
										echo $row['IdArticuloLlamada'];
									} elseif ($dt_LS == 1 && isset($row_Articulo['ItemCode'])) {
										echo $row_Articulo['ItemCode'];
									} ?>">

									<!-- Descripción del Item -->
									<input name="DeArticuloLlamada" type="text" required class="form-control" id="DeArticuloLlamada" placeholder="Digite para buscar..."
								
									value="<?php if (($edit == 1 || $sw_error == 1 || $dt_LS == 1) && isset($row_Articulo['ItemCode'])) {
										echo $row_Articulo['ItemCode'] . " - " . $row_Articulo['ItemName'];
									} ?>" <?php if(PermitirFuncion(346)) { echo "readonly"; } ?>>
								</div>
								
								<div class="col-lg-3">
									<label class="control-label">
										<i onclick="ConsultarEquipo();" title="Consultar Tarjeta Equipo"
											style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Tarjeta de equipo padre
											<?php if(PermitirFuncion(347)) { ?><span class="text-danger">*</span><?php } ?>
									</label>

									<!-- Se necesita el SerialInterno para el llamado al WebService. SMM, 27/11/2023 -->
									<input type="hidden" class="form-control" name="SerialInterno" id="SerialInterno"
										value="<?php echo $row_NumeroSerie['SerialInterno'] ?? ""; ?>">

									<input type="hidden" class="form-control" name="NumeroSerie" id="NumeroSerie"
										value="<?php if (isset($row_NumeroSerie['IdTarjetaEquipo']) && ($row_NumeroSerie['IdTarjetaEquipo'] != 0)) {
											echo $row_NumeroSerie['IdTarjetaEquipo'];
										} ?>">
									<input readonly type="text" class="form-control" <?php if(PermitirFuncion(347)) { echo "required"; } ?>
										name="Desc_NumeroSerie" id="Desc_NumeroSerie"
										placeholder="Haga clic en el botón"
										value="<?php if (isset($row_NumeroSerie['IdTarjetaEquipo']) && ($row_NumeroSerie['IdTarjetaEquipo'] != 0)) {
											echo "SN Fabricante: " . ($row_NumeroSerie['SerialFabricante'] ?? "") . " - Núm. Serie: " . ($row_NumeroSerie['SerialInterno'] ?? "");
										} ?>">
								</div>
								<!-- /#NumeroSerie -->

								<br>
								<button type="button" class="btn btn-sm btn-success btn-circle" title="Cambiar Tarjeta Equipo"
									onclick="$('#mdTE').modal('show');">
									<i class="fa fa-refresh"></i>
								</button>
								<button type="button" id="AddCampana" class="btn btn-sm btn-info btn-circle" title="Adicionar Campaña" disabled <?php if ($edit == 1) {
									echo "style='display: none;'";
								} ?>>
									<i class="fa fa-bell"></i>
								</button>
							</div>

							<div class="form-group" <?php if ($edit == 1) {
								echo "style='display: none;'";
							} ?>>
								<div class="col-lg-8">
									<label class="control-label">Campañas Asociadas</label>

									<select name="Campanas[]" id="Campanas" class="form-control select2" data-placeholder="Debe seleccionar las campañas que desea asociar con la Solicitud de Llamada de servicio." multiple>
										<!-- Generadas por JS -->
									</select>
								</div>
							</div>
							<!-- /#CampanasAsociadas -->

							<div class="form-group">
								<div class="col-lg-8 border-bottom">
									<label class="control-label text-danger">Información del servicio/equipo/articulo componente</label>
								</div>
							</div>
							
							<div class="form-group">
								<div class="col-lg-8">
									<label class="control-label">
										<i onclick="ConsultarArticulo(true);" title="Consultar Articulo Componente"
											style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i>
										ID servicio componente
									</label>

									<input type="hidden" class="form-control" name="IdArticuloComponente" id="IdArticuloComponente"
										value="<?php echo $row["IdArticuloComponente"] ?? ""; ?>" readonly>

									<input type="text" class="form-control" name="ArticuloComponente" id="ArticuloComponente" readonly
										value="<?php echo ($id_tarjeta_equipo_hijo == "") ? "" : $de_articulo_encabezado; ?>">
								</div>

								<div class="col-lg-3">
									<label class="control-label">
										<i onclick="ConsultarEquipo(true);" title="Consultar Tarjeta Equipo Componente"
											style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i>
										Tarjeta de equipo componente
									</label>

									<input type="hidden" class="form-control" name="IdTarjetaEquipoComponente" id="IdTarjetaEquipoComponente"
										value="<?php echo $row["IdTarjetaEquipoComponente"] ?? ""; ?>" readonly>

									<input readonly type="text" class="form-control"
										name="DeTarjetaEquipoComponente" id="DeTarjetaEquipoComponente"
										placeholder="Haga clic en el botón"
										value="<?php echo ($id_tarjeta_equipo_hijo == "") ? "" : $descripcion_te_encabezado; ?>">
								</div>
								<!-- /#NumeroSerie -->

								<br>
								<button type="button" class="btn btn-sm btn-success btn-circle" title="Cambiar Tarjeta Equipo Componente"
									onclick="$('#mdTE_Componente').modal('show');">
									<i class="fa fa-refresh"></i>
								</button>
							</div>

							<div class="form-group">
								<div class="col-lg-4" <?php if (!$IncluirCamposAdicionales) { ?> style="display: none;" <?php } ?>>
									<label class="control-label">Cantidad artículo</label>
								<input name="CantArticulo" type="text" class="form-control" id="CantArticulo" maxlength="50" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo number_format($row['CDU_CantArticulo'], 2);
								} ?>" onkeypress="return justNumbers(event,this.value);" onkeyup="revisaCadena(this);">
								</div>
								<div class="col-lg-4" <?php if (!$IncluirCamposAdicionales) { ?> style="display: none;" <?php } ?>>
									<label class="control-label">Precio artículo</label>
								<input name="PrecioArticulo" type="text" class="form-control" id="PrecioArticulo" maxlength="50" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo number_format($row['CDU_PrecioArticulo'], 2);
								} ?>" onkeypress="return justNumbers(event,this.value);" onkeyup="revisaCadena(this);">
								</div>
							</div>
							<div class="form-group">
								<div class="col-lg-8 border-bottom">
									<label class="control-label text-danger">Información de la lista de materiales</label>
								</div>
							</div>
							<div class="form-group">
								<div class="col-lg-8">
									<label class="control-label"><i onClick="ConsultarMateriales();" title="Consultar Lista de Materiales" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> ID lista de materiales</label>
									<select name="CDU_ListaMateriales" class="form-control select2" id="CDU_ListaMateriales">
											<option value="">Seleccione...</option>
											
											<?php if (($edit == 1) || ($sw_error == 1)) { ?>
													<?php while ($row_ListaMateriales = sqlsrv_fetch_array($SQL_ListaMateriales)) { ?>
															<option value="<?php echo $row_ListaMateriales['ItemCode']; ?>" <?php if ((isset($row['CDU_ListaMateriales'])) && (strcmp($row_ListaMateriales['ItemCode'], $row['CDU_ListaMateriales']) == 0)) {
																echo "selected";
															} ?>><?php echo $row_ListaMateriales['ItemName']; ?></option>
													<?php } ?>
											<?php } ?>
									</select>
								</div>

								<div class="col-lg-3">
									<label class="control-label">Tiempo tarea (Minutos) <span class="text-danger">*</span></label>
									<input name="CDU_TiempoTarea" type="number" class="form-control" id="CDU_TiempoTarea" required value="<?php if (($edit == 1) || ($sw_error == 1)) {
										echo $row['CDU_TiempoTarea'];
									} ?>">
								</div>
							</div>
						</div>
					</div>	
				</div>
				<!-- /# DatosCliente -->

				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-info-circle"></i> Información de servicio</h5>
						 <a class="collapse-link pull-right">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<div class="form-group">
							<div class="col-lg-8 border-bottom m-r-sm">
								<label class="control-label text-danger">Información básica</label>
							</div>
							<div class="col-lg-3 border-bottom ">
								<label class="control-label text-danger">Programación</label>
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Serie <span class="text-danger">*</span></label>
								<select name="Series" class="form-control TecnicoSugerido" required id="Series">
									<option value="">Seleccione...</option>
									
									<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
											<option value="<?php echo $row_Series['IdSeries']; ?>"
											<?php if ((isset($row['Series'])) && (strcmp($row_Series['IdSeries'], $row['Series']) == 0)) {
												echo "selected";
											} elseif ((isset($row['IdSeries'])) && (strcmp($row_Series['IdSeries'], $row['IdSeries']) == 0)) {
												echo "selected";
											} ?>>
												<?php echo $row_Series['DeSeries']; ?>
											</option>
									<?php } ?>
								</select>
							</div>
							<!-- /.col-lg-4 -->

							<div class="col-lg-2">
								<label class="control-label">Número de llamada</label>
								<input autocomplete="off" name="Ticket" type="text" class="form-control" id="Ticket" maxlength="50" readonly="readonly" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['DocNum'];
								} ?>">
							</div>
							<!-- /.col-lg-2 -->

							<div class="col-lg-2">
								<label class="control-label">ID de solicitud</label>
								<input autocomplete="off" name="CallID" type="text" class="form-control" id="CallID" maxlength="50" readonly="readonly" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['ID_SolicitudLlamadaServicio'];
								} ?>">
							</div>
							<!-- /.col-lg-2 -->

							<div class="col-lg-4">
								<!-- Fecha de Solicitud -->
								<label class="control-label">Fecha Inicio Solicitud <span class="text-danger">*</span></label>
								
								<div class="input-group date">
									<span class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</span>
									<input required type="text" name="FechaCreacion" id="FechaCreacion" class="form-control fecha"
										value="<?php echo $ValorFechaCreacion; ?>">
								</div>
							</div>
							<!-- /.col-lg-4 -->
						</div>

						<div class="form-group">
							<div class="col-lg-8">
								<label class="control-label">Asunto de llamada <span class="text-danger">*</span></label>
								<input autocomplete="off" name="AsuntoLlamada" type="text" required class="form-control" id="AsuntoLlamada" maxlength="150" value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['AsuntoLlamada'];
								} else {
									echo $TituloLlamada;
								} ?>">
							</div>

							<div class="col-lg-4">
								<!-- Fecha de Solicitud -->
								<label class="control-label">Hora Inicio Solicitud <span class="text-danger">*</span></label>
								
								<div class="input-group clockpicker" data-autoclose="true">
									<span class="input-group-addon">
										<i class="fa fa-clock-o"></i>
									</span>
									<input required type="text" name="HoraCreacion" id="HoraCreacion" class="form-control hora"
										value="<?php echo $ValorHoraCreacion; ?>">
								</div>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Origen <span class="text-danger">*</span></label>
								
								<select name="OrigenLlamada" class="form-control TecnicoSugerido" required id="OrigenLlamada">
									<option value="">Seleccione...</option>
									
									<?php while ($row_OrigenLlamada = sqlsrv_fetch_array($SQL_OrigenLlamada)) { ?>
											<option value="<?php echo $row_OrigenLlamada['IdOrigenLlamada']; ?>" <?php if (isset($row['IdOrigenLlamada']) && ($row_OrigenLlamada['IdOrigenLlamada'] == $row['IdOrigenLlamada'])) {
												   echo "selected";
											   } elseif ((!isset($row['IdOrigenLlamada'])) && ($OrigenLlamada == $row_OrigenLlamada['IdOrigenLlamada'])) {
												   echo "selected";
											   } ?>>
												<?php echo $row_OrigenLlamada['DeOrigenLlamada']; ?>
											</option>
									<?php } ?>
								</select>
							</div>

							<div class="col-lg-4">
								<label class="control-label">Tipo llamada (Tipo Cliente) <span class="text-danger">*</span></label>
								
								<select name="TipoLlamada" class="form-control" required id="TipoLlamada">
									<option value="">Seleccione...</option>
										  
									<?php while ($row_TipoLlamadas = sqlsrv_fetch_array($SQL_TipoLlamadas)) { ?>
											<option value="<?php echo $row_TipoLlamadas['IdTipoLlamada']; ?>" <?php if ((isset($row['IdTipoLlamada'])) && (strcmp($row_TipoLlamadas['IdTipoLlamada'], $row['IdTipoLlamada']) == 0)) {
												   echo "selected";
											   } elseif ((!isset($row['IdTipoLlamada'])) && ($TipoLlamada == $row_TipoLlamadas['IdTipoLlamada'])) {
												   echo "selected";
											   } ?>>
												<?php echo $row_TipoLlamadas['DeTipoLlamada']; ?>
											</option>
									<?php } ?>
								</select>
							</div>
							
							<div class="col-lg-4">
								<!-- Fecha de Solicitud -->
								<label class="control-label">Fecha Fin Solicitud <span class="text-danger">*</span></label>
								
								<div class="input-group date">
									<span class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</span>
									<input required type="text" name="FechaFinCreacion" id="FechaFinCreacion" class="form-control fecha"
										value="<?php echo $ValorFechaFinCreacion; ?>">
								</div>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Tipo problema (Tipo Servicio) <span class="text-danger">*</span></label>
								<select name="TipoProblema" class="form-control TecnicoSugerido" id="TipoProblema" required>
									<option value="">Seleccione...</option>
									  
									<?php while ($row_TipoProblema = sqlsrv_fetch_array($SQL_TipoProblema)) { ?>
											<option value="<?php echo $row_TipoProblema['IdTipoProblemaLlamada']; ?>" <?php if ((isset($row['IdTipoProblemaLlamada'])) && (strcmp($row_TipoProblema['IdTipoProblemaLlamada'], $row['IdTipoProblemaLlamada']) == 0)) {
												   echo "selected";
											   } elseif ((!isset($row['IdTipoProblemaLlamada'])) && ($TipoProblema == $row_TipoProblema['IdTipoProblemaLlamada'])) {
												   echo "selected";
											   } ?>>
												<?php echo $row_TipoProblema['DeTipoProblemaLlamada']; ?>
											</option>
									  <?php } ?>
								</select>
							</div>

							<div class="col-lg-4">
								<label class="control-label">SubTipo problema (Subtipo Servicio) <span class="text-danger">*</span></label>
								<select name="SubTipoProblema" class="form-control" id="SubTipoProblema" required>
									<option value="">Seleccione...</option>
									  
									<?php while ($row_SubTipoProblema = sqlsrv_fetch_array($SQL_SubTipoProblema)) { ?>
											<option value="<?php echo $row_SubTipoProblema['IdSubTipoProblemaLlamada']; ?>" <?php if ((isset($row['IdSubTipoProblemaLlamada'])) && (strcmp($row_SubTipoProblema['IdSubTipoProblemaLlamada'], $row['IdSubTipoProblemaLlamada']) == 0)) {
												   echo "selected";
											   } elseif ((!isset($row['IdSubTipoProblemaLlamada'])) && ($SubtipoProblema == $row_SubTipoProblema['IdSubTipoProblemaLlamada'])) {
												   echo "selected";
											   } ?>>
												<?php echo $row_SubTipoProblema['DeSubTipoProblemaLlamada']; ?>
											</option>
									<?php } ?>
								</select>
							</div>

							<div class="col-lg-4">
								<!-- Fecha de Solicitud -->
								<label class="control-label">Hora Fin Solicitud <span class="text-danger">*</span></label>
								
								<div class="input-group clockpicker" data-autoclose="true">
									<span class="input-group-addon">
										<i class="fa fa-clock-o"></i>
									</span>
									<input required type="text" name="HoraFinCreacion" id="HoraFinCreacion" class="form-control hora"
										value="<?php echo $ValorHoraFinCreacion; ?>">
								</div>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-4" <?php if (!$IncluirCamposAdicionales) { ?> style="display: none;" <?php } ?>>
								<label class="control-label"><i onClick="ConsultarContrato();" title="Consultar Contrato servicio" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Contrato servicio</label>
								<select name="ContratoServicio" class="form-control" id="ContratoServicio">
									<option value="">Seleccione...</option>
										
									<?php if (($edit == 1) || ($sw_error == 1)) { ?>
											<?php while ($row_Contrato = sqlsrv_fetch_array($SQL_Contrato)) { ?>
													<option value="<?php echo $row_Contrato['ID_Contrato']; ?>" <?php if ((isset($row_Contrato['ID_Contrato'])) && (strcmp($row_Contrato['ID_Contrato'], $row['IdContratoServicio']) == 0)) {
														   echo "selected";
													   } ?>><?php echo $row_Contrato['ID_Contrato'] . " - " . $row_Contrato['DE_Contrato']; ?></option>
											<?php } ?>
									<?php } ?>
								</select>
							</div>

							<div class="col-lg-4" <?php if (!$IncluirCamposAdicionales) { ?> style="display: none;" <?php } ?>>
								<label class="control-label">Cola</label>
								<select name="ColaLlamada" class="form-control" id="ColaLlamada">
									<option value="">Seleccione...</option>
								</select>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-4" <?php if (!$IncluirCamposAdicionales) { ?> style="display: none;" <?php } ?>>
								<label class="control-label">Aseguradora</label>
								
								<select name="CDU_Aseguradora" class="form-control select2"id="CDU_Aseguradora"
								>
									<option value="" disabled selected>Seleccione...</option>
									  
									<?php while ($row_Aseguradora = sqlsrv_fetch_array($SQL_Aseguradora)) { ?>
											<option value="<?php echo $row_Aseguradora['NombreAseguradora']; ?>"
											<?php if ((isset($row['CDU_Aseguradora'])) && (strcmp($row_Aseguradora['NombreAseguradora'], $row['CDU_Aseguradora']) == 0)) {
												echo "selected";
											} ?>>
												<?php echo $row_Aseguradora['NombreAseguradora']; ?>
											</option>
									  <?php } ?>
								</select>
							</div>

							<div class="col-lg-4" <?php if (!PermitirFuncion(327)) { ?> style="display: none;" <?php } ?>>
								<label class="control-label">Contrato/Campaña <span class="text-danger">*</span></label>
								
								<select name="CDU_Contrato" class="form-control select2" id="CDU_Contrato" required>
									<option value="" disabled selected>Seleccione...</option>
									  
									<?php while ($row_Contrato = sqlsrv_fetch_array($SQL_ContratosLlamada)) { ?>
										<option value="<?php echo $row_Contrato['NombreContrato']; ?>"
											<?php if ((isset($row['CDU_Contrato'])) && ($row_Contrato['NombreContrato'] == $row['CDU_Contrato'])) {
												echo "selected";
											} ?>>
											<?php echo $row_Contrato['NombreContrato']; ?>
										</option>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-8 border-bottom">
								<label class="control-label text-danger">Información de responsables</label>
							</div>
							<div class="col-lg-4 border-bottom">
								<label class="control-label text-danger">Estados de servicio</label>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">
									<?php echo (ObtenerVariable("LabelTecnicoResponsableSolicitudLlamada") == "") ? "Técnico/Asesor" : ObtenerVariable("LabelTecnicoResponsableSolicitudLlamada"); ?> 
									<?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?><span class="text-danger">*</span><?php } ?>
								</label>
								
								<select <?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?> required <?php } ?> name="Tecnico" class="form-control select2" id="Tecnico">
									<option value="">Seleccione...</option>
									  
									<?php while ($row_Tecnicos = sqlsrv_fetch_array($SQL_Tecnicos)) { ?>
											<?php if (in_array($row_Tecnicos['IdCargo'], $ids_grupos) || ($MostrarTodosRecursos || (count($ids_grupos) == 0))) { ?>
													<option value="<?php echo $row_Tecnicos['ID_Empleado']; ?>" <?php if ((isset($row['IdTecnico'])) && (strcmp($row_Tecnicos['ID_Empleado'], $row['IdTecnico']) == 0)) {
														   echo "selected";
													   } ?> 
													<?php if ((count($ids_grupos) > 0) && (!in_array($row_Tecnicos['IdCargo'], $ids_grupos))) {
														echo "disabled";
													} ?>>
														<?php echo $row_Tecnicos['NombreEmpleado'] . " (" . $row_Tecnicos['NombreCentroCosto2'] . " - " . $row_Tecnicos['DeCargo'] . ")"; ?>
													</option>
											<?php } ?>
									  <?php } ?>
								</select>
							</div>

							<!-- SMM -->
							<div class="col-lg-4">
								<!-- Fecha de Agenda -->
								<label class="control-label">Fecha Inicio Actividad <?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?><span class="text-danger">*</span><?php } ?></label>
								
								<div class="input-group date">
									<span class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</span>
									<input type="text" name="FechaAgenda" id="FechaAgenda" <?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?> required <?php } ?>
										class="form-control fechaAgenda" value="<?php echo $ValorFechaAgenda; ?>">
								</div>
							</div>
							<!-- 01/06/2022 -->
							
							<div class="col-lg-4">
								<label class="control-label">Estado <span class="text-danger">*</span></label>
								<select name="EstadoLlamada" class="form-control" id="EstadoLlamada" required>
								  <?php while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) { ?>
										<option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['IdEstadoLlamada'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['IdEstadoLlamada']) == 0)) {
											   echo "selected";
										   } ?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option>
								  <?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">
									<?php echo (ObtenerVariable("LabelTecnicoAdicionalSolicitudLlamada") == "") ? "Técnico/Asesor Adicional" : ObtenerVariable("LabelTecnicoAdicionalSolicitudLlamada"); ?> 
								</label>
								
								<select name="CDU_IdTecnicoAdicional" class="form-control select2" id="CDU_IdTecnicoAdicional">
									<option value="">Seleccione...</option>
								  
									<?php while ($row_Tecnicos = sqlsrv_fetch_array($SQL_TecnicosAdicionales)) { ?>
											<?php if (in_array($row_Tecnicos['IdCargo'], $ids_grupos) || ($MostrarTodosRecursos || (count($ids_grupos) == 0))) { ?>
													<option value="<?php echo $row_Tecnicos['ID_Empleado']; ?>" <?php if ((isset($row['CDU_IdTecnicoAdicional'])) && (strcmp($row_Tecnicos['ID_Empleado'], $row['CDU_IdTecnicoAdicional']) == 0)) {
														   echo "selected";
													   } ?>	
														<?php if ((count($ids_grupos) > 0) && (!in_array($row_Tecnicos['IdCargo'], $ids_grupos))) {
															echo "disabled";
														} ?>>
														<?php echo $row_Tecnicos['NombreEmpleado'] . " (" . $row_Tecnicos['NombreCentroCosto2'] . " - " . $row_Tecnicos['DeCargo'] . ")"; ?>
													</option>
											<?php } ?>
									<?php } ?>
								</select>
							</div>

							<!-- SMM -->
							<div class="col-lg-4">
								<!-- Fecha de Agenda -->
								<label class="control-label">Hora Inicio Actividad <?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?><span class="text-danger">*</span><?php } ?></label>
								
								<div class="input-group clockpicker" data-autoclose="true">
									<span class="input-group-addon">
										<i class="fa fa-clock-o"></i>
									</span>
									<input type="text" name="HoraAgenda" id="HoraAgenda" <?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?> required <?php } ?>
										class="form-control horaAgenda"  value="<?php echo $ValorHoraAgenda; ?>">
								</div>
							</div>
							<!-- 01/06/2022 -->

							<div class="col-lg-4">
								<label class="control-label">Estado de servicio <span class="text-danger">*</span></label>
								<select name="CDU_EstadoServicio" class="form-control" id="CDU_EstadoServicio" required>
								  <?php while ($row_EstServLlamada = sqlsrv_fetch_array($SQL_EstServLlamada)) { ?>
												<option value="<?php echo $row_EstServLlamada['id_tipo_estado_servicio_sol_llamada']; ?>" <?php if ((($edit == 0) && ($row_EstServLlamada['id_tipo_estado_servicio_sol_llamada'] == 0)) || ((isset($row['CDU_EstadoServicio'])) && (strcmp($row_EstServLlamada['id_tipo_estado_servicio_sol_llamada'], $row['CDU_EstadoServicio']) == 0))) {
													   echo "selected";
												   } ?>><?php echo $row_EstServLlamada['tipo_estado_servicio_sol_llamada']; ?></option>
								  <?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-4"></div>
							
							<!-- SMM -->
							<div class="col-lg-4">
								<!-- Fecha de Agenda -->
								<label class="control-label">Fecha Fin Actividad <?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?><span class="text-danger">*</span><?php } ?></label>
								
								<div class="input-group date">
									<span class="input-group-addon">
										<i class="fa fa-calendar"></i>
									</span>
									<input type="text" name="FechaFinAgenda" id="FechaFinAgenda" <?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?> required <?php } ?>
										class="form-control fechaAgenda" value="<?php echo $ValorFechaFinAgenda; ?>">
								</div>
							</div>
							<!-- 27/10/2023 -->

							<div class="col-lg-4">
								<label class="control-label">Cancelado por <span class="text-danger">*</span></label>

								<select name="CDU_CanceladoPor" class="form-control" id="CDU_CanceladoPor" required>
								  <?php while ($row_CanceladoPorLlamada = sqlsrv_fetch_array($SQL_CanceladoPorLlamada)) { ?>
											<option value="<?php echo $row_CanceladoPorLlamada['IdCanceladoPor']; ?>" <?php if ((isset($row['CDU_CanceladoPor'])) && (strcmp($row_CanceladoPorLlamada['IdCanceladoPor'], $row['CDU_CanceladoPor']) == 0)) {
												   echo "selected";
											   } ?>><?php echo $row_CanceladoPorLlamada['DeCanceladoPor']; ?></option>
								  <?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-4"></div>

							<!-- SMM -->
							<div class="col-lg-4">
								<!-- Fecha de Agenda -->
								<label class="control-label">Hora Fin Actividad <?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?><span class="text-danger">*</span><?php } ?></label>
								<div class="input-group clockpicker" data-autoclose="true">
									<span class="input-group-addon">
										<i class="fa fa-clock-o"></i>
									</span>
									<input type="text" name="HoraFinAgenda" id="HoraFinAgenda" <?php if (PermitirFuncion(323) && PermitirFuncion(304)) { ?> required <?php } ?>
										class="form-control horaAgenda" value="<?php echo $ValorHoraFinAgenda; ?>">
								</div>
							</div>
							<!-- 14/11/2023 -->

							<div class="col-lg-4"></div>
						</div>

						<div class="form-group">
							<div class="col-lg-4" <?php if (!$IncluirCamposAdicionales) { ?> style="visibility: hidden;" <?php } ?>>
								<label class="control-label">Asignado a</label>
								<select name="EmpleadoLlamada" class="form-control select2" id="EmpleadoLlamada">
									<option value="">(Sin asignar)</option>
									  
									<?php if ($IncluirCamposAdicionales) { ?>
											<?php while ($row_EmpleadoLlamada = sqlsrv_fetch_array($SQL_EmpleadoLlamada)) { ?>
													<option value="<?php echo $row_EmpleadoLlamada['ID_Empleado']; ?>" <?php if ((isset($row['IdAsignadoA'])) && (strcmp($row_EmpleadoLlamada['ID_Empleado'], $row['IdAsignadoA']) == 0)) {
														   echo "selected";
													   } elseif (($edit == 0) && (isset($_SESSION['CodigoSAP'])) && (strcmp($row_EmpleadoLlamada['ID_Empleado'], $_SESSION['CodigoSAP']) == 0)) {
														   echo "selected";
													   } ?>>
														<?php echo $row_EmpleadoLlamada['NombreEmpleado']; ?>
													</option>
											<?php } ?>
									<?php } ?>
								</select>
							</div>

							<div class="col-lg-4" <?php if (!$IncluirCamposAdicionales) { ?> style="visibility: hidden;" <?php } ?>>
								<label class="control-label">Proyecto</label>
								
								<select name="Proyecto" class="form-control select2" id="Proyecto">
									<option value="">Seleccione...</option>

									<?php if ($IncluirCamposAdicionales) { ?>
											<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
													<option value="<?php echo $row_Proyecto['IdProyecto']; ?>" <?php if ((isset($row['IdProyecto'])) && (strcmp($row_Proyecto['IdProyecto'], $row['IdProyecto']) == 0)) {
														   echo "selected";
													   } ?>>
														<?php echo $row_Proyecto['DeProyecto']; ?>
													</option>
											<?php } ?>
									<?php } ?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<div class="col-lg-8">
								<label class="control-label">Comentario <span class="text-danger">*</span></label>
								<textarea name="ComentarioLlamada" rows="7" maxlength="3000" required class="form-control" id="ComentarioLlamada" type="text"><?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['ComentarioLlamada'];
								} ?></textarea>
							</div>							
						</div>
						<!-- /.form-group -->
						</div>
				</div>
				
				
	<!-- INICIO, información del vehículo y de la cita -->
	<div class="ibox" <?php if(!PermitirFuncion(327)) { echo "style='display: none'"; } ?>>
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-info-circle"></i> Información del vehículo y de la cita</h5>
						 <a class="collapse-link pull-right">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">

						<!-- Agregado por Stiven Muñoz Murillo -->
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Kilometros <span class="text-danger">*</span></label>
								<input autocomplete="off" name="CDU_Kilometros" type="number" class="form-control" id="CDU_Kilometros" maxlength="100"
								value="<?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['CDU_Kilometros'];
								} ?>" required>
							</div>

							<!-- SMM, 14/09/2022 -->
							<div class="col-lg-4">
								<label class="control-label">Tipo preventivo <span class="text-danger">*</span></label>
								<select name="CDU_TipoPreventivo" class="form-control select2" required id="CDU_TipoPreventivo"
								>
									<option value="" disabled selected>Seleccione...</option>
									
									<?php while ($row_TipoPreventivo = sqlsrv_fetch_array($SQL_TipoPreventivo)) { ?>
											<option value="<?php echo $row_TipoPreventivo['CodigoTipoPreventivo']; ?>"
											<?php if ((isset($row['CDU_TipoPreventivo'])) && (strcmp($row_TipoPreventivo['CodigoTipoPreventivo'], $row['CDU_TipoPreventivo']) == 0)) {
												echo "selected";
											} ?>>
												<?php echo $row_TipoPreventivo['TipoPreventivo']; ?>
											</option>
									<?php } ?>
								</select>
							</div>
							<!-- Hasta aquí, 14/09/2022 -->
						</div>
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Marca del vehículo <span class="text-danger">*</span></label>
								<select name="CDU_Marca" class="form-control select2 TecnicoSugerido" required id="CDU_Marca"
								>
									<option value="" disabled selected>Seleccione...</option>
								  
									<?php while ($row_MarcaVehiculo = sqlsrv_fetch_array($SQL_MarcaVehiculo)) { ?>
											<option value="<?php echo $row_MarcaVehiculo['IdMarcaVehiculo']; ?>"
												<?php if ((isset($row['CDU_IdMarca'])) && (strcmp($row_MarcaVehiculo['IdMarcaVehiculo'], $row['CDU_IdMarca']) == 0)) {
													echo "selected";
												} elseif ((isset($row['CDU_Marca'])) && (strcmp($row_MarcaVehiculo['IdMarcaVehiculo'], $row['CDU_Marca']) == 0)) {
													echo "selected";
												} ?>>
												<?php echo $row_MarcaVehiculo['DeMarcaVehiculo']; ?>
											</option>
									  <?php } ?>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Línea del vehículo <span class="text-danger">*</span></label>
								
								<select name="CDU_Linea" class="form-control select2" required id="CDU_Linea"
								>
									<option value="" disabled selected>Seleccione...</option>
									  
									<?php while ($row_LineaVehiculo = sqlsrv_fetch_array($SQL_LineaVehiculo)) { ?>
											<option value="<?php echo $row_LineaVehiculo['IdLineaModeloVehiculo']; ?>"
												<?php if ((isset($row['CDU_IdLinea'])) && (strcmp($row_LineaVehiculo['IdLineaModeloVehiculo'], $row['CDU_IdLinea']) == 0)) {
													echo "selected";
												} elseif ((isset($row['CDU_Linea'])) && (strcmp($row_LineaVehiculo['IdLineaModeloVehiculo'], $row['CDU_Linea']) == 0)) {
													echo "selected";
												} ?>>
												<?php echo $row_LineaVehiculo['DeLineaModeloVehiculo']; ?>
											</option>
									  <?php } ?>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Modelo del vehículo <span class="text-danger">*</span></label>
								
								<select name="CDU_Ano" class="form-control select2" required id="CDU_Ano"
								>
									<option value="" disabled selected>Seleccione...</option>
									  
									<?php while ($row_ModeloVehiculo = sqlsrv_fetch_array($SQL_ModeloVehiculo)) { ?>
											<option value="<?php echo $row_ModeloVehiculo['CodigoModeloVehiculo']; ?>"
												<?php if ((isset($row['CDU_Ano'])) && ((strcmp($row_ModeloVehiculo['CodigoModeloVehiculo'], $row['CDU_Ano']) == 0) || (strcmp($row_ModeloVehiculo['AñoModeloVehiculo'], $row['CDU_Ano']) == 0))) {
													echo "selected";
												} ?>>
												<?php echo $row_ModeloVehiculo['AñoModeloVehiculo']; ?>
											</option>
									  <?php } ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Concesionario <span class="text-danger">*</span></label>
								
								<select name="CDU_Concesionario" class="form-control select2" required id="CDU_Concesionario"
								>
									<option value="" disabled selected>Seleccione...</option>
								  
									<?php while ($row_Concesionario = sqlsrv_fetch_array($SQL_Concesionario)) { ?>
											<option value="<?php echo $row_Concesionario['NombreConcesionario']; ?>"
												<?php if ((isset($row['CDU_Concesionario'])) && (strcmp($row_Concesionario['NombreConcesionario'], $row['CDU_Concesionario']) == 0)) {
													echo "selected";
												} ?>>
												<?php echo $row_Concesionario['NombreConcesionario']; ?>
											</option>
									  <?php } ?>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Tipo servicio <span class="text-danger">*</span></label>
								
								<select name="CDU_TipoServicio" class="form-control select2" required id="CDU_TipoServicio"
								>
										<option value="" disabled selected>Seleccione...</option>
								  
										<?php while ($row_TipoServicio = sqlsrv_fetch_array($SQL_TipoServicio)) { ?>
												<option value="<?php echo $row_TipoServicio['NombreTipoServicio']; ?>"
													<?php if ((isset($row['CDU_TipoServicio'])) && (strcmp($row_TipoServicio['NombreTipoServicio'], $row['CDU_TipoServicio']) == 0)) {
														echo "selected";
													} ?>>
													<?php echo $row_TipoServicio['NombreTipoServicio']; ?>
												</option>
										  <?php } ?>
								</select>
							</div>
						</div>
						<!-- Agregado, hasta aquí -->
					</div>
				</div>
				<!-- FIN, información del vehículo y de la cita -->

				<!-- Inicio, información adicional -->
				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-edit"></i> Información adicional</h5>
						 <a class="collapse-link pull-right">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<div class="form-group">
							<div class="col-lg-5 border-bottom m-r-sm">
								<label class="control-label text-danger">Información del contacto del cliente</label>
							</div>
							<div class="col-lg-6 border-bottom ">
								<label class="control-label text-danger">Información del servicio</label>
							</div>
						</div>
						<div class="col-lg-5 m-r-md">
							<div class="form-group">
								<label class="control-label">Nombre de contacto <?php if (PermitirFuncion(324)) { ?><span class="text-danger">*</span><?php } ?></label>
								<input <?php if (PermitirFuncion(324)) { ?> required <?php } ?> autocomplete="off" name="CDU_NombreContacto" type="text" class="form-control" id="CDU_NombreContacto" maxlength="100" value="<?php if (($edit == 1) || ($sw_error == 1)) {
										   echo $row['CDU_NombreContacto'];
									   } ?>">
							</div>
							<div class="form-group">
								<label class="control-label">Cargo de contacto <?php if (PermitirFuncion(324)) { ?><span class="text-danger">*</span><?php } ?></label>
								<input <?php if (PermitirFuncion(324)) { ?> required <?php } ?> autocomplete="off" name="CDU_CargoContacto" type="text" class="form-control" id="CDU_CargoContacto" maxlength="100" value="<?php if (($edit == 1) || ($sw_error == 1)) {
										   echo $row['CDU_CargoContacto'];
									   } ?>">
							</div>
							<div class="form-group">
								<label class="control-label">Teléfono de contacto <?php if (PermitirFuncion(324)) { ?><span class="text-danger">*</span><?php } ?></label>
								<input <?php if (PermitirFuncion(324)) { ?> required <?php } ?> autocomplete="off" name="CDU_TelefonoContacto" type="text" class="form-control" id="CDU_TelefonoContacto" maxlength="100" value="<?php if (($edit == 1) || ($sw_error == 1)) {
										   echo $row['CDU_TelefonoContacto'];
									   } ?>">
							</div>
							<div class="form-group">
								<label class="control-label">Correo de contacto <?php if (PermitirFuncion(324)) { ?><span class="text-danger">*</span><?php } ?></label>
								<input <?php if (PermitirFuncion(324)) { ?> required <?php } ?> autocomplete="off" name="CDU_CorreoContacto" type="email" class="form-control" id="CDU_CorreoContacto" maxlength="100" value="<?php if (($edit == 1) || ($sw_error == 1)) {
										   echo $row['CDU_CorreoContacto'];
									   } ?>">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label class="control-label">Servicios</label>
								<textarea name="CDU_Servicios" rows="5" maxlength="2000" class="form-control" id="CDU_Servicios" type="text"><?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['CDU_Servicios'];
								} ?></textarea>
							</div>
							<div class="form-group">
								<label class="control-label">Áreas</label>
								<textarea name="CDU_Areas" rows="5" maxlength="2000" class="form-control" id="CDU_Areas" type="text"><?php if (($edit == 1) || ($sw_error == 1)) {
									echo $row['CDU_Areas'];
								} ?></textarea>
							</div>
						</div>
					</div>
				</div>
				<!-- Fin, información adicional -->
				
				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-paperclip"></i> Anexos</h5>
						 <a class="collapse-link pull-right">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<!-- Inicio, cargar anexos -->
						<?php if ($edit == 1) { ?>
								<?php if ($SQL_AnexoSolicitudLlamada && sqlsrv_has_rows($SQL_AnexoSolicitudLlamada)) { ?>
										<div class="form-group">
											<div class="col-xs-12">
												<?php while ($row_Anexo = sqlsrv_fetch_array($SQL_AnexoSolicitudLlamada)) { ?>
														<?php $Icon = IconAttach($row_Anexo['FileExt']); ?>

														<div class="file-box">
															<div class="file">	
																<a href="filedownload.php?file=<?php echo base64_encode($row_Anexo['FileName'] . "." . $row_Anexo['FileExt']); ?>&dir=<?php echo base64_encode($dir_new); ?>" target="_blank" title="Descargar archivo">
																	<div class="icon">
																		<i class="<?php echo $Icon; ?>"></i>
																	</div>
																	<div class="file-name">
																		<?php echo $row_Anexo['FileName']; ?>
																		<br/>
																		<small><?php echo $row_Anexo['Fecha']->format("Y-m-d h:i:s"); ?></small>
																	</div>
																</a>
															</div>
														</div>
												<?php } ?>
											</div>
										</div>
							<?php } else { ?>
								<p>Sin anexos.</p>
							<?php } ?>
						<?php } ?>
						<!-- Fin, cargar anexos -->

						<?php
						if (isset($_GET['return'])) {
							$return = base64_decode($_GET['pag']) . "?" . $_GET['return'];
						} else {
							$return = "gestionar_solicitudes_llamadas.php";
						}
						$return = QuitarParametrosURL($return, array("a"));
						?>
						
						<input type="hidden" id="P" name="P" value="<?php if (($edit == 0) && ($sw_error == 0)) {
							echo "32";
						} else {
							echo "33";
						} ?>">
						
						<input type="hidden" id="swTipo" name="swTipo" value="0">
						<input type="hidden" id="swError" name="swError" value="<?php echo $sw_error; ?>">
						<input type="hidden" id="tl" name="tl" value="<?php echo $edit; ?>">
						
						<input type="hidden" id="IdLlamadaPortal" name="IdLlamadaPortal" value="<?php if (isset($row['ID_SolicitudLlamadaServicio'])) {
							echo base64_encode($row['ID_SolicitudLlamadaServicio']);
						} ?>">
						
						<input type="hidden" id="DocEntry" name="DocEntry" value="<?php if (isset($row['ID_SolicitudLlamadaServicio'])) {
							echo base64_encode($row['ID_SolicitudLlamadaServicio']);
						} ?>">
						
						<input type="hidden" id="DocNum" name="DocNum" value="<?php if (isset($row['ID_SolicitudLlamadaServicio'])) {
							echo base64_encode($row['ID_SolicitudLlamadaServicio']);
						} ?>">

						<input type="hidden" id="IdSucursalCliente" name="IdSucursalCliente" value="<?php if ($edit == 1) {
							echo $row['IdNombreSucursal'];
						} ?>">

						<input type="hidden" id="IdAnexos" name="IdAnexos" value="<?php if ($edit == 1) {
							echo $row['IdAnexoLlamada'];
						} ?>" />
						</form>

						<!-- Inicio, agregar anexos -->
					   <?php if (($edit == 0) || (($edit == 1) && ($row['IdEstadoLlamada'] != '-1'))) { ?>
							<div class="row">
								<form action="upload.php" class="dropzone" id="dropzoneForm" name="dropzoneForm">
									<?php if ($sw_error == 0) {
										LimpiarDirTemp();
									} ?>
									<div class="fallback">
										<input name="File" id="File" type="file" form="dropzoneForm" />
									</div>
									</form>
							</div>
						<?php } ?>
						<!-- Fin, agregar anexos -->
					</div>
				</div>

				   <div class="form-group">
						<br>
						<div class="col-lg-8">
							<?php if (($edit == 1) && (($row['IdEstadoLlamada'] == '-3') || ($row['IdEstadoLlamada'] == '-2'))) { ?>
									<button class="btn btn-warning" type="submit" form="CrearLlamada" id="Actualizar" <?php if (!$ActualizarSolicitud) {
										echo "disabled";
									} ?>>
										<i class="fa fa-refresh"></i> Actualizar Solicitud (Agenda)
									</button>
								
									<button id="copiarSolicitud" style="margin-left: 10px;" class="alkin btn btn-success">
										<i class="fa fa-copy"></i> Copiar a Llamada Servicio
									</button>
							<?php } elseif (($edit == 0) && $CrearSolicitud) { ?>
									<button class="btn btn-primary" form="CrearLlamada" type="submit" id="Crear">
										<i class="fa fa-check"></i> Crear Solicitud (Agenda)
									</button>
							<?php } ?>
						</div>

						<div class="col-lg-4">
							<a href="<?php echo $return; ?>" class="alkin btn btn-outline btn-default pull-right"><i class="fa fa-arrow-circle-o-left"></i> Regresar</a>
						</div>
					</div>
					
					<br><br><br>
				   <?php if ($edit == 1) { ?>
						<div class="ibox">
							<div class="ibox-title bg-success">
								<h5 class="collapse-link"><i class="fa fa-pencil-square-o"></i> Seguimiento de la Solicitud de Llamada</h5>
								<a class="collapse-link pull-right">
									<i class="fa fa-chevron-up"></i>
								</a>
							</div>

							<div class="ibox-content">
								<div class="tabs-container">
									<ul class="nav nav-tabs">
										<li class="active" id="nav-1"><a data-toggle="tab" href="#tab-1"><i class="fa fa-clipboard"></i> Anotaciones</a></li>
										<li id="nav-2"><a data-toggle="tab" href="#tab-2"><i class="fa fa-tags"></i> Documentos relacionados</a></li>
										<li id="nav-4"><a data-toggle="tab" href="#tab-4"><i class="fa fa-bell"></i> Campañas</a></li>
									</ul>

									<div class="tab-content">
											<div id="tab-1" class="tab-pane active">
												<div class="panel-body">
													<div class="row">
														<?php if (isset($row['IdEstadoLlamada']) && ($row['IdEstadoLlamada'] != "-1")) { ?>
																<button type="button" onclick="AdicionarAnotacion()" class="alkin btn btn-primary btn-xs"><i class="fa fa-plus-circle"></i> Adicionar Anotación</button>
														<?php } ?>
													</div>
													<br>
												
													<!-- Table Campanas -->
													<div class="row">
														<div class="col-12 text-center">
															<div class="ibox-content">
																<?php if ($hasRowsAnotaciones) { ?>
																		<div class="table-responsive">
																			<table class="table table-striped table-bordered table-hover dataTables-example">
																				<thead>
																					<tr>
																						<th>Número</th>
																						<th>Fecha</th>
																						<th>Evento</th>
																						<th>Comentarios</th>
																						<th>Usuario Actualización</th>
																						<th>Fecha Actualización</th>
																					</tr>
																				</thead>
																				<tbody>
																					<?php while ($row_Anotaciones = sqlsrv_fetch_array($SQL_Anotaciones)) { ?>
																							<tr class="gradeX">
																								<td><?php echo $row_Anotaciones['linea']; ?></td>
																					
																								<td>
																									<?php if ($row_Anotaciones['fecha_anotacion'] != "") {
																										echo $row_Anotaciones['fecha_anotacion']->format('Y-m-d');
																									} else { ?>
																											<p class="text-muted">--</p>
																									<?php } ?>
																								</td>

																								<td><?php echo $row_Anotaciones['tipo_anotacion']; ?></td>
																								<td><?php echo $row_Anotaciones['comentarios_anotacion']; ?></td>

																								<td><?php echo $row_Anotaciones['usuario_actualizacion']; ?></td>

																								<td>
																									<?php if ($row_Anotaciones['fecha_actualizacion'] != "") {
																										echo $row_Anotaciones['fecha_actualizacion']->format('Y-m-d H:i:s');
																									} else { ?>
																											<p class="text-muted">--</p>
																									<?php } ?>
																								</td>
																							</tr>
																					<?php } ?>
																				</tbody>
																			</table>
																		</div>	
																	<?php } else { ?>
																		<i class="fa fa-search" style="font-size: 18px; color: lightgray;"></i>
																		<span style="font-size: 13px; color: lightgray;">No hay Anotaciones para mostrar</span>
																<?php } ?>
															</div>
															<!-- /.ibox-content -->
														</div>
													</div>
													<!-- End Table Campanas -->
												</div>
												<!-- /.panel-body -->
											</div>
											<!-- /#tab-1 -->

										<div id="tab-2" class="tab-pane">
											<div class="panel-body">
												<!-- Agregar documento, Inicio -->
												<div class="row">
													<div class="col-lg-9">
														<?php if ($CrearSolicitud && ($row['IdEstadoLlamada'] != '-1')) { ?>
																<?php if (PermitirFuncion([401, 402, 404, 409])) { ?>
																		<div class="btn-group">
																			<button data-toggle="dropdown" class="btn btn-outline btn-success dropdown-toggle"><i class="fa fa-plus-circle"></i> Agregar documento <i class="fa fa-caret-down"></i></button>
																			<ul class="dropdown-menu">
																				<?php if (PermitirFuncion(401)) { ?>
																						<li><a class="dropdown-item alkin d-venta" href="oferta_venta.php?dt_SLS=1&dt_LS=1&Cardcode=<?php echo base64_encode($row['ID_CodigoCliente']); ?>&Contacto=<?php echo base64_encode($row['IdContactoLLamada']); ?>&Sucursal=<?php echo base64_encode($row['NombreSucursal']); ?>&Direccion=<?php echo base64_encode($row['DireccionLlamada']); ?>&TipoLlamada=<?php echo base64_encode($row['IdTipoLlamada']); ?>&ItemCode=<?php echo base64_encode($row['CDU_ListaMateriales']); ?>&SLS=<?php echo base64_encode($IdSolicitud); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('solicitud_llamada.php'); ?>">Oferta de venta con LMT</a></li>
																						<li><a class="dropdown-item alkin d-venta" href="oferta_venta.php?dt_SLS=1&dt_LS=1&Cardcode=<?php echo base64_encode($row['ID_CodigoCliente']); ?>&Contacto=<?php echo base64_encode($row['IdContactoLLamada']); ?>&Sucursal=<?php echo base64_encode($row['NombreSucursal']); ?>&Direccion=<?php echo base64_encode($row['DireccionLlamada']); ?>&TipoLlamada=<?php echo base64_encode($row['IdTipoLlamada']); ?>&ItemCode=<?php echo base64_encode($row['CDU_ListaMateriales']); ?>&SLS=<?php echo base64_encode($IdSolicitud); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('solicitud_llamada.php'); ?>&LMT=false">Oferta de venta sin LMT</a></li>
																				<?php } ?>
																			</ul>
																		</div>
																<?php } ?>
														<?php } ?>
													</div>
													<div class="col-lg-3">
														<div class="row">
															<!-- Espacio para botones -->
														</div>
													</div>
												</div>
												<br>
												<!-- Agregar documento, Fin -->
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example" >
														<thead>
														<tr>
															<th>Nombre cliente</th>
															<th>Tipo de documento</th>
															<th>Número de documento</th>
															<th>Fecha de documento</th>
															<th>Autorización</th>
															<th>Estado de documento</th>
															<th>Creado por</th>
															<th>Artículos/Costos</th>
															<th>Acciones</th>
														</tr>
														</thead>
														<tbody>
													<?php while ($row_DocRel = sqlsrv_fetch_array($SQL_DocRel)) { ?>
																		<tr class="gradeX">
																			<td><?php echo $row_DocRel['NombreCliente']; ?></td>
																			<td><?php echo $row_DocRel['DeObjeto']; ?></td>
																			<td><?php echo $row_DocRel['DocNum']; ?></td>
																			<td><?php echo $row_DocRel['DocDate']; ?></td>
																			<td><?php echo $row_DocRel['DeAuthPortal']; ?></td>
																			<td><span <?php if ($row_DocRel['Cod_Estado'] == 'O') {
																				echo "class='label label-info'";
																			} else {
																				echo "class='label label-danger'";
																			} ?>><?php echo $row_DocRel['NombreEstado']; ?></span></td>
																			<td><?php echo $row_DocRel['Usuario']; ?></td>
																			<td>
																				<a class="btn btn-primary btn-xs" id="btnPreCostos" name="btnPreCostos" onClick="MostrarCostos_Documentos('<?php echo $row_DocRel['DocNum']; ?>', '<?php echo $row_DocRel['IdObjeto']; ?>', '<?php echo $row_DocRel['DeObjeto']; ?>');"><i class="fa fa-money"></i> Previsualizar Precios</a>
																			</td>
																			<td>
																		<?php if ($row_DocRel['Link'] != "") { ?>
																			<a href="<?php echo $row_DocRel['Link']; ?>.php?id=<?php echo base64_encode($row_DocRel['DocEntry']); ?>&id_portal=<?php echo base64_encode($row_DocRel['IdPortal']); ?>&tl=1&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('solicitud_llamada.php'); ?>" class="alkin btn btn-success btn-xs"><i class="fa fa-folder-open-o"></i> Abrir</a>
																		<?php } ?>
																		<?php if ($row_DocRel['Descargar'] != "") { ?>
																			<a href="sapdownload.php?id=<?php echo base64_encode('15'); ?>&type=<?php echo base64_encode('2'); ?>&DocKey=<?php echo base64_encode($row_DocRel['DocEntry']); ?>&ObType=<?php echo base64_encode($row_DocRel['IdObjeto']); ?>&IdFrm=<?php echo base64_encode($row_DocRel['IdSeries']); ?>" target="_blank" class="btn btn-warning btn-xs"><i class="fa fa-download"></i> Descargar</a>
																		<?php } ?>
																			</td>
																		</tr>
													<?php } ?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
										<!-- /#tab-2 -->

										<!-- Campanas -->
										<div id="tab-4" class="tab-pane">
											<div class="panel-body">
												<div class="row">
													<?php if (isset($row['IdEstadoLlamada']) && ($row['IdEstadoLlamada'] != "-1")) { ?>
															<button type="button" onclick="AdicionarCampana()" class="alkin btn btn-primary btn-xs"><i class="fa fa-plus-circle"></i> Adicionar Campaña</button>
													<?php } ?>
												</div>
												<br>
												<!-- Table Campanas -->
												<div class="row">
													<div class="col-12 text-center">
														<div class="ibox-content">
															<?php if ($hasRowsCampanas) { ?>
																	<div class="table-responsive">
																		<table class="table table-striped table-bordered table-hover dataTables-example">
																			<thead>
																				<tr>
																					<th>ID Campaña</th> 

																					<th>Campaña</th> 
																					<th>VIN</th>

																					<th>Acciones</th>
																				</tr>
																			</thead>
																			<tbody>
																				<?php while ($row_Campana = sqlsrv_fetch_array($SQL_Campanas)) { ?>
																						<tr class="gradeX">
																							<td>
																								<a href="campanas_vehiculo.php?id=<?php echo $row_Campana['id_campana']; ?>&edit=1" class="btn btn-success btn-xs" target="_blank">
																									<i class="fa fa-folder-open-o"></i> <?php echo $row_Campana['id_campana']; ?>
																								</a>
																							</td>

																							<td><?php echo $row_Campana['campana']; ?></td>
																							<td><?php echo $row_Campana['VIN']; ?></td>

																							<td>
																								<?php if (isset($row['IdEstadoLlamada']) && ($row['IdEstadoLlamada'] != "-1")) { ?>
																										<button type="button"
																											id="btnDelete<?php echo $row_Campana['id_campana']; ?>"
																											class="btn btn-danger btn-xs"
																											onclick="EliminarCampana('<?php echo $row_Campana['id_campana']; ?>');"><i
																												class="fa fa-trash"></i>
																											Eliminar
																										</button>
																								<?php } ?>
																							</td>
																						</tr>
																				<?php } ?>
																			</tbody>
																		</table>
																	</div>
															<?php } else { ?>
																	<i class="fa fa-search" style="font-size: 18px; color: lightgray;"></i>
																	<span style="font-size: 13px; color: lightgray;">No hay registros de Campañas de Vehículo</span>
															<?php } ?>
														</div>
														<!-- /.ibox-content -->
													</div>
												</div>
												<!-- End Table Campanas -->
											</div>
											<!-- /.panel-body -->
										</div>
										<!-- End Campanas -->	   
									</div>
									<!-- /.tab-content -->
								</div>
								<!-- /.tabs-container -->
							</div>
							<!-- /.ibox-content -->
						</div>
						<!-- /.ibox -->
				   <?php } ?>
		   </div>
			</div>
		  </div>
		</div>
		<!-- InstanceEndEditable -->
		<?php include "includes/footer.php"; ?>

	</div>
</div>
<?php include "includes/pie.php"; ?>
<!-- InstanceBeginEditable name="EditRegion4" -->
<script>
$(document).ready(function () {
	// Esto se utiliza al momento de crear la OT desde la TE.
	<?php if (isset($_GET["IdTE"])) { ?>
		$('#NumeroSerie').trigger('change');
	<?php } ?>
	// SMM, 07/12/2023

	// SMM, 07/11/2023
	<?php if (PermitirFuncion(342)) { ?>
			$(".fecha, .hora").on("change", function() {
				let fechaCreacion = $("#FechaCreacion").val();
				let horaCreacion = $("#HoraCreacion").val();
				let fechaFinCreacion = $("#FechaFinCreacion").val();
				let horaFinCreacion = $("#HoraFinCreacion").val();

				// Igualar la fecha de solicitud a la de actividad.
				$("#FechaAgenda").val(fechaCreacion);
				$("#HoraAgenda").val(horaCreacion);
				$("#FechaFinAgenda").val(fechaFinCreacion);
				$("#HoraFinAgenda").val(horaFinCreacion);
			});
	<?php } ?>
	
	// SMM, 02/10/2023
	<?php if ($testMode) { ?>
			// Selecciona todos los elementos con el atributo 'required' dentro del formulario
			$('form [required]').removeAttr('required');
	<?php } ?>

	$("#CrearLlamada").validate({
		submitHandler: function (form) {
			if (Validar() && ValidarFechas()) {
				let vP = document.getElementById('P');
				let msg = (vP.value == '40') ? "¿Está seguro que desea reabrir la llamada?" : "¿Está seguro que desea guardar los datos?";
				let sw_ValDir =<?php echo $sw_valDir; ?>;

				if (sw_ValDir == 1) {
					let dirAnterior = '<?php echo isset($row['NombreSucursal']) ? $row['NombreSucursal'] : ""; ?>';
					let combo = document.getElementById("SucursalCliente");
					let dirActual = combo.options[combo.selectedIndex].text;

					Swal.fire({
						title: '¡Advertencia!',
						html: 'La sucursal <strong>' + dirAnterior + '</strong> ha cambiado de nombre por <strong>' + dirActual + '</strong>. Se actualizará en la llamada de servicio.',
						icon: 'warning',
						showCancelButton: true,
						confirmButtonText: "Entendido",
						cancelButtonText: "Cancelar"
					}).then((des) => {
						if (des.isConfirmed) {
							Swal.fire({
								title: msg,
								icon: "info",
								showCancelButton: true,
								confirmButtonText: "Si, confirmo",
								cancelButtonText: "No"
							}).then((result) => {
								if (result.isConfirmed) {
									$('.ibox-content').toggleClass('sk-loading', true);
									form.submit();
								}
							});
						}
					});
				} else {
					Swal.fire({
						title: msg,
						icon: "info",
						showCancelButton: true,
						confirmButtonText: "Si, confirmo",
						cancelButtonText: "No"
					}).then((result) => {
						if (result.isConfirmed) {
							$('.ibox-content').toggleClass('sk-loading', true);
							form.submit();
						}
					});
				}

			} else {
				$('.ibox-content').toggleClass('sk-loading', false);
			}
		}
	});

	maxLength('ComentarioLlamada');

	maxLength('CDU_Servicios'); // SMM, 02/03/2022
	maxLength('CDU_Areas'); // SMM, 02/03/2022

	<?php if (($edit == 0) || (($edit == 1) && ($CrearSolicitud && ($row['IdEstadoLlamada'] != '-1')))) { ?>
		$(".fechaAgenda").datepicker({
			todayBtn: "linked",
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true,
			format: 'yyyy-mm-dd',
			todayHighlight: true
		});
		$(".horaAgenda").clockpicker({
			donetext: 'Done'
		});

		// SMM, 15/11/2023
		<?php if (($edit == 0) || !PermitirFuncion(344)) { ?>
			$(".fecha").datepicker({
				todayBtn: "linked",
				keyboardNavigation: false,
				forceParse: false,
				calendarWeeks: true,
				autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true
			});
			$(".hora").clockpicker({
				donetext: 'Done'
			});
		<?php } ?>
	<?php } ?>
	
	<?php if (($edit == 1) && ($CrearSolicitud && ($row['IdEstadoLlamada'] != '-1'))) { ?>
		$('#FechaCierre').datepicker({
			todayBtn: "linked",
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true,
			format: 'yyyy-mm-dd',
			todayHighlight: true,
			startDate: '<?php echo $row['FechaCreacionLLamada']; ?>',
			endDate: '<?php echo date('Y- m - d'); ?>'
		});
	<?php } ?>
	
	$(".select2").select2();

var options = {
	url: function (phrase) {
		return "ajx_buscar_datos_json.php?type=7&id=" + phrase;
	},

	getValue: "NombreBuscarCliente",
	requestDelay: 400,
	list: {
		match: {
			enabled: true
		},
		onClickEvent: function () {
			var value = $("#NombreClienteLlamada").getSelectedItemData().CodigoCliente;
			$("#ClienteLlamada").val(value).trigger("change");
		}
	}
};

var options2 = {
	url: function (phrase) {
		return "ajx_buscar_datos_json.php?type=8&id=" + phrase;
	},

	getValue: "Ciudad",
	requestDelay: 400,
	template: {
		type: "description",
		fields: {
			description: "Codigo"
		}
	},
	list: {
		match: {
			enabled: true
		}
	}
};

// Stiven Muñoz Murillo, 24/01/2022
var options3 = {
	url: function (phrase) {
		return "ajx_buscar_datos_json.php?type=46&id=" + phrase;
	},
	getValue: "DeArticuloLlamada",
	requestDelay: 400,
	list: {
		match: {
			enabled: true
		},
		onClickEvent: function () {
			var value = $("#DeArticuloLlamada").getSelectedItemData().IdArticuloLlamada;
			$("#IdArticuloLlamada").val(value).trigger("change");
		}
	}
};

	<?php if ($edit == 0) { ?>
		$("#NombreClienteLlamada").easyAutocomplete(options);
	<?php } ?>

	$("#CiudadLlamada").easyAutocomplete(options2);

	// Stiven Muñoz Murillo, 24/01/2022
	$("#DeArticuloLlamada").easyAutocomplete(options3);

	<?php if ($dt_LS == 1) { ?>
		$('#ClienteLlamada').trigger('change');

		// Stiven Muñoz Murillo, 24/01/2022
		$('#IdArticuloLlamada').trigger('change');
	 <?php } ?>

	<?php if ($edit == 1) { ?>
		$('#Series option:not(:selected)').attr('disabled', true);
	<?php } ?>

	$('.dataTables-example').DataTable({
		pageLength: 10,
		dom: '<"html5buttons"B>lTfgitp',
		language: {
			"decimal": "",
			"emptyTable": "No se encontraron resultados.",
			"info": "Mostrando _START_ - _END_ de _TOTAL_ registros",
			"infoEmpty": "Mostrando 0 - 0 de 0 registros",
			"infoFiltered": "(filtrando de _MAX_ registros)",
			"infoPostFix": "",
			"thousands": ",",
			"lengthMenu": "Mostrar _MENU_ registros",
			"loadingRecords": "Cargando...",
			"processing": "Procesando...",
			"search": "Filtrar:",
			"zeroRecords": "Ningún registro encontrado",
			"paginate": {
				"first": "Primero",
				"last": "Último",
				"next": "Siguiente",
				"previous": "Anterior"
			},
			"aria": {
				"sortAscending": ": Activar para ordenar la columna ascendente",
				"sortDescending": ": Activar para ordenar la columna descendente"
			}
		},
		buttons: []	
	});

	// SMM, 18/10/2023
	$('#SubTipoProblema[readonly] option:not(:selected)').attr('disabled', true);
});

// Validación de la llamada de servicio, se ejecuta al momento de crear o actualizar.
function Validar() {
	let res = true;

	let vP = document.getElementById('P');
	let EstLlamada = document.getElementById('EstadoLlamada');
	let txtResol = document.getElementById('ResolucionLlamada');
	let EstadoServicio = document.getElementById("CDU_EstadoServicio");
	let CanceladoPor = document.getElementById("CDU_CanceladoPor");

	// ($_POST['P'] == 40), Reabrir llamada de servicio
	if (vP.value != 40) {
		if (EstLlamada.value == '-1') {
			if (EstadoServicio.value == '0') {
				res = false;
				Swal.fire({
					title: '¡Advertencia!',
					text: 'Cuando está cerrando la llamada, el Estado de servicio debe ser diferente a NO EJECUTADO',
					icon: 'warning'
				});
			}
		}

		if (EstadoServicio.value == '2') {
			if (CanceladoPor.value == '' || CanceladoPor.value == '1.N/A') {
				res = false;
				Swal.fire({
					title: '¡Advertencia!',
					text: 'Debe seleccionar un valor en el campo Cancelado Por.',
					icon: 'warning'
				});
			}
		}
	}
	return res;
}

// SMM, 26/07/2022
function MostrarCostos_Documentos(docnum, id_objeto, de_objeto) {
	$('.ibox-content').toggleClass('sk-loading', true);
	$.ajax({
		type: "POST",
		async: false,
		url: "md_articulos_documentos.php",
		data: {
			pre: 4,
			DocNum: docnum,
			IdObjeto: id_objeto
		},
		success: function (response) {
			$('.ibox-content').toggleClass('sk-loading', false);
			$('#ContenidoModal').html(response);
			$('#TituloModal').html(`Precios IVA Incluido (${de_objeto}: ${docnum})`);
			$('#myModal').modal("show");
		}
	});
}
</script>

<script>
$(function () {
	// Permisos de edición y creación. SMM, 14/11/2023
	<?php if (($edit == 1) && ((!$ActualizarSolicitud) || ($row['IdEstadoLlamada'] == '-1'))) { ?>
		$("input").prop("disabled", true);
		$("select").prop("disabled", true);
		$("textarea").prop("disabled", true);
	<?php } elseif($edit == 0 && !$CrearSolicitud) { ?>
		$("input").prop("disabled", true);
		$("select").prop("disabled", true);
		$("textarea").prop("disabled", true);
	<?php } ?>

	// SMM, 15/11/2023
	<?php if (($edit == 1) && PermitirFuncion(344)) { ?>
		$("select").prop("disabled", true);
		$("textarea").prop("disabled", true);

		// ID es necesario, por eso no puede ser disabled.
		$("input").prop("readonly", true);

		// Habilitar fecha solicitud. Las fechas también son de tipo input.
		$(".fechaAgenda, .horaAgenda").prop("readonly", false);

		// Habilitar técnico adicional.
		$("#CDU_IdTecnicoAdicional").prop("disabled", false);
	<?php } ?>
	// Hasta aquí. SMM, 14/11/2023

	var url = "";
	var params = [];

	$(".alkin").on("click", function (event) {
		$('.ibox-content').toggleClass('sk-loading'); // Cargando...
	});

	$(".d-venta").on("click", function (event) {
		<?php if (PermitirFuncion(419)) { ?>
				event.preventDefault(); // Evitar redirección del ancla
				console.log(event);

				Swal.fire({
					title: "¿Desea cambiar de socio de negocio?",
					icon: "question",
					showCancelButton: true,
					confirmButtonText: "Si, confirmo",
					cancelButtonText: "No"
				}).then((result) => {
					if (result.isConfirmed) {
						let qs = "";
						[url, qs] = $(this).attr('href').split('?');
						params = Object.fromEntries(new URLSearchParams(qs));

						$('#modalSN').modal("show");
					} else {
						location.href = $(this).attr('href');
					}
				});
		<?php } else { ?>
				console.log("Permiso 419, no esta activo");
		<?php } ?>
	});

	let options = {
		url: function (phrase) {
			return "ajx_buscar_datos_json.php?type=7&id=" + phrase;
		},
		adjustWidth: false,
		getValue: "NombreBuscarCliente",
		requestDelay: 400,
		list: {
			match: {
				enabled: true
			},
			onClickEvent: function () {
				var value = $("#NombreClienteSN").getSelectedItemData().CodigoCliente;
				$("#ClienteSN").val(value).trigger("change");
			}
		}
	};

	$("#NombreClienteSN").easyAutocomplete(options);

	$(".CancelarSN").on("click", function () {
		$('.ibox-content').toggleClass('sk-loading', false);
	});

	$("#formCambiarSN").on("submit", function (event) {
		event.preventDefault(); // Evitar redirección del formulario

		let ClienteSN = document.getElementById('ClienteSN').value;
		let ContactoSN = document.getElementById('ContactoSN').value;
		let SucursalSN = document.getElementById('SucursalSN').value;
		let DireccionSN = document.getElementById('DireccionSN').value;

		params.Cardcode = Base64.encode(ClienteSN);
		params.Contacto = Base64.encode(ContactoSN);
		params.Sucursal = Base64.encode(SucursalSN);
		params.Direccion = Base64.encode(DireccionSN);

		let qs = new URLSearchParams(params).toString();
		location.href = `${url}?${qs}`;
	});

	$("#ClienteSN").change(function () {
		let ClienteSN = document.getElementById('ClienteSN').value;

		$.ajax({
			type: "POST",
			url: "ajx_cbo_select.php?type=2&id=" + ClienteSN,
			success: function (response) {
				$('#ContactoSN').html(response).fadeIn();
				$('#ContactoSN').trigger('change');
			},
			error: function (error) {
				console.error("ContactoSN", error.responseText);
			}
		});
		$.ajax({
			type: "POST",
			url: "ajx_cbo_select.php?type=3&id=" + ClienteSN,
			success: function (response) {
				console.log(response);

				$('#SucursalSN').html(response).fadeIn();
				$('#SucursalSN').trigger('change');
			},
			error: function (error) {
				console.error("SucursalSN", error.responseText);
			}
		});
	});

	$("#SucursalSN").change(function () {
		let ClienteSN = document.getElementById('ClienteSN').value;
		let SucursalSN = document.getElementById('SucursalSN').value;

		if (SucursalSN != -1 && SucursalSN != '') {
			$.ajax({
				url: "ajx_buscar_datos_json.php",
				data: {
					type: 1,
					CardCode: ClienteSN,
					Sucursal: SucursalSN
				},
				dataType: 'json',
				success: function (data) {
					document.getElementById('DireccionSN').value = data.Direccion;
				},
				error: function (error) {
					console.error("SucursalSN", error.responseText);
				}
			});
		}
	});

	$("#formCambiarFactSN").on("submit", function (event) {
		event.preventDefault(); // Evitar redirección del formulario

		let Cliente = document.getElementById('ClienteFactSN').value;
		let Contacto = document.getElementById('ContactoFactSN').value;
		let Sucursal = document.getElementById('SucursalFactSN').value;
		let Direccion = document.getElementById('DireccionFactSN').value;

		CopiarFacturaSN(Cliente, Contacto, Sucursal, Direccion);
	});

	$("#ClienteFactSN").change(function () {
		let ClienteFactSN = document.getElementById('ClienteFactSN').value;

		$.ajax({
			type: "POST",
			url: "ajx_cbo_select.php?type=2&id=" + ClienteFactSN,
			success: function (response) {
				$('#ContactoFactSN').html(response).fadeIn();
				$('#ContactoFactSN').trigger('change');
			},
			error: function (error) {
				console.error("ContactoFactSN", error.responseText);
			}
		});
		$.ajax({
			type: "POST",
			url: "ajx_cbo_select.php?type=3&id=" + ClienteFactSN,
			success: function (response) {
				console.log(response);

				$('#SucursalFactSN').html(response).fadeIn();
				$('#SucursalFactSN').trigger('change');
			},
			error: function (error) {
				console.error("SucursalFactSN", error.responseText);
			}
		});
	});

	$("#SucursalFactSN").change(function () {
		let ClienteFactSN = document.getElementById('ClienteFactSN').value;
		let SucursalFactSN = document.getElementById('SucursalFactSN').value;

		if (SucursalFactSN != -1 && SucursalFactSN != '') {
			$.ajax({
				url: "ajx_buscar_datos_json.php",
				data: {
					type: 1,
					CardCode: ClienteFactSN,
					Sucursal: SucursalFactSN
				},
				dataType: 'json',
				success: function (data) {
					document.getElementById('DireccionFactSN').value = data.Direccion;
				},
				error: function (error) {
					console.error("SucursalFactSN", error.responseText);
				}
			});
		}
	});

	// Adicionar campanas.
	$("#NumeroSerie").on("change", function () {
		$('.ibox-content').toggleClass('sk-loading', true);

		if ($(this).val() != "") {
			$('#AddCampana').prop('disabled', false);
		} else {
			$('#AddCampana').prop('disabled', true);
		}

		// SMM, 22/11/2023
		let id_tarjeta_equipo = $(this).val();

		$.ajax({
			type: "POST",
			url: `ajx_cbo_select.php?type=49&id=${id_tarjeta_equipo}`,
			success: function (response) {
				console.log("ajx_buscar_datos_json(49)", response);

				$("#Campanas").html(response).fadeIn();
				$("#Campanas").trigger('change');

				// SMM, 15/09/2023
				let campanas = "<?php echo $row["CampanasAsociadas"] ?? ""; ?>";
				let ids = campanas.split(";"); // Dividimos la cadena en un arreglo
				console.log("ids campanas", ids);

				// Iterar sobre cada ID
				ids.forEach(function (id) {

					// Seleccionar opciones específicas
					$(`#Campanas option[value='${id}']`).prop("selected", true);
				});
				// .forEach()

				// Cargar de nuevo con los ids seleccionados.
				$("#Campanas").trigger('change');

				$('.ibox-content').toggleClass('sk-loading', false);
			},
			error: function(error) {
				console.log("error (4128), ", error);

				$('.ibox-content').toggleClass('sk-loading', false);
			}
		});
	});

	$("#AddCampana").on("click", function () {
		AdicionarCampanaAsincrono();
	});
	// SMM, 08/09/2023

		// SMM, 13/09/2023
		<?php if (isset($_GET["active"])) { ?>
			VerTAB(<?php echo $_GET["active"]; ?>);
	<?php } ?>
	
	// SMM, 15/09/2023
	<?php if ($sw_error == 1) { ?>
		$('#NumeroSerie').trigger('change');		
	<?php } ?>

	// SMM, 18/10/2023
	$('#SubTipoProblema[readonly] option:not(:selected)').attr('disabled', true);

	// SMM, 31/10/2023
	$(".TecnicoSugerido").change(function () {
		let IdSerieLlamada = $("#Series").val() || "";
		let IdOrigen = $("#OrigenLlamada").val() || "";
		let IdTipoProblema = $("#TipoProblema").val() || "";
		let IdMarca = $("#CDU_Marca").val() || "";
		
		if ((IdSerieLlamada != "") && (IdOrigen != "") && (IdTipoProblema != "") && (IdMarca != "")) {
			$.ajax({
				url: "ajx_buscar_datos_json.php",
				data: {
					type: 50,
					IdSerieLlamada: IdSerieLlamada,
					IdOrigen: IdOrigen,
					IdTipoProblema: IdTipoProblema,
					IdMarca: IdMarca
				},
				dataType: 'json',
				success: function (data) {
					$("#CDU_IdTecnicoAdicional").val(data.IdTecnico);
					$("#CDU_IdTecnicoAdicional").change();

					Swal.fire({
						title: 'Técnico Adicional Sugerido',
						text: data.DeTecnico,
						icon: 'success',
					});
				},
				error: function (error) {
					console.log("Error TecnicoSugerido:", error.responseText);
				}
			});
		}
	});
	// Hasta aquí, 31/10/2023

	// SMM, 09/11/2023
	<?php if (PermitirFuncion(345)) { ?>
			let fechaInicioSolicitud = new Date(`${$("#FechaCreacion").val()}T${$("#HoraCreacion").val()}`);
			let fechaActual = new Date();

			if (fechaInicioSolicitud < fechaActual) {
				console.log("La fecha de inicio de la solicitud es menor que la fecha actual.");
				$("#copiarSolicitud").prop("disabled", true);
			}
	<?php } ?>

	$("#copiarSolicitud").on("click", function() {
		location.href = "llamada_servicio.php?dt_SLS=1&SLS=<?php echo base64_encode($IdSolicitud); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('solicitud_llamada.php'); ?>"
	});
});

// SMM, 02/11/2023
function ValidarFechas() {
	let fechaCreacion = new Date(`${$("#FechaCreacion").val()}T${$("#HoraCreacion").val()}`);
	let fechaFinCreacion = new Date(`${$("#FechaFinCreacion").val()}T${$("#HoraFinCreacion").val()}`);
	let fechaAgenda = new Date(`${$("#FechaAgenda").val()}T${$("#HoraAgenda").val()}`);
	let fechaFinAgenda = new Date(`${$("#FechaFinAgenda").val()}T${$("#HoraFinAgenda").val()}`);

	if ((fechaAgenda >= fechaFinAgenda) || (fechaCreacion >= fechaFinCreacion)) {
		Swal.fire({
			title: '¡Advertencia!',
			text: 'Tiempo no válido. Ingrese una duración positiva.',
			icon: 'warning',
		});
		return false;
	}
	// SMM, 03/11/2023
	else if(fechaCreacion > fechaAgenda) {
		Swal.fire({
			title: '¡Advertencia!',
			text: 'La fecha de inicio de la solicitud debe ser menor o igual a la fecha de inicio de la actividad.',
			icon: 'warning',
		});
		return false;
	}
	return true;
}

function AdicionarCampana() {
	$('.ibox-content').toggleClass('sk-loading', true);
	
	// SMM, 22/11/2023
	let IdTarjetaEquipo = $("#NumeroSerie").val() || "";

	$.ajax({
		type: "POST",
		data: {
			id_tarjeta_equipo: IdTarjetaEquipo,
			id_llamada_servicio: $("#Ticket").val(),
			docentry_llamada_servicio: $("#CallID").val(),
			solicitud: "Solicitud"
		},
		url: "md_adicionar_campanas.php",
		success: function (response) {
			$('.ibox-content').toggleClass('sk-loading', false);

			$('#ContenidoModal2').html(response);
			$('#myModal2').modal("show");
		},
		error: function (error) {
			console.log("error (3490), ", error);
		}
	});
}

function AdicionarCampanaAsincrono() {
	$('.ibox-content').toggleClass('sk-loading', true);
	
	// SMM, 22/11/2023
	let IdTarjetaEquipo = $("#NumeroSerie").val() || "";

	$.ajax({
		type: "POST",
		data: {
			id_tarjeta_equipo: IdTarjetaEquipo,
			asincrono: 1, // Asincrono - En la creación.
			solicitud: "Solicitud"
		},
		url: "md_adicionar_campanas.php",
		success: function (response) {
			$('.ibox-content').toggleClass('sk-loading', false);

			$('#ContenidoModal2').html(response);
			$('#myModal2').modal("show");
		},
		error: function (error) {
			console.log("error (3515), ", error);
		}
	});
}

function ConsultarDatosClienteSN() {
	let ClienteSN = document.getElementById('ClienteSN');

	if (ClienteSN.value != "") {
		self.name = 'opener';
		remote = open('socios_negocios.php?id=' + Base64.encode(ClienteSN.value) + '&ext=1&tl=1', 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
		remote.focus();
	}
}

function ConsultarDatosFactSN() {
	let ClienteFactSN = document.getElementById('ClienteFactSN');

	if (ClienteFactSN.value != "") {
		self.name = 'opener';
		remote = open('socios_negocios.php?id=' + Base64.encode(ClienteFactSN.value) + '&ext=1&tl=1', 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
		remote.focus();
	}
}

// SMM, 14/09/2023
function VerTAB(id) {
	// Hacer scroll hasta el final de la página
	window.scrollTo(0, document.body.scrollHeight);

	// Eliminar la clase "active" de todos los títulos dentro de nav-tabs
	var tituloTabs = document.querySelectorAll('.nav-tabs li');
	tituloTabs.forEach(function (titulo) {
		titulo.classList.remove('active');
	});

	// Eliminar la clase "active" de todas las pestañas dentro de tab-content
	var tabs = document.querySelectorAll('.tab-content .tab-pane');
	tabs.forEach(function (tab) {
		tab.classList.remove('active');
	});

	// Agregar la clase "active" a la pestaña tab-"id" y su título
	let tab = document.querySelector(`.nav-tabs #nav-${id}`);
	let tabContenido = document.querySelector(`.tab-content #tab-${id}`);
	tab.classList.add('active');
	tabContenido.classList.add('active');
}

// SMM, 14/09/2023
function EliminarCampana(id) {
	Swal.fire({
		title: "¿Está seguro que desea eliminar esta Campaña?",
		icon: "question",
		showCancelButton: true,
		confirmButtonText: "Si, confirmo",
		cancelButtonText: "No"
	}).then((result) => {
		if (result.isConfirmed) {
			$.ajax({
				type: "POST",
				url: "md_adicionar_campanas.php",
				data: {
					type: 3,
					id_llamada_servicio: $("#Ticket").val(), // "DocNum"
					docentry_llamada_servicio: $("#CallID").val(), // "DocNum"
					id_campana: id,  // Usar el ID actual en esta iteración
					solicitud: "Solicitud"
				},
				success: function (response) {
					Swal.fire({
						icon: (response == "OK") ? "success" : "warning'",
						title: (response == "OK") ? "¡Listo!" : "¡Error!",
						text: (response == "OK") ? "Se elimino la Campaña correctamente." : response
					}).then((result) => {
						if (result.isConfirmed) {
							// Obtén la URL actual
							let currentUrl = new URL(window.location.href);

							// Obtén los parámetros del query string
							let searchParams = currentUrl.searchParams;

							// Actualiza el valor del parámetro 'active' o agrega si no existe
							searchParams.set('active', 4);

							// Crea una nueva URL con los parámetros actualizados
							let newUrl = currentUrl.origin + currentUrl.pathname + '?' + searchParams.toString();

							// Recarga la página con la nueva URL
							window.location.href = newUrl;
						}
					});
				},
				error: function (error) {
					console.error("640->", error.responseText);
				}
			});
			// $.ajax
		}
	});
}

// SMM, 14/09/2023
function AdicionarAnotacion() {
	$('.ibox-content').toggleClass('sk-loading', true);
	
	$.ajax({
		type: "POST",
		url: "md_adicionar_anotaciones.php",
		success: function (response) {
			$('.ibox-content').toggleClass('sk-loading', false);

			$('#ContenidoModal2').html(response);
			$('#myModal2').modal("show");
		}
	});
}
</script>

<script>
Dropzone.options.dropzoneForm = {
	paramName: "File", // The name that will be used to transfer the file
	maxFilesize: "<?php echo ObtenerVariable("MaxSizeFile"); ?>", // MB
	maxFiles: "<?php echo ObtenerVariable("CantidadArchivos"); ?>",
	uploadMultiple: true,
	addRemoveLinks: true,
	dictRemoveFile: "Quitar",
	acceptedFiles: "<?php echo ObtenerVariable("TiposArchivos"); ?>",
	dictDefaultMessage: "<strong>Haga clic aqui para cargar anexos</strong><br>Tambien puede arrastrarlos hasta aqui<br><h4><small>(máximo <?php echo ObtenerVariable("CantidadArchivos"); ?> archivos a la vez)<small></h4>",
	dictFallbackMessage: "Tu navegador no soporta cargue de archivos mediante arrastrar y soltar",
	removedfile: function (file) {
		$.get("includes/procedimientos.php", {
			type: "3",
			nombre: file.name
		}).done(function (data) {
			var _ref;
			return (_ref = file.previewElement) !== null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
		});
	}
};
</script>

<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion); ?>
