<?php require_once "includes/conexion.php";

// PermitirAcceso(1605);
$ID = $_GET['id'] ?? "";
$Edit = $_GET['edit'] ?? 0;

$Titulo = ($Edit != 0) ? "Editar Campaña de Vehículos" : "Crear Campaña de Vehículos";

$Cons_Encabezado = "SELECT * FROM tbl_CampanaVehiculos WHERE id_campana = '$ID'";
$SQL_Encabezado = sqlsrv_query($conexion, $Cons_Encabezado);
$row_Encabezado = sqlsrv_fetch_array($SQL_Encabezado);

$Campana = $row_Encabezado['campana'] ?? "";
$Comentario = $row_Encabezado['descripcion_campana'] ?? "";
$Estado = $row_Encabezado['estado'] ?? "";

$FechaVigencia = isset($row_Encabezado['fecha_limite_vigencia']) ? $row_Encabezado['fecha_limite_vigencia']->format("Y-m-d") : "";

$Proveedor = $row_Encabezado['id_socio_negocio'] ?? "";
$Sucursal = $row_Encabezado['id_consecutivo_direccion'] ?? "";

$Cons_Detalle = "SELECT * FROM tbl_CampanaVehiculosDetalle WHERE id_campana = '$ID'";
$SQL_Detalle = sqlsrv_query($conexion, $Cons_Detalle);

if (!$SQL_Encabezado || !$SQL_Detalle) {
	echo $Cons_Encabezado;
	echo "<br>$Cons_Detalle";
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
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include "includes/cabecera.php"; ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>
		<?php echo $Titulo; ?>
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
					<h2>
						<?php echo $Titulo; ?>
					</h2>
					<ol class="breadcrumb">
						<li>
							<a href="#">Inicio</a>
						</li>
						<li>
							<a href="#">Servicios</a>
						</li>
						<li class="active">
							<strong>
								<?php echo $Titulo; ?>
							</strong>
						</li>
					</ol>
				</div>

				<?php if (true) { ?>
					<div class="col-sm-4">
						<div class="title-action">
							<a href="gestionar_campanas_vehiculo.php" class="alkin btn btn-default">
								<i class="fa fa-arrow-circle-o-left"></i> Regresar
							</a>
						</div>
					</div>
				<?php } ?>

				<?php // echo $Cons_Detalle; ?>
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
										<h3 class="bg-success p-xs b-r-sm">
											<i class="fa fa-info-circle"></i> Información de la Campaña
										</h3>
									</label>
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">
										ID Campaña <span class="text-danger">*</span>
									</label>
									<div class="col-lg-3">
										<input name="ID" type="text" class="form-control" id="ID" maxlength="100"
											value="<?php echo $ID; ?>">
									</div>

									<label class="col-lg-1 control-label">
										Nombre Campaña <span class="text-danger">*</span>
									</label>
									<div class="col-lg-3">
										<input name="Campana" type="text" class="form-control" id="Campana"
											maxlength="100" value="<?php echo $Campana; ?>">
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
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Fecha Límite Vigente</label>
									<div class="col-lg-3 input-group date">
										<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
											autocomplete="off" name="FechaVigencia" id="FechaVigencia" type="text"
											class="form-control fecha" placeholder="AAAA-MM-DD"
											value="<?php echo $FechaVigencia; ?>">
									</div>

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
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Comentario</label>
									<div class="col-lg-7">
										<textarea name="Comentario" rows="3" maxlength="3000" class="form-control"
											id="Comentario" type="text"><?php echo $Comentario; ?></textarea>
									</div>

									<div class="col-lg-4">
										<br>
										<div class="btn-group pull-right">
											<button type="button" class="btn btn-outline btn-primary">
												<i class="fa <?php echo ($Edit == 0) ? "fa-plus" : "fa-refresh"; ?>"></i>
												<?php echo ($Edit == 0) ? "Crear Campaña" : "Actualizar Campaña"; ?>
											</button>

											<button type="button" class="btn btn-outline btn-info" style="margin-left: 10px;"
												<?php if ($Edit == 0) { echo "disabled"; } ?>>
												<i class="fa fa-plus"></i> Adicionar VIN
											</button>
										</div>
									</div>
								</div>

								<?php if (($Edit == 1) && sqlsrv_has_rows($SQL_Detalle)) { ?>
									<div class="form-group">
										<div class="col-lg-10">
											<a href="exportar_excel.php?exp=20&b64=0&Cons=<?php echo $Cons_Detalle; ?>">
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

				<?php if ($Edit == 1) { ?>
					<div class="row">
						<div class="col-lg-12">
							<div class="ibox-content">
								<?php include "includes/spinner.php"; ?>
								<div class="table-responsive">
									<table class="table table-striped table-bordered table-hover dataTables-example"
										id="example">
										<thead>
											<tr>
												<th>ID</th>
												<th>VIN</th>
												<th>Estado VIN</th>
												<th>ID Llamada Servicio</th>
												<th>Origen</th>
												<th>Estado Llamada</th>
												<th>Nombre Cliente</th>
												<th>Fecha Cierre</th>
												<th>Acciones</th>
											</tr>
										</thead>
										<tbody>
											<?php while ($row_Detalle = sqlsrv_fetch_array($SQL_Detalle)) { ?>
												<tr class="gradeX tooltip-demo">
													<td>
														<?php echo $row_Detalle['id_campana'] . "-" . $row_Detalle['id_campana_detalle']; ?>
													</td>
													<td>
														<?php echo $row_Detalle['VIN']; ?>
													</td>
													<td>
														<span class="label <?php echo ($row_Detalle['estado_VIN'] == "P") ? "label-warning" : "label-info"; ?>">
															<?php echo ($row_Detalle['estado_VIN'] == "P") ? "Pendiente" : "Aplicado"; ?>
														</span>
													</td>
													<td class="text-left">
														<?php if (isset($row_Detalle['docnum_llamada_servicio']) && ($row_Detalle['docnum_llamada_servicio'] != "")) { ?>
																<a href="llamada_servicio.php?id=<?php echo base64_encode($row_Detalle['docentry_llamada_servicio']); ?>&tl=1&pag=<?php echo base64_encode('gestionar_llamadas_servicios.php'); ?>" class="alkin btn btn-success btn-xs">
																	<i class="fa fa-folder-open-o"></i> <?php echo $row_Detalle['docnum_llamada_servicio']; ?>
																</a>
														<?php } ?>
													</td>

													<td><?php echo $row_Detalle['DeOrigenLlamada'] ?? ""; ?></td>
													<td><?php echo $row_Detalle['DeEstadoLlamada'] ?? ""; ?></td>
													<td><?php echo $row_Detalle['socio_negocios'] ?? ""; ?></td>
													<td><?php echo (isset($row_Detalle["FechaCierre"]) && $row_Detalle["FechaCierre"] != "") ? $row_Detalle['FechaCierre']->format("Y-m-d") : ""; ?></td>
													
													<td>
														<button type="button" id="btnDelete<?php //echo $row_Proceso['IdInterno']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php //echo $row_Proceso['IdInterno']; ?>');"><i class="fa fa-trash"></i> Eliminar</button>
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