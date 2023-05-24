<?php
// Dimensiones, SMM 24/05/2023
$DimSeries = intval(ObtenerVariable("DimensionSeries"));
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimActive='Y'");

$array_Dimensiones = [];
while ($row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones)) {
	array_push($array_Dimensiones, $row_Dimension);
}

$encode_Dimensiones = json_encode($array_Dimensiones);
$cadena_Dimensiones = "JSON.parse('$encode_Dimensiones'.replace(/\\n|\\r/g, ''))";
// Hasta aquí, SMM 24/05/2023

// Filtrar estados
$Filtro = "";
$Filtro .= " AND [IdEstadoLlamada] <> -1";

// Obtener series de llamada
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'191'",
	1,
);
$SQL_SeriesLlamada = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Filtrar series
$FilSerie = "";
$i = 0;
while ($row_Series = sqlsrv_fetch_array($SQL_SeriesLlamada)) {
	if ($i == 0) {
		$FilSerie .= "'" . $row_Series['IdSeries'] . "'";
	} else {
		$FilSerie .= ",'" . $row_Series['IdSeries'] . "'";
	}
	$i++;
}
$Filtro .= " AND [Series] IN (" . $FilSerie . ")";
$SQL_SeriesLlamada = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Filtrar cliente y sucursales
$ID_CodigoCliente = "";
if (($edit == 1) || ($sw_error == 1)) {
	$ID_CodigoCliente = $row['CardCode'];
} elseif ((isset($dt_LS) && ($dt_LS == 1)) || (isset($dt_OV) && ($dt_OV == 1)) || (isset($dt_ET) && ($dt_ET == 1))) {
	$ID_CodigoCliente = $row_Cliente['CodigoCliente'];
}

if ($ID_CodigoCliente != "") {
	$Filtro .= " AND ID_CodigoCliente = '$ID_CodigoCliente'";

	$Where = "CodigoCliente = '$ID_CodigoCliente'";
	$SQL_ClienteLlamada = Seleccionar("uvw_Sap_tbl_SociosNegocios", "NombreCliente", $Where);
	$row_ClienteLlamada = sqlsrv_fetch_array($SQL_ClienteLlamada);
	// var_dump($row_ClienteLlamada);

	// Obtener sucursales
	$SQL_Sucursal = Seleccionar("uvw_Sap_tbl_Clientes_Sucursales", "NombreSucursal", $Where);
}

// Filtrar fechas
$fecha = date('Y-m-d');
$nuevafecha = strtotime('-' . ObtenerVariable("DiasRangoFechasDocSAP") . ' day');
$nuevafecha = date('Y-m-d', $nuevafecha);

$FechaInicial = $nuevafecha;
$FechaFinal = $fecha;

// Realizar consulta con filtros
$Where = "Metodo = 0 AND ([FechaCreacionLLamada] BETWEEN '$FechaInicial' AND '$FechaFinal') $Filtro";
$SQL_Llamadas = Seleccionar('uvw_Sap_tbl_LlamadasServicios', 'TOP 100 *', $Where);
// echo "SELECT TOP 100 * FROM uvw_Sap_tbl_LlamadasServicios WHERE $Where";
?>

<div class="modal inmodal fade" id="mdArticulo" tabindex="1" role="dialog" aria-hidden="true">
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
												id="<?php echo $dim['IdPortalOne'] ?>" class="form-control select2"
												required>
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

										<select name="AlmacenDestino" id="AlmacenDestino" class="form-control select2"
											required>
											<option value="">Seleccione...</option>

											<?php while ($row_AlmacenDestino = sqlsrv_fetch_array($SQL_AlmacenDestino)) { ?>
												<option value="<?php echo $row_AlmacenDestino['ToWhsCode']; ?>"><?php echo $row_AlmacenDestino['ToWhsCode'] . " - " . $row_AlmacenDestino['ToWhsName']; ?></option>
											<?php } ?>
										</select>
									</div> <!-- col-xs-12 -->

									<div class="col-xs-12" style="margin-bottom: 10px;">
										<label class="control-label">Proyecto <span class="text-danger">*</span></label>

										<select id="PrjCode" name="PrjCode" class="form-control select2" required>
											<option value="">(NINGUNO)</option>

											<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
												<option value="<?php echo $row_Proyecto['IdProyecto']; ?>">
													<?php echo $row_Proyecto['IdProyecto'] . " - " . $row_Proyecto['DeProyecto']; ?>
												</option>
											<?php } ?>
										</select>
									</div> <!-- col-xs-12 -->

									<div class="col-xs-12">
										<button type="submit" class="btn btn-outline btn-success pull-right"><i
												class="fa fa-search"></i> Buscar</button>
									</div> <!-- col-xs-12 -->
								</div> <!-- form-group -->
							</div> <!-- col-lg-6 -->

						</div> <!-- ibox-content -->
					</div> <!-- row -->
				</form>
				<!-- Fin, filtros -->

				<!-- Inicio, tabla -->
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<div class="table-responsive" id="tableContainer">
								<table id="footable" class="table" data-paging="true" data-sorting="true">
									<thead>
										<tr>
											<th>Fecha creación</th>
											<th>Sucursal</th>
											<th>Cliente</th>
											<th>Estado</th>
											<th>Tipo llamada</th>
											<th>Asunto</th>
											<th>Ticket</th>
											<th data-breakpoints="all">Serial Interno</th>
											<th data-breakpoints="all">Asignado por</th>
											<th data-breakpoints="all">Tipo problema</th>
											<th data-breakpoints="all">Estado servicio</th>
											<th data-breakpoints="all">Acciones</th>
										</tr>
									</thead>
									<tbody>
										<?php while ($row_Llamadas = sql_fetch_array($SQL_Llamadas)) { ?>
											<tr>
												<td>
													<?php echo $row_Llamadas['FechaHoraCreacionLLamada']->format('Y-m-d H:i'); ?>
												</td>
												<td>
													<?php echo $row_Llamadas['NombreSucursal']; ?>
												</td>
												<td>
													<?php echo $row_Llamadas['NombreClienteLlamada']; ?>
												</td>
												<td>
													<span <?php if ($row_Llamadas['IdEstadoLlamada'] == '-3') {
														echo "class='label label-info'";
													} elseif ($row_Llamadas['IdEstadoLlamada'] == '-2') {
														echo "class='label label-warning'";
													} else {
														echo "class='label label-danger'";
													} ?>>
														<?php echo $row_Llamadas['DeEstadoLlamada']; ?>
													</span>
												</td>
												<td>
													<?php echo $row_Llamadas['DeTipoLlamada']; ?>
												</td>
												<td>
													<?php echo $row_Llamadas['AsuntoLlamada']; ?>
												</td>
												<td>
													<a type="button" class="btn btn-success btn-xs"
														onclick="cambiarOT('<?php echo $row_Llamadas['ID_LlamadaServicio']; ?>', '<?php echo $row_Llamadas['DocNum'] . ' - ' . $row_Llamadas['AsuntoLlamada'] . ' (' . $row_Llamadas['DeTipoLlamada'] . ')'; ?>')"><b>
															<?php echo $row_Llamadas['DocNum']; ?>
														</b></a>
												</td>
												<td>
													<?php echo $row_Llamadas['IdNumeroSerie']; ?>
												</td>
												<td>
													<?php echo $row_Llamadas['DeAsignadoPor']; ?>
												</td>
												<td>
													<?php echo $row_Llamadas['DeTipoProblemaLlamada']; ?>
												</td>
												<td>
													<span <?php if ($row_Llamadas['CDU_EstadoServicio'] == '0') {
														echo "class='label label-warning'";
													} elseif ($row_Llamadas['CDU_EstadoServicio'] == '1') {
														echo "class='label label-primary'";
													} else {
														echo "class='label label-danger'";
													} ?>>
														<?php echo $row_Llamadas['DeEstadoServicio']; ?>
													</span>
												</td>
												<td>
													<a target="_blank"
														href="llamada_servicio.php?id=<?php echo base64_encode($row_Llamadas['ID_LlamadaServicio']); ?>&tl=1"
														class="btn btn-success btn-xs"><i class="fa fa-folder-open-o"></i>
														Abrir</a>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
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
</div>

<script>
	function cambiarOT(orden_trabajo, descripcion_ot) {
		$("#OrdenServicioCliente").val(orden_trabajo);
		$("#Desc_OrdenServicioCliente").val(descripcion_ot);
		$('#mdOT').modal('hide');
	}



	$(document).ready(function () {
		$('#footable').footable();

		// Inicio, cambio asincrono de sucursal en base al cliente.
		$("#NombreCliente").on("change", function () {
			var NomCliente = document.getElementById("NombreCliente");
			var Cliente = document.getElementById("Cliente");

			if (NomCliente.value == "") {
				Cliente.value = "";
				$("#Cliente").trigger("change");
			}
		});

		$("#Cliente").change(function () {
			var Cliente = document.getElementById("Cliente");

			$.ajax({
				type: "POST",
				url: "ajx_cbo_sucursales_clientes_simple.php?CardCode=" + Cliente.value,
				success: function (response) {
					$('#Sucursal').html(response).fadeIn();
				}
			});
		});
		// Fin, cambio asincrono de sucursal en base al cliente.

		$('#formBuscar').on('submit', function (event) {
			// Stiven Muñoz Murillo, 04/08/2022
			event.preventDefault();
		});

		$("#formBuscar").validate({
			submitHandler: function (form) {
				$('.ibox-content').toggleClass('sk-loading');

				let formData = new FormData(form);

				let json = Object.fromEntries(formData);
				console.log("Line 250", json);

				// Inicio, AJAX
				$.ajax({
					url: 'md_consultar_llamadas_servicios_ws.php',
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