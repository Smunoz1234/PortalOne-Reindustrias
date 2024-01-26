<?php require_once "includes/conexion.php";
PermitirAcceso(1711);

$IdFrm = "";
$msg_error = ""; //Mensaje del error
$dt_LS = 0; //sw para saber si vienen datos del SN. 0 no vienen. 1 si vienen.

if (isset($_GET['dt_LS']) && ($_GET['dt_LS']) == 1) { // Verificar que viene de una Llamada de servicio
	$dt_LS = 1;

	// Orden de servicio
	$SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . base64_decode($_GET['LS']) . "'");
}

if (isset($_POST['swError']) && ($_POST['swError'] != "")) { //Para saber si ha ocurrido un error.
	$sw_error = $_POST['swError'];
} else {
	$sw_error = 0;
}

if ($type_frm == 0) {
	$Title = "Crear nuevo Car Advisor";
} else {
	$Title = "Editar Car Advisor"; // useless
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
			// Espacio para nuevo código jQuery
		});
		
		// Espacio para nueva funciones JS
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
							<a
								href="<?php echo "consultar_frm_car_advisor.php"; ?>">
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
								enctype="multipart/form-data" id="entregaForm">
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
												<label class="control-label">Teléfono <span
														class="text-danger">*</span></label>

												<input name="telefono" type="text" class="form-control" id="telefono"
													required maxlength="50" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {
														echo "readonly";
													} ?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {
														  echo $row['TelefonoContacto'];
													  } elseif ($dt_LS == 1) {
														  echo isset($_GET['Telefono']) ? base64_decode($_GET['Telefono']) : "";
													  } ?>">
											</div>
											<div class="col-lg-4">
												<label class="control-label">Celular</label>

												<input name="celular" type="text" class="form-control" id="celular"
													maxlength="50" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {
														echo "readonly";
													} ?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {
														  echo $row['CelularContacto'];
													  } elseif ($dt_LS == 1) {
														  echo isset($_GET['Celular']) ? base64_decode($_GET['Celular']) : "";
													  } ?>">
											</div>
											<div class="col-lg-4">
												<label class="control-label">Correo <span
														class="text-danger">*</span></label>

												<input name="correo" type="email" class="form-control" id="correo"
													required maxlength="100" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {
														echo "readonly";
													} ?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {
														  echo $row['CorreoContacto'];
													  } elseif ($dt_LS == 1) {
														  echo isset($_GET['Correo']) ? base64_decode($_GET['Correo']) : "";
													  } ?>">
											</div>
										</div>
										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label">Dirección</label>

												<input name="direccion_destino" type="text" class="form-control"
													id="direccion_destino" maxlength="100" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {
														echo "readonly";
													} ?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {
														  echo $row['Direccion'];
													  } elseif ($dt_LS == 1) {
														  echo isset($_GET['Direccion']) ? base64_decode($_GET['Direccion']) : "";
													  } ?>">
											</div>
											<div class="col-lg-4">
												<label class="control-label">Barrio</label>

												<input name="barrio" type="text" class="form-control" id="barrio"
													maxlength="50" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {
														echo "readonly";
													} ?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {
														  echo $row['barrio'];
													  } elseif ($dt_LS == 1) {
														  echo isset($_GET['Barrio']) ? base64_decode($_GET['Barrio']) : "";
													  } ?>">
											</div>

											<div class="col-lg-4">
												<label class="control-label">Ciudad</label>

												<input name="city" type="text" class="form-control" id="city"
													maxlength="50" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {
														echo "readonly";
													} ?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {
														  echo $row['barrio'];
													  } elseif ($dt_LS == 1) {
														  echo isset($_GET['Barrio']) ? base64_decode($_GET['Barrio']) : "";
													  } ?>">
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
									// Stiven Muñoz Murillo, 10/01/2022
									$return = "consultar_frm_entrega_vehiculo.php?id=" . $frm;
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
									<?php if ($type_frm == 0) { ?>
										<button class="btn btn-primary" form="entregaForm" type="submit" id="Crear"><i
												class="fa fa-check"></i> Registrar formulario</button>
									<?php } ?>
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
		var anexos = []; // SMM, 16/02/2022

		// Stiven Muñoz Murillo, 11/01/2022
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
				var indice = anexos.indexOf(file.name);
				if (indice !== -1) {
					anexos.splice(indice, 1);
				}

				$.get("includes/procedimientos.php", {
					type: "3",
					nombre: file.name
				}).done(function (data) {
					var _ref;
					return (_ref = file.previewElement) !== null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
				});
			},
			init: function (file) {
				this.on("addedfile", file => {
					anexos.push(file.name); // SMM, 16/02/2022
					console.log("Line 1057, Dropzone(addedfile)", file.name);

					// SMM, 28/09/2022
					$("#Crear").prop("disabled", true);
				});
			},
			queuecomplete: function () {
				console.log("Line 1087, Dropzone(queuecomplete)");

				// SMM, 28/09/2022
				$("#Crear").prop("disabled", false);
			}
		};
	</script>

	<script>
		$(document).ready(function () {
			maxLength('observaciones'); // SMM, 02/03/2022

			var bandera_fechas = false; // SMM, 25/02/2022
			$('#entregaForm').on('submit', function (event) {
				// Stiven Muñoz Murillo, 08/02/2022
				event.preventDefault();

				// Stiven Muñoz Murillo, 25/02/2022
				let d1 = new Date(`${$('#fecha_ingreso').val()} ${$('#hora_ingreso').val()}`);
				let d2 = new Date(`${$('#fecha_aprox_entrega').val()} ${$('#hora_aprox_entrega').val()}`);

				console.log(d1);
				console.log(d2);

				// Stiven Muñoz Murillo, 25/02/2022
				bandera_fechas = (d1 > d2) ? true : false;
			});

			$("#entregaForm").validate({
				submitHandler: function (form) {
					if (bandera_fechas) {
						Swal.fire({
							"title": "¡Ha ocurrido un error!",
							"text": "La fecha de ingreso no puede superar a la fecha de entrega.",
							"icon": "warning"
						});
					} else {
						Swal.fire({
							title: "¿Desea continuar con el registro?",
							icon: "question",
							showCancelButton: true,
							confirmButtonText: "Si, confirmo",
							cancelButtonText: "No"
						}).then((result) => {
							if (result.isConfirmed) {
								$('.ibox-content').toggleClass('sk-loading', true); // Carga iniciada.

								let formData = new FormData(form);
								Object.entries(photos).forEach(([key, value]) => formData.append(key, value));
								Object.entries(anexos).forEach(([key, value]) => formData.append(`Anexo${key}`, value));

								// Agregar valores de las listas
								formData.append("id_llamada_servicio", $("#id_llamada_servicio").val());
								formData.append("id_marca", $("#id_marca").val());
								formData.append("id_linea", $("#id_linea").val());
								formData.append("id_annio", $("#id_annio").val());
								formData.append("id_color", $("#id_color").val());

								let json = Object.fromEntries(formData);
								localStorage.entregaForm = JSON.stringify(json);

								console.log("Line 1790", json);

								// Inicio, AJAX
								$.ajax({
									url: 'frm_entrega_vehiculo_ws.php',
									type: 'POST',
									data: formData,
									processData: false,  // tell jQuery not to process the data
									contentType: false,   // tell jQuery not to set contentType
									success: function (response) {
										console.log("Line 1273", response);

										try {
											let json_response = JSON.parse(response);
											Swal.fire(json_response).then(() => {
												if (json_response.hasOwnProperty('return')) {
													window.location = json_response.return;
												}
											});
										} catch (error) {
											console.log("Line 1283", error);
										}

										$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
									},
									error: function (response) {
										console.error("server error")
										console.error(response);

										$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
									}
								});
								// Fin, AJAX
							} else {
								console.log("Registro NO confirmado.")
							}
						}); // SMM, 14/06/2022
					}
				}
			});

			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});

			// Inicio, sección de fechas y horas.
			if (!$('#fecha_ingreso').prop('readonly')) {
				$('#fecha_ingreso').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					calendarWeeks: true,
					autoclose: true,
					format: 'yyyy-mm-dd',
					todayHighlight: true,
					endDate: '<?php echo date('Y-m-d'); ?>'
				});

				$('#hora_ingreso').clockpicker({
					donetext: 'Done'
				});
			}
			if (!$('#fecha_autoriza_campana').prop('readonly')) {
				$('#fecha_autoriza_campana').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					calendarWeeks: true,
					autoclose: true,
					format: 'yyyy-mm-dd',
					todayHighlight: true
				});

				$('#hora_autoriza_campana').clockpicker({
					donetext: 'Done'
				});
			}
			if (!$('#fecha_aprox_entrega').prop('readonly')) {
				$('#fecha_aprox_entrega').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					calendarWeeks: true,
					autoclose: true,
					format: 'yyyy-mm-dd',
					todayHighlight: true
				});

				$('#hora_aprox_entrega').clockpicker({
					donetext: 'Done'
				});
			}
			// Fin, sección de fechas y horas.


			$(".select2").select2();
			$('.i-checks').iCheck({
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green',
			});

			<?php if ($dt_LS == 1) { ?>
				$('#SucursalCliente option:not(:selected)').attr('disabled', true);
				$('#id_llamada_servicio option:not(:selected)').attr('disabled', true);

				// Stiven Muñoz Murillo, 20/01/2022
				$('#id_llamada_servicio').trigger('change');
				$('#id_socio_negocio').trigger('change');
			<?php } ?>
		});
	</script>

	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>

<?php sqlsrv_close($conexion); ?>
