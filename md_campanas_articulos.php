<?php
require_once "includes/conexion.php";
$id_campana = $_POST['id_campana'] ?? "";

if ($id_campana == "") {
	$SQL_VIN = Seleccionar('tbl_CampanaVehiculosDetalle', '*', "", 'VIN');
} else {
	$SQL_VIN = Seleccionar('tbl_CampanaVehiculosDetalle', '*', "[id_campana] = '$id_campana'", '[VIN]');
	// echo "SELECT * FROM tbl_CampanaVehiculosDetalle WHERE [id_campana] = '$id_campana' ORDER BY [VIN]";
	// exit();
}

// SMM, 25/02/2023
$msg_error_articulo = "";
$parametros_articulo = array();

$coduser = $_SESSION['CodUser'];
$datetime_articulo = FormatoFecha(date('Y-m-d'), date('H:i:s'));

$type_articulo = $_POST['type'] ?? 0;

$ID = $_POST['ID'] ?? "";
$id_campana_articulo = $_POST['id_campana_articulo'] ?? "NULL";
$VIN_articulo = $_POST['VIN'] ?? "";

$id_articulo = $_POST['IdArticulo'] ?? "";
$descripcion_articulo = $_POST['DescripcionArticulo'] ?? "";

$id_usuario_creacion_articulo = "'$coduser'";
$fecha_creacion_articulo = "'$datetime_articulo'";
$hora_creacion_articulo = "'$datetime_articulo'";
$id_usuario_actualizacion_articulo = "'$coduser'";
$fecha_actualizacion_articulo = "'$datetime_articulo'";
$hora_actualizacion_articulo = "'$datetime_articulo'";

if ($type_articulo == 1) {
	$msg_error = "No se pudo crear el Articulo.";

	$parametros = array(
		$type_articulo,
		"'$ID'",
		$id_campana_articulo,
		"'$VIN_articulo'",
		"'$id_articulo'",
		"'$descripcion_articulo'",
		$id_usuario_actualizacion_articulo,
		$fecha_actualizacion_articulo,
		$hora_actualizacion_articulo,
		$id_usuario_creacion_articulo,
		$fecha_creacion_articulo,
		$hora_creacion_articulo,
	);
} elseif ($type_articulo == 3) {
	$msg_error = "No se pudo eliminar el Articulo.";

	$parametros = array(
		$type_articulo,
		"'$ID'",
		$id_campana_articulo,
	);
}

if ($type_articulo != 0) {
	$SQL_Operacion = EjecutarSP('sp_tbl_CampanaVehiculosDetalle_Articulos', $parametros);

	if (!$SQL_Operacion) {
		echo $msg_error_articulo;
	} else {
		$row = sqlsrv_fetch_array($SQL_Operacion);

		if (isset($row['Error']) && ($row['Error'] != "")) {
			echo "$msg_error_articulo";
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

						<select id="VIN" name="VIN" class="form-control select2" onchange="EliminarFilas()" required>
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

		<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal" id="btnCerrar"><i
				class="fa fa-times"></i>
			Cerrar</button>
	</div>
	<!-- /modal-footer -->
</form>

<script>
	$(document).ready(function () {
		$(".select2").select2();
		$('#footableOne').footable();
		$('.chosen-select').chosen({ width: "100%" });

		$('#frmBuscarArticulo').on('submit', function (event) {
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

		// SMM, 29/08/2023
		$("#btnAceptar").on("click", function () {
			var totalArticulos = $("#footableTwo tbody tr").length; // Obtener el total de artículos

			// Validación del ciclo
			var validarAjax = true;
			var contadorAjax = 0;

			$("#footableTwo tbody tr").each(function () {
				let id_articulo = $(this).attr("id");
				let articulo = $(this).find('.descripcion').text();
				let vin = $(this).find('.vin').text();

				// Articulo que se esta enviando a registro.
				console.log(articulo);

				// Envio AJAX del Articulo.
				$.ajax({
					type: "POST",
					url: "md_campanas_articulos.php",
					data: {
						type: 1,
						ID: $("#id_campana").val(),
						VIN: vin.trim(),
						IdArticulo: id_articulo,
						DescripcionArticulo: articulo.trim()
					},
					success: function (response) {
						console.log(response);

						contadorAjax++;
						if (response !== "OK") {
							validarAjax = false;
						}

						// Verificar si todas las solicitudes AJAX han finalizado
						if (contadorAjax === totalArticulos) {
							Swal.fire({
								icon: (validarAjax) ? "success" : "warning",
								title: (validarAjax) ? "¡Listo!" : "¡Error!",
								text: (validarAjax) ? "Todos los articulos se insertaron correctamente." : "No se pudieron insertar algunos articulos, por favor verifique."
							}).then((result) => {
								if (result.isConfirmed) {
									// if(validarAjax) {

									// Obtén la URL actual
									let currentUrl = new URL(window.location.href);

									// Obtén los parámetros del query string
									let searchParams = currentUrl.searchParams;

									// Actualiza el valor del parámetro 'active' o agrega si no existe
									searchParams.set('active', 2);

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
						console.error("320->", error.responseText);

						validarAjax = false;
					}
				});
				// Fin AJAX
			});
			// Fin Loop Articulos
		});
		// Fin Evento CLICK

		$("#btnCerrar").on("click", function () {
			// location.reload();

			// Obtén la URL actual
			let currentUrl = new URL(window.location.href);

			// Obtén los parámetros del query string
			let searchParams = currentUrl.searchParams;

			// Actualiza el valor del parámetro 'active' o agrega si no existe
			searchParams.set('active', 2);

			// Crea una nueva URL con los parámetros actualizados
			let newUrl = currentUrl.origin + currentUrl.pathname + '?' + searchParams.toString();

			// Recarga la página con la nueva URL
			window.location.href = newUrl;
		});
	});
</script>

<script>
	function VerificarFilas() {
		if ($(".footable-detail-row").length) {
			Swal.fire({
				"title": "¡Advertencia!",
				"text": "Debe contraer todas las filas para poder realizar alguna acción en las tablas.",
				"icon": "warning"
			});

			return false;
		}

		return true;
	}

	function EliminarFilas() {
		let contenido = `<i class="fa fa-search" style="font-size: 20px; color: gray;"></i>
		<span style="font-size: 15px; color: gray;">Debe buscar un artículo.</span>`;

		$("#tableContainerOne").html(contenido);
	}

	function AgregarArticulo(ID) {
		if (VerificarFilas()) {

			if ($("#footableTwo").length) {
				console.log("footableTwo existe.");
			} else {
				console.log("footableTwo no existe.");

				// Clonar la tabla con el ID "footableOne"
				let tableTwo = $('#footableOne').clone();

				// Vaciar el tbody de la tabla clonada
				tableTwo.find('tbody').empty();

				// Asignar el ID "footableTwo" a la tabla clonada
				tableTwo.attr('id', 'footableTwo');

				// Agregar la tabla clonada al DOM
				$('#tableContainerTwo').replaceWith(tableTwo);
			}

			// Obtener la fila correspondiente al artículo seleccionado
			let fila = $(`#${ID}`).clone();

			// Eliminar el botón "fooicon" de la fila clonada
			fila.find('.fooicon').remove();

			// Reemplazar el botón "Agregar" por "Eliminar"
			fila.find(".btn-success")
				.removeClass("btn-success")
				.addClass("btn-danger")
				.html('<i class="fa fa-trash"></i> Eliminar')
				.attr("onclick", `EliminarArticulo(this);`);


			// Agregar la fila al carrito de compras
			$("#footableTwo tbody").append(fila);

			// Re-renderizar.
			$('#footableTwo').footable();

		} // VerificarFilas()
	}

	function EliminarArticulo(btn) {
		if (VerificarFilas()) {

			$(btn).closest("tr").remove(); // Eliminar la fila padre del botón

		} // VerificarFilas()
	}
</script>