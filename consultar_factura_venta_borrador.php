<?php require_once "includes/conexion.php";
PermitirAcceso(423);
$sw = 0;

// Cerrar documento en borrador. SMM, 22/12/2023
$CerrarBorrador = ($_POST["Cerrar"] ?? "");
if ($CerrarBorrador != "") {
	try {
		$IdBorrador = ($_POST["IdBorrador"] ?? "");

		$Parametros = array(
			"id_documento_borrador" => $IdBorrador
		);

		$Metodo = "FacturasVentas/CerrarBorrador/$IdBorrador";
		$Resultado = EnviarWebServiceSAP($Metodo, $Parametros, true, true);

		if ($Resultado->Success == 0) {
			echo "No se pudo cerrar el documento. \n";
			echo $Resultado->Mensaje;
		} else {
			echo "OK";
		}

		// Mostrar mensaje de la API.
		exit();
	} catch (Exception $e) {
		echo "Excepción capturada: " . $e->getMessage() . "\n";

		// Mostrar excepción.
		exit();
	}
}

//Estado actividad
$SQL_Estado = Seleccionar('uvw_tbl_EstadoDocSAP', '*');

//Series de documento
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'13'",
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

//Estado autorizacion
$SQL_EstadoAuth = Seleccionar('uvw_Sap_tbl_EstadosAuth', '*');

$Filtro = ""; //Filtro

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
} else {
	$FechaFinal = date('Y-m-d');
}
$WhereFecha = "(DocDate Between '$FechaInicial' and '$FechaFinal')";

if (isset($_GET['FechaVenc']) && $_GET['FechaVenc'] != "") {
	$WhereFecha = "DocDueDate='" . $_GET['FechaVenc'] . "'";
}

//Filtros
if (isset($_GET['Estado']) && $_GET['Estado'] != "") {
	$Filtro .= " and Cod_Estado='" . $_GET['Estado'] . "'";
}

if (isset($_GET['Autorizacion']) && $_GET['Autorizacion'] != "") {
	$Filtro .= " and AuthPortal='" . $_GET['Autorizacion'] . "'";
}

if (isset($_GET['Cliente']) && $_GET['Cliente'] != "") {
	$Filtro .= " and CardCode='" . $_GET['Cliente'] . "'";
}

if (isset($_GET['Series']) && $_GET['Series'] != "") {
	$Filtro .= " and [IdSeries]='" . $_GET['Series'] . "'";
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
	$Filtro .= " and [IdSeries] IN (" . $FilSerie . ")";
	$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);
}

if (isset($_GET['BuscarDato']) && $_GET['BuscarDato'] != "") {
	$Filtro .= " and (DocNum LIKE '%" . $_GET['BuscarDato'] . "%' OR NombreContacto LIKE '%" . $_GET['BuscarDato'] . "%' OR DocNumLlamadaServicio LIKE '%" . $_GET['BuscarDato'] . "%' OR ID_LlamadaServicio LIKE '%" . $_GET['BuscarDato'] . "%' OR IdDocPortal LIKE '%" . $_GET['BuscarDato'] . "%' OR NombreEmpleadoVentas LIKE '%" . $_GET['BuscarDato'] . "%' OR Comentarios LIKE '%" . $_GET['BuscarDato'] . "%')";
}

$Cons = "Select * From uvw_Sap_tbl_FacturasVentas_Borrador Where $WhereFecha $Filtro Order by DocNum DESC";

if (isset($_GET['IDTicket']) && $_GET['IDTicket'] != "") {
	$Where = "DocNumLlamadaServicio LIKE '%" . $_GET['IDTicket'] . "%'";

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
	$Where .= " and [IdSeries] IN (" . $FilSerie . ")";
	$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

	$Cons = "Select * From uvw_Sap_tbl_FacturasVentas_Borrador Where $Where";
}

// SMM, 22/07/2022
if (isset($_GET['DocNum']) && $_GET['DocNum'] != "") {
	$Where = "DocNum LIKE '%" . trim($_GET['DocNum']) . "%'";

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
	$Where .= " and [IdSeries] IN (" . $FilSerie . ")";
	$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

	$Cons = "Select * From uvw_Sap_tbl_FacturasVentas_Borrador Where $Where";
}

$SQL = sqlsrv_query($conexion, $Cons);
//echo $Cons;
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include_once "includes/cabecera.php"; ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Consultar factura de venta borrador |
		<?php echo NOMBRE_PORTAL; ?>
	</title>
	<!-- InstanceEndEditable -->
	<!-- InstanceBeginEditable name="head" -->
	<?php
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_FactVentAdd"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La Factura de venta ha sido creada exitosamente.',
                icon: 'success'
            });
		});
		</script>";
	}
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_FactVentUpd"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La Factura de venta ha sido actualizada exitosamente.',
                icon: 'success'
            });
		});
		</script>";
	}
	?>
	<script type="text/javascript">
		$(document).ready(function () {
			$("#NombreCliente").change(function () {
				var NomCliente = document.getElementById("NombreCliente");
				var Cliente = document.getElementById("Cliente");
				if (NomCliente.value == "") {
					Cliente.value = "";
				}
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
					<h2>Consultar factura de venta borrador</h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">Ventas - Clientes</a>
						</li>
						<li>
							<a href="#">Consultas</a>
						</li>
						<li class="active">
							<strong>Consultar factura de venta borrador</strong>
						</li>
					</ol>
				</div>
			</div>
			<div class="wrapper wrapper-content">
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<?php include "includes/spinner.php"; ?>
							<form action="consultar_factura_venta_borrador.php" method="get" id="formBuscar"
								class="form-horizontal">
								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para
											filtrar</h3>
									</label>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Fechas</label>
									<div class="col-lg-3">
										<div class="input-daterange input-group" id="datepicker">
											<input name="FechaInicial" type="text" autocomplete="off"
												class="input-sm form-control" id="FechaInicial"
												placeholder="Fecha inicial" value="<?php echo $FechaInicial; ?>" />
											<span class="input-group-addon">hasta</span>
											<input name="FechaFinal" type="text" autocomplete="off"
												class="input-sm form-control" id="FechaFinal" placeholder="Fecha final"
												value="<?php echo $FechaFinal; ?>" />
										</div>
									</div>
									<label class="col-lg-1 control-label">Estado</label>
									<div class="col-lg-3">
										<select name="Estado" class="form-control" id="Estado">
											<option value="">(Todos)</option>
											<?php while ($row_Estado = sqlsrv_fetch_array($SQL_Estado)) { ?>
												<option value="<?php echo $row_Estado['Cod_Estado']; ?>" <?php if ((isset($_GET['Estado'])) && (strcmp($row_Estado['Cod_Estado'], $_GET['Estado']) == 0)) {
													   echo "selected=\"selected\"";
												   } ?>>
													<?php echo $row_Estado['NombreEstado']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
									<label class="col-lg-1 control-label">Serie</label>
									<div class="col-lg-3">
										<select name="Series" class="form-control" id="Series">
											<option value="">(Todos)</option>
											<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
												<option value="<?php echo $row_Series['IdSeries']; ?>" <?php if ((isset($_GET['Series'])) && (strcmp($row_Series['IdSeries'], $_GET['Series']) == 0)) {
													   echo "selected=\"selected\"";
												   } ?>>
													<?php echo $row_Series['DeSeries']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Cliente</label>
									<div class="col-lg-3">
										<input name="Cliente" type="hidden" id="Cliente"
											value="<?php if (isset($_GET['Cliente']) && ($_GET['Cliente'] != "")) {
												echo $_GET['Cliente'];
											} ?>">
										<input name="NombreCliente" type="text" class="form-control" id="NombreCliente"
											placeholder="Para TODOS, dejar vacio..."
											value="<?php if (isset($_GET['NombreCliente']) && ($_GET['NombreCliente'] != "")) {
												echo $_GET['NombreCliente'];
											} ?>">
									</div>
									<label class="col-lg-1 control-label">Buscar dato</label>
									<div class="col-lg-3">
										<input name="BuscarDato" type="text" class="form-control" id="BuscarDato"
											maxlength="100"
											value="<?php if (isset($_GET['BuscarDato']) && ($_GET['BuscarDato'] != "")) {
												echo $_GET['BuscarDato'];
											} ?>">
									</div>
									<label class="col-lg-1 control-label">Autorización</label>
									<div class="col-lg-3">
										<select name="Autorizacion" class="form-control" id="Autorizacion">
											<option value="">(Todos)</option>
											<?php while ($row_EstadoAuth = sqlsrv_fetch_array($SQL_EstadoAuth)) { ?>
												<option value="<?php echo $row_EstadoAuth['IdAuth']; ?>" <?php if (isset($_GET['Autorizacion']) && (strcmp($row_EstadoAuth['IdAuth'], $_GET['Autorizacion']) == 0)) {
													   echo "selected=\"selected\"";
												   } ?>>
													<?php echo $row_EstadoAuth['DeAuth']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-lg-1 control-label">Orden servicio</label>
									<div class="col-lg-3">
										<input name="IDTicket" type="text" class="form-control" id="IDTicket"
											maxlength="50"
											placeholder="Digite un número completo, o una parte del mismo..."
											value="<?php if (isset($_GET['IDTicket']) && ($_GET['IDTicket'] != "")) {
												echo $_GET['IDTicket'];
											} ?>">
									</div>

									<!-- Número de documento -->
									<label class="col-lg-1 control-label">Número documento</label>
									<div class="col-lg-3">
										<input name="DocNum" type="text" class="form-control" id="DocNum" maxlength="50"
											placeholder="Digite un número completo, o una parte del mismo..."
											value="<?php if (isset($_GET['DocNum']) && ($_GET['DocNum'] != "")) {
												echo $_GET['DocNum'];
											} ?>">
									</div>
									<!-- SMM, 22/07/2022 -->

									<label class="col-lg-1 control-label">Fecha venc. servicio</label>
									<div class="col-lg-3 input-group date">
										<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input
											name="FechaVenc" type="text" class="form-control" id="FechaVenc"
											value="<?php if (isset($_GET['FechaVenc']) && ($_GET['FechaVenc'] != "")) {
												echo $_GET['FechaVenc'];
											} ?>"
											readonly="readonly" placeholder="YYYY-MM-DD">
									</div>
								</div>

								<div class="form-group">
									<div class="col-lg-12">
										<button type="submit" class="btn btn-outline btn-success pull-right"><i
												class="fa fa-search"></i> Buscar</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
				<br>
				<?php //echo $Cons;?>
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<?php include "includes/spinner.php"; ?>
							<div class="table-responsive">
								<table class="table table-striped table-bordered table-hover dataTables-example">
									<thead>
										<tr>
											<th>
												ID Borrador
											</th> <!-- SMM, 23/11/2023 -->

											<th>Número</th>
											<th>Serie</th>
											<th>Fecha factura</th>
											<th>Socio de negocio</th>
											<th>Comentarios</th>
											<th>Empleado de venta</th>
											<th>Autorización</th>
											<th>Usuario Autoriza</th>
											<th>Orden servicio</th>
											<th>Documento destino</th>
											<th>Usuario creación</th>
											<th>Estado</th>
											<th>Acciones</th>
										</tr>
									</thead>
									<tbody>
										<?php if ($sw == 1) { ?>
											<?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
												<tr class="gradeX">
													<td>
														<?php echo $row['ID_FacturaVenta']; ?>
													</td> <!-- SMM, 23/11/2023 -->

													<td>
														<?php echo $row['DocNum']; ?>
													</td>
													<td>
														<?php echo $row['DeSeries']; ?>
													</td>
													<td>
														<?php echo $row['DocDate']; ?>
													</td>
													<td>
														<?php echo $row['NombreCliente']; ?>
													</td>
													<td>
														<?php echo $row['Comentarios']; ?>
													</td>
													<td>
														<?php echo $row['NombreEmpleadoVentas']; ?>
													</td>
													<td>
														<?php echo $row['DeAuthPortal']; ?>
													</td>
													<td>
														<?php echo $row['UsuarioAutoriza']; ?>
													</td>
													<td>
														<?php if ($row['ID_LlamadaServicio'] != 0) { ?><a
																href="llamada_servicio.php?id=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('consultar_factura_venta_borrador.php'); ?>&tl=1"
																target="_blank">
																<?php echo $row['DocNumLlamadaServicio']; ?>
															</a>
														<?php } else {
															echo "--";
														} ?>
													</td>
													<td>
														<?php if ($row['DocDestinoDocEntry'] != "") { ?><a
																href="entrega_venta.php?id=<?php echo base64_encode($row['DocDestinoDocEntry']); ?>&id_portal=<?php echo base64_encode($row['DocDestinoIdPortal']); ?>&tl=1"
																target="_blank">
																<?php echo $row['DocDestinoDocNum']; ?>
															</a>
														<?php } else {
															echo "--";
														} ?>
													</td>
													<td>
														<?php echo $row['UsuarioCreacion']; ?>
													</td>
													<td><span <?php if ($row['Cod_Estado'] == 'O') {
														echo "class='label label-info'";
													} else {
														echo "class='label label-danger'";
													} ?>>
															<?php echo $row['NombreEstado']; ?>
														</span></td>
													<td>
														<a href="factura_venta_borrador.php?id=<?php echo base64_encode($row['ID_FacturaVenta']); ?>&id_portal=<?php echo base64_encode($row['IdDocPortal']); ?>&tl=1&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('consultar_factura_venta_borrador.php'); ?>"
															class="alkin btn btn-success btn-xs"><i
																class="fa fa-folder-open-o"></i> Abrir</a>

														<!-- SMM, 23/01/2024 -->
														<div class="btn-group">
															<button data-toggle="dropdown"
																class="btn btn-outline btn-primary btn-xs dropdown-toggle">
																<i class="fa fa-download"></i> Descargar formato <i class="fa fa-caret-down"></i>
															</button>
															<ul class="dropdown-menu">
																<?php $SQL_Formato = Seleccionar('uvw_tbl_FormatosSAP', '*', "ID_Objeto=13 AND (IdFormato='" . $row['IdSeries'] . "' OR DeSeries IS NULL) AND VerEnDocumento='Y' AND EsBorrador='Y'"); ?>
																<?php while ($row_Formato = sqlsrv_fetch_array($SQL_Formato)) { ?>
																	<li>
																		<a class="dropdown-item" target="_blank"
																			href="formatdownload.php?DocKey=<?php echo $row['ID_FacturaVenta'] ?? ""; ?>&ObType=<?php echo $row_Formato['ID_Objeto'] ?? ""; ?>&IdFrm=<?php echo $row_Formato['IdFormato'] ?? ""; ?>&IdReg=<?php echo $row_Formato['ID'] ?? ""; ?>">
																			<?php echo $row_Formato['NombreVisualizar'] ?? ""; ?>
																		</a>
																	</li>
																<?php } ?>
															</ul>
														</div>
														<!-- Hasta aquí, 23/01/2024 -->

														<button class="btn btn-danger btn-xs" style="margin-top: 5px;"
														title="Cerrar documento" onclick="CerrarDocumento('<?php echo $row['ID_FacturaVenta']; ?>')">
															<i class="fa fa-close"></i> Cerrar documento borrador
														</button>

														<?php if ($row['URLVisorPublico'] != "") { ?>
															<a href="<?php echo $row['URLVisorPublico']; ?>" target="_blank"
															class="btn btn-primary btn-xs" title="Ver factura eléctronica">
																<i class="fa fa-external-link"></i> Fact. Elect
															</a>
														<?php } ?>
													</td>
												</tr>
											<?php } ?>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- InstanceEndEditable -->
			<?php include_once "includes/footer.php"; ?>

		</div>
	</div>
	
	<?php include_once "includes/pie.php"; ?>
	<!-- InstanceBeginEditable name="EditRegion4" -->
	
	<script>
		$(document).ready(function () {
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
				todayHighlight: true,
			});
			$('#FechaFinal').datepicker({
				todayBtn: "linked",
				keyboardNavigation: false,
				forceParse: false,
				calendarWeeks: true,
				autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true,
			});
			$('#FechaVenc').datepicker({
				todayBtn: "linked",
				keyboardNavigation: false,
				forceParse: false,
				calendarWeeks: true,
				autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true,
			});

			$('.chosen-select').chosen({ width: "100%" });

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
						$("#Cliente").val(value);
					}
				}
			};
			$("#NombreCliente").easyAutocomplete(options);

			$('.dataTables-example').DataTable({
				pageLength: 25,
				responsive: false,
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
				order: [[ 0, "desc" ]]
			});
		});

		// SMM, 22/12/2023
		function CerrarDocumento(id) {
			Swal.fire({
				title: "¿Está seguro que desea cerrar el documento?",
				icon: "question",
				showCancelButton: true,
				confirmButtonText: "Si, confirmo",
				cancelButtonText: "No"
			}).then((result) => {
				$('.ibox-content').toggleClass('sk-loading', true);

				if (result.isConfirmed) {
					$.ajax({
						type: "POST",
						url: "consultar_factura_venta_borrador.php",
						data: {
							Cerrar: "CerrarBorrador",
							IdBorrador: id,
						},
						success: function (response) {
							Swal.fire({
								icon: (response == "OK") ? "success" : "warning",
								title: (response == "OK") ? "¡Listo!" : "¡Error!",
								text: (response == "OK") ? "Se cerro el documento correctamente." : response
							}).then((result) => {
								$('.ibox-content').toggleClass('sk-loading', false);
								
								if (result.isConfirmed && (response == "OK")) {
									location.reload();
								}
							});
						},
						error: function (error) {
							console.error("630->", error.responseText);

							$('.ibox-content').toggleClass('sk-loading', false);
						}
					});
					// $.ajax
				} else {
					$('.ibox-content').toggleClass('sk-loading', false);
				}
			});
		}
	</script>
	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>
<?php sqlsrv_close($conexion); ?>