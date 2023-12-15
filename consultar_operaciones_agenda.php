<?php require_once "includes/conexion.php";
// require_once("includes/conexion_hn.php");
PermitirAcceso(339);

$sw = 0;

$Cliente = "";
$Sucursal = "";
$Serie = "";
$TipoLlamada = "";
$EstadoLlamada = "";
$EstadoServicioLlamada = "";
$NombreEmpleado = "";
$NombreAdicional = "";

$sw_suc = 0;

// Estado llamada
$SQL_EstadoLlamada = Seleccionar('uvw_tbl_EstadoLlamada', '*');

// SMM, 29/11/2023
$SQL_OrigenLlamada = Seleccionar('uvw_Sap_tbl_LlamadasServiciosOrigen', '*', "Activo = 'Y'", 'DeOrigenLlamada');
$SQL_TipoLlamadas = Seleccionar('uvw_Sap_tbl_TipoLlamadas', '*', "Activo = 'Y'", 'DeTipoLlamada');
$SQL_TipoProblema = Seleccionar('uvw_Sap_tbl_TipoProblemasLlamadas', '*', "Activo = 'Y'", 'DeTipoProblemaLlamada');
$SQL_SubTipoProblema = Seleccionar('uvw_Sap_tbl_SubTipoProblemasLlamadas', '*', "Activo = 'Y'", 'DeSubTipoProblemaLlamada');

// Estado servicio llamada
$SQL_EstServLlamada = Seleccionar('uvw_Sap_tbl_LlamadasServiciosEstadoServicios', '*', '', 'DeEstadoServicio');

// Empleados. SMM, 29/11/2023
$SQL_EmpleadoActividad = Seleccionar('uvw_Sap_tbl_Empleados', '*', "IdUsuarioSAP=0", 'NombreEmpleado');
$SQL_EmpleadoAdicional = Seleccionar('uvw_Sap_tbl_Empleados', '*', "IdUsuarioSAP=0", 'NombreEmpleado');

// Serie de llamada
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'191'",
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Fechas
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

if (isset($_GET['Cliente']) && $_GET['Cliente'] != "") {
	$Cliente = $_GET['Cliente'];
	$sw_suc = 1;
	$sw = 1;
}

if (isset($_GET['Sucursal']) && $_GET['Sucursal'] != "") {
	$Sucursal = $_GET['Sucursal'];
	$sw = 1;
}

if (isset($_GET['Series']) && $_GET['Series'] != "") {
	$Serie = $_GET['Series'];
	$sw = 1;
}

if (isset($_GET['EstadoLlamada']) && $_GET['EstadoLlamada'] != "") {
	$EstadoLlamada = $_GET['EstadoLlamada'];
	$sw = 1;
}

if (isset($_GET['NombreEmpleado']) && $_GET['NombreEmpleado'] != "") {
	$NombreEmpleado = $_GET['NombreEmpleado'];
	$sw = 1;
}

// SMM, 29/11/2023
if (isset($_GET['NombreAdicional']) && $_GET['NombreAdicional'] != "") {
	$NombreAdicional = $_GET['NombreAdicional'];
	$sw = 1;
}

$EstadoServicioLlamada = isset($_GET['EstadoServicio']) ? implode(",", $_GET['EstadoServicio']) : "";
$FiltroOperacion = isset($_GET['FiltroOperacion']) ? $_GET['FiltroOperacion'] : "1";

// SMM, 29/11/2023
$TipoLlamada = isset($_GET['TipoLlamada']) ? implode(",", $_GET['TipoLlamada']) : "";
$OrigenLlamada = isset($_GET['OrigenLlamada']) ? implode(",", $_GET['OrigenLlamada']) : "";
$TipoProblema = isset($_GET['TipoProblema']) ? implode(",", $_GET['TipoProblema']) : "";
$SubTipoProblema = isset($_GET['SubTipoProblema']) ? implode(",", $_GET['SubTipoProblema']) : "";

if ($sw == 1) {
	$Param = array(
		"'" . FormatoFecha($FechaInicial) . "'",
		"'" . FormatoFecha($FechaFinal) . "'",
		"'$Cliente'",
		"'$Sucursal'",
		"'$Serie'",
		"'$TipoLlamada'",
		"'$OrigenLlamada'",
		"'$TipoProblema'",
		"'$SubTipoProblema'",
		"'$EstadoLlamada'",
		"'$EstadoServicioLlamada'",
		"'$NombreEmpleado'",
		"'$NombreAdicional'",
		"'$FiltroOperacion'",
		"'" . strtolower($_SESSION['User']) . "'",
	);
	$SQL = EjecutarSP("usp_rep_OperacionesReindustrias_SolicitudLLamadas", $Param);
	//    sqlsrv_next_result($SQL);
	//    print_r($row);

	// SMM, 30/08/2022
	$parametros = base64_encode(implode(",", $Param));
	$procedimiento = base64_encode("usp_rep_OperacionesReindustrias_SolicitudLLamadas");
	$ruta_excel = "exportar_excel.php?exp=19&Cons=$parametros&sp=$procedimiento";
}
?>
<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include_once "includes/cabecera.php"; ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>
		Gestión de operaciones | <?php echo NOMBRE_PORTAL; ?>
	</title>
	<!-- InstanceEndEditable -->
	<!-- InstanceBeginEditable name="head" -->
	<style>
		.modal-dialog {
			width: 70% !important;
		}

		.modal-footer {
			border: 0px !important;
		}
	</style>
	<script type="text/javascript">
		$(document).ready(function () {
			$("#NombreCliente").change(function () {
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
						$('#Sucursal').html(response).fadeIn().change();
					}
				});
			});

		});
	</script>
	<!-- InstanceEndEditable -->
</head>

<body>

	<div id="wrapper">

		<?php include_once "includes/menu.php"; ?>

		<div id="page-wrapper" class="gray-bg">
			<?php include_once "includes/menu_superior.php"; ?>
			<!-- InstanceBeginEditable name="Contenido" -->
			<div class="row wrapper border-bottom white-bg page-heading">
				<div class="col-sm-8">
					<h2>Gestión de operaciones - Solicitud Llamada (Agenda)</h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">Servicios</a>
						</li>
						<li>
							<a href="#">Asistentes</a>
						</li>
						<li class="active">
							<strong>Gestión de operaciones - Solicitud Llamada (Agenda)</strong>
						</li>
					</ol>
				</div>
			</div>
			<div class="wrapper wrapper-content">
				<div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
					<div class="modal-dialog modal-lg">
						<div class="modal-content">
							<div class="modal-header">
								<h4 class="modal-title" id="TituloModal"></h4>
							</div>
							<div class="modal-body" id="ContenidoModal">
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-success m-t-md" data-dismiss="modal"><i
										class="fa fa-times"></i> Cerrar</button>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<?php include "includes/spinner.php"; ?>
							<form action="consultar_operaciones_agenda.php" method="get" id="formBuscar"
								class="form-horizontal">
								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para
											filtrar</h3>
									</label>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Filtro operacion</label>
									<div class="col-lg-3">
										<select name="FiltroOperacion" class="form-control" id="FiltroOperacion">
											<option value="1" <?php if (isset($_GET['FiltroOperacion']) && ($_GET['FiltroOperacion'] == "1")) {
												echo "selected";
											} ?>>
												Solicitud Llamada (Agenda)
											</option>
										</select>
									</div>
									<label class="col-lg-1 control-label">Fechas</label>
									<div class="col-lg-3">
										<div class="input-daterange input-group" id="datepicker">
											<input name="FechaInicial" type="text" class="input-sm form-control"
												id="FechaInicial" placeholder="Fecha inicial"
												value="<?php echo $FechaInicial; ?>" />
											<span class="input-group-addon">hasta</span>
											<input name="FechaFinal" type="text" class="input-sm form-control"
												id="FechaFinal" placeholder="Fecha final"
												value="<?php echo $FechaFinal; ?>" />
										</div>
									</div>
									
									<label class="col-lg-1 control-label">Serie</label>
									<div class="col-lg-3">
										<select name="Series" class="form-control" id="Series">
											<option value="">(Todos)</option>
											<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
												<option value="<?php echo $row_Series['IdSeries']; ?>" <?php if ((isset($_GET['Series'])) && (strcmp($row_Series['IdSeries'], $_GET['Series']) == 0)) {
													   echo "selected";
												   } ?>>
													<?php echo $row_Series['DeSeries']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Tipo llamada</label>
									<div class="col-lg-3">
										<select data-placeholder="(Todos)" name="TipoLlamada[]"
											class="form-control chosen-select" id="TipoLlamada" multiple>
											<?php $j = 0;
											while ($row_TipoLlamadas = sqlsrv_fetch_array($SQL_TipoLlamadas)) { ?>
												<option value="<?php echo $row_TipoLlamadas['IdTipoLlamada']; ?>" <?php if ((isset($_GET['TipoLlamada'][$j]) && ($_GET['TipoLlamada'][$j] != "")) && (strcmp($row_TipoLlamadas['IdTipoLlamada'], $_GET['TipoLlamada'][$j]) == 0)) {
													   echo "selected";
													   $j++;
												   } ?>>
													<?php echo $row_TipoLlamadas['DeTipoLlamada']; ?>
												</option>
											<?php } ?>
										</select>
									</div>

									<label class="col-lg-1 control-label">Cliente</label>
									<div class="col-lg-3">
										<input name="Cliente" type="hidden" id="Cliente" value="<?php if (isset($_GET['Cliente']) && ($_GET['Cliente'] != "")) {
											echo $_GET['Cliente'];
										} ?>">
										<input name="NombreCliente" type="text" class="form-control" id="NombreCliente"
											placeholder="Ingrese para buscar..." value="<?php if (isset($_GET['NombreCliente']) && ($_GET['NombreCliente'] != "")) {
												echo $_GET['NombreCliente'];
											} ?>">
									</div>

									<label class="col-lg-1 control-label">Sucursal cliente</label>
									<div class="col-lg-3">
										<select id="Sucursal" name="Sucursal" class="form-control select2">
											<option value="">(Todos)</option>
											<?php
											if ($sw_suc == 1) { //Cuando se ha seleccionado una opción
												if (PermitirFuncion(205)) {
													$Where = "CodigoCliente='" . $_GET['Cliente'] . "'";
													$SQL_Sucursal = Seleccionar("uvw_Sap_tbl_Clientes_Sucursales", "NombreSucursal", $Where);
												} else {
													$Where = "CodigoCliente='" . $_GET['Cliente'] . "' and ID_Usuario = " . $_SESSION['CodUser'];
													$SQL_Sucursal = Seleccionar("uvw_tbl_SucursalesClienteUsuario", "NombreSucursal", $Where);
												}
												while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) { ?>
													<option value="<?php echo $row_Sucursal['NombreSucursal']; ?>" <?php if (strcmp($row_Sucursal['NombreSucursal'], $_GET['Sucursal']) == 0) {
														   echo "selected";
													   } ?>>
														<?php echo $row_Sucursal['NombreSucursal']; ?>
													</option>
												<?php }
											} ?>
										</select>
									</div>	
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Origen llamada</label>
									<div class="col-lg-3">
										<select data-placeholder="(Todos)" name="OrigenLlamada[]"
											class="form-control chosen-select" id="OrigenLlamada" multiple>
											<?php $j = 0;
											while ($row_OrigenLlamada = sqlsrv_fetch_array($SQL_OrigenLlamada)) { ?>
												<option value="<?php echo $row_OrigenLlamada['IdOrigenLlamada']; ?>" <?php if ((isset($_GET['OrigenLlamada'][$j]) && ($_GET['OrigenLlamada'][$j] != "")) && (strcmp($row_OrigenLlamada['IdOrigenLlamada'], $_GET['OrigenLlamada'][$j]) == 0)) {
													   echo "selected";
													   $j++;
												   } ?>>
													<?php echo $row_OrigenLlamada['DeOrigenLlamada']; ?>
												</option>
											<?php } ?>
										</select>
									</div>

									<label class="col-lg-1 control-label">Técnico responsable</label>
									<div class="col-lg-3">
										<select name="NombreEmpleado" class="form-control select2" id="NombreEmpleado">
											<option value="">(Todos)</option>
											<?php while ($row_EmpleadoActividad = sqlsrv_fetch_array($SQL_EmpleadoActividad)) { ?>
												<option value="<?php echo $row_EmpleadoActividad['NombreEmpleado']; ?>"
													<?php if ((isset($_GET['NombreEmpleado'])) && (strcmp($row_EmpleadoActividad['NombreEmpleado'], $_GET['NombreEmpleado']) == 0)) {
														echo "selected";
													} ?>>
													<?php echo $row_EmpleadoActividad['NombreEmpleado']; ?>
												</option>
											<?php } ?>
										</select>
									</div>

									<label class="col-lg-1 control-label">Estado llamada</label>
									<div class="col-lg-3">
										<select name="EstadoLlamada" class="form-control" id="EstadoLlamada">
											<option value="">(Todos)</option>
											<?php while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) { ?>
												<option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($_GET['EstadoLlamada'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $_GET['EstadoLlamada']) == 0)) {
													   echo "selected";
												   } ?>>
													<?php echo $row_EstadoLlamada['NombreEstado']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Tipo problema</label>
									<div class="col-lg-3">
										<select data-placeholder="(Todos)" name="TipoProblema[]"
											class="form-control chosen-select" id="TipoProblema" multiple>
											<?php $j = 0;
											while ($row_TipoProblema = sqlsrv_fetch_array($SQL_TipoProblema)) { ?>
												<option value="<?php echo $row_TipoProblema['IdTipoProblemaLlamada']; ?>" <?php if ((isset($_GET['TipoProblema'][$j]) && ($_GET['TipoProblema'][$j] != "")) && (strcmp($row_TipoProblema['IdTipoProblemaLlamada'], $_GET['TipoProblema'][$j]) == 0)) {
													   echo "selected";
													   $j++;
												   } ?>>
													<?php echo $row_TipoProblema['DeTipoProblemaLlamada']; ?>
												</option>
											<?php } ?>
										</select>
									</div>

									<label class="col-lg-1 control-label">Técnico adicional</label>
									<div class="col-lg-3">
										<select name="NombreAdicional" class="form-control select2" id="NombreAdicional">
											<option value="">(Todos)</option>
											
											<?php while ($row_EmpleadoAdicional = sqlsrv_fetch_array($SQL_EmpleadoAdicional)) { ?>
												<option value="<?php echo $row_EmpleadoAdicional['NombreEmpleado']; ?>"
													<?php if (isset($_GET['NombreAdicional']) && ($row_EmpleadoAdicional['NombreEmpleado'] == $_GET['NombreAdicional'])) {
														echo "selected";
													} ?>>
													<?php echo $row_EmpleadoAdicional['NombreEmpleado']; ?>
												</option>
											<?php } ?>
										</select>
									</div>

									<label class="col-lg-1 control-label">Estado servicio llamada</label>
									<div class="col-lg-3">
										<select data-placeholder="(Todos)" name="EstadoServicio[]"
											class="form-control chosen-select" id="EstadoServicio" multiple>
											<?php $j = 0;
											while ($row_EstServLlamada = sqlsrv_fetch_array($SQL_EstServLlamada)) { ?>
												<option value="<?php echo $row_EstServLlamada['IdEstadoServicio']; ?>" <?php if ((isset($_GET['EstadoServicio'][$j]) && ($_GET['EstadoServicio'][$j] != "")) && (strcmp($row_EstServLlamada['IdEstadoServicio'], $_GET['EstadoServicio'][$j]) == 0)) {
													   echo "selected";
													   $j++;
												   } ?>>
													<?php echo $row_EstServLlamada['DeEstadoServicio']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Subtipo problema</label>
									<div class="col-lg-3">
										<select data-placeholder="(Todos)" name="SubTipoProblema[]"
											class="form-control chosen-select" id="SubTipoProblema" multiple>
											<?php $j = 0;
											while ($row_SubTipoProblema = sqlsrv_fetch_array($SQL_SubTipoProblema)) { ?>
												<option value="<?php echo $row_SubTipoProblema['IdSubTipoProblemaLlamada']; ?>" <?php if ((isset($_GET['SubTipoProblema'][$j]) && ($_GET['SubTipoProblema'][$j] != "")) && (strcmp($row_SubTipoProblema['IdSubTipoProblemaLlamada'], $_GET['SubTipoProblema'][$j]) == 0)) {
													   echo "selected";
													   $j++;
												   } ?>>
													<?php echo $row_SubTipoProblema['DeSubTipoProblemaLlamada']; ?>
												</option>
											<?php } ?>
										</select>
									</div>

									<div class="col-lg-4 pull-right">
										<button type="submit" class="btn btn-outline btn-success pull-right"><i
												class="fa fa-search"></i> Buscar</button>
									</div>
								</div>
								<?php if ($sw == 1) { ?>
									<hr>
									<div class="form-group">
										<!-- SMM, 30/08/2022 -->
										<label class="col-lg-1 control-label">Tipo Informe</label>
										<div class="col-lg-3">
											<select class="form-control" id="TipoInforme">
												<option value="">Informe Estándar</option>
											</select>
										</div>
										<!-- Hasta aquí, 30/08/2022 -->

										<div class="col-lg-4">
											<a id="btn_excel" href="#">
												<img src="css/exp_excel.png" width="50" height="30" alt="Exportar a Excel"
													title="Exportar a Excel" />
											</a>
										</div>
									</div>
								<?php } ?>
							</form>
						</div>
					</div>
				</div>
				<?php if ($sw == 1) { ?>
					<br>
					<div class="row">
						<div class="col-lg-12">
							<div class="ibox-content">
								<?php include "includes/spinner.php"; ?>
								<div class="table-responsive">
									<table class="table table-bordered table-hover table-striped dataTables-example">
										<thead>
											<tr>
												<th>ID</th>
												<th>Acciones</th>
												<th>Estado Servicio</th>
												<th>Fecha Agenda</th>
												<th>Hora Agenda</th>
												<th>Comentario Llamada</th>
												<th>Asesor</th>
												<th>Técnico</th>
												<th>Asunto</th>
												<th>Origen llamada</th>
												<th>Tipo problema</th>
												<th>Subtipo problema</th>
												<th>Tipo llamada</th>
												<th>Cliente</th>
												<th>Tel-Cel Cliente</th>
												<th>Correo</th>
												<th>Dirección</th>
												<th>Sucursal</th>
												<th>Marca</th>
												<th>Línea</th>
												<th>Serial Interno</th>
												<th>#Llamada</th>
												<th>#Recepción</th>
												<th>#Entrega</th>
											</tr>
										</thead>
										<tbody>
											<?php while ($row = sql_fetch_array($SQL)) { ?>
												<tr id="tr_<?php echo $row['NoAgenda']; ?>" class="gradeX">
													<td>
														<?php echo $row['NoAgenda']; ?>
													</td>
													<td>
														<a href="solicitud_llamada.php?id=<?php echo base64_encode($row['NoAgenda']); ?>&tl=1"
															class="btn btn-success btn-xs" target="_blank">
															<i class="fa fa-folder-open-o"></i> Abrir
														</a>
														<a href="sapdownload.php?id=<?php echo base64_encode('15'); ?>&type=<?php echo base64_encode('2'); ?>&DocKey=<?php echo base64_encode($row['NoAgenda']); ?>&ObType=<?php echo base64_encode('20008'); ?>&IdFrm=<?php echo base64_encode(0); ?>"
															target="_blank" class="btn btn-warning btn-xs">
															<i class="fa fa-download"></i> Descargar
														</a>
													</td>
													<td>
														<span class="label" style="color: white; background-color: <?php echo $row['ColorEstadoServicioLlamada'] ?? ""; ?>;">
															<?php echo $row['DeEstadoServicio'] ?? ""; ?>
														</span>
													</td>
													<td>
														<?php echo $row['FechaAgenda'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['HoraAgenda'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['RequerimientoLlamada'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['NombreAsesor'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['NombreTecnicoAdicional'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['Asunto'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['Origen'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['TipoProblema'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['SubtipoProblema'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['TipoLlamada'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['NombreCliente'] ?? ""; ?>
													</td>
													<td>
														<?php echo ($row['Telefono1'] ?? "") . "-" . ($row['Celular'] ?? ""); ?>
													</td>
													<td>
														<?php echo $row['Correo'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['DireccionLlamada'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['Sucursal'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['Marca'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['Linea'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['Placa'] ?? ""; ?>
													</td>
													<td>
														<a href="llamada_servicio.php?id=<?php echo base64_encode($row['ID_Llamada']); ?>&tl=1"
															target="_blank">
															<?php echo $row['DocNum_Llamada'] ?? ""; ?>
														</a>
													</td>
													<td>
														<?php echo $row['IdRecepcion'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['IdEntrega'] ?? ""; ?>
													</td>
												</tr>
											<?php } ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
			<!-- InstanceEndEditable -->
			<?php include_once "includes/footer.php"; ?>

		</div>
	</div>
	<?php include_once "includes/pie.php"; ?>
	<!-- InstanceBeginEditable name="EditRegion4" -->
	<script>
		$(document).ready(function () {
			// SMM, 30/08/2022
			$("#btn_excel").on("click", function () {
				$('.ibox-content').toggleClass('sk-loading');
				let ti = $("#TipoInforme").val();

				$.ajax({
					type: 'POST',
					url: `<?php echo $ruta_excel; ?>&TipoInforme=${ti}`,
					data: {},
					dataType: 'json'
				}).done(function (data) {
					if (data.op === "ok") {
						let $a = $("<a>");

						$a.attr("href", data.file);
						$("body").append($a);
						$a.attr("download", data.filename);
						$a[0].click();
						$a.remove();
					} else {
						alert("Consulta sin resultados.");
					}

					$('.ibox-content').toggleClass('sk-loading');
				}).fail(function (error) {
					console.error("Error en la descarga.");

					console.log(error.responseText);
					console.log(`<?php echo $ruta_excel; ?>&TipoInforme=${ti}`);

					$('.ibox-content').toggleClass('sk-loading');
				});
			});
			// Hasta aquí, 30/08/2022

			$("#formBuscar").validate({
				submitHandler: function (form) {
					$('.ibox-content').toggleClass('sk-loading');
					form.submit();
				}
			});
			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});
			$('#FechaInicial').datepicker({
				todayBtn: "linked",
				keyboardNavigation: false,
				forceParse: false,
				calendarWeeks: true,
				autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true
			});
			$('#FechaFinal').datepicker({
				todayBtn: "linked",
				keyboardNavigation: false,
				forceParse: false,
				calendarWeeks: true,
				autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true
			});
			$(".select2").select2();
			$('.chosen-select').chosen({ width: "100%" });
			$('.i-checks').iCheck({
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green',
			});

			var options = {
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

			$('.dataTables-example').DataTable({
				dom: '<"html5buttons"B>lTfgitp',
				lengthMenu: [[10, 25, 50, 100, 150, 200, -1], [10, 25, 50, 100, 150, 200, "Todos"]],
				language: {
					"decimal": "",
					"emptyTable": "No se encontraron resultados.",
					"info": "Mostrando _START_ - _END_ de _TOTAL_ registros",
					"infoEmpty": "Mostrando 0 - 0 de 0 registros",
					"infoFiltered": "(filtrando de _MAX_ registros)",
					"infoPostFix": "",
					"thousands": ",",
					"lengthMenu": "Mostrar _MENU_ registros",
					"loadingRecords": "Cargando...",
					"processing": "Procesando...",
					"search": "Filtrar:",
					"zeroRecords": "Ningún registro encontrado",
					"paginate": {
						"first": "Primero",
						"last": "Último",
						"next": "Siguiente",
						"previous": "Anterior"
					},
					"aria": {
						"sortAscending": ": Activar para ordenar la columna ascendente",
						"sortDescending": ": Activar para ordenar la columna descendente"
					}
				},
				buttons: []

			});

		});

	</script>
	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>
<?php sqlsrv_close($conexion); ?>