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
$Filtro .= " AND [Series] IN (" . $FilSerie . ")";
$SQL_SeriesLlamada = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Filtrar cliente y sucursales
$ID_CodigoCliente = "";
if (($edit == 1) || ($sw_error == 1)) {
    $ID_CodigoCliente = $row['CardCode'] ?? "";
} elseif ((isset($dt_LS) && ($dt_LS == 1)) || (isset($dt_OV) && ($dt_OV == 1)) || (isset($dt_ET) && ($dt_ET == 1))) {
    $ID_CodigoCliente = $row_Cliente['CodigoCliente'] ?? "";
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
?>

<div class="modal inmodal fade" id="mdOT" tabindex="1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" style="width: 60% !important;">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">
					Consultar llamadas de servicio
				</h4>
			</div>
			<div class="modal-body">

				<!-- Inicio, filtros -->
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<form id="formBuscarOT" class="form-horizontal">
								<div class="form-group">
									<label class="col-xs-12"><h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para filtrar</h3></label>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Fechas</label>
									<div class="col-lg-5">
										<div class="input-daterange input-group" id="datepicker">
											<input name="FechaInicialOT" autocomplete="off" type="text" class="input-sm form-control" id="FechaInicialOT" placeholder="Fecha inicial" value="<?php echo $FechaInicial; ?>"/>
											<span class="input-group-addon">hasta</span>
											<input name="FechaFinalOT" autocomplete="off" type="text" class="input-sm form-control" id="FechaFinalOT" placeholder="Fecha final" value="<?php echo $FechaFinal; ?>" />
										</div>
									</div>
									<label class="col-lg-1 control-label">Cliente</label>
									<div class="col-lg-5">
										<input name="ClienteOT" type="hidden" id="ClienteOT" value="<?php echo $ID_CodigoCliente ?? ''; ?>">
										<input name="NombreClienteOT" type="text" class="form-control" id="NombreClienteOT" placeholder="Para TODOS, dejar vacio..." value="<?php echo $row_ClienteLlamada['NombreClienteOT'] ?? ''; ?>">
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Serie</label>
									<div class="col-lg-5">
										<select name="SeriesOT" class="form-control" id="SeriesOT">
											<option value="">(Todos)</option>
											<?php while ($row_Series = sqlsrv_fetch_array($SQL_SeriesLlamada)) {?>
												<option value="<?php echo $row_Series['IdSeries']; ?>"><?php echo $row_Series['DeSeries']; ?></option>
											<?php }?>
										</select>
									</div>
									<label class="col-lg-1 control-label">Sucursal</label>
									<div class="col-lg-5">
										<select id="Sucursal" name="Sucursal" class="form-control">
											<option value="">(Todos)</option>
											<?php while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) {?>
												<option value="<?php echo $row_Sucursal['NombreSucursal']; ?>"><?php echo $row_Sucursal['NombreSucursal']; ?></option>
											<?php }?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Ticket</label>
									<div class="col-lg-6">
										<input name="IDTicket" type="text" class="form-control" id="IDTicket" maxlength="50" placeholder="Digite un número completo, o una parte del mismo..." value="<?php if (isset($_GET['IDTicket']) && ($_GET['IDTicket'] != "")) {echo $_GET['IDTicket'];}?>">
									</div>
									<div class="col-lg-4"> <!-- pull-right -->
										<button type="submit" class="btn btn-outline btn-success"><i class="fa fa-search"></i> Buscar</button>
									</div>
								</div>
							</form>
						</div> <!-- ibox-content -->
					</div> <!-- col-lg-12 -->
				</div>
				<!-- Fin, filtros -->

				<?php
				if (!$SQL_Llamadas) {
					echo "SELECT TOP 100 * FROM uvw_Sap_tbl_LlamadasServicios WHERE $Where";
				} ?>

				<!-- Inicio, tabla -->
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<div class="table-responsive" id="tableContainerOT">
								<table id="footableOT" class="table" data-paging="true" data-sorting="true">
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
									<?php while ($row_Llamadas = sqlsrv_fetch_array($SQL_Llamadas)) {?>
										<tr>
											<td><?php echo $row_Llamadas['FechaHoraCreacionLLamada']->format('Y-m-d H:i'); ?></td>
											<td><?php echo $row_Llamadas['NombreSucursal']; ?></td>
											<td><?php echo $row_Llamadas['NombreClienteLlamada']; ?></td>
											<td>
												<span <?php if ($row_Llamadas['IdEstadoLlamada'] == '-3') {echo "class='label label-info'";} elseif ($row_Llamadas['IdEstadoLlamada'] == '-2') {echo "class='label label-warning'";} else {echo "class='label label-danger'";}?>>
													<?php echo $row_Llamadas['DeEstadoLlamada']; ?>
												</span>
											</td>
											<td><?php echo $row_Llamadas['DeTipoLlamada']; ?></td>
											<td><?php echo $row_Llamadas['AsuntoLlamada']; ?></td>
											<td>
												<a type="button" class="btn btn-success btn-xs" onclick="cambiarOT('<?php echo $row_Llamadas['ID_LlamadaServicio']; ?>', '<?php echo $row_Llamadas['DocNum'] . ' - ' . $row_Llamadas['AsuntoLlamada'] . ' (' . $row_Llamadas['DeTipoLlamada'] . ')'; ?>')"><b><?php echo $row_Llamadas['DocNum']; ?></b></a>
											</td>
											<td><?php echo $row_Llamadas['IdNumeroSerie']; ?></td>
											<td><?php echo $row_Llamadas['DeAsignadoPor']; ?></td>
											<td><?php echo $row_Llamadas['DeTipoProblemaLlamada']; ?></td>
											<td>
												<span <?php if ($row_Llamadas['CDU_EstadoServicio'] == '0') {echo "class='label label-warning'";} elseif ($row_Llamadas['CDU_EstadoServicio'] == '1') {echo "class='label label-primary'";} else {echo "class='label label-danger'";}?>>
													<?php echo $row_Llamadas['DeEstadoServicio']; ?>
												</span>
											</td>
											<td>
												<a target="_blank" href="llamada_servicio.php?id=<?php echo base64_encode($row_Llamadas['ID_LlamadaServicio']); ?>&tl=1" class="btn btn-success btn-xs"><i class="fa fa-folder-open-o"></i> Abrir</a>
											</td>
										</tr>
									<?php }?>
									</tbody>
								</table>
							</div> <!-- table-responsive -->
						</div> <!-- ibox-content -->
					</div> <!-- col-lg-12 -->
				</div>
				<!-- Fin, tabla -->

			</div> <!-- modal-body -->
			<div class="modal-footer">
				<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
			</div>
		</div> <!-- modal-content -->
	</div> <!-- modal-dialog -->
</div>

<script>
function cambiarOT(orden_trabajo, descripcion_ot){
	$("#OrdenServicioCliente").val(orden_trabajo);
	$("#Desc_OrdenServicioCliente").val(descripcion_ot);
	$('#mdOT').modal('hide');
}



$(document).ready(function(){
	$('#footableOT').footable();

	// Inicio, cambio asincrono de sucursal en base al cliente.
	$("#NombreClienteOT").on("change", function() {
		var NomCliente=document.getElementById("NombreClienteOT");
		var Cliente=document.getElementById("ClienteOT");

		if(NomCliente.value==""){
			Cliente.value="";
			$("#ClienteOT").trigger("change");
		}
	});

	$("#ClienteOT").change(function(){
		var Cliente=document.getElementById("Cliente");

		$.ajax({
			type: "POST",
			url: "ajx_cbo_sucursales_clientes_simple.php?CardCode="+Cliente.value,
			success: function(response){
				$('#Sucursal').html(response).fadeIn();
			}
		});
	});
	// Fin, cambio asincrono de sucursal en base al cliente.

	$('#formBuscarOT').on('submit', function (event) {
		// Stiven Muñoz Murillo, 04/08/2022
		event.preventDefault();
	});

	$("#formBuscarOT").validate({
		submitHandler: function(form){
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
				success: function(response) {
					// console.log("Line 260", response);

					$("#tableContainerOT").html(response);
					$('#footableOT').footable();

					$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
				},
				error: function(error) {
					console.error(error.responseText);

					$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
				}
			});
			// Fin, AJAX
		}
	});
	$('#FechaInicialOT').datepicker({
		todayBtn: "linked",
		keyboardNavigation: false,
		forceParse: false,
		calendarWeeks: true,
		autoclose: true,
		todayHighlight: true,
		format: 'yyyy-mm-dd'
	});
	$('#FechaFinalOT').datepicker({
		todayBtn: "linked",
		keyboardNavigation: false,
		forceParse: false,
		calendarWeeks: true,
		autoclose: true,
		todayHighlight: true,
		format: 'yyyy-mm-dd'
	});
	$('.chosen-select').chosen({width: "100%"});
	var options = {
		adjustWidth: false,
		url: function(phrase) {
			return "ajx_buscar_datos_json.php?type=7&id="+phrase;
		},
		getValue: "NombreBuscarCliente",
		requestDelay: 400,
		list: {
			match: {
				enabled: true
			},
			onClickEvent: function() {
				var value = $("#NombreClienteOT").getSelectedItemData().CodigoCliente;
				$("#ClienteOT").val(value).trigger("change");
			}
		}
	};
	$("#NombreClienteOT").easyAutocomplete(options);
});
</script>