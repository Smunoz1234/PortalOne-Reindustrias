<?php require_once "includes/conexion.php";
// Dimensiones. SMM, 24/05/2023
$DimSeries = intval(ObtenerVariable("DimensionSeries"));
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimActive='Y'");

// Pruebas, SMM 29/05/2023
// $SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', 'DimCode IN (1,2,3,4,5)');

$array_Dimensiones = [];
while ($row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones)) {
	array_push($array_Dimensiones, $row_Dimension);
}

$encode_Dimensiones = json_encode($array_Dimensiones);
$cadena_Dimensiones = "JSON.parse('$encode_Dimensiones'.replace(/\\n|\\r/g, ''))";
// Hasta aquí, SMM 24/05/2023

$Procedure = $_POST['Procedure'];
$Edit = $_POST['Edit'];
$CardCode = $_POST['CardCode'];
$IdSeries = $_POST['IdSeries'];
$Proyecto = $_POST['IdProyecto'];
$IdEmpleado = $_POST['IdEmpleado'];
$ListaPrecio = $_POST['ListaPrecio'];

// Proyectos. SMM, 24/05/2023
$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', '', 'DeProyecto');

// Almacenes. SMM, 24/05/2023
$SQL_Almacen = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'WhsCode, WhsName', "IdSeries='$IdSeries'", "WhsCode, WhsName", 'WhsName');
$SQL_AlmacenDestino = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'ToWhsCode, ToWhsName', "IdSeries='$IdSeries'", "ToWhsCode, ToWhsName", 'ToWhsName');

// Sucursales. SMM, 26/05/2023
$SQL_Sucursales = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'IdSucursal "OcrCode", DeSucursal "OcrName"', "IdSeries='$IdSeries'", "IdSucursal, DeSucursal", 'DeSucursal');

// Lista de precios, 29/05/2023
$SQL_ListaPrecios = Seleccionar('uvw_Sap_tbl_ListaPrecios', '*');

// Empleado de ventas, 29/05/2023
$SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*', "Estado = 'Y'", 'DE_EmpVentas');
?>

<style>
	.select2-container {
		z-index: 9000;
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
</style>

<div class="modal-dialog modal-lg" style="width: 75% !important;">
	<div class="modal-content">
		<div class="modal-body">
			<!-- Inicio, filtros -->
			<form id="formBuscar" class="form-horizontal">
				<div class="row">
					<div class="ibox-title bg-success" data-toggle="collapse" data-target="#filtros">
						<h5 class="collapse-link"><i class="fa fa-filter"></i> Datos para filtrar</h5>
						<a class="collapse-link pull-right">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>

					<div class="collapse in" id="filtros">
						<div class="col-lg-6">
							<div class="form-group">
								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Almacén origen <span
											class="text-danger">*</span></label>

									<select name="Almacen" id="Almacen" class="form-control select2" required>
										<option value="">Seleccione...</option>

										<?php while ($row_Almacen = sqlsrv_fetch_array($SQL_Almacen)) { ?>
											<option value="<?php echo $row_Almacen['WhsCode']; ?>"><?php echo $row_Almacen['WhsCode'] . " - " . $row_Almacen['WhsName']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Almacén destino</label>

									<select name="AlmacenDestino" id="AlmacenDestino" class="form-control select2"
										disabled>
										<option value="">Seleccione...</option>

										<?php while ($row_AlmacenDestino = sqlsrv_fetch_array($SQL_AlmacenDestino)) { ?>
											<option value="<?php echo $row_AlmacenDestino['ToWhsCode']; ?>"><?php echo $row_AlmacenDestino['ToWhsCode'] . " - " . $row_AlmacenDestino['ToWhsName']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Proyecto</label>

									<select id="PrjCode" name="PrjCode" class="form-control select2">
										<option value="">(NINGUNO)
											<?php echo $Proyecto; ?>
										</option>

										<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
											<option <?php if ($Proyecto == $row_Proyecto['IdProyecto']) {
												echo "selected";
											} ?> value="<?php echo $row_Proyecto['IdProyecto']; ?>">
												<?php echo $row_Proyecto['IdProyecto'] . " - " . $row_Proyecto['DeProyecto']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Lista Precios <span
											class="text-danger">*</span></label>

									<select name="ListaPrecio" id="ListaPrecio" class="form-control select2" required>
										<option value="">Seleccione...</option>

										<?php while ($row_ListaPrecio = sqlsrv_fetch_array($SQL_ListaPrecios)) { ?>
											<option <?php if ($ListaPrecio == $row_ListaPrecio['IdListaPrecio']) {
												echo "selected";
											} ?> value="<?php echo $row_ListaPrecio['IdListaPrecio']; ?>">

												<?php echo $row_ListaPrecio['IdListaPrecio'] . " - " . $row_ListaPrecio['DeListaPrecio']; ?>

											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Empleado de ventas <span
											class="text-danger">*</span></label>

									<select name="EmpVentas" id="EmpVentas" class="form-control select2" required>
										<option value="">Seleccione...</option>

										<?php while ($row_EmpleadosVentas = sqlsrv_fetch_array($SQL_EmpleadosVentas)) { ?>
											<option <?php if ($IdEmpleado == $row_EmpleadosVentas['ID_EmpVentas']) {
												echo "selected";
											} ?> value="<?php echo $row_EmpleadosVentas['ID_EmpVentas']; ?>"><?php echo $row_EmpleadosVentas['ID_EmpVentas'] . " - " . $row_EmpleadosVentas['DE_EmpVentas']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->
							</div> <!-- form-group -->
						</div> <!-- col-lg-6 -->

						<div class="col-lg-6">
							<div class="form-group">
								<?php foreach ($array_Dimensiones as &$dim) { ?>
									<div class="col-xs-12" style="margin-bottom: 10px;">
										<label class="control-label">
											<?php echo $dim['DescPortalOne']; ?> <span class="text-danger">*</span>
										</label>

										<select name="<?php echo $dim['IdPortalOne'] ?>" required
											id="<?php echo $dim['IdPortalOne'] ?>" class="form-control select2">
											<option value="">Seleccione...</option>

											<?php $SQL_Dim = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', 'DimCode=' . $dim['DimCode']); ?>

											<?php if ($dim['DimCode'] == $DimSeries) { ?>
												<?php $SQL_Dim = $SQL_Sucursales; ?>
											<?php } ?>

											<?php while ($row_Dim = sqlsrv_fetch_array($SQL_Dim)) { ?>
												<?php $DimCode = intval($dim['DimCode']); ?>
												<?php $OcrId = ($DimCode == 1) ? "" : $DimCode; ?>

												<option value="<?php echo $row_Dim['OcrCode']; ?>">
													<?php echo $row_Dim['OcrCode'] . " - " . $row_Dim['OcrName']; ?>
												</option>
											<?php } ?>
										</select>
									</div> <!-- col-xs-12 -->
								<?php } ?>
							</div> <!-- form-group -->
						</div> <!-- col-lg-6 -->
					</div> <!-- ibox-content -->
				</div> <!-- row -->

				<div class="row">
					<div class="col-lg-6">
						<label class="control-label">Buscar artículo <span class="text-danger">*</span></label>

						<input name="BuscarItem" id="BuscarItem" type="text" class="form-control"
							placeholder="Escriba para buscar..." required>
					</div>

					<div class="col-lg-4" style="margin-top: 20px;">
						<label class="checkbox-inline i-checks"><input name="chkStock" type="checkbox" id="chkStock"
								value="1" checked="checked"> Mostrar solo los artículos con
							stock</label>
					</div>

					<div class="col-lg-2" style="margin-top: 20px;">
						<button type="submit" class="btn btn-outline btn-success pull-right"><i
								class="fa fa-search"></i> Buscar</button>
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

		$("#formBuscar").validate({
			submitHandler: function (form) {
				$('.ibox-content').toggleClass('sk-loading');

				let formData = new FormData(form);

				// Ejemplo de como agregar nuevos campos.
				// formData.append("Dim1", $("#Dim1").val() || "");

				let json = Object.fromEntries(formData);
				console.log("Line 250", json);

				// Inicio, AJAX
				$.ajax({
					url: 'md_consultar_articulos_ws.php',
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
			let p = <?php echo $Procedure; ?>;
			let dt = 1;
			let cc = "<?php echo $CardCode; ?>";

			var totalArticulos = $("#footableTwo tbody tr").length; // Obtener el total de artículos
			var contadorArticulos = 0; // Inicializar el contador de artículos

			$("#footableTwo tbody tr").each(function () {
				let idArticulo = $(this).attr("id");
				let whsCode = $(this).find('.WhsCode').text();

				let dim1 = $(this).find('.Dim1').length ? $(this).find('.Dim1').text() : "";
				let dim2 = $(this).find('.Dim2').length ? $(this).find('.Dim2').text() : "";
				let dim3 = $(this).find('.Dim3').length ? $(this).find('.Dim3').text() : "";
				let dim4 = $(this).find('.Dim4').length ? $(this).find('.Dim4').text() : "";
				let dim5 = $(this).find('.Dim5').length ? $(this).find('.Dim5').text() : "";

				let prjCode = $(this).find('.PrjCode').text();
				let priceList = $(this).find('.PriceList').text();
				let empVentas = $(this).find('.EmpVentas').text();

				let articulo = {
					P: p,
					doctype: dt,
					cardcode: cc,
					item: idArticulo,
					whscode: whsCode.trim(),
					dim1: dim1.trim(),
					dim2: dim2.trim(),
					dim3: dim3.trim(),
					dim4: dim4.trim(),
					dim5: dim5.trim(),
					prjcode: prjCode.trim(),
					pricelist: priceList.trim(),
					empventas: empVentas.trim()
				};

				// Articulo que se esta enviando a registro.
				console.log(articulo);

				// Envio AJAX del Articulo.
				$.ajax({
					url: "registro.php",
					type: "GET",
					data: articulo,
					success: function (response) {
						// Manejar la respuesta del servidor
						// console.log("Respuesta:", response);
						contadorArticulos++; // Incrementar el contador de artículos

						// Verificar si todas las solicitudes AJAX han finalizado
						if (contadorArticulos === totalArticulos) {
							// Obtén el elemento con el ID 'DataGrid'
							let dataGrid = document.getElementById('DataGrid');

							// Crea un objeto URL a partir del atributo 'src'
							let url = new URL(dataGrid.src);

							// Elimina todos los parámetros existentes
							console.log(url.search);
							// url.search = '';

							//?id=0&type=1&usr=101&cardcode=CL-1054994729

							// Agrega varios parámetros nuevos
							// url.searchParams.set('parametro1', 'valor1');
							// url.searchParams.set('parametro2', 'valor2');
							// url.searchParams.set('parametro3', 'valor3');

							// Asigna la nueva URL al atributo 'src' del elemento
							dataGrid.src = url.href;

							// Cerrar el modal al finalizar la lógica
							$("#mdArticulos").modal("hide");
						}
					},
					error: function (error) {
						// Manejar el error de la petición AJAX
						console.log("Error:", error);
						alert("Ocurrio un error al insertar los articulos, se recomienda repetir el procedimiento o consultar al administrador");
					}
				});
				// Fin AJAX
			}); // Fin Loop Articulos

		}); // Fin Evento CLICK
	});
</script>