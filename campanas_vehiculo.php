<?php require_once "includes/conexion.php";
PermitirAcceso(338);

$ID = $_GET['id'] ?? ($_POST['id'] ?? "");
$Edit = $_GET['edit'] ?? ($_POST['edit'] ?? 0);
$Active = $_GET['active'] ?? 1; // Tag active -> (1) VINs & (2) Articulos

$Titulo = ($Edit != 0) ? "Editar Campaña de Vehículos" : "Crear Campaña de Vehículos";

$Cons_Encabezado = "SELECT * FROM tbl_CampanaVehiculos WHERE id_campana = '$ID'";
$SQL_Encabezado = sqlsrv_query($conexion, $Cons_Encabezado);
$row_Encabezado = sqlsrv_fetch_array($SQL_Encabezado);

$Campana = $row_Encabezado['campana'] ?? "";
$Comentario = $row_Encabezado['descripcion_campana'] ?? "";
$Estado = $row_Encabezado['estado'] ?? "";
$Tipo = $row_Encabezado['tipo_campana'] ?? "";

$TiempoMeses = number_format(($row_Encabezado['tiempo_campana_meses'] ?? 0), 2);
$ValorCampana = number_format(($row_Encabezado['valor_campana'] ?? 0), 2, ".", "");

// SMM, 24/11/2023
$IdMarca = $row_Encabezado['id_marca'] ?? "";
$SQL_Marca = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_MarcaVehiculo', '*');

$FechaVigencia = isset($row_Encabezado['fecha_limite_vigencia']) ? $row_Encabezado['fecha_limite_vigencia']->format("Y-m-d") : "";

$Proveedor = $row_Encabezado['id_socio_negocio'] ?? "";
$NombreProveedor = $row_Encabezado['socio_negocio'] ?? "";
$Sucursal = $row_Encabezado['id_consecutivo_direccion'] ?? "";

$Cons_Detalle = "SELECT * FROM uvw_tbl_CampanaVehiculosDetalle WHERE id_campana = '$ID'";
$SQL_Detalle = sqlsrv_query($conexion, $Cons_Detalle);

$Cons_Articulos = "SELECT * FROM uvw_tbl_CampanaVehiculosDetalle_Articulos WHERE id_campana = '$ID'";
$SQL_Articulos = sqlsrv_query($conexion, $Cons_Articulos);

if (!$SQL_Encabezado || !$SQL_Detalle || !$SQL_Articulos) {
	echo $Cons_Encabezado;
	echo "<br>$Cons_Detalle";
	echo "<br>$Cons_Articulos";
}

// Validación de campañas aplicadas. SMM, 20/01/2023
$SQL_Aplicado = sqlsrv_query($conexion, $Cons_Detalle);

$CampanasAplicadas = false;
while ($row_Aplicado = sqlsrv_fetch_array($SQL_Aplicado)) {
	if ($row_Aplicado["estado_VIN"] == "A") {
		$CampanasAplicadas = true;
	}
}

// Desde ajx_cbo_select(3)
if ($Proveedor != "") {
	$Parametros = array(
		"'$Proveedor'",
		"'S'",
		// Destino (S) / Facturacion (B)
		"'" . $_SESSION['CodUser'] . "'",
		1,
		// Proveedor (1) / Cliente (0)
	);

	$SQL_Sucursal = EjecutarSP('sp_ConsultarSucursalesClientes', $Parametros);
}

// SMM, 24/08/2023
$msg_error = "";
$parametros = array();

$coduser = $_SESSION['CodUser'];
$datetime = FormatoFecha(date('Y-m-d'), date('H:i:s'));

$type = $_POST['type'] ?? 0;
$id_campana = $_POST['id_campana'] ?? "";
$campana = $_POST['campana'] ?? "";
$descripcion_campana = $_POST['descripcion_campana'] ?? "";
$id_socio_negocio = $_POST['id_socio_negocio'] ?? "";
$socio_negocio = $_POST['socio_negocio'] ?? "";
$id_consecutivo_direccion = isset($_POST['id_consecutivo_direccion']) && ($_POST['id_consecutivo_direccion'] != "") ? $_POST['id_consecutivo_direccion'] : "NULL";
$id_direccion_destino = $_POST['id_direccion_destino'] ?? "";
$direccion_destino = $_POST['direccion_destino'] ?? "";
$tiempo_campana_meses = $_POST['tiempo_campana_meses'] ?? "NULL";
$fecha_limite_vigencia = isset($_POST['fecha_limite_vigencia']) ? FormatoFecha($_POST['fecha_limite_vigencia']) : "";
$id_marca = $_POST['Marca'] ?? "";
$tipo_campana = $_POST['tipo'] ?? "";
$estado_campana = $_POST['estado'] ?? "";
$valor_campana = $_POST['valor_campana'] ?? "NULL";
$id_usuario_creacion = "'$coduser'";
$fecha_creacion = "'$datetime'";
$hora_creacion = "'$datetime'";
$id_usuario_actualizacion = "'$coduser'";
$fecha_actualizacion = "'$datetime'";
$hora_actualizacion = "'$datetime'";

if ($type == 1) {
	$msg_error = "No se pudo crear el registro.";

	$parametros = array(
		$type,
		"'$id_campana'",
		"'$campana'",
		"'$descripcion_campana'",
		"'$id_socio_negocio'",
		"'$socio_negocio'",
		$id_consecutivo_direccion,
		"'$id_direccion_destino'",
		"'$direccion_destino'",
		$tiempo_campana_meses,
		"'$fecha_limite_vigencia'",
		"'$id_marca'",
		"'$tipo_campana'",
		"'$estado_campana'",
		$valor_campana,
		$id_usuario_actualizacion,
		$fecha_actualizacion,
		$hora_actualizacion,
		$id_usuario_creacion,
		$fecha_creacion,
		$hora_creacion,
	);

} elseif ($type == 2) {
	$msg_error = "No se pudo actualizar el registro.";

	$parametros = array(
		$type,
		"'$id_campana'",
		"'$campana'",
		"'$descripcion_campana'",
		"'$id_socio_negocio'",
		"'$socio_negocio'",
		$id_consecutivo_direccion,
		"'$id_direccion_destino'",
		"'$direccion_destino'",
		$tiempo_campana_meses,
		"'$fecha_limite_vigencia'",
		"'$id_marca'",
		"'$tipo_campana'",
		"'$estado_campana'",
		$valor_campana,
		$id_usuario_actualizacion,
		$fecha_actualizacion,
		$hora_actualizacion,
	);

} elseif ($type == 3) {
	$msg_error = "No se pudo eliminar el registro.";

	$parametros = array(
		$type,
		"'$id_zona_sn'",
	);
}

$sw_OK = 0;
$sw_error = 0;
if ($type != 0) {
	$SQL_Operacion = EjecutarSP('sp_tbl_CampanaVehiculos', $parametros);

	if (!$SQL_Operacion) {
		$sw_error = 1;
	} else {
		$row = sqlsrv_fetch_array($SQL_Operacion);

		if (isset($row['Error']) && ($row['Error'] != "")) {
			$sw_error = 1;

			$msg_error .= " (" . $row['Error'] . ")";
		} else {
			$SQL_Corregir = EjecutarSP('sp_tbl_CampanaVehiculos', [4]); // @type = 4 -- Corregir

			if (!$SQL_Corregir) {
				$sw_error = 1;
			} else {
				$sw_OK = 1;
			}
		}
	}
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

	<script>
		$(document).ready(function () {
			<?php if ($sw_error == 1) { ?>
				Swal.fire({
					title: '¡Ha ocurrido un error!',
					text: '<?php echo LSiqmlObs($msg_error); ?>',
					icon: 'warning'
				});
			<?php } ?>

			<?php if ($sw_OK == 1) { ?>
				Swal.fire({
					title: '¡Listo!',
					text: 'La <?php echo ($Edit == 0) ? "creación" : "actualización"; ?> se realizo exitosamente.',
					icon: 'success'
				}).then((result) => {
					if (result.isConfirmed) {
						location.href = "campanas_vehiculo.php?id=<?php echo $id_campana; ?>&edit=1";
					}
				});
			<?php } ?>
		}); // SMM, 24/08/2023
	</script>

	<!-- InstanceBeginEditable name="head" -->
	<script type="text/javascript">
		$(document).ready(function () {
			$("#socio_negocio").change(function () {
				if ($("#socio_negocio").val() == "") {
					$("#id_socio_negocio").val("");
					$("#id_socio_negocio").trigger("change");
				}
			});

			$("#id_socio_negocio").change(function () {
				$('.ibox-content').toggleClass('sk-loading', true);

				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=3&pv=1&sucline=1&tdir=S&id=" + $("#id_socio_negocio").val(),
					success: function (response) {
						$('#id_consecutivo_direccion').html(response).fadeIn();
						$('#id_consecutivo_direccion').trigger('change');

						$('.ibox-content').toggleClass('sk-loading', false);
					},
					error: function (error) {
						console.log("Line 180", error.responseText);

						$('.ibox-content').toggleClass('sk-loading', false);
					}
				});
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
				<div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
					<div class="modal-dialog modal-lg" style="width: 70% !important;">
						<div class="modal-content" id="ContenidoModal">
							<!-- Contenido generado por JS -->
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<?php include "includes/spinner.php"; ?>

							<form action="campanas_vehiculo.php" method="post" id="formEncabezado"
								class="form-horizontal">
								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm">
											<i class="fa fa-info-circle"></i> Información de la Campaña
										</h3>
									</label>
								</div>

								<input type="hidden" id="id" name="id" value="<?php echo $ID; ?>">
								<input type="hidden" id="edit" name="edit" value="<?php echo $Edit; ?>">
								<input type="hidden" id="type" name="type" value="<?php echo ($Edit == 0) ? 1 : 2; ?>">

								<div class="form-group">
									<label class="col-lg-1 control-label">
										ID Campaña <span class="text-danger">*</span>
									</label>
									<div class="col-lg-3">
										<input name="id_campana" type="text" class="form-control" id="id_campana"
											maxlength="100" value="<?php echo $ID; ?>" <?php if ($Edit == 1) {
												   echo "readonly";
											   } ?>>
									</div>

									<label class="col-lg-1 control-label">
										Nombre Campaña <span class="text-danger">*</span>
									</label>
									<div class="col-lg-3">
										<input name="campana" type="text" class="form-control" id="campana"
											maxlength="100" value="<?php echo $Campana; ?>" <?php if ($CampanasAplicadas == true) {
												   echo "readonly";
											   } ?>>
									</div>

									<label class="col-lg-1 control-label">
										Estado Campaña <span class="text-danger">*</span>
									</label>
									<div class="col-lg-3">
										<select name="estado" class="form-control" id="estado" required>
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
									<label class="col-lg-1 control-label">Fecha Límite Vigente <span
											class="text-danger">*</span></label>
									<div class="col-lg-3 input-group date">
										<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
											required autocomplete="off" name="fecha_limite_vigencia"
											id="fecha_limite_vigencia" type="text" class="form-control fecha"
											placeholder="AAAA-MM-DD" value="<?php echo $FechaVigencia; ?>" <?php if ($CampanasAplicadas == true) {
												   echo "readonly";
											   } ?>>
									</div>

									<label class="col-lg-1 control-label">Proveedor <span
											class="text-danger">*</span></label>
									<div class="col-lg-3">
										<input name="id_socio_negocio" type="hidden" id="id_socio_negocio"
											value="<?php echo $Proveedor; ?>">
										<input name="socio_negocio" type="text" class="form-control" required
											id="socio_negocio" placeholder="Para TODOS, dejar vacio..."
											value="<?php echo $NombreProveedor; ?>" <?php if ($CampanasAplicadas == true) {
												   echo "readonly";
											   } ?>>
									</div>

									<label class="col-lg-1 control-label">Sucursal Proveedor <span
											class="text-danger">*</span></label>
									<div class="col-lg-3">
										<select id="id_consecutivo_direccion" name="id_consecutivo_direccion"
											class="form-control" required <?php if ($CampanasAplicadas == true) {
												echo "readonly";
											} ?>>
											<option value="" <?php if ($Sucursal != "") {
												echo "disabled";
											} ?>>Seleccione...</option>

											<?php if ($Proveedor != "") { ?>
												<?php while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) { ?>
													<option value="<?php echo $row_Sucursal['NumeroLinea']; ?>" <?php if (strcmp($row_Sucursal['NumeroLinea'], $Sucursal) == 0) {
														   echo "selected";
													   } elseif ($CampanasAplicadas == true) {
														   echo "disabled";
													   } ?>>
														<?php echo $row_Sucursal['NombreSucursal']; ?>
													</option>
												<?php } ?>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="form-group">
									<!-- Inicio, #Marca -->
									<label class="col-lg-1 control-label">Marca</label>
									
									<div class="col-lg-3">
										<select name="Marca" id="Marca" class="form-control select2">
											<option value="">Seleccione...</option>
											
											<?php while ($row_Marca = sqlsrv_fetch_array($SQL_Marca)) { ?>
												<option value="<?php echo $row_Marca['IdMarcaVehiculo']; ?>" <?php if ($row_Marca['IdMarcaVehiculo'] == $IdMarca) {
														echo "selected";
												   	} ?>>
													<?php echo $row_Marca['DeMarcaVehiculo']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<!-- Fin, #Marca -->

									<label class="col-lg-1 control-label">
										Tiempo meses <span class="text-danger">*</span>
									</label>
									<div class="col-lg-3">
										<input name="tiempo_campana_meses" type="number" class="form-control"
											id="tiempo_campana_meses" value="<?php echo $TiempoMeses; ?>" required <?php if ($CampanasAplicadas == true) {
												echo "readonly";
											} ?>>
									</div>
									
									<label class="col-lg-1 control-label">
										Tipo Campaña
									</label>
									<div class="col-lg-3">
										<select name="tipo" class="form-control" id="tipo">
											<option value="">Seleccione...</option>

											<option value="1" <?php if ($Tipo == "1") {
												echo "selected";
											} ?>>Garantía</option>
											<option value="2" <?php if ($Tipo == "2") {
												echo "selected";
											} ?>>Comercial</option>
										</select>
									</div>
								</div>
								<!-- /.form-group -->

								<div class="form-group">
									<label class="col-lg-1 control-label">
										Comentario <span class="text-danger">*</span>
									</label>
									<div class="col-lg-7">
										<textarea name="descripcion_campana" rows="3" maxlength="3000"
											class="form-control" required id="descripcion_campana"
											type="text"><?php echo $Comentario; ?></textarea>
									</div>

									<label class="col-lg-1 control-label">
										Valor Campaña <span class="text-danger">*</span>
									</label>
									<div class="col-lg-3">
										<input name="valor_campana" type="number" class="form-control"
											id="valor_campana" value="<?php echo $ValorCampana; ?>" required <?php if ($CampanasAplicadas == true) {
												echo "readonly";
											} ?>>
									</div>
								</div>
								<!-- /.form-group -->

								<div class="form-group">
									<div class="col-lg-12">
										<div class="btn-group pull-right">
											<button type="submit" class="btn btn-outline btn-primary">
												<i
													class="fa <?php echo ($Edit == 0) ? "fa-plus" : "fa-refresh"; ?>"></i>
												<?php echo ($Edit == 0) ? "Crear Campaña" : "Actualizar Campaña"; ?>
											</button>
										</div>
									</div>
								</div>
								<!-- /.form-group -->

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
								<div class="col-lg-12">
									<div class="btn-group pull-right">
										<button type="button" class="btn btn-outline btn-primary" style="margin-left: 10px;"
											<?php if ($Edit == 0) {
												echo "disabled";
											} ?> onclick="CrearRegistro();">
											<i class="fa fa-plus"></i> Adicionar VIN
										</button>

										<button type="button" class="btn btn-outline btn-info" style="margin-left: 10px;"
											<?php if ($Edit == 0) {
												echo "disabled";
											} ?> onclick="AgregarArticulos();">
											<i class="fa fa-shopping-cart"></i> Adicionar Articulo
										</button>
									</div>
								</div>
								<!-- /.col-lg-12 > .btn-group -->

								<div class="tabs-container">
									<ul class="nav nav-tabs">
										<li class="<?php if ($Active == 1) {
											echo "active";
										} ?>">
											<a data-toggle="tab" href="#tab-1"><i class="fa fa-table"></i>Lista de VINs</a>
										</li>
										<li class="<?php if ($Active == 2) {
											echo "active";
										} ?>">
											<a data-toggle="tab" href="#tab-2"><i class="fa fa-table"></i> Lista de
												Articulos</a>
										</li>
									</ul>
									<!-- /.nav-tabs -->

									<div class="tab-content">

										<div id="tab-1" class="tab-pane <?php if ($Active == 1) {
											echo "active";
										} ?>">
											<div class="row">
												<div class="ibox-content">
													<div class="table-responsive">
														<table
															class="table table-striped table-bordered table-hover dataTables-example"
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
																			<span
																				class="label <?php echo ($row_Detalle['estado_VIN'] == "P") ? "label-warning" : "label-info"; ?>">
																				<?php echo ($row_Detalle['estado_VIN'] == "P") ? "Pendiente" : "Aplicado"; ?>
																			</span>
																		</td>
																		<td class="text-left">
																			<?php if (isset($row_Detalle['docnum_llamada_servicio']) && ($row_Detalle['docnum_llamada_servicio'] != "")) { ?>
																				<a href="llamada_servicio.php?id=<?php echo base64_encode($row_Detalle['docentry_llamada_servicio']); ?>&tl=1&pag=<?php echo base64_encode('gestionar_llamadas_servicios.php'); ?>"
																					class="alkin btn btn-success btn-xs">
																					<i class="fa fa-folder-open-o"></i>
																					<?php echo $row_Detalle['docnum_llamada_servicio']; ?>
																				</a>
																			<?php } ?>
																		</td>

																		<td>
																			<?php echo $row_Detalle['DeOrigenLlamada'] ?? ""; ?>
																		</td>
																		<td>
																			<?php echo $row_Detalle['DeEstadoLlamada'] ?? ""; ?>
																		</td>
																		<td>
																			<?php echo $row_Detalle['NombreClienteLlamada'] ?? ""; ?>
																		</td>
																		<td>
																			<?php echo (isset($row_Detalle["FechaCierreLlamada"]) && $row_Detalle["FechaCierreLlamada"] != "") ? $row_Detalle['FechaCierreLlamada']->format("Y-m-d") : ""; ?>
																		</td>

																		<td>
																			<?php if ($row_Detalle['estado_VIN'] == "P") { ?>
																				<button type="button"
																					id="btnDelete<?php echo $row_Detalle['id_campana_detalle']; ?>"
																					class="btn btn-danger btn-xs"
																					onclick="EliminarRegistro('<?php echo $row_Detalle['id_campana_detalle']; ?>');"><i
																						class="fa fa-trash"></i>
																					Eliminar</button>
																			<?php } ?>
																		</td>
																	</tr>
																<?php } ?>
															</tbody>
														</table>
													</div>
													<!-- /.table-responsive -->
												</div>
												<!-- /.ibox-content -->
											</div>
											<!-- /.row -->
										</div>
										<!-- /#tab-1 -->

										<div id="tab-2" class="tab-pane <?php if ($Active == 2) {
											echo "active";
										} ?>">
											<div class="row">
												<div class="ibox-content">
													<div class="table-responsive">
														<table
															class="table table-striped table-bordered table-hover dataTables-example"
															id="example">
															<thead>
																<tr>
																	<th>ID</th>
																	<th>VIN</th>
																	<th>Estado VIN</th>
																	<th>ID Articulo</th>
																	<th>Articulo</th>
																	<th>Acciones</th>
																</tr>
															</thead>
															<tbody>
																<?php while ($row_Articulos = sqlsrv_fetch_array($SQL_Articulos)) { ?>
																	<tr class="gradeX tooltip-demo">
																		<td>
																			<?php echo $row_Articulos['id_campana'] . "-" . $row_Articulos['id_campana_detalle_articulo']; ?>
																		</td>
																		<td>
																			<?php echo $row_Articulos['VIN']; ?>
																		</td>
																		<td>
																			<span
																				class="label <?php echo ($row_Articulos['estado_VIN'] == "P") ? "label-warning" : "label-info"; ?>">
																				<?php echo ($row_Articulos['estado_VIN'] == "P") ? "Pendiente" : "Aplicado"; ?>
																			</span>
																		</td>
																		<td>
																			<button type="button" class="btn btn-success btn-xs"
																				onClick="ConsultarArticulo('<?php echo base64_encode($row_Articulos['id_articulo']); ?>');"
																				title="Consultar Articulo">
																				<i class="fa fa-search"></i>
																				<?php echo $row_Articulos['id_articulo']; ?>
																			</button>
																		</td>
																		<td>
																			<?php echo $row_Articulos['articulo']; ?>
																		</td>
																		<td>
																			<button type="button"
																				id="btnDelete<?php echo $row_Articulos['id_campana_detalle_articulo']; ?>"
																				class="btn btn-danger btn-xs"
																				onclick="EliminarArticulos('<?php echo $row_Articulos['id_campana_detalle_articulo']; ?>');">
																				<i class="fa fa-trash"></i> Eliminar
																			</button>
																		</td>
																	</tr>
																<?php } ?>
															</tbody>
														</table>
													</div>
													<!-- /.table-responsive -->
												</div>
												<!-- /.ibox-content -->
											</div>
											<!-- /.row -->
										</div>
										<!-- /#tab-2 -->

									</div>
									<!-- /.tab-content-->
								</div>
								<!-- /.tabs-container -->

							</div>
							<!-- /.ibox-content -->
						</div>
						<!-- /.col-lg-12 -->
					</div>
					<!-- /.row -->
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

			$("#formEncabezado").validate({
				submitHandler: function (form) {
					if ($("#tiempo_campana_meses").val() > 0) {
						form.submit();
					} else {
						alert("La cantidad de meses de la campaña debe ser mayor a cero.");
					}
				}
			});

			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});

			// SMM, 28/09/2023
			<?php if ($CampanasAplicadas == false) { ?>
				$('.fecha').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					calendarWeeks: true,
					autoclose: true,
					todayHighlight: true,
					format: 'yyyy-mm-dd'
				});
			<?php } ?>

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
						var value = $("#socio_negocio").getSelectedItemData().CodigoCliente;
						$("#id_socio_negocio").val(value).trigger("change");
					},
					onKeyEnterEvent: function () {
						var value = $("#socio_negocio").getSelectedItemData().CodigoCliente;
						$("#id_socio_negocio").val(value).trigger("change");
					}
				}
			};

			$("#socio_negocio").easyAutocomplete(options);

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
				order: [[1, "desc"]]
			});
		});
	</script>

	<script>
		function CrearRegistro() {
			if ($("#estado").val() == "Y") {
				$('.ibox-content').toggleClass('sk-loading', true);

				$.ajax({
					type: "POST",
					url: "md_campanas_vehiculo.php",
					success: function (response) {
						$('.ibox-content').toggleClass('sk-loading', false);

						$('#ContenidoModal').html(response);
						$('#myModal').modal("show");
					}
				});
			} else {
				Swal.fire({
					title: "¡Ha ocurrido un error!",
					text: "No puede insertar VIN's en una Campaña Inactiva.",
					icon: "warning"
				});
			}
		}

		function AgregarArticulos() {
			if ($("#estado").val() == "Y") {
				$('.ibox-content').toggleClass('sk-loading', true);

				$.ajax({
					type: "POST",
					data: {
						id_campana: $("#id_campana").val()
					},
					url: "md_campanas_articulos.php",
					success: function (response) {
						$('.ibox-content').toggleClass('sk-loading', false);

						$('#ContenidoModal').html(response);
						$('#myModal').modal("show");
					}
				});
			} else {
				Swal.fire({
					title: "¡Ha ocurrido un error!",
					text: "No puede insertar Articulos en una Campaña Inactiva.",
					icon: "warning"
				});
			}
		}

		function EliminarRegistro(id) {
			Swal.fire({
				title: "¿Está seguro que desea eliminar este registro?",
				icon: "question",
				showCancelButton: true,
				confirmButtonText: "Si, confirmo",
				cancelButtonText: "No"
			}).then((result) => {
				if (result.isConfirmed) {
					$.ajax({
						type: "POST",
						url: "md_campanas_vehiculo.php",
						data: {
							type: 3,
							ID: $("#id_campana").val(),
							id_campana_detalle: id,
						},
						success: function (response) {
							Swal.fire({
								icon: (response == "OK") ? "success" : "warning'",
								title: (response == "OK") ? "¡Listo!" : "¡Error!",
								text: (response == "OK") ? "Se elimino el VIN correctamente." : response
							}).then((result) => {
								if (result.isConfirmed) {
									// Obtén la URL actual
									let currentUrl = new URL(window.location.href);

									// Obtén los parámetros del query string
									let searchParams = currentUrl.searchParams;

									// Actualiza el valor del parámetro 'active' o agrega si no existe
									searchParams.set('active', 1);

									// Crea una nueva URL con los parámetros actualizados
									let newUrl = currentUrl.origin + currentUrl.pathname + '?' + searchParams.toString();

									// Recarga la página con la nueva URL
									window.location.href = newUrl;
								}
							});
						},
						error: function (error) {
							console.error("640->", error.responseText);
						}
					});
					// $.ajax
				}
			});
		}

		function EliminarArticulos(id) {
			Swal.fire({
				title: "¿Está seguro que desea eliminar articulo?",
				icon: "question",
				showCancelButton: true,
				confirmButtonText: "Si, confirmo",
				cancelButtonText: "No"
			}).then((result) => {
				if (result.isConfirmed) {
					$.ajax({
						type: "POST",
						url: "md_campanas_articulos.php",
						data: {
							type: 3,
							ID: $("#id_campana").val(),
							id_campana_articulo: id,
						},
						success: function (response) {
							Swal.fire({
								icon: (response == "OK") ? "success" : "warning'",
								title: (response == "OK") ? "¡Listo!" : "¡Error!",
								text: (response == "OK") ? "Se elimino el articulo correctamente." : response
							}).then((result) => {
								if (result.isConfirmed) {
									// Obtén la URL actual
									let currentUrl = new URL(window.location.href);

									// Obtén los parámetros del query string
									let searchParams = currentUrl.searchParams;

									// Actualiza el valor del parámetro 'active' o agrega si no existe
									searchParams.set('active', 2);

									// Crea una nueva URL con los parámetros actualizados
									let newUrl = currentUrl.origin + currentUrl.pathname + '?' + searchParams.toString();

									// Recarga la página con la nueva URL
									window.location.href = newUrl;
								}
							});
						},
						error: function (error) {
							console.error("800->", error.responseText);
						}
					});
					// $.ajax
				}
			});
		}

		// SMM, 30/08/2023
		function ConsultarArticulo(articulo) {
			if (articulo != "") {
				self.name = 'opener';
				remote = open(`articulos.php?id=${articulo}&ext=1&tl=1`, 'remote', 'location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
				remote.focus();
			}
		}
	</script>
	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>

<?php sqlsrv_close($conexion); ?>