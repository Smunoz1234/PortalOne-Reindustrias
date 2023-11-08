<?php require_once "includes/conexion.php";
PermitirAcceso(405);

$dt_SLS = 0; // Para saber si viene desde la Solicitud de Llamada de servicio.
$dt_LS = 0; //sw para saber si vienen datos de la llamada de servicio. 0 no vienen. 1 si vienen.
$dt_OF = 0; //sw para saber si vienen datos de una Oferta de venta.

$msg_error = ""; //Mensaje del error
$IdOferta = 0;
$IdPortal = 0; //Id del portal para las ordenes que fueron creadas en el portal, para eliminar el registro antes de cargar al editar
$ObjType = 23;

$BillToDef = ""; // Sucursal de Facturación por Defecto.
$ShipToDef = ""; // Sucursal de Destino por Defecto.

// SMM, 07/11/2023
$NameFirma = "";
$PuedeFirmar = 0;

if (isset($_GET['id']) && ($_GET['id'] != "")) {
	$IdOferta = base64_decode($_GET['id']);
}
if (isset($_GET['id_portal']) && ($_GET['id_portal'] != "")) { //Id del portal de venta (ID interno)
	$IdPortal = base64_decode($_GET['id_portal']);
}

if (isset($_POST['IdOfertaVenta']) && ($_POST['IdOfertaVenta'] != "")) { //Tambien el Id interno, pero lo envío cuando mando el formulario
	$IdOfertaVenta = base64_decode($_POST['IdOfertaVenta']);
	$IdEvento = base64_decode($_POST['IdEvento']);
}

if (isset($_REQUEST['tl']) && ($_REQUEST['tl'] != "")) { //0 Si se está creando. 1 Se se está editando.
	$edit = $_REQUEST['tl'];
} else {
	$edit = 0;
}

if (isset($_POST['swError']) && ($_POST['swError'] != "")) { //Para saber si ha ocurrido un error.
	$sw_error = $_POST['swError'];
} else {
	$sw_error = 0;
}

if (isset($_POST['P']) && ($_POST['P'] != "")) { //Grabar Oferta de venta
	//*** Carpeta temporal ***
	$i = 0; //Archivos
	$RutaAttachSAP = ObtenerDirAttach();
	$dir = CrearObtenerDirTemp();
	$dir_new = CrearObtenerDirAnx("ofertaventa");

	// $directorio = opendir("."); // ruta actual
	$dir_firma = CrearObtenerDirTempFirma();

	// La firma se copia al directorio temporal, pero luego es que se copia a SAP.
	if ((isset($_POST['SigRecibe'])) && ($_POST['SigRecibe'] != "")) {
		$NombreFileFirma = base64_decode($_POST['SigRecibe']);
		$Nombre_Archivo = "Sig_$NombreFileFirma";
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
		if ($_POST['P'] == 47) { //Actualizar
			$IdOfertaVenta = base64_decode($_POST['IdOfertaVenta']);
			$IdEvento = base64_decode($_POST['IdEvento']);
			$Type = 2;

			/*
			if (!PermitirFuncion(403)) { //Permiso para autorizar orden de venta
				$_POST['Autorizacion'] = 'P'; //Si no tengo el permiso, la orden queda pendiente
			}
			*/

			// SMM, 07/11/2023
			if ((isset($_POST['SigRecibe'])) && ($_POST['SigRecibe'] != "")) { // Si solo estoy actualizando la firma
                $Type = 4;
            }
		} else { //Crear
			$IdOfertaVenta = "NULL";
			$IdEvento = "0";
			$Type = 1;
		}

		// SMM, 07/11/2023
		if ($Type == 4) {
            $ParametrosCabTrasladoInv = array(
                $IdOfertaVenta,
                $IdEvento,
                "'" . $_SESSION['CodUser'] . "'",
                "'" . $_SESSION['CodUser'] . "'",
                $Type,
                "'" . ($_POST['NombreRecibeFirma'] ?? "") . "'",
                "'" . ($_POST['CedulaRecibeFirma'] ?? "") . "'",
            );
        } else {
			$ParametrosCabOfertaVenta = array(
				$IdOfertaVenta,
				$IdEvento,
				// Estos campos deben ir arriba para poder firmar.
				"'" . $_SESSION['CodUser'] . "'",
                "'" . $_SESSION['CodUser'] . "'",
                $Type,
                // SMM, 07/11/2023
                "'" . ($_POST['NombreRecibeFirma'] ?? "") . "'",
                "'" . ($_POST['CedulaRecibeFirma'] ?? "") . "'",
                // Hasta aquí.
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
				"'" . str_replace(',', '', $_POST['TotalOferta']) . "'",
				"'" . $_POST['SucursalFacturacion'] . "'",
				"'" . $_POST['DireccionFacturacion'] . "'",
				"'" . $_POST['SucursalDestino'] . "'",
				"'" . $_POST['DireccionDestino'] . "'",
				"'" . $_POST['CondicionPago'] . "'",
				"''", // SMM, 15/06/2023
				"''", // SMM, 15/06/2023
				"''", // SMM, 15/06/2023
				"'" . $_POST['PrjCode'] . "'",
				"'" . $_POST['Autorizacion'] . "'",
				"'" . ($_POST['Almacen'] ?? "") . "'",
				"'" . ($_POST['SolicitudLlamadaCliente'] ?? "") . "'",
				"'" . ($_POST['dt_SLS'] ?? "") . "'",
			);
		}

		$SQL_CabeceraOfertaVenta = EjecutarSP('sp_tbl_OfertaVenta', $ParametrosCabOfertaVenta, $_POST['P']);
		if ($SQL_CabeceraOfertaVenta) {
			if ($Type == 1) {
				$row_CabeceraOfertaVenta = sqlsrv_fetch_array($SQL_CabeceraOfertaVenta);
				$IdOfertaVenta = $row_CabeceraOfertaVenta[0];
				$IdEvento = $row_CabeceraOfertaVenta[1];
			} else {
				$IdOfertaVenta = base64_decode($_POST['IdOfertaVenta']); //Lo coloco otra vez solo para saber que tiene ese valor
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
							"'" . $ObjType . "'",
							"'" . $IdOfertaVenta . "'",
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

			//Enviar datos al WebServices
			try {
				$Parametros = array(
					'id_documento' => intval($IdOfertaVenta),
					'id_evento' => intval($IdEvento),
				);
				$Metodo = "OfertasVentas";
				$Resultado = EnviarWebServiceSAP($Metodo, $Parametros, true, true);

				if ($Resultado->Success == 0) {
					//InsertarLog(1, 0, 'Error al generar el informe');
					//throw new Exception('Error al generar el informe. Error de WebServices');
					$sw_error = 1;
					$msg_error = $Resultado->Mensaje;
				} else {
					if ($_POST['P'] == 46) { //Creando oferta
						// Consultar ID creado para cargar el documento
						$SQL_ConsID = Seleccionar('uvw_Sap_tbl_OfertasVentas', 'ID_OfertaVenta', "IdDocPortal='$IdOfertaVenta'");
						$row_ConsID = sqlsrv_fetch_array($SQL_ConsID);
						
						sqlsrv_close($conexion);
						header('Location:oferta_venta.php?id=' . base64_encode($row_ConsID['ID_OfertaVenta']) . '&id_portal=' . base64_encode($IdOfertaVenta) . '&tl=1&a=' . base64_encode("OK_OFertAdd"));
					} else { // Actualizando oferta
						$SQL_ConsID = Seleccionar('uvw_Sap_tbl_OfertasVentas', 'ID_OfertaVenta', "IdDocPortal='$IdOfertaVenta'");
						$row_ConsID = sqlsrv_fetch_array($SQL_ConsID);
						
						sqlsrv_close($conexion);
						header('Location:oferta_venta.php?id=' . base64_encode($row_ConsID['ID_OfertaVenta']) . '&id_portal=' . base64_encode($row_ConsID['ID_OfertaVenta']) . '&tl=1&a=' . base64_encode("OK_OFertUpd"));
						// header('Location:' . base64_decode($_POST['return']) . '&a=' . base64_encode("OK_OFertUpd"));
					}
				}
			} catch (Exception $e) {
				echo 'Excepcion capturada: ', $e->getMessage(), "\n";
			}

		} else {
			$sw_error = 1;
			$msg_error = "Ha ocurrido un error al crear la oferta de venta";
		}
	} catch (Exception $e) {
		echo 'Excepcion capturada: ', $e->getMessage(), "\n";
	}

}

// SMM, 16/08/2023
if (isset($_GET['dt_SLS']) && ($_GET['dt_SLS'] == 1)) {
	$dt_SLS = 1;

	// SMM 16/08/2023
	if(isset($_GET['SLS'])) {
		$SQL_SolicitudLlamada = Seleccionar('uvw_tbl_SolicitudLlamadasServicios', '*', "ID_SolicitudLlamadaServicio='" . base64_decode($_GET['SLS']) . "'");
		$row_SolicitudLlamada = sqlsrv_fetch_array($SQL_SolicitudLlamada);
	}
}

if (isset($_GET['dt_LS']) && ($_GET['dt_LS'] == 1)) { //Verificar que viene de una Llamada de servicio (Datos Llamada servicio)
	$dt_LS = 1;

	// Orden de servicio, SMM 16/08/2023
	if(isset($_GET['LS'])) {
		$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . base64_decode($_GET['LS']) . "'");
		$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);
	}

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
		$SQL_AddLMT = EjecutarSP('sp_CargarLMT_OfertaVentaDetalleCarrito', $ParametrosAddLMT);
	} else {
		Eliminar('tbl_OfertaVentaDetalleCarrito', "Usuario='" . $_SESSION['CodUser'] . "' AND CardCode='" . base64_decode($_GET['Cardcode']) . "'");
	}

	// Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreCliente');
	$row_Cliente = sqlsrv_fetch_array($SQL_Cliente);

	// SMM, 29/09/2023
	$BillToDef = $row_Cliente["BillToDef"];
	$ShipToDef = $row_Cliente["ShipToDef"];

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreContacto');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='B'", 'NombreSucursal');
}

// SMM, 26/04/2022
if (isset($_GET['dt_OF']) && ($_GET['dt_OF']) == 1) { //Verificar que viene de una Oferta de ventas (Duplicar)
	$dt_OF = 1;

	$ParametrosCopiarOfertaToOrden = array(
		"'" . base64_decode($_GET['OF']) . "'",
		"'" . base64_decode($_GET['Evento']) . "'",
		"'" . base64_decode(($_GET['Almacen'] ?? "")) . "'",
		"'" . base64_decode($_GET['Cardcode']) . "'",
		"'" . $_SESSION['CodUser'] . "'",
	);
	$SQL_CopiarOfertaToOferta = EjecutarSP('sp_tbl_OfertaVentaDet_To_OfertaVentaDet', $ParametrosCopiarOfertaToOrden);
	if (!$SQL_CopiarOfertaToOferta) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Ha ocurrido un error!',
				text: 'No se pudo duplicae la Oferta de venta.',
				icon: 'error'
			});
		});
		</script>";
	}

	// Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreCliente');
	$row_Cliente = sqlsrv_fetch_array($SQL_Cliente);

	// SMM, 29/09/2023
	$BillToDef = $row_Cliente["BillToDef"];
	$ShipToDef = $row_Cliente["ShipToDef"];

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreContacto');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "' AND TipoDireccion='B'", 'NombreSucursal');
}

if ($edit == 1 && $sw_error == 0) {

	$ParametrosLimpiar = array(
		"'" . $IdOferta . "'",
		"'" . $IdPortal . "'",
		"'" . $_SESSION['CodUser'] . "'",
	);
	$LimpiarOferta = EjecutarSP('sp_EliminarDatosOfertaVenta', $ParametrosLimpiar);

	$SQL_Evento = sqlsrv_fetch_array($LimpiarOferta);
	$IdEvento = $SQL_Evento[0];

	//Oferta de venta
	$Cons = "Select * From uvw_tbl_OfertaVenta Where DocEntry='" . $IdOferta . "' AND IdEvento='" . $IdEvento . "'";
	$SQL = sqlsrv_query($conexion, $Cons);
	$row = sqlsrv_fetch_array($SQL);

	//Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreCliente');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='B'", 'NombreSucursal');

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreContacto');

	//Orden de servicio, SMM 05/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . $row['ID_LlamadaServicio'] . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);

	// SMM, 16/08/2023
	$SQL_SolicitudLlamada = Seleccionar('uvw_tbl_SolicitudLlamadasServicios', '*', "ID_SolicitudLlamadaServicio='" . $row['ID_SolicitudLlamadaServicio'] . "'");
	$row_SolicitudLlamada = sqlsrv_fetch_array($SQL_SolicitudLlamada);

	//Anexos
	$SQL_Anexo = Seleccionar('uvw_Sap_tbl_DocumentosSAP_Anexos', '*', "AbsEntry='" . $row['IdAnexo'] . "'");

}

if ($sw_error == 1) {

	//Oferta de venta
	$Cons = "Select * From uvw_tbl_OfertaVenta Where ID_OfertaVenta='" . $IdOfertaVenta . "' AND IdEvento='" . $IdEvento . "'";
	$SQL = sqlsrv_query($conexion, $Cons);
	$row = sqlsrv_fetch_array($SQL);

	//Clientes
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreCliente');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='B'", 'NombreSucursal');

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreContacto');

	//Orden de servicio, SMM 05/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . $row['ID_LlamadaServicio'] . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);

	// SMM, 16/08/2023
	$SQL_SolicitudLlamada = Seleccionar('uvw_tbl_SolicitudLlamadasServicios', '*', "ID_SolicitudLlamadaServicio='" . $row['ID_SolicitudLlamadaServicio'] . "'");
	$row_SolicitudLlamada = sqlsrv_fetch_array($SQL_SolicitudLlamada);

	//Anexos
	$SQL_Anexo = Seleccionar('uvw_Sap_tbl_DocumentosSAP_Anexos', '*', "AbsEntry='" . $row['IdAnexo'] . "'");

}

// SMM, 14/10/2023
$FiltroPrj = "";
$FiltrarDest = 0;
$FiltrarFact = 0;
if($edit == 0) {
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
	if(isset($SQL_SucursalDestino) && (sqlsrv_num_rows($SQL_SucursalDestino) == 1)) {
		$FiltrarDest = 1;
	}

	if(isset($SQL_SucursalFacturacion) && (sqlsrv_num_rows($SQL_SucursalFacturacion) == 1)) {
		$FiltrarFact = 1;
	}
}

//Condiciones de pago
$SQL_CondicionPago = Seleccionar('uvw_Sap_tbl_CondicionPago', '*', '', 'IdCondicionPago');

//Estado documento
$SQL_EstadoDoc = Seleccionar('uvw_tbl_EstadoDocSAP', '*');

//Estado autorizacion
$SQL_EstadoAuth = Seleccionar('uvw_Sap_tbl_EstadosAuth', '*');

//Empleado de ventas
$SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*', '', 'DE_EmpVentas');

//Series de documento
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'" . $ObjType . "'",
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Lista de precios, 07/03/2022
$SQL_ListaPrecios = Seleccionar('uvw_Sap_tbl_ListaPrecios', '*');

// Proyectos, SMM 07/03/2022
$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', '', 'DeProyecto');

// Stiven Muñoz Murillo, 07/03/2022
$row_encode = isset($row) ? json_encode($row) : "";
$cadena = isset($row) ? "JSON.parse('$row_encode'.replace(/\\n|\\r/g, ''))" : "'Not Found'";
// echo "<script> console.log($cadena); </script>";

// SMM, 07/11/2023
if ((isset($row['CodEmpleado']) && ($row['CodEmpleado'] == $_SESSION['IdCardCode'])) || PermitirFuncion(427)) {
	$PuedeFirmar = 1;
}
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include_once "includes/cabecera.php"; ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Oferta de venta |
		<?php echo NOMBRE_PORTAL; ?>
	</title>
	<?php
	if (isset($_GET['a']) && $_GET['a'] == base64_encode("OK_OFertAdd")) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Listo!',
				text: 'La Oferta de venta ha sido creada exitosamente.',
				icon: 'success'
			});
		});
		</script>";
	}
	if (isset($_GET['a']) && $_GET['a'] == base64_encode("OK_OFertUpd")) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Listo!',
				text: 'La Oferta de venta ha sido actualizada exitosamente.',
				icon: 'success'
			});
		});
		</script>";
	}
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_ActAdd"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La actividad ha sido agregada exitosamente.',
                icon: 'success'
            });
		});
		</script>";
	}
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_UpdActAdd"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La actividad ha sido actualizada exitosamente.',
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
		function CrearLead() {
			self.name = 'opener';
			var altura = 720;
			var anchura = 1240;
			var posicion_y = parseInt((window.screen.height / 2) - (altura / 2));
			var posicion_x = parseInt((window.screen.width / 2) - (anchura / 2));
			remote = open('popup_crear_lead.php', 'remote', 'width=' + anchura + ',height=' + altura + ',location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=no,status=yes,left=' + posicion_x + ',top=' + posicion_y);
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
					url: "ajx_cbo_select.php?type=2&id=" + carcode,
					success: function (response) {
						$('#ContactoCliente').html(response).fadeIn();
					},
					error: function (error) {
						console.error(error.responseText);
						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});

				// Lista de precio en el SN, SMM 20/01/2022
				let cardcode = carcode;

				// SMM, 05/05/2022
				document.cookie = `cardcode=${cardcode}`;

				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: {
						type: 45,
						id: cardcode
					},
					dataType: 'json',
					success: function (data) {
						console.log("Line 503", data);

						document.getElementById('IdListaPrecio').value = data.IdListaPrecio;
						$('#IdListaPrecio').trigger('change');

						document.getElementById('Exento').value = data.SujetoImpuesto; // SMM, 23/04/2022
					},
					error: function (error) {
						console.error(error.responseText);
						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});

				<?php if ($edit == 0 && $sw_error == 0 && $dt_LS == 0 && $dt_OF == 0) { // Limpiar carrito detalle. ?>
					$.ajax({
						type: "POST",
						url: "includes/procedimientos.php?type=7&objtype=23&cardcode=" + carcode
					});

					// Recargar sucursales.
					$.ajax({
						type: "POST",
						url: "ajx_cbo_select.php?type=3&tdir=S&id=" + carcode,
						success: function (response) {
							$('#SucursalDestino').html(response).fadeIn();
							$('#SucursalDestino').trigger('change');
						},
						error: function (error) {
							console.error(error.responseText);
							$('.ibox-content').toggleClass('sk-loading', false);
						}
					});
					$.ajax({
						type: "POST",
						url: "ajx_cbo_select.php?type=3&tdir=B&id=" + carcode,
						success: function (response) {
							$('#SucursalFacturacion').html(response).fadeIn();
							$('#SucursalFacturacion').trigger('change');
						},
						error: function (error) {
							console.error(error.responseText);
							$('.ibox-content').toggleClass('sk-loading', false);
						}
					});
				<?php } ?>

				<?php if ($edit == 0 && $sw_error == 0 && $dt_OF == 0) { // Recargar condición de pago. ?>
					$.ajax({
						type: "POST",
						url: "ajx_cbo_select.php?type=7&id=" + carcode,
						success: function (response) {
							$('#CondicionPago').html(response).fadeIn();
						},
						error: function (error) {
							console.error(error.responseText);
							$('.ibox-content').toggleClass('sk-loading', false);
						}
					});
					// En la llamada no hay condición de pago, por lo que se carga desde el cliente.
				<?php } ?>

				<?php if ($edit == 0) { ?>
					if (carcode != "") {
						frame.src = "detalle_oferta_venta.php?id=0&type=1&usr=<?php echo $_SESSION['CodUser']; ?>&cardcode=" + carcode;
					} else {
						frame.src = "detalle_oferta_venta.php";
					}
				<?php } else { ?>
					if (carcode != "") {
						frame.src = "detalle_oferta_venta.php?id=<?php echo base64_encode($row['ID_OfertaVenta']); ?>&evento=<?php echo base64_encode($row['IdEvento']); ?>&type=2";
					} else {
						frame.src = "detalle_oferta_venta.php";
					}
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
					},
					error: function (error) {
						console.error("Line 657", error.responseText);
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
					},
					error: function (error) {
						console.error("Line 657", error.responseText);
						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
			}); // SMM, 15/06/2023
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
					<h2>Oferta de venta</h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">Ventas - Clientes</a>
						</li>
						<li class="active">
							<strong>Oferta de venta</strong>
						</li>
					</ol>
				</div>
			</div>
			<div class="wrapper wrapper-content">
				<!-- SMM, 27/06/2023 -->
				<div class="modal inmodal fade" id="mdLoteArticulos" tabindex="1" role="dialog" aria-hidden="true"
				data-backdrop="static" data-keyboard="false">
				</div>
				
				<!-- SMM, 17/06/2023 -->
				<div class="modal inmodal fade" id="mdArticulos" tabindex="1" role="dialog" aria-hidden="true"
				data-backdrop="static" data-keyboard="false">
				</div>

				<!-- SMM, 02/08/2022 -->
				<?php include_once 'md_consultar_llamadas_servicios.php'; ?>

				<!-- SMM, 16/08/2023 -->
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
									<?php if ($edit == 1) { ?>
										<!-- SMM, 06/10/2022 -->
										<div class="btn-group">
											<button data-toggle="dropdown"
												class="btn btn-outline btn-success dropdown-toggle"><i
													class="fa fa-download"></i> Descargar formato <i
													class="fa fa-caret-down"></i></button>
											<ul class="dropdown-menu">
												<?php $SQL_Formato = Seleccionar('uvw_tbl_FormatosSAP', '*', "ID_Objeto=23 AND (IdFormato='" . $row['IdSeries'] . "' OR DeSeries IS NULL) AND VerEnDocumento='Y' AND (EsBorrador='N' OR EsBorrador IS NULL)"); ?>
												<?php while ($row_Formato = sqlsrv_fetch_array($SQL_Formato)) { ?>
													<li>
														<a class="dropdown-item" target="_blank"
															href="sapdownload.php?id=<?php echo base64_encode('15'); ?>&type=<?php echo base64_encode('2'); ?>&DocKey=<?php echo base64_encode($row['DocEntry']); ?>&ObType=<?php echo base64_encode($row_Formato['ID_Objeto']); ?>&IdFrm=<?php echo base64_encode($row_Formato['IdFormato']); ?>"><?php echo $row_Formato['NombreVisualizar']; ?></a>
													</li>
												<?php } ?>
											</ul>
										</div>
										<!-- Hasta aquí, 06/10/2022 -->

										<a href="#" class="btn btn-outline btn-info"
											onClick="VerMapaRel('<?php echo base64_encode($row['DocEntry']); ?>','<?php echo base64_encode('23'); ?>');"><i
												class="fa fa-sitemap"></i> Mapa de relaciones</a>
									<?php } else { ?>
										<button type="button" onClick="CrearLead();" class="btn btn-outline btn-primary"><i
												class="fa fa-user-circle"></i> Crear Prospecto</button>
									<?php } ?>
								</div>
								<div class="col-lg-6">
									<?php if ($edit == 1 && $row['Cod_Estado'] != 'C') { ?>
										<button type="button"
											onClick="javascript:location.href='actividad.php?dt_DM=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&DM_type=<?php echo base64_encode('23'); ?>&DM=<?php echo base64_encode($row['DocEntry']); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('oferta_venta.php'); ?>'"
											class="alkin btn btn-outline btn-primary pull-right m-l-xs"><i
												class="fa fa-plus-circle"></i> Agregar actividad</button>
									<?php } ?>
									<?php if ($edit == 1 && $row['DocDestinoDocEntry'] != "") { ?>
										<a href="orden_venta.php?id=<?php echo base64_encode($row['DocDestinoDocEntry']); ?>&id_portal=<?php echo base64_encode($row['DocDestinoIdPortal']); ?>&tl=1"
											target="_blank" class="btn btn-outline btn-primary pull-right">Ir a documento
											destino <i class="fa fa-external-link"></i></a>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<br>
				<div class="ibox-content">
					<?php include "includes/spinner.php"; ?>
					<div class="row">
						<div class="col-lg-12">
							<form action="oferta_venta.php" method="post" class="form-horizontal"
								enctype="multipart/form-data" id="CrearOfertaVenta">
								<?php
								$_GET['obj'] = "23";
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
											<input name="CardCode" type="hidden" id="CardCode"
												value="<?php if (($edit == 1) || ($sw_error == 1)) {
													echo $row['CardCode'];
												} elseif ($dt_LS == 1 || $dt_OF == 1) {
													echo $row_Cliente['CodigoCliente'];
												} ?>">

											<input name="CardName" type="text" required="required" class="form-control"
												id="CardName" placeholder="Digite para buscar..."
												value="<?php if (($edit == 1) || ($sw_error == 1)) {
													echo $row['NombreCliente'];
												} elseif ($dt_LS == 1 || $dt_OF == 1) {
													echo $row_Cliente['NombreCliente'];
												} ?>"
												<?php if ($dt_LS == 1 || $dt_OF == 1 || $edit == 1) {
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
											<select name="ContactoCliente" class="form-control" id="ContactoCliente"
												required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled='disabled'";
												} ?>>
												<option value="">Seleccione...</option>
												<?php
												if ($edit == 1 || $sw_error == 1) {
													while ($row_ContactoCliente = sqlsrv_fetch_array($SQL_ContactoCliente)) { ?>
														<option value="<?php echo $row_ContactoCliente['CodigoContacto']; ?>"
															<?php if ((isset($row['CodigoContacto'])) && (strcmp($row_ContactoCliente['CodigoContacto'], $row['CodigoContacto']) == 0)) {
																echo "selected=\"selected\"";
															} ?>><?php echo $row_ContactoCliente['ID_Contacto']; ?></option>
													<?php }
												} ?>
											</select>
										</div>

										<!-- Inicio, Lista Precios SN -->
										<label class="col-lg-1 control-label">Lista Precios
											<!--span class="text-danger">*</span--></label>
										<div class="col-lg-5">
											<select name="IdListaPrecio" class="form-control" id="IdListaPrecio" <?php if (!PermitirFuncion(418)) {
												echo "disabled='disabled'";
											} ?>>
												<?php while ($row_ListaPrecio = sqlsrv_fetch_array($SQL_ListaPrecios)) { ?>
													<option value="<?php echo $row_ListaPrecio['IdListaPrecio']; ?>" <?php if (isset($row['IdListaPrecio']) && (strcmp($row_ListaPrecio['IdListaPrecio'], $row['IdListaPrecio']) == 0)) {
														   echo "selected=\"selected\"";
													   } ?>>
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
											<select name="SucursalDestino" class="form-control"
												id="SucursalDestino" required="required" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<option value="">Seleccione...</option>
												<?php if ($edit == 1 || $sw_error == 1 || $dt_LS == 1 || $dt_OF == 1) { ?>
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
											<select name="SucursalFacturacion" class="form-control"
												id="SucursalFacturacion" required="required" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<option value="">Seleccione...</option>
												<?php if ($edit == 1 || $sw_error == 1 || $dt_LS == 1 || $dt_OF == 1) { ?>
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
												id="DireccionDestino"
												value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DireccionDestino'];
												} elseif ($dt_LS == 1) {
													echo base64_decode($_GET['Direccion']);
												} ?>"
												<?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "readonly";
												} ?>>
										</div>
										<label class="col-lg-1 control-label">Dirección facturación</label>
										<div class="col-lg-5">
											<input type="text" class="form-control" name="DireccionFacturacion"
												id="DireccionFacturacion"
												value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DireccionFacturacion'];
												} ?>"
												<?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "readonly";
												} ?>>
										</div>
									</div>
								</div>
								<!-- /.col-lg-8 -->

								<div class="col-lg-4">
									<div class="form-group">
										<label class="col-lg-5">Número</label>
										<div class="col-lg-7">
											<input type="text" name="DocNum" id="DocNum" class="form-control"
												value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DocNum'];
												} ?>"
												readonly>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-5">Fecha de contabilización <span
												class="text-danger">*</span></label>
										<div class="col-lg-7 input-group date">
											<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
												name="DocDate" type="text" required="required" class="form-control"
												id="DocDate"
												value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DocDate'];
												} else {
													echo date('Y-m-d');
												} ?>"
												readonly="readonly" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "readonly";
												} ?>>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-5">Fecha de entrega/servicio <span
												class="text-danger">*</span></label>
										<div class="col-lg-7 input-group date">
											<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
												name="DocDueDate" type="text" required="required" class="form-control"
												id="DocDueDate"
												value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DocDueDate'];
												} else {
													echo date('Y-m-d');
												} ?>"
												readonly="readonly" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
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
												id="TaxDate"
												value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['TaxDate'];
												} else {
													echo date('Y-m-d');
												} ?>"
												readonly="readonly" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "readonly";
												} ?>>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-5">Estado <span class="text-danger">*</span></label>
										<div class="col-lg-7">
											<select name="EstadoDoc" class="form-control" id="EstadoDoc" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
												echo "disabled='disabled'";
											} ?>>
												<?php while ($row_EstadoDoc = sqlsrv_fetch_array($SQL_EstadoDoc)) { ?>
													<option value="<?php echo $row_EstadoDoc['Cod_Estado']; ?>" <?php if (($edit == 1) && (isset($row['Cod_Estado'])) && (strcmp($row_EstadoDoc['Cod_Estado'], $row['Cod_Estado']) == 0)) {
														   echo "selected=\"selected\"";
													   } ?>><?php echo $row_EstadoDoc['NombreEstado']; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>
								<!-- /.col-lg-4 -->
								
								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm">
											<i class="fa fa-user"></i> Documentos relacionados
										</h3>
									</label>
								</div>
								<div class="col-lg-12">
									<div class="form-group">
										<label class="col-lg-1 control-label">
										<?php if (($edit == 1) && ($row['ID_SolicitudLlamadaServicio'] != 0)) { ?><a
													href="solicitud_llamada.php?id=<?php echo base64_encode($row['ID_SolicitudLlamadaServicio']); ?>&tl=1"
													target="_blank" title="Consultar Solicitud de Llamada de servicio"
													class="btn-xs btn-success fa fa-search"></a>
											<?php } ?>Solicitud de Llamada (Agenda)
										</label>
										<div class="col-lg-7">
											<input type="hidden" class="form-control" name="SolicitudLlamadaCliente"
												id="SolicitudLlamadaCliente"
												value="<?php if (isset($row_SolicitudLlamada['ID_SolicitudLlamadaServicio']) && ($row_SolicitudLlamada['ID_SolicitudLlamadaServicio'] != 0)) {
													echo $row_SolicitudLlamada['ID_SolicitudLlamadaServicio'];
												} ?>">
											<input readonly type="text" class="form-control"
												name="Desc_SolicitudLlamadaCliente" id="Desc_SolicitudLlamadaCliente"
												placeholder="Haga clic en el botón"
												value="<?php if (isset($row_SolicitudLlamada['ID_SolicitudLlamadaServicio']) && ($row_SolicitudLlamada['ID_SolicitudLlamadaServicio'] != 0)) {
													echo $row_SolicitudLlamada['ID_SolicitudLlamadaServicio'] . " - " . $row_SolicitudLlamada['AsuntoLlamada'] . " (" . $row_SolicitudLlamada['DeTipoLlamada'] . ")";
												} ?>">
										</div>
										<div class="col-lg-4">
											<button class="btn btn-success" type="button"
												onClick="$('#mdSLS').modal('show');"><i class="fa fa-refresh"></i>
												Cambiar Agenda</button>
										</div>
									</div>
									
									<div class="form-group">
										<label class="col-lg-1 control-label">
											<?php if (($edit == 1) && ($row['ID_LlamadaServicio'] != 0)) { ?><a
													href="llamada_servicio.php?id=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&tl=1"
													target="_blank" title="Consultar Llamada de servicio"
													class="btn-xs btn-success fa fa-search"></a>
											<?php } ?>Orden de Servicio
										</label>
										<div class="col-lg-7">
											<input type="hidden" class="form-control" name="OrdenServicioCliente"
												id="OrdenServicioCliente"
												value="<?php if (isset($row_OrdenServicioCliente['ID_LlamadaServicio']) && ($row_OrdenServicioCliente['ID_LlamadaServicio'] != 0)) {
													echo $row_OrdenServicioCliente['ID_LlamadaServicio'];
												} ?>">
											<input readonly type="text" class="form-control"
												name="Desc_OrdenServicioCliente" id="Desc_OrdenServicioCliente"
												placeholder="Haga clic en el botón"
												value="<?php if (isset($row_OrdenServicioCliente['ID_LlamadaServicio']) && ($row_OrdenServicioCliente['ID_LlamadaServicio'] != 0)) {
													echo $row_OrdenServicioCliente['DocNum'] . " - " . $row_OrdenServicioCliente['AsuntoLlamada'] . " (" . $row_OrdenServicioCliente['DeTipoLlamada'] . ")";
												} ?>">
										</div>
										<div class="col-lg-4">
											<button class="btn btn-success" type="button"
												onClick="$('#mdOT').modal('show');"><i class="fa fa-refresh"></i>
												Cambiar Orden de Servicio</button>
										</div>
									</div>
								</div>
								<!-- /.col-lg-12 -->



								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-info-circle"></i> Datos de la
											oferta</h3>
									</label>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Serie <span
											class="text-danger">*</span></label>
									<div class="col-lg-3">
										<select name="Serie" class="form-control" required="required" id="Serie" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled='disabled'";
										} ?>>
											<!-- SMM, 20/06/2023 -->
											<?php if (sqlsrv_num_rows($SQL_Series) > 1) { ?>
												<option value=''>Seleccione...</option>
											<?php } ?>

											<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
												<option value="<?php echo $row_Series['IdSeries']; ?>" <?php if (($edit == 1 || $sw_error == 1) && (isset($row['IdSeries'])) && (strcmp($row_Series['IdSeries'], $row['IdSeries']) == 0)) {
													   echo "selected=\"selected\"";
												   } elseif (isset($_GET['Serie']) && (strcmp($row_Series['IdSeries'], base64_decode($_GET['Serie'])) == 0)) {
													   echo "selected=\"selected\"";
												   } ?>><?php echo $row_Series['DeSeries']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
									
									<label class="col-lg-1 control-label">Referencia</label>
									<div class="col-lg-3">
										<input type="text" name="Referencia" id="Referencia" class="form-control"
											value="<?php if ($edit == 1 || $sw_error == 1) {
												echo $row['NumAtCard'];
											} ?>"
											<?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
												echo "readonly";
											} ?>>
									</div>
									<label class="col-lg-1 control-label">Condición de pago <span
											class="text-danger">*</span></label>
									<div class="col-lg-3">
										<select name="CondicionPago" class="form-control" id="CondicionPago"
											required="required" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
												echo "disabled='disabled'";
											} ?>>
											<option value="">Seleccione...</option>
											<?php while ($row_CondicionPago = sqlsrv_fetch_array($SQL_CondicionPago)) { ?>
												<option value="<?php echo $row_CondicionPago['IdCondicionPago']; ?>" <?php if ($edit == 1 || $sw_error == 1) {
													   if (($row['IdCondicionPago'] != "") && (strcmp($row_CondicionPago['IdCondicionPago'], $row['IdCondicionPago']) == 0)) {
														   echo "selected=\"selected\"";
													   }
												   } elseif ((isset($_GET['CondicionPago'])) && (strcmp($row_CondicionPago['IdCondicionPago'], base64_decode($_GET['CondicionPago'])) == 0)) {
													   echo "selected=\"selected\"";
												   } ?>><?php echo $row_CondicionPago['NombreCondicion']; ?></option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="form-group">									
									<label class="col-lg-1 control-label">Autorización</label>
									<div class="col-lg-3">
										<select name="Autorizacion" class="form-control" id="Autorizacion" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?>>
											<?php while ($row_EstadoAuth = sqlsrv_fetch_array($SQL_EstadoAuth)) { ?>
												<option value="<?php echo $row_EstadoAuth['IdAuth']; ?>" <?php if (($edit == 1 || $sw_error == 1) && (isset($row['AuthPortal'])) && (strcmp($row_EstadoAuth['IdAuth'], $row['AuthPortal']) == 0)) {
													   echo "selected";
												   } elseif (($edit == 0 && $sw_error == 0) && ($row_EstadoAuth['IdAuth'] == 'N')) {
													   echo "selected";
												   } ?>>
													<?php echo $row_EstadoAuth['DeAuth']; ?></option>
											<?php } ?>
										</select>
									</div>

									<!-- Inicio, Proyecto -->
									<label class="col-lg-1 control-label">Proyecto <span 
									class="text-danger">*</span></label>
									
									<div class="col-lg-3">
										<select id="PrjCode" name="PrjCode" class="form-control select2" 
										form="CrearOfertaVenta" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
											} ?>>
												<option value="">(NINGUNO)</option>

												<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
													<option value="<?php echo $row_Proyecto['IdProyecto']; ?>" <?php if ((isset($row['PrjCode']) && (!isset($_GET['Proyecto']))) && ($row_Proyecto['IdProyecto'] == $row['PrjCode'])) {
														echo "selected";
													} elseif (isset($_GET['Proyecto']) && ($row_Proyecto['IdProyecto'] == base64_decode($_GET['Proyecto']))) {
														echo "selected";
													} elseif(((!isset($row['PrjCode'])) && (!isset($_GET['Proyecto']))) && ($FiltroPrj == $row_Proyecto['IdProyecto'])) { 
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
											oferta</h3>
									</label>
								</div>
								<div class="form-group">
									<!-- SMM, 15/06/2023 -->
									<div class="col-lg-4">
										<button <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "disabled";
										} ?> class="btn btn-success"
											type="button" onclick="AgregarArticulos();"><i class="fa fa-plus"></i>
											Agregar artículo</button>

										<!-- SMM, 27/06/2023 -->
										<button <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
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
										<?php $ID_OfertaVenta = $row['ID_OfertaVenta']; ?>
										<?php $Evento = $row['IdEvento']; ?>
										<?php $consulta_detalle = "SELECT $filtro_consulta FROM uvw_tbl_OfertaVentaDetalle WHERE ID_OfertaVenta='$ID_OfertaVenta' AND IdEvento='$Evento' AND Metodo <> 3"; ?>
									<?php } else { ?>
										<?php $Usuario = $_SESSION['CodUser']; ?>
										<?php $cookie_cardcode = 1; ?>
										<?php $consulta_detalle = "SELECT $filtro_consulta FROM uvw_tbl_OfertaVentaDetalleCarrito WHERE Usuario='$Usuario'"; ?>
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
												height="300"
												src="<?php if ($edit == 0) {
													echo "detalle_oferta_venta.php";
												} else {
													echo "detalle_oferta_venta.php?id=" . base64_encode($row['ID_OfertaVenta']) . "&evento=" . base64_encode($row['IdEvento']) . "&type=2&status=" . base64_encode($row['Cod_Estado']);
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
									<?php if (($edit == 1) || (isset($SQL_Anexo) && sqlsrv_has_rows($SQL_Anexo))) { ?>
										<?php if ((($edit == 1) && ($row['IdAnexo'] != 0)) || (sqlsrv_has_rows($SQL_Anexo) && ($edit == 0))) { ?>
											<div class="form-group">
												<div class="col-lg-4">
													<ul class="folder-list" style="padding: 0">
														<?php while ($row_Anexo = sqlsrv_fetch_array($SQL_Anexo)) { ?>

															<?php
															$Icon = IconAttach($row_Anexo['FileExt']);
															$tmp = substr($row_Anexo['NombreArchivo'], 0, 4);
															if ($tmp == "Sig_") {
																$NameFirma = $row_Anexo['NombreArchivo'];
															}
															?>
															
															<li>
																<a <?php if ($edit == 0) {
																	echo "disabled";
																} else {
																	echo "href='attachdownload.php?file=" . base64_encode($row_Anexo['AbsEntry']) . "&line=" . base64_encode($row_Anexo['Line']) . "'";
																} ?> target="_blank" class="btn-link btn-xs">
																	<i class="<?php echo $Icon; ?>"></i> <?php echo $row_Anexo['NombreArchivo']; ?>
																</a>
															</li>
														<?php } ?>
													</ul>
												</div>
											</div>
										<?php } else { ?>
											<p>Sin anexos.</p>
										<?php } ?>
									<?php } ?>

									<?php if (($edit == 0) || (($edit == 1) && ($row['Cod_Estado'] == 'O'))) { ?>
										<div class="row">
											<form action="upload.php" class="dropzone" id="dropzoneForm"
												name="dropzoneForm">
												<?php if (($sw_error == 0)) {
													LimpiarDirTemp();
												} ?>
												<div class="fallback">
													<input name="File" id="File" type="file" form="dropzoneForm" />
												</div>
											</form>
										</div>
									<?php } ?>
								</div>
								<!-- /.panel-body -->
							</div>
						</div>
					</div>
					<form id="frm" action="" class="form-horizontal">
						<div class="form-group">&nbsp;</div>
						<div class="col-lg-8">
							<div class="form-group">
								<label class="col-lg-2">Empleado de ventas <span class="text-danger">*</span></label>
								<div class="col-lg-5">
									<select name="EmpleadoVentas" class="form-control" id="EmpleadoVentas"
										form="CrearOfertaVenta" required="required" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
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
									<textarea type="text" maxlength="2000" name="Comentarios" form="CrearOfertaVenta"
										rows="4" id="Comentarios" class="form-control" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
											echo "readonly";
										} ?>><?php if (($edit == 1) || ($sw_error == 1)) {
											 echo $row['Comentarios'] ?? "";
										 } elseif (isset($_GET['Comentarios'])) {
											 echo base64_decode($_GET['Comentarios']);
										 } ?></textarea>
								</div>
							</div>

							<?php /* if (PermitirFuncion(417)) { ?>
								<div class="form-group">
									<label class="col-lg-2">Firma quien recibe</label>
									
									<?php if (($edit == 1) && ($NameFirma != "")) { ?>
										<div class="col-lg-10">
											<span class="badge badge-primary">Firmado</span>
										</div>
									<?php } else { ?>
										<?php LimpiarDirTempFirma(); ?>
										
										<div class="col-lg-5">
											<button class="btn btn-primary" type="button" id="FirmaCliente"
												onclick="AbrirFirma('SigRecibe');">
												<i class="fa fa-pencil-square-o"></i> Realizar firma
											</button>
											
											<input type="hidden" id="SigRecibe" name="SigRecibe" value=""
												form="CrearOfertaVenta">
											
											<div id="msgInfoSigRecibe" style="display: none;" class="alert alert-info">
												<i class="fa fa-info-circle"></i> El documento ya ha sido firmado.
											</div>
										</div>
										
										<div class="col-lg-5">
											<img id="ImgSigRecibe" style="display: none; max-width: 100%; height: auto;" src="" alt="">
										</div>
									<?php } ?>
								</div>
								<!-- /.form-group -->
							<?php } */ ?>

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
									<input type="text" name="SubTotal" form="CrearOfertaVenta" id="SubTotal"
										class="form-control" style="text-align: right; font-weight: bold;"
										value="<?php if ($edit == 1) {
											echo number_format($row['SubTotal'], 0);
										} else {
											echo "0.00";
										} ?>"
										readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Descuentos</strong></label>
								<div class="col-lg-5">
									<input type="text" name="Descuentos" form="CrearOfertaVenta" id="Descuentos"
										class="form-control" style="text-align: right; font-weight: bold;"
										value="<?php if ($edit == 1) {
											echo number_format($row['DiscSum'], 0);
										} else {
											echo "0.00";
										} ?>"
										readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">IVA</strong></label>
								<div class="col-lg-5">
									<input type="text" name="Impuestos" form="CrearOfertaVenta" id="Impuestos"
										class="form-control" style="text-align: right; font-weight: bold;"
										value="<?php if ($edit == 1) {
											echo number_format($row['VatSum'], 0);
										} else {
											echo "0.00";
										} ?>"
										readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Redondeo</strong></label>
								<div class="col-lg-5">
									<input type="text" name="Redondeo" form="CrearOfertaVenta" id="Redondeo"
										class="form-control" style="text-align: right; font-weight: bold;" value="0.00"
										readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Total</strong></label>
								<div class="col-lg-5">
									<input type="text" name="TotalOferta" form="CrearOfertaVenta" id="TotalOferta"
										class="form-control" style="text-align: right; font-weight: bold;"
										value="<?php if ($edit == 1) {
											echo number_format($row['DocTotal'], 0);
										} else {
											echo "0.00";
										} ?>"
										readonly>
								</div>
							</div>
						</div>

						<!-- Información firma. SMM, 07/11/2023 -->
						<?php if ($edit == 1) {?>
							<div class="col-lg-12">
								<div class="form-group">
									<div class="col-lg-6 border-bottom ">
										<label class="control-label text-danger">Información de quien recibe</label>
									</div>
								</div>

								<?php // if (PermitirFuncion(1213)) {?>
									<div class="form-group">
										<div class="col-lg-5">
											<label class="control-label">Nombre de quien recibe <span class="text-danger cierre-span">*</span></label>
											<input form="CrearTrasladoInventario" autocomplete="off" name="NombreRecibeFirma" type="text" class="form-control cierre-input" id="NombreRecibeFirma" maxlength="200" value="<?php echo $row['NombreRecibeFirma'] ?? ""; ?>" required <?php if ($NameFirma != "") {echo "readonly";}?>>
										</div>
										<div class="col-lg-5">
											<label class="control-label">Cédula de quien recibe</label>
											<input form="CrearTrasladoInventario" autocomplete="off" name="CedulaRecibeFirma" type="number" class="form-control cierre-input" id="CedulaRecibeFirma" maxlength="20" value="<?php echo $row['CedulaRecibeFirma'] ?? ""; ?>" <?php if ($NameFirma != "") {echo "readonly";}?>>
										</div>
									</div>
								<?php // }?>

								<!-- Componente "firma"-->
								<div class="form-group">
									<label class="col-lg-2">Firma de quien recibe</label>
									<?php if ($NameFirma != "") {?>
									<div class="col-lg-10">
										<span class="badge badge-primary">Firmado</span>
									</div>
									<?php } elseif ($PuedeFirmar == 1) {LimpiarDirTempFirma();?>
									<div class="col-lg-5">
										<button class="btn btn-primary" type="button" id="FirmaCliente" onClick="AbrirFirma('SigRecibe');"><i class="fa fa-pencil-square-o"></i> Realizar firma</button>
										<input type="hidden" id="SigRecibe" name="SigRecibe" value="" form="CrearTrasladoInventario" />
										<div id="msgInfoSigRecibe" style="display: none;" class="alert alert-info"><i class="fa fa-info-circle"></i> El documento ya ha sido firmado.</div>
									</div>
									<div class="col-lg-5">
										<img id="ImgSigRecibe" style="display: none; max-width: 100%; height: auto;" src="" alt="" />
									</div>
									<?php } else {?>
										<div class="col-lg-10">
											<span class="badge badge-warning">Usuario no autorizado para firmar</span>
										</div>
									<?php }?>
								</div>
								<!-- Hasta aquí -->
								<br><br>
							</div>
						<?php }?>
						<!-- Hasta aquí, 07/11/2023 -->

						<div class="form-group">
							<div class="col-lg-9">
								<?php if ($edit == 0 && PermitirFuncion(401)) {?>
									<button class="btn btn-primary" type="submit" form="CrearOfertaVenta" id="Crear">
										<i class="fa fa-check"></i> Crear Oferta de venta
									</button>
								<?php } elseif (($edit == 1) && (($row['Cod_Estado'] == "O" && PermitirFuncion(401)) || (($NameFirma == "") && ($PuedeFirmar == 1)))) {?>
									<button class="btn btn-warning" type="submit" form="CrearOfertaVenta" id="Actualizar">
										<i class="fa fa-refresh"></i> Actualizar Oferta de venta
									</button>
								<?php } ?>

								<?php
								$EliminaMsg = array("&a=" . base64_encode("OK_OFertAdd"), "&a=" . base64_encode("OK_OFertUpd")); //Eliminar mensajes
								if (isset($_GET['return'])) {
									$_GET['return'] = str_replace($EliminaMsg, "", base64_decode($_GET['return']));
								}
								if (isset($_GET['return'])) {
									$return = base64_decode($_GET['pag']) . "?" . $_GET['return'];
								} elseif (isset($_POST['return'])) {
									$return = base64_decode($_POST['return']);
								} else {
									$return = "oferta_venta.php?" . $_SERVER['QUERY_STRING'];
								}
								?>
								<a href="<?php echo $return; ?>" class="btn btn-outline btn-default"><i
										class="fa fa-arrow-circle-o-left"></i> Regresar</a>
							</div>

							<?php if (PermitirFuncion(401)) { ?>
								<?php if (($edit == 1) && ($row['Cod_Estado'] != 'C') && ($NameFirma != "")) { ?>
									<div class="col-lg-3">
										<div class="btn-group dropup pull-right">
											<button data-toggle="dropdown" class="btn btn-success dropdown-toggle"><i
													class="fa fa-mail-forward"></i> Copiar a <i
													class="fa fa-caret-up"></i></button>
											
											<ul class="dropdown-menu">
												<li>
													<a class="alkin dropdown-item"
														href="entrega_venta.php?dt_OF=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&OF=<?php echo base64_encode($row['ID_OfertaVenta']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>">
														Entrega de venta
													</a>
												</li>
												<li>
													<a class="alkin dropdown-item"
														href="entrega_venta.php?Aprobados=1&dt_OF=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&OF=<?php echo base64_encode($row['ID_OfertaVenta']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>">
														Entrega de venta (Solo articulos aprobados)
													</a>
												</li>
												<li>
													<a class="alkin dropdown-item"
														href="orden_venta.php?dt_OF=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&OF=<?php echo base64_encode($row['ID_OfertaVenta']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>">
														Orden de venta
													</a>
												</li>
												<li>
													<a class="alkin dropdown-item"
														href="orden_venta.php?Aprobados=1&dt_OF=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&OF=<?php echo base64_encode($row['ID_OfertaVenta']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>">
														Orden de venta (Solo articulos aprobados)
													</a>
												</li>
												<li>
													<a class="alkin dropdown-item d-venta"
														href="oferta_venta.php?dt_OF=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&OF=<?php echo base64_encode($row['ID_OfertaVenta']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>&Serie=<?php echo base64_encode($row['IdSeries']); ?>">
														Oferta de venta (Duplicar)
													</a>
												</li>
											</ul>
										</div>
									</div>
								<?php } elseif (($edit == 1) && $row['Cod_Estado'] == 'C') { ?>
									<div class="col-lg-3">
										<div class="btn-group dropup pull-right">
											<button data-toggle="dropdown" class="btn btn-success dropdown-toggle"><i
													class="fa fa-mail-forward"></i> Copiar a <i
													class="fa fa-caret-up"></i></button>
											
											<ul class="dropdown-menu">
												<li>
													<a class="alkin dropdown-item d-venta"
														href="oferta_venta.php?dt_OF=1&Cardcode=<?php echo base64_encode($row['CardCode']); ?>&Dim1=<?php echo base64_encode($row['OcrCode']); ?>&Dim2=<?php echo base64_encode($row['OcrCode2']); ?>&Dim3=<?php echo base64_encode($row['OcrCode3']); ?>&Sucursal=<?php echo base64_encode($row['SucursalDestino']); ?>&SucursalFact=<?php echo base64_encode($row['SucursalFacturacion']); ?>&Direccion=<?php echo base64_encode($row['DireccionDestino']); ?>&Almacen=<?php echo base64_encode($row['WhsCode']); ?>&Contacto=<?php echo base64_encode($row['CodigoContacto']); ?>&Empleado=<?php echo base64_encode($row['SlpCode']); ?>&OF=<?php echo base64_encode($row['ID_OfertaVenta']); ?>&Evento=<?php echo base64_encode($row['IdEvento']); ?>&dt_LS=1&LS=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&Comentarios=<?php echo base64_encode($row['Comentarios']); ?>&Proyecto=<?php echo base64_encode($row['PrjCode']); ?>&CondicionPago=<?php echo base64_encode($row['IdCondicionPago']); ?>&Serie=<?php echo base64_encode($row['IdSeries']); ?>">
														Oferta de venta (Duplicar)
													</a>
												</li>
											</ul>
										</div>
									</div>
								<?php } ?>
							<?php } ?>
						</div>
						<input type="hidden" form="CrearOfertaVenta" id="P" name="P"
							value="<?php if ($edit == 0) {
								echo "46";
							} else {
								echo "47";
							} ?>" />
						<input type="hidden" form="CrearOfertaVenta" id="IdOfertaVenta" name="IdOfertaVenta"
							value="<?php if ($edit == 1) {
								echo base64_encode($row['ID_OfertaVenta']);
							} ?>" />
						<input type="hidden" form="CrearOfertaVenta" id="IdEvento" name="IdEvento"
							value="<?php if ($edit == 1) {
								echo base64_encode($IdEvento);
							} ?>" />
						
						<input type="hidden" form="CrearOfertaVenta" id="dt_SLS" name="dt_SLS"
							value="<?php echo $dt_SLS; ?>" />

						<input type="hidden" form="CrearOfertaVenta" id="tl" name="tl" value="<?php echo $edit; ?>" />
						<input type="hidden" form="CrearOfertaVenta" id="return" name="return"
							value="<?php echo base64_encode($return); ?>" />

						<input type="hidden" form="CrearOfertaVenta" id="swError" name="swError"
							value="<?php echo $sw_error; ?>" />
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
			$("#CrearOfertaVenta").validate({
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

			// SMM, 03/11/2023
			maxLength('Comentarios');

			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});

			// Cambiar SN en las copias
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

			let optionsSN = {
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
				$('#Serie option:not(:selected)').attr('disabled', true);
			<?php } ?>

			<?php
			if (!PermitirFuncion(403)) { ?>
				$('#Autorizacion option:not(:selected)').attr('disabled', true);
			<?php } ?>

			var options = {
				url: function (phrase) {
					return "ajx_buscar_datos_json.php?type=7&id=" + phrase + "&lead=1";
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
			<?php if (PermitirFuncion(419) || ($edit == 0)) { ?>
				$("#CardName").easyAutocomplete(options);
			<?php } ?>

			<?php if ($dt_LS == 1 || $dt_OF == 1) { ?>
				$('#CardCode').trigger('change');
			<?php } ?>

			<?php if ($edit == 0) { ?>
				$('#Serie').trigger('change');
			<?php } ?>

			$('#CardCode').trigger('change'); // SMM, 26/04/2022

			// SMM, 29/09/2023
			<?php if (isset($_GET['SucursalFact']) || ($BillToDef != "")) { ?>
				$('#SucursalFacturacion').trigger('change');
			<?php } ?>
		});
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
					objtype: 23,
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
						} ?>&objtype=23",
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

	<script>
		// SMM, 15/06/2023
		function AgregarArticulos() {
			let probarModal = false;
			let ordenServicio = $("#OrdenServicioCliente").val();

			// SMM, 14/10/2023
			let solicitudLlamada = $("#SolicitudLlamadaCliente").val();

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
						ObjType: 23,
						OT: ordenServicio,
						Solicitud: solicitudLlamada,
						Edit: <?php echo $edit; ?>,
						DocType: "<?php echo ($edit == 0) ? 3 : 4; ?>",
						DocId: "<?php echo $row['ID_OfertaVenta'] ?? 0; ?>",
						DocEvent: "<?php echo $row['IdEvento'] ?? 0; ?>",
						CardCode: cardCode,
						IdSeries: serie,
						IdProyecto: proyecto,
						ListaPrecio: listaPrecio,
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
						DocType: "<?php echo 2; ?>",
						DocId: "<?php echo $row['ID_OfertaVenta'] ?? 0; ?>",
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
	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>
<?php sqlsrv_close($conexion); ?>