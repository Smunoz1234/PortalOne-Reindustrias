<?php require_once "includes/conexion.php";
$DocId = $_POST["DocId"] ?? "";
$SQL_Doc = Seleccionar('uvw_Sap_tbl_TarjetasEquipos', '*', "IdTarjetaEquipo='$DocId'");
$row_Doc = sqlsrv_fetch_array($SQL_Doc);

// SMM, 29/02/2024
$SQL_TipoEquipo = Seleccionar("tbl_TarjetaEquipo_TiposEquipos", "*", "estado_tipo_equipo = 'Y'");
$SQL_Ubicacion = Seleccionar("uvw_tbl_TarjetaEquipo_Ubicaciones", "*");
$SQL_Proyecto = Seleccionar("uvw_Sap_tbl_Proyectos", "*");

// Jerarquias, SMM 29/02/2024
$SQL_Jerarquias = Seleccionar("tbl_TarjetaEquipo_DimensionJerarquias", "*", "estado_dimension_jerarquia = 'Y'");

$array_Jerarquias = [];
while ($row_Jerarquia = sqlsrv_fetch_array($SQL_Jerarquias)) {
	array_push($array_Jerarquias, $row_Jerarquia);
}
// Hasta aquí, SMM 29/02/2024

// Dimensiones. SMM, 24/05/2023
$DimSeries = intval(ObtenerVariable("DimensionSeries"));
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimActive='Y'");

$array_Dimensiones = [];
while ($row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones)) {
	array_push($array_Dimensiones, $row_Dimension);
}
// Hasta aquí, SMM 24/05/2023

// Datos de dimensiones del usuario actual, 31/05/2023
$SQL_DatosEmpleados = Seleccionar("uvw_tbl_Usuarios", "*", "ID_Usuario='" . $_SESSION['CodUser'] . "'");
$row_DatosEmpleados = sqlsrv_fetch_array($SQL_DatosEmpleados);

// SMM, 25/02/2023
$msg_error = "";
$parametros = array();

$coduser = $_SESSION['CodUser'];
$id_usuario = "'$coduser'";

$type = $_POST['type'] ?? 0;
$id_padre = $_POST['id_padre'] ?? "";
$id_hijo = $_POST['id_hijo'] ?? "";

if ($type == 1) {
    $msg_error = "No se pudo crear el registro.";

    $parametros = array(
        $type,
        $id_padre,
        $id_hijo,
        $id_usuario,
    );

} elseif ($type == 3) {
    $msg_error = "No se pudo eliminar el registro.";

    $parametros = array(
        $type,
        $id_padre,
        $id_hijo,
        $id_usuario,
    );
}

if ($type != 0) {
    $SQL_Operacion = EjecutarSP("sp_tbl_TarjetaEquipo_Componentes", $parametros);

    if (!$SQL_Operacion) {
        echo $msg_error;
    } else {
        $row = sqlsrv_fetch_array($SQL_Operacion);

        if (isset($row['Error']) && ($row['Error'] != "")) {
            echo "$msg_error ";
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
	.select2-dropdown {
		z-index: 9009;
	}

	.ibox-title {
		border-radius: 5px;
		margin-bottom: 10px;
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
</style>

<div class="modal-dialog modal-lg" style="width: 80% !important;">
	<div class="modal-content">
		<div class="modal-body">
			<!-- Inicio, filtros -->
			<form id="formBuscar" class="form-horizontal">
				<div class="row">
					<!-- data-toggle="collapse" data-target="#filtros" -->
					<div class="ibox-title bg-success">
						<h5 class="collapse-link">
							<i class="fa fa-filter"></i> Datos para filtrar
							(<?php echo $row_Doc["ItemCode"] ?? ""; ?> -
							<?php echo $row_Doc["ItemName"] ?? ""; ?>)
						</h5>
						<a class="collapse-link pull-right">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>

					<div class="collapse in" id="filtros">
						<div class="col-lg-4">
							<div class="form-group">
								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">ID Articulo</label>
									
									<input name="item_code" type="text" class="form-control" id="item_code"
										maxlength="100" placeholder="ID del articulo">
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Serial</label>
									
									<input name="serial_equipo" type="text" class="form-control" id="serial_equipo"
										maxlength="100" placeholder="Serial fabricante o interno">
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Buscar dato</label>
									
									<input name="buscar_dato" type="text" class="form-control" id="buscar_dato"
										placeholder="Digite un dato completo, o una parte del mismo...">
								</div> <!-- col-xs-12 -->
							</div> <!-- form-group -->
						</div> <!-- col-lg-4 -->

						<div class="col-lg-4">
							<div class="form-group">
								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Tipo equipo</label>

									<select name="id_tipo_equipo" id="id_tipo_equipo" class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_TipoEquipo = sqlsrv_fetch_array($SQL_TipoEquipo)) { ?>
											<option value="<?php echo $row_TipoEquipo['id_tipo_equipo']; ?>">
												<?php echo $row_TipoEquipo['tipo_equipo']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<?php foreach ($array_Jerarquias as &$dimJ) { ?>
									<div class="col-xs-12" style="margin-bottom: 10px;">
										<label class="control-label">
											<?php echo $dimJ['dimension_jerarquia']; ?> <!-- span class="text-danger">*</span -->
										</label>

										<?php $DimJCode = intval($dimJ['id_dimension_jerarquia'] ?? 0); ?>
										<select name="id_jerarquia_<?php echo $DimJCode; ?>" <?php // required ?>
											id="id_jerarquia_<?php echo $DimJCode; ?>" class="form-control select2">
											<option value="">Seleccione...</option>

											<?php $SQL_DimJ = Seleccionar("tbl_TarjetaEquipo_Jerarquias", "*", "id_dimension_jerarquia = $DimJCode"); ?>
											<?php while ($row_DimJ = sqlsrv_fetch_array($SQL_DimJ)) { ?>
												<option value="<?php echo $row_DimJ['id_jerarquia']; ?>">
													<?php echo $row_DimJ['jerarquia']; ?>
												</option>
											<?php } ?>
										</select>
									</div> <!-- col-xs-12 -->
								<?php } ?>

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Ubicación</label>

									<select name="id_ubicacion_equipo" id="id_ubicacion_equipo" class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_Ubicacion = sqlsrv_fetch_array($SQL_Ubicacion)) { ?>
											<option value="<?php echo $row_Ubicacion['id_ubicacion_equipo']; ?>">
												<?php echo $row_Ubicacion['ubicacion_equipo']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Proyecto</label>

									<select name="id_proyecto" id="id_proyecto" class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
											<option value="<?php echo $row_Proyecto['IdProyecto']; ?>">
												<?php echo $row_Proyecto['DeProyecto']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->
							</div> <!-- form-group -->
						</div> <!-- col-lg-4 -->

						<div class="col-lg-4">
							<div class="form-group">
								<?php foreach ($array_Dimensiones as &$dim) { ?>
									<div class="col-xs-12" style="margin-bottom: 10px;">
										<label class="control-label">
											<?php echo $dim['DescPortalOne']; ?> <!-- span class="text-danger">*</span -->
										</label>

										<?php $DimCode = intval($dim['DimCode'] ?? 0); ?>
										<select name="id_dimension<?php echo $DimCode; ?>"
											id="id_dimension<?php echo $DimCode; ?>"
											class="form-control select2" <?php // required ?>>
											<option value="">Seleccione...</option>

											<?php $SQL_Dim = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', 'DimCode=' . $dim['DimCode']); ?>
											<?php while ($row_Dim = sqlsrv_fetch_array($SQL_Dim)) { ?>

												<option <?php /* if ($row_DatosEmpleados["CentroCosto$DimCode"] == $row_Dim['OcrCode']) {
													echo "selected";
												} */ ?> value="<?php echo $row_Dim['OcrCode']; ?>">
													<?php echo $row_Dim['OcrName']; ?>
												</option>
											<?php } ?>
										</select>
									</div> <!-- col-xs-12 -->
								<?php } ?>
							</div> <!-- form-group -->
						</div> <!-- col-lg-4 -->
					</div> <!-- ibox-content -->
				</div> <!-- row -->

				<div class="row">
					<div class="col-lg-12" style="margin-top: 20px;">
						<button type="submit" class="btn btn-outline btn-success pull-right">
							<i class="fa fa-search"></i> Buscar
						</button>
					</div>
				</div> <!-- row -->
			</form>
			<br><br><br>
			<!-- Fin, filtros -->

			<!-- Inicio, tabla -->
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

		</div> <!-- modal-body -->
		<div class="modal-footer">
			<button type="button" class="btn btn-success m-t-md" id="btnAceptar"><i class="fa fa-check"></i>
				Aceptar</button>
			<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i>
				Cerrar</button>
		</div>
	</div> <!-- modal-content -->
</div> <!-- modal-dialog -->

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

	$(document).ready(function () {
		$(".select2").select2();
		$('#footableOne').footable();

		$('#formBuscar').on('submit', function (event) {
			event.preventDefault();
		});

		// SMM, 29/05/2023
		$('#filtros').on('show.bs.collapse', function () {
			$('.collapse-link i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
		});

		$('#filtros').on('hide.bs.collapse', function () {
			$('.collapse-link i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
		});

		// SMM, 31/05/2023
		$(".collapse-link").on("click", function () {
			$("#filtros").collapse("toggle");
		});

		$("#formBuscar").validate({
			submitHandler: function (form) {
				$('.ibox-content').toggleClass('sk-loading', true);

				// Comprimir el acordeón
				$("#filtros").collapse("hide");

				let formData = new FormData(form);

				// Ejemplo de como agregar nuevos campos.
				// formData.append("Dim1", $("#Dim1").val() || "");
				
				// SMM, 05/03/2024
				formData.append("id_doc", "<?php echo $DocId; ?>");

				let json = Object.fromEntries(formData);
				console.log("Line 400", json);

				// Inicio, AJAX
				$.ajax({
					url: 'md_consultar_componentes_ws.php',
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
		});

		$('.chosen-select').chosen({ width: "100%" });

		$('.i-checks').iCheck({
			checkboxClass: 'icheckbox_square-green',
			radioClass: 'iradio_square-green',
		});

		$("#btnAceptar").on("click", function () {
			var totalTE = $("#footableTwo tbody tr").length; // Obtener el total.
			var contadorTE = 0; // Inicializar el contador.

			$("#footableTwo tbody tr").each(function () {
				let IdTarjetaEquipo = $(this).find('.IdTarjetaEquipo').text();

				let componente = {
					type: 1,
					id_padre: "<?php echo $DocId; ?>",
					id_hijo: IdTarjetaEquipo.trim()
				};

				// JSON que se esta enviando a registro.
				console.log(componente);

				// Envio AJAX del Articulo.
				$.ajax({
					url: "md_consultar_componentes.php",
					type: "POST",
					data: componente,
					success: function (response) {
						// Manejar la respuesta del servidor
						// console.log("Respuesta:", response);
						contadorTE++;

						// Verificar si todas las solicitudes AJAX han finalizado
						if (contadorTE === totalTE) {
							// Obtener la URL actual
							let currentUrl = new URL(window.location.href);
							console.log(currentUrl);
							
							// Modificar o crear parámetro
							currentUrl.searchParams.set("tab", "components");
							
							// Redirigir a la nueva URL
							window.location.href = currentUrl.href;
						}
					},
					error: function (error) {
						// Manejar el error de la petición AJAX
						console.log("Error inserción:", error);

						// alert("Ocurrio un error al insertar los articulos, se recomienda repetir el procedimiento o consultar al administrador");
					}
				});
				// Fin AJAX
			}); // Fin Loop Articulos
		}); // Fin Evento CLICK

		// SMM, 30/08/2023
		$(".select2").on("change", function() {
			EliminarFilas();
		});
	});
</script>