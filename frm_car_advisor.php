<?php require_once "includes/conexion.php";
PermitirAcceso(1711);

$IdFrm = "";
$msg_error = ""; //Mensaje del error
$dt_LS = 0; //sw para saber si vienen datos del SN. 0 no vienen. 1 si vienen.

if (isset($_GET['dt_LS']) && ($_GET['dt_LS']) == 1) { // Verificar que viene de una Llamada de servicio
	$dt_LS = 1;

	// Orden de servicio, OT
	$SQL_OrdenServicio = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . base64_decode($_GET['LS']) . "'");
	$row_OT = sqlsrv_fetch_array($SQL_OrdenServicio);
}

if (isset($_POST['swError']) && ($_POST['swError'] != "")) { //Para saber si ha ocurrido un error.
	$sw_error = $_POST['swError'];
} else {
	$sw_error = 0;
}

$Title = "Crear nuevo Car Advisor";

$SQL_ID = Seleccionar("tbl_FormularioCarAdvisor", "MAX(id_formulario_caradvisor) + 1 AS next_id");
$row_ID = sqlsrv_fetch_array($SQL_ID);
$id = $row_ID["next_id"] ?? "";

// Consumo la API para Crear e Integrar el CarAdvisor. SMM, 30/01/2024
$id_formulario_caradvisor = $_POST["id_formulario_caradvisor"] ?? "";
if ($id_formulario_caradvisor != "") {
	$firstname = $_POST["firstname"] ?? "";
	$lastname = $_POST["lastname"] ?? "";
	$email = $_POST["email"] ?? "";
	$zip = $_POST["zip"] ?? "";
	$city = $_POST["city"] ?? "";
	$street = $_POST["street"] ?? "";
	$phone = $_POST["phone"] ?? "";
	$phonecompany = $_POST["phonecompany"] ?? "";
	$phonemobile = $_POST["phonemobile"] ?? "";
	$modelvariant = $_POST["modelvariant"] ?? "";
	$CodUser = $_SESSION["CodUser"] ?? "";
	$User = $_SESSION["User"] ?? "";

	$Cabecera = array(
		"estado" => "O",
		"vin" => "9BWBH6BFXM4063505",
		"modelcode" => "",
		"brand" => "V",
		"modelvariant" => "$modelvariant",
		"deliverydate" => "2023-12-26T00:00:00",
		"connected" => "false",
		"dealernr" => 28032,
		"agentid" => 0,
		"kdbid" => "",
		"employeefirstname" => "ADRIAN",
		"employeelastname" => "GALEANO RODRIGUEZ",
		"cupraspecialist" => "false",
		"gaid" => "",
		"salutation" => "M",
		"academictitle" => "",
		"firstname" => "$firstname",
		"lastname" => "$lastname",
		"zip" => "$zip",
		"city" => "$city",
		"street" => "$street",
		"phone" => "$phone",
		"phonecompany" => "$phonecompany",
		"phonemobile" => "$phonemobile",
		"email" => "$email",
		"type" => "SERVICE",
		"invoicedate" => "2023-12-26T00:00:00",
		"annualservice" => "false",
		"iacs" => "false",
		"emobility" => "false",
		"id_usuario_cierre" => null,
		"fecha_cierre" => null,
		"hora_cierre" => null,
		"comentarios_cierre" => null,
		"app" => "PortalOne",
		"id_documento_base" => 111000181,
		"id_tipo_objeto_documento_base" => "191",
		"id_usuario_creacion" => $CodUser,
		"usuario_creacion" => "$User",
		"id_llamada_servicio" => 111000181,
		"docentry_llamada_servicio" => 44178
	);

	try {
		$Metodo = "FormularioCarAdvisor/CrearIntegrar";
		$Resultado = EnviarWebServiceSAP($Metodo, $Cabecera, true, true);
	
		if ($Resultado->Success == 0) {
			$msg_error = $Resultado->Mensaje;
			echo "No se pudo actualizar el registro. ($msg_error)";
		} else {
			echo "OK";
		}
	} catch (Exception $e) {
		$msg_error = $e->getMessage();
		echo "Excepción capturada, CrearIntegrar: $msg_error";
	}

	// Mostrar mensajes AJAX.
	exit();
}
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

	<script type="text/javascript">
		$(document).ready(function () {
			// Espacio para nuevo código jQuery.
		});

		// Espacio para nueva funciones JS.
	</script>
	<!-- InstanceEndEditable -->
</head>

<body>

	<div id="wrapper">

		<?php include "includes/menu.php"; ?>

		<div id="page-wrapper" class="gray-bg">
			<?php include "includes/menu_superior.php"; ?>
			<!-- InstanceBeginEditable name="Contenido" -->
			<div class="row wrapper border-bottom white-bg page-heading">
				<div class="col-sm-8">
					<h2>
						<?php echo $Title; ?>
					</h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">
								<?php echo "Formularios"; ?>
							</a>
						</li>
						<li class="active">
							<a href="<?php echo "consultar_frm_car_advisor.php"; ?>">
								<?php echo "Car Advisor"; ?>
							</a>
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
				<div class="ibox-content">
					<?php include "includes/spinner.php"; ?>
					<div class="row">
						<div class="col-lg-12">
							<form action="frm_entrega_vehiculo.php" method="post" class="form-horizontal"
								enctype="multipart/form-data" id="formCA">
								<!-- IBOX, Inicio -->
								<div class="ibox">
									<div class="ibox-title bg-success">
										<h5 class="collapse-link"><i class="fa fa-car"></i> Información Car Advisor</h5>
										<a class="collapse-link pull-right" style="color: white;">
											<i class="fa fa-chevron-up"></i>
										</a>
									</div>
									<div class="ibox-content">
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">
													ID <span class="text-danger">*</span>
												</label>
												<input required readonly type="text" class="form-control"
													name="id_formulario_caradvisor" id="id_formulario_caradvisor"
													value="<?php echo $id; ?>">
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Nombre Contacto <span class="text-danger">*</span>
												</label>
												<input required maxlength="40" autocomplete="off" type="text"
													class="form-control" name="firstname" id="firstname"
													value="<?php echo $row_OT["NombreClienteLlamada"] ?? ""; ?>">
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Apellido Contacto <span class="text-danger">*</span>
												</label>
												<input required maxlength="40" autocomplete="off" type="text"
													class="form-control" name="lastname" id="lastname"
													value="<?php echo $row_OT["NombreClienteLlamada"] ?? ""; ?>">
											</div>
										</div>

										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">
													ZIP <span class="text-danger">*</span>
												</label>
												<input required max="99999999" autocomplete="off" type="number"
													class="form-control" name="zip" id="zip"
													value="<?php echo $row_OT["zip"] ?? ""; ?>">
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Ciudad <span class="text-danger">*</span>
												</label>
												<input required maxlength="20" autocomplete="off" type="text"
													class="form-control" name="city" id="city"
													value="<?php echo $row_OT["NombreSucursal"] ?? ""; ?>">
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Dirección <span class="text-danger">*</span>
												</label>
												<input required maxlength="20" autocomplete="off" type="text"
													class="form-control" name="street" id="street"
													value="<?php echo $row_OT["DireccionLlamada"] ?? ""; ?>">
											</div>
										</div>

										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">
													Teléfono <span class="text-danger">*</span>
												</label>
												<input required max="9999999999" autocomplete="off" type="number"
													class="form-control" name="phone" id="phone"
													value="<?php echo $row_OT["TelefonoContactoLlamada"] ?? ""; ?>">
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Teléfono Compañía <span class="text-danger">*</span>
												</label>
												<input required max="9999999999" autocomplete="off" type="number"
													class="form-control" name="phonecompany" id="phonecompany"
													value="<?php echo $row_OT["TelefonoContactoLlamada"] ?? ""; ?>">
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Teléfono Móvil <span class="text-danger">*</span>
												</label>
												<input required max="9999999999" autocomplete="off" type="number"
													class="form-control" name="phonemobile" id="phonemobile"
													value="<?php echo $row_OT["TelefonoContactoLlamada"] ?? ""; ?>">
											</div>
										</div>

										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">
													Correo electrónico <span class="text-danger">*</span>
												</label>
												<input required maxlength="100" autocomplete="off" type="email"
													class="form-control" name="email" id="email"
													value="<?php echo $row_OT["CorreoContactoLlamada"] ?? ""; ?>">
											</div>

											<div class="col-lg-4">
												<label class="control-label">
													Modelo Variante <span class="text-danger">*</span>
												</label>
												<input required maxlength="20" autocomplete="off" type="text"
													class="form-control" name="modelvariant" id="modelvariant"
													value="<?php echo $row_OT["modelvariant"] ?? ""; ?>">
											</div>
										</div>
									</div>
								</div>
								<!-- IBOX, Fin -->

								<!-- Inicio, relacionado al $return -->
								<?php
								$EliminaMsg = array("&a=" . base64_encode("OK_FrmAdd"), "&a=" . base64_encode("OK_FrmUpd"), "&a=" . base64_encode("OK_FrmDel")); //Eliminar mensajes
								
								if (isset($_GET['return'])) {
									$_GET['return'] = str_replace($EliminaMsg, "", base64_decode($_GET['return']));
								}
								if (isset($_GET['return'])) {
									$return = base64_decode($_GET['pag']) . "?" . $_GET['return'];
								} else {
									$return = "consultar_frm_car_advisor.php";
								}
								?>
								<!-- Fin, relacionado al $return -->

								<!-- Campos ocultos -->
								<input type="hidden" id="return" name="return"
									value="<?php echo base64_encode($return); ?>" />
							</form>

							<!-- Botones de acción al final del formulario, SMM -->
							<div class="form-group">
								<div class="col-lg-9">
									<button class="btn btn-primary" form="formCA" type="submit" id="Crear"><i
											class="fa fa-check"></i> Registrar formulario</button>
									<a href="<?php echo $return; ?>" class="alkin btn btn-outline btn-default"><i
											class="fa fa-arrow-circle-o-left"></i> Regresar</a>
								</div>
							</div>
							<!-- Pendiente a agregar al formulario, SMM -->
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
			$('#formCA').on('submit', function (event) {
				event.preventDefault();
			});

			$("#formCA").validate({
				submitHandler: function (form) {
					$('.ibox-content').toggleClass('sk-loading', true); // Cargando...

					let formData = new FormData(form);

					// Ejemplo de como agregar nuevos campos.
					// formData.append("Dim1", $("#Dim1").val() || "");

					let json = Object.fromEntries(formData);
					console.log("Line 140", json);

					// Inicio, AJAX
					$.ajax({
						url: 'frm_car_advisor.php',
						type: 'POST',
						data: formData,
						processData: false,  // tell jQuery not to process the data
						contentType: false,   // tell jQuery not to set contentType
						success: function (response) {
							console.log("Line 330", response);

							if (response === "OK") {
								Swal.fire({
									icon: "success",
									title: "¡Listo!",
									text: "Se agrego el registro correctamente."
								}).then((result) => {
									if (result.isConfirmed) {
										location.reload();
									}
								});
							} else {
								Swal.fire({
									icon: "warning",
									title: "¡Error!",
									text: response
								});
							}

							$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
						},
						error: function (error) {
							console.error(error.responseText);

							$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
						}
					});
					// Fin, AJAX
				}
			});

			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});

			$(".select2").select2();

			$('.i-checks').iCheck({
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green',
			});
		});
	</script>

	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>

<?php sqlsrv_close($conexion); ?>