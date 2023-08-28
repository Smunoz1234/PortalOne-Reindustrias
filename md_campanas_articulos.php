<?php
require_once "includes/conexion.php";

$SQL_VIN = Seleccionar('tbl_CampanaVehiculosDetalle', '*', '', 'VIN');

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

<form id="frmBuscarArticulo" method="post" enctype="multipart/form-data">
	<div class="modal-body">
		<div class="form-group">
			<div class="ibox-content">
				<?php include "includes/spinner.php"; ?>

				<div class="row">
					<div class="col-xs-12" style="margin-bottom: 10px;">
						<label class="control-label">
							VIN <span class="text-danger">*</span>
						</label>

						<select id="VIN" name="VIN" class="form-control select2" required>
							<option value="">Seleccione...</option>

							<?php while ($row_VIN = sqlsrv_fetch_array($SQL_VIN)) { ?>
								<option value="<?php echo $row_VIN['VIN']; ?>">
									<?php echo $row_VIN['VIN']; ?>
								</option>
							<?php } ?>
						</select>
					</div>
					<!-- /.col-xs-12 -->
				</div>
				<!-- /.row -->

				<div class="row">
					<div class="col-lg-10">
						<label class="control-label">
							Buscar artículo <span class="text-danger">*</span>
						</label>

						<input name="BuscarItem" id="BuscarItem" type="text" class="form-control"
							placeholder="Escriba para buscar..." required>
					</div>

					<div class="col-lg-2" style="margin-top: 20px;">
						<button type="submit" class="btn btn-outline btn-success pull-right"><i
								class="fa fa-search"></i> Buscar</button>
					</div>
				</div>
				<!-- /.row -->

				<!-- Inicio, tabla -->
				<br><br><br>
				<div class="row">
					<div class="col-lg-6">
						<div class="ibox-content">
							<div class="table-responsive" id="tableContainerOne">
								<i class="fa fa-search" style="font-size: 20px; color: gray;"></i>
								<span style="font-size: 15px; color: gray;">Debe buscar un artículo.</span>
							</div> <!-- table-responsive -->
						</div> <!-- ibox-content -->
					</div> <!-- col-lg-6 -->
					<div class="col-lg-6">
						<div class="ibox-content">
							<div class="table-responsive" id="tableContainerTwo">
								<i class="fa fa-exclamation-circle" style="font-size: 20px; color: gray;"></i>
								<span style="font-size: 15px; color: gray;">Todavía no se han agregado artículos al
									carrito.</span>
							</div> <!-- table-responsive -->
						</div> <!-- ibox-content -->
					</div> <!-- col-lg-6 -->
				</div>
				<!-- Fin, tabla -->
			</div>
			<!-- /.ibox-content -->
		</div>
		<!-- /.form-group -->
	</div>
	<!-- /.modal-body -->

	<div class="modal-footer">
		<button type="button" class="btn btn-success m-t-md" id="btnAceptar"><i class="fa fa-check"></i>
			Aceptar</button>

		<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i>
			Cerrar</button>
	</div>
	<!-- /modal-footer -->
</form>

<script>
	$(document).ready(function () {
		$(".select2").select2();
		$('#footableOne').footable();
		$('.chosen-select').chosen({ width: "100%" });

		$('#formBuscarArticulo').on('submit', function (event) {
			event.preventDefault();
		});

		$("#frmBuscarArticulo").validate({
			submitHandler: function (form) {
				$('.ibox-content').toggleClass('sk-loading', true);

				let formData = new FormData(form);

				// Ejemplo de como agregar nuevos campos.
				// formData.append("Dim1", $("#Dim1").val() || "");

				let json = Object.fromEntries(formData);
				console.log("Line 240", json);

				// Inicio, AJAX
				$.ajax({
					url: 'md_campanas_articulos_ws.php',
					type: 'POST',
					data: formData,
					processData: false,  // tell jQuery not to process the data
					contentType: false,   // tell jQuery not to set contentType
					success: function (response) {
						// console.log("Line 260", response);

						$("#tableContainerOne").html(response);
						$('#footableOne').footable();

						$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
					},
					error: function (error) {
						console.error(error.responseText);

						$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
					}
				});
				// Fin, AJAX
			}
			// submitHandler
		});
	});
</script>