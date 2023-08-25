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

				<div class="panel panel-info">
					<div class="panel-heading active" role="tab" id="headingOne">
						<h4 class="panel-title">
							<a role="button" data-toggle="collapse" href="#collapseOne" aria-controls="collapseOne">
								<i class="fa fa-info-circle"></i> Información importante
							</a>
						</h4>
					</div>
					<!-- /.panel_heading -->
					
					<div id="collapseOne" class="panel-collapse collapse in" role="tabpanel"
						aria-labelledby="headingOne">
						<div class="panel-body">
							<p>Para adicionar más de un (1) VIN es necesario separar con (;)</p>
							<p><b>Ejemplo:</b> <span style="color: red;">9BWBH6BF0M4091426;WV1ZZZ2HZHA007804</span></p>
							<p><b>32 caracteres máximo por VIN, recuerde usar el botón Validar</b></p>
						</div>
						<!-- /.panel-body-->
					</div>
					<!-- /.panel-collapse -->
				</div>
				<!-- /.panel-info -->

				<div class="row">
					<div class="col-md-12">
						<label class="control-label">Lista VIN</label>
						<textarea name="ListaVIN" rows="5" maxlength="3000" class="form-control" id="ListaVIN"
							type="text"></textarea>
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
		<button type="submit" class="btn btn-success m-t-md" id="btnAdicionar" disabled><i class="fa fa-check"></i>
			Aceptar</button>

		<button type="button" class="btn btn-info m-t-md pull-left" onclick="Validar();"><i class="fa fa-thumbs-up"></i>
			Validar</button>

		<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i>
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

				$.ajax({
					type: "POST",
					url: "md_campanas_vehiculo.php",
					data: {
						type: 1,
						ID: $("#id_campana").val(),
						VIN: arregloVINs[0],
					},
					success: function (response) {
						console.log(response);

						Swal.fire({
							icon: (response == "OK") ? "success" : "warning'",
							title: (response == "OK") ? "Operación exitosa" : "Ocurrió un error",
							text: (response == "OK") ? "La consulta se ha ejecutado correctamente." : response
						}).then((result) => {
							if (result.isConfirmed) {
								location.reload();
							}
						});
					},
					error: function (error) {
						console.error("280->", error.responseText);
					}
				});
				// $.ajax
			}
			// submitHandler
		});

		$('.chosen-select').chosen({ width: "100%" });
		$(".select2").select2();

		$("#ListaVIN").on("input", function () {
			$("#btnAdicionar").prop("disabled", true);
		});
	});
</script>

<script>
	function Validar() {
		Swal.fire({
			title: 'Validando VINs',
			text: 'Se esta validando la estructura de los códigos VIN.',
			icon: 'info'
		}).then((result) => {
			if (result.isConfirmed) {
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
			// if (result.isConfirmed)
		});
	}
</script>