<?php require_once "includes/conexion.php";

PermitirAcceso(337);
$sw = 0;

// SMM, 23/11/2023
$SQL_Marca = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_MarcaVehiculo', '*');

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
	// $FI_FechaVigencia = $nuevafecha;
}
if (isset($_GET['FF_FechaVigencia']) && $_GET['FF_FechaVigencia'] != "") {
	$FF_FechaVigencia = $_GET['FF_FechaVigencia'];
	$sw = 1;
} else {
	// $FF_FechaVigencia = date('Y-m-d');
}

// Filtros
$Estado = $_GET['Estado'] ?? "";
$BuscarDato = $_GET['BuscarDato'] ?? "";

$IdMarca = $_GET['Marca'] ?? "";
$Campana = $_GET['Campana'] ?? "";
$Proveedor = $_GET['Proveedor'] ?? "";
$Sucursal = $_GET['Sucursal'] ?? "";

// SMM, 23/11/2023
if($Estado != "") {
	$sw = 1;
	$Filtro = "estado = '$Estado'";
}

if ($sw == 1) {
	$Filtro .= ($FI_FechaVigencia == "") ? "" : "AND fecha_limite_vigencia >= '" . FormatoFecha($FI_FechaVigencia) . "'";
	$Filtro .= ($FF_FechaVigencia == "") ? "" : "AND fecha_limite_vigencia >= '" . FormatoFecha($FF_FechaVigencia) . "'";
	
	$Filtro .= ($Campana == "") ? "" : "AND id_campana = '$Campana'";
	$Filtro .= ($IdMarca == "") ? "" : "AND id_marca = '$IdMarca'";
	$Filtro .= ($Proveedor == "") ? "" : "AND id_socio_negocio = '$Proveedor'";
	$Filtro .= ($Sucursal == "") ? "" : "AND id_direccion_destino = '$Sucursal'";

	$Filtro .= ($BuscarDato == "") ? "" : " AND (
		id_campana LIKE '%$BuscarDato%' OR
		campana LIKE '%$BuscarDato%' OR
		socio_negocio LIKE '%$BuscarDato%' OR
		direccion_destino LIKE '%$BuscarDato%'
	)";

	// SMM, 23/11/2023
	$Cons = "SELECT * FROM uvw_tbl_CampanaVehiculos WHERE $Filtro";
	
	// echo $Cons;
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

				<?php if (true) { ?>
					<div class="col-sm-4">
						<div class="title-action">
							<a href="campanas_vehiculo.php" class="alkin btn btn-primary"><i class="fa fa-plus-circle"></i>
								Crear Campaña
							</a>
						</div>
					</div>
				<?php } ?>

				<?php // echo $Cons;?>
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

									<!-- Inicio, #Marca -->
									<label class="col-lg-1 control-label">Marca</label>
									
									<div class="col-lg-3">
										<select name="Marca" id="Marca" class="form-control select2">
											<option value="">Seleccione...</option>
											
											<?php while ($row_Marca = sqlsrv_fetch_array($SQL_Marca)) { ?>
												<option value="<?php echo $row_Marca['IdMarcaVehiculo']; ?>" <?php if ((isset($_GET['Marca'])) && ($row_Marca['IdMarcaVehiculo'] == $_GET['Marca'])) {
														echo "selected";
												   	} ?>>
													<?php echo $row_Marca['DeMarcaVehiculo']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<!-- Fin, #Marca -->
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
										<select id="Sucursal" name="Sucursal" class="form-control">
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
									<label class="col-lg-1 control-label">Buscar Dato</label>
									<div class="col-lg-3">
										<input name="BuscarDato" type="text" class="form-control" id="BuscarDato"
											maxlength="100" value="<?php echo $BuscarDato; ?>">
									</div>

									<div class="col-lg-8">
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
												<th>ID Campaña</th>
												<th>Campaña</th>
												<th>Descripción</th>
												<th>Marca</th>
												<th>Estado</th>
												<th>Proveedor</th>
												<th>Sucursal Proveedor</th>
												<th>Fecha Creación</th>
												<th>Fecha Vigencia</th>
											</tr>
										</thead>
										<tbody>
											<?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
												<tr class="gradeX tooltip-demo">
													<td>
														<a href="campanas_vehiculo.php?id=<?php echo $row['id_campana']; ?>&edit=1"
															class="btn btn-success btn-xs" target="_blank">
															<i class="fa fa-folder-open-o"></i>
															<?php echo $row['id_campana']; ?>
														</a>
													</td>
													<td>
														<?php echo $row['campana'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['descripcion_campana'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['marca'] ?? ""; ?>
													</td>
													<td>
														<?php if ($row['estado'] == 'Y') { ?>
															<span class='label label-info'>Activa</span>
														<?php } else { ?>
															<span class='label label-danger'>Inactiva</span>
														<?php } ?>
													</td>
													<td>
														<?php echo $row['socio_negocio'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row['id_direccion_destino'] ?? ""; ?>
													</td>
													<td>
														<?php echo (isset($row["fecha_creacion"]) && $row["fecha_creacion"] != "") ? $row['fecha_creacion']->format("Y-m-d") : ""; ?>
													</td>
													<td>
														<?php echo (isset($row["fecha_limite_vigencia"]) && $row["fecha_limite_vigencia"] != "") ? $row['fecha_limite_vigencia']->format("Y-m-d") : ""; ?>
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

			// SMM, 23/11/2023
			$(".select2").select2();

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
				buttons: [],
				order: [[6, "desc"]]
			});

		});

	</script>
	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>

<?php sqlsrv_close($conexion); ?>