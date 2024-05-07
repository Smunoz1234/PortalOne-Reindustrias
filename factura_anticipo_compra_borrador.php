<?php require_once "includes/conexion.php";
PermitirAcceso(728);

$success = 1; // Confirmación de autorización (1 - Autorizado / 0 - NO Autorizado). SMM, 10/12/2022
$mensajeMotivo = ""; // Comentario motivo, mensaje de salida del procedimiento almacenado. SMM, 10/12/2022

// Bandera que indica si el documento se autoriza desde SAP.
$autorizaSAP = ""; // SMM, 15/12/2022

// Bandera de pruebas que me permite comportame como Autorizador en lugar de Autor.
// Nota: Si un usuario es Autorizador y Autor se le da prioridad al hecho de ser Autor.
// Nota: Debo tener el perfil del Autor asignado en el gestor de usuarios para ser Autorizador.
$serAutorizador = false; // SMM, 19/12/2022

$msg_error = ""; //Mensaje del error
$IdFacturaCompra = 0;
$IdPortal = 0; //Id del portal para las solicitudes que fueron creadas en el portal, para eliminar el registro antes de cargar al editar

// Motivos de autorización, SMM 10/12/2022
$SQL_Motivos = Seleccionar("uvw_tbl_Autorizaciones_Motivos", "*", "Estado = 'Y' AND IdTipoDocumento = 1250000001");

// SMM, 30/11/2022
$IdMotivo = "";
$motivoAutorizacion = "";

$debug_Condiciones = true; // Ocultar o mostrar modal y otras opciones de debug.
$IdTipoDocumento = 22; // Cambiar por el ID respectivo.
$success = 1; // Confirmación de autorización (1 - Autorizado / 0 - NO Autorizado)
$mensajeProceso = ""; // Mensaje proceso, mensaje de salida del procedimiento almacenado.

// Procesos de autorización, SMM 25/01/2024
$SQL_Procesos = Seleccionar("uvw_tbl_Autorizaciones_Procesos", "*", "Estado = 'Y' AND IdTipoDocumento = $IdTipoDocumento");

if (isset($_GET['id']) && ($_GET['id'] != "")) { //ID de la Factura anticipo de Compras (DocEntry)
	$IdFactura = base64_decode($_GET['id']);
}

if (isset($_GET['id_portal']) && ($_GET['id_portal'] != "")) { //Id del portal de compra (ID interno)
	$IdPortal = base64_decode($_GET['id_portal']);
}

if (isset($_POST['IdFacturaCompra']) && ($_POST['IdFacturaCompra'] != "")) { //Tambien el Id interno, pero lo envío cuando mando el formulario
	$IdFacturaCompra = base64_decode($_POST['IdFacturaCompra']);
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

// Validar si tiene doc de destino, no se pueda editar. Modificado, SMM 25/01/2024
if (!isset($row['DocDestinoDocEntry']) || ($row['DocDestinoDocEntry'] == "")) {
	$EstadoReal = $row['Cod_Estado'] ?? "C";
}

// Consulta decisión de autorización en la edición de documentos.
if ($edit == 1) {
	$DocEntry = "'$IdFactura'"; // Cambiar por el ID respectivo del documento.

	$EsBorrador = (true) ? "DocumentoBorrador" : "Documento";
	$SQL_Autorizaciones = Seleccionar("uvw_Sap_tbl_Autorizaciones", "*", "IdTipoDocumento = $IdTipoDocumento AND DocEntry$EsBorrador = $DocEntry");
	$row_Autorizaciones = sqlsrv_fetch_array($SQL_Autorizaciones);

	// SMM, 25/01/2024
	$SQL_Procesos = Seleccionar("uvw_tbl_Autorizaciones_Procesos", "*", "IdTipoDocumento = $IdTipoDocumento");
}

if (isset($_POST['P']) && ($_POST['P'] != "")) { // Grabar Factura anticipo de Compras
	//*** Carpeta temporal ***
	$i = 0; //Archivos
	$RutaAttachSAP = ObtenerDirAttach();
	$dir = CrearObtenerDirTemp();
	$dir_new = CrearObtenerDirAnx("facturacompraanticipo");
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
		if ($_POST['P'] == 39) { // Actualizar
			$IdFacturaCompra = base64_decode($_POST['IdFacturaCompra']);
			$IdEvento = base64_decode($_POST['IdEvento']);
			$Type = 2;
		} else { // Crear
			$IdFacturaCompra = "NULL";
			$IdEvento = "0";
			$Type = 1;
		}

		$ParametrosCabFacturaCompra = array(
			$IdFacturaCompra,
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
			"'" . $_POST['OrdenServicioCliente'] . "'",
			"'" . $_POST['Referencia'] . "'",
			"'" . $_POST['EmpleadoVentas'] . "'",
			"'" . LSiqmlObs($_POST['Comentarios']) . "'",
			"'" . str_replace(',', '', $_POST['SubTotal']) . "'",
			"'" . str_replace(',', '', $_POST['Descuentos']) . "'",
			"NULL",
			"'" . str_replace(',', '', $_POST['Impuestos']) . "'",
			"'" . str_replace(',', '', $_POST['TotalFacturaAnticipo']) . "'",
			"'" . LSiqmlObs($_POST['SucursalFacturacion']) . "'",
			"'" . LSiqmlObs($_POST['DireccionFacturacion']) . "'",
			"'" . LSiqmlObs($_POST['SucursalDestino']) . "'",
			"'" . LSiqmlObs($_POST['DireccionDestino']) . "'",
			"'" . $_POST['CondicionPago'] . "'",
			"'" . $_POST['PrjCode'] . "'",
			"'" . ($_POST['Autorizacion'] ?? "P") . "'", // SMM, 25/01/2024
			"'" . ($_POST['Almacen'] ?? "") . "'",
			"'" . $_SESSION['CodUser'] . "'",
			"'" . $_SESSION['CodUser'] . "'",
			"$Type",
			// SMM, 30/11/2022
			"'" . ($_POST['IdMotivoAutorizacion'] ?? "") . "'",
			"'" . ($_POST['ComentariosAutor'] ?? "") . "'",
			"'" . ($_POST['MensajeProceso'] ?? "") . "'",
			// SMM, 25/01/2024
			"'" . ($_POST['AutorizacionSAP'] ?? "") . "'",
			isset($_POST['FechaAutorizacionPO']) ? ("'" . FormatoFecha($_POST['FechaAutorizacionPO']) . "'") : "NULL",
			isset($_POST['HoraAutorizacionPO']) ? ("'" . $_POST['HoraAutorizacionPO'] . "'") : "NULL",
			"'" . ($_POST['UsuarioAutorizacionPO'] ?? "") . "'",
			"'" . ($_POST['ComentariosAutorizacionPO'] ?? "") . "'",
		);

		$SQL_CabeceraFacturaCompra = EjecutarSP('sp_tbl_FacturaCompraAnticipo_Borrador', $ParametrosCabFacturaCompra, $_POST['P']);
		if ($SQL_CabeceraFacturaCompra) {
			if ($Type == 1) {
				$row_CabeceraFacturaCompra = sqlsrv_fetch_array($SQL_CabeceraFacturaCompra);

				$IdFacturaCompra = $row_CabeceraFacturaCompra[0];
				$IdEvento = $row_CabeceraFacturaCompra[1];

				// Comprobar procesos de autorización en la creación, SMM 20/08/2022
				while ($row_Proceso = sqlsrv_fetch_array($SQL_Procesos)) {
					$ids_perfiles = ($row_Proceso['Perfiles'] != "") ? explode(";", $row_Proceso['Perfiles']) : [];

					if (in_array($_SESSION['Perfil'], $ids_perfiles) || (count($ids_perfiles) == 0)) {
						$sql = $row_Proceso['Condiciones'] ?? '';
						$autorizaSAP = $row_Proceso['AutorizacionSAP'] ?? ''; // SMM, 25/01/2024

						// Aquí se debe reemplazar por el ID del documento. SMM, 25/01/2024
						$sql = str_replace("[IdDocumento]", $IdFacturaCompra, $sql);
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
				// Hasta aquí, 25/01/2024

			} else {
				$IdFacturaCompra = base64_decode($_POST['IdFacturaCompra']); //Lo coloco otra vez solo para saber que tiene ese valor
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
							"'$IdTipoDocumento'", // SMM, 25/01/2024
							"'$IdFacturaCompra'",
							"'$OnlyName'",
							"'$Ext'",
							"1",
							"'" . $_SESSION['CodUser'] . "'",
							"1",
							"1", // EsBorrador
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

			// Verificar que el documento cumpla las Condiciones o este Pendiente de Autorización.
			// if (($success == 1) || ($_POST['Autorizacion'] != "P")) {
				if (isset($_POST['Autorizacion']) && ($_POST['Autorizacion'] != "P")) {
					$success = 1;
	
					// Inicio, Enviar datos al WebServices.
				try {
					$Parametros = array(
						'id_documento' => intval($IdFacturaCompra),
						'id_evento' => intval($IdEvento),
					);

					// SMM, 25/01/2024
					$end_point = "Borrador";
					$msg_ok = "OK_OCompAdd";
					if ((strtoupper($_SESSION["User"]) == strtoupper($_POST['Usuario'])) && (!$serAutorizador)) {
						$end_point = "CrearBorrador_A_Definitivo";
						$msg_ok = "OK_DefinitivoAdd";
					}

					// SMM, 25/01/2024
					$Metodo = "FacturasAnticiposCompras/$end_point";
					$Resultado = EnviarWebServiceSAP($Metodo, $Parametros, true, true);

					if ($Resultado->Success == 0) {
						$sw_error = 1;
						$msg_error = $Resultado->Mensaje;
					} else {
						// Inicio, redirección documento autorizado.
						sqlsrv_close($conexion);
						if ($_POST['P'] == 37) { // Creando Factura Anticipo
							header('Location:' . base64_decode($_POST['return']) . '&a=' . base64_encode($msg_ok));
						} else { //Actualizando solicitud
							header('Location:' . base64_decode($_POST['return']) . '&a=' . base64_encode($msg_ok));
						}
						// Fin, redirección documento autorizado.
					}
				} catch (Exception $e) {
					echo 'Excepcion capturada: ', $e->getMessage(), "\n";
				}
				// Fin, Enviar datos al WebServices.
			} else {
				$sw_error = 1;
				$msg_error = "Este documento necesita autorización.";
			}
			// Hasta aquí, 25/01/2024

		} else {
			$sw_error = 1;
			$msg_error = "Ha ocurrido un error al crear la Factura anticipo de Compras";
		}
	} catch (Exception $e) {
		echo 'Excepcion capturada: ', $e->getMessage(), "\n";
	}

}

// Empleado de compras. SMM, 29/05/2023 
$SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*', "Estado = 'Y'", 'DE_EmpVentas');

if ($edit == 1 && $sw_error == 0) {

	$ParametrosLimpiar = array(
		"'$IdFactura'",
		"'$IdPortal'",
		"'" . $_SESSION['CodUser'] . "'",
	);
	$LimpiarDocumento = EjecutarSP('sp_EliminarDatosFacturaCompraAnticipo_Borrador', $ParametrosLimpiar);

	$SQL_IdEvento = sqlsrv_fetch_array($LimpiarDocumento);
	$IdEvento = $SQL_IdEvento[0];

	// Empleado de ventas. SMM, 29/05/2023 
	$SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*', '', 'DE_EmpVentas');

	//Factura anticipo de Compras
	$Cons = "SELECT * FROM uvw_tbl_FacturaCompraAnticipo_Borrador WHERE DocEntry='$IdFactura' AND IdEvento='$IdEvento'";
	$SQL = sqlsrv_query($conexion, $Cons);
	$row = sqlsrv_fetch_array($SQL);

	// SMM, 06/09/2022
	// echo $Cons;

	// Proveedores
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Proveedores', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreCliente');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='B'", 'NombreSucursal');

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ProveedorContactos', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreContacto');

	//Orden de servicio, SMM 05/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . ($row['ID_LlamadaServicio'] ?? "") . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);

	//Anexos
	$SQL_Anexo = Seleccionar('uvw_Sap_tbl_DocumentosSAP_Anexos', '*', "AbsEntry='" . $row['IdAnexo'] . "'");
}

if ($sw_error == 1) {
	$Cons = "SELECT * FROM uvw_tbl_FacturaCompraAnticipo_Borrador WHERE ID_FacturaCompraAnticipo='$IdFacturaCompra' AND IdEvento='$IdEvento'";
	// echo $Cons;

	$SQL = sqlsrv_query($conexion, $Cons);
	$row = sqlsrv_fetch_array($SQL);

	// Proveedores
	$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Proveedores', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreCliente');

	//Sucursales, SMM 06/05/2022
	$SQL_SucursalDestino = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='S'", 'NombreSucursal');
	$SQL_SucursalFacturacion = Seleccionar('uvw_Sap_tbl_Proveedores_Sucursales', '*', "CodigoCliente='" . $row['CardCode'] . "' AND TipoDireccion='B'", 'NombreSucursal');

	//Contacto cliente
	$SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ProveedorContactos', '*', "CodigoCliente='" . $row['CardCode'] . "'", 'NombreContacto');

	// Orden de servicio. SMM, 05/08/2022
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . ($row['ID_LlamadaServicio'] ?? "") . "'");
	$row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente);

	//Anexos
	$SQL_Anexo = Seleccionar('uvw_Sap_tbl_DocumentosSAP_Anexos', '*', "AbsEntry='" . $row['IdAnexo'] . "'");
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
	"'$IdTipoDocumento'", // SMM, 25/01/2024
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Lista de precios, 24/02/2022
$SQL_ListaPrecios = Seleccionar('uvw_Sap_tbl_ListaPrecios', '*');

// Proyectos, SMM 04/03/2022
$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', '', 'DeProyecto');

// Consultar el motivo de autorización según el ID. SMM, 25/01/2024
if (isset($row['IdMotivoAutorizacion']) && ($row['IdMotivoAutorizacion'] != "") && ($IdMotivo == "")) {
	$IdMotivo = $row['IdMotivoAutorizacion'];
	$SQL_Motivos = Seleccionar("uvw_tbl_Autorizaciones_Motivos", "*", "IdMotivoAutorizacion = '$IdMotivo'");
	$row_MotivoAutorizacion = sqlsrv_fetch_array($SQL_Motivos);
	$motivoAutorizacion = $row_MotivoAutorizacion['MotivoAutorizacion'] ?? "";
}

// Verificar si el Autorizador tiene asignado el perfil del Autor. SMM, 25/01/2024
$autorAsignado = false;
if (isset($row['ID_PerfilUsuario']) && ($row['ID_PerfilUsuario'] != "")) {
	$Where_PerfilesAutorizador = "ID_Usuario='" . $_SESSION['CodUser'] . "' AND IdPerfil='" . $row['ID_PerfilUsuario'] . "'";
	$SQL_PerfilesAutorizador = Seleccionar('uvw_tbl_UsuariosPerfilesAsignados', '*', $Where_PerfilesAutorizador);

	// Valida si el perfil del autor esta en la respuesta.
	$autorAsignado = sqlsrv_has_rows($SQL_PerfilesAutorizador);
}

// Permiso para actualizar la Factura anticipo de Compras en borrador. SMM, 05/04/2024
$BloquearDocumento = false;
if (!PermitirFuncion(729)) {
	$BloquearDocumento = true;
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
		Factura anticipo de Compras borrador | <?php echo NOMBRE_PORTAL; ?>
	</title>

	<?php
	if (isset($_GET['a']) && $_GET['a'] == base64_encode("OK_OCompAdd")) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Listo!',
				text: 'La Factura anticipo de Compras ha sido creada exitosamente.',
				icon: 'success'
			});
		});
		</script>";
	}
	if (isset($_GET['a']) && $_GET['a'] == base64_encode("OK_OCompUpd")) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
				title: '¡Listo!',
				text: 'La Factura anticipo de Compras ha sido actualizada exitosamente.',
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

	// SMM, 25/01/2024
	if (isset($sw_error) && ($sw_error == 1)) {
		$error_title = ($success == 0) ? "Advertencia" : "Ha ocurrido un error";

		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡$error_title!',
                text: `" . LSiqmlObs($msg_error) . "`,
                icon: 'warning'
            });
		});
		</script>";
	}

	// SMM, 25/01/2024
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

		.nav-tabs>li>a {
			padding: 14px 20px 14px 25px !important;
		}

		/**
		* Stiven Muñoz Murillo
		* 25/01/2024
		*/
		<?php if ($BloquearDocumento) { ?>
			.select2-selection {
				background-color: #eee !important;
				opacity: 1;
			}
		<?php } ?>

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
						console.log("Line 891", data);

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

				<?php if ($edit == 0 && $sw_error == 0) { // Limpiar carrito detalle. ?>
					$.ajax({
						type: "POST",
						url: "includes/procedimientos.php?type=7&objtype=<?php echo $IdTipoDocumento; ?>&cardcode=" + carcode
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

				<?php if ($edit == 0 && $sw_error == 0) { // Recargar condición de pago. ?>
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
							frame.src = "detalle_factura_anticipo_compra_borrador.php?id=0&type=1&usr=<?php echo $_SESSION['CodUser']; ?>&cardcode=" + carcode;
						} else {
							frame.src = "detalle_factura_anticipo_compra_borrador.php";
						}
					<?php } else { ?>
						if (carcode != "") {
							frame.src = "detalle_factura_anticipo_compra_borrador.php?autoriza=1&id=<?php echo base64_encode($row['ID_FacturaCompraAnticipo']); ?>&evento=<?php echo base64_encode($row['IdEvento']); ?>&type=2";
						} else {
							frame.src = "detalle_factura_anticipo_compra_borrador.php";
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
					<h2>Factura anticipo de Compras borrador</h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">Compras - Proveedores</a>
						</li>
						<li class="active">
							<strong>Factura anticipo de Compras borrador</strong>
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

				<!-- Inicio, modalAUT. SMM, 25/01/2024 -->
				<?php if (($edit == 1) || ($success == 0) || ($sw_error == 1) || $debug_Condiciones) { ?>
					<div class="modal inmodal fade" id="modalAUT" tabindex="-1" role="dialog" aria-hidden="true">
						<div class="modal-dialog modal-lg">
							<div class="modal-content">
								<div class="modal-header">
									<h4 class="modal-title">Autorización de documento</h4>
								</div>

								<!-- form id="formAUT" -->
									<div class="modal-body">
										<div class="ibox">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-info-circle"></i> Autor</h5>
												<a class="collapse-link pull-right" style="color: white;">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div> <!-- ibox-title -->
											<div class="ibox-content">
												<div class="form-group">
													<label class="control-label col-lg-2">Autorización <span class="text-danger">*</span></label>
													<div class="col-lg-10">
														<select readonly form="CrearFacturaCompra" class="form-control" id="AutorizacionSAP" name="AutorizacionSAP" style="color: black; font-weight: bold;">
															<option value="" <?php if ($autorizaSAP == "") {
																echo "selected";
															} elseif (!isset($row['AutorizacionSAP']) || ($row['AutorizacionSAP'] == "")) {
																echo "selected";
															} ?>>Seleccione...</option>
															<option value="Y" <?php if ($autorizaSAP == "Y") {
																echo "selected";
															} elseif (isset($row['AutorizacionSAP']) && ($row['AutorizacionSAP'] == "Y")) {
																echo "selected";
															} ?>>Se autoriza desde SAP</option>
															<option value="N" <?php if ($autorizaSAP == "N") {
																echo "selected";
															} elseif (isset($row['AutorizacionSAP']) && ($row['AutorizacionSAP'] == "N")) {
																echo "selected";
															} ?>>Se autoriza desde PortalOne</option>
														</select>
													</div>
												</div>

												<br><br><br>
												<div class="form-group">
													<label class="col-lg-2">Motivo <span class="text-danger">*</span></label>
													<div class="col-lg-10">
														<input required type="hidden" form="CrearFacturaCompra" class="form-control"
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
														<textarea readonly form="CrearFacturaCompra"
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
													<label class="col-lg-2">
														Comentarios autor <span class="text-danger">*</span>
													</label>
													<div class="col-lg-10">
														<textarea <?php if ($edit == 1) {
															echo "readonly";
														} ?> form="CrearFacturaCompra"
															class="form-control required" name="ComentariosAutor"
															id="ComentariosAutor" type="text" maxlength="250" rows="4"><?php if ($edit == 1 || $sw_error == 1) {
																echo $row['ComentariosAutor'] ?? "";
															} elseif (isset($_GET['ComentariosAutor'])) {
																echo base64_decode($_GET['ComentariosAutor']);
															} ?></textarea>
													</div>
												</div>
												<br><br><br>
											</div> <!-- ibox-content -->
										</div> <!-- ibox -->
										<div class="ibox">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-info-circle"></i> Autorizador</h5>
												<a class="collapse-link pull-right" style="color: white;">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div> <!-- ibox-title -->
											<div class="ibox-content">
												<br>
												<div class="form-group">
													<div class="row">
														<label class="col-lg-6 control-label" style="text-align: left !important;">Fecha y hora decisión</label>
													</div>
													<div class="row">
														<div class="col-lg-6 input-group date">
															<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input readonly form="CrearFacturaCompra" name="FechaAutorizacionPO" type="text" autocomplete="off" class="form-control" id="FechaAutorizacionPO" value="<?php if (isset($row_Autorizaciones['FechaAutorizacion_SAPB1']) && ($row_Autorizaciones['FechaAutorizacion_SAPB1']->format('Y-m-d') != "1900-01-01")) {
																echo $row_Autorizaciones['FechaAutorizacion_SAPB1']->format('Y-m-d');
															} elseif (($row['AuthPortal'] != "P") && (isset($row['FechaAutorizacion_PortalOne']) && ($row['FechaAutorizacion_PortalOne']->format('Y-m-d') != "1900-01-01"))) {
																echo $row['FechaAutorizacion_PortalOne']->format('Y-m-d');
															} else {
																echo date('Y-m-d');
															} ?>" placeholder="YYYY-MM-DD">
														</div>
														<div class="col-lg-6 input-group clockpicker" data-autoclose="true">
															<input readonly name="HoraAutorizacionPO" form="CrearFacturaCompra" id="HoraAutorizacionPO" type="text" autocomplete="off" class="form-control" value="<?php if (isset($row_Autorizaciones['HoraAutorizacion_SAPB1'])) {
																echo $row_Autorizaciones['HoraAutorizacion_SAPB1'];
															} elseif (($row['AuthPortal'] != "P") && (isset($row['HoraAutorizacion_PortalOne']) && ($row['HoraAutorizacion_PortalOne']->format('H:i') != "00:00"))) {
																echo $row['HoraAutorizacion_PortalOne']->format('H:i');
															} else {
																echo date('H:i');
															} ?>" placeholder="hh:mm">
															<span class="input-group-addon">
																<span class="fa fa-clock-o"></span>
															</span>
														</div>
													</div>
												</div> <!-- form-group -->

												<br><br>
												<div class="form-group">
													<label class="col-lg-2">Decisión (Estado)</label>
													<div class="col-lg-10">
														<?php if (isset($row_Autorizaciones['EstadoAutorizacion'])) { ?>
																<input type="text" class="form-control" name="IdEstadoAutorizacion" id="IdEstadoAutorizacion" readonly
																value="<?php echo $row_Autorizaciones['EstadoAutorizacion']; ?>" style="font-weight: bold; color: white; background-color: <?php echo $row_Autorizaciones['ColorEstadoAutorizacion']; ?>;">
														<?php } else { ?>
																<select class="form-control" name="EstadoAutorizacionPO" id="EstadoAutorizacionPO" <?php if ((strtoupper($_SESSION["User"]) == strtoupper($row['Usuario'])) && (!$serAutorizador)) {
																	echo "disabled";
																} ?>>
																	<!-- El contenido se agrega por JS desde el componente "#Autorizacion", y hace cambiar dicho componente "onchange".  -->
																</select>
														<?php } ?>
													</div>
												</div>
												<br><br><br>
												<div class="form-group">
													<label class="col-lg-2">Usuario autorizador</label>
													<div class="col-lg-10">
														<?php if (isset($row_Autorizaciones['IdUsuarioAutorizacion_SAPB1'])) { ?>
																<input type="text" class="form-control" name="IdUsuarioAutorizacion" id="IdUsuarioAutorizacion" readonly
																value="<?php echo $row_Autorizaciones['NombreUsuarioAutorizacion_SAPB1']; ?>">
														<?php } else { ?>
															<input type="text" class="form-control" form="CrearFacturaCompra" name="UsuarioAutorizacionPO" id="UsuarioAutorizacionPO" value="<?php echo ($row["AuthPortal"] == "P") ? $_SESSION["User"] : $row["UsuarioAutorizacion_PortalOne"]; ?>" readonly>
														<?php } ?>
													</div>
												</div>
												<br><br><br>
												<div class="form-group">
													<label class="col-lg-2">Comentarios autorizador</label>
													<div class="col-lg-10">
														<textarea <?php if ($row["AuthPortal"] != "P") {
															echo "readonly";
														} ?> type="text" maxlength="200" rows="4" class="form-control" form="CrearFacturaCompra" name="ComentariosAutorizacionPO" id="ComentariosAutorizacionPO"><?php if (isset($row_Autorizaciones['ComentariosAutorizador_SAPB1'])) {
															 echo $row_Autorizaciones['ComentariosAutorizador_SAPB1'];
														 } elseif ($row["AuthPortal"] != "P") {
															 echo $row["ComentarioAutorizacion_PortalOne"];
														 } ?></textarea>
													</div>
												</div>
												<br><br><br><br>
											</div> <!-- ibox-content -->
										</div> <!-- ibox -->
									</div> <!-- modal-body -->

									<div class="modal-footer">
										<button type="button" class="btn btn-success m-t-md" id="formAUT_button"><i class="fa fa-check"></i> Enviar</button>
										<button type="button" class="btn btn-warning m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
									</div>
								<!-- /form -->
							</div>
						</div>
					</div>
				<?php } ?>
				<!-- Fin, modalAUT. SMM, 25/01/2024 -->

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
									<h3 class="no-margins"><?php echo (isset($row['CDU_FechaHoraCreacion']) && ($row['CDU_FechaHoraCreacion'] != "")) ? $row['CDU_FechaHoraCreacion']->format('Y-m-d H:i') : "&nbsp;"; ?></h3>
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
									<h3 class="no-margins"><?php echo (isset($row['CDU_FechaHoraActualizacion']) && ($row['CDU_FechaHoraActualizacion'] != "")) ? $row['CDU_FechaHoraActualizacion']->format('Y-m-d H:i') : "&nbsp;"; ?></h3>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
				<!-- Hasta aquí. SMM, 23/12/2023 -->
			
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
										<!-- SMM, 22/02/2023 -->
										<div class="btn-group">
											<button data-toggle="dropdown" class="btn btn-outline btn-success dropdown-toggle"><i class="fa fa-download"></i> Descargar formato <i class="fa fa-caret-down"></i></button>
											<ul class="dropdown-menu">
												<?php $SQL_Formato = Seleccionar('uvw_tbl_FormatosSAP', '*', "ID_Objeto=$IdTipoDocumento AND (IdFormato='" . $row['IdSeries'] . "' OR DeSeries IS NULL) AND VerEnDocumento='Y' AND EsBorrador='Y'"); ?>
												<?php while ($row_Formato = sqlsrv_fetch_array($SQL_Formato)) { ?>
													<li>
														<a class="dropdown-item" target="_blank" href="sapdownload.php?type=<?php echo base64_encode('2'); ?>&id=<?php echo base64_encode('15'); ?>&ObType=<?php echo base64_encode($row_Formato['ID_Objeto']); ?>&IdFrm=<?php echo base64_encode($row_Formato['IdFormato']); ?>&DocKey=<?php echo base64_encode($row['DocEntry']); ?>&IdReg=<?php echo base64_encode($row_Formato['ID']); ?>"><?php echo $row_Formato['NombreVisualizar']; ?></a>
													</li>
												<?php } ?>
											</ul>
										</div>
										<!-- Hasta aquí, 22/02/2023 -->
									</div>

									<div class="col-lg-6">
										<?php if ($row['DocDestinoDocEntry'] != "") { ?>
											<a href="entrada_compra.php?id=<?php echo base64_encode($row['DocDestinoDocEntry']); ?>&id_portal=<?php echo base64_encode($row['DocDestinoIdPortal']); ?>&tl=1"
												target="_blank" class="btn btn-outline btn-primary pull-right">Ir a documento
												destino <i class="fa fa-external-link"></i></a>
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
							<form action="factura_anticipo_compra_borrador.php" method="post" class="form-horizontal"
								enctype="multipart/form-data" id="CrearFacturaCompra">
								
								<?php
								$_GET['obj'] = "$IdTipoDocumento";
								include_once 'md_frm_campos_adicionales.php';
								?>

								<div class="form-group">
									<label class="col-md-8 col-xs-12">
										<h3 class="bg-success p-xs b-r-sm">
											<i class="fa fa-user"></i> Información de proveedor
										</h3>
									</label>

									<label class="col-md-4 col-xs-12">
										<h3 class="bg-success p-xs b-r-sm">
											<i class="fa fa-calendar"></i> Fechas y estado de documento
										</h3>
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
											} ?>">

											<input name="CardName" type="text" class="form-control"
												id="CardName" placeholder="Digite para buscar..." value="<?php if (($edit == 1) || ($sw_error == 1)) {
													echo $row['NombreCliente'];
												} ?>" <?php if ($edit == 1) {
													echo "readonly";
												} ?>>
										</div>
										<div class="col-lg-2">
											<input type="hidden" id="Exento" name="Exento" class="form-control" readonly>
											<input type="hidden" id="Definitivo" name="Definitivo" class="form-control" value="0">
										</div>
									</div>

									<div class="form-group">
										<label class="col-lg-1 control-label">
											Contacto
										</label>
										<div class="col-lg-5">
											<select class="form-control select2" id="ContactoCliente"
												name="ContactoCliente" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<option value="">Seleccione...</option>
												<?php if ($edit == 1 || $sw_error == 1) { ?>
													<?php while ($row_ContactoCliente = sqlsrv_fetch_array($SQL_ContactoCliente)) { ?>
														<option value="<?php echo $row_ContactoCliente['CodigoContacto']; ?>"
															<?php if ((isset($row['CodigoContacto'])) && (strcmp($row_ContactoCliente['CodigoContacto'], $row['CodigoContacto']) == 0)) {
																echo "selected";
															} ?>>
															<?php echo $row_ContactoCliente['ID_Contacto']; ?>
														</option>
													<?php } ?>
												<?php } ?>
											</select>
										</div>

										<!-- Inicio, Lista Precios SN -->
										<label class="col-lg-1 control-label">
											Lista Precios <!--span class="text-danger">*</span-->
										</label>
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
										<label class="col-lg-1 control-label">
											Sucursal destino
											</label>
										<div class="col-lg-5">
											<select class="form-control select2" name="SucursalDestino"
												id="SucursalDestino" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<option value="">Seleccione...</option>
												
												<?php if ($edit == 1 || $sw_error == 1) { ?>
													<optgroup label='Dirección de destino'></optgroup>

													<?php while ($row_SucursalDestino = sqlsrv_fetch_array($SQL_SucursalDestino)) { ?>
														<option value="<?php echo $row_SucursalDestino['NombreSucursal']; ?>"
															<?php if ((isset($row['SucursalDestino'])) && (strcmp($row_SucursalDestino['NombreSucursal'], $row['SucursalDestino']) == 0)) {
																echo "selected";
															} elseif (isset($_GET['Sucursal']) && (strcmp($row_SucursalDestino['NombreSucursal'], base64_decode($_GET['Sucursal'])) == 0)) {
																echo "selected";
															} elseif (isset($_GET['Sucursal']) && (strcmp(LSiqmlObs($row_SucursalDestino['NombreSucursal']), base64_decode($_GET['Sucursal'])) == 0)) {
																echo "selected";
															} ?>>
																<?php echo $row_SucursalDestino['NombreSucursal']; ?>
															</option>
													<?php } ?>
												<?php } ?>
											</select>
										</div>
										<label class="col-lg-1 control-label">
											Sucursal facturación
										</label>
										<div class="col-lg-5">
											<select class="form-control select2" name="SucursalFacturacion"
												id="SucursalFacturacion" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													echo "disabled";
												} ?>>
												<option value="">Seleccione...</option>
												
												<?php if ($edit == 1 || $sw_error == 1) { ?>
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
										<label class="col-lg-5">
											Fecha de contabilización
										</label>
										<div class="col-lg-7 input-group date">
											<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
												name="DocDate" type="text" class="form-control"
												id="DocDate" value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DocDate'];
												} else {
													echo date('Y-m-d');
												} ?>" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													 echo "readonly";
												 } ?>>
										</div>
									</div>

									<div class="form-group">
										<label class="col-lg-5">
											Fecha de entrada/servicio
										</label>
										<div class="col-lg-7 input-group date">
											<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
												name="DocDueDate" type="text" class="form-control"
												id="DocDueDate" value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['DocDueDate'];
												} else {
													echo date('Y-m-d');
												} ?>" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
													 echo "readonly";
												} ?>>
										</div>
									</div>
									<div class="form-group">
										<label class="col-lg-5">
											Fecha del documento
										</label>
										<div class="col-lg-7 input-group date">
											<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
												name="TaxDate" type="text" class="form-control"
												id="TaxDate" value="<?php if ($edit == 1 || $sw_error == 1) {
													echo $row['TaxDate'];
												} else {
													echo date('Y-m-d');
												} ?>" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
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
										<h3 class="bg-success p-xs b-r-sm">
											<i class="fa fa-info-circle"></i> Datos de la Factura Anticipo
										</h3>
									</label>
								</div>
								
								<div class="form-group">
									<label class="col-lg-1 control-label">
										Serie
									</label>
									<div class="col-lg-3">
										<select class="form-control select2" name="Serie" id="Serie" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
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
									
									<!-- SMM, 01/04/2024 -->
									<label class="col-lg-1 control-label">
										Condición de pago
									</label>
									<div class="col-lg-3">
										<select name="CondicionPago" class="form-control" id="CondicionPago" <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
												echo "disabled";
											} ?>>
											<option value="">Seleccione...</option>
											<?php while ($row_CondicionPago = sqlsrv_fetch_array($SQL_CondicionPago)) { ?>
													<option value="<?php echo $row_CondicionPago['IdCondicionPago']; ?>" 
														<?php if (isset($row['IdCondicionPago']) && ($row['IdCondicionPago'] != "") && ($row['IdCondicionPago'] == $row_CondicionPago['IdCondicionPago'])) {
															echo "selected";
														} elseif (isset($_GET['CondicionPago']) && ($_GET['CondicionPago'] == $row_CondicionPago['IdCondicionPago'])) {
															echo "selected";
														} ?>>
														<?php echo $row_CondicionPago['NombreCondicion'] ?? ""; ?>
													</option>
											<?php } ?>
										</select>
									</div>
									<!-- Hasta aquí. SMM, 01/04/2024 -->
								</div>

								<div class="form-group">
									<!-- SMM, 25/01/2024 -->
									<label class="col-lg-1 control-label">
										Autorización
										<?php if ((isset($row_Autorizaciones['IdEstadoAutorizacion']) && ($edit == 1)) || ($success == 0) || ($sw_error == 1) || $debug_Condiciones) { ?>
												<i onClick="verAutorizacion();" title="Ver Autorización" style="cursor: pointer" class="btn-xs btn-success fa fa-eye"></i>
										<?php } ?>
									</label>
									<div class="col-lg-3">
										<select name="Autorizacion" class="form-control" id="Autorizacion" readonly>
											<?php while ($row_EstadoAuth = sqlsrv_fetch_array($SQL_EstadoAuth)) { ?>
												<option value="<?php echo $row_EstadoAuth['IdAuth']; ?>" <?php if ($row_EstadoAuth['IdAuth'] == "N") {
														echo "disabled";
													} ?>
												<?php if (($edit == 1 || $sw_error == 1) && (isset($row['AuthPortal'])) && (strcmp($row_EstadoAuth['IdAuth'], $row['AuthPortal']) == 0)) {
													echo "selected";
												} elseif (isset($row_Autorizaciones['IdEstadoAutorizacion']) && ($row_Autorizaciones['IdEstadoAutorizacion'] == 'Y') && ($row_EstadoAuth['IdAuth'] == 'Y')) {
													echo "selected";
												} elseif (isset($row_Autorizaciones['IdEstadoAutorizacion']) && ($row_Autorizaciones['IdEstadoAutorizacion'] == 'W') && ($row_EstadoAuth['IdAuth'] == 'P')) {
													echo "selected";
												} elseif (($edit == 0 && $sw_error == 0) && ($row_EstadoAuth['IdAuth'] == 'N')) {
													echo "selected";
												} ?>>
													<?php echo ($row_EstadoAuth['IdAuth'] == "N") ? "Seleccione..." : $row_EstadoAuth['DeAuth']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<!-- Hasta aquí, 25/01/2024 -->

									<!-- Inicio, Proyecto -->
									<label class="col-lg-1 control-label">Proyecto <span 
										class="text-danger">*</span></label>
									<div class="col-lg-3">
										<select id="PrjCode" name="PrjCode" class="form-control select2" 
										form="CrearFacturaCompra" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
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
										<h3 class="bg-success p-xs b-r-sm">
											<i class="fa fa-list"></i> Contenido de la Factura Anticipo
										</h3>
									</label>
								</div>

								<div class="form-group">
									<div class="col-lg-4">
										<!-- SMM, 30/05/2023 -->
										<button <?php if ((($edit == 1) && ($row['Cod_Estado'] == 'C')) || (!PermitirFuncion(726))) {
											echo "disabled";
										} ?> class="btn btn-success" id="addArticulos"
											type="button" onclick="AgregarArticulos();"><i class="fa fa-plus"></i>
											Agregar artículo</button>

										<!-- SMM, 27/06/2023 -->
										<button <?php if ((($edit == 1) && ($row['Cod_Estado'] == 'C')) || (!PermitirFuncion(726))) {
											echo "disabled";
										} ?> class="btn btn-warning" id="updArticulos"
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
										<?php $ID_FacturaCompraAnticipo = $row['ID_FacturaCompraAnticipo']; ?>
										<?php $Evento = $row['IdEvento']; ?>
										<?php $consulta_detalle = "SELECT $filtro_consulta FROM uvw_tbl_FacturaCompraAnticipoDetalle_Borrador WHERE ID_FacturaCompraAnticipo='$ID_FacturaCompraAnticipo' AND IdEvento='$Evento' AND Metodo <> 3"; ?>
									<?php } else { ?>
										<?php $Usuario = $_SESSION['CodUser']; ?>
										<?php $cookie_cardcode = 1; ?>
										<?php $consulta_detalle = "SELECT $filtro_consulta FROM uvw_tbl_FacturaCompraAnticipoDetalleCarrito_Borrador WHERE Usuario='$Usuario'"; ?>
									<?php } ?>

									<div class="col-lg-1 pull-right">
										<a href="exportar_excel.php?exp=20&cookie_cardcode=<?php echo $cookie_cardcode; ?>&Cons=<?php echo base64_encode($consulta_detalle); ?>">
											<img src="css/exp_excel.png" width="50" height="30" alt="Exportar a Excel"
												title="Exportar a Excel">
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
													echo "detalle_factura_anticipo_compra_borrador.php";
												} elseif ($edit == 0 && $sw_error == 1) {
													echo "detalle_factura_anticipo_compra_borrador.php?id=0&type=1&usr=" . $_SESSION['CodUser'] . "&cardcode=" . $row['CardCode'];
												} else {
													echo "detalle_factura_anticipo_compra_borrador.php?bloquear=$BloquearDocumento&id=" . base64_encode($row['ID_FacturaCompraAnticipo']) . "&evento=" . base64_encode($row['IdEvento']) . "&docentry=" . base64_encode($row['DocEntry']) . "&type=2&status=" . base64_encode($EstadoReal);
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
							<?php if (($sw_error == 0)) {
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
								<label class="col-lg-2">Encargado de compras <span class="text-danger">*</span></label>
								<div class="col-lg-5">
									<select class="form-control select2" name="EmpleadoVentas" id="EmpleadoVentas"
										form="CrearFacturaCompra" required <?php if (($edit == 1) && ($row['Cod_Estado'] == 'C')) {
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
									<textarea type="text" maxlength="2000" name="Comentarios" form="CrearFacturaCompra"
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
										onclick="VerCamposAdi();">
										<i class="fa fa-list"></i> Ver campos adicionales
									</button>
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
									<input type="text" name="SubTotal" form="CrearFacturaCompra" id="SubTotal"
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
									<input type="text" name="Descuentos" form="CrearFacturaCompra" id="Descuentos"
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
									<input type="text" name="Impuestos" form="CrearFacturaCompra" id="Impuestos"
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
									<input type="text" name="Redondeo" form="CrearFacturaCompra" id="Redondeo"
										class="form-control" style="text-align: right; font-weight: bold;" value="0.00"
										readonly>
								</div>
							</div>

							<div class="form-group">
								<label class="col-lg-7"><strong class="pull-right">Total</strong></label>
								<div class="col-lg-5">
									<input type="text" name="TotalFacturaAnticipo" form="CrearFacturaCompra" id="TotalFacturaAnticipo"
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

							<?php if ($edit == 0 && PermitirFuncion(728)) { ?>
							<!-- button class="btn btn-primary" type="submit" form="CrearFacturaCompra" id="Crear"><i class="fa fa-check"></i> Crear Factura anticipo de Compras</button -->
							<?php } elseif ($row['Cod_Estado'] == "O" && PermitirFuncion(728)) { ?>

								<!-- SMM, 20/12/2022 -->
								<?php if ((strtoupper($_SESSION["User"]) != strtoupper($row['Usuario'])) || $serAutorizador) { ?>
									<!-- Modificado para incluir la bandera de asignación. SMM, 25/01/2024 -->
									<button class="btn btn-warning" type="submit" form="CrearFacturaCompra" id="Actualizar" <?php if (!$autorAsignado) {
										echo "disabled";
									} ?>>
										<i class="fa fa-refresh"></i> Actualizar Factura anticipo de Compras Borrador
									</button>
								<?php } else { ?>
									<?php if ($row["AuthPortal"] == "Y") { ?>
										<button class="btn btn-primary" type="submit" form="CrearFacturaCompra" id="Actualizar">
											<i class="fa fa-check"></i> Crear Factura anticipo de Compras Definitiva
										</button>
									<?php } ?>
								<?php } ?>

								<!-- Usuario de creación en el POST -->
								<input type="hidden" form="CrearFacturaCompra" name="Usuario" id="Usuario" value="<?php echo $row['Usuario']; ?>">
						<?php } ?>

						<?php
						// Eliminar mensajes. SMM, 25/01/2024
						$EliminaMsg = array("&a=" . base64_encode("OK_OCompAdd"), "&a=" . base64_encode("OK_OCompUpd")); 
						if (isset($_GET['return'])) {
							$_GET['return'] = str_replace($EliminaMsg, "", base64_decode($_GET['return']));
						}

						if (isset($_GET['return'])) {
							$return = base64_decode($_GET['pag']) . "?" . $_GET['return'];
						} elseif (isset($_POST['return'])) {
									$return = base64_decode($_POST['return']);
								} else {
									$return = "factura_anticipo_compra_borrador.php?" . $_SERVER['QUERY_STRING'];
								}
								$return = QuitarParametrosURL($return, array("a"));
								?>
								<a href="<?php echo $return; ?>" class="btn btn-outline btn-default"><i
										class="fa fa-arrow-circle-o-left"></i> Regresar</a>
							</div>

							<!-- Aquí va el copiar a otros documentos, 23/08/2022 -->
						
						</div>

						<input type="hidden" form="CrearFacturaCompra" id="P" name="P" value="<?php if ($edit == 0) {
							echo "37";
						} else {
							echo "39";
						} ?>" />

						<input type="hidden" form="CrearFacturaCompra" id="IdFacturaCompra" name="IdFacturaCompra" value="<?php if ($edit == 1) {
							echo base64_encode($row['ID_FacturaCompraAnticipo']);
						} ?>" />
						
						<input type="hidden" form="CrearFacturaCompra" id="IdEvento" name="IdEvento" value="<?php if ($edit == 1) {
							echo base64_encode($IdEvento);
						} ?>" />
						
						<input type="hidden" form="CrearFacturaCompra" id="tl" name="tl" value="<?php echo $edit; ?>" />
						
						<input type="hidden" form="CrearFacturaCompra" id="swError" name="swError"
							value="<?php echo $sw_error; ?>" />
						
						<input type="hidden" form="CrearFacturaCompra" id="return" name="return"
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
			// SMM, 21/12/2022
			<?php if ($BloquearDocumento) { ?>
				$("input").prop("readonly", true);
				$("select").attr("readonly", true);
				$("textarea").prop("readonly", true);

				// Desactivar sólo el botón de actualizar y no el definitivo.
				$("#Actualizar.btn-warning").prop("disabled", true);

				$("#addArticulos").prop("disabled", true);
				$("#updArticulos").prop("disabled", true);
				
				$('#PrjCode option:not(:selected)').attr('disabled', true);
			<?php } ?>
	
			// Estado de autorización de PortalOne en el Modal. SMM, 15/12/2022
			$("#EstadoAutorizacionPO").html($("#Autorizacion").html());
			$("#EstadoAutorizacionPO").on("change", function() {
				$("#Autorizacion option").prop("disabled", false); // SMM, 04/04/2023

				$("#Autorizacion").val($(this).val());
				$("#Autorizacion").change(); // SMM, 17/01/2023
			});

			// Estado de autorización PortalOne, para la creación y actualización. SMM, 16/12/2022
			/*
			// SMM, 17/01/2023
			$("#Autorizacion").on("change", function() {
				$("#EstadoAutorizacionPO").val($(this).val());

				if($(this).val() == "Y") {
					$("#Actualizar").text("Crear Solicitud de Traslado Definitiva");
					$("#Actualizar").removeClass("btn-warning").addClass("btn-primary");
				} else {
					$("#Actualizar").text("Actualizar Solicitud de Traslado Borrador");
					$("#Actualizar").removeClass("btn-primary").addClass("btn-warning");
				}
			});
			*/
				
			$("#CrearFacturaCompra").validate({
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
						"text": "Puede continuar con la actualización del documento.",
						"icon": "success"
					});

					// Cambiar estado de autorización a pendiente.
					if($("#Autorizacion").val() == "N") {
					$("#Autorizacion").val("P").change();

					// Corregir valores nulos en el combo de autorización.
					$('#Autorizacion option:selected').attr('disabled', false);
					$('#Autorizacion option:not(:selected)').attr('disabled', true);
				} else if($("#Autorizacion").val() == "P") {
					Swal.fire({
						"title": "¡Advertencia!",
						"text": "Debería cambiar el estado de la autorización por uno diferente.",
						"icon": "warning"
					});
				}

				// Ocultar Modal
					$('#modalAUT').modal('hide');
				}
			});
			// Almacenar campos autorización, hasta aquí.

			maxLength('Comentarios'); // SMM, 15/07/2022
			maxLength('ComentariosAutor'); // SMM, 15/07/2022

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

			// $('.chosen-select').chosen({width: "100%"});
			$(".select2").select2();
			
			$('.i-checks').iCheck({
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green',
			});

			<?php if ($edit == 1) { ?>
				// $('#Serie option:not(:selected)').attr('disabled',true);
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
			
			<?php if ($edit == 0) { ?>
				$("#CardName").easyAutocomplete(options);
			<?php } ?>

			<?php if ($edit == 0) { ?>
				$('#Serie').trigger('change');
			<?php } ?>

			// SMM, 18/12/2022
			<?php if ((strtoupper($_SESSION["User"]) == strtoupper($row['Usuario'])) && (!$serAutorizador)) { ?>
				// Desactivado, 03/04/2023
				// $('#Autorizacion option:not(:selected)').attr('disabled',true);
			<?php } ?>

			// SMM, 01/04/2024
			$('#Autorizacion option:not(:selected)').attr('disabled', true);
			$('#AutorizacionSAP option:not(:selected)').attr('disabled', true);
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
						} ?>&objtype=<?php echo $IdTipoDocumento; ?>",
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
					objtype: <?php echo $IdTipoDocumento; ?>,
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
			let ordenServicio = $("#OrdenServicioCliente").val();

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
						ObjType: <?php echo $IdTipoDocumento; ?>,
						OT: ordenServicio,
						Edit: <?php echo $edit; ?>,
						DocType: "<?php echo ($edit == 0) ? 18 : 19; ?>",
						DocId: "<?php echo $row['ID_FacturaCompraAnticipo'] ?? 0; ?>",
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
						DocType: "<?php echo 16; ?>",
						DocId: "<?php echo $row['ID_FacturaCompraAnticipo'] ?? 0; ?>",
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
