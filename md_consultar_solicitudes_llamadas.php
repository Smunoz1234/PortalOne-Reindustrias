<?php
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
$Filtro .= " AND [IdSeries] IN (" . $FilSerie . ")";
$SQL_SeriesLlamada = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Filtrar cliente y sucursales
$ID_CodigoClienteSLS = "";
if (($edit == 1) || ($sw_error == 1)) {
	$ID_CodigoClienteSLS = $row['CardCode'] ?? "";
} elseif ((isset($dt_LS) && ($dt_LS == 1)) || (isset($dt_OV) && ($dt_OV == 1)) || (isset($dt_ET) && ($dt_ET == 1))) {
	$ID_CodigoClienteSLS = $row_Cliente['CodigoCliente'];
}

if ($ID_CodigoClienteSLS != "") {
	$Filtro .= " AND ID_CodigoCliente = '$ID_CodigoClienteSLS'";

	$Where = "CodigoCliente = '$ID_CodigoClienteSLS'";
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
$Where = "([FechaCreacionLLamada] BETWEEN '$FechaInicial' AND '$FechaFinal') $Filtro";
$SQL_SolicitudesLlamadas = Seleccionar('uvw_tbl_SolicitudLlamadasServicios', 'TOP 100 *', $Where);
?>

<div class="modal inmodal fade" id="mdSLS" tabindex="1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" style="width: 60% !important;">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">
					Consultar Solicitudes de Llamadas de servicio
				</h4>
			</div>
			<div class="modal-body">

				<!-- Inicio, filtros -->
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<form id="formBuscarSLS" class="form-horizontal">
								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para
											filtrar</h3>
									</label>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Fechas</label>
									<div class="col-lg-5">
										<div class="input-daterange input-group" id="datepicker">
											<input name="FechaInicialSLS" autocomplete="off" type="text"
												class="input-sm form-control" id="FechaInicialSLS"
												placeholder="Fecha inicial" value="<?php echo $FechaInicial; ?>" />
											<span class="input-group-addon">hasta</span>
											<input name="FechaFinalSLS" autocomplete="off" type="text"
												class="input-sm form-control" id="FechaFinalSLS" placeholder="Fecha final"
												value="<?php echo $FechaFinal; ?>" />
										</div>
									</div>
									<label class="col-lg-1 control-label">Cliente</label>
									<div class="col-lg-5">
										<input name="ClienteSLS" type="hidden" id="ClienteSLS"
											value="<?php echo $ID_CodigoClienteSLS ?? ''; ?>">
										<input name="NombreClienteSLS" type="text" class="form-control" id="NombreClienteSLS"
											placeholder="Para TODOS, dejar vacio..."
											value="<?php echo $row_ClienteLlamada['NombreCliente'] ?? ''; ?>">
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Serie</label>
									<div class="col-lg-5">
										<select name="SeriesSLS" class="form-control" id="SeriesSLS">
											<option value="">(Todos)</option>
											<?php while ($row_Series = sqlsrv_fetch_array($SQL_SeriesLlamada)) { ?>
												<option value="<?php echo $row_Series['IdSeries']; ?>"><?php echo $row_Series['DeSeries']; ?></option>
											<?php } ?>
										</select>
									</div>
									<label class="col-lg-1 control-label">Sucursal</label>
									<div class="col-lg-5">
										<select id="Sucursal" name="Sucursal" class="form-control">
											<option value="">(Todos)</option>
											<?php while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) { ?>
												<option value="<?php echo $row_Sucursal['NombreSucursal']; ?>"><?php echo $row_Sucursal['NombreSucursal']; ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Solicitud</label>
									<div class="col-lg-6">
										<input name="IDSolicitud" type="text" class="form-control" id="IDSolicitud"
											maxlength="50"
											placeholder="Digite un número completo, o una parte del mismo..." value="<?php if (isset($_GET['IDSolicitud']) && ($_GET['IDSolicitud'] != "")) {
												echo $_GET['IDSolicitud'];
											} ?>">
									</div>
									<div class="col-lg-4"> <!-- pull-right -->
										<button type="submit" class="btn btn-outline btn-success"><i
												class="fa fa-search"></i> Buscar</button>
									</div>
								</div>
							</form>
						</div> <!-- ibox-content -->
					</div> <!-- col-lg-12 -->
				</div>
				<!-- Fin, filtros -->

				<?php
				if (!$SQL_SolicitudesLlamadas) {
					echo "Consulta:<br>";
					echo "SELECT TOP 100 * FROM uvw_tbl_SolicitudLlamadasServicios WHERE $Where";
				} ?>

				<!-- Inicio, tabla -->
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<div class="table-responsive" id="tableContainerSLS">
								<table id="footableSLS" class="table" data-paging="true" data-sorting="true">
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
											<th data-breakpoints="all">Tipo problema</th>
											<th data-breakpoints="all">Estado servicio</th>
											<th data-breakpoints="all">Acciones</th>
										</tr>
									</thead>
									<tbody>
										<?php while ($row_SolicitudesLlamadas = sqlsrv_fetch_array($SQL_SolicitudesLlamadas)) { ?>
											<tr>
												<td>
													<?php echo $row_SolicitudesLlamadas['FechaHoraCreacionLLamada']->format('Y-m-d H:i'); ?>
												</td>
												<td>
													<?php echo $row_SolicitudesLlamadas['NombreSucursal']; ?>
												</td>
												<td>
													<?php echo $row_SolicitudesLlamadas['NombreClienteLlamada']; ?>
												</td>

												<td>
													<span <?php if ($row_SolicitudesLlamadas['IdEstadoLlamada'] == '-3') {
														echo "class='label label-info'";
													} else {
														echo "class='label label-warning'";
													} ?>>
														<?php echo $row_SolicitudesLlamadas['NombreEstado']; ?>
													</span>
												</td>
												
												<td>
													<?php echo $row_SolicitudesLlamadas['DeTipoLlamada']; ?>
												</td>
												<td>
													<?php echo $row_SolicitudesLlamadas['AsuntoLlamada']; ?>
												</td>
												<td>
													<a type="button" class="btn btn-success btn-xs"
														title="Cambiar Agenda"
														onclick="cambiarSLS('<?php echo $row_SolicitudesLlamadas['ID_SolicitudLlamadaServicio']; ?>', '<?php echo $row_SolicitudesLlamadas['ID_SolicitudLlamadaServicio'] . ' - ' . $row_SolicitudesLlamadas['AsuntoLlamada'] . ' (' . $row_SolicitudesLlamadas['DeTipoLlamada'] . ')'; ?>')">
														<i class="fa fa-refresh"></i> <b><?php echo $row_SolicitudesLlamadas['ID_SolicitudLlamadaServicio']; ?></b>
													</a>
												</td>
												<td>
													<?php echo $row_SolicitudesLlamadas['IdNumeroSerie']; ?>
												</td>
												<td>
													<?php echo $row_SolicitudesLlamadas['DeTipoProblemaLlamada']; ?>
												</td>
												<td>
													<span <?php if ($row_SolicitudesLlamadas['CDU_EstadoServicio'] == '0') {
														echo "class='label label-warning'";
													} elseif ($row_SolicitudesLlamadas['CDU_EstadoServicio'] == '1') {
														echo "class='label label-primary'";
													} else {
														echo "class='label label-danger'";
													} ?>>
														<?php echo $row_SolicitudesLlamadas['DeEstadoServicio']; ?>
													</span>
												</td>
												<td>
													<a target="_blank"
														href="llamada_servicio.php?id=<?php echo base64_encode($row_SolicitudesLlamadas['ID_SolicitudesLlamadaServicio']); ?>&tl=1"
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
	function cambiarSLS(orden_trabajo, descripcion_ot) {
		$("#SolicitudLlamadaCliente").val(orden_trabajo);
		$("#Desc_SolicitudLlamadaCliente").val(descripcion_ot);
		$('#mdSLS').modal('hide');
	}

	$(document).ready(function () {
		$('#footableSLS').footable();

		// Inicio, cambio asincrono de sucursal en base al cliente.
		$("#NombreClienteSLS").on("change", function () {
			let NomCliente2 = document.getElementById("NombreClienteSLS");
			
			if (NomCliente2.value == "") {
				$("#ClienteSLS").val("");
				$("#ClienteSLS").trigger("change");
			}
		});

		$("#ClienteSLS").change(function () {
			let Cliente2 = document.getElementById("ClienteSLS").value;

			$.ajax({
				type: "POST",
				url: `ajx_cbo_sucursales_clientes_simple.php?CardCode=${Cliente2}`,
				success: function (response) {
					$('#Sucursal').html(response).fadeIn();
				}
			});
		});
		// Fin, cambio asincrono de sucursal en base al cliente.

		$('#formBuscarSLS').on('submit', function (event) {
			// Stiven Muñoz Murillo, 04/08/2022
			event.preventDefault();
		});

		$("#formBuscarSLS").validate({
			submitHandler: function (form) {
				$('.ibox-content').toggleClass('sk-loading');

				let formData = new FormData(form);

				let json = Object.fromEntries(formData);
				console.log("Line 300", json);

				// Inicio, AJAX
				$.ajax({
					url: 'md_consultar_solicitudes_llamadas_ws.php',
					type: 'POST',
					data: formData,
					processData: false,  // tell jQuery not to process the data
					contentType: false,   // tell jQuery not to set contentType
					success: function (response) {
						// console.log("Line 310", response);

						$("#tableContainerSLS").html(response);
						$('#footableSLS').footable();

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
		$('#FechaInicialSLS').datepicker({
			todayBtn: "linked",
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true,
			todayHighlight: true,
			format: 'yyyy-mm-dd'
		});
		$('#FechaFinalSLS').datepicker({
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
					var value = $("#NombreClienteSLS").getSelectedItemData().CodigoCliente;
					$("#ClienteSLS").val(value).trigger("change");
				}
			}
		};
		$("#NombreClienteSLS").easyAutocomplete(options);
	});
</script>