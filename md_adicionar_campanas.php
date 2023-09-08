<?php
require_once "includes/conexion.php";

// SMM, 25/02/2023
$msg_error_detalle = "";
$parametros_detalle = array();

$coduser = $_SESSION['CodUser'];
$datetime_detalle = FormatoFecha(date('Y-m-d'), date('H:i:s'));

$type_detalle = $_POST['type'] ?? 0;
$ID_detalle = $_POST['ID'] ?? "";

$id_campana_detalle = $_POST['id_campana_detalle'] ?? "NULL";
$VIN_detalle = $_POST['VIN'] ?? "";

$id_usuario_creacion_detalle = "'$coduser'";
$fecha_creacion_detalle = "'$datetime_detalle'";
$hora_creacion_detalle = "'$datetime_detalle'";
$id_usuario_actualizacion_detalle = "'$coduser'";
$fecha_actualizacion_detalle = "'$datetime_detalle'";
$hora_actualizacion_detalle = "'$datetime_detalle'";

if ($type_detalle == 1) {
	$msg_error = "No se pudo crear el VIN.";

	$parametros = array(
		$type_detalle,
		"'$ID_detalle'",
		$id_campana_detalle,
		"'$VIN_detalle'",
		$id_usuario_actualizacion_detalle,
		$fecha_actualizacion_detalle,
		$hora_actualizacion_detalle,
		$id_usuario_creacion_detalle,
		$fecha_creacion_detalle,
		$hora_creacion_detalle,
	);

} elseif ($type_detalle == 2) {
	$msg_error = "No se pudo actualizar el VIN.";

	$parametros = array(
		$type_detalle,
		"'$ID_detalle'",
		$id_campana_detalle,
		"'$VIN_detalle'",
		$id_usuario_actualizacion_detalle,
		$fecha_actualizacion_detalle,
		$hora_actualizacion_detalle,
	);

} elseif ($type_detalle == 3) {
	$msg_error = "No se pudo eliminar el VIN.";

	$parametros = array(
		$type_detalle,
		"'$ID_detalle'",
		$id_campana_detalle,
	);
}

if ($type_detalle != 0) {
	$SQL_Operacion = EjecutarSP('sp_tbl_CampanaVehiculosDetalle', $parametros);

	if (!$SQL_Operacion) {
		echo $msg_error_detalle;
	} else {
		$row = sqlsrv_fetch_array($SQL_Operacion);

		if (isset($row['Error']) && ($row['Error'] != "")) {
			echo "$msg_error_detalle";
			echo "(" . $row['Error'] . ")";
		} else {
			echo "OK";
		}
	}

	// Mostrar mensajes AJAX.
	exit();
}
?>

<style>
	.select2-container {
		z-index: 10000;
	}

	.select2-search--inline {
		display: contents;
	}

	.select2-search__field:placeholder-shown {
		width: 100% !important;
	}

	.panel-heading a:before {
		font-family: 'Glyphicons Halflings';
		content: "\e114";
		float: right;
		transition: all 0.5s;
	}

	.panel-heading.active a:before {
		-webkit-transform: rotate(180deg);
		-moz-transform: rotate(180deg);
		transform: rotate(180deg);
	}

	.ibox-title a {
		color: inherit !important;
	}

	.collapse-link:hover {
		cursor: pointer;
	}

	.swal2-container {
		z-index: 9000;
	}

	.easy-autocomplete {
		width: 100% !important
	}
</style>

<form id="frmCampanasDetalle" method="post" enctype="multipart/form-data">
	<div class="modal-header">
		<h4 class="modal-title">Crear VIN a Campaña</h4>
	</div>
	<!-- /.modal-title -->

	<div class="modal-body">
		<div class="form-group">
			<div class="ibox-content">
				<?php include "includes/spinner.php"; ?>

				<div class="row">
					<div class="col-md-12">
						<label class="control-label">Lista VIN</label>
						<textarea name="ListaVIN" rows="5" maxlength="3000" class="form-control" id="ListaVIN"
							type="text"></textarea>
					</div>
				</div>
				<!-- /.row -->

				<div class="panel-body">
					<div class="row">
						<button type="button" onclick="AdicionarCampana()" class="alkin btn btn-primary btn-xs"><i
								class="fa fa-plus-circle"></i> Adicionar Campaña</button>
					</div>
					<br>
					<!-- Table Campanas -->
					<div class="row">
						<div class="col-12 text-center">
							<div class="ibox-content">
								<?php if ($hasRowsCampanas) { ?>
									<div class="table" style="max-height: 230px; overflow-y: auto;">
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
															<a href="campanas_vehiculo.php?id=<?php echo $row_Campana['id_campana']; ?>&edit=1"
																class="btn btn-success btn-xs" target="_blank">
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
															<button type="button"
																id="btnDelete<?php echo $row_Detalle['id_campana_detalle']; ?>"
																class="btn btn-danger btn-xs"
																onclick="EliminarRegistro('<?php echo $row_Detalle['id_campana_detalle']; ?>');"><i
																	class="fa fa-trash"></i>
																Eliminar</button>
														</td>
													</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
								<?php } else { ?>
									<i class="fa fa-search" style="font-size: 18px; color: lightgray;"></i>
									<span style="font-size: 13px; color: lightgray;">No hay registros de Campañas de
										Vehículo</span>
								<?php } ?>
							</div>
						</div>
					</div>
					<!-- End Table Campanas -->
				</div>
				<!-- /.panel-body -->
			</div>
			<!-- /.ibox-content -->
		</div>
		<!-- /.form-group -->
	</div>
	<!-- /.modal-body -->

	<div class="modal-footer">
		<button type="submit" class="btn btn-success m-t-md" id="btnAdicionar" disabled><i class="fa fa-check"></i>
			Aceptar</button>

		<button type="button" class="btn btn-info m-t-md pull-left" onclick="Validar();"><i class="fa fa-thumbs-up"></i>
			Validar</button>

		<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal" id="btnCerrar"><i
				class="fa fa-times"></i>
			Cerrar</button>
	</div>
	<!-- /modal-footer -->
</form>

<script>
	$(document).ready(function () {
		// SMM, 19/08/2022
		$('.panel-collapse').on('show.bs.collapse', function () {
			$(this).siblings('.panel-heading').addClass('active');
		});

		$('.panel-collapse').on('hide.bs.collapse', function () {
			$(this).siblings('.panel-heading').removeClass('active');
		});
		// Hasta aquí, 19/08/2022

		$("#frmCampanasDetalle").validate({
			submitHandler: function (form) {
				// Obtén el valor del campo de entrada
				let listaVINs = $("#ListaVIN").val();

				// Divide la lista en un arreglo usando el separador ";"
				let arregloVINs = listaVINs.split(";");

				// Limpia los espacios en blanco y elementos vacíos del arreglo
				arregloVINs = arregloVINs.map(function (vin) {
					return vin.trim();
				}).filter(function (vin) {
					return vin !== "";
				});

				// Validación del ciclo
				var validarAjax = true;
				var contadorAjax = 0;

				// Iterar sobre cada VIN y realizar una llamada AJAX por separado
				arregloVINs.forEach(function (vin) {
					$.ajax({
						type: "POST",
						url: "md_campanas_vehiculo.php",
						data: {
							type: 1,
							ID: $("#id_campana").val(),
							VIN: vin,  // Usar el VIN actual en esta iteración
						},
						success: function (response) {
							console.log(response);

							contadorAjax++;
							if (response !== "OK") {
								validarAjax = false;
							}

							// Verificar si todas las solicitudes AJAX han finalizado
							if (contadorAjax === arregloVINs.length) {
								Swal.fire({
									icon: (validarAjax) ? "success" : "warning",
									title: (validarAjax) ? "¡Listo!" : "¡Error!",
									text: (validarAjax) ? "Todos los VINs se insertaron correctamente." : "No se pudieron insertar algunos VINs, por favor verifique."
								}).then((result) => {
									if (result.isConfirmed) {
										// if(validarAjax) {

										// Obtén la URL actual
										let currentUrl = new URL(window.location.href);

										// Obtén los parámetros del query string
										let searchParams = currentUrl.searchParams;

										// Actualiza el valor del parámetro 'active' o agrega si no existe
										searchParams.set('active', 1);

										// Crea una nueva URL con los parámetros actualizados
										let newUrl = currentUrl.origin + currentUrl.pathname + '?' + searchParams.toString();

										// Recarga la página con la nueva URL
										window.location.href = newUrl;

										// }
									}
								});
								// Swal.fire
							}
						},
						error: function (error) {
							console.error("240->", error.responseText);

							validarAjax = false;
						}
					});
				});
				// .forEach()
			}
			// submitHandler
		});

		$('.chosen-select').chosen({ width: "100%" });
		$(".select2").select2();

		$("#ListaVIN").on("input", function () {
			$("#btnAdicionar").prop("disabled", true);
		});

		$("#btnCerrar").on("click", function () {
			// Obtén la URL actual
			let currentUrl = new URL(window.location.href);

			// Obtén los parámetros del query string
			let searchParams = currentUrl.searchParams;

			// Actualiza el valor del parámetro 'active' o agrega si no existe
			searchParams.set('active', 1);

			// Crea una nueva URL con los parámetros actualizados
			let newUrl = currentUrl.origin + currentUrl.pathname + '?' + searchParams.toString();

			// Recarga la página con la nueva URL
			window.location.href = newUrl;
		});
	});
</script>

<script>
	function Validar() {
		let listaVINs = $("#ListaVIN").val();

		// Divide la lista en un arreglo usando el separador ";"
		let arregloVINs = listaVINs.split(";");

		// Limpia los espacios en blanco y elementos vacíos del arreglo
		arregloVINs = arregloVINs.map(function (vin) {
			return vin.trim();
		}).filter(function (vin) {
			return vin !== "";
		});

		// Realiza la validación
		let formatoCorrecto = arregloVINs.every(function (vin) {
			// Verificar si tiene entre 1 y 32 caracteres alfanuméricos.
			return /^[a-zA-Z0-9]{1,32}$/.test(vin);
		});

		if (formatoCorrecto && (listaVINs != "")) {
			Swal.fire({
				title: '¡Listo!',
				text: 'Puede continuar con el proceso dando clic en el botón Aceptar.',
				icon: 'success'
			}).then((result) => {
				if (result.isConfirmed) {
					$("#btnAdicionar").prop("disabled", false);
				}
			});

			// Haz algo con los VINs válidos en arregloVINs
			console.log("VINs válidos:", arregloVINs);
		} else {
			Swal.fire({
				title: '¡Error!',
				text: 'La estructura es incorrecta, por favor verifique.',
				icon: 'warning'
			});
		}
	}
</script>