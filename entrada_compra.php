<?php require_once "includes/conexion.php";
PermitirAcceso(707);

$dt_LS = 0; //sw para saber si vienen datos de la llamada de servicio. 0 no vienen. 1 si vienen.
$dt_OV = 0; //sw para saber si vienen datos de una Orden de compra.

$IdMotivo = "";
$motivoAutorizacion = "";
$debug_Condiciones = false;

$success = 1; // Confirmación de autorización (1 - Autorizado / 0 - NO Autorizado), SMM 16/08/2022
$mensajeProceso = ""; // Mensaje proceso, mensaje de salida del procedimiento almacenado.

$msg_error = ""; //Mensaje del error
$IdEntrada = 0;
$IdPortal = 0; //Id del portal para las entregas que fueron creadas en el portal, para eliminar el registro antes de cargar al editar
$NameFirma = "";

$BillToDef = ""; // Sucursal de Facturación por Defecto.
$ShipToDef = ""; // Sucursal de Destino por Defecto.

// Procesos de autorización, SMM 19/08/2022
$SQL_Procesos = Seleccionar("uvw_tbl_Autorizaciones_Procesos", "*", "Estado = 'Y' AND IdTipoDocumento = 20");

if (isset($_GET['id']) && ($_GET['id'] != "")) { //ID de la Entrada de compras (DocEntry)
	$IdEntrada = base64_decode($_GET['id']);
}

if (isset($_GET['id_portal']) && ($_GET['id_portal'] != "")) { // Id del portal de venta (ID interno)
	$IdPortal = base64_decode($_GET['id_portal']);
}

if (isset($_POST['IdEntradaCompra']) && ($_POST['IdEntradaCompra'] != "")) { //Tambien el Id interno, pero lo envío cuando mando el formulario
	$IdEntradaCompra = base64_decode($_POST['IdEntradaCompra']);
	$IdEvento = base64_decode($_POST['IdEvento']);
}

if (isset($_POST['swError']) && ($_POST['swError'] != "")) { //Para saber si ha ocurrido un error.
	$sw_error = $_POST['swError'];
} else {
	$sw_error = 0;
}

// echo $_REQUEST['tl'];
if (isset($_REQUEST['tl']) && ($_REQUEST['tl'] != "")) { //0 Si se está creando. 1 Se se está editando.
	$edit = $_REQUEST['tl'];
} else {
	$edit = 0;
}

// Consulta decisión de autorización en la edición de documentos.
if ($edit == 1) {
	$DocEntry = "'" . $IdEntrada . "'";
	$EsBorrador = (false) ? "DocumentoBorrador" : "Documento";
	$SQL_Autorizaciones = Seleccionar("uvw_Sap_tbl_Autorizaciones", "*", "IdTipoDocumento = 20 AND DocEntry$EsBorrador = $DocEntry");
	$row_Autorizaciones = sqlsrv_fetch_array($SQL_Autorizaciones);

	// SMM, 19/08/2022
	$SQL_Procesos = Seleccionar("uvw_tbl_Autorizaciones_Procesos", "*", "IdTipoDocumento = 20");
}

if (isset($_POST['P']) && ($_POST['P'] != "")) { //Grabar Entrada de compras
	//*** Carpeta temporal ***
	$i = 0; //Archivos
	$RutaAttachSAP = ObtenerDirAttach();
	$dir = CrearObtenerDirTemp();
	$dir_new = CrearObtenerDirAnx("entradacompra");

	$dir_firma = CrearObtenerDirTempFirma();
	if ((isset($_POST['SigRecibe'])) && ($_POST['SigRecibe'] != "")) {
		$NombreFileFirma = base64_decode($_POST['SigRecibe']);
		$Nombre_Archivo = "Sig_" . $NombreFileFirma;
		if (!copy($dir_firma . $NombreFileFirma, $dir . $Nombre_Archivo)) {
			$sw_error = 1;
			$msg_error = "No se pudo mover la firma";
		}
	}
	// $directorio = opendir("."); //ruta actual

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
			$IdEntradaCompra = base64_decode($_POST['IdEntradaCompra']);
			$IdEvento = base64_decode($_POST['IdEvento']);
			$Type = 2;
			/*
					 if (!PermitirFuncion(716)) { //Permiso para autorizar Entrada de compras
						 $_POST['Autorizacion'] = 'P'; //Si no tengo el permiso, la Entrada queda pendiente
					 }
					 */
		} else { //Crear
			$IdEntradaCompra = "NULL";
			$IdEvento = "0";
			$Type = 1;
		}

		$ParametrosCabEntradaCompra = array(
			$IdEntradaCompra,
			$IdEvento,
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
			"'" . str_replace(',', '', $_POST['TotalEntrada']) . "'",
			"'" . LSiqmlObs($_POST['SucursalFacturacion']) . "'",
			"'" . LSiqmlObs($_POST['DireccionFacturacion']) . "'",
			"'" . LSiqmlObs($_POST['SucursalDestino']) . "'",
			"'" . LSiqmlObs($_POST['DireccionDestino']) . "'",
			"'" . $_POST['CondicionPago'] . "'",
			"'" . $_POST['PrjCode'] . "'",
			"'" . $_POST['Autorizacion'] . "'",
			"'" . ($_POST['Almacen'] ?? "") . "'",
			"'" . $_SESSION['CodUser'] . "'",
			"'" . $_SESSION['CodUser'] . "'",
			"$Type",
			"'" . ($_POST['IdMotivoAutorizacion'] ?? "") . "'",
			"'" . ($_POST['ComentariosAutor'] ?? "") . "'",
			"'" . ($_POST['MensajeProceso'] ?? "") . "'",
		);

		$SQL_CabeceraEntradaCompra = EjecutarSP('sp_tbl_EntradaCompra', $ParametrosCabEntradaCompra, $_POST['P']);
		if ($SQL_CabeceraEntradaCompra) {
			if ($Type == 1) {
				$row_CabeceraEntradaCompra = sqlsrv_fetch_array($SQL_CabeceraEntradaCompra);

				$IdEntradaCompra = $row_CabeceraEntradaCompra[0];
				$IdEvento = $row_CabeceraEntradaCompra[1];

				// Comprobar procesos de autorización en la creación, SMM 16/08/2022
				while ($row_Proceso = sqlsrv_fetch_array($SQL_Procesos)) {
					$ids_perfiles = ($row_Proceso['Perfiles'] != "") ? explode(";", $row_Proceso['Perfiles']) : [];

					if (in_array($_SESSION['Perfil'], $ids_perfiles) || (count($ids_perfiles) == 0)) {
						$sql = $row_Proceso['Condiciones'] ?? '';

						$sql = str_replace("[IdDocumento]", $IdEntradaCompra, $sql);
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

				// Consultar el motivo de autorización según el id, SMM 20/08/2022
				$SQL_Motivos = Seleccionar("uvw_tbl_Autorizaciones_Motivos", "*", "IdMotivoAutorizacion = '$IdMotivo'");
				$row_MotivoAutorizacion = sqlsrv_fetch_array($SQL_Motivos);
				$motivoAutorizacion = $row_MotivoAutorizacion['MotivoAutorizacion'] ?? "";
			} else {
				$IdEntradaCompra = base64_decode($_POST['IdEntradaCompra']); //Lo coloco otra vez solo para saber que tiene ese valor
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
							"'20'",
							"'" . $IdEntradaCompra . "'",
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
					}
					$j++;
				}
			} catch (Exception $e) {
				echo 'Excepcion capturada: ', $e->getMessage(), "\n";
			}

			// Inicio, Envio WebService
			// SMM, 31/02/2023

			// Consultar cabecera
			$SQL_Cab = Seleccionar("uvw_tbl_EntradaCompra", '*', "ID_EntradaCompra='$IdEntradaCompra' and IdEvento='$IdEvento'");
			$row_Cab = sqlsrv_fetch_array($SQL_Cab);

			// Consultar detalle
			$SQL_Det = Seleccionar("uvw_tbl_EntradaCompraDetalle", '*', "ID_EntradaCompra='$IdEntradaCompra' and IdEvento='$IdEvento'");

			//Consultar anexos
			$SQL_Anx = Seleccionar("uvw_tbl_DocumentosSAP_Anexos", '*', "ID_Documento='$IdEntradaCompra' and TipoDocumento='20' and Metodo=1");

			//Consultar Lotes
			$SQL_Lotes = Seleccionar("uvw_tbl_LotesDocSAP", '*', "DocEntry='$IdEntradaCompra' and IdEvento='$IdEvento' and ObjType='20'");

			$Detalle = array();
			$Anexos = array();
			$Lotes = array();
			$Seriales = array();

			// Detalle
			while ($row_Det = sqlsrv_fetch_array($SQL_Det)) {

				array_push($Detalle, array(
					"base_type" => (($row_Det['BaseType'] === "") || (intval($row_Det['BaseType']) === 0)) ? null : intval($row_Det['BaseType']),
					"base_entry" => (($row_Det['BaseEntry'] === "") || (intval($row_Det['BaseEntry']) === 0)) ? null : intval($row_Det['BaseEntry']),
					"base_line" => (($row_Det['BaseType'] === "") || (intval($row_Det['BaseType']) === 0)) ? null : intval($row_Det['BaseLine']),
					"line_num" => intval($row_Det['LineNum']),
					"id_tipo_articulo" => "",
					"tipo_articulo" => 0,
					"id_articulo" => $row_Det['ItemCode'],
					"articulo" => $row_Det['ItemName'],
					"unidad_medida" => $row_Det['UnitMsr'],
					"texto_libre" => $row_Det['FreeTxt'],
					"id_bodega" => $row_Det['WhsCode'],
					"cant_articulo" => intval($row_Det['Quantity']),
					"precio_articulo" => intval($row_Det['Price']),
					"dim1" => $row_Det['OcrCode'],
					"dim2" => $row_Det['OcrCode2'],
					"dim3" => $row_Det['OcrCode3'],
					"dim4" => $row_Det['OcrCode4'],
					"dim5" => $row_Det['OcrCode5'],
					"id_proyecto" => $row_Det['PrjCode'],
					"metodo_linea" => intval($row_Det['Metodo']),
					"maneja_serial" => $row_Det['ManSerNum'],
					"maneja_lote" => $row_Det['ManBtchNum'],
					"CDU_id_servicio" => $row_Det['CDU_IdServicio'],
					"CDU_id_metodo_aplicacion" => $row_Det['CDU_IdMetodoAplicacion'],
					"CDU_id_tipo_plagas" => $row_Det['CDU_IdTipoPlagas'],
					"CDU_areas_controladas" => $row_Det['CDU_AreasControladas'],
					"CDU_cant_litros" => intval($row_Det['CDU_CantLitros']),
					"CDU_dosificacion" => intval($row_Det['CDU_Dosificacion']),
					"id_empleado_ventas" => (($row_Det['EmpVentas'] === "") || (intval($row_Det['EmpVentas']) === 0)) ? null : intval($row_Det['EmpVentas']),
					"CDU_IdTipoOT" => ($row_Det['CDU_IdTipoOT'] ?? ""),
					"CDU_IdSedeEmpresa" => ($row_Det['CDU_IdSedeEmpresa'] ?? ""),
					"CDU_IdTipoCargo" => ($row_Det['CDU_IdTipoCargo'] ?? ""),
					"CDU_IdTipoProblema" => ($row_Det['CDU_IdTipoProblema'] ?? ""),
					"CDU_IdTipoPreventivo" => ($row_Det['CDU_IdTipoPreventivo'] ?? ""),
				));
			}

			//Anexos
			$i = 0;
			while ($row_Anx = sqlsrv_fetch_array($SQL_Anx)) {

				array_push($Anexos, array(
					"id_anexo" => $i,
					"tipo_documento" => intval($row_Anx['TipoDocumento']),
					"id_documento" => intval($row_Anx['ID_Documento']),
					"archivo" => $row_Anx['FileName'],
					"ext_archivo" => $row_Anx['FileExt'],
					"metodo" => intval($row_Anx['Metodo']),
					"fecha" => FormatoFechaToSAP($row_Anx['Fecha']->format('Y-m-d')),
					"id_usuario" => intval($row_Anx['ID_Usuario']),
				));
				$i++;
			}

			//Lotes
			while ($row_Lotes = sqlsrv_fetch_array($SQL_Lotes)) {

				array_push($Lotes, array(
					"id_documento" => intval($row_Lotes['DocEntry']),
					"id_linea" => intval($row_Lotes['DocLinea']),
					"id_articulo" => $row_Lotes['ItemCode'],
					"articulo" => $row_Lotes['ItemName'],
					"cantidad" => intval($row_Lotes['Cantidad']),
					"serial_lote" => $row_Lotes['DistNumber'],
					"id_systema_articulo" => 0,
				));
			}

			$Cabecera = array(
				"id_documento" => 0,
				"id_tipo_documento" => "20",
				"tipo_documento" => "Entrada de compras",
				"moneda_documento" => "$",
				"estado" => $row_Cab['Cod_Estado'],
				"id_doc_portal" => "" . $row_Cab['ID_EntradaCompra'] . "",
				"id_series" => intval($row_Cab['IdSeries']),
				"id_cliente" => $row_Cab['CardCode'],
				"cliente" => $row_Cab['NombreCliente'],
				"id_contacto_cliente" => intval($row_Cab['CodigoContacto']),
				"contacto_cliente" => $row_Cab['NombreContacto'],
				"referencia" => $row_Cab['NumAtCard'],
				"id_condicion_pago" => intval($row_Cab['IdCondicionPago']),
				"id_direccion_facturacion" => $row_Cab['SucursalFacturacion'],
				"id_direccion_destino" => $row_Cab['SucursalDestino'],
				"fecha_contabilizacion" => FormatoFechaToSAP($row_Cab['DocDate']),
				"fecha_vencimiento" => FormatoFechaToSAP($row_Cab['DocDueDate']),
				"fecha_documento" => FormatoFechaToSAP($row_Cab['TaxDate']),
				"comentarios" => $row_Cab['Comentarios'],
				"usuario" => $row_Cab['Usuario'],
				"fecha_creacion" => FormatoFechaToSAP($row_Cab['FechaRegistro']->format('Y-m-d'), $row_Cab['FechaRegistro']->format('H:i:s')),
				"hora_creacion" => FormatoFechaToSAP($row_Cab['FechaRegistro']->format('Y-m-d'), $row_Cab['FechaRegistro']->format('H:i:s')),
				"id_anexo" => 0,
				"docentry_llamada_servicio" => intval($row_Cab['ID_LlamadaServicio']),
				"docentry_documento" => 0,
				"id_llamada_servicio" => 0,
				"id_vendedor" => intval($row_Cab['SlpCode']),
				"metodo" => intval($row_Cab['Metodo']),
				"documento_destino" => "18",
				"documento_destino_borrador" => PermitirFuncion(713),
				"documentos_Lineas" => $Detalle,
				"documentos_Anexos" => $Anexos,
				"documentos_Lotes" => $Lotes,
				"documentos_Seriales" => $Seriales,
				"id_proyecto" => $row_Cab['PrjCode'], // SMM, 03/02/2023
			);

			// $Cabecera_json=json_encode($Cabecera);
			// echo $Cabecera_json;
			// exit();

			// Hasta aquí, 31/02/2023
			// Fin, Envio WebService

			// Verificar que el documento cumpla las Condiciones o este Pendiente de Autorización.
			if (($success == 1) || ($_POST['Autorizacion'] == "P")) {
				$success = 1;

				//Enviar datos al WebServices
				try {
					if ($_POST['tl'] == 0) { //Creando
						$Metodo = "EntradasCompras";
						$Resultado = EnviarWebServiceSAP($Metodo, $Cabecera, true, true);
					} else { //Editando
						$Metodo = "EntradasCompras";
						$Resultado = EnviarWebServiceSAP($Metodo, $Cabecera, true, true, "PUT");
					}

					if ($Resultado->Success == 0) {
						//InsertarLog(1, 0, 'Error al generar el informe');
						//throw new Exception('Error al generar el informe. Error de WebServices');
						$sw_error = 1;
						$msg_error = $Resultado->Mensaje;
					} else {
						if (isset($_POST['Autorizacion']) && ($_POST['Autorizacion'] == "P")) {
							header('Location:entrada_compra.php?a=' . base64_encode("OK_BorradorAdd"));
						} else {
							if ($_POST['tl'] == 0) { //Creando Entrada
								//Consultar ID creado para cargar el documento
								$SQL_ConsID = Seleccionar('uvw_Sap_tbl_EntradasCompras', 'ID_EntradaCompra', "IdDocPortal='" . $IdEntradaCompra . "'");
								$row_ConsID = sqlsrv_fetch_array($SQL_ConsID);
								sqlsrv_close($conexion);
								header('Location:entrada_compra.php?id=' . base64_encode($row_ConsID['ID_EntradaCompra']) . '&id_portal=' . base64_encode($IdEntradaCompra) . '&tl=1&a=' . base64_encode("OK_ECompAdd"));
							} else { //Actualizando entrada
								//Consultar ID creado para cargar el documento
								$SQL_ConsID = Seleccionar('uvw_Sap_tbl_EntradasCompras', 'ID_EntradaCompra', "IdDocPortal='$IdEntradaCompra'");
								$row_ConsID = sqlsrv_fetch_array($SQL_ConsID);
								sqlsrv_close($conexion);
								header('Location:entrada_compra.php?id=' . base64_encode($row_ConsID['ID_EntradaCompra']) . '&id_portal=' . base64_encode($IdEntradaCompra) . '&tl=1&a=' . base64_encode("OK_ECompUpd"));

								// ."&json=".base64_encode(json_encode($Cabecera))
								// sqlsrv_close($conexion);
								// header('Location:'.base64_decode($_POST['return']).'&a='.base64_encode("OK_ECompUpd"));
							}
						}
					}
				} catch (Exception $e) {
					echo 'Excepcion capturada: ', $e->getMessage(), "\n";
				}
			} else {
				$sw_error = 1;
				$msg_error = "Este documento necesita autorización.";
			}

		} else {
			$sw_error = 1;
			$msg_error = "Ha ocurrido un error al crear la Entrada de compras";
		}
	} catch (Exception $e) {
		echo 'Excepcion capturada: ', $e->getMessage(), "\n";
	}

}

if (isset($_GET['dt_LS']) && ($_GET['dt_LS']) == 1) { // Verificar que viene de una Llamada de servicio (Datos Llamada servicio).
	$dt_LS = 1;

	if (!isset($_GET['LMT']) && isset($_GET['ItemCode']) && $_GET['ItemCode'] != "") {
		//Consultar datos de la LMT
		$SQL_LMT = Seleccionar('uvw_Sap_tbl_ArticulosLlamadas', '*', "ItemCode='" . base64_decode($_GET['ItemCode']) . "'");
		$row_LMT = sqlsrv_fetch_array($SQL_LMT);

		//Cargar la LMT
		$ParametrosAddLMT = array(
			"'" . base64_decode($_GET['ItemCode']) . "'",
			"'" . $row_LMT['WhsCode'] . "'",
			"'" . base64_decode($_GET['Cardcode']) . "'",
			"'" . $_SESSION['CodUser'] . "'",
		);
		$SQL_AddLMT = EjecutarSP('sp_CargarLMT_EntradaCompraDetalleCarrito', $ParametrosAddLMT);
	} else {
		Eliminar('tbl_EntradaCompraDetalleCarrito', "Usuario='" . $_SESSION['CodUser'] . "' AND CardCode='" . base64_decode($_GET['Cardcode']) . "'");
	}

	// Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Proveedores', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreCliente');
	$row_Cliente = sqlsrv_fetch_array($SQL_Cliente);

	// SMM, 30/03/2024
	$BillToDef = $row_Cliente["BillToDef"] ?? "";
	$ShipToDef = $row_Cliente["ShipToDef"] ?? "";

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ProveedorContactos', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreContacto');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='B'", 'NombreSucursal');

	// Orden de servicio
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . base64_decode($_GET['LS']) . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);
}

// Inicio, Verificar que viene de una Orden de Compras
if (isset($_GET['dt_OV']) && ($_GET['dt_OV']) == 1) {
	$dt_OV = 1;

	// SMM, 30/09/2022
	$ID_Documento = "'" . base64_decode($_GET['OV']) . "'";

	$WhereAnexos = "ID_Documento=$ID_Documento";
	// echo $WhereAnexos;

	Eliminar("tbl_DocumentosSAP_Anexos", $WhereAnexos);
	// Hasta aquí, 30/09/2022

	$ParametrosCopiarOrdenToEntrada = array(
		$ID_Documento, // SMM, 30/09/2022
		"'" . base64_decode($_GET['Evento']) . "'",
		"'" . base64_decode($_GET['Almacen']) . "'",
		"'" . base64_decode($_GET['Cardcode']) . "'",
		"'" . $_SESSION['CodUser'] . "'",
	);

	$SQL_CopiarOrdenToEntrada = EjecutarSP('sp_tbl_OrdenCompraDet_To_EntradaCompraDet', $ParametrosCopiarOrdenToEntrada);
	if (!$SQL_CopiarOrdenToEntrada) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Ha ocurrido un error!',
				text: 'No se pudo copiar la Orden en Entrada de compra.',
				icon: 'error'
			});
		});
		</script>";
	}

	//Proveedores
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Proveedores', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreCliente');
	$row_Cliente = sqlsrv_fetch_array($SQL_Cliente);

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreContacto');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='B'", 'NombreSucursal');

	//Orden de servicio, SMM, 02/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . base64_decode($_GET['LS']) . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);

	// Anexos, SMM 30/09/2022
	$SQL_Anexo = Seleccionar('uvw_tbl_DocumentosSAP_Anexos', '*', $WhereAnexos);
}
// Fin, Verificar que viene de una Orden de Compras

// SMM, 07/03/2022
if (isset($_GET['dt_EC']) && ($_GET['dt_EC']) == 1) { // Verificar que viene de una Entrada de compras (Duplicar)
	$dt_OV = 1;

	$ParametrosCopiarEntradaToEntrada = array(
		"'" . base64_decode($_GET['EC']) . "'",
		"'" . base64_decode($_GET['Evento']) . "'",
		"'" . base64_decode($_GET['Almacen']) . "'",
		"'" . base64_decode($_GET['Cardcode']) . "'",
		"'" . $_SESSION['CodUser'] . "'",
	);

	$SQL_CopiarEntradaToEntrada = EjecutarSP('sp_tbl_EntradaCompraDet_To_EntradaCompraDet', $ParametrosCopiarEntradaToEntrada);
	if (!$SQL_CopiarEntradaToEntrada) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Ha ocurrido un error!',
				text: 'No se pudo duplicar la Entrada de compra.',
				icon: 'error'
			});
		});
		</script>";
	}

	//Proveedores
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Proveedores', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreCliente');
	$row_Cliente = sqlsrv_fetch_array($SQL_Cliente);

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreContacto');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='B'", 'NombreSucursal');

	//Orden de servicio, SMM, 02/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . base64_decode($_GET['LS']) . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);
}

// Empleado de compras. SMM, 29/05/2023 
$SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*', "Estado = 'Y'", 'DE_EmpVentas');

if ($edit == 1 && $sw_error == 0) {

	$ParametrosLimpiar = array(
		"'" . $IdEntrada . "'",
		"'" . $IdPortal . "'",
		"'" . $_SESSION['CodUser'] . "'",
	);
	$LimpiarEntrada = EjecutarSP('sp_EliminarDatosEntradaCompra', $ParametrosLimpiar);

	$SQL_IdEvento = sqlsrv_fetch_array($LimpiarEntrada);
	$IdEvento = $SQL_IdEvento[0];

	// Empleado de ventas. SMM, 29/05/2023 
	$SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*', '', 'DE_EmpVentas');

	//Entrada de compra
	$Cons = "Select * From uvw_tbl_EntradaCompra Where DocEntry='" . $IdEntrada . "' AND IdEvento='" . $IdEvento . "'";
	$SQL = sqlsrv_query($conexion, $Cons);
	$row = sqlsrv_fetch_array($SQL);

	// SMM, 06/09/2022
	// echo $Cons;

	//Proveedores
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Proveedores', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreCliente');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='B'", 'NombreSucursal');

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ProveedorContactos', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreContacto');

	// Orden de servicio, SMM 05/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . $row['ID_LlamadaServicio'] . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);

	//Anexos
	$SQL_Anexo = Seleccionar('uvw_Sap_tbl_DocumentosSAP_Anexos', '*', "AbsEntry='" . $row['IdAnexo'] . "'");
}

if ($sw_error == 1) {

	//Entrada de compras
	$Cons = "Select * From uvw_tbl_EntradaCompra Where ID_EntradaCompra='" . $IdEntradaCompra . "' AND IdEvento='" . $IdEvento . "'";
	$SQL = sqlsrv_query($conexion, $Cons);
	$row = sqlsrv_fetch_array($SQL);

	//Proveedores
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Proveedores', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreCliente');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='B'", 'NombreSucursal');

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ProveedorContactos', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreContacto');

	// Orden de servicio, SMM 05/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . $row['ID_LlamadaServicio'] . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);

	//Anexos
	$SQL_Anexo = Seleccionar('uvw_Sap_tbl_DocumentosSAP_Anexos', '*', "AbsEntry='" . $row['IdAnexo'] . "'");
}

// SMM, 14/10/2023
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

//Estado documento
$SQL_EstadoDoc = Seleccionar('uvw_tbl_EstadoDocSAP', '*');

//Estado autorizacion
$SQL_EstadoAuth = Seleccionar('uvw_Sap_tbl_EstadosAuth', '*');

//Series de documento
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'20'",
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Lista de precios, 24/02/2022
$SQL_ListaPrecios = Seleccionar('uvw_Sap_tbl_ListaPrecios', '*');

// Proyectos, SMM 04/03/2022
$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', '', 'DeProyecto');

// Consultar el motivo de autorización según el id, SMM 20/08/2022
if (isset($row['IdMotivoAutorizacion']) && ($row['IdMotivoAutorizacion'] != "") && ($IdMotivo == "")) {
	$IdMotivo = $row['IdMotivoAutorizacion'];
	$SQL_Motivos = Seleccionar("uvw_tbl_Autorizaciones_Motivos", "*", "IdMotivoAutorizacion = '$IdMotivo'");
	$row_MotivoAutorizacion = sqlsrv_fetch_array($SQL_Motivos);
	$motivoAutorizacion = $row_MotivoAutorizacion['MotivoAutorizacion'] ?? "";
}

// SMM, 09/02/2023
if ($edit == 0) {
	$ClienteDefault = "";
	$NombreClienteDefault = "";
	$SucursalDestinoDefault = "";
	$SucursalFacturacionDefault = "";

	if (ObtenerVariable("NITProveedorDefault") != "") {
		$ClienteDefault = ObtenerVariable("NITProveedorDefault");

		$SQL_ClienteDefault = Seleccionar('uvw_Sap_tbl_Proveedores', '*', "CodigoCliente='$ClienteDefault'");
		$row_ClienteDefault = sqlsrv_fetch_array($SQL_ClienteDefault);

		$NombreClienteDefault = $row_ClienteDefault["NombreBuscarCliente"]; // NombreCliente
		$SucursalDestinoDefault = "NEIVA";
		$SucursalFacturacionDefault = "NEIVA";
	}
}

// Stiven Muñoz Murillo, 02/03/2022
$row_encode = isset($row) ? json_encode($row) : "";
$cadena = isset($row) ? "JSON.parse('$row_encode'.replace(/\\n|\\r/g, ''))" : "'Not Found'";
// echo "<script> console.log($cadena); </script>";
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include_once "includes/cabecera.php"; ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>
		Entrada de compras | <?php echo NOMBRE_PORTAL; ?>
	</title>
	<?php
	if (isset($_GET['a']) && $_GET['a'] == base64_encode("OK_ECompAdd")) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Listo!',
				text: 'La Entrada de compras ha sido creada exitosamente.',
				icon: 'success'
			});
		});
		</script>";
	}
	if (isset($_GET['a']) && $_GET['a'] == base64_encode("OK_ECompUpd")) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Listo!',
				text: 'La Entrada de compras ha sido actualizada exitosamente.',
				icon: 'success'
			});
		});
		</script>";
	}

	// SMM, 16/08/2022
	if (isset($sw_error) && ($sw_error == 1)) {
		$error_title = ($success == 0) ? "Advertencia" : "Ha ocurrido un error";

		echo "<script>
			$(document).ready(function() {
				Swal.fire({
					title: '¡$error_title!',
					text: '" . preg_replace('/\s+/', ' ', LSiqmlObs($msg_error)) . "',
					icon: 'warning'
				});
			});
			</script>";
	}

	// SMM, 16/08/2022
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_BorradorAdd"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'El documento en borrador se ha creado exitosamente.',
                icon: 'success'
            });
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

		.nav-tabs > li > a {
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

		// SMM, 15/07/2022
		function verAutorizacion() {
			$('#modalAUT').modal('show');
		}
		
		function AbrirFirma(IDCampo){
			var posicion_x;
			var posicion_y;
			posicion_x=(screen.width/2)-(1200/2);
			posicion_y=(screen.height/2)-(500/2);
			self.name='opener';
			remote=open('popup_firma.php?id='+Base64.encode(IDCampo),'remote',"width=1200,height=500,location=no,scrollbars=yes,menubars=no,toolbars=no,resizable=no,fullscreen=no,directories=no,status=yes,left="+posicion_x+",top="+posicion_y+"");
			remote.focus();
		}
	</script>

	<script type="text/javascript">
		$(document).ready(function () { // Cargar los combos dependiendo de otros
			$("#CardCode").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);

				var frame = document.getElementById('DataGrid');
				var carcode = document.getElementById('CardCode').value;

				// Cargar contactos del cliente.
				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=2&id=" + carcode + "&pv=1",
					success: function (response) {
						$('#ContactoCliente').html(response).fadeIn();
						$('#ContactoCliente').change();
					},
					error: function (error) {
						console.log("Linea 641", error.responseText);
						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});

				// Lista de precio en el SN, SMM 20/01/2022
				let cardcode = carcode;

				// SMM, 04/05/2022
				document.cookie = `cardcode=${cardcode}`;

				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: {
						type: 45,
						id: cardcode
					},
					dataType: 'json',
					success: function (data) {
						console.log("Line 554", data);

						document.getElementById('IdListaPrecio').value = data.IdListaPrecio;
						$('#IdListaPrecio').trigger('change');

						document.getElementById('Exento').value = data.SujetoImpuesto; // SMM, 23/04/2022
					},
					error: function (error) {
						// console.log("Linea 693", error.responseText);
						console.log("El cliente no tiene IdListaPrecio");

						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});

				<?php if ($edit == 0 && $sw_error == 0 && $dt_LS == 0 && $dt_OV == 0) { // Limpiar carrito detalle. ?>
						$.ajax({
							type: "POST",
							url: "includes/procedimientos.php?type=7&objtype=20&cardcode=" + carcode
						});

						// Recargar sucursales.
						$.ajax({
							type: "POST",
							url: "ajx_cbo_select.php?type=3&tdir=S&id=" + carcode + "&pv=1",
							success: function (response) {
								$('#SucursalDestino').html(response).fadeIn();
								$('#SucursalDestino').trigger('change');
							},
							error: function (error) {
								console.log(error.responseText);
								$('.ibox-content').toggleClass('sk-loading', false);
							}
						});
						$.ajax({
							type: "POST",
							url: "ajx_cbo_select.php?type=3&tdir=B&id=" + carcode + "&pv=1",
							success: function (response) {
								$('#SucursalFacturacion').html(response).fadeIn();
								$('#SucursalFacturacion').trigger('change');
							},
							error: function (error) {
								console.log(error.responseText);
								$('.ibox-content').toggleClass('sk-loading', false);
							}
						});
				<?php } ?>

				<?php if ($edit == 0 && $sw_error == 0 && $dt_OV == 0) { // Recargar condición de pago. ?>
						$.ajax({
							type: "POST",
							url: "ajx_cbo_select.php?type=7&id=" + carcode + "&pv=1",
							success: function (response) {
								$('#CondicionPago').html(response).fadeIn();
								$('#CondicionPago').trigger("change");
							},
							error: function (error) {
								console.log(error.responseText);
								$('.ibox-content').toggleClass('sk-loading', false);
							}
						});
						// En la llamada no hay condición de pago, por lo que se carga desde el cliente.
				<?php } ?>

				// Se debe esperar a que se elimine la información de la tabla temporal antes de cargar el detalle. 20/02/2024
				setTimeout(() => {
					<?php if ($edit == 0) { ?>
						if (carcode != "") {
							frame.src = "detalle_entrada_compra.php?id=0&type=1&usr=<?php echo $_SESSION['CodUser']; ?>&cardcode=" + carcode;
						} else {
							frame.src = "detalle_entrada_compra.php";
						}
					<?php } else { ?>
						if (carcode != "") {
							frame.src = "detalle_entrada_compra.php?id=<?php echo base64_encode($row['ID_EntradaCompra']); ?>&evento=<?php echo base64_encode($row['IdEvento']); ?>&type=2&docentry=<?php echo base64_encode($row['DocEntry']); ?>";
						} else {
							frame.src = "detalle_entrada_compra.php";
						}
					<?php } ?>
				}, 500);


				$('.ibox-content').toggleClass('sk-loading', false);
			});

			$("#SucursalDestino").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);

				var Cliente = document.getElementById('CardCode').value;
				var Sucursal = document.getElementById('SucursalDestino').value;
				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: { type: 3, CardCode: Cliente, Sucursal: Sucursal, pv: 1 },
					dataType: 'json',
					success: function (data) {
						document.getElementById('DireccionDestino').value = data.Direccion;
						$('.ibox-content').toggleClass('sk-loading', false);
					},
					error: function (error) {
						// console.log("Line 657", error.responseText);
						console.log("El cliente no tiene Dirección de Destino");

						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
			}); // Fin, #SucursalDestino

			$("#SucursalFacturacion").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);

				var Cliente = document.getElementById('CardCode').value;
				var Sucursal = document.getElementById('SucursalFacturacion').value;
				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: { type: 3, CardCode: Cliente, Sucursal: Sucursal, pv: 1 },
					dataType: 'json',
					success: function (data) {
						document.getElementById('DireccionFacturacion').value = data.Direccion;
						$('.ibox-content').toggleClass('sk-loading', false);
					},
					error: function (error) {
						// console.log("Line 677", error.responseText);
						console.log("El cliente no tiene Dirección de Facturación");

						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
			}); // Fin, #SucursalFacturacion

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
					<h2>Entrada de compra</h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">Compras - Proveedores</a>
						</li>
						<li class="active">
							<strong>Entrada de compra</strong>
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

				<!-- SMM, 02/08/2022 -->
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
												name="NombreClienteSN" placeholder="Digite para buscar..."
												required="required">
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

				<!-- Inicio, modalAUT -->
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
													<input required type="hidden" form="CrearEntradaCompra" class="form-control"
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
													<textarea readonly form="CrearEntradaCompra"
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
													} ?> form="CrearEntradaCompra"
														class="form-control required" name="ComentariosAutor"
														id="ComentariosAutor" type="text" maxlength="250" rows="4"><?php if ($edit == 1 || $sw_error == 1) {
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
														style="text-align: left !important;">
														Fecha y hora decisión SAP B1
													</label>
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
				<!-- Fin, modalAUT -->

				<?php if ($edit == 1) { ?>
						<div class="row">
							<div class="col-lg-3">
								<div class="ibox ">
									<div class="ibox-title">
										<h5><span class="font-normal">Creada por</span></h5>
									</div>
									<div class="ibox-content">
										<h3 class="no-margins">
											<?php if ($row['CDU_UsuarioCreacion'] != "") {
												echo $row['CDU_UsuarioCreacion'];
											} else {
												echo "&nbsp;";
											} ?>
										</h3>
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
											<?php echo ($row['CDU_FechaHoraCreacion'] != "") ? $row['CDU_FechaHoraCreacion']->format('Y-m-d H:i') : "&nbsp;"; ?>
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
										<h3 class="no-margins">
											<?php if ($row['CDU_UsuarioActualizacion'] != "") {
												echo $row['CDU_UsuarioActualizacion'];
											} else {
												echo "&nbsp;";
											} ?>
										</h3>
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
											<?php echo ($row['CDU_FechaHoraActualizacion'] != "") ? $row['CDU_FechaHoraActualizacion']->format('Y-m-d H:i') : "&nbsp;"; ?>
										</h3>
									</div>
								</div>
							</div>
						</div>
				<?php } ?>
				<?php if ($edit == 1) { ?>
						<div class="ibox-content">
							<?php include "includes/spinner.php"; ?>
							<div class="row">
								<div class="col-lg-12 form-horizontal">
									<div class="form-group">
										<label class="col-xs-12">
											<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-plus-square"></i> Acciones</h3>
										</label>
									</div>
									<div class="form-group">
										<div class="col-lg-6">
											<!-- SMM, 06/10/2022 -->
											<div class="btn-group">
												<button data-toggle="dropdown"
													class="btn btn-outline btn-success dropdown-toggle"><i
														class="fa fa-download"></i> Descargar formato <i
														class="fa fa-caret-down"></i></button>
												<ul class="dropdown-menu">
													<?php $SQL_Formato = Seleccionar('uvw_tbl_FormatosSAP', '*', "ID_Objeto=20 AND (IdFormato='" . $row['IdSeries'] . "' OR DeSeries IS NULL) AND VerEnDocumento='Y' AND (EsBorrador='N' OR EsBorrador IS NULL)"); ?>
													<?php while ($row_Formato = sqlsrv_fetch_array($SQL_Formato)) { ?>
															<li>
																<a class="dropdown-item" target="_blank"
																	href="sapdownload.php?id=<?php echo base64_encode('15'); ?>&type=<?php echo base64_encode('2'); ?>&DocKey=<?php echo base64_encode($row['DocEntry']); ?>&ObType=<?php echo base64_encode($row_Formato['ID_Objeto']); ?>&IdFrm=<?php echo base64_encode($row_Formato['IdFormato']); ?>&IdReg=<?php echo base64_encode($row_Formato['ID']); ?>"><?php echo $row_Formato['NombreVisualizar']; ?></a>
															</li>
													<?php } ?>
												</ul>
											</div>
											<!-- Hasta aquí, 06/10/2022 -->

											<a href="#" class="btn btn-outline btn-info"
												onClick="VerMapaRel('<?php echo base64_encode($row['DocEntry']); ?>','<?php echo base64_encode('20'); ?>');"><i
													class="fa fa-sitemap"></i> Mapa de relaciones</a>
										</div>
										<div class="col-lg-6">
											<?php if ($row['DocDestinoDocEntry'] != "") { ?>
													<a href="orden_compra.php?id=<?php echo base64_encode($row['DocDestinoDocEntry']); ?>&id_portal=<?php echo base64_encode($row['DocDestinoIdPortal']); ?>&tl=1"
														target="_blank" class="btn btn-outline btn-primary pull-right">Ir a documento
														destino <i class="fa fa-external-link"></i></a>
											<?php } ?>
											<?php if ($row['Cod_Estado'] != 'C') { ?>
													<button type="button"
														onClick="javascript:location.href='actividad.php?dt_DM=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&DM_type=<?php echo base64_encode('22'); ?>&DM=<?php echo base64_encode($row['DocEntry']); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('entrada_compra.php'); ?>'"
														class="alkin btn btn-outline btn-primary pull-right m-l-xs"><i
															class="fa fa-plus-circle"></i> Agregar actividad</button>
											<?php } ?>
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
							<form action="entrada_compra.php" method="post" class="form-horizontal"
								enctype="multipart/form-data" id="CrearEntradaCompra">
								<?php
								$_GET['obj'] = "20";
								include_once 'md_frm_campos_adicionales.php';
								?>
								<div class="form-group">
									<label class="col-md-8 col-xs-12">
										<h3 class="bg-success p-xs b-r-sm">
											<i class="fa fa-user"></i> Información de proveedor
										</h3>
									</label>
									<label class="col-md-4 col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-calendar"></i> Fechas y
											estado de documento</h3>
									</label>
								</div>
								<div class="col-lg-8">
									<div class="form-group">
										<label class="col-lg-1 control-label"><i onClick="ConsultarDatosCliente();"
												title="Consultar cliente" style="cursor: pointer"
												class="btn-xs btn-success fa fa-search"></i> Proveedor <span
												class="text-danger">*</span></label>
										<div class="col-lg-9">
											<input name="CardCode" type="hidden" id="CardCode" value="<?php if (($edit == 1) || ($sw_error == 1)) {
												echo $row['CardCode'];
											} elseif ($dt_LS == 1 || $dt_OV == 1) {
												echo $row_Cliente['CodigoCliente'];
											} ?>">

											<input name="CardName" type="text" required="required" class="form-control"
												id="CardName" placeholder="Digite para buscar..." value="<?php if (($edit == 1) || ($sw_error == 1)) {
													echo $row['NombreCliente'];
												} elseif ($dt_LS == 1 || $dt_OV == 1) {
													echo $row_Cliente['NombreCliente'];
												} ?>" <?php if ($dt_LS == 1 || $dt_OV == 1 || $edit == 1) {
													 echo "readonly";
												 } ?>>
										</div>
										<div class="col-lg-2">
											<input type="hidden" id="Exento" name="Exento" class="form-control"
												readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-1 control-label">Contacto <span
												class="text-danger">*</span></label>
										<div class="col-lg-5">
											<select class="form-control select2" id="ContactoCliente"
												name="ContactoCliente" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<option value="">Seleccione...</option>
												<?php
												if ($edit == 1 || $sw_error == 1) {
													while ($row_ContactoCliente = sqlsrv_fetch_array($SQL_ContactoCliente)) { ?>
																<option value="<?php echo $row_ContactoCliente['CodigoContacto']; ?>"
																	<?php if ((isset($row['CodigoContacto'])) && (strcmp($row_ContactoCliente['CodigoContacto'], $row['CodigoContacto']) == 0)) {
																		echo "selected";
																	} ?>><?php echo $row_ContactoCliente['ID_Contacto']; ?></option>
														<?php }
												} ?>
											</select>
										</div>

										<!-- Inicio, Lista Precios SN -->
										<label class="col-lg-1 control-label">Lista Precios
											<!--span class="text-danger">*</span--></label>
										<div class="col-lg-5">
											<select class="form-control select2" name="IdListaPrecio" id="IdListaPrecio"
												<?php if (!PermitirFuncion(719)) {
													echo "disabled";
												} ?>>
												<?php while ($row_ListaPrecio = sqlsrv_fetch_array($SQL_ListaPrecios)) { ?>
														<option <?php if (isset($row['IdListaPrecio']) && ($row_ListaPrecio['IdListaPrecio'] == $row['IdListaPrecio'])) {
															echo "selected";
														} ?> value="<?php echo $row_ListaPrecio['IdListaPrecio']; ?>">
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
												id="SucursalDestino" required="required" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<option value="">Seleccione...</option>
												
												<?php if ($edit == 1 || $sw_error == 1 || $dt_LS == 1 || $dt_OV == 1) { ?>
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
												<option value="">Seleccione...</option>
												
												<?php if ($edit == 1 || $sw_error == 1 || $dt_LS == 1 || $dt_OV == 1) { ?>
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

									<div class="form-group" style="display: none;">
										<label class="col-lg-1 control-label">Dirección destino</label>
										<div class="col-lg-5">
											<input type="text" class="form-control" name="DireccionDestino"
												id="DireccionDestino" value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DireccionDestino'];
												} elseif ($dt_LS == 1) {
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
									<div class="form-group">
										<label class="col-lg-1 control-label">
											<?php if (($edit == 1) && ($row['ID_LlamadaServicio'] != 0)) { ?><a
													href="llamada_servicio.php?id=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&tl=1"
													target="_blank" title="Consultar Llamada de servicio"
													class="btn-xs btn-success fa fa-search"></a>
											<?php } ?>Orden servicio
										</label>
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
									<!-- /.form-group -->
									
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
												name="DocDate" type="text" required="required" class="form-control"
												id="DocDate" value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DocDate'];
												} else {
													echo date('Y-m-d');
												} ?>" readonly="readonly" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													 echo "readonly";
												 } ?>>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-5">Fecha de entrada/servicio <span
												class="text-danger">*</span></label>
										<div class="col-lg-7 input-group date">
											<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
												name="DocDueDate" type="text" required="required" class="form-control"
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
												name="TaxDate" type="text" required="required" class="form-control"
												id="TaxDate" value="<?php if ($edit == 1 || $sw_error == 1) {
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
											<select class="form-control select2" name="EstadoDoc" id="EstadoDoc" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
												echo "disabled";
											} ?>>
												<?php while ($row_EstadoDoc = sqlsrv_fetch_array($SQL_EstadoDoc)) { ?>
														<option value="<?php echo $row_EstadoDoc['Cod_Estado']; ?>" <?php if (($edit == 1) && (isset($row['Cod_Estado'])) && (strcmp($row_EstadoDoc['Cod_Estado'], $row['Cod_Estado']) == 0)) {
															   echo "selected";
														   } ?>><?php echo $row_EstadoDoc['NombreEstado']; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>
								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-info-circle"></i> Datos de la
											entrada</h3>
									</label>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Serie <span
											class="text-danger">*</span></label>
									<div class="col-lg-3">
										<select class="form-control select2" name="Serie" id="Serie" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
											<!-- SMM, 01/05/2022 -->
											<?php if (sqlsrv_num_rows($SQL_Series) > 1) { ?>
													<option value=''>Seleccione...</option>
											<?php } ?>

											<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
													<option value="<?php echo $row_Series['IdSeries']; ?>" <?php if (($edit == 1 || $sw_error == 1) && (isset($row['IdSeries'])) && (strcmp($row_Series['IdSeries'], $row['IdSeries']) == 0)) {
														   echo "selected";
													   } elseif (isset($_GET['Serie']) && (strcmp($row_Series['IdSeries'], base64_decode($_GET['Serie'])) == 0)) {
														   echo "selected";
													   } ?>><?php echo $row_Series['DeSeries']; ?></option>
											<?php } ?>
										</select>
									</div>
									<label class="col-lg-1 control-label">Referencia</label>
									<div class="col-lg-3">
										<input type="text" name="Referencia" id="Referencia" class="form-control" value="<?php if ($edit == 1 || $sw_error == 1) {
											echo $row['NumAtCard'];
										} ?>" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											 echo "readonly";
										 } ?>>
									</div>
									<label class="col-lg-1 control-label">Condición de pago <span
											class="text-danger">*</span></label>
									<div class="col-lg-3">
										<select class="form-control select2" name="CondicionPago" id="CondicionPago"
											required="required" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
												echo "disabled";
											} ?>>
											<option value="">Seleccione...</option>
											<?php while ($row_CondicionPago = sqlsrv_fetch_array($SQL_CondicionPago)) { ?>
													<option value="<?php echo $row_CondicionPago['IdCondicionPago']; ?>" <?php if ($edit == 1 || $sw_error == 1) {
														   if (($row['IdCondicionPago'] != "") && (strcmp($row_CondicionPago['IdCondicionPago'], $row['IdCondicionPago']) == 0)) {
															   echo "selected";
														   }
													   } elseif ((isset($_GET['CondicionPago'])) && (strcmp($row_CondicionPago['IdCondicionPago'], base64_decode($_GET['CondicionPago'])) == 0)) {
														   echo "selected";
													   } ?>><?php echo $row_CondicionPago['NombreCondicion']; ?></option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">
										Autorización
										<?php if ((isset($row_Autorizaciones['IdEstadoAutorizacion']) && ($edit == 1)) || ($success == 0) || ($sw_error == 1) || $debug_Condiciones) { ?>
												<i onClick="verAutorizacion();" title="Ver Autorización" style="cursor: pointer"
													class="btn-xs btn-success fa fa-eye"></i>
										<?php } ?>
									</label>
									<div class="col-lg-3">
										<select class="form-control select2" name="Autorizacion" id="Autorizacion" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
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

									<!-- Inicio, Proyecto -->
									<label class="col-lg-1 control-label">Proyecto <span 
									class="text-danger">*</span></label>
									
									<div class="col-lg-3">
										<select id="PrjCode" name="PrjCode" class="form-control select2" 
										form="CrearEntradaCompra" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
												<option value="">(NINGUNO)</option>

												<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
														<option value="<?php echo $row_Proyecto['IdProyecto']; ?>" <?php if ((isset($row['PrjCode']) && (!isset($_GET['Proyecto']))) && ($row_Proyecto['IdProyecto'] == $row['PrjCode'])) {
															   echo "selected";
														   } elseif (isset($_GET['Proyecto']) && ($row_Proyecto['IdProyecto'] == base64_decode($_GET['Proyecto']))) {
															   echo "selected";
														   } elseif (((!isset($row['PrjCode'])) && (!isset($_GET['Proyecto']))) && ($FiltroPrj == $row_Proyecto['IdProyecto'])) {
															   echo "selected";
														   } ?>>
														<?php echo $row_Proyecto['DeProyecto']; ?>
													</option>
											<?php } ?>
										</select>
									</div>
									<!-- Fin, Proyecto -->
								</div>

								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-list"></i> Contenido de la
											entrada</h3>
									</label>
								</div>

								<div class="form-group">

									<div class="col-lg-4">
										<!-- SMM, 30/05/2023 -->
										<button <?php if ((($edit == 1) && ($row['Cod_Estado'] == 'C')) || (!PermitirFuncion(702))) {
											echo "disabled";
										} ?> class="btn btn-success"
											type="button" onclick="AgregarArticulos();"><i class="fa fa-plus"></i>
											Agregar artículo</button>

										<!-- SMM, 27/06/2023 -->
										<button <?php if ((($edit == 1) && ($row['Cod_Estado'] == 'C')) || (!PermitirFuncion(702))) {
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
											<?php $ID_EntradaCompra = $row['ID_EntradaCompra']; ?>
											<?php $Evento = $row['IdEvento']; ?>
											<?php $consulta_detalle = "SELECT $filtro_consulta FROM uvw_tbl_EntradaCompraDetalle WHERE ID_EntradaCompra='$ID_EntradaCompra' AND IdEvento='$Evento' AND Metodo <> 3"; ?>
									<?php } else { ?>
											<?php $Usuario = $_SESSION['CodUser']; ?>
											<?php $cookie_cardcode = 1; ?>
											<?php $consulta_detalle = "SELECT $filtro_consulta FROM uvw_tbl_EntradaCompraDetalleCarrito WHERE Usuario='$Usuario'"; ?>
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
															class="fa fa-calendar"></i> Actividades</a></li>
										<?php } ?>
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
													echo "detalle_entrada_compra.php";
												} elseif ($edit == 0 && $sw_error == 1) {
													echo "detalle_entrada_compra.php?id=0&type=1&usr=" . $_SESSION['CodUser'] . "&cardcode=" . $row['CardCode'];
												} else {
													echo "detalle_entrada_compra.php?id=" . base64_encode($row['ID_EntradaCompra']) . "&evento=" . base64_encode($row['IdEvento']) . "&docentry=" . base64_encode($row['DocEntry']) . "&type=2&status=" . base64_encode($row['Cod_Estado']);
												} ?>"></iframe>
										</div>
										<?php if ($edit == 1) { ?>
												<div id="tab-2" class="tab-pane">
													<div id="dv_actividades" class="panel-body">

													</div>
												</div>
										<?php } ?>
							</form>

							<!-- Limpiar directorio temporal antes de copiar los anexos de SAP, 01/10/2022 -->
							<!-- dt_OV, también hace referencia al duplicar entrada de compra -->
							<?php if (($sw_error == 0) && ($dt_OV == 0)) {
								LimpiarDirTemp();
							} ?>

							<div id="tab-3" class="tab-pane">
								<div class="panel-body">
									<?php if (($edit == 1) || (isset($SQL_Anexo) && sqlsrv_has_rows($SQL_Anexo))) {
										if ((($edit == 1) && ($row['IdAnexo'] != 0)) || (sqlsrv_has_rows($SQL_Anexo) && ($edit == 0))) { ?>
													<div class="form-group">
														<div class="col-lg-4">
															<ul class="folder-list" style="padding: 0">
																<?php while ($row_Anexo = sqlsrv_fetch_array($SQL_Anexo)) {
																	$Icon = IconAttach($row_Anexo['FileExt']);

																	// SMM, 30/09/2022
																	$RutaAnexoSAP = ObtenerDirAttach()[0] . $row_Anexo['NombreArchivo'];
																	$RutaAnexoTemporal = CrearObtenerDirTemp() . $row_Anexo['NombreArchivo'];

																	copy($RutaAnexoSAP, $RutaAnexoTemporal);
																	?>
																		<li><a <?php if ($edit == 0) {
																			echo "disabled";
																		} else {
																			echo "href='attachdownload.php?file=" . base64_encode($row_Anexo['AbsEntry']) . "&line=" . base64_encode($row_Anexo['Line']) . "'";
																		} ?> target="_blank"
																				class="btn-link btn-xs"><i class="<?php echo $Icon; ?>"></i>
																				<?php echo $row_Anexo['NombreArchivo']; ?>
																			</a></li>
																<?php } ?>
															</ul>
														</div>
													</div>
											<?php } else {
											echo "<p>Sin anexos.</p>";
										}
									} ?>
									<div class="row">
										<form action="upload.php" class="dropzone" id="dropzoneForm"
											name="dropzoneForm">
											<div class="fallback">
												<input name="File" id="File" type="file" form="dropzoneForm" />
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
					<form id="frm" action="" class="form-horizontal">
						<div class="form-group">&nbsp;</div>
						<div class="col-lg-8">
							<div class="form-group">
								<label class="col-lg-2">Empleado de compras <span class="text-danger">*</span></label>
								<div class="col-lg-5">
									<select class="form-control select2" name="EmpleadoVentas" id="EmpleadoVentas"
										form="CrearEntradaCompra" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
										<?php while ($row_EmpleadosVentas = sqlsrv_fetch_array($SQL_EmpleadosVentas)) { ?>
												<option value="<?php echo $row_EmpleadosVentas['ID_EmpVentas']; ?>" <?php if ($edit == 0 && $sw_error == 0) {
													   if (isset($_GET['Empleado']) && (strcmp($row_EmpleadosVentas['ID_EmpVentas'], base64_decode($_GET['Empleado'])) == 0)) {
														   echo "selected";
													   } elseif (($_SESSION['CodigoEmpVentas'] != "") && (!isset($_GET['Empleado'])) && (strcmp($row_EmpleadosVentas['ID_EmpVentas'], $_SESSION['CodigoEmpVentas']) == 0)) {
														   echo "selected";
													   }
												   } elseif ($edit == 1 || $sw_error == 1) {
													   if (($row['SlpCode'] != "") && (strcmp($row_EmpleadosVentas['ID_EmpVentas'], $row['SlpCode']) == 0)) {
														   echo "selected";
													   }
												   } ?>><?php echo $row_EmpleadosVentas['DE_EmpVentas']; ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-2">Comentarios</label>
								<div class="col-lg-10">
									<textarea type="text" maxlength="2000" name="Comentarios" form="CrearEntradaCompra"
										rows="4" id="Comentarios" class="form-control" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
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
								<div class="col-lg-4">
									<button class="btn btn-success" type="button" id="DatoAdicionales"
										onClick="VerCamposAdi();"><i class="fa fa-list"></i> Ver campos
										adicionales</button>
								</div>
								<div class="col-lg-6">
									<!-- Espacio para un botón -->
								</div>
							</div>
						</div>
						<div class="col-lg-4">
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Subtotal</strong></label>
								<div class="col-lg-5">
									<input type="text" name="SubTotal" form="CrearEntradaCompra" id="SubTotal"
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
									<input type="text" name="Descuentos" form="CrearEntradaCompra" id="Descuentos"
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
									<input type="text" name="Impuestos" form="CrearEntradaCompra" id="Impuestos"
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
									<input type="text" name="Redondeo" form="CrearEntradaCompra" id="Redondeo"
										class="form-control" style="text-align: right; font-weight: bold;" value="0.00"
										readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Total</strong></label>
								<div class="col-lg-5">
									<input type="text" name="TotalEntrada" form="CrearEntradaCompra" id="TotalEntrada"
										class="form-control" style="text-align: right; font-weight: bold;" value="<?php if ($edit == 1) {
											echo number_format($row['DocTotal'], 0);
										} else {
											echo "0.00";
										} ?>" readonly>
								</div>
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-9">
								<?php if ($edit == 0 && PermitirFuncion(703)) { ?>
										<button class="btn btn-primary" type="submit" form="CrearEntradaCompra" id="Crear"><i
											class="fa fa-check"></i> Crear Entrada de compra</button>
								<?php } elseif ($row['Cod_Estado'] == "O" && PermitirFuncion(703)) { ?>
										<button class="btn btn-warning" type="submit" form="CrearEntradaCompra" id="Actualizar"><i
											class="fa fa-refresh"></i> Actualizar Entrada de compra</button>
								<?php } ?>
								<?php
								//
								if (isset($_GET['return'])) {
									$return = base64_decode($_GET['pag']) . "?" . base64_decode($_GET['return']);
								} elseif (isset($_POST['return'])) {
									$return = base64_decode($_POST['return']);
								} else {
									$return = "entrada_compra.php?" . $_SERVER['QUERY_STRING'];
								}
								$return = QuitarParametrosURL($return, array("a"));
								?>
								<a href="<?php echo $return; ?>" class="btn btn-outline btn-default"><i
									class="fa fa-arrow-circle-o-left"></i> Regresar</a>
							</div>

							<?php if (($edit == 1) && ($row['Cod_Estado'] != 'C')) { ?>
									<div class="col-lg-3">
										<div class="btn-group dropup pull-right">
											<button data-toggle="dropdown" class="btn btn-success dropdown-toggle"><i class="fa fa-mail-forward"></i> Copiar a <i class="fa fa-caret-up"></i></button>
											<ul class="dropdown-menu">
												<li><a class="alkin dropdown-item" href="factura_compra.php?dt_EC=1&EC=<?php echo base64_encode($row['ID_EntradaCompra']); ?>&Referencia=<?php echo base64_encode($row['NumAtCard']); ?>&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>">Factura de compra</a></li>

												<li><a class="alkin dropdown-item" href="devolucion_compra.php?dt_EC=1&EC=<?php echo base64_encode($row['ID_EntradaCompra']); ?>&Referencia=<?php echo base64_encode($row['NumAtCard']); ?>&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>">Devolución de compra</a></li>
									
												<li><a class="alkin dropdown-item d-compra" href="entrada_compra.php?dt_EC=1&EC=<?php echo base64_encode($row['ID_EntradaCompra']); ?>&pag=<?php echo $_GET['pag']; ?>&return=<?php echo $_GET['return']; ?>&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>&Serie=<?php echo base64_encode($row['IdSeries']); ?>">Entrada de compra (Duplicar)</a></li>
											</ul>
										</div>
									</div>
						<?php } elseif (($edit == 1) && $row['Cod_Estado'] == 'C') { ?>
									<div class="col-lg-3">
										<div class="btn-group dropup pull-right">
											<button data-toggle="dropdown" class="btn btn-success dropdown-toggle"><i class="fa fa-mail-forward"></i> Copiar a <i class="fa fa-caret-up"></i></button>
											<ul class="dropdown-menu">
												<li><a class="alkin dropdown-item d-compra" href="entrada_compra.php?dt_EC=1&EC=<?php echo base64_encode($row['ID_EntradaCompra']); ?>&pag=<?php echo $_GET['pag']; ?>&return=<?php echo $_GET['return']; ?>&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>&Serie=<?php echo base64_encode($row['IdSeries']); ?>">Entrada de compra (Duplicar)</a></li>
											</ul>
										</div>
									</div>
						<?php } ?>
					</div>
						
						<input type="hidden" form="CrearEntradaCompra" id="P" name="P" value="57" />

						<input type="hidden" form="CrearEntradaCompra" id="IdEntradaCompra" name="IdEntradaCompra" value="<?php if ($edit == 1) {
							echo base64_encode($row['ID_EntradaCompra']);
						} ?>" />
						
						<input type="hidden" form="CrearEntradaCompra" id="IdEvento" name="IdEvento" value="<?php if ($edit == 1) {
							echo base64_encode($IdEvento);
						} ?>" />
						
						<input type="hidden" form="CrearEntradaCompra" id="d_LS" name="d_LS"
							value="<?php echo $dt_LS; ?>" />
						
						<input type="hidden" form="CrearEntradaCompra" id="tl" name="tl" value="<?php echo $edit; ?>" />
						
						<input type="hidden" form="CrearEntradaCompra" id="swError" name="swError"
							value="<?php echo $sw_error; ?>" />
						
						<input type="hidden" form="CrearEntradaCompra" id="return" name="return"
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
			<?php if (($edit == 0) && ($ClienteDefault != "")) { ?>
				$("#CardCode").change();
			<?php } ?>

			$("#CrearEntradaCompra").validate({
				submitHandler: function (form) {
					if (Validar()) {
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
					} else {
						$('.ibox-content').toggleClass('sk-loading', false);
					}
				}
			});

			// Mostrar modal NO se cumplen las condiciones, SMM 01/08/2022
			<?php if ($success == 0) { ?>
					$('#modalAUT').modal('show');
			<?php } ?>

			// SMM, 28/08/2022
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

			maxLength('Comentarios'); // SMM, 15/07/2022
			maxLength('ComentariosAutor'); // SMM, 15/07/2022

			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});


			// Cambiar SN en las copias
			$(".d-compra").on("click", function (event) {
				<?php if (PermitirFuncion(720)) { ?>
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
						console.log("Permiso 720, no esta activo");
				<?php } ?>
			});

			let optionsSN = {
				url: function (phrase) {
					return "ajx_buscar_datos_json.php?type=7&id=" + phrase + "&pv=1";
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

			$("#NombreClienteSN").easyAutocomplete(optionsSN);

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
						console.log("ContactoSN", error.responseText);
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
						console.log("SucursalSN", error.responseText);
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
							console.log("SucursalSN", error.responseText);
						}
					});
				}
			});
			// SMM, 02/04/2022


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

			// $('.chosen-select').chosen({width: "100%"});
			$(".select2").select2();

			<?php
			if ($edit == 1) { ?>
					// $('#Serie option:not(:selected)').attr('disabled',true);
			<?php } ?>

			<?php if (!PermitirFuncion(716) || true) { ?>
					$('#Autorizacion').attr('readonly', true); // SMM, 01/08/2022
					$('#Autorizacion option:not(:selected)').attr('disabled', true);
			<?php } ?>

			var options = {
				url: function (phrase) {
					return "ajx_buscar_datos_json.php?type=7&id=" + phrase + "&pv=1";
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
			<?php if (PermitirFuncion(720) || ($edit == 0)) { ?>
					$("#CardName").easyAutocomplete(options);
			<?php } ?>
			<?php if ($dt_LS == 1 || $dt_OV == 1) { ?>
					$('#CardCode').trigger('change');
			<?php } ?>

			<?php if ($edit == 0) { ?>
					$('#Serie').trigger('change');
			<?php } ?>

			$('#CardCode').trigger('change'); // SMM, 24/02/2022

			// SMM, 29/09/2023
			<?php if (isset($_GET['SucursalFact']) || ($BillToDef != "")) { ?>
					$('#SucursalFacturacion').trigger('change');
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
						} ?>&objtype=22",
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

			//Validar si fue actualizado por otro usuario
			$.ajax({
				url: "ajx_buscar_datos_json.php",
				data: {
					type: 15,
					docentry: '<?php if ($edit == 1) {
						echo base64_encode($row['DocEntry']);
					} ?>',
					objtype: 20,
					date: '<?php echo FormatoFecha(date('Y-m-d'), date('H:i:s')); ?>'
				},
				dataType: 'json',
				success: function (data) {
					if (data.Result != 1) {
						result = false;
						Swal.fire({
							title: '¡Lo sentimos!',
							text: 'Este documento ya fue actualizado por otro usuario. Debe recargar la página para volver a cargar los datos.',
							icon: 'error'
						});
					}
				}
			});

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
						ObjType: 20,
						OT: OrdenServicio,
						Edit: <?php echo $edit; ?>,
						DocType: "<?php echo ($edit == 0) ? 20 : 21; ?>",
						DocId: "<?php echo $row['ID_EntradaCompra'] ?? 0; ?>",
						DocEvent: "<?php echo $row['IdEvento'] ?? 0; ?>",
						CardCode: cardCode,
						IdSeries: serie,
						IdProyecto: proyecto,
						ListaPrecio: listaPrecio,
						TipoDoc: 1, // Compras
						IdEmpleado: empleado
					},
					success: function (response) {
						$('#mdArticulos').html(response);
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
						DocType: "<?php echo 17; ?>",
						DocId: "<?php echo $row['ID_EntradaCompra'] ?? 0; ?>",
						DocEvent: "<?php echo $row['IdEvento'] ?? 0; ?>",
						CardCode: cardCode,
						IdSeries: serie,
						IdProyecto: proyecto,
						ListaPrecio: listaPrecio,
						IdEmpleado: empleado
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
