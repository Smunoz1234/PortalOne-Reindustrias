<?php require_once "includes/conexion.php";
PermitirAcceso(1204);
$msg_error = ""; //Mensaje del error
$dt_SS = 0; //sw para saber si vienen datos de una Solicitud de salida.
$dt_DR = 0; //sw Para saber que viene de un despachode rutas
$IdTrasladoInv = 0;
$IdPortal = 0; //Id del portal para las solicitudes que fueron creadas en el portal, para eliminar el registro antes de cargar al editar
$NameFirma = "";
$PuedeFirmar = 0;

// SMM, 30/11/2022
$IdMotivo = "";
$motivoAutorizacion = "";

$debug_Condiciones = false; // Ocultar o mostrar modal y otras opciones de debug.
$IdTipoDocumento = 67; // Cambiar por el ID respectivo.
$success = 1; // Confirmación de autorización (1 - Autorizado / 0 - NO Autorizado)
$mensajeProceso = ""; // Mensaje proceso, mensaje de salida del procedimiento almacenado.

// SMM, 20/12/2023
$BillToDef = ""; // Sucursal de Facturación por Defecto.
$ShipToDef = ""; // Sucursal de Destino por Defecto.

// Procesos de autorización, SMM 30/11/2022
$SQL_Procesos = Seleccionar("uvw_tbl_Autorizaciones_Procesos", "*", "Estado = 'Y' AND IdTipoDocumento = $IdTipoDocumento");

if (isset($_GET['id']) && ($_GET['id'] != "")) { //ID de la Salida de inventario (DocEntry)
	$IdTrasladoInv = base64_decode($_GET['id']);
}

if (isset($_GET['id_portal']) && ($_GET['id_portal'] != "")) { //Id del portal de venta (ID interno)
	$IdPortal = base64_decode($_GET['id_portal']);
}

if (isset($_POST['IdTrasladoInv']) && ($_POST['IdTrasladoInv'] != "")) { //Tambien el Id interno, pero lo envío cuando mando el formulario
	$IdTrasladoInv = base64_decode($_POST['IdTrasladoInv']);
	$IdEvento = base64_decode($_POST['IdEvento']);
}

if (isset($_POST['swError']) && ($_POST['swError'] != "")) { //Para saber si ha ocurrido un error.
	$sw_error = $_POST['swError'];
} else {
	$sw_error = 0;
}

if (isset($_REQUEST['tl']) && ($_REQUEST['tl'] != "")) { //0 Si se está creando. 1 Se se está editando.
	$edit = $_REQUEST['tl'];
} else {
	$edit = 0;
}

// Consulta decisión de autorización en la edición de documentos.
if ($edit == 1) {
	$DocEntry = "'" . $IdTrasladoInv . "'"; // Cambiar por el ID respectivo del documento.

	$EsBorrador = (false) ? "DocumentoBorrador" : "Documento";
	$SQL_Autorizaciones = Seleccionar("uvw_Sap_tbl_Autorizaciones", "*", "IdTipoDocumento = $IdTipoDocumento AND DocEntry$EsBorrador = $DocEntry");
	$row_Autorizaciones = sqlsrv_fetch_array($SQL_Autorizaciones);

	// SMM, 30/11/2022
	$SQL_Procesos = Seleccionar("uvw_tbl_Autorizaciones_Procesos", "*", "IdTipoDocumento = $IdTipoDocumento");
}
// Hasta aquí, 30/11/2022

if (isset($_POST['P']) && ($_POST['P'] != "")) { //Grabar Salida de inventario
	//*** Carpeta temporal ***
	$i = 0; //Archivos
	$RutaAttachSAP = ObtenerDirAttach();
	$dir = CrearObtenerDirTemp();
	$dir_firma = CrearObtenerDirTempFirma();
	$dir_new = CrearObtenerDirAnx("trasladoinventario");

	// La firma se copia al directorio temporal, pero luego es que se copia a SAP.
	if ((isset($_POST['SigRecibe'])) && ($_POST['SigRecibe'] != "")) {
		$NombreFileFirma = base64_decode($_POST['SigRecibe']);
		$Nombre_Archivo = $NombreFileFirma;
		if (!copy($dir_firma . $NombreFileFirma, $dir . $Nombre_Archivo)) {
			$sw_error = 1;
			$msg_error = "No se pudo mover la firma";
		}
	}

	// Luego el directorio temporal se lee y se copia al directorio de SAP (RutaAttachSAP).
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
		if ($_POST['tl'] == 1) { //Actualizar
			$IdTrasladoInv = base64_decode($_POST['IdTrasladoInv']);
			$IdEvento = base64_decode($_POST['IdEvento']);
			$Type = 2;

			/*
					 if (!PermitirFuncion(403)) { //Permiso para autorizar Solicitud de salida
					 $_POST['Autorizacion'] = 'P'; //Si no tengo el permiso, la Solicitud queda pendiente
					 }
					  */

			if ((isset($_POST['SigRecibe'])) && ($_POST['SigRecibe'] != "")) { //Si solo estoy actualizando la firma
				$Type = 4;
			}
		} else { //Crear
			$IdTrasladoInv = "NULL";
			$IdEvento = "0";
			$Type = 1;
		}

		if (isset($_POST['AnioEntrega']) && ($_POST['AnioEntrega'] != "")) {
			$AnioEntrega = "'" . $_POST['AnioEntrega'] . "'";
		} else {
			$AnioEntrega = "NULL";
		}

		if (isset($_POST['EntregaDescont']) && ($_POST['EntregaDescont'] != "")) {
			$EntregaDescont = "'" . $_POST['EntregaDescont'] . "'";
		} else {
			$EntregaDescont = "NULL";
		}

		if (isset($_POST['ValorCuotaDesc']) && ($_POST['ValorCuotaDesc'] != "")) {
			$ValorCuotaDesc = "'" . $_POST['ValorCuotaDesc'] . "'";
		} else {
			$ValorCuotaDesc = "NULL";
		}

		if ($Type == 4) {
			$ParametrosCabTrasladoInv = array(
				$IdTrasladoInv,
				$IdEvento,
				"'" . $_SESSION['CodUser'] . "'",
				"'" . $_SESSION['CodUser'] . "'",
				$Type,
				// SMM, 17/02/2023
				"'" . ($_POST['NombreRecibeFirma'] ?? "") . "'",
				"'" . ($_POST['CedulaRecibeFirma'] ?? "") . "'",
				// Estos campos deben ir arriba para poder firmar.
			);
		} else {
			$ParametrosCabTrasladoInv = array(
				$IdTrasladoInv,
				$IdEvento,
				"'" . $_SESSION['CodUser'] . "'",
				"'" . $_SESSION['CodUser'] . "'",
				$Type,
				// SMM, 16/02/2023
				"'" . ($_POST['NombreRecibeFirma'] ?? "") . "'",
				"'" . ($_POST['CedulaRecibeFirma'] ?? "") . "'",
				// Estos campos deben ir arriba para poder firmar.
				"NULL",
				"NULL",
				"'" . $_POST['Serie'] . "'",
				"'" . $_POST['EstadoDoc'] . "'",
				"'" . FormatoFecha($_POST['DocDate']) . "'",
				"'" . FormatoFecha($_POST['DocDueDate']) . "'",
				"'" . FormatoFecha($_POST['TaxDate']) . "'",
				"'" . $_POST['CardCode'] . "'",
				"'" . $_POST['ContactoCliente'] . "'",
				"'" . ($_POST['OrdenServicioCliente'] ?? "") . "'",
				"'" . $_POST['Referencia'] . "'",
				"'" . $_POST['EmpleadoVentas'] . "'",
				"'" . LSiqmlObs($_POST['Comentarios']) . "'",
				"'" . str_replace(',', '', $_POST['SubTotal']) . "'",
				"'" . str_replace(',', '', $_POST['Descuentos']) . "'",
				"NULL",
				"'" . str_replace(',', '', $_POST['Impuestos']) . "'",
				"'" . str_replace(',', '', $_POST['TotalTraslado']) . "'",
				"'" . $_POST['SucursalFacturacion'] . "'",
				"'" . $_POST['DireccionFacturacion'] . "'",
				"'" . $_POST['SucursalDestino'] . "'",
				"'" . $_POST['DireccionDestino'] . "'",
				"'" . $_POST['CondicionPago'] . "'",

				"''", // Almacen
				"''", // AlmacenDestino

				// Se eliminaron las dimensiones, SMM 29/08/2022

				"'" . $_POST['PrjCode'] . "'", // SMM, 29/11/2022
				"'" . $_POST['Autorizacion'] . "'", // SMM, 29/11/2022

				// SMM, 17/04/2024
				"'" . ($_POST['TipoEntrega'] ?? "") . "'",
				$AnioEntrega,
				$EntregaDescont,
				$ValorCuotaDesc,

				"NULL", // @CodEmpleado

				// SMM, 30/11/2022
				"'" . ($_POST['IdMotivoAutorizacion'] ?? "") . "'",
				"'" . ($_POST['ComentariosAutor'] ?? "") . "'",
				"'" . ($_POST['MensajeProceso'] ?? "") . "'",

				// SMM, 23/12/2022
				"''", // ConceptoSalida
			);
		}

		$SQL_CabeceraTrasladoInv = EjecutarSP('sp_tbl_TrasladoInventario', $ParametrosCabTrasladoInv, $_POST['P']);
		if ($SQL_CabeceraTrasladoInv) {
			if ($Type == 1) {
				$row_CabeceraTrasladoInv = sqlsrv_fetch_array($SQL_CabeceraTrasladoInv);
				$IdTrasladoInv = $row_CabeceraTrasladoInv[0];
				$IdEvento = $row_CabeceraTrasladoInv[1];

				// Comprobar procesos de autorización en la creación, SMM 30/11/2022
				while ($row_Proceso = sqlsrv_fetch_array($SQL_Procesos)) {
					$ids_perfiles = ($row_Proceso['Perfiles'] != "") ? explode(";", $row_Proceso['Perfiles']) : [];

					if (in_array($_SESSION['Perfil'], $ids_perfiles) || (count($ids_perfiles) == 0)) {
						$sql = $row_Proceso['Condiciones'] ?? '';
						$autorizaSAP = $row_Proceso['AutorizacionSAP'] ?? ''; // SMM, 13/12/2022

						// Aquí se debe reemplazar por el ID del documento. SMM, 13/12/2022
						$sql = str_replace("[IdDocumento]", $IdTrasladoInv, $sql);
						$sql = str_replace("[IdEvento]", $IdEvento, $sql);

						$stmt = sqlsrv_query($conexion, $sql);

						$data = "";
						if ($stmt === false) {
							$data = json_encode(sqlsrv_errors(), JSON_PRETTY_PRINT);
						} else {
							$records = array();
							while ($obj = sqlsrv_fetch_object($stmt)) {
								if (isset($obj->success) && ($obj->success == 0)) {
									$success = 0;
									$IdMotivo = $obj->IdMotivo;
									$mensajeProceso = $obj->mensaje;
								}

								array_push($records, $obj);
							}
							$data = json_encode($records, JSON_PRETTY_PRINT);
						}

						if ($debug_Condiciones) {
							$dataString = "JSON.stringify($data, null, '\t')";
							echo "<script> console.log($dataString); </script>";
						}
					}
				}

				// Consultar el motivo de autorización según el ID.
				if ($IdMotivo != "") {
					$SQL_Motivos = Seleccionar("uvw_tbl_Autorizaciones_Motivos", "*", "IdMotivoAutorizacion = '$IdMotivo'");
					$row_MotivoAutorizacion = sqlsrv_fetch_array($SQL_Motivos);
				}

				$motivoAutorizacion = $row_MotivoAutorizacion['MotivoAutorizacion'] ?? "";

				// Hasta aquí, 30/11/2022
			} else {
				$IdTrasladoInv = base64_decode($_POST['IdTrasladoInv']); //Lo coloco otra vez solo para saber que tiene ese valor
				$IdEvento = base64_decode($_POST['IdEvento']);
			}

			try {
				//Mover los anexos a la carpeta de archivos de SAP
				$j = 0;
				while ($j < $CantFiles) {
					$Archivo = FormatoNombreAnexo($DocFiles[$j]);
					$NuevoNombre = $Archivo[0];
					$OnlyName = $Archivo[1];
					$Ext = $Archivo[2];

					if (file_exists($dir_new)) {
						copy($dir . $DocFiles[$j], $dir_new . $NuevoNombre);
						//move_uploaded_file($_FILES['FileArchivo']['tmp_name'],$dir_new.$NuevoNombre);
						copy($dir_new . $NuevoNombre, $RutaAttachSAP[0] . $NuevoNombre);

						//Registrar archivo en la BD
						$ParamInsAnex = array(
							"'67'",
							"'$IdTrasladoInv'",
							"'$OnlyName'",
							"'$Ext'",
							"1",
							"'" . $_SESSION['CodUser'] . "'",
							"1",
						);
						$SQL_InsAnex = EjecutarSP('sp_tbl_DocumentosSAP_Anexos', $ParamInsAnex, $_POST['P']);
						if (!$SQL_InsAnex) {
							$sw_error = 1;
							$msg_error = "Error al insertar los anexos.";
						}
					}
					$j++;
				}
			} catch (Exception $e) {
				echo 'Excepcion capturada: ', $e->getMessage(), "\n";
			}

			// SMM, 13/12/2022
			if ($debug_Condiciones && ($success == 1)) {
				$success = 0;
				// echo 'La bandera "$success" cambio de 1 a 0, en el modo de depuración';
			}

			// Verificar que el documento cumpla las Condiciones o este Pendiente de Autorización.
			if (($success == 1) || ($_POST['Autorizacion'] == "P")) {
				$success = 1;

				// Inicio, Enviar datos al WebServices.
				try {
					$Parametros = array(
						'id_documento' => intval($IdTrasladoInv),
						'id_evento' => intval($IdEvento),
					);
					$Metodo = "TrasladosInventarios";
					$Resultado = EnviarWebServiceSAP($Metodo, $Parametros, true, true);

					if ($Resultado->Success == 0) {
						$sw_error = 1;
						$msg_error = $Resultado->Mensaje;
					} else {
						// SMM, 30/11/2022
						if (isset($_POST['Autorizacion']) && ($_POST['Autorizacion'] == "P")) {
							$nombreArchivo = "traslado_inventario"; // Ajustar según sea el caso.
							header("Location:$nombreArchivo.php?a=" . base64_encode("OK_BorradorAdd"));
						} else {
							// Inicio, redirección documento autorizado.
							if ($_POST['tl'] == 0) { //Creando traslado
								//Consultar ID creado para cargar el documento
								$SQL_ConsID = Seleccionar('uvw_Sap_tbl_TrasladosInventarios', 'ID_TrasladoInv', "IdDocPortal='" . $IdTrasladoInv . "'");
								$row_ConsID = sqlsrv_fetch_array($SQL_ConsID);
								sqlsrv_close($conexion);
								header('Location:traslado_inventario.php?id=' . base64_encode($row_ConsID['ID_TrasladoInv']) . '&id_portal=' . base64_encode($IdTrasladoInv) . '&tl=1&a=' . base64_encode("OK_TrasInvAdd"));
							} else { //Actualizando traslado

								// SMM, 17/02/2023
								$SQL_ConsID = Seleccionar('uvw_Sap_tbl_TrasladosInventarios', 'ID_TrasladoInv', "IdDocPortal='" . $IdTrasladoInv . "'");
								$row_ConsID = sqlsrv_fetch_array($SQL_ConsID);
								sqlsrv_close($conexion);

								/*
														   echo "SELECT ID_TrasladoInv FROM uvw_Sap_tbl_TrasladosInventarios WHERE IdDocPortal='$IdTrasladoInv'";
														   echo '<br>traslado_inventario.php?id=' . $row_ConsID['ID_TrasladoInv'] . "&id_portal=$IdTrasladoInv&tl=1&a=OK_TrasInvAdd";
														   echo '<br>traslado_inventario.php?id=' . base64_encode($row_ConsID['ID_TrasladoInv']) . '&id_portal=' . base64_encode($IdTrasladoInv) . '&tl=1&a=' . base64_encode("OK_TrasInvAdd");
														   exit();
														   */

								header('Location:traslado_inventario.php?id=' . base64_encode($row_ConsID['ID_TrasladoInv']) . '&id_portal=' . base64_encode($IdTrasladoInv) . '&tl=1&a=' . base64_encode("OK_TrasInvUpd"));
								// header('Location:' . base64_decode($_POST['return']) . '&a=' . base64_encode("OK_TrasInvUpd"));
							}
							// Fin, redirección documento autorizado.
						}
					}
				} catch (Exception $e) {
					echo 'Excepcion capturada: ', $e->getMessage(), "\n";
				}
				// Fin, Enviar datos al WebServices.
			} else {
				$sw_error = 1;
				$msg_error = "Este documento necesita autorización.";
			}
			// Hasta aquí, 30/11/2022

			/*
					 sqlsrv_close($conexion);
					 if($_POST['tl']==0) { // Creando Entrada
						 header('Location:'.base64_decode($_POST['return']).'&a='.base64_encode("OK_TrasInvAdd"));
					 } else { // Actualizando Entrada
						 header('Location:'.base64_decode($_POST['return']).'&a='.base64_encode("OK_TrasInvUpd"));
					 }
					 */
		} else {
			$sw_error = 1;
			$msg_error = "Ha ocurrido un error al crear el traslado de inventario";
		}
	} catch (Exception $e) {
		echo 'Excepcion capturada: ', $e->getMessage(), "\n";
	}

}

if (isset($_GET['dt_SS']) && ($_GET['dt_SS']) == 1) { //Verificar que viene de una Solicitud de salida
	$dt_SS = 1;

	// Limpiar lotes y seriales. SMM, 23/01/2022
	$ConsLote = "DELETE FROM tbl_LotesDocSAP WHERE CardCode = '" . base64_decode($_GET['Cardcode']) . "' AND Usuario = '" . $_SESSION['CodUser'] . "'";
	$ConsSerial = "DELETE FROM tbl_SerialesDocSAP WHERE CardCode = '" . base64_decode($_GET['Cardcode']) . "' AND Usuario = '" . $_SESSION['CodUser'] . "'";
	$SQL_ConsLote = sqlsrv_query($conexion, $ConsLote);
	$SQL_ConsSerial = sqlsrv_query($conexion, $ConsSerial);

	//Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreCliente');
	$row_Cliente = sqlsrv_fetch_array($SQL_Cliente);

	// Sucursales. SMM, 01/12/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND NombreSucursal='" . base64_decode($_GET['Sucursal']) . "'");

	if (isset($_GET['SucursalFact'])) {
		$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND NombreSucursal='" . base64_decode($_GET['SucursalFact']) . "' AND TipoDireccion='B'", 'NombreSucursal');
	}
	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreContacto');

	$ParametrosCopiarSolSalidaToTrasladoInv = array(
		"'" . base64_decode($_GET['SS']) . "'",
		"'" . base64_decode($_GET['Evento']) . "'",
		"'" . base64_decode($_GET['Almacen']) . "'",
		"'" . base64_decode($_GET['Cardcode']) . "'",
		"'" . $_SESSION['CodUser'] . "'",
	);
	$SQL_CopiarSolSalidaToTrasladoInv = EjecutarSP('sp_tbl_SolSalidaDet_To_TrasladoInvDet', $ParametrosCopiarSolSalidaToTrasladoInv);
	if (!$SQL_CopiarSolSalidaToTrasladoInv) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Ha ocurrido un error!',
				text: 'No se pudo copiar la Solicitud en la Salida de inventario.',
				icon: 'error'
			});
		});
		</script>";
	}

}

if (isset($_GET['dt_DR']) && ($_GET['dt_DR']) == 1) { //Verificar que viene del despacho de rutas
	$dt_SS = 1;
	$dt_DR = 1;

	//Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreCliente');
	$row_Cliente = sqlsrv_fetch_array($SQL_Cliente);

	//Sucursal destino
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='S' and NumeroLinea=0");
	$row_SucursalDestino = sqlsrv_fetch_array($SQL_SucursalDestino);
	$_GET['Direccion'] = base64_encode($row_SucursalDestino['Direccion']);

	sqlsrv_fetch($SQL_SucursalDestino, SQLSRV_SCROLL_ABSOLUTE, -1);

	//Contacto cliente
	//$SQL_ContactoCliente=Seleccionar('uvw_Sap_tbl_ClienteContactos','*',"CodigoCliente='".base64_decode($_GET['Cardcode'])."'",'NombreContacto');

	$ParamCopiarDespachosToTraslados = array(
		"'" . base64_decode($_GET['AlmacenDestino']) . "'",
		"'" . base64_decode($_GET['Cardcode']) . "'",
		"'" . $_SESSION['CodUser'] . "'",
	);
	$SQL_CopiarDespachosToTraslados = EjecutarSP('sp_tbl_DespachoRutas_To_TrasladoInvDet', $ParamCopiarDespachosToTraslados);
	if (!$SQL_CopiarDespachosToTraslados) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Ha ocurrido un error!',
				text: 'No se pudo copiar el despacho en el traslado de inventario.',
				icon: 'error'
			});
		});
		</script>";
	}

}

if ($edit == 1 && $sw_error == 0) {

	$ParametrosLimpiar = array(
		"'" . $IdTrasladoInv . "'",
		"'" . $IdPortal . "'",
		"'" . $_SESSION['CodUser'] . "'",
	);
	$LimpiarSolSalida = EjecutarSP('sp_EliminarDatosTrasladoInventario', $ParametrosLimpiar);

	$SQL_IdEvento = sqlsrv_fetch_array($LimpiarSolSalida);
	$IdEvento = $SQL_IdEvento[0];

	// Salida inventario
	$Cons = "SELECT * FROM uvw_tbl_TrasladoInventario WHERE DocEntry = '$IdTrasladoInv' AND IdEvento = '$IdEvento'";
	// echo $Cons;
	// exit();

	$SQL = sqlsrv_query($conexion, $Cons);
	$row = sqlsrv_fetch_array($SQL);

	//Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreCliente');

	//Sucursales
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' and TipoDireccion='B'", 'NombreSucursal');
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' and TipoDireccion='S'", 'NombreSucursal');

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreContacto');

	//Orden de servicio, SMM, 29/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . $row['ID_LlamadaServicio'] . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);

	//Sucursal
	$SQL_Sucursal = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'IdSucursal, DeSucursal', "IdSeries='" . $row['IdSeries'] . "'", "IdSucursal, DeSucursal");

	//Anexos
	$SQL_Anexo = Seleccionar('uvw_Sap_tbl_DocumentosSAP_Anexos', '*', "AbsEntry='" . $row['IdAnexo'] . "'");

	if (($row['CodEmpleado'] == $_SESSION['IdCardCode']) || PermitirFuncion(1213)) {
		$PuedeFirmar = 1;
	} else {
		$PuedeFirmar = 0;
	}

}

if ($sw_error == 1) {

	// Salida de inventario
	$Cons = "SELECT * FROM uvw_tbl_TrasladoInventario WHERE ID_TrasladoInv = '$IdTrasladoInv' AND IdEvento = '$IdEvento'";
	$SQL = sqlsrv_query($conexion, $Cons);
	$row = sqlsrv_fetch_array($SQL);

	//Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreCliente');

	//Sucursales
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' and TipoDireccion='B'", 'NombreSucursal');
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' and TipoDireccion='S'", 'NombreSucursal');

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreContacto');

	//Orden de servicio, SMM, 29/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . $row['ID_LlamadaServicio'] . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);

	//Sucursal
	$SQL_Sucursal = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'IdSucursal, DeSucursal', "IdSeries='" . $row['IdSeries'] . "'", "IdSucursal, DeSucursal");

	//Anexos
	$SQL_Anexo = Seleccionar('uvw_Sap_tbl_DocumentosSAP_Anexos', '*', "AbsEntry='" . $row['IdAnexo'] . "'");

	if (($row['CodEmpleado'] == $_SESSION['IdCardCode']) || PermitirFuncion(1213)) {
		$PuedeFirmar = 1;
	} else {
		$PuedeFirmar = 0;
	}

}

// SMM, 30/06/2023
$FiltroPrj = "";
$FiltrarDest = 0;
$FiltrarFact = 0;
if ($edit == 0) {
	// Filtrar proyectos asignados
	$Where_Proyectos = "ID_Usuario='" . $_SESSION['CodUser'] . "'";
	$SQL_Proyectos = Seleccionar('uvw_tbl_UsuariosProyectos', '*', $Where_Proyectos);

	$Proyectos = array();
	while ($Proyecto = sqlsrv_fetch_array($SQL_Proyectos)) {
		$Proyectos[] = $Proyecto['IdProyecto'];
	}

	if (count($Proyectos) == 1) {
		$FiltroPrj = $Proyectos[0];
	}

	// Filtrar sucursales
	if (isset($SQL_SucursalDestino) && (sqlsrv_num_rows($SQL_SucursalDestino) == 1)) {
		$FiltrarDest = 1;
	}

	if (isset($SQL_SucursalFacturacion) && (sqlsrv_num_rows($SQL_SucursalFacturacion) == 1)) {
		$FiltrarFact = 1;
	}
}

//Condiciones de pago
$SQL_CondicionPago = Seleccionar('uvw_Sap_tbl_CondicionPago', '*', '', 'IdCondicionPago');

//Datos de dimensiones del usuario actual
$SQL_DatosEmpleados = Seleccionar('uvw_tbl_Usuarios', '*', "ID_Usuario='" . $_SESSION['CodUser'] . "'");
$row_DatosEmpleados = sqlsrv_fetch_array($SQL_DatosEmpleados);

//Tipo entrega
$SQL_TipoEntrega = Seleccionar('uvw_Sap_tbl_TipoEntrega', '*', '', 'DeTipoEntrega');

//Año entrega
$SQL_AnioEntrega = Seleccionar('uvw_Sap_tbl_TipoEntregaAnio', '*', '', 'DeAnioEntrega');

//Estado documento
$SQL_EstadoDoc = Seleccionar('uvw_tbl_EstadoDocSAP', '*');

//Estado autorizacion
$SQL_EstadoAuth = Seleccionar('uvw_Sap_tbl_EstadosAuth', '*');

//Series de documento
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'67'",
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Filtrar conceptos de salida. SMM, 20/01/2023
$Where_Conceptos = "ID_Usuario='" . $_SESSION['CodUser'] . "'";
$SQL_Conceptos = Seleccionar('uvw_tbl_UsuariosConceptos', '*', $Where_Conceptos);

$Conceptos = array();
while ($Concepto = sqlsrv_fetch_array($SQL_Conceptos)) {
	$Conceptos[] = ("'" . $Concepto['IdConcepto'] . "'");
}

$Filtro_Conceptos = "Estado = 'Y'";
if (count($Conceptos) > 0 && ($edit == 0)) {
	$Filtro_Conceptos .= " AND id_concepto_salida IN (";
	$Filtro_Conceptos .= implode(",", $Conceptos);
	$Filtro_Conceptos .= ")";
}

$SQL_ConceptoSalida = Seleccionar('tbl_SalidaInventario_Conceptos', '*', $Filtro_Conceptos, 'id_concepto_salida');
// Hasta aquí, 16/02/2023

// Filtrar proyectos asignados. SMM, 16/02/2023
$Where_Proyectos = "ID_Usuario='" . $_SESSION['CodUser'] . "'";
$SQL_Proyectos = Seleccionar('uvw_tbl_UsuariosProyectos', '*', $Where_Proyectos);

$Proyectos = array();
while ($Concepto = sqlsrv_fetch_array($SQL_Proyectos)) {
	$Proyectos[] = ("'" . $Concepto['IdProyecto'] . "'");
}

$Filtro_Proyectos = "";
if (count($Proyectos) > 0 && ($edit == 0)) {
	$Filtro_Proyectos .= "IdProyecto IN (";
	$Filtro_Proyectos .= implode(",", $Proyectos);
	$Filtro_Proyectos .= ")";
}

$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', $Filtro_Proyectos, 'DeProyecto');
// Hasta aquí, 16/02/2023

// Consultar el motivo de autorización según el ID. SMM, 30/11/2022
if (isset($row['IdMotivoAutorizacion']) && ($row['IdMotivoAutorizacion'] != "") && ($IdMotivo == "")) {
	$IdMotivo = $row['IdMotivoAutorizacion'];
	$SQL_Motivos = Seleccionar("uvw_tbl_Autorizaciones_Motivos", "*", "IdMotivoAutorizacion = '$IdMotivo'");
	$row_MotivoAutorizacion = sqlsrv_fetch_array($SQL_Motivos);
	$motivoAutorizacion = $row_MotivoAutorizacion['MotivoAutorizacion'] ?? "";
}

// SMM, 20/01/2023
if ($edit == 0) {
	$ClienteDefault = "";
	$NombreClienteDefault = "";
	$SucursalDestinoDefault = "";
	$SucursalFacturacionDefault = "";

	if (ObtenerVariable("NITClienteDefault") != "") {
		$ClienteDefault = ObtenerVariable("NITClienteDefault");

		$SQL_ClienteDefault = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='$ClienteDefault'");
		$row_ClienteDefault = sqlsrv_fetch_array($SQL_ClienteDefault);

		$NombreClienteDefault = $row_ClienteDefault["NombreBuscarCliente"]; // NombreCliente
		$SucursalDestinoDefault = "NEIVA";
		$SucursalFacturacionDefault = "NEIVA";
	}
}

// SMM, 21/03/2024
$SQL_ListaPrecios = Seleccionar('uvw_Sap_tbl_ListaPrecios', '*');

// Empleado de ventas. SMM, 21/03/2024 
$SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*', "Estado = 'Y'", 'DE_EmpVentas');

// Stiven Muñoz Murillo, 29/08/2022
$row_encode = isset($row) ? json_encode($row) : "";
$cadena = isset($row) ? "JSON.parse('$row_encode'.replace(/\\n|\\r/g, ''))" : "'Not Found'";
// echo "<script> console.log('consulta principal'); </script>";
// echo "<script> console.log($cadena); </script>";

// SMM, 17/02/2023
// echo base64_decode($_GET["id"]) . "<br>";
// echo base64_decode($_GET["id_portal"]);
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include_once "includes/cabecera.php"; ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Traslado de inventario | <?php echo NOMBRE_PORTAL; ?></title>
	<?php
	if (isset($_GET['a']) && $_GET['a'] == base64_encode("OK_TrasInvAdd")) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Listo!',
				text: 'El traslado de inventario ha sido creado exitosamente.',
				icon: 'success'
			});
		});
		</script>";
	}
	if (isset($_GET['a']) && $_GET['a'] == base64_encode("OK_TrasInvUpd")) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Listo!',
				text: 'El traslado de inventario ha sido actualizado exitosamente.',
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
                text: '" . preg_replace('/\s+/', ' ', LSiqmlObs($msg_error)) . "',
                icon: 'warning'
            });
			console.log('json:','" . ($Cabecera_json ?? "") . "');
		});
		</script>";
	}
	?>
	<!-- InstanceEndEditable -->
	<!-- InstanceBeginEditable name="head" -->
	<style>
		.panel-body {
			padding: 0px !important;
		}

		.tabs-container .panel-body {
			padding: 0px !important;
		}

		.nav-tabs>li>a {
			padding: 14px 20px 14px 25px !important;
		}

		.bootstrap-maxlength {
			background-color: black;
			z-index: 9999999;
		}

		.swal2-container {
			z-index: 9999999 !important;
		}
	</style>

	<script>
		function ConsultarDatosCliente() {
			var Cliente = document.getElementById('CardCode');
			if (Cliente.value != "") {
				self.name = 'opener';
				remote = open('socios_negocios.php?id=' + Base64.encode(Cliente.value) + '&ext=1&tl=1', 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
				remote.focus();
			}
		}
		function AbrirFirma(IDCampo) {
			var posicion_x;
			var posicion_y;
			posicion_x = (screen.width / 2) - (1200 / 2);
			posicion_y = (screen.height / 2) - (500 / 2);
			self.name = 'opener';
			remote = open('popup_firma.php?id=' + Base64.encode(IDCampo), 'remote', "width=1200,height=500,location=no,scrollbars=yes,menubars=no,toolbars=no,resizable=no,fullscreen=no,directories=no,status=yes,left=" + posicion_x + ",top=" + posicion_y + "");
			remote.focus();
		}

		// SMM, 30/11/2022
		function verAutorizacion() {
			$('#modalAUT').modal('show');
		}
	</script>

	<script type="text/javascript">
		$(document).ready(function () {//Cargar los combos dependiendo de otros
			$("#CardCode").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);

				var frame = document.getElementById('DataGrid');
				var carcode = document.getElementById('CardCode').value;

				// Inicio, buscar lista precio SN.
				let cardcode = carcode;
				document.cookie = `cardcode=${cardcode}`;

				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: {
						type: 45,
						id: cardcode
					},
					dataType: 'json',
					success: function (data) {
						console.log("Line 891", data);

						document.getElementById('IdListaPrecio').value = data.IdListaPrecio;
						$('#IdListaPrecio').trigger('change');

						// document.getElementById('Exento').value = data.SujetoImpuesto;
					},
					error: function (error) {
						// console.log("Linea 693", error.responseText);
						console.log("El cliente no tiene IdListaPrecio");

						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
				// Fin, buscar lista precio SN.

				<?php if ($edit == 0 && $dt_DR == 0 && $dt_SS == 0 && $sw_error == 0) { ?>
					$.ajax({
						type: "POST",
						url: "includes/procedimientos.php?type=7&objtype=67&cardcode=" + carcode
					});
				<?php } ?>

				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=2&id=" + carcode,
					success: function (response) {
						$('#ContactoCliente').html(response).fadeIn();
					}
					, error: function (error) {
						console.log("724->", error.responseText);
					}
				});

				<?php if ($dt_SS == 0) { //Para que no recargue las listas cuando vienen de una solicitud de salida. ?>
					$.ajax({
						type: "POST",
						url: "ajx_cbo_select.php?type=3&tdir=S&id=" + carcode,
						success: function (response) {
							$('#SucursalDestino').html(response).fadeIn();

							<?php if (($edit == 0) && ($ClienteDefault != "")) { ?>
								$("#SucursalDestino").val("<?php echo $SucursalDestinoDefault; ?>");
							<?php } ?>

							$('#SucursalDestino').trigger('change');
						}
						, error: function (error) {
							console.log("736->", error.responseText);
						}
					});

					$.ajax({
						type: "POST",
						url: "ajx_cbo_select.php?type=3&tdir=B&id=" + carcode,
						success: function (response) {
							$('#SucursalFacturacion').html(response).fadeIn();

							<?php if (($edit == 0) && ($ClienteDefault != "")) { ?>
								$("#SucursalFacturacion").val("<?php echo $SucursalFacturacionDefault; ?>");
							<?php } ?>

							$('#SucursalFacturacion').trigger('change');
						}
						, error: function (error) {
							console.log("749->", error.responseText);
						}
					});

					// Recargar condición de pago.
					$.ajax({
						type: "POST",
						url: "ajx_cbo_select.php?type=7&id=" + carcode,
						success: function (response) {
							$('#CondicionPago').html(response).fadeIn();
						}
						, error: function (error) {
							console.log("760->", error.responseText);
						}
					});
				<?php } ?>

				// SMM, 23/01/2023
				<?php if (isset($_GET['a'])) { ?>
					frame.src = "detalle_traslado_inventario.php";
				<?php } else { ?>
					// Se debe esperar a que se elimine la información de la tabla temporal antes de cargar el detalle. 20/02/2024
					setTimeout(() => {
						// Antiguo fragmento de código
						<?php if ($edit == 0) { ?>
							if (carcode != "") {
								frame.src = "detalle_traslado_inventario.php?id=0&type=1&usr=<?php echo $_SESSION['CodUser']; ?>&cardcode=" + carcode;
							} else {
								frame.src = "detalle_traslado_inventario.php";
							}
						<?php } else { ?>
							if (carcode != "") {
								frame.src = "detalle_traslado_inventario.php?id=<?php echo base64_encode($row['ID_TrasladoInv']); ?>&evento=<?php echo base64_encode($row['IdEvento']); ?>&docentry=<?php echo base64_encode($row['DocEntry']); ?>&type=2";
							} else {
								frame.src = "detalle_traslado_inventario.php";
							}
						<?php } ?>
						// Hasta aquí
					}, 500);
				<?php } ?>

				$('.ibox-content').toggleClass('sk-loading', false);
			});

			$("#SucursalDestino").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);

				var Cliente = document.getElementById('CardCode').value;
				var Sucursal = document.getElementById('SucursalDestino').value;

				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: { type: 3, CardCode: Cliente, Sucursal: Sucursal },
					dataType: 'json',
					success: function (data) {
						document.getElementById('DireccionDestino').value = data.Direccion;

						$('.ibox-content').toggleClass('sk-loading', false);
					}
					, error: function (error) {
						console.log("798->", error.responseText);

						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
			});

			$("#SucursalFacturacion").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);

				var Cliente = document.getElementById('CardCode').value;
				var Sucursal = document.getElementById('SucursalFacturacion').value;

				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: { type: 3, CardCode: Cliente, Sucursal: Sucursal },
					dataType: 'json',
					success: function (data) {
						document.getElementById('DireccionFacturacion').value = data.Direccion;

						$('.ibox-content').toggleClass('sk-loading', false);
					}
					, error: function (error) {
						console.log("820->", error.responseText);

						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
			});
		});
	</script>
	<!-- InstanceEndEditable -->
</head>

<body>

	<div id="wrapper">

		<?php include_once "includes/menu.php"; ?>

		<div id="page-wrapper" class="gray-bg">
			<?php include_once "includes/menu_superior.php"; ?>
			<!-- InstanceBeginEditable name="Contenido" -->
			<div class="row wrapper border-bottom white-bg page-heading">
				<div class="col-sm-8">
					<h2>Traslado de inventario</h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">Inventario</a>
						</li>
						<li class="active">
							<strong>Traslado de inventario</strong>
						</li>
					</ol>
				</div>
			</div>

			<div class="wrapper wrapper-content">
				<!-- SMM, 27/06/2023 -->
				<div class="modal inmodal fade" id="mdLoteArticulos" tabindex="1" role="dialog" aria-hidden="true"
					data-backdrop="static" data-keyboard="false">
				</div>

				<!-- SMM, 24/05/2023 -->
				<div class="modal inmodal fade" id="mdArticulos" tabindex="1" role="dialog" aria-hidden="true"
					data-backdrop="static" data-keyboard="false">
				</div>

				<!-- SMM, 29/08/2022 -->
				<?php include_once 'md_consultar_llamadas_servicios.php'; ?>

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
												<i onClick="ConsultarDatosClienteSN();" title="Consultar cliente"
													style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i>
												Cliente <span class="text-danger">*</span>
											</label>
											<input type="hidden" id="ClienteSN" name="ClienteSN">
											<input type="text" class="form-control" id="NombreClienteSN"
												name="NombreClienteSN" placeholder="Digite para buscar..." required>
										</div>
										<div class="col-lg-5">
											<label class="control-label">Contacto</label>
											<select class="form-control select2" id="ContactoSN" name="ContactoSN">
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
											<select class="form-control select2" id="SucursalSN" name="SucursalSN">
												<option value="">Seleccione...</option>
											</select>
										</div>
										<div class="col-lg-5">
											<label class="control-label">Dirección</label>
											<input type="text" class="form-control" id="DireccionSN" name="DireccionSN"
												maxlength="100">
										</div>
										<div class="col-lg-1"></div>
									</div>
								</div>

								<div class="modal-footer">
									<button type="submit" class="btn btn-success m-t-md"><i class="fa fa-check"></i>
										Aceptar</button>
									<button type="button" class="btn btn-secondary m-t-md CancelarSN"
										data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
								</div>
							</form>
						</div>
					</div>
				</div>
				<!-- Fin, modalSN -->

				<!-- Inicio, modalAUT. SMM, 30/11/2022 -->
				<?php if (($edit == 1) || ($success == 0) || ($sw_error == 1) || $debug_Condiciones) { ?>
					<div class="modal inmodal fade" id="modalAUT" tabindex="-1" role="dialog" aria-hidden="true">
						<div class="modal-dialog modal-lg">
							<div class="modal-content">
								<div class="modal-header">
									<h4 class="modal-title">Autorización de documento</h4>
								</div>

								<!-- form id="formAUT" -->
								<div class="modal-body">
									<div class="ibox-content">
										<div class="form-group">
											<label class="col-lg-2">Motivo <span class="text-danger">*</span></label>
											<div class="col-lg-10">
												<input required type="hidden" form="CrearEntregaVenta" class="form-control"
													name="IdMotivoAutorizacion" id="IdMotivoAutorizacion"
													value="<?php echo $IdMotivo; ?>">
												<input readonly type="text" style="color: black; font-weight: bold;"
													class="form-control" id="MotivoAutorizacion"
													value="<?php echo $motivoAutorizacion; ?>">
											</div>
										</div>
										<br><br><br>
										<div class="form-group">
											<label class="col-lg-2">Mensaje proceso</label>
											<div class="col-lg-10">
												<textarea readonly form="CrearEntregaVenta"
													style="color: black; font-weight: bold;" class="form-control"
													name="MensajeProceso" id="MensajeProceso" type="text" maxlength="250"
													rows="4"><?php if ($mensajeProceso != "") {
														echo $mensajeProceso;
													} elseif ($edit == 1 || $sw_error == 1) {
														echo $row['ComentariosMotivo'];
													} ?></textarea>
											</div>
										</div>
										<br><br><br>
										<br><br><br>
										<div class="form-group">
											<label class="col-lg-2">Comentarios autor <span
													class="text-danger">*</span></label>
											<div class="col-lg-10">
												<textarea <?php if ($edit == 1) {
													echo "readonly";
												} ?>
													form="CrearEntregaVenta" class="form-control required"
													name="ComentariosAutor" id="ComentariosAutor" type="text"
													maxlength="250" rows="4"><?php if ($edit == 1 || $sw_error == 1) {
														echo $row['ComentariosAutor'];
													} elseif (isset($_GET['ComentariosAutor'])) {
														echo base64_decode($_GET['ComentariosAutor']);
													} ?></textarea>
											</div>
										</div>
										<br><br><br>

										<!-- Inicio, Componente Fecha y Hora -->
										<br><br><br>
										<div class="form-group">
											<div class="row">
												<label class="col-lg-6 control-label"
													style="text-align: left !important;">Fecha y hora decisión SAP
													B1</label>
											</div>
											<div class="row">
												<div class="col-lg-6 input-group date">
													<span class="input-group-addon"><i
															class="fa fa-calendar"></i></span><input readonly
														name="FechaAutorizacion" type="text" autocomplete="off"
														class="form-control" id="FechaAutorizacion" value="<?php if (isset($row_Autorizaciones['FechaAutorizacion_SAPB1']) && ($row_Autorizaciones['FechaAutorizacion_SAPB1']->format('Y-m-d') != "1900-01-01")) {
															echo $row_Autorizaciones['FechaAutorizacion_SAPB1']->format('Y-m-d');
														} ?>" placeholder="YYYY-MM-DD">
												</div>
												<div class="col-lg-6 input-group clockpicker" data-autoclose="true">
													<input readonly name="HoraAutorizacion" id="HoraAutorizacion"
														type="text" autocomplete="off" class="form-control" value="<?php if (isset($row_Autorizaciones['HoraAutorizacion_SAPB1'])) {
															echo $row_Autorizaciones['HoraAutorizacion_SAPB1'];
														} ?>" placeholder="hh:mm">
													<span class="input-group-addon">
														<span class="fa fa-clock-o"></span>
													</span>
												</div>
											</div>
										</div>
										<!-- Fin, Componente Fecha y Hora -->

										<br>
										<div class="form-group">
											<label class="col-lg-2">Decisión</label>
											<div class="col-lg-10">
												<?php if (isset($row_Autorizaciones['EstadoAutorizacion'])) { ?>
													<input type="text" class="form-control" name="IdEstadoAutorizacion"
														id="IdEstadoAutorizacion" readonly
														value="<?php echo $row_Autorizaciones['EstadoAutorizacion']; ?>"
														style="font-weight: bold; color: white; background-color: <?php echo $row_Autorizaciones['ColorEstadoAutorizacion']; ?>;">
												<?php } else { ?>
													<input type="text" class="form-control" name="IdEstadoAutorizacion"
														id="IdEstadoAutorizacion" readonly>
												<?php } ?>
											</div>
										</div>
										<br><br><br>
										<div class="form-group">
											<label class="col-lg-2">Usuario autorizador</label>
											<div class="col-lg-10">
												<?php if (isset($row_Autorizaciones['IdUsuarioAutorizacion_SAPB1'])) { ?>
													<input type="text" class="form-control" name="IdUsuarioAutorizacion"
														id="IdUsuarioAutorizacion" readonly
														value="<?php echo $row_Autorizaciones['NombreUsuarioAutorizacion_SAPB1']; ?>">
												<?php } else { ?>
													<input type="text" class="form-control" name="IdUsuarioAutorizacion"
														id="IdUsuarioAutorizacion" readonly>
												<?php } ?>
											</div>
										</div>
										<br><br><br>
										<div class="form-group">
											<label class="col-lg-2">Comentarios autorizador</label>
											<div class="col-lg-10">
												<textarea readonly type="text" maxlength="200" rows="4" class="form-control"
													name="ComentariosAutorizador" id="ComentariosAutorizador"><?php if (isset($row_Autorizaciones['ComentariosAutorizador_SAPB1'])) {
														echo $row_Autorizaciones['ComentariosAutorizador_SAPB1'];
													} ?></textarea>
											</div>
										</div>
										<br><br><br><br>
									</div>
								</div>

								<div class="modal-footer">
									<?php if ($edit == 0) { ?>
										<button type="button" class="btn btn-success m-t-md" id="formAUT_button"><i
												class="fa fa-check"></i> Enviar</button>
									<?php } ?>
									<button type="button" class="btn btn-warning m-t-md" data-dismiss="modal"><i
											class="fa fa-times"></i> Cerrar</button>
								</div>
								<!-- /form -->
							</div>
						</div>
					</div>
				<?php } ?>
				<!-- Fin, modalAUT. SMM, 30/11/2022 -->

				<!-- Campos de auditoria de documento. SMM, 23/12/2022 -->
				<?php if ($edit == 1) { ?>
					<div class="row">
						<div class="col-lg-3">
							<div class="ibox ">
								<div class="ibox-title">
									<h5><span class="font-normal">Creada por</span></h5>
								</div>
								<div class="ibox-content">
									<h3 class="no-margins"><?php if (isset($row['CDU_UsuarioCreacion']) && ($row['CDU_UsuarioCreacion'] != "")) {
										echo $row['CDU_UsuarioCreacion'];
									} else {
										echo "&nbsp;";
									} ?></h3>
								</div>
							</div>
						</div>
						<div class="col-lg-3">
							<div class="ibox ">
								<div class="ibox-title">
									<h5><span class="font-normal">Fecha creación</span></h5>
								</div>
								<div class="ibox-content">
									<h3 class="no-margins">
										<?php echo (isset($row['CDU_FechaHoraCreacion']) && ($row['CDU_FechaHoraCreacion'] != "")) ? $row['CDU_FechaHoraCreacion']->format('Y-m-d H:i') : "&nbsp;"; ?>
									</h3>
								</div>
							</div>
						</div>
						<div class="col-lg-3">
							<div class="ibox ">
								<div class="ibox-title">
									<h5><span class="font-normal">Actualizado por</span></h5>
								</div>
								<div class="ibox-content">
									<h3 class="no-margins"><?php if (isset($row['CDU_UsuarioActualizacion']) && ($row['CDU_UsuarioActualizacion'] != "")) {
										echo $row['CDU_UsuarioActualizacion'];
									} else {
										echo "&nbsp;";
									} ?></h3>
								</div>
							</div>
						</div>
						<div class="col-lg-3">
							<div class="ibox ">
								<div class="ibox-title">
									<h5><span class="font-normal">Fecha actualización</span></h5>
								</div>
								<div class="ibox-content">
									<h3 class="no-margins">
										<?php echo (isset($row['CDU_FechaHoraActualizacion']) && ($row['CDU_FechaHoraActualizacion'] != "")) ? $row['CDU_FechaHoraActualizacion']->format('Y-m-d H:i') : "&nbsp;"; ?>
									</h3>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
				<!-- Hasta aquí. SMM, 23/12/2022 -->

				<?php if ($edit == 1) { ?>
					<div class="row">
						<div class="col-lg-12">
							<div class="ibox-content">
								<?php include "includes/spinner.php"; ?>

								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-plus-square"></i> Acciones</h3>
									</label>
								</div>

								<div class="form-group">
									<div class="col-lg-6">
										<!-- SMM, 22/02/2023 -->
										<div class="btn-group">
											<button data-toggle="dropdown"
												class="btn btn-outline btn-success dropdown-toggle"><i
													class="fa fa-download"></i> Descargar formato <i
													class="fa fa-caret-down"></i></button>
											<ul class="dropdown-menu">
												<?php $SQL_Formato = Seleccionar('uvw_tbl_FormatosSAP', '*', "ID_Objeto=67 AND (IdFormato='" . $row['IdSeries'] . "' OR DeSeries IS NULL) AND VerEnDocumento='Y' AND (EsBorrador='N' OR EsBorrador IS NULL)"); ?>
												<?php while ($row_Formato = sqlsrv_fetch_array($SQL_Formato)) { ?>
													<li>
														<a class="dropdown-item" target="_blank"
															href="sapdownload.php?type=<?php echo base64_encode('2'); ?>&id=<?php echo base64_encode('15'); ?>&ObType=<?php echo base64_encode($row_Formato['ID_Objeto']); ?>&IdFrm=<?php echo base64_encode($row_Formato['IdFormato']); ?>&DocKey=<?php echo base64_encode($row['DocEntry']); ?>&IdReg=<?php echo base64_encode($row_Formato['ID']); ?>"><?php echo $row_Formato['NombreVisualizar']; ?></a>
													</li>
												<?php } ?>
											</ul>
										</div>
										<!-- Hasta aquí, 22/02/2023 -->

										<a href="#" class="btn btn-info btn-outline"
											onClick="VerMapaRel('<?php echo base64_encode($row['DocEntry']); ?>','<?php echo base64_encode('67'); ?>');"><i
												class="fa fa-sitemap"></i> Mapa de relaciones</a>
									</div>
									<div class="col-lg-6">
										<?php if ($row['DocDestinoDocEntry'] != "") { ?>
											<a href="salida_inventario.php?id=<?php echo base64_encode($row['DocDestinoDocEntry']); ?>&id_portal=<?php echo base64_encode($row['DocDestinoIdPortal']); ?>&tl=1"
												target="_blank" class="btn btn-outline btn-success pull-right m-l-sm">Ir a
												documento destino <i class="fa fa-mail-forward"></i></a>
										<?php } ?>
										<?php if ($row['DocBaseDocEntry'] != "") { ?>
											<a href="solicitud_traslado.php?id=<?php echo base64_encode($row['DocBaseDocEntry']); ?>&id_portal=<?php echo base64_encode($row['DocBaseIdPortal']); ?>&tl=1"
												target="_blank" class="btn btn-outline btn-success pull-right m-l-sm"><i
													class="fa fa-mail-reply"></i> Ir a documento base</i></a>
										<?php } ?>
										<button type="button"
											onClick="javascript:location.href='actividad.php?dt_DM=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&DM_type=<?php echo base64_encode('67'); ?>&DM=<?php echo base64_encode($row['DocEntry']); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('traslado_inventario.php'); ?>'"
											class="alkin btn btn-outline btn-primary pull-right"><i
												class="fa fa-plus-circle"></i> Agregar actividad</button>
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
							<form action="traslado_inventario.php" method="post" class="form-horizontal"
								enctype="multipart/form-data" id="CrearTrasladoInventario">
								<?php
								$_GET['obj'] = "67";
								include_once 'md_frm_campos_adicionales.php';
								?>
								<div class="form-group">
									<label class="col-md-8 col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-user"></i> Información de
											cliente</h3>
									</label>
									<label class="col-md-4 col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-calendar"></i> Fechas de
											documento</h3>
									</label>
								</div>
								<div class="col-lg-8">
									<div class="form-group">
										<label class="col-lg-1 control-label"><i onClick="ConsultarDatosCliente();"
												title="Consultar cliente" style="cursor: pointer"
												class="btn-xs btn-success fa fa-search"></i> Cliente <span
												class="text-danger">*</span></label>
										<div class="col-lg-9">
											<input name="CardCode" type="hidden" id="CardCode" value="<?php if (($edit == 1) || ($sw_error == 1)) {
												echo $row['CardCode'];
											} elseif ($dt_SS == 1) {
												echo $row_Cliente['CodigoCliente'];
											} elseif (($edit == 0) && ($ClienteDefault != "")) {
												echo $ClienteDefault;
											} ?>">

											<input autocomplete="off" name="CardName" type="text" required
												class="form-control" id="CardName" placeholder="Digite para buscar..."
												value="<?php if (($edit == 1) || ($sw_error == 1)) {
													echo $row['NombreCliente'];
												} elseif ($dt_SS == 1) {
													echo $row_Cliente['NombreCliente'];
												} elseif (($edit == 0) && ($ClienteDefault != "")) {
													echo $NombreClienteDefault;
												} ?>" <?php if ((($edit == 1) && ($row['Cod_Estado'] == 'C')) || ($dt_SS == 1) || ($edit == 1)) {
													 echo "readonly";
												 } ?>>
										</div>
									</div>

									<div class="form-group">
										<label class="col-lg-1 control-label">Contacto <span
												class="text-danger">*</span></label>
										<div class="col-lg-5">
											<select name="ContactoCliente" class="form-control" id="ContactoCliente"
												required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<option value="">Seleccione...</option>
												<?php
												if ($edit == 1 || $sw_error == 1) {
													while ($row_ContactoCliente = sqlsrv_fetch_array($SQL_ContactoCliente)) { ?>
														<option value="<?php echo $row_ContactoCliente['CodigoContacto']; ?>"
															<?php if ((isset($row['CodigoContacto'])) && (strcmp($row_ContactoCliente['CodigoContacto'], $row['CodigoContacto']) == 0)) {
																echo "selected";
															} ?>>
															<?php echo $row_ContactoCliente['ID_Contacto']; ?></option>
													<?php }
												} ?>
											</select>
										</div>

										<!-- Inicio, Lista Precios SN -->
										<label class="col-lg-1 control-label">Lista Precios
											<!--span class="text-danger">*</span--></label>
										<div class="col-lg-5">
											<select class="form-control select2" name="IdListaPrecio" id="IdListaPrecio"
												<?php if (!PermitirFuncion(718)) {
													echo "disabled";
												} ?>>
												<?php while ($row_ListaPrecio = sqlsrv_fetch_array($SQL_ListaPrecios)) { ?>
													<option <?php if (isset($row['IdListaPrecio']) && ($row_ListaPrecio['IdListaPrecio'] == $row['IdListaPrecio'])) {
														echo "selected";
													} ?>
														value="<?php echo $row_ListaPrecio['IdListaPrecio']; ?>">
														<?php echo $row_ListaPrecio['DeListaPrecio']; ?>
													</option>
												<?php } ?>
											</select>
										</div>
										<!-- Fin, Lista Precios SN -->
									</div>

									<div class="form-group">
										<label class="col-lg-1 control-label">Sucursal destino <span
												class="text-danger">*</span></label>
										<div class="col-lg-5">
											<select class="form-control select2" name="SucursalDestino"
												id="SucursalDestino" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<?php if (($edit == 0) && ($dt_SS == 0)) { ?>
													<option value="">Seleccione...</option>
												<?php } ?>

												<?php if (($edit == 1) || ($sw_error == 1) || ($dt_SS == 1)) { ?>
													<optgroup label='Dirección de destino'></optgroup>
													<?php while ($row_SucursalDestino = sqlsrv_fetch_array($SQL_SucursalDestino)) { ?>
														<option value="<?php echo $row_SucursalDestino['NombreSucursal']; ?>"
															<?php if ((isset($row['SucursalDestino'])) && (strcmp($row_SucursalDestino['NombreSucursal'], $row['SucursalDestino']) == 0)) {
																echo "selected";
															} elseif (isset($_GET['Sucursal']) && (strcmp($row_SucursalDestino['NombreSucursal'], base64_decode($_GET['Sucursal'])) == 0)) {
																echo "selected";
															} elseif (isset($_GET['Sucursal']) && (strcmp(LSiqmlObs($row_SucursalDestino['NombreSucursal']), base64_decode($_GET['Sucursal'])) == 0)) {
																echo "selected";
															} elseif ($ShipToDef == $row_SucursalDestino['NombreSucursal']) {
																echo "selected";
															} elseif ($FiltrarDest == 1) {
																echo "selected";
															} ?>>
															<?php echo $row_SucursalDestino['NombreSucursal']; ?>
														</option>
													<?php } ?>
												<?php } ?>
											</select>
										</div>
										<label class="col-lg-1 control-label">Sucursal facturación <span
												class="text-danger">*</span></label>
										<div class="col-lg-5">
											<select class="form-control select2" name="SucursalFacturacion"
												id="SucursalFacturacion" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<?php if (($edit == 0) && ($dt_SS == 0)) { ?>
													<option value="">Seleccione...</option>
												<?php } ?>

												<?php if (($edit == 1) || ($sw_error == 1) || ($dt_SS == 1)) { ?>
													<optgroup label='Dirección de facturas'></optgroup>
													<?php while ($row_SucursalFacturacion = sqlsrv_fetch_array($SQL_SucursalFacturacion)) { ?>
														<option
															value="<?php echo $row_SucursalFacturacion['NombreSucursal']; ?>"
															<?php if ((isset($row['SucursalFacturacion'])) && (strcmp($row_SucursalFacturacion['NombreSucursal'], $row['SucursalFacturacion']) == 0)) {
																echo "selected";
															} elseif (isset($_GET['SucursalFact']) && (strcmp($row_SucursalFacturacion['NombreSucursal'], base64_decode($_GET['SucursalFact'])) == 0)) {
																echo "selected";
															} elseif (isset($_GET['SucursalFact']) && (strcmp(LSiqmlObs($row_SucursalFacturacion['NombreSucursal']), base64_decode($_GET['SucursalFact'])) == 0)) {
																echo "selected";
															} elseif ($BillToDef == $row_SucursalFacturacion['NombreSucursal']) {
																echo "selected";
															} elseif ($FiltrarFact == 1) {
																echo "selected";
															} ?>>
															<?php echo $row_SucursalFacturacion['NombreSucursal']; ?>
														</option>
													<?php } ?>
												<?php } ?>
											</select>
										</div>
									</div>

									<div class="form-group">
										<label class="col-lg-1 control-label">Dirección destino</label>
										<div class="col-lg-5">
											<input type="text" class="form-control" name="DireccionDestino"
												id="DireccionDestino" value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DireccionDestino'];
												} elseif ($dt_SS == 1 && isset($_GET['Direccion'])) {
													echo base64_decode($_GET['Direccion']);
												} ?>" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													 echo "readonly";
												 } ?>>
										</div>
										<label class="col-lg-1 control-label">Dirección facturación</label>
										<div class="col-lg-5">
											<input type="text" class="form-control" name="DireccionFacturacion"
												id="DireccionFacturacion" value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DireccionFacturacion'];
												} ?>" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													 echo "readonly";
												 } ?>>
										</div>
									</div>

									<!-- SMM, 29/08/2022 -->
									<div class="form-group">
										<label
											class="col-lg-1 control-label"><?php if (($edit == 1) && ($row['ID_LlamadaServicio'] != 0)) { ?><a
													href="llamada_servicio.php?id=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&tl=1"
													target="_blank" title="Consultar Llamada de servicio"
													class="btn-xs btn-success fa fa-search"></a> <?php } ?>Orden
											servicio</label>
										<div class="col-lg-7">
											<input type="hidden" class="form-control" name="OrdenServicioCliente"
												id="OrdenServicioCliente" value="<?php if (isset($row_OrdenServicioCliente['ID_LlamadaServicio']) && ($row_OrdenServicioCliente['ID_LlamadaServicio'] != 0)) {
													echo $row_OrdenServicioCliente['ID_LlamadaServicio'];
												} ?>">
											<input readonly type="text" class="form-control"
												name="Desc_OrdenServicioCliente" id="Desc_OrdenServicioCliente"
												placeholder="Haga clic en el botón" value="<?php if (isset($row_OrdenServicioCliente['ID_LlamadaServicio']) && ($row_OrdenServicioCliente['ID_LlamadaServicio'] != 0)) {
													echo $row_OrdenServicioCliente['DocNum'] . " - " . $row_OrdenServicioCliente['AsuntoLlamada'] . " (" . $row_OrdenServicioCliente['DeTipoLlamada'] . ")";
												} ?>">
										</div>
										<div class="col-lg-4">
											<button class="btn btn-success" type="button"
												onClick="$('#mdOT').modal('show');"><i class="fa fa-refresh"></i>
												Cambiar orden servicio</button>
										</div>
									</div>
									<!-- Hasta aquí -->
								</div>
								<div class="col-lg-4">
									<div class="form-group">
										<label class="col-lg-5">Número</label>
										<div class="col-lg-7">
											<input type="text" name="DocNum" id="DocNum" class="form-control" value="<?php if ($edit == 1 || $sw_error == 1) {
												echo $row['DocNum'];
											} ?>" readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-5">Fecha de contabilización <span
												class="text-danger">*</span></label>
										<div class="col-lg-7 input-group date">
											<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
												name="DocDate" type="text" required class="form-control" id="DocDate"
												value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DocDate'];
												} else {
													echo date('Y-m-d');
												} ?>" readonly="readonly" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													 echo "readonly";
												 } ?>>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-5">Fecha de requerida salida <span
												class="text-danger">*</span></label>
										<div class="col-lg-7 input-group date">
											<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
												name="DocDueDate" type="text" required class="form-control"
												id="DocDueDate" value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DocDueDate'];
												} else {
													echo date('Y-m-d');
												} ?>" readonly="readonly" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													 echo "readonly";
												 } ?>>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-5">Fecha del documento <span
												class="text-danger">*</span></label>
										<div class="col-lg-7 input-group date">
											<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
												name="TaxDate" type="text" required class="form-control" id="TaxDate"
												value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['TaxDate'];
												} else {
													echo date('Y-m-d');
												} ?>" readonly="readonly" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													 echo "readonly";
												 } ?>>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-5">Estado <span class="text-danger">*</span></label>
										<div class="col-lg-7">
											<select name="EstadoDoc" class="form-control" id="EstadoDoc" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
												echo "disabled";
											} ?>>
												<?php while ($row_EstadoDoc = sqlsrv_fetch_array($SQL_EstadoDoc)) { ?>
													<option value="<?php echo $row_EstadoDoc['Cod_Estado']; ?>" <?php if (($edit == 1) && (isset($row['Cod_Estado'])) && (strcmp($row_EstadoDoc['Cod_Estado'], $row['Cod_Estado']) == 0)) {
														   echo "selected";
													   } ?>><?php echo $row_EstadoDoc['NombreEstado']; ?>
													</option>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>

								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-info-circle"></i> Datos del
											traslado</h3>
									</label>
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Serie</label>
									<div class="col-lg-3">
										<select name="Serie" class="form-control" id="Serie" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
											<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
												<option value="<?php echo $row_Series['IdSeries']; ?>" <?php if (($edit == 1 || $sw_error == 1) && (isset($row['IdSeries'])) && (strcmp($row_Series['IdSeries'], $row['IdSeries']) == 0)) {
													   echo "selected";
												   } ?>><?php echo $row_Series['DeSeries']; ?></option>
											<?php } ?>
										</select>
									</div>

									<label class="col-lg-1 control-label">Referencia</label>
									<div class="col-lg-3">
										<input type="text" name="Referencia" id="Referencia" class="form-control" value="<?php if ($edit == 1) {
											echo $row['NumAtCard'];
										} ?>" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											 echo "readonly";
										 } ?>>
									</div>

									<!-- Inicio, TipoEntrega -->
									<label class="col-lg-1 control-label">Tipo entrega <span
											class="text-danger">*</span></label>
									<div class="col-lg-3">
										<select name="TipoEntrega" class="form-control select2" id="TipoEntrega" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>
											required>
											<option value="">Seleccione...</option>

											<?php while ($row_TipoEntrega = sqlsrv_fetch_array($SQL_TipoEntrega)) { ?>
												<option value="<?php echo $row_TipoEntrega['IdTipoEntrega']; ?>" <?php if (isset($row['IdTipoEntrega']) && ($row['IdTipoEntrega'] == $row_TipoEntrega['IdTipoEntrega'])) {
													   echo "selected";
												   } elseif (isset($_GET['IdTipoEntrega']) && ($_GET['IdTipoEntrega'] == $row_TipoEntrega['IdTipoEntrega'])) {
													   echo "selected";
												   } ?>>
													<?php echo $row_TipoEntrega['DeTipoEntrega'] ?? ""; ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<!-- Hasta aquí -->
								</div> <!-- form-group -->

								<div class="form-group">
									<label class="col-lg-1 control-label">Año entrega</label>
									<div class="col-lg-3">
										<select name="AnioEntrega" class="form-control" id="AnioEntrega" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
											<?php while ($row_AnioEntrega = sqlsrv_fetch_array($SQL_AnioEntrega)) { ?>
												<option value="<?php echo $row_AnioEntrega['IdAnioEntrega']; ?>" <?php if (isset($row['IdAnioEntrega']) && ($row['IdAnioEntrega'] == $row_AnioEntrega['IdAnioEntrega'])) {
													   echo "selected";
												   } elseif (isset($_GET['IdAnioEntrega']) && ($_GET['IdAnioEntrega'] == $row_AnioEntrega['IdAnioEntrega'])) {
													   echo "selected";
												   } elseif (date('Y') == $row_AnioEntrega['DeAnioEntrega']) {
													   echo "selected";
												   } ?>>
													<?php echo $row_AnioEntrega['DeAnioEntrega'] ?? ""; ?>
												</option>
											<?php } ?>
										</select>
									</div>

									<label class="col-lg-1 control-label">Entrega descontable</label>
									<div class="col-lg-3">
										<select name="EntregaDescont" class="form-control" id="EntregaDescont" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
											<option value="NO" <?php if (isset($row['Descontable']) && ($row['Descontable'] == "NO")) {
												echo "selected";
											} elseif (isset($_GET['Descontable']) && ($_GET['Descontable'] == "NO")) {
												echo "selected";
											} ?>>NO</option>
											<option value="SI" <?php if (isset($row['Descontable']) && ($row['Descontable'] == "SI")) {
												echo "selected";
											} elseif (isset($_GET['Descontable']) && ($_GET['Descontable'] == "SI")) {
												echo "selected";
											} ?>>SI</option>
										</select>
									</div>

									<label class="col-lg-1 control-label">Cant cuota</label>
									<div class="col-lg-3">
										<input type="text" class="form-control" name="ValorCuotaDesc"
											id="ValorCuotaDesc" onKeyPress="return justNumbers(event,this.value);"
											value="<?php echo $row['ValorCuotaDesc'] ?? ($_GET["ValorCuotaDesc"] ?? ""); ?>"
											<?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
												echo "readonly";
											} ?>>
									</div>
								</div> <!-- form-group -->

								<div class="form-group">
									<!-- SMM, 29/08/2022 -->
									<label class="col-lg-1 control-label">Condición de pago</label>
									<div class="col-lg-3">
										<select name="CondicionPago" class="form-control" id="CondicionPago" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
											<option value="">Seleccione...</option>

											<?php while ($row_CondicionPago = sqlsrv_fetch_array($SQL_CondicionPago)) { ?>
												<option value="<?php echo $row_CondicionPago['IdCondicionPago']; ?>" <?php if (isset($row['IdCondicionPago']) && ($row['IdCondicionPago'] == $row_CondicionPago['IdCondicionPago'])) {
													   echo "selected";
												   } elseif (isset($_GET['CondicionPago']) && (base64_decode($_GET['CondicionPago']) == $row_CondicionPago['IdCondicionPago'])) {
													   echo "selected";
												   } ?>>
													<?php echo $row_CondicionPago['NombreCondicion'] ?? ""; ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<!-- Hasta aquí -->

									<!-- Inicio, Proyecto -->
									<label class="col-lg-1 control-label">Proyecto <span
											class="text-danger">*</span></label>
									<div class="col-lg-3">
										<select id="PrjCode" name="PrjCode" class="form-control select2" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
											<option value="">(NINGUNO)</option>

											<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
												<option value="<?php echo $row_Proyecto['IdProyecto']; ?>" <?php if (isset($row['PrjCode']) && ($row['PrjCode'] == $row_Proyecto['IdProyecto'])) {
													   echo "selected";
												   } elseif (isset($_GET['Proyecto']) && ($row_Proyecto['IdProyecto'] == base64_decode($_GET['Proyecto']))) {
													   echo "selected";
												   } elseif ($FiltroPrj == $row_Proyecto['IdProyecto']) {
													   echo "selected";
												   } ?>>
													<?php echo $row_Proyecto['IdProyecto'] . "-" . $row_Proyecto['DeProyecto']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<!-- Fin, Proyecto -->

									<!-- SMM, 30/11/2022 -->
									<label class="col-lg-1 control-label">
										Autorización
										<?php if ((isset($row_Autorizaciones['IdEstadoAutorizacion']) && ($edit == 1)) || ($success == 0) || ($sw_error == 1) || $debug_Condiciones) { ?>
											<i onClick="verAutorizacion();" title="Ver Autorización" style="cursor: pointer"
												class="btn-xs btn-success fa fa-eye"></i>
										<?php } ?>
									</label>
									<div class="col-lg-3">
										<select name="Autorizacion" class="form-control" id="Autorizacion" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
											<?php while ($row_EstadoAuth = sqlsrv_fetch_array($SQL_EstadoAuth)) { ?>
												<option value="<?php echo $row_EstadoAuth['IdAuth']; ?>" <?php if (($edit == 1 || $sw_error == 1) && (isset($row['AuthPortal'])) && (strcmp($row_EstadoAuth['IdAuth'], $row['AuthPortal']) == 0)) {
													   echo "selected";
												   } elseif (isset($row_Autorizaciones['IdEstadoAutorizacion']) && ($row_Autorizaciones['IdEstadoAutorizacion'] == 'Y') && ($row_EstadoAuth['IdAuth'] == 'Y')) {
													   echo "selected";
												   } elseif (isset($row_Autorizaciones['IdEstadoAutorizacion']) && ($row_Autorizaciones['IdEstadoAutorizacion'] == 'W') && ($row_EstadoAuth['IdAuth'] == 'P')) {
													   echo "selected";
												   } elseif (($edit == 0 && $sw_error == 0) && ($row_EstadoAuth['IdAuth'] == 'N')) {
													   echo "selected";
												   } ?>>
													<?php echo $row_EstadoAuth['DeAuth']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<!-- Hasta aquí, 30/11/2022 -->
								</div> <!-- form-group -->

								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-list"></i> Contenido del
											traslado</h3>
									</label>
								</div>

								<div class="form-group">
									<div class="col-lg-4">
										<!-- SMM, 30/05/2023 -->
										<button <?php if ((($edit == 1) && ($row['Cod_Estado'] == 'C')) || (!PermitirFuncion(1204))) {
											echo "disabled";
										} ?> class="btn btn-success"
											type="button" onclick="AgregarArticulos();"><i class="fa fa-plus"></i>
											Agregar artículo</button>

										<!-- SMM, 27/06/2023 -->
										<button <?php if ((($edit == 1) && ($row['Cod_Estado'] == 'C')) || (!PermitirFuncion(1204))) {
											echo "disabled";
										} ?> class="btn btn-warning"
											style="margin-left: 20px;" type="button" onclick="ActualizarArticulos();"><i
												class="fa fa-refresh"></i>
											Actualización en lote</button>
									</div>

									<!-- SMM, 04/05/2022 -->
									<?php $filtro_consulta = "LineNum NoLinea, ItemCode IdArticulo, ItemName DeArticulo, Quantity Cantidad,
	UnitMsr UnidadMedida, WhsCode IdAlmacen, WhsName DeAlmacen, OnHand Stock, Price Precio, PriceTax PrecioConIva,
	TarifaIVA, VatSum IVATotalLinea, DiscPrcnt PorcenDescuento, LineTotal TotalLinea, CDU_AreasControladas AreasControladas,
	OcrCode IdDimension1, OcrCode2 IdDimension2, OcrCode3 IdDimension3, OcrCode4 IdDimension4, OcrCode5 IdDimension5, PrjCode IdProyecto"; ?>

									<?php $cookie_cardcode = 0; ?>
									<?php if ($edit == 1) { ?>
										<?php $ID_TrasladoInv = $row['ID_TrasladoInv'] ?? ""; ?>
										<?php $Evento = $row['IdEvento']; ?>
										<?php $consulta_detalle = "SELECT $filtro_consulta FROM uvw_tbl_SolicitudCompraDetalle WHERE ID_TrasladoInv='$ID_TrasladoInv' AND IdEvento='$Evento' AND Metodo <> 3"; ?>
									<?php } else { ?>
										<?php $Usuario = $_SESSION['CodUser']; ?>
										<?php $cookie_cardcode = 1; ?>
										<?php $consulta_detalle = "SELECT $filtro_consulta FROM uvw_tbl_SolicitudCompraDetalleCarrito WHERE Usuario='$Usuario'"; ?>
									<?php } ?>

									<div class="col-lg-1 pull-right">
										<a
											href="exportar_excel.php?exp=20&cookie_cardcode=<?php echo $cookie_cardcode; ?>&Cons=<?php echo base64_encode($consulta_detalle); ?>">
											<img src="css/exp_excel.png" width="50" height="30" alt="Exportar a Excel"
												title="Exportar a Excel" />
										</a>
									</div>
								</div>

								<div class="tabs-container">
									<ul class="nav nav-tabs">
										<li class="active"><a data-toggle="tab" href="#tab-1"><i class="fa fa-list"></i>
												Contenido</a></li>
										<?php if ($edit == 1) { ?>
											<li><a data-toggle="tab" href="#tab-2" onClick="ConsultarTab('2');"><i
														class="fa fa-calendar"></i> Actividades</a></li><?php } ?>
										<li><a data-toggle="tab" href="#tab-3"><i class="fa fa-paperclip"></i>
												Anexos</a></li>
										<li><span class="TimeAct">
												<div id="TimeAct">&nbsp;</div>
											</span></li>
										<span class="TotalItems"><strong>Total Items:</strong>&nbsp;<input type="text"
												name="TotalItems" id="TotalItems" class="txtLimpio" value="0" size="1"
												readonly></span>
									</ul>
									<div class="tab-content">
										<div id="tab-1" class="tab-pane active">
											<iframe id="DataGrid" name="DataGrid" style="border: 0;" width="100%"
												height="300" src="<?php if ($edit == 0 && $sw_error == 0) {
													echo "detalle_traslado_inventario.php";
												} elseif ($edit == 0 && $sw_error == 1) {
													echo "detalle_traslado_inventario.php?id=0&type=1&usr=" . $_SESSION['CodUser'] . "&cardcode=" . $row['CardCode'];
												} else {
													echo "detalle_traslado_inventario.php?id=" . base64_encode($row['ID_TrasladoInv']) . "&evento=" . base64_encode($row['IdEvento']) . "&docentry=" . base64_encode($row['DocEntry']) . "&type=2&status=" . base64_encode($row['Cod_Estado']);
												} ?>"></iframe>
										</div>
										<?php if ($edit == 1) { ?>
											<div id="tab-2" class="tab-pane">
												<div id="dv_actividades" class="panel-body">

												</div>
											</div>
										<?php } ?>
							</form>
							<div id="tab-3" class="tab-pane">
								<div class="panel-body">
									<?php if ($edit == 1) {
										LimpiarDirTemp();
										if ($row['IdAnexo'] != 0) { ?>
											<div class="form-group">
												<div class="col-xs-12">
													<?php while ($row_Anexo = sqlsrv_fetch_array($SQL_Anexo)) {
														$Icon = IconAttach($row_Anexo['FileExt']);
														$tmp = substr($row_Anexo['NombreArchivo'], 0, 4);
														if ($tmp == "Sig_") {
															$NameFirma = $row_Anexo['NombreArchivo'];
														} ?>
														<div class="file-box">
															<div class="file">
																<a href="attachdownload.php?file=<?php echo base64_encode($row_Anexo['AbsEntry']); ?>&line=<?php echo base64_encode($row_Anexo['Line']); ?>"
																	target="_blank">
																	<div class="icon">
																		<i class="<?php echo $Icon; ?>"></i>
																	</div>
																	<div class="file-name">
																		<?php echo $row_Anexo['NombreArchivo']; ?>
																		<br />
																		<small><?php echo $row_Anexo['Fecha']; ?></small>
																	</div>
																</a>
															</div>
														</div>
													<?php } ?>
												</div>
											</div>
										<?php } else {
											echo "<p>Sin anexos.</p>";
										}
									} elseif ($edit == 0) {
										LimpiarDirTemp(); ?>
										<div class="row">
											<form action="upload.php" class="dropzone" id="dropzoneForm"
												name="dropzoneForm">
												<div class="fallback">
													<input name="File" id="File" type="file" form="dropzoneForm" />
												</div>
											</form>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
					<form id="frm" action="" class="form-horizontal">
						<div class="form-group">&nbsp;</div>
						<div class="col-lg-8">
							<div class="form-group">
								<label class="col-lg-2">Encargado del departamento <span
										class="text-danger">*</span></label>
								<div class="col-lg-5">
									<select name="EmpleadoVentas" class="form-control" id="EmpleadoVentas"
										form="CrearTrasladoInventario" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
										<?php while ($row_EmpleadosVentas = sqlsrv_fetch_array($SQL_EmpleadosVentas)) { ?>
											<option value="<?php echo $row_EmpleadosVentas['ID_EmpVentas']; ?>" <?php if ($edit == 0) {
												   if (($_SESSION['CodigoEmpVentas'] != "") && (strcmp($row_EmpleadosVentas['ID_EmpVentas'], $_SESSION['CodigoEmpVentas']) == 0)) {
													   echo "selected";
												   }
											   } elseif ($edit == 1) {
												   if (($row['SlpCode'] != "") && (strcmp($row_EmpleadosVentas['ID_EmpVentas'], $row['SlpCode']) == 0)) {
													   echo "selected";
												   }
											   } ?>><?php echo $row_EmpleadosVentas['DE_EmpVentas']; ?>
											</option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-2">Comentarios</label>
								<div class="col-lg-10">
									<textarea type="text" maxlength="2000" name="Comentarios"
										form="CrearTrasladoInventario" rows="4" id="Comentarios" class="form-control"
										<?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "readonly";
										} ?>><?php if ($edit == 1 || $sw_error == 1) {
											 echo $row['Comentarios'];
										 } elseif (isset($_GET['Comentarios'])) {
											 echo base64_decode($_GET['Comentarios']);
										 } ?></textarea>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-2">Información adicional</label>
								<div class="col-lg-10">
									<button class="btn btn-success" type="button" id="DatoAdicionales"
										onClick="VerCamposAdi();"><i class="fa fa-list"></i> Ver campos
										adicionales</button>
								</div>
							</div>
						</div>
						<div class="col-lg-4">
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Subtotal</strong></label>
								<div class="col-lg-5">
									<input type="text" name="SubTotal" form="CrearTrasladoInventario" id="SubTotal"
										class="form-control" style="text-align: right; font-weight: bold;" value="<?php if ($edit == 1) {
											echo number_format($row['SubTotal'], 0);
										} else {
											echo "0.00";
										} ?>" readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Descuentos</strong></label>
								<div class="col-lg-5">
									<input type="text" name="Descuentos" form="CrearTrasladoInventario" id="Descuentos"
										class="form-control" style="text-align: right; font-weight: bold;" value="<?php if ($edit == 1) {
											echo number_format($row['DiscSum'], 0);
										} else {
											echo "0.00";
										} ?>" readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">IVA</strong></label>
								<div class="col-lg-5">
									<input type="text" name="Impuestos" form="CrearTrasladoInventario" id="Impuestos"
										class="form-control" style="text-align: right; font-weight: bold;" value="<?php if ($edit == 1) {
											echo number_format($row['VatSum'], 0);
										} else {
											echo "0.00";
										} ?>" readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Redondeo</strong></label>
								<div class="col-lg-5">
									<input type="text" name="Redondeo" form="CrearTrasladoInventario" id="Redondeo"
										class="form-control" style="text-align: right; font-weight: bold;" value="0.00"
										readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Total</strong></label>
								<div class="col-lg-5">
									<input type="text" name="TotalTraslado" form="CrearTrasladoInventario"
										id="TotalTraslado" class="form-control"
										style="text-align: right; font-weight: bold;" value="<?php if ($edit == 1) {
											echo number_format($row['DocTotal'], 0);
										} else {
											echo "0.00";
										} ?>" readonly>
								</div>
							</div>
						</div>

						<!-- SMM, 16/07/2023 -->
						<?php if ($edit == 1) { ?>
							<div class="col-lg-12">
								<div class="form-group">
									<div class="col-lg-6 border-bottom ">
										<label class="control-label text-danger">Información de quien recibe</label>
									</div>
								</div>

								<?php // if (PermitirFuncion(1213)) { ?>
								<div class="form-group">
									<div class="col-lg-5">
										<label class="control-label">Nombre de quien recibe <span
												class="text-danger cierre-span">*</span></label>
										<input form="CrearTrasladoInventario" autocomplete="off" name="NombreRecibeFirma"
											type="text" class="form-control cierre-input" id="NombreRecibeFirma"
											maxlength="200" value="<?php echo $row['NombreRecibeFirma'] ?? ""; ?>" required
											<?php if ($NameFirma != "") {
												echo "readonly";
											} ?>>
									</div>
									<div class="col-lg-5">
										<label class="control-label">Cédula de quien recibe</label>
										<input form="CrearTrasladoInventario" autocomplete="off" name="CedulaRecibeFirma"
											type="number" class="form-control cierre-input" id="CedulaRecibeFirma"
											maxlength="20" value="<?php echo $row['CedulaRecibeFirma'] ?? ""; ?>" <?php if ($NameFirma != "") {
													 echo "readonly";
												 } ?>>
									</div>
								</div>
								<?php // } ?>

								<!-- Componente "firma"-->
								<div class="form-group">
									<label class="col-lg-2">Firma de quien recibe</label>
									<?php if ($NameFirma != "") { ?>
										<div class="col-lg-10">
											<span class="badge badge-primary">Firmado</span>
										</div>
									<?php } elseif ($PuedeFirmar == 1) {
										LimpiarDirTempFirma(); ?>
										<div class="col-lg-5">
											<button class="btn btn-primary" type="button" id="FirmaCliente"
												onClick="AbrirFirma('SigRecibe');"><i class="fa fa-pencil-square-o"></i>
												Realizar firma</button>
											<input type="hidden" id="SigRecibe" name="SigRecibe" value=""
												form="CrearTrasladoInventario" />
											<div id="msgInfoSigRecibe" style="display: none;" class="alert alert-info"><i
													class="fa fa-info-circle"></i> El documento ya ha sido firmado.</div>
										</div>
										<div class="col-lg-5">
											<img id="ImgSigRecibe" style="display: none; max-width: 100%; height: auto;" src=""
												alt="" />
										</div>
									<?php } else { ?>
										<div class="col-lg-10">
											<span class="badge badge-warning">Usuario no autorizado para firmar</span>
										</div>
									<?php } ?>
								</div>
								<!-- Hasta aquí -->
								<br><br>
							</div>
						<?php } ?>
						<!-- Hasta aquí, 16/07/2023 -->

						<div class="form-group">
							<div class="col-lg-9">
								<?php if ($edit == 0 && PermitirFuncion(1203)) { ?>
									<button class="btn btn-primary" type="submit" form="CrearTrasladoInventario"
										id="Crear"><i class="fa fa-check"></i> Crear Traslado</button>
								<?php } elseif (($edit == 1) && (($row['Cod_Estado'] == "O" && PermitirFuncion(1203)) || (($NameFirma == "") && ($PuedeFirmar == 1)))) { ?>
									<button class="btn btn-warning" type="submit" form="CrearTrasladoInventario"
										id="Actualizar"><i class="fa fa-refresh"></i> Actualizar Traslado</button>
								<?php } ?>
								<?php
								if (isset($_GET['return'])) {
									$return = base64_decode($_GET['pag']) . "?" . base64_decode($_GET['return']);
								} elseif (isset($_POST['return'])) {
									$return = base64_decode($_POST['return']);
								} else {
									$return = "traslado_inventario.php?";
								}
								$return = QuitarParametrosURL($return, array("a"));
								?>
								<a href="<?php echo $return; ?>" class="btn btn-outline btn-default"><i
										class="fa fa-arrow-circle-o-left"></i> Regresar</a>
							</div>

							<?php if (($edit == 1) && ($row['DocDestinoDocEntry'] == "") && ($NameFirma != "")) { ?>
								<div class="col-lg-3">
									<div class="btn-group pull-right">
										<button data-toggle="dropdown" class="btn btn-success dropdown-toggle"><i
												class="fa fa-mail-forward"></i> Copiar a <i
												class="fa fa-caret-down"></i></button>
										<ul class="dropdown-menu">
											<li><a class="alkin dropdown-item"
													href="salida_inventario.php?dt_TI=1&DocEntry=<?php echo base64_encode($row['DocEntry']); ?>&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['ToWhsCode']); ?>&AlmacenDestino=<?php echo base64_encode($row['ToWhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['CodEmpleado']); ?>&IdTipoEntrega=<?php echo $row['IdTipoEntrega'] ?? ""; ?>&IdAnioEntrega=<?php echo $row['IdAnioEntrega'] ?? ""; ?>&Descontable=<?php echo $row['Descontable'] ?? ""; ?>&ValorCuotaDesc=<?php echo $row['ValorCuotaDesc'] ?? ""; ?>&TI=<?php echo base64_encode($row['ID_TrasladoInv']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&ConceptoSalida=<?php echo base64_encode($row['ConceptoSalida']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>">Salida
													de inventario</a></li>
										</ul>
									</div>
								</div>
							<?php } ?>
						</div>
						<input type="hidden" form="CrearTrasladoInventario" id="P" name="P" value="52" />
						<input type="hidden" form="CrearTrasladoInventario" id="IdTrasladoInv" name="IdTrasladoInv"
							value="<?php if ($edit == 1) {
								echo base64_encode($row['ID_TrasladoInv']);
							} ?>" />
						<input type="hidden" form="CrearTrasladoInventario" id="IdEvento" name="IdEvento" value="<?php if ($edit == 1) {
							echo base64_encode($IdEvento);
						} ?>" />
						<input type="hidden" form="CrearTrasladoInventario" id="tl" name="tl"
							value="<?php echo $edit; ?>" />
						<input type="hidden" form="CrearTrasladoInventario" id="dt_SS" name="dt_SS"
							value="<?php echo $dt_SS; ?>" />
						<input type="hidden" form="CrearTrasladoInventario" id="swError" name="swError"
							value="<?php echo $sw_error; ?>" />
						<input type="hidden" form="CrearTrasladoInventario" id="return" name="return"
							value="<?php echo base64_encode($return); ?>" />
					</form>
				</div>
			</div>
		</div>
	</div>
	<!-- InstanceEndEditable -->
	<?php include_once "includes/footer.php"; ?>

	</div>
	</div>
	<?php include_once "includes/pie.php"; ?>
	<!-- InstanceBeginEditable name="EditRegion4" -->
	<script>
		$(document).ready(function () {
			maxLength('Comentarios'); // SMM, 17/02/2023

			// SMM, 20/01/2023
			<?php if (($edit == 0) && ($ClienteDefault != "")) { ?>
				$("#CardCode").change();
			<?php } ?>

			$("#CrearTrasladoInventario").validate({
				submitHandler: function (form) {
					if (Validar()) {
						Swal.fire({
							title: "¿Está seguro que desea guardar los datos?",
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
					} else {
						$('.ibox-content').toggleClass('sk-loading', false);
					}
				}
			});

			// Mostrar modal NO se cumplen las condiciones. SMM, 30/11/2022
			<?php if ($success == 0) { ?>
				$('#modalAUT').modal('show');
			<?php } ?>
			// Hasta aquí, 30/11/2022

			// Almacenar campos de autorización. SMM, 30/11/2022
			$("#formAUT_button").on("click", function (event) {
				// event.preventDefault(); // Evitar redirección del formulario

				let incompleto = false;
				$('.required').each(function () {
					if ($(this).val() == null || $(this).val() == "") {
						incompleto = true;
					}
				});

				if (incompleto) {
					Swal.fire({
						"title": "¡Advertencia!",
						"text": "Aún tiene campos sin completar.",
						"icon": "warning"
					});
				} else {
					Swal.fire({
						"title": "¡Listo!",
						"text": "Puede continuar con la creación del documento.",
						"icon": "success"
					});

					// Cambiar estado de autorización a pendiente.
					if ($("#Autorizacion").val() == "N") {
						$("#Autorizacion").val("P").change();

						// Corregir valores nulos en el combo de autorización.
						$('#Autorizacion option:selected').attr('disabled', false);
						$('#Autorizacion option:not(:selected)').attr('disabled', true);
					}
					$('#modalAUT').modal('hide');
				}
			});
			// Almacenar campos autorización, hasta aquí.

			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});
			<?php if ((($edit == 1) && ($row['Cod_Estado'] == 'O') || ($edit == 0))) { ?>
				$('#DocDate').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					autoclose: true,
					format: 'yyyy-mm-dd',
					todayHighlight: true,
					startDate: '<?php echo date('Y-m-d'); ?>'
				});
				$('#DocDueDate').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					autoclose: true,
					format: 'yyyy-mm-dd',
					todayHighlight: true,
					startDate: '<?php echo date('Y-m-d'); ?>'
				});
				$('#TaxDate').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					autoclose: true,
					format: 'yyyy-mm-dd',
					todayHighlight: true,
					startDate: '<?php echo date('Y-m-d'); ?>'
				});
			<?php } ?>
			//$('.chosen-select').chosen({width: "100%"});
			$(".select2").select2();

			<?php if ($edit == 1) { ?>
				$('#Serie option:not(:selected)').attr('disabled', true);
			<?php } ?>

			// $('#Autorizacion option:not(:selected)').attr('disabled',true);

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
						var value = $("#CardName").getSelectedItemData().CodigoCliente;
						$("#CardCode").val(value).trigger("change");
					}
				}
			};

			<?php if ($edit == 0) { ?>
				$("#CardName").easyAutocomplete(options);
			<?php } ?>

			<?php if ($dt_SS == 1) { ?>
				$('#CardCode').trigger('change');

				// SMM, 01/12/2022
				$('#SucursalFacturacion').trigger('change');
			<?php } ?>

			<?php if ($edit == 0) { ?>
				$('#Serie').trigger('change');
			<?php } ?>
		});
	</script>

	<script>
		//Variables de tab
		var tab_2 = 0;

		function ConsultarTab(type) {
			if (type == 2) {//Actividades
				if (tab_2 == 0) {
					$('.ibox-content').toggleClass('sk-loading', true);
					$.ajax({
						type: "POST",
						url: "dm_actividades.php?id=<?php if ($edit == 1) {
							echo base64_encode($row['DocEntry']);
						} ?>&objtype=67",
						success: function (response) {
							$('#dv_actividades').html(response).fadeIn();
							$('.ibox-content').toggleClass('sk-loading', false);
							tab_2 = 1;
						}
					});
				}
			}
		}
	</script>

	<script>
		function Validar() {
			var result = true;
			var TotalItems = document.getElementById("TotalItems");

			<?php if ($edit == 0) { ?>
				//Validar que los items con lote ya fueron seleccionados
				var Cliente = document.getElementById('CardCode').value;

				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: {
						type: 43,
						cardcode: Cliente
					},
					dataType: 'json',
					async: false,
					success: function (data) {
						if (data.Estado == '0') {
							result = false;
							Swal.fire({
								title: data.Title,
								text: data.Mensaje,
								icon: data.Icon,
							});
						}
					}
				});
			<?php } ?>

			if (TotalItems.value == "0") {
				result = false;
				Swal.fire({
					title: '¡Lo sentimos!',
					text: 'No puede guardar el documento sin contenido. Por favor verifique.',
					icon: 'error'
				});
			}

			return result;
		}

		// SMM, 24/05/2023
		function AgregarArticulos() {
			let probarModal = false;
			let OrdenServicio = $("#OrdenServicioCliente").val();

			let serie = $("#Serie").val();
			let proyecto = $("#PrjCode").val();
			let cardCode = $("#CardCode").val();
			let listaPrecio = $("#IdListaPrecio").val();
			let empleado = $("#EmpleadoVentas").val();

			if (((cardCode != "") && (serie != "")) || probarModal) {
				$.ajax({
					type: "POST",
					url: "md_consultar_articulos.php",
					data: {
						ObjType: 67,
						OT: OrdenServicio,
						Edit: <?php echo $edit; ?>,
						DocType: "<?php echo ($edit == 0) ? 11 : 12; ?>",
						DocId: "<?php echo $row['ID_TrasladoInv'] ?? 0; ?>",
						DocEvent: "<?php echo $row['IdEvento'] ?? 0; ?>",
						CardCode: cardCode,
						IdSeries: serie,
						IdProyecto: proyecto,
						ListaPrecio: listaPrecio,
						TipoDoc: 3, // Inventario
						IdEmpleado: empleado,
						Inventario: "Traslado"
					},
					success: function (response) {
						$("#mdArticulos").html(response);
						$("#mdArticulos").modal("show");
					}
				});
			} else {
				Swal.fire({
					title: "¡Advertencia!",
					text: "Debe seleccionar un Cliente y una Serie.",
					icon: "warning",
					confirmButtonText: "OK"
				});
			}
		}

		// SMM, 27/06/2023
		function ActualizarArticulos() {
			let probarModal = false;
			let totalItems = parseInt(document.getElementById('TotalItems').value);

			let serie = $("#Serie").val();
			let proyecto = $("#PrjCode").val();
			let cardCode = $("#CardCode").val();
			let listaPrecio = $("#IdListaPrecio").val();
			let empleado = $("#EmpleadoVentas").val();

			if (((cardCode != "") && (serie != "") && (totalItems > 0)) || probarModal) {
				$.ajax({
					type: "POST",
					url: "md_actualizar_articulos.php",
					data: {
						Edit: <?php echo $edit; ?>,
						DocType: "<?php echo 6; ?>",
						DocId: "<?php echo $row['ID_TrasladoInv'] ?? 0; ?>",
						DocEvent: "<?php echo $row['IdEvento'] ?? 0; ?>",
						CardCode: cardCode,
						IdSeries: serie,
						IdProyecto: proyecto,
						ListaPrecio: listaPrecio,
						IdEmpleado: empleado,
						Inventario: "Traslado"
					},
					success: function (response) {
						$('#mdLoteArticulos').html(response);
						$("#mdLoteArticulos").modal("show");
					}
				});
			} else {
				Swal.fire({
					title: "¡Advertencia!",
					text: "Debe seleccionar un Cliente y una Serie. También debe haber al menos un artículo en el detalle del documento.",
					icon: "warning",
					confirmButtonText: "OK"
				});
			}
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

<!-- InstanceEnd -->

</html>
<?php sqlsrv_close($conexion); ?>