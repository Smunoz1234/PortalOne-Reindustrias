<?php require_once "includes/conexion.php";

// PermitirAcceso(1605);
$sw = 0;

// Fechas
$FI_FechaVigencia = "";
$FF_FechaVigencia = "";
if (isset($_GET['FI_FechaVigencia']) && $_GET['FI_FechaVigencia'] != "") {
	$FI_FechaVigencia = $_GET['FI_FechaVigencia'];
	$sw = 1;
} else {
	// Restar 7 dias a la fecha actual
	$fecha = date('Y-m-d');
	$nuevafecha = strtotime('-' . ObtenerVariable("DiasRangoFechasGestionar") . ' day');
	$nuevafecha = date('Y-m-d', $nuevafecha);
	$FI_FechaVigencia = $nuevafecha;
}
if (isset($_GET['FF_FechaVigencia']) && $_GET['FF_FechaVigencia'] != "") {
	$FF_FechaVigencia = $_GET['FF_FechaVigencia'];
	$sw = 1;
} else {
	$FF_FechaVigencia = date('Y-m-d');
}

// Filtros
$Estado = $_GET['Estado'] ?? "";
$Campana = $_GET['Campana'] ?? "";
$Proveedor = $_GET['Proveedor'] ?? "";
$Sucursal = $_GET['Sucursal'] ?? "";
$BuscarDato = $_GET['BuscarDato'] ?? "";

if ($sw == 1) {
	$Filtro = "(fecha_limite_vigencia BETWEEN '" . FormatoFecha($FI_FechaVigencia) . "' AND '" . FormatoFecha($FI_FechaVigencia) . "')";

	$Filtro .= ($Estado == "") ? "" : "AND estado = '$Estado'";
	$Filtro .= ($Campana == "") ? "" : "AND id_campana = '$Campana'";
	$Filtro .= ($Proveedor == "") ? "" : "AND id_socio_negocio = '$Proveedor'";
	$Filtro .= ($Sucursal == "") ? "" : "AND id_consecutivo_direccion = '$Sucursal'";

	$Filtro .= ($BuscarDato == "") ? "" : " AND (
		id_campana LIKE '%$BuscarDato%' OR
		campana LIKE '%$BuscarDato%' OR
		socio_negocio LIKE '%$BuscarDato%' OR
		direccion_destino LIKE '%$BuscarDato%'
	)";

	$Cons = "SELECT * FROM uvw_Sap_tbl_CampanaVehiculos WHERE $Filtro";
	$SQL = sqlsrv_query($conexion, $Cons);

	if (!$SQL) {
		echo $Cons;
	}

	// Desde ajx_cbo_select(3)
	if ($Proveedor != "") {
		$Parametros = array(
			"'$Proveedor'",
			"'S'", // Destino (S) / Facturacion (B)
			"'" . $_SESSION['CodUser'] . "'",
			1, // Proveedor (1) / Cliente (0)
		);

		$SQL_Sucursal = EjecutarSP('sp_ConsultarSucursalesClientes', $Parametros);
	}
}
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include "includes/cabecera.php"; ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Campañas de Vehículos |
		<?php echo NOMBRE_PORTAL; ?>
	</title>
	<!-- InstanceEndEditable -->
	<!-- InstanceBeginEditable name="head" -->
	<script type="text/javascript">
		$(document).ready(function () {
			$("#NombreProveedor").change(function () {
				var NomCliente = document.getElementById("NombreProveedor");
				var Cliente = document.getElementById("Proveedor");
				if (NomCliente.value == "") {
					Cliente.value = "";
				}
			});
		});
	</script>
	<!-- InstanceEndEditable -->

	<style>
		table.dataTable tbody tr.selected {
			background-color: gray !important;
		}
	</style>
</head>

<body>

	<div id="wrapper">

		<?php include "includes/menu.php"; ?>

		<div id="page-wrapper" class="gray-bg">
			<?php include "includes/menu_superior.php"; ?>
			<!-- InstanceBeginEditable name="Contenido" -->
			<div class="row wrapper border-bottom white-bg page-heading">
				<div class="col-sm-8">
					<h2>Campañas de Vehículos</h2>
					<ol class="breadcrumb">
						<li>
							<a href="#">Inicio</a>
						</li>
						<li>
							<a href="#">Servicios</a>
						</li>
						<li class="active">
							<strong>Campañas de Vehículos</strong>
						</li>
					</ol>
				</div>
				<?php if (PermitirFuncion(1602)) { ?>
					<div class="col-sm-4">
						<div class="title-action">
							<a href="tarjeta_equipo.php" class="alkin btn btn-primary"><i class="fa fa-plus-circle"></i>
								Crear nueva tarjeta de equipo</a>
						</div>
					</div>
				<?php } ?>
				<?php //echo $Cons;?>
			</div>
			<div class="wrapper wrapper-content">
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<?php include "includes/spinner.php"; ?>

							<form action="gestionar_campanas_vehiculo.php" method="get" id="formBuscar"
								class="form-horizontal">
								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para
											filtrar</h3>
									</label>
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Fechas Vigentes</label>
									<div class="col-lg-3">
										<div class="input-daterange input-group">
											<input name="FI_FechaVigencia" type="text"
												class="input-sm form-control fecha" id="FI_FechaVigencia"
												placeholder="Fecha inicial" value="<?php echo $FI_FechaVigencia; ?>"
												autocomplete="off" />
											<span class="input-group-addon">hasta</span>
											<input name="FF_FechaVigencia" type="text"
												class="input-sm form-control fecha" id="FF_FechaVigencia"
												placeholder="Fecha final" value="<?php echo $FF_FechaVigencia; ?>"
												autocomplete="off" />
										</div>
									</div>

									<label class="col-lg-1 control-label">
										Estado Campaña <span class="text-danger">*</span>
									</label>
									<div class="col-lg-3">
										<select name="Estado" class="form-control" id="Estado" required>
											<option value="Y" <?php if ($Estado == "Y") {
												echo "selected";
											} ?>>Activa</option>
											<option value="N" <?php if ($Estado == "N") {
												echo "selected";
											} ?>>Inactiva
											</option>
										</select>
									</div>

									<label class="col-lg-1 control-label">ID Campaña</label>
									<div class="col-lg-3">
										<input name="Campana" type="text" class="form-control" id="Campana"
											maxlength="100" value="<?php echo $Campana; ?>">
									</div>
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Proveedor</label>
									<div class="col-lg-3">
										<input name="Proveedor" type="hidden" id="Proveedor" value="<?php if (isset($_GET['Proveedor']) && ($_GET['Proveedor'] != "")) {
											echo $_GET['Proveedor'];
										} ?>">
										<input name="NombreProveedor" type="text" class="form-control"
											id="NombreProveedor" placeholder="Para TODOS, dejar vacio..." value="<?php if (isset($_GET['NombreProveedor']) && ($_GET['NombreProveedor'] != "")) {
												echo $_GET['NombreProveedor'];
											} ?>">
									</div>

									<label class="col-lg-1 control-label">Sucursal Proveedor</label>
									<div class="col-lg-3">
										<select id="Sucursal" name="Sucursal" class="form-control select2">
											<option value="">(Todos)</option>

											<?php if ($Proveedor != "") { ?>
												<?php while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) { ?>
													<option value="<?php echo $row_Sucursal['NombreSucursal']; ?>" <?php if (strcmp($row_Sucursal['NombreSucursal'], $_GET['Sucursal']) == 0) {
														   echo "selected";
													   } ?>>
														<?php echo $row_Sucursal['NombreSucursal']; ?>
													</option>
												<?php } ?>
											<?php } ?>
										</select>
									</div>

									<label class="col-lg-1 control-label">Buscar Dato</label>
									<div class="col-lg-3">
										<input name="BuscarDato" type="text" class="form-control" id="BuscarDato"
											maxlength="100" value="<?php echo $BuscarDato; ?>">
									</div>
								</div>

								<div class="form-group">
									<div class="col-lg-12">
										<button type="submit" class="btn btn-outline btn-success pull-right"><i
												class="fa fa-search"></i> Buscar</button>
									</div>
								</div>

								<?php if (($sw == 1) && sqlsrv_has_rows($SQL)) { ?>
									<div class="form-group">
										<div class="col-lg-10">
											<a href="exportar_excel.php?exp=20&b64=0&Cons=<?php echo $Cons; ?>">
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
				<br>

				<?php if ($sw == 1) { ?>
					<div class="row">
						<div class="col-lg-12">
							<div class="ibox-content">
								<?php include "includes/spinner.php"; ?>
								<div class="table-responsive">
									<table class="table table-striped table-bordered table-hover dataTables-example"
										id="example">
										<thead>
											<tr>
												<th>Núm.</th>
												<th>Código cliente</th>
												<th>Nombre cliente</th>
												<th>Serial interno</th>
												<th>Marca vehículo</th>
												<th>Ciudad Sede</th>
												<th>Concesionario</th>
												<th>Fecha Matricula</th>
												<th>Fecha SOAT</th>
												<th>Fecha Tecno.</th>
												<th>Fecha Ult. Camb. Aceite</th>
												<th>Fecha Prox. Camb. Aceite</th>
												<th>Novedad</th>
												<th>Fecha Agenda</th>
												<th>Fecha Ult. Mant.</th>
												<th>Fecha Prox. Mant.</th>
												<th>Estado</th>
												<th>Acciones</th>
											</tr>
										</thead>
										<tbody>
											<?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
												<tr class="gradeX tooltip-demo">
													<td>
														<?php echo $row['IdTarjetaEquipo']; ?>
													</td>
													<td>
														<?php echo $row['CardCode']; ?>
													</td>
													<td>
														<?php echo $row['CardName']; ?>
													</td>
													<td>
														<?php echo $row['SerialInterno']; ?>
													</td>
													<td>
														<?php echo $row['CDU_Marca']; ?>
													</td>
													<td>
														<?php echo $row['CDU_SedeVenta']; ?>
													</td>
													<td>
														<?php echo $row['CDU_Concesionario']; ?>
													</td>
													<td>
														<?php echo ($row['CDU_FechaMatricula'] != "") ? $row['CDU_FechaMatricula']->format('Y-m-d') : ""; ?>
													</td>
													<td>
														<?php echo ($row['CDU_Fecha_SOAT'] != "") ? $row['CDU_Fecha_SOAT']->format('Y-m-d') : ""; ?>
													</td>
													<td>
														<?php echo ($row['CDU_Fecha_Tecno'] != "") ? $row['CDU_Fecha_Tecno']->format('Y-m-d') : ""; ?>
													</td>
													<td>
														<?php echo ($row['CDU_FechaUlt_CambAceite'] != "") ? $row['CDU_FechaUlt_CambAceite']->format('Y-m-d') : ""; ?>
													</td>
													<td>
														<?php echo ($row['CDU_FechaProx_CambAceite'] != "") ? $row['CDU_FechaProx_CambAceite']->format('Y-m-d') : ""; ?>
													</td>

													<td>
														<?php echo $row['CDU_Novedad']; ?>
													</td>
													<td>
														<?php echo ($row['CDU_FechaAgenda'] != "") ? $row['CDU_FechaAgenda']->format('Y-m-d') : ""; ?>
													</td>

													<td>
														<?php echo ($row['CDU_FechaUlt_Mant'] != "") ? $row['CDU_FechaUlt_Mant']->format('Y-m-d') : ""; ?>
													</td>
													<td>
														<?php echo ($row['CDU_FechaProx_Mant'] != "") ? $row['CDU_FechaProx_Mant']->format('Y-m-d') : ""; ?>
													</td>
													<td>
														<?php if ($row['CodEstado'] == 'A') { ?>
															<span class='label label-info'>Activo</span>
														<?php } elseif ($row['CodEstado'] == 'R') { ?>
															<span class='label label-danger'>Devuelto</span>
														<?php } elseif ($row['CodEstado'] == 'T') { ?>
															<span class='label label-success'>Finalizado</span>
														<?php } elseif ($row['CodEstado'] == 'L') { ?>
															<span class='label label-secondary'>Concedido en préstamo</span>
														<?php } elseif ($row['CodEstado'] == 'I') { ?>
															<span class='label label-warning'>En laboratorio de reparación</span>
														<?php } ?>
													</td>
													<td>
														<div>
															<a href="tarjeta_equipo.php?id=<?php echo base64_encode($row['IdTarjetaEquipo']); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('informe_tarjeta_equipo.php'); ?>&tl=1"
																class="alkin btn btn-success btn-xs"><i
																	class="fa fa-folder-open-o"></i> Abrir</a>
															<a target="_blank"
																href="gestionar_cartera.php?Clt=<?php echo base64_encode($row['CardCode']); ?>&TE=<?php echo base64_encode($row['IdTarjetaEquipo']); ?>"
																class="btn btn-info btn-xs"><i class="fa fa-plus"></i> Crear
																Gestión CRM</a>
														</div>
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
			<?php include "includes/footer.php"; ?>

		</div>
	</div>
	<?php include "includes/pie.php"; ?>
	<!-- InstanceBeginEditable name="EditRegion4" -->
	<script>
		$(document).ready(function () {
			$('#example tbody').on('click', 'tr', function () {
				if ($(this).hasClass('selected')) {
					$(this).removeClass('selected');
				} else {
					$('#example tr.selected').removeClass('selected');
					$(this).addClass('selected');
				}
			});

			$("#formBuscar").validate({
				submitHandler: function (form) {
					$('.ibox-content').toggleClass('sk-loading');
					form.submit();
				}
			});
			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});

			// SMM, 25/08/2022
			$('.fecha').datepicker({
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
				url: function (phrase) {
					return `ajx_buscar_datos_json.php?type=7&id=${phrase}&pv=1`;
				},

				getValue: "NombreBuscarCliente",
				requestDelay: 400,
				list: {
					match: {
						enabled: true
					},
					onClickEvent: function () {
						var value = $("#NombreProveedor").getSelectedItemData().CodigoCliente;
						$("#Proveedor").val(value).trigger("change");
					},
					onKeyEnterEvent: function () {
						var value = $("#NombreProveedor").getSelectedItemData().CodigoCliente;
						$("#Proveedor").val(value).trigger("change");
					}
				}
			};

			$("#NombreProveedor").easyAutocomplete(options);

			$('.dataTables-example').DataTable({
				pageLength: 25,
				dom: '<"html5buttons"B>lTfgitp',
				order: [[0, "desc"]],
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