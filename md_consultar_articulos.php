<?php require_once "includes/conexion.php";
// Dimensiones. SMM, 24/05/2023
$DimSeries = intval(ObtenerVariable("DimensionSeries"));
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimActive='Y'");

$array_Dimensiones = [];
while ($row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones)) {
	array_push($array_Dimensiones, $row_Dimension);
}

$encode_Dimensiones = json_encode($array_Dimensiones);
$cadena_Dimensiones = "JSON.parse('$encode_Dimensiones'.replace(/\\n|\\r/g, ''))";
// Hasta aquí, SMM 24/05/2023

// Proyectos. SMM, 24/05/2023
$IdSeries = $_POST['IdSeries'];
$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', '', 'DeProyecto');

//Almacenes origen. SMM, 24/05/2023
$SQL_Almacen = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'WhsCode, WhsName', "IdSeries='$IdSeries'", "WhsCode, WhsName", 'WhsName');

//Almacenes destino. SMM, 24/05/2023
$SQL_AlmacenDestino = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'ToWhsCode, ToWhsName', "IdSeries='$IdSeries'", "ToWhsCode, ToWhsName", 'ToWhsName');
?>

<style>
	.select2-container {
		z-index: 9000;
	}
</style>

<div class="modal-dialog modal-lg" style="width: 70% !important;">
	<div class="modal-content">
		<div class="modal-body">
			<!-- Inicio, filtros -->
			<form id="formBuscar" class="form-horizontal">
				<div class="row">
					<div class="ibox-content">
						<div class="form-group">
							<label class="col-xs-12">
								<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para filtrar
								</h3>
							</label>
						</div> <!-- form-group -->

						<div class="col-lg-6">
							<div class="form-group">
								<?php foreach ($array_Dimensiones as &$dim) { ?>
									<div class="col-xs-12" style="margin-bottom: 10px;">
										<label class="control-label">
											<?php echo $dim['DescPortalOne']; ?> <span class="text-danger">*</span>
										</label>

										<select name="<?php echo $dim['IdPortalOne'] ?>"
											id="<?php echo $dim['IdPortalOne'] ?>" class="form-control select2">
											<option value="">Seleccione...</option>

											<?php $SQL_Dim = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', 'DimCode=' . $dim['DimCode']); ?>
											<?php while ($row_Dim = sqlsrv_fetch_array($SQL_Dim)) { ?>
												<?php $DimCode = intval($dim['DimCode']); ?>
												<?php $OcrId = ($DimCode == 1) ? "" : $DimCode; ?>

												<option value="<?php echo $row_Dim['OcrCode']; ?>"><?php echo $row_Dim['OcrCode'] . " - " . $row_Dim['OcrName']; ?>
												</option>
											<?php } ?>
										</select>
									</div> <!-- col-xs-12 -->
								<?php } ?>
							</div> <!-- form-group -->
						</div> <!-- col-lg-6 -->

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
									<label class="control-label">Almacén destino <span
											class="text-danger">*</span></label>

									<select name="AlmacenDestino" id="AlmacenDestino" class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_AlmacenDestino = sqlsrv_fetch_array($SQL_AlmacenDestino)) { ?>
											<option value="<?php echo $row_AlmacenDestino['ToWhsCode']; ?>"><?php echo $row_AlmacenDestino['ToWhsCode'] . " - " . $row_AlmacenDestino['ToWhsName']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Proyecto <span class="text-danger">*</span></label>

									<select id="PrjCode" name="PrjCode" class="form-control select2">
										<option value="">(NINGUNO)</option>

										<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
											<option value="<?php echo $row_Proyecto['IdProyecto']; ?>">
												<?php echo $row_Proyecto['IdProyecto'] . " - " . $row_Proyecto['DeProyecto']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->
							</div> <!-- form-group -->
						</div> <!-- col-lg-6 -->

						<div class="row">
							<div class="col-lg-6">
								<label class="control-label">Buscar artículo <span class="text-danger">*</span></label>

								<input name="BuscarItem" id="BuscarItem" type="text" class="form-control"
									placeholder="Escriba para buscar..." required>
							</div> <!-- col-lg-6 -->

							<div class="col-lg-6">
								<br>
								<button type="submit" class="btn btn-outline btn-success pull-right"><i
										class="fa fa-search"></i> Buscar</button>
							</div> <!-- col-lg-6 -->
						</div>
					</div> <!-- ibox-content -->
				</div> <!-- row -->
			</form>
			<br><br><br>
			<!-- Fin, filtros -->

			<!-- Inicio, tabla -->
			<div class="row">
				<div class="col-lg-6">
					<div class="ibox-content">
						<div class="table-responsive" id="tableContainer">
							<i class="fa fa-search" style="font-size: 20px; color: gray;"></i>
							<span style="font-size: 15px; color: gray;">Debe buscar un artículo.</span>
						</div> <!-- table-responsive -->
					</div> <!-- ibox-content -->
				</div> <!-- col-lg-12 -->
			</div>
			<!-- Fin, tabla -->

		</div> <!-- modal-body -->
		<div class="modal-footer">
			<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i>
				Cerrar</button>
		</div>
	</div> <!-- modal-content -->
</div> <!-- modal-dialog -->

<script>
	function cambiarOT(orden_trabajo, descripcion_ot) {
		$("#OrdenServicioCliente").val(orden_trabajo);
		$("#Desc_OrdenServicioCliente").val(descripcion_ot);
		$('#mdOT').modal('hide');
	}



	$(document).ready(function () {
		$(".select2").select2();
		$('#footable').footable();

		$('#formBuscar').on('submit', function (event) {
			// Stiven Muñoz Murillo, 04/08/2022
			event.preventDefault();
		});

		$("#formBuscar").validate({
			submitHandler: function (form) {
				$('.ibox-content').toggleClass('sk-loading');

				alert("Hola");

				let formData = new FormData(form);

				let json = Object.fromEntries(formData);
				console.log("Line 250", json);

				// Inicio, AJAX
				$.ajax({
					url: 'md_consultar_Articulos_servicios_ws.php',
					type: 'POST',
					data: formData,
					processData: false,  // tell jQuery not to process the data
					contentType: false,   // tell jQuery not to set contentType
					success: function (response) {

						// console.log("Line 260", response);

						$("#tableContainer").html(response);
						$('#footable').footable();

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
		$('#FechaInicial').datepicker({
			todayBtn: "linked",
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true,
			todayHighlight: true,
			format: 'yyyy-mm-dd'
		});
		$('#FechaFinal').datepicker({
			todayBtn: "linked",
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true,
			todayHighlight: true,
			format: 'yyyy-mm-dd'
		});
		$('.chosen-select').chosen({ width: "100%" });
		var options = {
			adjustWidth: false,
			url: function (phrase) {
				return "ajx_buscar_datos_json.php?type=7&id=" + phrase;
			},
			getValue: "NombreBuscarCliente",
			requestDelay: 400,
			list: {
				match: {
					enabled: true
				},
				onClickEvent: function () {
					var value = $("#NombreCliente").getSelectedItemData().CodigoCliente;
					$("#Cliente").val(value).trigger("change");
				}
			}
		};
		$("#NombreCliente").easyAutocomplete(options);
	});
</script>