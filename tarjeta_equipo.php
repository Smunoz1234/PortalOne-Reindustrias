<?php require_once "includes/conexion.php";
PermitirAcceso(1601);
$IdTarjetaEquipo = "";
$IdTE = true; // SMM, 19/04/2022
$msg_error = ""; // Mensaje del error.
$dt_TE = 0; //sw para saber si vienen datos de la llamada de servicio. 0 no vienen. 1 si vienen.
$sw_dirS = 0; //Direcciones de destino
$sw_dirB = 0; //Direcciones de factura

// Dimensiones, SMM 21/02/2024
$DimSeries = intval(ObtenerVariable("DimensionSeries"));
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimActive='Y'");

// Pruebas, SMM 21/02/2024
// $SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', 'DimCode IN (1,2)');

$array_Dimensiones = [];
while ($row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones)) {
	array_push($array_Dimensiones, $row_Dimension);
}

// $encode_Dimensiones = json_encode($array_Dimensiones);
// $cadena_Dimensiones = "JSON.parse('$encode_Dimensiones'.replace(/\\n|\\r/g, ''))";
// echo "<script> console.log('cadena_Dimensiones'); </script>";
// echo "<script> console.log($cadena_Dimensiones); </script>";
// Hasta aquí, SMM 21/02/2024

// Jerarquias, SMM 22/02/2024
$SQL_Jerarquias = Seleccionar("tbl_TarjetaEquipo_DimensionJerarquias", "*", "estado_dimension_jerarquia = 'Y'");

$array_Jerarquias = [];
while ($row_Jerarquia = sqlsrv_fetch_array($SQL_Jerarquias)) {
	array_push($array_Jerarquias, $row_Jerarquia);
}
// Hasta aquí, SMM 22/02/2024

if (isset($_GET['id']) && ($_GET['id'] != "")) {
	$IdTarjetaEquipo = base64_decode($_GET['id']);
}

if (isset($_GET['tl']) && ($_GET['tl'] != "")) { //0 Creando una actividad. 1 Editando actividad.
	$edit = $_GET['tl'];
} elseif (isset($_POST['tl']) && ($_POST['tl'] != "")) {
	$edit = $_POST['tl'];
} else {
	$edit = 0;
}

if (isset($_GET['ext']) && ($_GET['ext'] == 1)) {
	$sw_ext = 1; //Se está abriendo como pop-up
} elseif (isset($_POST['ext']) && ($_POST['ext'] == 1)) {
	$sw_ext = 1; //Se está abriendo como pop-up
} else {
	$sw_ext = 0;
}

if (isset($_POST['swError']) && ($_POST['swError'] != "")) { //Para saber si ha ocurrido un error.
	$sw_error = $_POST['swError'];
} elseif (isset($_GET['swError']) && ($_GET['swError'] != "")) {
	$sw_error = $_GET['swError'];
} else {
	$sw_error = 0;
}

$Title = ($edit == 0) ? "Crear tarjeta de equipo" : "Editar tarjeta de equipo";

if (isset($_POST['P']) && ($_POST['P'] != "")) { // Guardar tarjeta de equipo
	//*** Carpeta temporal ***
	$i = 0; //Archivos
	$RutaAttachSAP = ObtenerDirAttach();
	$dir = CrearObtenerDirTemp();
	$dir_new = CrearObtenerDirAnx("tarjetas_equipos");
	$route = opendir($dir);
	$DocFiles = array();
	while ($archivo = readdir($route)) { //obtenemos un archivo y luego otro sucesivamente
		if (($archivo == ".") || ($archivo == "..")) {
			continue;
		}

		if (!is_dir($archivo)) { //verificamos si es o no un directorio
			$DocFiles[$i] = $archivo;
			$i++;
		}
	}
	closedir($route);
	$CantFiles = count($DocFiles);

	try {
		$Metodo = ($edit == 1) ? 2 : 1;

		//Armar array de parámetros que se enviaran al procedimiento almacenado
		$ParametrosTarjetaEquipo = array(
			"NULL",
			// ID_Equipo
			"NULL", // IdEvento
			"'" . base64_decode($_POST['ID_TarjetaEquipo']) . "'",
			"'" . $_POST['TipoEquipo'] . "'",
			"'" . $_POST['SerialFabricante'] . "'",
			"'" . $_POST['SerialInterno'] . "'",
			"'" . $_POST['ItemCode'] . "'",
			"'" . $_POST['ItemName'] . "'",
			"'" . $_POST['ClienteEquipo'] . "'", // CardCode
			"'" . $_POST['NombreClienteEquipo'] . "'", // CardName
			"'" . $_POST['ContactoCliente'] . "'",
			"'" . $_POST['IdTecnico'] . "'",
			"'" . $_POST['IdTerritorio'] . "'",
			"'" . $_POST['CodEstado'] . "'",
			"'" . $_POST['SerieAnterior'] . "'",
			"'" . $_POST['SerieNueva'] . "'",
			"'" . $_POST['CardCodeCompras'] . "'",
			"'" . $_POST['CardNameCompras'] . "'",
			isset($_POST['DocEntryEntrega']) ? $_POST['DocEntryEntrega'] : "''",
			(isset($_POST['DocNumEntrega']) && $_POST['DocNumEntrega'] != '') ? $_POST['DocNumEntrega'] : "NULL",
			isset($_POST['DocEntryFactura']) ? $_POST['DocEntryFactura'] : "''",
			(isset($_POST['DocNumFactura']) && $_POST['DocNumFactura'] != '') ? $_POST['DocNumFactura'] : "NULL",
			"'" . $_POST['Calle'] . "'",
			isset($_POST['CalleNum']) ? $_POST['CalleNum'] : "''",
			isset($_POST['Edificio']) ? $_POST['Edificio'] : "''",
			"'" . $_POST['CodigoPostal'] . "'",
			isset($_POST['Barrio']) ? $_POST['Barrio'] : "''",
			"'" . $_POST['Ciudad'] . "'",
			"'" . $_POST['EstadoPais'] . "'",
			"'" . $_POST['Distrito'] . "'",
			isset($_POST['Pais']) ? $_POST['Pais'] : "''",
			isset($_POST['IdAnexo']) ? ("'" . $_POST['IdAnexo'] . "'") : ("'176'"),
			// IdAnexos
			$Metodo,
			"'" . $_SESSION['CodUser'] . "'",
			"'" . $_SESSION['CodUser'] . "'",
			"1",
			// Nuevos campos
			"'" . $_POST['CDU_IdMarca'] . "'",
			"'" . $_POST['CDU_IdLinea'] . "'",
			"'" . $_POST['CDU_Ano'] . "'",
			"'" . ($_POST['CDU_Concesionario'] ?? "") . "'",
			"'" . ($_POST['CDU_No_Motor'] ?? "") . "'",
			"'" . ($_POST['CDU_Color'] ?? "") . "'",
			"'" . ($_POST['CDU_Cilindraje'] ?? "") . "'",
			strtotime($_POST['CDU_FechaUlt_CambAceite']) ? ("'" . FormatoFecha($_POST['CDU_FechaUlt_CambAceite']) . "'") : "NULL",
			strtotime($_POST['CDU_FechaProx_CambAceite']) ? ("'" . FormatoFecha($_POST['CDU_FechaProx_CambAceite']) . "'") : "NULL",
			strtotime($_POST['CDU_FechaUlt_Mant']) ? ("'" . FormatoFecha($_POST['CDU_FechaUlt_Mant']) . "'") : "NULL",
			strtotime($_POST['CDU_FechaProx_Mant']) ? ("'" . FormatoFecha($_POST['CDU_FechaProx_Mant']) . "'") : "NULL",
			strtotime($_POST['CDU_FechaMatricula']) ? ("'" . FormatoFecha($_POST['CDU_FechaMatricula']) . "'") : "NULL",
			strtotime($_POST['CDU_FechaUlt_CambLlantas']) ? ("'" . FormatoFecha($_POST['CDU_FechaUlt_CambLlantas']) . "'") : "NULL",
			strtotime($_POST['CDU_FechaProx_CambLlantas']) ? ("'" . FormatoFecha($_POST['CDU_FechaProx_CambLlantas']) . "'") : "NULL",
			strtotime($_POST['CDU_Fecha_SOAT']) ? ("'" . FormatoFecha($_POST['CDU_Fecha_SOAT']) . "'") : "NULL",
			strtotime($_POST['CDU_Fecha_Tecno']) ? ("'" . FormatoFecha($_POST['CDU_Fecha_Tecno']) . "'") : "NULL",
			strtotime($_POST['CDU_FechaUlt_AlinBalan']) ? ("'" . FormatoFecha($_POST['CDU_FechaUlt_AlinBalan']) . "'") : "NULL",
			strtotime($_POST['CDU_FechaProx_AlinBalan']) ? ("'" . FormatoFecha($_POST['CDU_FechaProx_AlinBalan']) . "'") : "NULL",
			"'" . ($_POST['CDU_TipoServicio'] ?? "") . "'",
			"'" . ($_POST['TelefonoCliente'] ?? "") . "'",
			"'" . ($_POST['CDU_Novedad'] ?? "") . "'",
			"'" . ($_POST['CDU_IdTipoVehiculo'] ?? "") . "'",
			"'" . ($_POST['CDU_IdTipoRin'] ?? "") . "'",
			// SMM, 23/02/2024
			"'" . ($_POST['IdTipoEquipoPropiedad'] ?? "") . "'",
			"'" . ($_POST['IdUnidadMedidaEquipo'] ?? "") . "'",
			strtotime($_POST['FechaOperacionEquipo']) ? ("'" . FormatoFecha($_POST['FechaOperacionEquipo']) . "'") : "NULL",
			"'" . ($_POST['IdFabricante'] ?? "") . "'",
			"'" . ($_POST['IdJerarquia1'] ?? "") . "'",
			"'" . ($_POST['IdJerarquia2'] ?? "") . "'",
			"'" . ($_POST['IdUbicacion'] ?? "") . "'",
			"'" . ($_POST['IdDimension1'] ?? "") . "'",
			"'" . ($_POST['IdDimension2'] ?? "") . "'",
			"'" . ($_POST['IdDimension3'] ?? "") . "'",
			"'" . ($_POST['IdDimension4'] ?? "") . "'",
			"'" . ($_POST['IdDimension5'] ?? "") . "'",
			"'" . ($_POST['IdProyecto'] ?? "") . "'",
			"'" . ($_POST['latGPS'] ?? "") . "'",
			"'" . ($_POST['lngGPS'] ?? "") . "'",
		);

		// Insertar a la tabla de PortalOne
		$SQL_CabeceraTarjetaEquipo = EjecutarSP('sp_tbl_TarjetaEquipo', $ParametrosTarjetaEquipo, $_POST['P']);
		if ($SQL_CabeceraTarjetaEquipo) {
			$row_CabeceraTarjetaEquipo = sqlsrv_fetch_array($SQL_CabeceraTarjetaEquipo);

			$IdTarjetaEquipo = $row_CabeceraTarjetaEquipo[0]; // Nuevo ID de TE
			// echo "<script> console.log($IdTarjetaEquipo); </script>";

			try {
				//Mover los anexos a la carpeta de archivos de SAP
				$j = 0;
				$Anexos = array(); // Anexos (WebService)
				while ($j < $CantFiles) {
					$Archivo = FormatoNombreAnexo($DocFiles[$j]);
					$NuevoNombre = $Archivo[0];
					$OnlyName = $Archivo[1];
					$Ext = $Archivo[2];

					if (file_exists($dir_new)) {
						copy($dir . $DocFiles[$j], $dir_new . $NuevoNombre);
						copy($dir_new . $NuevoNombre, $RutaAttachSAP[0] . $NuevoNombre);

						//Registrar archivo en la BD
						$ParamInsAnex = array(
							"'176'",
							"'" . $IdTarjetaEquipo . "'",
							"'" . $OnlyName . "'",
							"'" . $Ext . "'",
							"1",
							"'" . $_SESSION['CodUser'] . "'",
							"1",
						);

						$SQL_InsAnex = EjecutarSP('sp_tbl_DocumentosSAP_Anexos', $ParamInsAnex, $_POST['P']);
						if (!$SQL_InsAnex) {
							$sw_error = 1;
							$msg_error = "Error al insertar los anexos.";
						}

						// Anexos
						array_push(
							$Anexos,
							array(
								"id_anexo" => $j,
								"tipo_documento" => 0,
								"id_documento" => 0,
								"archivo" => $OnlyName,
								"ext_archivo" => $Ext,
								"metodo" => 1,
								"fecha" => FormatoFechaToSAP(date('Y-m-d'), date('H:i:s')),
								"id_usuario" => intval($_SESSION['CodUser']),
								"comentarios" => "",
								"id_destino_evidencia" => "",
							)
						);
					}
					$j++;
				}
			} catch (Exception $e) {
				echo 'Excepcion capturada: ', $e->getMessage(), "\n";
			}

			// Inicio, Insertar en WebService

			// Consultar cabecera
			$SQL_json = Seleccionar("uvw_tbl_TarjetaEquipo", "*", "ID_Equipo = $IdTarjetaEquipo");
			$row_json = sqlsrv_fetch_array($SQL_json);

			$Cabecera = array(
				"id_tipo_equipo" => $_POST['TipoEquipo'],
				"id_estado" => $_POST['CodEstado'],
				"id_contacto" => $row_json['CodigoContacto'],
				"telefono_contacto" => $_POST['TelefonoCliente'],
				"numero_serial_interno" => $_POST['SerialInterno'],
				"numero_serial_fabricante" => $_POST['SerialFabricante'],
				"id_articulo" => $_POST['ItemCode'],
				"articulo" => $_POST['ItemName'],
				"id_socio_negocio" => $_POST['ClienteEquipo'],
				"socio_negocio" => $_POST['NombreClienteEquipo'],
				"direccion" => $_POST['Calle'],
				"id_postal" => $_POST['CodigoPostal'],
				"barrio" => "",
				"distrito" => $_POST['Distrito'],
				"ciudad" => $_POST['Ciudad'],
				"id_pais" => $_POST['Pais'],
				"id_territorio" => $row_json['IdTerritorio'],
				"id_tecnico_responsable" => ($row_json['IdTecnico'] != 0) ? $row_json['IdTecnico'] : null,
				"id_anexo" => null,
				"id_doc_portal" => "",
				"usuario_creacion" => $_SESSION['User'],
				"CDU_id_marca" => $_POST['CDU_IdMarca'],
				"CDU_id_linea" => $_POST['CDU_IdLinea'],
				"CDU_annio" => $_POST['CDU_Ano'],
				"CDU_id_concesionario" => ($_POST['CDU_Concesionario'] ?? ""),
				"CDU_id_color" => ($_POST['CDU_Color'] ?? ""),
				"CDU_id_cilindraje" => ($_POST['CDU_Cilindraje'] ?? ""),
				"CDU_id_tipo_servicio" => ($_POST['CDU_TipoServicio'] ?? ""),
				"CDU_no_motor" => ($_POST['CDU_No_Motor'] ?? ""),
				"CDU_id_novedad" => ($_POST['CDU_Novedad'] ?? ""),
				"CDU_IdTipoVehiculo" => ($_POST['CDU_IdTipoVehiculo'] ?? ""),
				"CDU_IdTipoRin" => ($_POST['CDU_IdTipoRin'] ?? ""),
				"anexos" => (count($Anexos) > 0) ? $Anexos : null,
				// SMM, 27/02/2024
				"id_tipo_equipo_propiedad" => ($_POST['IdTipoEquipoPropiedad'] ?? ""),
				"id_unidad_medida_equipo" => ($_POST['IdUnidadMedidaEquipo'] ?? ""),
				"fecha_operacion_equipo" => ($_POST['FechaOperacionEquipo'] ?? ""),
				"id_jerarquia_1" => ($_POST['IdJerarquia1'] ?? ""),
				"id_jerarquia_2" => ($_POST['IdJerarquia2'] ?? ""),
				"id_ubicacion" => ($_POST['IdUbicacion'] ?? ""),
				"id_dimension_1" => ($_POST['IdDimension1'] ?? ""),
				"id_dimension_2" => ($_POST['IdDimension2'] ?? ""),
				"id_dimension_3" => ($_POST['IdDimension3'] ?? ""),
				"id_dimension_4" => ($_POST['IdDimension4'] ?? ""),
				"id_dimension_5" => ($_POST['IdDimension5'] ?? ""),
				"id_proyecto" => ($_POST['IdProyecto'] ?? ""),
				"id_fabricante_equipo" => ($_POST['IdFabricante'] ?? ""),
				"latitud_gps_ubicacion" => ($_POST['latGPS'] ?? ""),
				"longitud_gps_ubicacion" => ($_POST['lngGPS'] ?? ""),
			);

			// Agregar fechas, inicio.
			if (isset($row_json['CDU_FechaMatricula'])) {
				$Cabecera["CDU_fecha_matricula"] = ($row_json['CDU_FechaMatricula']->format('Y-m-d') . "T" . $row_json['CDU_FechaMatricula']->format('H:i:s'));
			}

			if (isset($row_json['CDU_Fecha_Tecno'])) {
				$Cabecera["CDU_fecha_tecnicomecanica"] = ($row_json['CDU_Fecha_Tecno']->format('Y-m-d') . "T" . $row_json['CDU_Fecha_Tecno']->format('H:i:s'));
			}

			if (isset($row_json['CDU_Fecha_SOAT'])) {
				$Cabecera["CDU_fecha_soat"] = ($row_json['CDU_Fecha_SOAT']->format('Y-m-d') . "T" . $row_json['CDU_Fecha_SOAT']->format('H:i:s'));
			}

			if (isset($row_json['CDU_FechaUlt_CambAceite'])) {
				$Cabecera["CDU_fecha_ult_cambio_aceite"] = ($row_json['CDU_FechaUlt_CambAceite']->format('Y-m-d') . "T" . $row_json['CDU_FechaUlt_CambAceite']->format('H:i:s'));
			}

			if (isset($row_json['CDU_FechaUlt_Mant'])) {
				$Cabecera["CDU_fecha_ult_mantenimiento"] = ($row_json['CDU_FechaUlt_Mant']->format('Y-m-d') . "T" . $row_json['CDU_FechaUlt_Mant']->format('H:i:s'));
			}

			if (isset($row_json['CDU_FechaUlt_CambLlantas'])) {
				$Cabecera["CDU_fecha_ult_cambio_llanta"] = ($row_json['CDU_FechaUlt_CambLlantas']->format('Y-m-d') . "T" . $row_json['CDU_FechaUlt_CambLlantas']->format('H:i:s'));
			}

			if (isset($row_json['CDU_FechaUlt_AlinBalan'])) {
				$Cabecera["CDU_fecha_ult_alineacion_balanceo"] = ($row_json['CDU_FechaUlt_AlinBalan']->format('Y-m-d') . "T" . $row_json['CDU_FechaUlt_AlinBalan']->format('H:i:s'));
			}
			// Agregar fechas, fin.

			// Agregar campos de actualización, inicio.
			if ($Metodo == 2) {
				$Cabecera["id_tarjeta_equipo"] = $row_json['IdTarjetaEquipo'];
				$Cabecera["usuario_actualizacion"] = $_SESSION['User'];
				$Cabecera["fecha_actualizacion"] = ($row_json['FechaActualizacion']->format('Y-m-d') . "T" . $row_json['FechaActualizacion']->format('H:i:s'));
				$Cabecera["hora_actualizacion"] = ($row_json['FechaActualizacion']->format('Y-m-d') . "T" . $row_json['FechaActualizacion']->format('H:i:s'));
				$Cabecera["seg_actualizacion"] = intval($row_json['FechaActualizacion']->format('s'));
				$Cabecera["metodo"] = $Metodo;
			}
			// Agregar campos de actualización, fin.

			// Enviar datos al WebServices
			try {
				if ($Metodo == 1) { // Creando
					$Metodo = "TarjetaEquipos";
					$Resultado = EnviarWebServiceSAP($Metodo, $Cabecera, true, true);
				} else { // Editando
					$Metodo = "TarjetaEquipos/" . base64_decode($_POST['ID_TarjetaEquipo']);
					$Resultado = EnviarWebServiceSAP($Metodo, $Cabecera, true, true, "PUT");
				}

				if ($Resultado->Success == 0) {
					$sw_error = 1;
					$msg_error = $Resultado->Mensaje;
					$Cabecera_json = json_encode($Cabecera);
					// header("Location:tarjeta_equipo.php?id=$IdTarjetaEquipo&swError=1&a=" . base64_encode($Msg));
					// echo "<script>alert('$msg_error'); location = 'tarjeta_equipo.php';</script>";
				} else {
					// Solo se utiliza en la creación.
					$SQL_ID = Seleccionar("uvw_Sap_tbl_TarjetasEquipos", "IdTarjetaEquipo", "SerialInterno='" . ($row_json["SerialInterno"] ?? "") . "'");
					$row_ID = sqlsrv_fetch_array($SQL_ID);
					
					// SMM, 27/02/2024
					$ID_TarjetaEquipo = ($_POST['tl'] == 1) ? base64_decode($_POST['ID_TarjetaEquipo']) : ($row_ID['IdTarjetaEquipo'] ?? "");
					
					// Las propiedades solo se pueden modificar en la Actualización.
					if($_POST['tl'] == 1) {
						$Param_Propiedades = array(
							3, // Eliminar
							"'$ID_TarjetaEquipo'",
						);
						EjecutarSP("sp_tbl_TarjetaEquipo_Propiedades", $Param_Propiedades);
	
						// Inicio, insertar propiedades dinámicamente.
						foreach ($_POST as $ClavePropiedad => $ValorPropiedad) {
							if (strpos($ClavePropiedad, 'propiedad_') === 0) {
								// Obtener el ID correspondiente
								$IdPropiedad = substr($ClavePropiedad, strlen('propiedad_'));
						
								// echo "ID: $IdPropiedad, $ClavePropiedad: $ValorPropiedad <br>";
								$Usuario = "'" . ($_SESSION['CodUser'] ?? "") . "'";
	
								$Param_Propiedades = array(
									1, // Crear
									"'$ID_TarjetaEquipo'",
									"'$IdPropiedad'",
									"'$ValorPropiedad'",
									$Usuario, // Usuario de creación
								);
	
								$SQL_Propiedades = EjecutarSP("sp_tbl_TarjetaEquipo_Propiedades", $Param_Propiedades);
							}
						}
						// Fin, inserción en la tabla de propiedades.
	
						// SMM, 27/02/2024
						// exit();
					}

					// Redirección según el tipo de operación.
					$Msg = ($_POST['tl'] == 1) ? "OK_TarjetaEquipoUpdate" : "OK_TarjetaEquipoAdd";

					if ($_POST['tl'] == 1) {
						header('Location:tarjeta_equipo.php?id=' . $_POST['ID_TarjetaEquipo'] . '&tl=1&a=' . base64_encode($Msg));
					} else {
						header('Location:tarjeta_equipo.php?id=' . base64_encode($row_ID['IdTarjetaEquipo']) . '&tl=1&a=' . base64_encode($Msg));
					}

					$edit = 1;
					$_GET['a'] = base64_encode($Msg);
				}
			} catch (Exception $e) {
				echo 'Excepcion capturada: ', $e->getMessage(), "\n";
			}
			// Fin, Insertar en WebService
		} else {
			$sw_error = 1;
			$msg_error = "Ha ocurrido un error al crear la tarjeta de equipo";
		}
	} catch (Exception $e) {
		echo 'Excepcion capturada: ', $e->getMessage(), "\n";
	}

}

if ($edit == 1 && $sw_error == 0) { // Editando la tarjeta de equipo
	if (isset($_GET['te']) && ($_GET['te'] == 1)) {
		$SQL = Seleccionar('uvw_Sap_tbl_TarjetasEquipos', '*', "SerialInterno='$IdTarjetaEquipo'");
	} else {
		// Ruta normal
		$SQL = Seleccionar('uvw_Sap_tbl_TarjetasEquipos', '*', "IdTarjetaEquipo='$IdTarjetaEquipo'");
	}

	// var_dump($IdTarjetaEquipo);
	$row = sqlsrv_fetch_array($SQL);

	$CardCode = $row['CardCode'] ?? "";
	$IdAnexo = $row['IdAnexo'] ?? "";
	$IdTarjetaEquipo = $row['IdTarjetaEquipo'] ?? "";

	if ($IdTarjetaEquipo == "") {
		$IdTE = false;
		echo "Tarjeta de equipo NO encontrada.";
	}

	//Clientes
	$SQL_Cliente = Seleccionar("uvw_Sap_tbl_Clientes", "CodigoCliente, NombreCliente", "CodigoCliente='$CardCode'", 'NombreCliente');

	//Contactos clientes
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='$CardCode'", 'NombreContacto');

	//Anexos
	$SQL_Anexos = Seleccionar('uvw_Sap_tbl_DocumentosSAP_Anexos', '*', "AbsEntry='$IdAnexo'");

	//Llamadas de servicio
	$SQL_LlamadasServicio = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_LlamadasServicios', '*', "IdTarjetaEquipo='$IdTarjetaEquipo'", 'FechaHoraCreacionLlamada', 'DESC');

	// Solicitud Llamada (Agenda). SMM, 22/01/2024
	$SQL_Agenda = Seleccionar("uvw_Sap_tbl_TarjetasEquipos_SolicitudLlamadasServicios", "*", "IdTarjetaEquipo='$IdTarjetaEquipo'");

	//Contratos de servicio
	$SQL_ContratosServicio = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_Contratos', '*', "IdTarjetaEquipo='$IdTarjetaEquipo'", 'ID_Contrato');

	// Historico de gestiones, SMM 04/04/2024
	$SQL_HistGestion = Seleccionar("uvw_tbl_Cartera_Gestion", "TOP(50) *", "CardCode = '$CardCode'", "FechaRegistro DESC");
}

if ($sw_error == 1) {
	// Si ocurre un error, vuelvo a consultar los datos insertados desde la base de datos.
	$SQL = Seleccionar("uvw_tbl_TarjetaEquipo", "*", "ID_Equipo='$IdTarjetaEquipo'");
	$row = sqlsrv_fetch_array($SQL);

	$CardCode = $row['CardCode'] ?? "";
	$IdAnexo = $row['IdAnexo'] ?? "";
	$IdTarjetaEquipo = $row['ID_Equipo'] ?? "";

	//Clientes
	$SQL_Cliente = Seleccionar("uvw_Sap_tbl_Clientes", "CodigoCliente, NombreCliente", "CodigoCliente='$CardCode'", 'NombreCliente');

	//Contactos clientes
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='$CardCode'", 'NombreContacto');

	// Anexos, Stiven Muñoz Murillo
	$SQL_Anexos = Seleccionar('uvw_tbl_DocumentosSAP_Anexos', '*', "ID_Documento='$IdTarjetaEquipo'");

	//Llamadas de servicio
	$SQL_LlamadasServicio = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_LlamadasServicios', '*', "IdTarjetaEquipo='$IdTarjetaEquipo'", 'AsuntoLlamada');

	// Solicitud Llamada (Agenda). SMM, 22/01/2024
	$SQL_Agenda = Seleccionar("uvw_Sap_tbl_TarjetasEquipos_SolicitudLlamadasServicios", "*", "IdTarjetaEquipo='$IdTarjetaEquipo'");

	//Contratos de servicio
	$SQL_ContratosServicio = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_Contratos', '*', "IdTarjetaEquipo='$IdTarjetaEquipo'", 'ID_Contrato');

	// Historico de gestiones, SMM 04/04/2024
	$SQL_HistGestion = Seleccionar("uvw_tbl_Cartera_Gestion", "TOP(50) *", "CardCode = '$CardCode'", "FechaRegistro DESC");
}

//Tecnicos
$SQL_Tecnicos = Seleccionar('uvw_Sap_tbl_Recursos', '*', '', 'NombreEmpleado');

// Territorios
$SQL_Territorios = Seleccionar('uvw_Sap_tbl_Territorios', '*', '', 'DeTerritorio');

// @author Stiven Muñoz Murillo
// @version 05/12/2021

// Marcas de vehiculo en la tarjeta de equipo
$SQL_MarcaVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_MarcaVehiculo', '*');

// Lineas de vehiculo en la tarjeta de equipo
$SQL_LineaVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_LineaVehiculo', '*');

// Modelo o año de fabricación de vehiculo en la tarjeta de equipo
$SQL_ModeloVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_AñoModeloVehiculo', '*');

// Concesionarios en la tarjeta de equipo
$SQL_Concesionario = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_Concesionario', '*');

// Colores de vehiculo en la tarjeta de equipo
$SQL_ColorVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_ColorVehiculo', '*');

// Cilindraje de vehiculos en la tarjeta de equipo
$SQL_CilindrajeVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_CilindrajeVehiculo', '*');

// Tipos de servicio en la tarjeta de equipo
$SQL_TipoServicio = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_TipoServicio', '*');

// Tipo de vehículo. SMM, 28/06/2023
$SQL_TipoVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_TipoVehiculos', '*');

// Tipos de rin. SMM, 28/06/2023
$SQL_TipoRines = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_TipoRines', '*');

// Novedades en la tarjeta de equipo, SMM, 23/06/2022
$SQL_Novedades = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_Novedades', '*');

// SMM, 26/02/2024
$SQL_Fabricante = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_Fabricante', '*');

// SMM, 26/02/2024
$SQL_ActivoFijo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_ActivoFijo', '*');

// Stiven Muñoz Murillo, 08/02/2022
if (isset($_GET['dt_TE']) && ($_GET['dt_TE']) == 1) { //Verificar que viene de una Tarjeta de Equipo (Datos Tarjeta de Equipo)
	$dt_TE = 1;

	//Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreCliente');
	$row_Cliente = sqlsrv_fetch_array($SQL_Cliente);
	// echo base64_decode($_GET['Cardcode']);

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreContacto');

	//Sucursal cliente
	$SQL_SucursalCliente = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreSucursal');
}

// SMM, 15/08/2023
$SQL_Formularios = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_RecepcionEntregaVehiculo', '*', "IdTarjetaEquipo='$IdTarjetaEquipo'");

// SMM, 15/08/2023
$VIN = $row['SerialFabricante'] ?? "";
$SQL_Campanas = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_CampañaVehiculo', '*', "VIN='$VIN'");

// Stiven Muñoz Murillo, 28/01/2022
$row_encode = isset($row) ? json_encode($row) : "";
$cadena = isset($row) ? "JSON.parse('$row_encode'.replace(/\\n|\\r/g, ''))" : "'Not Found'";
// echo "<script> console.log($cadena); </script>";

// SMM, 22/02/2024
$SQL_TipoEquipo = Seleccionar("tbl_TarjetaEquipo_TiposEquipos", "*", "estado_tipo_equipo = 'Y'");
$SQL_UnidadMedida = Seleccionar("tbl_TarjetaEquipo_UnidadMedidas", "*");
$SQL_UbicacionEquipo = Seleccionar("uvw_tbl_TarjetaEquipo_Ubicaciones", "*");
$SQL_Proyecto = Seleccionar("uvw_Sap_tbl_Proyectos", "*");

// SMM, 23/02/2024
$id_tipo_equipo = isset($_GET["id_tipo_equipo"]) ? $_GET["id_tipo_equipo"] : ($row["IdTipoEquipoPropiedad"] ?? ""); 
$SQL_Propiedades = Seleccionar("tbl_TarjetaEquipo_TiposEquipos_Propiedades", "*","id_tipo_equipo = $id_tipo_equipo");

// SMM, 28/02/2024
$SQL_Campo = Seleccionar("tbl_TarjetaEquipo_TiposEquipos_Campos", "*","id_tipo_equipo_campo = 1");
$row_Campo = sqlsrv_fetch_array($SQL_Campo);
$lenTexto = $row_Campo["precision"] ?? 50;

$SQL_Campo = Seleccionar("tbl_TarjetaEquipo_TiposEquipos_Campos", "*","id_tipo_equipo_campo = 2");
$row_Campo = sqlsrv_fetch_array($SQL_Campo);
$lenComentarios = $row_Campo["precision"] ?? 300;

// SMM, 28/02/2024
$SQL_ValoresPropiedades = Seleccionar("tbl_TarjetaEquipo_Propiedades", "*","id_tarjeta_equipo = $IdTarjetaEquipo");

$array_ValoresPropiedades = [];
if($SQL_ValoresPropiedades) {
	while ($row_ValPropiedad = sqlsrv_fetch_array($SQL_ValoresPropiedades)) {
		array_push($array_ValoresPropiedades, $row_ValPropiedad);
	}
}

// print_r($array_ValoresPropiedades);
// exit();
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include "includes/cabecera.php"; ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>
		<?php echo $Title; ?> |
		<?php echo NOMBRE_PORTAL; ?>
	</title>
	<!-- InstanceEndEditable -->
	<!-- InstanceBeginEditable name="head" -->
	<?php
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_TarjetaEquipoAdd"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La tarjeta de equipo ha sido creada exitosamente.',
                icon: 'success'
            });
		});
		</script>";
	}
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_TarjetaEquipoUpdate"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La tarjeta de equipo ha sido actualizada exitosamente.',
                icon: 'success'
            });
		});
		</script>";
	}
	if (isset($sw_error) && ($sw_error == 1)) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Ha ocurrido un error!',
                text: `" . LSiqmlObs($msg_error) . "`,
                icon: 'warning'
            });
		});
		</script>";
	}

	// SMM, 19/04/2022
	if (!$IdTE) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Ha ocurrido un error!',
                text: 'Tarjeta de equipo NO encontrada.',
                icon: 'warning'
            });
		});
		</script>";
	}
	?>
	<style>
		.ibox-title a {
			color: inherit !important;
		}

		.collapse-link:hover {
			cursor: pointer;
		}

		/* SMM, 06/03/2024 */
		.highlighted {
			background-color: #f0f0f0; /* Cambiar el color de fondo */
		}
	</style>
	<script type="text/javascript">
		$(document).ready(function () {//Cargar los combos dependiendo de otros
			$("#ClienteEquipo").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);
				var Cliente = document.getElementById('ClienteEquipo').value;
				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=2&id=" + Cliente,
					success: function (response) {
						$('#ContactoCliente').html(response).fadeIn();
						$('#ContactoCliente').trigger('change');
					}
				});
				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: { type: 40, id: Cliente },
					dataType: 'json',
					success: function (data) {
						console.log("Line 514", data);
						if (data.CodigoCliente == '') {
							console.log("CodigoCliente no encontrado, posiblemente este en la vista [uvw_Sap_tbl_SociosNegocios]");
							console.log("Código decodificado, <?php echo base64_decode($_GET['Cardcode'] ?? ''); ?>");
						}

						document.getElementById('TelefonoCliente').value = data.Telefono;
						// Cargando información en pestaña 'Dirección'
						document.getElementById('Calle').value = data.DirDestino;
						document.getElementById('CodigoPostal').value = data.CodPostalDestino;
						document.getElementById('Ciudad').value = data.Ciudad;
						document.getElementById('EstadoPais').value = data.CodDepartamentoDestino;
						document.getElementById('Distrito').value = data.DepartamentoDestino;
						document.getElementById('Pais').value = data.PaisDestino;
					},
					error: function (data) {
						console.error("Line 530", data);
					}
				});
				$('.ibox-content').toggleClass('sk-loading', false);
			});
			// Stiven Muñoz Murillo, 20/12/2021
			$("#CDU_IdMarca").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);
				var marcaVehiculo = document.getElementById('CDU_IdMarca').value;
				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=39&id=" + marcaVehiculo,
					success: function (response) {
						$('#CDU_IdLinea').html(response).fadeIn();
						$('#CDU_IdLinea').trigger('change');
						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
			});
		});
	</script>
	<script>
		function HabilitarCampos(type = 1) {
			if (type == 0) {//Deshabilitar
				document.getElementById('DatosActividad').style.display = 'none';
				document.getElementById('DatosCliente').style.display = 'none';
			} else {//Habilitar
				document.getElementById('DatosActividad').style.display = 'block';
				document.getElementById('DatosCliente').style.display = 'block';
			}
		}
		function ConsultarDatosCliente() {
			var Cliente = document.getElementById('ClienteEquipo');
			if (Cliente.value != "") {
				self.name = 'opener';
				remote = open('socios_negocios.php?id=' + Base64.encode(Cliente.value) + '&ext=1&tl=1', 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
				remote.focus();
			}
		}
		// Stiven Muñoz Murillo, 02/02/2022
		function mayus(e) {
			e.value = e.value.toUpperCase();
		}

		// SMM, 01/07/2022
		function MostrarCostos(id_llamada) {
			$('.ibox-content').toggleClass('sk-loading', true);
			$.ajax({
				type: "POST",
				async: false,
				url: "md_articulos_documentos.php",
				data: {
					pre: 3,
					DocEntry: id_llamada
				},
				success: function (response) {
					$('.ibox-content').toggleClass('sk-loading', false);
					$('#ContenidoModal').html(response);
					$('#TituloModal').html('Precios IVA Incluido (Entregas (+) / Devoluciones (-))');
					$('#myModal').modal("show");
				}
			});
		}

		// SMM, 28/09/2022
		function MostrarAutorizados(id_llamada) {
			$('.ibox-content').toggleClass('sk-loading', true);
			$.ajax({
				type: "POST",
				async: false,
				url: "md_articulos_documentos.php",
				data: {
					pre: 5,
					DocEntry: id_llamada
				},
				success: function (response) {
					$('.ibox-content').toggleClass('sk-loading', false);
					$('#ContenidoModal').html(response);
					$('#TituloModal').html('Articulos No Autorizados');
					$('#myModal').modal("show");
				}
			});
		}

		// SMM, 15/09/2022
		function ConsultarDocVentas(tipo) {
			let palabra = ""
			if (tipo == 1) {
				palabra = "Entrega";
			} else {
				palabra = "Factura";
			}

			let doc = document.getElementById(`DocEntry${palabra}2`);
			if (doc.value != "") {
				self.name = 'opener';
				open(`${palabra.toLowerCase()}_venta.php?id=${Base64.encode(doc.value)}&tl=1`, "_blank");
			} else {
				console.log("El campo que se quiere consultar no puede estar vacio");
			}
		}
	</script>

	<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
	<script>
		function generarCodigoQR() {
			const urlActual = window.location.href; // Obtiene la URL actual
			const qrcode = new QRCode(document.getElementById("qrcode"), {
				text: urlActual,
				width: 128,
				height: 128
			});
		}
	</script>
	<!-- InstanceEndEditable -->

</head>

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
			<!-- InstanceBeginEditable name="Contenido" -->
			<div class="row wrapper border-bottom white-bg page-heading">
				<div class="col-sm-8">
					<h2>
						<?php echo $Title; ?>
					</h2>
					<ol class="breadcrumb">
						<li>
							<a href="#">Mantenimiento</a>
						</li>
						<li>
							<a href="consultar_tarjeta_equipo.php">Tarjetas de equipos</a>
						</li>
						<li class="active">
							<strong>
								<?php echo $Title; ?>
							</strong>
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
								<button type="button" class="btn btn-success m-t-md" data-dismiss="modal"><i
										class="fa fa-times"></i> Cerrar</button>
							</div>
						</div>
					</div>
				</div>
				<!-- Fin, myModal -->

				<!-- SMM, 29/02/2024 -->
				<div class="modal inmodal fade" id="mdComponents" tabindex="1" role="dialog" aria-hidden="true"
					data-backdrop="static" data-keyboard="false"></div>

				<?php if ($edit == 1) { ?>
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

												<div class="btn-group">
													<button data-toggle="dropdown"
														class="btn btn-outline btn-success dropdown-toggle"><i
															class="fa fa-download"></i> Descargar formato <i
															class="fa fa-caret-down"></i></button>
													<ul class="dropdown-menu">
														<?php
														$SQL_Formato = Seleccionar('uvw_tbl_FormatosSAP', '*', "ID_Objeto=176 and VerEnDocumento='Y'");
														while ($row_Formato = sqlsrv_fetch_array($SQL_Formato)) { ?>
															<li>
																<a class="dropdown-item" target="_blank"
																	href="formatdownload.php?DocKey=<?php echo $row['IdTarjetaEquipo'] ?? ""; ?>&ObType=<?php echo $row_Formato['ID_Objeto'] ?? ""; ?>&IdFrm=<?php echo $row_Formato['IdFormato'] ?? ""; ?>&IdReg=<?php echo $row_Formato['ID'] ?? ""; ?>">
																	<?php echo $row_Formato['NombreVisualizar'] ?? ""; ?>
																</a>
															</li>
														<?php } ?>
													</ul>

													<!-- Crear llamada, SMM 23/05/2022 -->
													<?php if (isset($row['CodEstado']) && (strcmp("A", $row['CodEstado']) == 0)) { ?>
														<a style="margin-left: 10px;" class="btn btn-outline btn-info"
															href="llamada_servicio.php?dt_LS=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Serial=<?php echo base64_encode($row['SerialInterno']); ?>&IdTE=<?php echo $row['IdTarjetaEquipo'] ?? ""; ?>"
															target="_blank"><i class="fa fa-plus-circle"></i> Crear llamada de
															servicio</a>
													<?php } ?>
												</div>

											</div>
											<div class="col-lg-6">
												
											<div id="qrcode"></div>
    										<button onclick="generarCodigoQR()">Generar Código QR</button>

												<button data-toggle="dropdown"
													class="btn btn-success dropdown-toggle pull-right">
													<i class="fa fa-qrcode"></i> 
													Generar QR 
													<i class="fa fa-caret-down"></i>
												</button>
												<ul class="dropdown-menu pull-right">
													<li>
														<a class="dropdown-item" target="_blank"
															href="#">
															Hoja de vida
														</a>
													</li>
													<li>
														<a class="dropdown-item" target="_blank"
															href="#">
															Tarjeta digital
														</a>
													</li>
												</ul>

											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<br>
				<?php } ?>

				<div class="ibox-content">
					<?php include "includes/spinner.php"; ?>
					<div class="row">
						<div class="col-lg-12">
							<form action="tarjeta_equipo.php" method="post" class="form-horizontal"
								enctype="multipart/form-data" id="CrearTarjetaEquipo">
								<div class="ibox">
									<div class="ibox-title bg-success">
										<h5 class="collapse-link"><i class="fa fa-info-circle"></i> Información de
											tarjeta de equipo</h5>
										<a class="collapse-link pull-right">
											<i class="fa fa-chevron-up"></i>
										</a>
									</div>
									<div class="ibox-content">
										<div class="form-group">
											<label class="col-lg-1 control-label">Tipo de proceso <span
													class="text-danger">*</span></label>
											<div class="col-lg-3">
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="TipoEquipo" class="form-control" id="TipoEquipo" required>
													<option value="">Seleccione...</option>
													<option value="P" <?php if ((isset($row['TipoEquipo'])) && (strcmp("P", $row['TipoEquipo']) == 0)) {
														echo "selected";
													} ?>>
														Compras</option>
													<option value="R" <?php if ((isset($row['TipoEquipo'])) && (strcmp("R", $row['TipoEquipo']) == 0)) {
														echo "selected";
													} ?>>
														Ventas</option>
												</select>
											</div>
											<label class="col-lg-1 control-label">Serial Interno (Placa) <span
													class="text-danger">*</span></label>
											<div class="col-lg-3">
												<input <?php if (!PermitirFuncion(1602)) {
													echo "readonly";
												} ?>
													autocomplete="off" onkeyup="mayus(this);" name="SerialInterno"
													type="text" required class="form-control"
													id="SerialInterno" maxlength="150" value="<?php if (isset($row['SerialInterno'])) {
														echo $row['SerialInterno'];
													} ?>">
											</div>
											<label class="col-lg-1 control-label">Serial Fabricante (VIN) <span
													class="text-danger">*</span></label>
											<div class="col-lg-3">
												<input <?php if (!PermitirFuncion(1602)) {
													echo "readonly";
												} ?>
													autocomplete="off" onkeyup="mayus(this);" name="SerialFabricante"
													type="text" required class="form-control"
													id="SerialFabricante" maxlength="150" value="<?php if (isset($row['SerialFabricante'])) {
														echo $row['SerialFabricante'];
													} ?>">
											</div>
										</div>
										<div class="form-group">
											<label class="col-lg-1 control-label">Número de artículo <span
													class="text-danger">*</span></label>
											<div class="col-lg-3">
												<input <?php if (!PermitirFuncion(1602)) {
													echo "readonly";
												} ?>
													autocomplete="off" placeholder="Digite para buscar..."
													name="ItemCode" type="text" required class="form-control"
													id="ItemCode" maxlength="150" value="<?php if (isset($row['ItemCode'])) {
														echo $row['ItemCode'];
													} ?>">
											</div>
											<label class="col-lg-1 control-label">Descripción del artículo <span
													class="text-danger">*</span></label>
											<div class="col-lg-3">
												<input <?php if (!PermitirFuncion(1602)) {
													echo "readonly";
												} ?>
													autocomplete="off" name="ItemName" type="text" required
													class="form-control" id="ItemName" maxlength="150" value="<?php if (isset($row['ItemName'])) {
														echo $row['ItemName'];
													} ?>">
											</div>
											<label class="col-lg-1 control-label">Estado <span
													class="text-danger">*</span></label>
											<div class="col-lg-3">
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CodEstado" class="form-control" id="CodEstado" required>
													<option value="">Seleccione...</option>
													<option value="A" <?php if ((isset($row['CodEstado'])) && (strcmp("A", $row['CodEstado']) == 0)) {
														echo "selected";
													} ?>>
														Activo</option>
													<option value="R" <?php if ((isset($row['CodEstado'])) && (strcmp("R", $row['CodEstado']) == 0)) {
														echo "selected";
													} ?>>
														Devuelto</option>
													<option value="T" <?php if ((isset($row['CodEstado'])) && (strcmp("T", $row['CodEstado']) == 0)) {
														echo "selected";
													} ?>>
														Finalizado</option>
													<option value="L" <?php if ((isset($row['CodEstado'])) && (strcmp("L", $row['CodEstado']) == 0)) {
														echo "selected";
													} ?>>
														Concedido en prestamo</option>
													<option value="I" <?php if ((isset($row['CodEstado'])) && (strcmp("I", $row['CodEstado']) == 0)) {
														echo "selected";
													} ?>>En
														laboratorio de reparación</option>
												</select>
											</div>
										</div>
										<div class="form-group">
											<label class="col-lg-1 control-label">Número de serie anterior</label>
											<div class="col-lg-3">
												<input <?php if (!PermitirFuncion(1602)) {
													echo "readonly";
												} ?>
													autocomplete="off" name="SerieAnterior" type="text"
													class="form-control" id="SerieAnterior" maxlength="150" value="<?php if (isset($row['SerieAnterior'])) {
														echo $row['SerieAnterior'];
													} ?>">
											</div>
											<label class="col-lg-1 control-label">Número de serie nuevo</label>
											<div class="col-lg-3">
												<input <?php if (!PermitirFuncion(1602)) {
													echo "readonly";
												} ?>
													autocomplete="off" name="SerieNueva" type="text"
													class="form-control" id="SerieNueva" maxlength="150" value="<?php if (isset($row['SerieNueva'])) {
														echo $row['SerieNueva'];
													} ?>">
											</div>
										</div>
									</div>
								</div>

								<!-- INICIO, InfoSN -->
								<div class="ibox">
									<div class="ibox-title bg-success">
										<h5 class="collapse-link"><i class="fa fa-group"></i> Información de socio de
											negocio</h5>
										<a class="collapse-link pull-right">
											<i class="fa fa-chevron-up"></i>
										</a>
									</div>
									<div class="ibox-content">
										<div class="form-group">
											<label class="col-lg-1 control-label"><i onClick="ConsultarDatosCliente();"
													title="Consultar cliente" style="cursor: pointer"
													class="btn-xs btn-success fa fa-search"></i> Socio de negocio <span
													class="text-danger">*</span></label>
											<div class="col-lg-3">
												<input <?php if (!PermitirFuncion(1602)) {
													echo "readonly";
												} ?>
													name="ClienteEquipo" type="hidden" id="ClienteEquipo" value="<?php if (($edit == 1) || ($sw_error == 1)) {
														echo $row['CardCode'];
													} elseif ($dt_TE == 1) {
														echo $row_Cliente['CodigoCliente'] ?? "";
													} ?>">

												<input <?php if (!PermitirFuncion(1602) || ($dt_TE == 1)) {
													echo "readonly";
												} ?> name="NombreClienteEquipo" type="text"
													required class="form-control" id="NombreClienteEquipo"
													placeholder="Digite para buscar..." value="<?php if (($edit == 1) || ($sw_error == 1)) {
														echo $row['CardName'] ?? "";
													} elseif ($dt_TE == 1) {
														echo $row_Cliente['NombreCliente'] ?? "";
													} ?>">
											</div>
											<label class="col-lg-1 control-label">Persona de contacto</label>
											<div class="col-lg-3">
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="ContactoCliente" class="form-control" id="ContactoCliente">
													<option value="">Seleccione...</option>
													<?php if (($edit == 1) || ($sw_error == 1) || ($dt_TE == 1)) {
														while ($row_ContactoCliente = sqlsrv_fetch_array($SQL_ContactoCliente)) { ?>
															<option
																value="<?php echo $row_ContactoCliente['CodigoContacto']; ?>"
																<?php if ((isset($row['CodigoContacto'])) && (strcmp($row_ContactoCliente['CodigoContacto'], $row['CodigoContacto']) == 0)) {
																	echo "selected";
																} ?>>
																<?php echo $row_ContactoCliente['ID_Contacto']; ?>
															</option>
														<?php }
													} ?>
												</select>
											</div>
											<label class="col-lg-1 control-label">Número de contacto <span
													class="text-danger">*</span></label>
											<div class="col-lg-3">
												<input <?php if (!PermitirFuncion(1602)) {
													echo "readonly";
												} ?>
													autocomplete="off" name="TelefonoCliente" type="text"
													class="form-control" id="TelefonoCliente" required
													maxlength="150" value="<?php if (isset($row['TelefonoCliente'])) {
														echo $row['TelefonoCliente'];
													} elseif (($dt_TE == 1) && isset($row_Cliente['Telefono'])) {
														echo $row_Cliente['Telefono'];
													} ?>">
											</div>
										</div>
										<div class="form-group">
											<label class="col-lg-1 control-label">Técnico</label>
											<div class="col-lg-3">
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="IdTecnico" class="form-control select2" id="IdTecnico">
													<option value="">Seleccione...</option>
													<?php while ($row_Tecnicos = sqlsrv_fetch_array($SQL_Tecnicos)) { ?>
														<option value="<?php echo $row_Tecnicos['ID_Empleado']; ?>" <?php if ((isset($row['IdTecnico'])) && (strcmp($row_Tecnicos['ID_Empleado'], $row['IdTecnico']) == 0)) {
															   echo "selected";
														   } ?>>
															<?php echo $row_Tecnicos['NombreEmpleado']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
											<label class="col-lg-1 control-label">Territorio <span
													class="text-danger">*</span></label>
											<div class="col-lg-3">
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="IdTerritorio" class="form-control" id="IdTerritorio" required>
													<option value="">(Ninguno)</option>
													<?php
													while ($row_Territorio = sqlsrv_fetch_array($SQL_Territorios)) { ?>
														<option value="<?php echo $row_Territorio['IdTerritorio']; ?>" <?php if ((isset($row['IdTerritorio'])) && (strcmp($row_Territorio['IdTerritorio'], $row['IdTerritorio']) == 0)) {
															   echo "selected";
														   } ?>>
															<?php echo $row_Territorio['DeTerritorio']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
										</div>
									</div>
								</div>
								<!-- FIN, InfoSN -->

								<!-- INICIO, Info. Equipo -->
								<div class="ibox">
									<div class="ibox-title bg-success">
										<h5 class="collapse-link">
											<i class="fa fa-wrench"></i> Información del equipo
										</h5>
										<a class="collapse-link pull-right">
											<i class="fa fa-chevron-up"></i>
										</a>
									</div>
									<div class="ibox-content">
										<div class="form-group">
											<div class="col-lg-6 border-bottom ">
												<label class="control-label text-danger">
													Información general del equipo
												</label>
											</div>
										</div>

										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">
													Tipo equipo <span class="text-danger">*</span>
												</label>

												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="IdTipoEquipoPropiedad" id="IdTipoEquipoPropiedad"
													class="form-control select2" required>
													<option value="" disabled selected>Seleccione...</option>

													<?php while ($row_TipoEquipo = sqlsrv_fetch_array($SQL_TipoEquipo)) { ?>
														<option value="<?php echo $row_TipoEquipo['id_tipo_equipo']; ?>"
															<?php if ($row_TipoEquipo['id_tipo_equipo'] == $id_tipo_equipo) {
																echo "selected";
															} ?>>
															<?php echo $row_TipoEquipo['tipo_equipo']; ?>
														</option>
													<?php } ?>
												</select>
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Unidad de medida <span class="text-danger">*</span>
												</label>

												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="IdUnidadMedidaEquipo" id="IdUnidadMedidaEquipo"
													class="form-control select2" required>
													<option value="" disabled selected>Seleccione...</option>

													<?php while ($row_UnidadMedida = sqlsrv_fetch_array($SQL_UnidadMedida)) { ?>
														<option
															value="<?php echo $row_UnidadMedida['id_unidad_medida_equipo']; ?>"
															<?php if ((isset($row['IdUnidadMedidaEquipo'])) && ($row_UnidadMedida['id_unidad_medida_equipo'] == $row['IdUnidadMedidaEquipo'])) {
																echo "selected";
															} ?>>
															<?php echo $row_UnidadMedida['unidad_medida_equipo']; ?>
														</option>
													<?php } ?>
												</select>
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Fecha Operación <span class="text-danger">*</span>
												</label>

												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span>
													<input autocomplete="off" <?php if (!PermitirFuncion(1602)) {
														echo "readonly";
													} ?> name="FechaOperacionEquipo" id="FechaOperacionEquipo"
														type="text" class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['FechaOperacionEquipo'])) {
															echo date_format($row['FechaOperacionEquipo'], 'Y-m-d');
														} // else { echo 'AAAA-mm-dd'; }   ?>" required>
												</div>
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Marca del equipo <span class="text-danger">*</span>
												</label>
												
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_IdMarca" class="form-control select2" required
													id="CDU_IdMarca">
													<option value="" disabled selected>Seleccione...</option>
													<?php while ($row_MarcaVehiculo = sqlsrv_fetch_array($SQL_MarcaVehiculo)) { ?>
														<option value="<?php echo $row_MarcaVehiculo['IdMarcaVehiculo']; ?>"
															<?php if ((isset($row['CDU_IdMarca'])) && (strcmp($row_MarcaVehiculo['IdMarcaVehiculo'], $row['CDU_IdMarca']) == 0)) {
																echo "selected";
															} ?>>
															<?php echo $row_MarcaVehiculo['DeMarcaVehiculo']; ?>
														</option>
													<?php } ?>
												</select>
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Línea del equipo <span class="text-danger">*</span>
												</label>
												
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_IdLinea" class="form-control select2" required
													id="CDU_IdLinea">
													<option value="" disabled selected>Seleccione...</option>
													<?php while ($row_LineaVehiculo = sqlsrv_fetch_array($SQL_LineaVehiculo)) { ?>
														<option
															value="<?php echo $row_LineaVehiculo['IdLineaModeloVehiculo']; ?>"
															<?php if ((isset($row['CDU_IdLinea'])) && (strcmp($row_LineaVehiculo['IdLineaModeloVehiculo'], $row['CDU_IdLinea']) == 0)) {
																echo "selected";
															} ?>>
															<?php echo $row_LineaVehiculo['DeLineaModeloVehiculo']; ?>
														</option>
													<?php } ?>
												</select>
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Contador / Horómetro
												</label>

												<input readonly
													autocomplete="off" name="ContadorHorometro" id="ContadorHorometro"
													type="number" class="form-control"
													value="<?php echo $row['ContadorHorometro'] ?? ""; ?>">
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Fabricante del equipo <span class="text-danger">*</span>
												</label>
												
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?> id="IdFabricante"
													name="IdFabricante" class="form-control select2" required>
													<option value="" disabled selected>Seleccione...</option>
													
													<?php while ($row_Fabricante = sqlsrv_fetch_array($SQL_Fabricante)) { ?>
														<option
															value="<?php echo $row_Fabricante['IdFabricante']; ?>"
															<?php if (isset($row['IdFabricante']) && ($row_Fabricante['IdFabricante'] ==  $row['IdFabricante'])) {
																echo "selected";
															} ?>>
															<?php echo $row_Fabricante['Fabricante']; ?>
														</option>
													<?php } ?>
												</select>
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Año del equipo <span class="text-danger">*</span>
												</label>
												
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_Ano" class="form-control select2" required
													id="CDU_Ano">
													<option value="" disabled selected>Seleccione...</option>
													<?php while ($row_ModeloVehiculo = sqlsrv_fetch_array($SQL_ModeloVehiculo)) { ?>
														<option
															value="<?php echo $row_ModeloVehiculo['CodigoModeloVehiculo']; ?>"
															<?php if (isset($row['CDU_Ano']) && ((strcmp($row_ModeloVehiculo['CodigoModeloVehiculo'], $row['CDU_Ano']) == 0) || (strcmp($row_ModeloVehiculo['AñoModeloVehiculo'], $row['CDU_Ano']) == 0))) {
																echo "selected";
															} ?>>
															<?php echo $row_ModeloVehiculo['AñoModeloVehiculo']; ?>
														</option>
													<?php } ?>
												</select>
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Activo Fijo <span class="text-danger">*</span>
												</label>
												
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="id_articulo_activo_fijo " class="form-control select2" required
													id="id_articulo_activo_fijo ">
													<option value="" disabled selected>Seleccione...</option>
													
													<?php while ($row_ActivoFijo = sqlsrv_fetch_array($SQL_ActivoFijo)) { ?>
														<option value="<?php echo $row_ActivoFijo['IdActivoFijo']; ?>"
															<?php if (isset($row['id_articulo_activo_fijo ']) && ($row['id_articulo_activo_fijo '] == $row_ActivoFijo['IdActivoFijo'])) {
																echo "selected";
															} ?>>
															<?php echo $row_ActivoFijo['DeActivoFijo']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
											<!-- /#id_articulo_activo_fijo -->
										</div>

										<!-- Jerarquías dinámicas, SMM 22/02/2024 -->
										<div class="form-group">
											<div class="col-lg-6 border-bottom ">
												<label class="control-label text-danger">Información de
													jerarquías</label>
											</div>
										</div>
										<div class="form-group">
											<?php foreach ($array_Jerarquias as &$dimJ) { ?>
												<div class="col-lg-4">
													<label class="control-label">
														<?php echo $dimJ['dimension_jerarquia']; ?> <span
															class="text-danger">*</span>
													</label>

													<?php $DimJCode = intval($dimJ['id_dimension_jerarquia'] ?? 0); ?>
													<select name="IdJerarquia<?php echo $DimJCode; ?>"
														id="IdJerarquia<?php echo $DimJCode; ?>"
														class="form-control select2" <?php if (!PermitirFuncion(1602)) {
															echo "disabled";
														} ?> required>
														<option value="">Seleccione...</option>

														<?php $SQL_DimJ = Seleccionar("tbl_TarjetaEquipo_Jerarquias", "*", "id_dimension_jerarquia = $DimJCode"); ?>
														<?php while ($row_DimJ = sqlsrv_fetch_array($SQL_DimJ)) { ?>
															<option value="<?php echo $row_DimJ['id_jerarquia']; ?>" <?php if ((isset($row["IdJerarquia$DimJCode"]) && ($row["IdJerarquia$DimJCode"] != "")) && ($row_DimJ['id_jerarquia'] == $row["IdJerarquia$DimJCode"])) {
																   echo "selected";
															   } ?>>
																<?php echo $row_DimJ['jerarquia']; ?>
															</option>
														<?php } ?>
													</select>
												</div>
											<?php } ?>

											<div class="col-lg-4">
												<label class="control-label">
													Ubicación <span class="text-danger">*</span>
												</label>

												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="IdUbicacion" id="IdUbicacion"
													class="form-control select2" required>
													<option value="" disabled selected>Seleccione...</option>

													<?php while ($row_UbicacionEquipo = sqlsrv_fetch_array($SQL_UbicacionEquipo)) { ?>
														<option
															value="<?php echo $row_UbicacionEquipo['id_ubicacion_equipo']; ?>"
															<?php if ((isset($row['IdUbicacion'])) && ($row_UbicacionEquipo['id_ubicacion_equipo'] == $row['IdUbicacion'])) {
																echo "selected";
															} ?>>
															<?php echo $row_UbicacionEquipo['ubicacion_equipo']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
										</div>
										<!-- Jerarquías dinámicas, hasta aquí -->

										<!-- Dimensiones dinámicas, SMM 21/02/2024 -->
										<div class="form-group">
											<div class="col-lg-6 border-bottom ">
												<label class="control-label text-danger">Información de
													dimensiones</label>
											</div>
										</div>
										<div class="form-group">
											<?php foreach ($array_Dimensiones as &$dim) { ?>
												<div class="col-lg-4">
													<label class="control-label">
														<?php echo $dim['DescPortalOne']; ?> <span
															class="text-danger">*</span>
													</label>

													<?php $DimCode = intval($dim['DimCode'] ?? 0); ?>
													<select name="IdDimension<?php echo $DimCode; ?>"
														id="IdDimension<?php echo $DimCode; ?>"
														class="form-control select2" <?php if (!PermitirFuncion(1602)) {
															echo "disabled";
														} ?> required>
														<option value="">Seleccione...</option>

														<?php $SQL_Dim = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', 'DimCode=' . $dim['DimCode']); ?>
														<?php while ($row_Dim = sqlsrv_fetch_array($SQL_Dim)) { ?>

															<option value="<?php echo $row_Dim['OcrCode']; ?>" <?php if ((isset($row["IdDimension$DimCode"]) && ($row["IdDimension$DimCode"] != "")) && ($row_Dim['OcrCode'] == $row["IdDimension$DimCode"])) {
																   echo "selected";
															   } ?>>
																<?php echo $row_Dim['OcrName']; ?>
															</option>
														<?php } ?>
													</select>
												</div>
											<?php } ?>

											<div class="col-lg-4">
												<label class="control-label">
													Proyecto <span class="text-danger">*</span>
												</label>

												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="IdProyecto" id="IdProyecto" class="form-control select2"
													required>
													<option value="" disabled selected>Seleccione...</option>

													<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
														<option value="<?php echo $row_Proyecto['IdProyecto']; ?>" <?php if ((isset($row['IdProyecto'])) && ($row_Proyecto['IdProyecto'] == $row['IdProyecto'])) {
															   echo "selected";
														   } ?>>
															<?php echo $row_Proyecto['DeProyecto']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
										</div>
										<!-- Dimensiones dinámicas, hasta aquí -->
									</div>
								</div>
								<!-- FIN, Info. Mantenimiento -->

								<!-- INICIO, información del vehículo y de la cita -->
								<div class="ibox" style="<?php if(!PermitirFuncion(1606)) { echo "display: none"; } ?>">
									<div class="ibox-title bg-success">
										<h5 class="collapse-link"><i class="fa fa-info-circle"></i> Información del
											vehículo y del mantenimiento</h5>
										<a class="collapse-link pull-right">
											<i class="fa fa-chevron-up"></i>
										</a>
									</div>
									<div class="ibox-content">

										<!-- Agregado por Stiven Muñoz Murillo -->
										<div class="form-group">
											<div class="col-lg-6 border-bottom ">
												<label class="control-label text-danger">Información base del
													vehículo</label>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">No_Motor <span
														class="text-danger">*</span></label>
												<input <?php if (!PermitirFuncion(1602)) {
													echo "readonly";
												} ?>
													autocomplete="off" name="CDU_No_Motor" required
													type="text" class="form-control" id="CDU_No_Motor" maxlength="100"
													value="<?php if (isset($row['CDU_No_Motor'])) {
														echo $row['CDU_No_Motor'];
													} ?>">
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Concesionario <span
														class="text-danger">*</span></label>
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_Concesionario" class="form-control select2"
													required id="CDU_Concesionario">
													<?php while ($row_Concesionario = sqlsrv_fetch_array($SQL_Concesionario)) { ?>
														<option
															value="<?php echo $row_Concesionario['NombreConcesionario']; ?>"
															<?php if (isset($row['CDU_Concesionario']) && (strcmp($row_Concesionario['NombreConcesionario'], $row['CDU_Concesionario']) == 0)) {
																echo "selected";
															} elseif (($edit == 0) && (strcmp($row_Concesionario['NombreConcesionario'], ObtenerValorDefecto(176, "IdConcesionario", false)) == 0)) {
																echo "selected";
															} elseif (($edit == 0) && (ObtenerValorDefecto(176, "IdConcesionario", false) == "") && (strcmp($row_Concesionario['NombreConcesionario'], "Otro") == 0)) {
																echo "selected";
															} ?>>

															<?php echo $row_Concesionario['NombreConcesionario']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Color <span
														class="text-danger">*</span></label>
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_Color" class="form-control select2" required
													id="CDU_Color">
													<option value="" disabled selected>Seleccione...</option>
													<?php while ($row_ColorVehiculo = sqlsrv_fetch_array($SQL_ColorVehiculo)) { ?>
														<option
															value="<?php echo $row_ColorVehiculo['CodigoColorVehiculo']; ?>"
															<?php if (isset($row['CDU_Color']) && (strcmp($row_ColorVehiculo['CodigoColorVehiculo'], $row['CDU_Color']) == 0)) {
																echo "selected";
															} ?>>
															<?php echo $row_ColorVehiculo['NombreColorVehiculo']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Tipo vehículo</label>
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_IdTipoVehiculo" class="form-control select2"
													id="CDU_IdTipoVehiculo">
													<option value="" disabled selected>Seleccione...</option>
													<?php while ($row_TipoVehiculo = sqlsrv_fetch_array($SQL_TipoVehiculo)) { ?>
														<option value="<?php echo $row_TipoVehiculo['IdTipoVehiculo']; ?>"
															<?php if (isset($row['CDU_IdTipoVehiculo']) && (strcmp($row_TipoVehiculo['IdTipoVehiculo'], $row['CDU_IdTipoVehiculo']) == 0)) {
																echo "selected";
															} ?>>
															<?php echo $row_TipoVehiculo['DeTipoVehiculo']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Cilindraje <span
														class="text-danger">*</span></label>
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_Cilindraje" class="form-control select2"
													required id="CDU_Cilindraje">
													<option value="" disabled selected>Seleccione...</option>
													<?php while ($row_Cilindraje = sqlsrv_fetch_array($SQL_CilindrajeVehiculo)) { ?>
														<option
															value="<?php echo $row_Cilindraje['DescripcionCilindraje']; ?>"
															<?php if (isset($row['CDU_Cilindraje']) && (strcmp($row_Cilindraje['DescripcionCilindraje'], $row['CDU_Cilindraje']) == 0)) {
																echo "selected";
															} ?>>
															<?php echo $row_Cilindraje['DescripcionCilindraje']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Tipo servicio <span
														class="text-danger">*</span></label>
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_TipoServicio" class="form-control select2"
													required id="CDU_TipoServicio">
													<option value="" disabled selected>Seleccione...</option>
													<?php while ($row_TipoServicio = sqlsrv_fetch_array($SQL_TipoServicio)) { ?>
														<option
															value="<?php echo $row_TipoServicio['NombreTipoServicio']; ?>"
															<?php if (isset($row['CDU_TipoServicio']) && (strcmp($row_TipoServicio['NombreTipoServicio'], $row['CDU_TipoServicio']) == 0)) {
																echo "selected";
															} ?>>
															<?php echo $row_TipoServicio['NombreTipoServicio']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Tipo rin</label>
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_IdTipoRin" class="form-control select2"
													id="CDU_IdTipoRin">
													<option value="" disabled selected>Seleccione...</option>
													<?php while ($row_TipoRin = sqlsrv_fetch_array($SQL_TipoRines)) { ?>
														<option value="<?php echo $row_TipoRin['IdTipoRines']; ?>" <?php if (isset($row['CDU_IdTipoRin']) && (strcmp($row_TipoRin['IdTipoRines'], $row['CDU_IdTipoRin']) == 0)) {
															   echo "selected";
														   } ?>>
															<?php echo $row_TipoRin['DeTipoRines']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4 border-bottom ">
												<label class="control-label text-danger">Información cronológica del
													vehículo</label>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Fecha Matricula</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														<?php if (!PermitirFuncion(1602)) {
															echo "readonly";
														} ?>
														name="CDU_FechaMatricula" id="CDU_FechaMatricula" type="text"
														class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaMatricula'])) {
															echo date_format($row['CDU_FechaMatricula'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Fecha SOAT</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														<?php if (!PermitirFuncion(1602)) {
															echo "readonly";
														} ?>
														name="CDU_Fecha_SOAT" id="CDU_Fecha_SOAT" type="text"
														class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_Fecha_SOAT'])) {
															echo date_format($row['CDU_Fecha_SOAT'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Fecha Tecnicomecanica</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														<?php if (!PermitirFuncion(1602)) {
															echo "readonly";
														} ?>
														name="CDU_Fecha_Tecno" id="CDU_Fecha_Tecno" type="text"
														class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_Fecha_Tecno'])) {
															echo date_format($row['CDU_Fecha_Tecno'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Fecha Ult. Cambio de Aceite</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														<?php if (!PermitirFuncion(1602)) {
															echo "readonly";
														} ?>
														name="CDU_FechaUlt_CambAceite" id="CDU_FechaUlt_CambAceite"
														type="text" class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaUlt_CambAceite'])) {
															echo date_format($row['CDU_FechaUlt_CambAceite'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Fecha Prox. Cambio de Aceite</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														readonly name="CDU_FechaProx_CambAceite"
														id="CDU_FechaProx_CambAceite" type="text" class="form-control"
														placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaProx_CambAceite'])) {
															echo date_format($row['CDU_FechaProx_CambAceite'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Fecha Ult. Mantenimiento</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														<?php if (!PermitirFuncion(1602)) {
															echo "readonly";
														} ?>
														name="CDU_FechaUlt_Mant" id="CDU_FechaUlt_Mant" type="text"
														class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaUlt_Mant'])) {
															echo date_format($row['CDU_FechaUlt_Mant'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Fecha Prox. Mantenimiento</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														readonly name="CDU_FechaProx_Mant" id="CDU_FechaProx_Mant"
														type="text" class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaProx_Mant'])) {
															echo date_format($row['CDU_FechaProx_Mant'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Fecha Ult. Cambio de Llantas</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														<?php if (!PermitirFuncion(1602)) {
															echo "readonly";
														} ?>
														name="CDU_FechaUlt_CambLlantas" id="CDU_FechaUlt_CambLlantas"
														type="text" class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaUlt_CambLlantas'])) {
															echo date_format($row['CDU_FechaUlt_CambLlantas'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Fecha Prox. Cambio de Llantas</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														readonly name="CDU_FechaProx_CambLlantas"
														id="CDU_FechaProx_CambLlantas" type="text" class="form-control"
														placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaProx_CambLlantas'])) {
															echo date_format($row['CDU_FechaProx_CambLlantas'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Fecha Ult. Alineación y Balanceo</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														<?php if (!PermitirFuncion(1602)) {
															echo "readonly";
														} ?>
														name="CDU_FechaUlt_AlinBalan" id="CDU_FechaUlt_AlinBalan"
														type="text" class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaUlt_AlinBalan'])) {
															echo date_format($row['CDU_FechaUlt_AlinBalan'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Fecha Prox. Alineación y Balanceo</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														readonly name="CDU_FechaProx_AlinBalan"
														id="CDU_FechaProx_AlinBalan" type="text" class="form-control"
														placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaProx_AlinBalan'])) {
															echo date_format($row['CDU_FechaProx_AlinBalan'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Fecha Factura</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														readonly name="CDU_FechaFactura" id="CDU_FechaFactura"
														type="text" class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaFactura'])) {
															echo date_format($row['CDU_FechaFactura'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
											<div class="col-lg-4">
												<label class="control-label">Novedad</label>
												<select <?php if (!PermitirFuncion(1602)) {
													echo "disabled";
												} ?>
													name="CDU_Novedad" id="CDU_Novedad" class="form-control select2">
													<option value="" disabled selected>Seleccione...</option>
													<?php while ($row_Novedad = sqlsrv_fetch_array($SQL_Novedades)) { ?>
														<option value="<?php echo $row_Novedad['IdNovedad']; ?>" <?php if ((isset($row['CDU_Novedad'])) && (strcmp($row_Novedad['IdNovedad'], $row['CDU_Novedad']) == 0)) {
															   echo "selected";
														   } ?>>
															<?php echo $row_Novedad['DeNovedad']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Fecha Agenda</label>
												<div class="input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input autocomplete="off"
														readonly name="CDU_FechaAgenda" id="CDU_FechaAgenda" type="text"
														class="form-control" placeholder="YYYY-MM-DD" value="<?php if (isset($row['CDU_FechaAgenda'])) {
															echo date_format($row['CDU_FechaAgenda'], 'Y-m-d');
														} //else {echo 'AAAA-mm-dd';}  ?>">
												</div>
											</div>
											<div class="form-group">
												<div class="col-lg-4">
													<label class="control-label">Sede Venta</label>
													<input readonly name="CDU_SedeVenta" id="CDU_SedeVenta"
														autocomplete="off" maxlength="100" type="text"
														class="form-control" value="<?php if (isset($row['CDU_SedeVenta'])) {
															echo $row['CDU_SedeVenta'];
														} ?>">
												</div>
											</div>
										</div>
										<!-- Agregado, hasta aquí -->
									</div>
								</div>
								<!-- FIN, información del vehículo y de la cita -->
								
								<!-- Inicio, TABS -->
								<div class="tabs-container">
									<ul class="nav nav-tabs">
										<li class="active" id="nav-address">
											<a data-toggle="tab" href="#tab-address">
												<i class="fa fa-address-book-o"></i> Dirección
											</a>
										</li>

										<li id="nav-service-calls">
											<a data-toggle="tab" href="#tab-service-calls">
												<i class="fa fa-table"></i> Llamadas de servicio
											</a>
										</li>
										
										<li id="nav-2">
											<a data-toggle="tab" href="#tab-2">
												<i class="fa fa-table"></i> Solicitud Llamada (Agenda)
											</a>
										</li>
										
										<li id="nav-3">
											<a data-toggle="tab" href="#tab-3">
												<i class="fa fa-table"></i> Recepción / Entrega
											</a>
										</li>
										
										<li id="nav-4">
											<a data-toggle="tab" href="#tab-4">
												<i class="fa fa-table"></i> Campañas
											</a>
										</li>
										
										<li id="nav-service-contracts">
											<a data-toggle="tab" href="#tab-service-contracts">
												<i class="fa fa-table"></i> Contratos de servicio
											</a>
										</li>

										<li id="nav-1">
											<a data-toggle="tab" href="#tab-sales-data">
												<i class="fa fa-table"></i> Datos de ventas
											</a>
										</li>
										
										<li id="nav-crm">
											<a data-toggle="tab" href="#tab-crm">
												<i class="fa fa-suitcase"></i> Gestión de CRM
											</a>
										</li>

										<li id="nav-components" style="<?php if($edit != 1) { echo "display: none"; } ?>">
											<a data-toggle="tab" href="#tab-components">
												<i class="fa fa-cogs"></i> Componentes
											</a>
										</li>

										<li id="nav-properties" style="<?php if($edit != 1) { echo "display: none"; } ?>">
											<a data-toggle="tab" href="#tab-properties">
												<i class="fa fa-cogs"></i> Propiedades
											</a>
										</li>

										<li>
											<a data-toggle="tab" href="#tab-annexes">
												<i class="fa fa-paperclip"></i> Anexos
											</a>
										</li>
									</ul>

									<div class="tab-content">
										<!-- Direcciones -->
										<div id="tab-address" class="tab-pane active">
											<div class="row">
												<div class="ibox-content">
													<div class="col-lg-6">
														<div class="form-group">
															<div class="col-lg-10 border-bottom m-r-sm">
																<label class="control-label text-danger">
																	Información del contacto en sitio
																</label>
															</div>
														</div>

														<div class="form-group">
															<div class="col-lg-6">
																<label class="control-label">Calle</label>

																<input <?php if (!PermitirFuncion(1602)) {
																	echo "readonly";
																} ?> autocomplete="off" name="Calle" type="text"
																	required class="form-control" id="Calle"
																	maxlength="150" value="<?php if (isset($row['Calle'])) {
																		echo $row['Calle'];
																	} ?>">
															</div>
															
															<div class="col-lg-6">
																<label class="control-label">Código postal</label>

																<input <?php if (!PermitirFuncion(1602)) {
																	echo "readonly";
																} ?> autocomplete="off" name="CodigoPostal" type="text"
																	required class="form-control"
																	id="CodigoPostal" maxlength="150" value="<?php if (isset($row['CodigoPostal'])) {
																		echo $row['CodigoPostal'];
																	} ?>">
															</div>
														</div>

														<div class="form-group">
															<div class="col-lg-6">
																<label class="control-label">Ciudad</label>
																
																<input <?php if (!PermitirFuncion(1602)) {
																	echo "readonly";
																} ?> autocomplete="off" name="Ciudad" type="text"
																	required class="form-control" id="Ciudad"
																	maxlength="150" value="<?php if (isset($row['Ciudad'])) {
																		echo $row['Ciudad'];
																	} ?>">
															</div>

															<div class="col-lg-6">
																<label class="control-label">Distrito</label>

																<input <?php if (!PermitirFuncion(1602)) {
																	echo "readonly";
																} ?> autocomplete="off" name="Distrito" type="text"
																	required class="form-control" id="Distrito"
																	maxlength="150" value="<?php if (isset($row['Distrito'])) {
																		echo $row['Distrito'];
																	} ?>">
															</div>
														</div>

														<div class="form-group">
															<input <?php if (!PermitirFuncion(1602)) {
																echo "readonly";
															} ?>
																type="hidden" name="EstadoPais" id="EstadoPais" value="<?php if (isset($row['EstadoPais'])) {
																	echo $row['EstadoPais'];
																} ?>" />
															
															<div class="col-lg-6">
																<label class="control-label">País</label>

																<select <?php if (!PermitirFuncion(1602)) {
																	echo "disabled";
																} ?> name="Pais" class="form-control" id="Pais"
																	required>
																	<option value="">(Ninguno)</option>
																	<option value="CO" <?php if ((isset($row['Pais'])) && (strcmp("CO", $row['Pais']) == 0)) {
																		echo "selected";
																	} ?>>Colombia</option>
																</select>
															</div>
														</div>
													</div>
													<!-- /.col-lg-6 -->

													<div class="col-lg-6">
														<div class="form-group">
															<div class="col-lg-10 border-bottom m-r-sm">
																<label class="control-label text-danger">Ubicación GPS</label>
															</div>
														</div>
															
														<div class="form-group">
															<div class="col-lg-12">
																<?php $map_lat = $row["LatitudGPSUbicacion"] ?? ""; ?>
																<input type="hidden" id="latGPS" name="latGPS" value="<?php echo $map_lat; ?>">
																
																<?php $map_lng = $row["LongitudGPSUbicacion"] ?? ""; ?>
																<input type="hidden" id="lngGPS" name="lngGPS" value="<?php echo $map_lng; ?>">

																<p>
																	<?php $map_coord = (($map_lat != "") && ($map_lng != "") ? "$map_lat, $map_lng" : "Sin seleccionar"); ?>
																	<b><i class="fa fa-map-marker"></i> Coordenadas GPS: </b><span id="coordGPS"><?php echo $map_coord; ?></span>
																</p>
															</div>
														</div>

														<div class="form-group">
															<div class="col-lg-12">
																<?php $map_params = (($map_lat != "") && ($map_lng != "") ? "lat=$map_lat&lng=$map_lng" : ""); ?>
																<iframe id="mapFrame" width="100%" height="420" src="maps_coordenadas.php?<?php echo $map_params; ?>" frameborder="0"></iframe>
															</div>
														</div>
													</div>
													<!-- /.col-lg-6 -->
												</div>
											</div>
										</div>

										<!-- Llamadas de servicio -->
										<div id="tab-service-calls" class="tab-pane">
											<!-- Table Llamadas de servicio -->
											<div class="row">
												<div class="col-12 text-center">
													<div class="ibox-content">
														<?php
														$hasRowsLlamadaServicio = (isset($SQL_LlamadasServicio)) ? sqlsrv_has_rows($SQL_LlamadasServicio) : false;
														if ($edit == 1 && $hasRowsLlamadaServicio === true) { ?>
															<div class="table" style="max-height: 230px; overflow-y: auto;">
																<table
																	class="table table-striped table-bordered table-hover dataTables-example">
																	<thead>
																		<tr>
																			<th>Número de llamada</th>
																			<th>Estado</th>
																			<th>Artículos/Costos</th>
																			<th>Fecha de apertura llamada</th>
																			<th>Origen</th> <!-- SMM, 03/09/2022 -->
																			<th>Tipo Problema</th>
																			<th>Subtipo Problema</th>
																			<!-- SMM, 03/09/2022 -->
																			<th>Asunto</th>
																			<th>Número de artículo</th>
																			<th>Número de serie</th>
																			<th>Nombre del cliente</th>
																		</tr>
																	</thead>
																	<tbody>
																		<?php
																		while ($row_LlamadaServicio = sqlsrv_fetch_array($SQL_LlamadasServicio)) { ?>
																			<tr class="gradeX">
																				<td class="text-left">
																					<a href="llamada_servicio.php?id=<?php echo base64_encode($row_LlamadaServicio['ID_LlamadaServicio']); ?>&tl=1&pag=<?php echo base64_encode('gestionar_llamadas_servicios.php'); ?>"
																						class="alkin btn btn-success btn-xs"><i
																							class="fa fa-folder-open-o"></i>
																						<?php echo $row_LlamadaServicio['DocNum']; ?>
																					</a>
																				</td>

																				<td>
																					<span <?php if ($row_LlamadaServicio['IdEstadoLlamada'] == '-3') {
																						echo "class='label label-info'";
																					} elseif ($row_LlamadaServicio['IdEstadoLlamada'] == '-2') {
																						echo "class='label label-warning'";
																					} else {
																						echo "class='label label-danger'";
																					} ?>>
																						<?php echo $row_LlamadaServicio['DeEstadoLlamada']; ?>
																					</span>
																				</td>

																				<td>
																					<a class="btn btn-primary btn-xs"
																						id="btnPreCostos" name="btnPreCostos"
																						onClick="MostrarCostos('<?php echo $row_LlamadaServicio['ID_LlamadaServicio']; ?>');"><i
																							class="fa fa-money"></i> Precios</a>
																					<a style="margin: 5px;"
																						class="btn btn-success btn-xs"
																						id="btnPreAutorizados"
																						name="btnPreAutorizados"
																						onClick="MostrarAutorizados('<?php echo $row_LlamadaServicio['ID_LlamadaServicio']; ?>');"><i
																							class="fa fa-wrench"></i>
																						Autorizaciones</a>
																				</td>
																				<td>
																					<?php echo $row_LlamadaServicio['FechaHoraCreacionLLamada']->format('Y-m-d H:i:s'); ?>
																				</td>

																				<td>
																					<?php echo $row_LlamadaServicio["DeOrigenLlamada"]; ?>
																				</td>

																				<td>
																					<?php echo $row_LlamadaServicio['DeTipoProblemaLlamada']; ?>
																				</td>

																				<td>
																					<?php echo $row_LlamadaServicio["DeSubTipoProblemaLlamada"]; ?>
																				</td>

																				<td>
																					<?php echo $row_LlamadaServicio['AsuntoLlamada']; ?>
																				</td>
																				<td>
																					<?php echo $row_LlamadaServicio['ItemCode']; ?>
																				</td>
																				<td>
																					<?php echo $row_LlamadaServicio['SerialFabricante']; ?>
																				</td>
																				<td>
																					<?php echo $row_LlamadaServicio['CardName']; ?>
																				</td>

																			</tr>
																		<?php } ?>
																	</tbody>
																</table>
															</div>
														<?php } else { ?>
															<i class="fa fa-search"
																style="font-size: 18px; color: lightgray;"></i>
															<span style="font-size: 13px; color: lightgray;">No hay
																registros de llamadas de servicio</span>
														<?php } ?>
													</div>
												</div>
											</div>
											<!-- End Table Llamadas de servicio -->
										</div>
										<!-- End Llamadas de servicio -->

										<!-- Agenda -->
										<div id="tab-2" class="tab-pane">
											<!-- Table Agenda -->
											<div class="row">
												<div class="col-12 text-center">
													<div class="ibox-content">
														<?php
														$hasRowsAgenda = (isset($SQL_Agenda)) ? sqlsrv_has_rows($SQL_Agenda) : false;
														if ($edit == 1 && $hasRowsAgenda === true) { ?>
															<div class="table" style="max-height: 230px; overflow-y: auto;">
																<table
																	class="table table-striped table-bordered table-hover dataTables-example">
																	<thead>
																		<tr>
																			<th>Número</th>
																			<th>Estado Doc.</th>
																			<th>Estado Servicio</th>
																			<th>Fecha Inicio Solicitud</th>
																			<th>Origen</th>
																			<th>Tipo Problema</th>
																			<th>Subtipo Problema</th>
																			<th>Asunto</th>
																			<th>Número Artículo</th>
																			<th>Nombre Cliente</th>
																		</tr>
																	</thead>
																	<tbody>
																		<?php
																		while ($row_Agenda = sqlsrv_fetch_array($SQL_Agenda)) { ?>
																			<tr class="gradeX">
																				<td class="text-left">
																					<a href="solicitud_llamada.php?id=<?php echo base64_encode($row_Agenda["ID_SolicitudLlamadaServicio"]); ?>&tl=1&pag=<?php echo base64_encode('gestionar_solicitudes_llamadas.php'); ?>"
																						class="alkin btn btn-success btn-xs">
																						<i class="fa fa-folder-open-o"></i>
																						<?php echo $row_Agenda["ID_SolicitudLlamadaServicio"]; ?>
																					</a>
																				</td>

																				<td>
																					<span class="label <?php if ($row_Agenda["IdEstadoSolicitudLlamada"] == -3) {
																						echo "label-primary";
																					} else {
																						echo "label-danger";
																					} ?>">
																						<?php echo $row_Agenda["NombreEstado"] ?? ""; ?>
																					</span>
																				</td>

																				<td>
																					<span class="label"
																						style="color: white; background-color: <?php echo $row_Agenda["ColorEstadoServicioLlamada"] ?? ""; ?>;">
																						<?php echo $row_Agenda["DeEstadoServicio"] ?? ""; ?>
																					</span>
																				</td>

																				<td>
																					<?php echo $row_Agenda["FechaHoraInicioSolicitud"]->format('Y-m-d H:i:s'); ?>
																				</td>

																				<td>
																					<?php echo $row_Agenda["DeOrigenSolicitudLlamada"] ?? ""; ?>
																				</td>

																				<td>
																					<?php echo $row_Agenda["DeTipoProblemaSolicitudLlamada"] ?? ""; ?>
																				</td>

																				<td>
																					<?php echo $row_Agenda["DeSubTipoProblemaSolicitudLlamada"] ?? ""; ?>
																				</td>

																				<td>
																					<?php echo $row_Agenda["AsuntoSolicitudLlamada"] ?? ""; ?>
																				</td>

																				<td>
																					<?php echo $row_Agenda["ItemCode"] ?? ""; ?>
																				</td>

																				<td>
																					<?php echo $row_Agenda["NombreCliente"] ?? ""; ?>
																				</td>
																			</tr>
																		<?php } ?>
																	</tbody>
																</table>
															</div>
														<?php } else { ?>
															<i class="fa fa-search"
																style="font-size: 18px; color: lightgray;"></i>
															<span style="font-size: 13px; color: lightgray;">No hay
																registros de llamadas de servicio</span>
														<?php } ?>
													</div>
												</div>
											</div>
											<!-- End Table Agenda -->
										</div>
										<!-- End Agenda -->

										<!-- Formularios -->
										<div id="tab-3" class="tab-pane">
											<!-- Table Formularios -->
											<div class="row">
												<div class="col-12 text-center">
													<div class="ibox-content">
														<?php
														$hasRowsFormularios = ($SQL_Formularios) ? sqlsrv_has_rows($SQL_Formularios) : false;
														if ($edit == 1 && $hasRowsFormularios) { ?>
															<div class="table" style="max-height: 230px; overflow-y: auto;">
																<table
																	class="table table-striped table-bordered table-hover dataTables-example">
																	<thead>
																		<tr>
																			<th>Tipo Documento</th>
																			<th>No de Documento</th>
																			<th>Fecha Creacion Reg.</th>
																			<th>Fecha Recepcion/Entrega</th>
																			<th>Observaciones</th>
																			<th>Asesor Servicio</th>

																			<th>No Llamada Servicio</th>

																			<th>Origen</th>
																			<th>Tipo Problema</th>
																			<th>SubTipo Problema</th>

																			<th>Acciones</th>
																		</tr>
																	</thead>
																	<tbody>
																		<?php
																		while ($row_Formulario = sqlsrv_fetch_array($SQL_Formularios)) { ?>
																			<tr class="gradeX">

																				<td>
																					<?php echo $row_Formulario["tipo_objeto"]; ?>
																				</td>
																				<td>
																					<?php echo $row_Formulario['id_formulario']; ?>
																				</td>
																				<td>
																					<?php echo (isset($row_Formulario["hora_creacion"]) && $row_Formulario["hora_creacion"] != "") ? $row_Formulario["hora_creacion"]->format("Y-m-d h:i:s") : ""; ?>
																				</td>
																				<td>
																					<?php echo (isset($row_Formulario["fecha_recepcion_entrega"]) && $row_Formulario["fecha_recepcion_entrega"] != "") ? $row_Formulario['fecha_recepcion_entrega']->format("Y-m-d h:i:s") : ""; ?>
																				</td>
																				<td>
																					<?php echo $row_Formulario['observaciones']; ?>
																				</td>
																				<td>
																					<?php echo $row_Formulario['empleado_tecnico']; ?>
																				</td>

																				<td class="text-left">
																					<a href="llamada_servicio.php?id=<?php echo base64_encode($row_Formulario['docentry_llamada_servicio']); ?>&tl=1&pag=<?php echo base64_encode('gestionar_llamadas_servicios.php'); ?>"
																						class="alkin btn btn-success btn-xs"><i
																							class="fa fa-folder-open-o"></i>
																						<?php echo $row_Formulario['id_llamada_servicio']; ?>
																					</a>
																				</td>

																				<td>
																					<?php echo $row_Formulario['DeOrigenLlamada']; ?>
																				</td>
																				<td>
																					<?php echo $row_Formulario['DeTipoProblemaLlamada']; ?>
																				</td>
																				<td>
																					<?php echo $row_Formulario['DeSubTipoProblemaLlamada']; ?>
																				</td>

																				<td>
																					<a href="filedownload.php?file=<?php echo base64_encode($row_Formulario['nombre_servicio'] . "/DescargarFormatos/" . $row_Formulario['id_formulario'] . "/" . $_SESSION['User']); ?>&api=1"
																						target="_blank"
																						class="btn btn-primary btn-xs"
																						title="Descargar Formato">
																						<i class="fa fa-download"></i> Descargar
																						Formato
																					</a>

																					<a href="sapdownload.php?id=<?php echo base64_encode('15'); ?>&type=<?php echo base64_encode('2'); ?>&DocKey=<?php echo base64_encode($row_Formulario['id_llamada_servicio']); ?>&ObType=<?php echo base64_encode('191'); ?>&IdFrm=<?php echo base64_encode($row_Formulario['IdSerieLlamada']); ?>"
																						target="_blank"
																						class="btn btn-warning btn-xs"
																						title="Descargar Llamada">
																						<i class="fa fa-download"></i> Descargar
																						Llamada
																					</a>
																				</td>
																			</tr>
																		<?php } ?>
																	</tbody>
																</table>
															</div>
														<?php } else { ?>
															<i class="fa fa-search"
																style="font-size: 18px; color: lightgray;"></i>
															<span style="font-size: 13px; color: lightgray;">No hay
																registros de Recepciones o Entregas de Vehiculo</span>
														<?php } ?>
													</div>
												</div>
											</div>
											<!-- End Table Formularios -->
										</div>
										<!-- End Formularios -->

										<!-- Campanas -->
										<div id="tab-4" class="tab-pane">
											<!-- Table Campanas -->
											<div class="row">
												<div class="col-12 text-center">
													<div class="ibox-content">
														<?php
														$hasRowsCampanas = ($SQL_Campanas) ? sqlsrv_has_rows($SQL_Campanas) : false;
														if ($edit == 1 && $hasRowsCampanas) { ?>
															<div class="table" style="max-height: 230px; overflow-y: auto;">
																<table
																	class="table table-striped table-bordered table-hover dataTables-example">
																	<thead>
																		<tr>
																			<th>ID Campaña</th>

																			<th>Campaña</th>
																			<th>VIN</th>

																			<th>Estado VIN Campaña</th>

																			<th>Fecha Límite Vigencia</th>

																			<th>ID Llamada Servicio</th>

																			<th>Origen</th>
																			<th>Estado Llamada</th>
																			<th>SubTipo Problema</th>

																			<th>Nombre Cliente</th>
																			<th>Fecha Cierre</th>

																			<th>Acciones</th>
																		</tr>
																	</thead>
																	<tbody>
																		<?php
																		while ($row_Campana = sqlsrv_fetch_array($SQL_Campanas)) { ?>
																			<tr class="gradeX">
																				<td>
																					<a href="campanas_vehiculo.php?id=<?php echo $row_Campana['id_campana']; ?>&edit=1"
																						class="btn btn-success btn-xs"
																						target="_blank">
																						<i class="fa fa-folder-open-o"></i>
																						<?php echo $row_Campana['id_campana']; ?>
																					</a>
																				</td>

																				<td>
																					<?php echo $row_Campana['campana']; ?>
																				</td>
																				<td>
																					<?php echo $row_Campana['VIN']; ?>
																				</td>

																				<td>
																					<span
																						class="label <?php echo ($row_Campana['estado_VIN_campaña'] == "P") ? "label-warning" : "label-info"; ?>">
																						<?php echo $row_Campana['nombre_estado_VIN_campaña']; ?>
																					</span>
																				</td>

																				<td>
																					<?php echo (isset($row_Campana["fecha_limite_vigencia"]) && $row_Campana["fecha_limite_vigencia"] != "") ? $row_Campana['fecha_limite_vigencia']->format("Y-m-d") : ""; ?>
																				</td>

																				<td class="text-left">
																					<?php if (isset($row_Campana['docnum_llamada_servicio']) && ($row_Campana['docnum_llamada_servicio'] != "")) { ?>
																						<a href="llamada_servicio.php?id=<?php echo base64_encode($row_Campana['docentry_llamada_servicio']); ?>&tl=1&pag=<?php echo base64_encode('gestionar_llamadas_servicios.php'); ?>"
																							class="alkin btn btn-success btn-xs">
																							<i class="fa fa-folder-open-o"></i>
																							<?php echo $row_Campana['docnum_llamada_servicio']; ?>
																						</a>
																					<?php } ?>
																				</td>

																				<td>
																					<?php echo $row_Campana['DeOrigenLlamada']; ?>
																				</td>
																				<td>
																					<?php echo $row_Campana['DeEstadoLlamada']; ?>
																				</td>
																				<td>
																					<?php echo $row_Campana['DeSubTipoProblemaLlamada']; ?>
																				</td>

																				<td>
																					<?php echo $row_Campana['socio_negocios'] ?? ""; ?>
																				</td>
																				<td>
																					<?php echo (isset($row_Campana["FechaCierre"]) && $row_Campana["FechaCierre"] != "") ? $row_Campana['FechaCierre']->format("Y-m-d") : ""; ?>
																				</td>

																				<td>
																					<?php if (isset($row_Campana['docnum_llamada_servicio']) && ($row_Campana['docnum_llamada_servicio'] != "")) { ?>
																						<a href="sapdownload.php?id=<?php echo base64_encode('15'); ?>&type=<?php echo base64_encode('2'); ?>&DocKey=<?php echo base64_encode($row_Campana['docnum_llamada_servicio']); ?>&ObType=<?php echo base64_encode('191'); ?>&IdFrm=<?php echo base64_encode($row_Formulario['IdSerieLlamada']); ?>"
																							target="_blank"
																							class="btn btn-warning btn-xs"
																							title="Descargar Llamada">
																							<i class="fa fa-download"></i> Descargar
																							Llamada
																						</a>
																					<?php } ?>
																				</td>
																			</tr>
																		<?php } ?>
																	</tbody>
																</table>
															</div>
														<?php } else { ?>
															<i class="fa fa-search"
																style="font-size: 18px; color: lightgray;"></i>
															<span style="font-size: 13px; color: lightgray;">No hay
																registros de Campañas de Vehículo</span>
														<?php } ?>
													</div>
												</div>
											</div>
											<!-- End Table Campanas -->
										</div>
										<!-- End Campanas -->

										<!-- Contractos de servicio -->
										<div id="tab-service-contracts" class="tab-pane">
											<!-- Table Contratos de servicios -->
											<div class="row">
												<div class="col-12 text-center">
													<div class="ibox-content">
														<?php
														$hasRowsContratosServicio = (isset($SQL_ContratosServicio)) ? sqlsrv_has_rows($SQL_ContratosServicio) : false;
														if ($edit == 1 && $hasRowsContratosServicio === true) { ?>
															<div class="table-responsive"
																style="max-height: 230px; overflow: hidden; overflow-y: auto;">
																<table
																	class="table table-striped table-bordered table-hover dataTables-example">
																	<thead>
																		<tr>
																			<th>Contrato</th>
																			<th>Fecha de inicio</th>
																			<th>Fecha final</th>
																			<th>Fecha de rescisión del contrato</th>
																			<th>Tipo de contrato</th>
																		</tr>
																	</thead>
																	<tbody>
																		<?php
																		while ($row_ContratoServicio = sqlsrv_fetch_array($SQL_ContratosServicio)) { ?>
																			<tr class="gradeX">
																				<td>
																					<?php echo $row_ContratoServicio['ID_Contrato']; ?>
																				</td>
																				<td>
																					<?php echo $row_ContratoServicio['FechaInicioContrato']; ?>
																				</td>
																				<td>
																					<?php echo $row_ContratoServicio['FechaFinContrato']; ?>
																				</td>
																				<td>
																					<?php echo $row_ContratoServicio['FechaRescisionContrato']; ?>
																				</td>
																				<td>
																					<?php echo $row_ContratoServicio['DeTipoServicio']; ?>
																				</td>
																			</tr>
																		<?php } ?>
																	</tbody>
																</table>
															</div>
														<?php } else { ?>
															<i class="fa fa-search"
																style="font-size: 18px; color: lightgray;"></i>
															<span style="font-size: 13px; color: lightgray;">No hay
																registros de contratos de servicio</span>
														<?php } ?>
													</div>
												</div>
											</div>
											<!-- End Table Contratos de servicio -->
										</div>

										<!-- Datos de ventas -->
										<div id="tab-sales-data" class="tab-pane">
											<div class="row">
												<div class="ibox-content">
													<label style="margin-bottom: 10px; color: darkgray;"><u>Encargado de
															compras</u></label>
													<div class="form-group">
														<label class="col-lg-1 control-label">Código</label>
														<div class="col-lg-3">
															<input readonly autocomplete="off" name="CardCodeCompras"
																type="text" required class="form-control"
																id="CardCodeCompras" maxlength="150" value="<?php if (isset($row['CardCodeCompras'])) {
																	echo $row['CardCodeCompras'];
																} ?>">
														</div>
														<label class="col-lg-1 control-label">Nombre</label>
														<div class="col-lg-3">
															<input readonly autocomplete="off" name="CardNameCompras"
																type="text" required class="form-control"
																id="CardNameCompras" maxlength="150" value="<?php if (isset($row['CardNameCompras'])) {
																	echo $row['CardNameCompras'];
																} ?>">
														</div>
													</div>
													<label style="margin-bottom: 10px; color: darkgray;"><u>Entrega y
															Factura</u></label>
													<div class="form-group">
														<label class="col-lg-1 control-label"><i
																onClick="ConsultarDocVentas(1);"
																title="Consultar documento de ventas"
																style="cursor: pointer"
																class="btn-xs btn-success fa fa-search"></i><br>Entrega</label>
														<div class="col-lg-3">
															<input readonly autocomplete="off" name="DocNumEntrega"
																type="number" required class="form-control"
																id="DocNumEntrega" maxlength="150" value="<?php if (isset($row['DocNumEntrega'])) {
																	echo $row['DocNumEntrega'];
																} ?>">
															<input readonly autocomplete="off" name="DocEntryEntrega2"
																type="hidden" required class="form-control"
																id="DocEntryEntrega2" maxlength="150" value="<?php if (isset($row['DocEntryEntrega'])) {
																	echo $row['DocEntryEntrega'];
																} ?>">
														</div>
														<label class="col-lg-1 control-label"><i
																onClick="ConsultarDocVentas(2);"
																title="Consultar documento de ventas"
																style="cursor: pointer"
																class="btn-xs btn-success fa fa-search"></i><br>Factura</label>
														<div class="col-lg-3">
															<input readonly autocomplete="off" name="DocNumFactura"
																type="number" required class="form-control"
																id="DocNumFactura" maxlength="150" value="<?php if (isset($row['DocNumFactura'])) {
																	echo $row['DocNumFactura'];
																} ?>">
															<input readonly autocomplete="off" name="DocEntryFactura2"
																type="hidden" required class="form-control"
																id="DocEntryFactura2" maxlength="150" value="<?php if (isset($row['DocEntryFactura'])) {
																	echo $row['DocEntryFactura'];
																} ?>">
														</div>
													</div>
												</div>
											</div>
										</div>

										<!-- Inicio Gestión CRM, SMM 01/07/2022 -->
										<div id="tab-crm" class="tab-pane">
											<div class="row">
												<div class="col-lg-12 text-center">
													<?php if (isset($SQL_HistGestion) && sqlsrv_has_rows($SQL_HistGestion)) { ?>
														<div class="table-responsive"
															style="max-height: 230px; overflow: hidden; overflow-y: auto;">
															<table
																class="table table-striped table-bordered table-hover dataTables-example">
																<thead>
																	<tr>
																		<th>Tipo gestión</th>
																		<th>Destino</th>
																		<th>Evento</th>
																		<th>Resultado</th>
																		<th>Comentario</th>
																		<th>Causa no pago</th>
																		<th>Acuerdo de pago</th>
																		<th>Fecha registro</th>
																		<th>Usuario</th>
																		<th>Sucursal</th>
																	</tr>
																</thead>
																<tbody>
																	<?php while ($row_HistGestion = sqlsrv_fetch_array($SQL_HistGestion)) { ?>
																		<?php if (false || (isset($row['SerialInterno']) && ($row_HistGestion['NumeroSerie'] == $row['SerialInterno']))) { ?>
																			<tr class="gradeX">
																				<td>
																					<?php echo $row_HistGestion['TipoGestion']; ?>
																				</td>
																				<td>
																					<?php echo $row_HistGestion['Destino']; ?>
																				</td>
																				<td>
																					<?php echo $row_HistGestion['NombreEvento']; ?>
																				</td>
																				<td>
																					<?php echo $row_HistGestion['ResultadoGestion']; ?>
																				</td>
																				<td>
																					<?php echo $row_HistGestion['Comentarios']; ?>
																				</td>
																				<td>
																					<?php echo $row_HistGestion['CausaNoPago']; ?>
																				</td>
																				<td>
																					<?php if ($row_HistGestion['AcuerdoPago'] == 1) {
																						echo "SI";
																					} else {
																						echo "NO";
																					} ?>
																				</td>
																				<td>
																					<?php echo $row_HistGestion['FechaRegistro']->format('Y-m-d H:i'); ?>
																				</td>
																				<td>
																					<?php echo $row_HistGestion['Usuario']; ?>
																				</td>
																				<td>
																					<?php echo $row_HistGestion['SucursalCliente']; ?>
																				</td>
																			</tr>
																		<?php } ?>
																	<?php } ?>
																</tbody>
															</table>
														</div>
													<?php } else { ?>
														<br>
														<i class="fa fa-search"
															style="font-size: 18px; color: lightgray;"></i>
														<span style="font-size: 13px; color: lightgray;">No hay registros de
															cartera</span>
													<?php } ?>
												</div>
											</div>
										</div>
										<!-- Fin Gestión CRM, SMM 01/07/2022 -->

										<!-- Inicio, Componentes -->
										<div id="tab-components" class="tab-pane" style="<?php if($edit != 1) { echo "display: none"; } ?>">
											<div class="panel-body">
												<div class="row">
													<div class="col-lg-2">
														<button type="button" onclick="AddComponents()" class="alkin btn btn-primary"><i class="fa fa-plus-circle"></i> Agregar Componente</button>
													</div>
													<div class="col-lg-4" style="text-align: right;">
														<button type="button" id="btnExpandir" class="btn btn-info"><i class="fa fa-plus-circle"></i> Expandir</button>
														<button type="button" id="btnContraer" class="btn btn-warning"><i class="fa fa-minus-circle"></i> Comprimir</button>
													</div>

													<div class="col-lg-6" style="text-align: right;">
														<button type="button" onclick="OpenComponent()" class="btn btn-success"><i class="fa fa-plus-circle"></i> Abrir Componente</button>
														<button type="button" onclick="DeleteComponent()" class="btn btn-danger"><i class="fa fa-minus-circle"></i> Eliminar Componente</button>
													</div>
												</div>

												<br>
												<div class="row">
													<div class="col-lg-6">
														<label class="control-label text-danger">
															Árbol de componentes
														</label>
														<?php include_once "jerarquias_componentes.php"; ?>
													</div>

													<div class="col-lg-6">
														<label class="control-label text-danger col-lg-10 border-bottom" style="text-align: left;">
															Información del componente <span id="id_tarjeta_equipo_hijo" style="visibility: hidden;"></span>
														</label>

														<div class="form-group">
															<div class="col-lg-6">
																<label class="control-label">Código Artículo</label>
																<input type="text" class="form-control" id="id_articulo_hijo" value="" readonly>
															</div>
														
															<div class="col-lg-6">
																<label class="control-label">Artículo</label>
																<input type="text" class="form-control" id="articulo_hijo" value="" readonly>
															</div>
														</div>

														<div class="form-group">
															<div class="col-lg-6">
																<label class="control-label">Unidad Medida</label>
																<input type="text" class="form-control" id="unidad_hijo" value="" readonly>
															</div>
														
															<div class="col-lg-6">
																<label class="control-label">Ubicación</label>
																<input type="text" class="form-control" id="ubicacion_hijo" value="" readonly>
															</div>
														</div>

														<div class="form-group">
															<div class="col-lg-6">
																<label class="control-label">Fecha Operación</label>
																<input type="text" class="form-control" id="fecha_operacion_hijo" value="" readonly>
															</div>
														
															<div class="col-lg-6">
																<label class="control-label">Contador/Horómetro</label>
																<input type="text" class="form-control" id="contador_hijo" value="" readonly>
															</div>
														</div>

														<div class="form-group">
															<div class="col-lg-6">
																<label class="control-label">Proyecto</label>
																<input type="text" class="form-control" id="proyecto_hijo" value="" readonly>
															</div>
														
															<div class="col-lg-6">
																<label class="control-label">Estado</label>
																<input type="text" class="form-control" id="estado_hijo" value="" readonly>
															</div>
														</div>
													</div>
												</div>
											</div>
											<!-- /.panel-body -->
										</div>
										<!-- Fin, Componentes -->

										<!-- Inicio, Propiedades -->
										<div id="tab-properties" class="tab-pane" style="<?php if($edit != 1) { echo "display: none"; } ?>">
											<div class="row">
												<div class="ibox-content">
												<?php if (isset($SQL_Propiedades) && $SQL_Propiedades && sqlsrv_has_rows($SQL_Propiedades)) { ?>

													<div class="form-group">
													<?php while ($row_Propiedad = sqlsrv_fetch_array($SQL_Propiedades)) { ?>

														<?php if ($row_Propiedad['id_tipo_equipo_campo'] == 1) { ?>
															<div class="col-lg-4">
																<label class="control-label">
																	<?php echo $row_Propiedad['propiedad']; ?>
																	<?php if ($row_Propiedad['obligatorio'] == "Y") { ?>
																		<span class="text-danger">*</span>
																	<?php } ?>
																</label>

																<input name="propiedad_<?php echo $row_Propiedad['id_propiedad']; ?>"
																	id="propiedad_<?php echo $row_Propiedad['id_propiedad']; ?>"
																	type="text" class="form-control" <?php if ($row_Propiedad['obligatorio'] == "Y") { 
																			echo "required";
																		} ?> value="" maxlength="<?php echo $lenTexto; ?>">
															</div>
														<?php } elseif ($row_Propiedad['id_tipo_equipo_campo'] == 2) { ?>
															<div class="col-lg-4">
																<label class="control-label">
																	<?php echo $row_Propiedad['propiedad']; ?>
																	<?php if ($row_Propiedad['obligatorio'] == "Y") { ?>
																		<span class="text-danger">*</span>
																	<?php } ?>
																</label>

																<textarea class="form-control" type="text" rows="5"
																	name="propiedad_<?php echo $row_Propiedad['id_propiedad']; ?>"
																	id="propiedad_<?php echo $row_Propiedad['id_propiedad']; ?>" <?php if ($row_Propiedad['obligatorio'] == "Y") { 
																			echo "required";
																		} ?> maxlength="<?php echo $lenComentarios; ?>"></textarea>
															</div>
														<?php } elseif ($row_Propiedad['id_tipo_equipo_campo'] == 3) { ?>
															<div class="col-lg-4 input-group">
																<label class="control-label">
																	<?php echo $row_Propiedad['propiedad']; ?>
																	<?php if ($row_Propiedad['obligatorio'] == "Y") { ?>
																		<span class="text-danger">*</span>
																	<?php } ?>
																</label>

																<div class="input-group date">
																	<span class="input-group-addon">
																		<i class="fa fa-calendar"></i>
																	</span>
																	
																	<input autocomplete="off" type="text" class="form-control"
																		id="propiedad_<?php echo $row_Propiedad['id_propiedad']; ?>"
																		name="propiedad_<?php echo $row_Propiedad['id_propiedad']; ?>" <?php if ($row_Propiedad['obligatorio'] == "Y") { 
																			echo "required";
																		} ?> value="<?php echo isset($_GET[$row_Propiedad['id_propiedad']]) ? $_GET[$row_Propiedad['id_propiedad']] : date('Y-m-d'); ?>">
																</div>
															</div>
														<?php } elseif ($row_Propiedad['id_tipo_equipo_campo'] == 4) { ?>
															<div class="col-lg-4">
																<label class="control-label">
																	<?php echo $row_Propiedad['propiedad']; ?>
																	<?php if ($row_Propiedad['obligatorio'] == "Y") { ?>
																		<span class="text-danger">*</span>
																	<?php } ?>
																</label>

																<select class="form-control"
																	id="propiedad_<?php echo $row_Propiedad['id_propiedad']; ?>"
																	name="propiedad_<?php echo $row_Propiedad['id_propiedad']; ?>" <?php if ($row_Propiedad['obligatorio'] == "Y") { 
																		echo "required";
																	} ?>>
																	<option value="" selected disabled>Seleccione...</option>
																	
																	<option value="Y" <?php if (isset($_GET[$row_Propiedad['id_propiedad']]) && ($_GET[$row_Propiedad['id_propiedad']] == "Y")) {
																			echo "selected";
																		} ?>>SI
																	</option>
																	
																	<option value="N" <?php if (isset($_GET[$row_Propiedad['id_propiedad']]) && ($_GET[$row_Propiedad['id_propiedad']] == "N")) {
																		echo "selected";
																	} ?>>NO
																	</option>
																</select>
															</div>
														<?php } elseif ($row_Propiedad['id_tipo_equipo_campo'] == 5) { ?>
															<?php $Cmp_Lista = ($row_Propiedad['etiqueta_lista']) . ", " . ($row_Propiedad['valor_lista']); ?>
															<?php $SQL_Lista = Seleccionar(($row_Propiedad['tabla_vinculada']), $Cmp_Lista); ?>

															<div class="col-lg-4">
																<label class="control-label">
																	<?php echo $row_Propiedad['propiedad']; ?>
																	<?php if ($row_Propiedad['obligatorio'] == "Y") { ?>
																		<span class="text-danger">*</span>
																	<?php } ?>
																</label>

																<select class="form-control select2" 
																	<?php if ($row_Propiedad['multiple'] == "Y") { ?> data-placeholder="Seleccione..." <?php } ?>
																	id="propiedad_<?php echo $row_Propiedad['id_propiedad']; ?>" 
																	name="propiedad_<?php if ($row_Propiedad['multiple'] == "Y") {
																		   	echo $row_Propiedad['id_propiedad'] . "[]";
																	   	} else {
																		   	echo $row_Propiedad['id_propiedad'];
																	   	} ?>" 
																		
																		<?php if ($row_Propiedad['obligatorio'] == "Y") { 
																			echo "required";
																		} ?> 
																		<?php if ($row_Propiedad['multiple'] == "Y") { ?> multiple="multiple" <?php } ?>>
																	
																	<?php if ($row_Propiedad['multiple'] == "N") { ?>
																		<option value="" selected disabled>Seleccione...</option>
																	<?php } ?>

																	<?php while ($row_Lista = sqlsrv_fetch_array($SQL_Lista)) { ?>
																		<option value="<?php echo $row_Lista[$row_Propiedad['valor_lista']]; ?>">
																			<?php echo $row_Lista[$row_Propiedad['etiqueta_lista']]; ?>
																		</option>
																	<?php } ?>
																</select>
															</div>
														<?php } ?>

												<?php } ?> <!-- while -->
												</div> <!-- form-group -->

											<?php } else { ?>
												<br>
												<i class="fa fa-search" style="font-size: 18px; color: lightgray;"></i>
												<span style="font-size: 13px; color: lightgray;">
													No hay propiedades para el tipo de equipo seleccionado.
												</span>
											<?php } ?>
											</div> <!-- ibox-content -->

										</div>
									</div>
									<!-- Fin, Propiedades -->

									<!-- Anexos -->
									<div id="tab-annexes" class="tab-pane">
										<!-- Anexos -->
										<div class="ibox-content">
											<?php $return = (isset($_GET['return'])) ? (base64_decode($_GET['pag']) . "?" . base64_decode($_GET['return'])) : "consultar_tarjeta_equipo.php?"; ?>
											<?php $return = QuitarParametrosURL($return, array("a")); ?>
											<input type="hidden" id="P" name="P" value="<?php if ($edit == 0) {
												echo "27";
											} else {
												echo "29";
											} ?>" />
											<input type="hidden" id="swTipo" name="swTipo" value="0" />
											<input type="hidden" id="swError" name="swError"
												value="<?php echo $sw_error; ?>" />
											<input type="hidden" id="tl" name="tl" value="<?php echo $edit; ?>" />
											<input type="hidden" id="pag_param" name="pag_param" value="<?php if (isset($_GET['pag'])) {
												echo $_GET['pag'];
											} ?>" />
											<input type="hidden" id="return_param" name="return_param" value="<?php if (isset($_GET['return'])) {
												echo $_GET['return'];
											} ?>" />
											<input type="hidden" id="return" name="return"
												value="<?php echo base64_encode($return); ?>" />
											<input type="hidden" id="ID_TarjetaEquipo" name="ID_TarjetaEquipo" value="<?php if ($edit == 1) {
												echo base64_encode($row['IdTarjetaEquipo']);
											} ?>" />
											<input type="hidden" id="IdAnexos" name="IdAnexos" value="<?php if ($edit == 1) {
												echo $row['IdAnexo'];
											} ?>" />
							</form>

							<?php if (($edit == 1) && ($row['IdAnexo'] != 0)) { ?>
								<div class="form-group">
									<div class="col-xs-12">
										<?php while ($row_Anexo = sqlsrv_fetch_array($SQL_Anexos)) {
											$Icon = IconAttach($row_Anexo['FileExt']); ?>
											<div class="file-box">
												<div class="file">
													<!-- attachdownload.php?swError=<?php echo $sw_error; ?>& -->
													<a href="attachdownload.php?file=<?php echo base64_encode($row_Anexo['AbsEntry']); ?>&line=<?php echo base64_encode($row_Anexo['Line']); ?>"
														target="_blank">
														<div class="icon">
															<i class="<?php echo $Icon; ?>"></i>
														</div>
														<div class="file-name">
															<?php echo $row_Anexo['NombreArchivo']; ?>
															<br />
															<!-- $row_Anexo['Fecha']->format('Y-m-d') -->
															<small>
																<?php echo $row_Anexo['Fecha']; ?>
															</small>
														</div>
													</a>
												</div>
											</div>
										<?php } ?>
									</div>
								</div>
							<?php } else {
								echo "<p>Sin anexos.</p>";
							} ?>
							<?php if (($edit == 0) || ($edit == 1)) { ?>
								<div class="row">
									<form action="upload.php" class="dropzone" id="dropzoneForm" name="dropzoneForm">
										<?php LimpiarDirTemp(); ?>
										<div class="fallback">
											<input name="File" id="File" type="file" form="dropzoneForm" />
										</div>
									</form>
								</div>
							<?php } ?>
						</div>
						<!-- End Anexos -->
					</div>
				</div>
			</div>

			<br><br><br><br><br>
			<div class="form-group">
				<?php if (PermitirFuncion(1602)) { ?>
					<div class="col-lg-12">
						<?php if ($edit == 1) { ?>
							<button class="btn btn-warning" form="CrearTarjetaEquipo" type="submit" id="Actualizar"><i
									class="fa fa-refresh"></i> Actualizar tarjeta de equipo</button>
							<a target="_blank"
								href="gestionar_cartera.php?Clt=<?php echo base64_encode($row['CardCode']); ?>&TE=<?php echo base64_encode($row['IdTarjetaEquipo']); ?>"
								class="btn btn-info pull-right"><i class="fa fa-plus"></i> Crear Gestión CRM</a>
						<?php } ?>
						<?php if ($edit == 0) { ?>
							<button class="btn btn-primary" form="CrearTarjetaEquipo" type="submit" id="Crear"><i
									class="fa fa-check"></i> Crear tarjeta de equipo</button>
						<?php } ?>
						<a href="<?php echo $return; ?>" class="alkin btn btn-outline btn-default"><i
								class="fa fa-arrow-circle-o-left"></i> Regresar</a>
					</div>
				<?php } ?>
			</div>
			<br><br>
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
			// Inicio, cargando Propiedades.
			var idProp = "";
			<?php foreach ($array_ValoresPropiedades as &$valProp) { ?>
				idProp = "propiedad_<?php echo $valProp["id_propiedad"]; ?>";
				$(`#${idProp}`).val("<?php echo $valProp["propiedad"]; ?>");
			<?php } ?>			
			// Fin, cargando Propiedades.

			// Obtener los parámetros de la URL
			const urlParams = new URLSearchParams(window.location.search);

			// Si el parámetro "id_tipo_equipo" está presente
			if (urlParams.get("id_tipo_equipo")) {
				// Obtener los datos del formulario almacenados en sessionStorage
				const formData = JSON.parse(sessionStorage.getItem('formDataTE'));

				// Buscar y asignar los valores de los campos en los datos del formulario
				const valorTipoEquipo = formData.find(field => field.name === 'TipoEquipo');
				$('#TipoEquipo').val(valorTipoEquipo.value);
				const valorSerialInterno = formData.find(field => field.name === 'SerialInterno');
				$('#SerialInterno').val(valorSerialInterno.value);
				const valorSerialFabricante = formData.find(field => field.name === 'SerialFabricante');
				$('#SerialFabricante').val(valorSerialFabricante.value);
				const valorItemCode = formData.find(field => field.name === 'ItemCode');
				$('#ItemCode').val(valorItemCode.value);
				const valorItemName = formData.find(field => field.name === 'ItemName');
				$('#ItemName').val(valorItemName.value);
				const valorCodEstado = formData.find(field => field.name === 'CodEstado');
				$('#CodEstado').val(valorCodEstado.value);
				const valorSerieAnterior = formData.find(field => field.name === 'SerieAnterior');
				$('#SerieAnterior').val(valorSerieAnterior.value);
				const valorSerieNueva = formData.find(field => field.name === 'SerieNueva');
				$('#SerieNueva').val(valorSerieNueva.value);
				const valorClienteEquipo = formData.find(field => field.name === 'ClienteEquipo');
				$('#ClienteEquipo').val(valorClienteEquipo.value);
				$('#ClienteEquipo').change();
				const valorNombreClienteEquipo = formData.find(field => field.name === 'NombreClienteEquipo');
				$('#NombreClienteEquipo').val(valorNombreClienteEquipo.value);
				const valorIdTecnico = formData.find(field => field.name === 'IdTecnico');
				$('#IdTecnico').val(valorIdTecnico.value);
				const valorIdTerritorio = formData.find(field => field.name === 'IdTerritorio');
				$('#IdTerritorio').val(valorIdTerritorio.value);
			}

			// SMM, 27/02/2024
			$("#IdTipoEquipoPropiedad").on("change", function () {
				Swal.fire({
					title: "¿Desea reiniciar la página para cargar las propiedades del tipo de equipo seleccionado?",
					icon: "question",
					showCancelButton: true,
					confirmButtonText: "Si, confirmo",
					cancelButtonText: "No"
				}).then((result) => {
					if (result.isConfirmed) {
						// Serializar el formulario y almacenar los datos en sessionStorage
						sessionStorage.setItem('formDataTE', JSON.stringify($("#CrearTarjetaEquipo").serializeArray()));

						// Obtener el valor seleccionado del elemento select
						let id_tipo_equipo = $(this).val();

						// Obtener la URL actual
						let currentUrl = new URL(window.location.href);
						
						// Modificar el parámetro id_tipo_equipo
						currentUrl.searchParams.set('id_tipo_equipo', id_tipo_equipo);
						
						// Redirigir a la nueva URL
						window.location.href = currentUrl.href;
					}
				});
			});
						
			<?php if ($dt_TE == 1) { ?>
				$('#ClienteEquipo').trigger('change'); // SMM, 17/02/2022
			<?php } ?>

			// SMM, 05/03/2024
			<?php if(isset($_GET["tab"])) { ?>
				VerTAB("<?php echo $_GET["tab"]; ?>");
			<?php } ?>

			// SMM, 07/06/2022
			<?php if ((($edit == 0) && (!PermitirFuncion(1603))) || (($edit == 1) && (!PermitirFuncion(1604)))) { ?>
				let CDU_Concesionario_Options = $('#CDU_Concesionario').find('option');
				$.each(CDU_Concesionario_Options, function () {
					$(this).is(":selected") ? "" : $(this).attr('disabled', true);
				});
			<?php } ?>

			// Abrir pestaña de "properties" para ver sus "required". SMM, 23/02/2024
			$("#Crear, #Actualizar").on("click", function () {
				VerTAB("properties");
			});

			$("#CrearTarjetaEquipo").validate({
				submitHandler: function (form) {
					Swal.fire({
						title: "¿Está seguro que desea guardar los datos?",
						icon: "question",
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

			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});

			maxLength('Comentarios');
			maxLength('NotasActividad');

			$(".select2").select2();
			$('.i-checks').iCheck({
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green',
			});
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
						var value = $("#NombreClienteEquipo").getSelectedItemData().CodigoCliente;
						$("#ClienteEquipo").val(value).trigger("change");
					},
					onKeyEnterEvent: function () {
						var value = $("#NombreClienteEquipo").getSelectedItemData().CodigoCliente;
						$("#ClienteEquipo").val(value).trigger("change");
					}
				}
			};
			var optionsArticulos = {
				url: function (phrase) {
					return "ajx_buscar_datos_json.php?type=24&id=" + phrase;
				},

				getValue: "NombreBuscarArticulo",
				requestDelay: 400,
				list: {
					maxNumberOfElements: 10, // SMM, 17/02/2022
					match: {
						enabled: true
					},
					onClickEvent: function () {
						var nombreArt = $("#ItemCode").getSelectedItemData().DescripcionArticulo;
						var idArt = $("#ItemCode").getSelectedItemData().IdArticulo;
						$("#ItemName").val(nombreArt);
						$("#ItemCode").val(idArt);
					}
				}
			};
			$("#NombreClienteEquipo").easyAutocomplete(options);
			$("#ItemCode").easyAutocomplete(optionsArticulos);
		});

		function EnviarFrm(P = 29) {
			var vP = document.getElementById('P');
			var txtNotas = document.getElementById('NotasActividad');
			if (P == 29) {
				vP.value = P;
				txtNotas.setAttribute("required", "required");
			} else {
				vP.value = P;
				txtNotas.removeAttribute("required");
			}
		}

		// SMM, 29/02/2024
		function AddComponents() {
			let SI = $("#SerialInterno").val();
			let SF = $("#SerialFabricante").val();

			if (((SI == "") && (SF == ""))) {
				Swal.fire({
					title: "¡Advertencia!",
					text: "Debe existir un Serial.",
					icon: "warning",
					confirmButtonText: "OK"
				});
			} else {
				$.ajax({
					type: "POST",
					url: "md_consultar_componentes.php",
					data: {
						DocId: "<?php echo $IdTarjetaEquipo; ?>"
					},
					success: function (response) {
						$('#mdComponents').html(response);
						$("#mdComponents").modal("show");
					}
				});
			}
		}

		// SMM, 22/03/2024
		function OpenComponent() {
			let IdTarjetaEquipoComponente = $("#id_tarjeta_equipo_hijo").text() || "";

			if (IdTarjetaEquipoComponente != "") {
				self.name = 'opener';
				remote = open(`tarjeta_equipo.php?id='${Base64.encode(IdTarjetaEquipoComponente)}'&ext=1&tl=1`, 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
				remote.focus();
			}
		}

		// SMM, 07/03/2024
		function DeleteComponent() {
			let idPadre = "<?php echo $IdTarjetaEquipo ?? ""; ?>";
			let idHijo = $("#id_tarjeta_equipo_hijo").text() || "";

			let componente = {};
			if (idHijo != "") {
				componente = {
					type: 3,
					id_padre: idPadre,
					id_hijo: idHijo
				};
			}

			// JSON que se esta enviando a eliminar.
			console.log(componente);

			Swal.fire({
				title: "¿Está seguro que desea eliminar el componente?",
				icon: "question",
				showCancelButton: true,
				confirmButtonText: "Si, confirmo",
				cancelButtonText: "No"
			}).then((result) => {
				if (result.isConfirmed) {
					$('.ibox-content').toggleClass('sk-loading',true);
					
					// Envio AJAX del Articulo.
					$.ajax({
						url: "md_consultar_componentes.php",
						type: "POST",
						data: componente,
						success: function (response) {
							// Manejar la respuesta del servidor
							// console.log("Respuesta:", response);

							// Obtener la URL actual
							let currentUrl = new URL(window.location.href);
							console.log(currentUrl);
							
							// Modificar o crear parámetro
							currentUrl.searchParams.set("tab", "components");
							
							// Redirigir a la nueva URL
							window.location.href = currentUrl.href;
						},
						error: function (error) {
							// Manejar el error de la petición AJAX
							console.log("Error eliminación:", error);

							// alert("Ocurrio un error al eliminar los articulos, se recomienda repetir el procedimiento o consultar al administrador");
						}
					});
					// Fin AJAX
				}
			});
		}

		// SMM, 05/03/2024
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

		// Agregado por Stiven, 25/06/2022
		$('.date input:not([readonly])').datepicker({
			todayBtn: "linked",
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true,
			format: 'yyyy-mm-dd',
			todayHighlight: true
		});
	</script>
	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>
<?php sqlsrv_close($conexion); ?>