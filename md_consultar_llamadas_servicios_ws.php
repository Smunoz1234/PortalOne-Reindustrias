<?php
$sw = 0;

//Serie de llamada
$ParamSerie = array(
    "'" . $_SESSION['CodUser'] . "'",
    "'191'",
    1,
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

//Fechas
if (isset($_GET['FechaInicial']) && $_GET['FechaInicial'] != "") {
    $FechaInicial = $_GET['FechaInicial'];
    $sw = 1;
} else {
    //Restar 7 dias a la fecha actual
    $fecha = date('Y-m-d');
    $nuevafecha = strtotime('-' . ObtenerVariable("DiasRangoFechasDocSAP") . ' day');
    $nuevafecha = date('Y-m-d', $nuevafecha);
    $FechaInicial = $nuevafecha;
}
if (isset($_GET['FechaFinal']) && $_GET['FechaFinal'] != "") {
    $FechaFinal = $_GET['FechaFinal'];
    $sw = 1;
} else {
    $FechaFinal = date('Y-m-d');
}

//Filtros
$Filtro = ""; //Filtro
if (isset($_GET['EstadoLlamada']) && $_GET['EstadoLlamada'] != "") {
    $Filtro .= " and [IdEstadoLlamada]='" . $_GET['EstadoLlamada'] . "'";
    $sw = 1;
}

//Cliente
if (isset($_GET['Cliente'])) {
    if ($_GET['Cliente'] != "") { //Si se selecciono el cliente
        $Filtro .= " and ID_CodigoCliente='" . $_GET['Cliente'] . "'";
        $sw_suc = 1; //Cuando se ha seleccionado una sucursal
        if (isset($_GET['Sucursal'])) {
            if ($_GET['Sucursal'] == "") {
                //Sucursales
                if (PermitirFuncion(205)) {
                    $Where = "CodigoCliente='" . $_GET['Cliente'] . "'";
                    $SQL_Sucursal = Seleccionar("uvw_Sap_tbl_Clientes_Sucursales", "NombreSucursal", $Where);
                } else {
                    $Where = "CodigoCliente='" . $_GET['Cliente'] . "' and ID_Usuario = " . $_SESSION['CodUser'];
                    $SQL_Sucursal = Seleccionar("uvw_tbl_SucursalesClienteUsuario", "NombreSucursal", $Where);
                }
                $j = 0;
                unset($WhereSuc);
                $WhereSuc = array();
                while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) {
                    $WhereSuc[$j] = "NombreSucursal='" . $row_Sucursal['NombreSucursal'] . "'";
                    $j++;
                }
                $FiltroSuc = implode(" OR ", $WhereSuc);
                $Filtro .= " and (" . $FiltroSuc . ")";
            } else {
                $Filtro .= " and NombreSucursal='" . $_GET['Sucursal'] . "'";
            }
        }

    } else {
        if (!PermitirFuncion(205)) {
            $Where = "ID_Usuario = " . $_SESSION['CodUser'];
            $SQL_Cliente = Seleccionar("uvw_tbl_ClienteUsuario", "CodigoCliente, NombreCliente", $Where);
            $k = 0;
            while ($row_Cliente = sqlsrv_fetch_array($SQL_Cliente)) {

                //Sucursales
                $Where = "CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' and ID_Usuario = " . $_SESSION['CodUser'];
                $SQL_Sucursal = Seleccionar("uvw_tbl_SucursalesClienteUsuario", "NombreSucursal", $Where);

                $j = 0;
                unset($WhereSuc);
                $WhereSuc = array();
                while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) {
                    $WhereSuc[$j] = "NombreSucursal='" . $row_Sucursal['NombreSucursal'] . "'";
                    $j++;
                }

                $FiltroSuc = implode(" OR ", $WhereSuc);

                if ($k == 0) {
                    $Filtro .= " AND (ID_CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' AND (" . $FiltroSuc . "))";
                } else {
                    $Filtro .= " OR (ID_CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' AND (" . $FiltroSuc . "))";
                }

                $k++;
            }
        }
    }
} else {
    if (!PermitirFuncion(205)) {
        $Where = "ID_Usuario = " . $_SESSION['CodUser'];
        $SQL_Cliente = Seleccionar("uvw_tbl_ClienteUsuario", "CodigoCliente, NombreCliente", $Where);
        $k = 0;
        while ($row_Cliente = sqlsrv_fetch_array($SQL_Cliente)) {

            //Sucursales
            $Where = "CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' and ID_Usuario = " . $_SESSION['CodUser'];
            $SQL_Sucursal = Seleccionar("uvw_tbl_SucursalesClienteUsuario", "NombreSucursal", $Where);

            $j = 0;
            unset($WhereSuc);
            $WhereSuc = array();
            while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) {
                $WhereSuc[$j] = "NombreSucursal='" . $row_Sucursal['NombreSucursal'] . "'";
                $j++;
            }

            $FiltroSuc = implode(" OR ", $WhereSuc);

            if ($k == 0) {
                $Filtro .= " AND (ID_CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' AND (" . $FiltroSuc . "))";
            } else {
                $Filtro .= " OR (ID_CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' AND (" . $FiltroSuc . "))";
            }

            $k++;
        }
    }
}

if (isset($_GET['Series']) && $_GET['Series'] != "") {
    $Filtro .= " and [Series]='" . $_GET['Series'] . "'";
    $sw = 1;
} else {
    $FilSerie = "";
    $i = 0;
    while ($row_Series = sqlsrv_fetch_array($SQL_Series)) {
        if ($i == 0) {
            $FilSerie .= "'" . $row_Series['IdSeries'] . "'";
        } else {
            $FilSerie .= ",'" . $row_Series['IdSeries'] . "'";
        }
        $i++;
    }
    $Filtro .= " and [Series] IN (" . $FilSerie . ")";
    $SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);
}

if ($sw == 1) {
    //$Where="([FechaCreacionLLamada] Between '$FechaInicial' and '$FechaFinal') $Filtro";
    //$SQL=Seleccionar('uvw_Sap_tbl_LlamadasServicios','*',$Where);
    $Cons = "Select * From uvw_Sap_tbl_LlamadasServicios Where (FechaCreacionLLamada Between '$FechaInicial' and '$FechaFinal') $Filtro";
    $SQL = sqlsrv_query($conexion, $Cons);

    //echo $Cons;
    // echo "<br>sw==1";
} else {
    // Metodo = 0, sincronizado con SAP
    $Where = "Metodo = 0 AND ([FechaCreacionLLamada] Between '$FechaInicial' and '$FechaFinal') $Filtro";
    $SQL = Seleccionar('uvw_Sap_tbl_LlamadasServicios', 'TOP 10 *', $Where);
    //$Cons="Select TOP 100 * From uvw_Sap_tbl_LlamadasServicios ";
    //$Cons="";

    //echo "sw!=1";
}

if (isset($_GET['IDTicket']) && $_GET['IDTicket'] != "") {
    $Where = "DocNum LIKE '%" . trim($_GET['IDTicket']) . "%'";

    $FilSerie = "";
    $i = 0;
    while ($row_Series = sqlsrv_fetch_array($SQL_Series)) {
        if ($i == 0) {
            $FilSerie .= "'" . $row_Series['IdSeries'] . "'";
        } else {
            $FilSerie .= ",'" . $row_Series['IdSeries'] . "'";
        }
        $i++;
    }
    $Where .= " and [Series] IN (" . $FilSerie . ")";
    $SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

    $SQL = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', $Where);
}
?>

<div class="modal inmodal fade" id="mdOT" tabindex="1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg">
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
							<?php include "includes/spinner.php";?>
							<form id="formBuscar" class="form-horizontal">
								<div class="form-group">
									<label class="col-xs-12"><h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para filtrar</h3></label>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Fechas</label>
									<div class="col-lg-3">
										<div class="input-daterange input-group" id="datepicker">
											<input name="FechaInicial" autocomplete="off" type="text" class="input-sm form-control" id="FechaInicial" placeholder="Fecha inicial" value="<?php echo $FechaInicial; ?>"/>
											<span class="input-group-addon">hasta</span>
											<input name="FechaFinal" autocomplete="off" type="text" class="input-sm form-control" id="FechaFinal" placeholder="Fecha final" value="<?php echo $FechaFinal; ?>" />
										</div>
									</div>
									<label class="col-lg-1 control-label">Serie</label>
									<div class="col-lg-2">
										<select name="Series" class="form-control" id="Series">
											<option value="">(Todos)</option>
											<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) {?>
												<option value="<?php echo $row_Series['IdSeries']; ?>"><?php echo $row_Series['DeSeries']; ?></option>
											<?php }?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Cliente</label>
									<div class="col-lg-3">
										<input name="Cliente" type="hidden" id="Cliente" value="<?php if (isset($_GET['Cliente']) && ($_GET['Cliente'] != "")) {echo $_GET['Cliente'];}?>">
										<input name="NombreCliente" type="text" class="form-control" id="NombreCliente" placeholder="Para TODOS, dejar vacio..." value="<?php if (isset($_GET['NombreCliente']) && ($_GET['NombreCliente'] != "")) {echo $_GET['NombreCliente'];}?>">
									</div>
									<label class="col-lg-1 control-label">Sucursal</label>
									<div class="col-lg-3">
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
									<div class="col-lg-2">
										<input name="IDTicket" type="text" class="form-control" id="IDTicket" maxlength="50" placeholder="Digite un número completo, o una parte del mismo..." value="<?php if (isset($_GET['IDTicket']) && ($_GET['IDTicket'] != "")) {echo $_GET['IDTicket'];}?>">
									</div>
									<div class="col-lg-1 pull-right">
										<button type="submit" class="btn btn-outline btn-success pull-right"><i class="fa fa-search"></i> Buscar</button>
									</div>
								</div>
							</form>
						</div> <!-- ibox-content -->
					</div> <!-- col-lg-12 -->
				</div>
				<!-- Fin, filtros -->

				<!-- Inicio, tabla -->
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<?php include "includes/spinner.php";?>
							<div class="table-responsive">
								<table class="table table-striped table-bordered table-hover dataTables-example" >
									<thead>
										<tr>
											<th>Ticket</th>
											<th>Asignado por</th>
											<th>Estado servicio</th>
											<th>Asunto</th>
											<th>Tipo problema</th>
											<th>Tipo llamada</th>
											<th>Cliente</th>
											<th>Sucursal</th>
											<th>Serial Interno</th>
											<th>Fecha creación</th>
											<th>Estado</th>
											<th>Acciones</th>
										</tr>
									</thead>
									<tbody>
										<?php while ($row = sql_fetch_array($SQL)) {?>
											<tr class="gradeX">
												<td><?php echo $row['DocNum']; ?></td>
												<td><?php echo $row['DeAsignadoPor']; ?></td>
												<td><span <?php if ($row['CDU_EstadoServicio'] == '0') {echo "class='label label-warning'";} elseif ($row['CDU_EstadoServicio'] == '1') {echo "class='label label-primary'";} else {echo "class='label label-danger'";}?>><?php echo $row['DeEstadoServicio']; ?></span></td>
												<td><?php echo $row['AsuntoLlamada']; ?></td>
												<td><?php echo $row['DeTipoProblemaLlamada']; ?></td>
												<td><?php echo $row['DeTipoLlamada']; ?></td>
												<td><?php echo $row['NombreClienteLlamada']; ?></td>
												<td><?php echo $row['NombreSucursal']; ?></td>
												<td><?php echo $row['IdNumeroSerie']; ?></td>
												<td><?php echo $row['FechaHoraCreacionLLamada']->format('Y-m-d H:i'); ?></td>
												<td><span <?php if ($row['IdEstadoLlamada'] == '-3') {echo "class='label label-info'";} elseif ($row['IdEstadoLlamada'] == '-2') {echo "class='label label-warning'";} else {echo "class='label label-danger'";}?>><?php echo $row['DeEstadoLlamada']; ?></span></td>
												<td>
													<a href="llamada_servicio.php?id=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&tl=1&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('gestionar_llamadas_servicios.php'); ?>" class="alkin btn btn-success btn-xs"><i class="fa fa-folder-open-o"></i> Abrir</a>
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
$(document).ready(function(){
	$("#formBuscar").validate({
		submitHandler: function(form){
			$('.ibox-content').toggleClass('sk-loading');
			form.submit();
		}
	});
	$(".alkin").on('click', function(){
		$('.ibox-content').toggleClass('sk-loading');
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
	$('.chosen-select').chosen({width: "100%"});
	var options = {
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
				var value = $("#NombreCliente").getSelectedItemData().CodigoCliente;
				$("#Cliente").val(value).trigger("change");
			}
		}
	};
	$("#NombreCliente").easyAutocomplete(options);
	$('.dataTables-example').DataTable({
		pageLength: 25,
		order: [[ 0, "desc" ]],
		dom: '<"html5buttons"B>lTfgitp',
		language: {
			"decimal":        "",
			"emptyTable":     "No se encontraron resultados.",
			"info":           "Mostrando _START_ - _END_ de _TOTAL_ registros",
			"infoEmpty":      "Mostrando 0 - 0 de 0 registros",
			"infoFiltered":   "(filtrando de _MAX_ registros)",
			"infoPostFix":    "",
			"thousands":      ",",
			"lengthMenu":     "Mostrar _MENU_ registros",
			"loadingRecords": "Cargando...",
			"processing":     "Procesando...",
			"search":         "Filtrar:",
			"zeroRecords":    "Ningún registro encontrado",
			"paginate": {
				"first":      "Primero",
				"last":       "Último",
				"next":       "Siguiente",
				"previous":   "Anterior"
			},
			"aria": {
				"sortAscending":  ": Activar para ordenar la columna ascendente",
				"sortDescending": ": Activar para ordenar la columna descendente"
			}
		},
		buttons: []
	});
});
</script>