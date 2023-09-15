<?php
require_once "includes/conexion.php";

$msg_error_anotacion = "";
$parametros_anotacion = array();

$coduser = $_SESSION["CodUser"];
$datetime_anotacion = FormatoFecha(date("Y-m-d"), date("H:i:s"));

$type_anotacion = $_POST["type"] ?? 0;

$id_solicitud_llamada_servicio = $_POST["id_solicitud_llamada_servicio"] ?? "";
$docentry_solicitud_llamada_servicio = $_POST["docentry_solicitud_llamada_servicio"] ?? "";

$id_tipo_anotacion = $_POST["id_tipo_anotacion"] ?? "";
$comentarios_anotacion = $_POST["comentarios_anotacion"] ?? "";

$linea_anotacion = "NULL";

// Por el momento no estoy trabajando la hora, ($_POST['fecha_anotacion'], $_POST['hora_anotacion'])
$fecha_anotacion = isset($_POST['fecha_anotacion']) ? FormatoFechaToSAP($_POST['fecha_anotacion']) : "";
$hora_anotacion = "'$fecha_anotacion'";

$id_usuario_creacion_anotacion = "'$coduser'";
$fecha_creacion_anotacion = "'$datetime_anotacion'";
$hora_creacion_anotacion = "'$datetime_anotacion'";
$id_usuario_actualizacion_anotacion = "'$coduser'";
$fecha_actualizacion_anotacion = "'$datetime_anotacion'";
$hora_actualizacion_anotacion = "'$datetime_anotacion'";

if ($type_anotacion == 1) {
	$msg_error = "No se pudo crear la Anotación.";

	$parametros = array(
		$type_anotacion,
		$id_solicitud_llamada_servicio,
		$docentry_solicitud_llamada_servicio,
		$linea_anotacion,
		"'$id_tipo_anotacion'",
		$hora_anotacion,
		$hora_anotacion,
		"'$comentarios_anotacion'",
		$id_usuario_actualizacion_anotacion,
		$fecha_actualizacion_anotacion,
		$hora_actualizacion_anotacion,
		$id_usuario_creacion_anotacion,
		$fecha_creacion_anotacion,
		$hora_creacion_anotacion,
	);

} elseif ($type_anotacion == 3) {
	$msg_error = "No se pudo eliminar la Anotación.";

	$parametros = array(
		$type_anotacion,
		$id_solicitud_llamada_servicio,
		$docentry_solicitud_llamada_servicio,
		$linea_anotacion,
	);
}

if ($type_anotacion != 0) {
	$SQL_Operacion = EjecutarSP("sp_tbl_SolicitudLlamadasServicios_Anotaciones", $parametros);

	if (!$SQL_Operacion) {
		echo $msg_error_anotacion;
	} else {
		$row = sqlsrv_fetch_array($SQL_Operacion);

		if (isset($row['Error']) && ($row['Error'] != "")) {
			echo "$msg_error_anotacion";
			echo "(" . $row['Error'] . ")";
		} else {
			echo "OK";
		}
	}

	// Mostrar mensajes AJAX.
	exit();
}

// SMM, 14/09/2023
$SQL_Evento = Seleccionar("tbl_SolicitudLlamadasServicios_Anotaciones_Tipo", "*");
?>

<style>
	.swal2-container {
		z-index: 9000;
	}

	.easy-autocomplete {
		width: 100% !important
	}
</style>

<form id="frmAnotaciones" method="post" enctype="multipart/form-data">
	<div class="modal-header">
		<h4 class="modal-title">Adicionar Anotaciones</h4>
	</div>
	<!-- /.modal-title -->

	<div class="modal-body">
		<div class="form-group">
			<div class="ibox-content">
				<?php include "includes/spinner.php"; ?>

				<div class="row">
					<div class="col-lg-6">
						<label class="control-label">Fecha <span class="text-danger">*</span></label>

						<div class="input-group date">
							<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input type="text"
								class="form-control" id="fecha_anotacion" name="fecha_anotacion"
								value="<?php echo date('Y-m-d'); ?>" required>
						</div>
					</div>

					<div class="col-md-6">
						<label class="control-label">Evento <span class="text-danger">*</span></label>

						<select name="id_tipo_anotacion" class="form-control" id="id_tipo_anotacion" required>
							<option value="" disabled selected>Seleccione...</option>

							<?php while ($row_Evento = sqlsrv_fetch_array($SQL_Evento)) { ?>
								<option value="<?php echo $row_Evento["id_tipo_anotacion"]; ?>">
									<?php echo $row_Evento["tipo_anotacion"]; ?>
								</option>
							<?php } ?>
						</select>
					</div>
				</div>
				<!-- /.row -->

				<br><br>
				<div class="row">
					<div class="col-md-12">
						<label class="control-label">Comentarios <span class="text-danger">*</span></label>

						<textarea name="comentarios_anotacion" rows="5" maxlength="3000" class="form-control"
							id="Comentarios" type="text" required></textarea>
					</div>
				</div>
				<!-- /.row -->
			</div>
			<!-- /.ibox-content -->
		</div>
		<!-- /.form-group -->
	</div>
	<!-- /.modal-body -->

	<div class="modal-footer">
		<button type="submit" class="btn btn-success m-t-md" id="btnAdicionar"><i class="fa fa-check"></i>
			Aceptar</button>

		<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal" id="btnCerrar"><i
				class="fa fa-times"></i>
			Cerrar</button>
	</div>
	<!-- /modal-footer -->
</form>

<script>
	$(document).ready(function () {
		$('.date').datepicker({
			todayBtn: "linked",
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true,
			todayHighlight: true,
			format: 'yyyy-mm-dd'
		});

		$("#frmAnotaciones").validate({
			submitHandler: function (form) {
				let formData = new FormData(form);
				let json = Object.fromEntries(formData);

				Swal.fire({
					title: "¿Está seguro que desea continuar?",
					icon: "question",
					showCancelButton: true,
					confirmButtonText: "Si, confirmo",
					cancelButtonText: "No"
				}).then((result) => {
					if (result.isConfirmed) {
						console.log(json);

						$.ajax({
							type: "POST",
							url: "md_adicionar_anotaciones.php",
							data: {
								type: 1,
								id_solicitud_llamada_servicio: $("#CallID").val(), // $IdSolicitud
								docentry_solicitud_llamada_servicio: $("#CallID").val(), // $IdSolicitud
								id_tipo_anotacion: json.id_tipo_anotacion,
								fecha_anotacion: json.fecha_anotacion,
								hora_anotacion: json.fecha_anotacion,
								comentarios_anotacion: json.comentarios_anotacion,
							},
							success: function (response) {
								console.log(response);

								let validarAjax = true;
								if (response !== "OK") {
									validarAjax = false;
								}

								Swal.fire({
									icon: (validarAjax) ? "success" : "warning",
									title: (validarAjax) ? "¡Listo!" : "¡Error!",
									text: (validarAjax) ? "La anotación se agrego correctamente." : "No se pudo agregar la anotación."
								}).then((result) => {
									if (result.isConfirmed) {

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
									}
								});
								// Swal.fire		
							},
							error: function (error) {
								alert("Ocurrio un error inesperado");
								console.error("240->", error.responseText);

								// Ocultar modal
								$('#myModal2').modal("hide");
							}
						});
					}
				});
			}
			// submitHandler
		});

		$("#dataTable_Campana").DataTable({
			pageLength: 25,
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
			buttons: [],
			order: [[0, "asc"]]
		});
	});
</script>