<?php
$Filtro = "";

// Filtros
$Filtro = "TipoEquipo <> ''";
if (isset($_GET['TipoEquipo']) && $_GET['TipoEquipo'] != "") {
	$Filtro .= " AND TipoEquipo='" . $_GET['TipoEquipo'] . "'";
	$sw = 1;
}
if (isset($_GET['SerialEquipo']) && $_GET['SerialEquipo'] != "") {
	$Filtro .= " AND (SerialFabricante LIKE '%" . $_GET['SerialEquipo'] . "%' OR SerialInterno LIKE '%" . $_GET['SerialEquipo'] . "%')";
	$sw = 1;
}
if (isset($_GET['EstadoEquipo']) && $_GET['EstadoEquipo'] != "") {
	$Filtro .= " AND CodEstado='" . $_GET['EstadoEquipo'] . "'";
	$sw = 1;
}

// Filtrar cliente
$ID_CodigoCliente = $row["Cliente"] ?? "";

if ($ID_CodigoCliente != "") {
	$Filtro .= " AND ID_CodigoCliente = '$ID_CodigoCliente'";

	$Where = "CodigoCliente = '$ID_CodigoCliente'";
	$SQL_Cliente = Seleccionar("uvw_Sap_tbl_SociosNegocios", "NombreCliente", $Where);
	$row_Cliente = sqlsrv_fetch_array($SQL_Cliente);
	// var_dump($row_Cliente);
}

if (isset($_GET['BuscarDato']) && $_GET['BuscarDato'] != "") {
	$BuscarDato = $_GET['BuscarDato'];
	$Filtro .= " AND (Calle LIKE '%$BuscarDato%' OR CodigoPostal LIKE '%$BuscarDato%' OR Barrio LIKE '%$BuscarDato%' OR Ciudad LIKE '%$BuscarDato%' OR Distrito LIKE '%$BuscarDato%' OR SerialFabricante LIKE '%$BuscarDato%' OR SerialInterno LIKE '%$BuscarDato%' OR IdTarjetaEquipo LIKE '%$BuscarDato%')";
	$sw = 1;
}

// Realizar consulta con filtros
$Where = "$Filtro ORDER BY IdTarjetaEquipo DESC";
$Cons_TarjetasEquipos = "SELECT TOP 100 * FROM uvw_Sap_tbl_TarjetasEquipos WHERE $Where";
$SQL_TE = sqlsrv_query($conexion, $Cons_TarjetasEquipos);
?>

<div class="modal inmodal fade" id="mdTE" tabindex="1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" style="width: 60% !important;">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">
					Consultar tarjetas de equipo
				</h4>
			</div>

			<div class="modal-body">

				
				<!-- Inicio, filtros -->
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<form id="formBuscar" class="form-horizontal">
								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm">
											<i class="fa fa-filter"></i> Datos para filtrar
										</h3>
									</label>
								</div>

								<div class="form-group">
									<label class="col-lg-1 control-label">Tipo de equipo</label>
									<div class="col-lg-5">
										<select name="TipoEquipo" class="form-control" id="TipoEquipo">
											<option value="">(Todos)</option>
											<option value="P">Compras</option>
											<option value="R">Ventas</option>
										</select>
									</div>

									<label class="col-lg-1 control-label">Serial</label>
									<div class="col-lg-5">
										<input name="SerialEquipo" type="text" class="form-control" id="SerialEquipo"
											maxlength="100"
											value="<?php if (isset($_GET['SerialEquipo']) && ($_GET['SerialEquipo'] != "")) {
												echo $_GET['SerialEquipo'];
											} ?>"
											placeholder="Serial fabricante o interno">
									</div>
								</div>

								<div class="form-group">									
									<label class="col-lg-1 control-label">Estado de equipo</label>
									<div class="col-lg-5">
										<select name="EstadoEquipo" class="form-control" id="EstadoEquipo">
											<option value="">(Todos)</option>
											<option value="A">Activo</option>
											<option value="R">Devuelto</option>
											<option value="T">Finalizado</option>
											<option value="L">Concedido en prestamo</option>
											<option value="I">En laboratorio de reparación</option>
										</select>
									</div>

									<label class="col-lg-1 control-label">Cliente</label>
									<div class="col-lg-5">
										<input name="Cliente" type="hidden" id="Cliente"
											value="<?php echo $ID_CodigoCliente ?? ''; ?>">
										<input name="NombreCliente" type="text" class="form-control" id="NombreCliente"
											placeholder="Para TODOS, dejar vacio..."
											value="<?php echo $row_Cliente['NombreCliente'] ?? ''; ?>">
									</div>
								</div>
								
								<div class="form-group">
									<label class="col-lg-1 control-label">Buscar dato</label>
									<div class="col-lg-6">
										<input name="BuscarDato" type="text" class="form-control" id="BuscarDato"
											placeholder="Digite un dato completo, o una parte del mismo..." 
											maxlength="100"
											value="<?php if (isset($_GET['BuscarDato']) && ($_GET['BuscarDato'] != "")) {
												echo $_GET['BuscarDato'];
											} ?>">
									</div>
									
									<div class="col-lg-4"> <!-- pull-right -->
										<button type="submit" class="btn btn-outline btn-success">
											<i class="fa fa-search"></i> Buscar
										</button>
									</div>
								</div>
							</form>
						</div> <!-- ibox-content -->
					</div> <!-- col-lg-12 -->
				</div>
				<!-- Fin, filtros -->

				<?php if ($SQL_TE) { ?>
				
				<!-- Inicio, tabla -->
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<div class="table-responsive" id="tableContainer">
								<table id="footable" class="table" data-paging="true" data-sorting="true">
									<thead>
										<tr>
											<th>Código cliente</th>
											<th>Cliente</th>
											<th>Serial fabricante</th>
											<th>Serial interno</th>
											<th>Núm.</th>
											<th data-breakpoints="all">Código de artículo</th>
											<th data-breakpoints="all">Artículo</th>
											<th data-breakpoints="all">Tipo de equipo</th>
											<th data-breakpoints="all">Estado</th>
											<th data-breakpoints="all">Acciones</th>
										</tr>
									</thead>
									<tbody>
										<?php while ($row_TE = sqlsrv_fetch_array($SQL_TE)) { ?>
											<tr>
												<td>
													<?php echo $row_TE['CardCode']; ?>
												</td>
												<td>
													<?php echo $row_TE['CardName']; ?>
												</td>
												<td>
													<?php echo $row_TE['SerialFabricante']; ?>
												</td>
												<td>
													<?php echo $row_TE['SerialInterno']; ?>
												</td>
												<td>
													<a type="button" class="btn btn-success btn-xs"
														title="Adicionar o cambiar TE"
														onclick="cambiarTE('<?php echo $row_TE['IdTarjetaEquipo']; ?>', '<?php echo 'SN Fabricante: ' . $row_TE['SerialFabricante'] . ' - Núm. Serie: ' . $row_TE['SerialInterno']; ?>')">
														<b>
															<?php echo $row_TE['IdTarjetaEquipo']; ?>
														</b>
													</a>
												</td>
												<td>
													<?php echo $row_TE['ItemCode']; ?>
												</td>
												<td>
													<?php echo $row_TE['ItemName']; ?>
												</td>
												<td>
													<?php if ($row_TE['TipoEquipo'] === 'P') {echo 'Compras';} elseif ($row_TE['TipoEquipo'] === 'R') {echo 'Ventas';}?>
												</td>
												<td>
													<?php if ($row_TE['CodEstado'] == 'A') {?>
														<span  class='label label-info'>Activo</span>
													<?php } elseif ($row_TE['CodEstado'] == 'R') {?>
														<span  class='label label-danger'>Devuelto</span>
													<?php } elseif ($row_TE['CodEstado'] == 'T') {?>
														<span  class='label label-success'>Finalizado</span>
													<?php } elseif ($row_TE['CodEstado'] == 'L') {?>
														<span  class='label label-secondary'>Concedido en préstamo</span>
													<?php } elseif ($row_TE['CodEstado'] == 'I') {?>
														<span  class='label label-warning'>En laboratorio de reparación</span>
													<?php }?>
												</td>
												<td>
													<a href="tarjeta_equipo.php?id=<?php echo base64_encode($row_TE['IdTarjetaEquipo']); ?>&tl=1"
														class="btn btn-success btn-xs" target="_blank">
														<i class="fa fa-folder-open-o"></i> Abrir
													</a>
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

				<?php } else {
					echo $Cons_TarjetasEquipos;
				} ?>

			</div> <!-- modal-body -->

			<div class="modal-footer">
				<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i>
					Cerrar</button>
			</div>
		</div> <!-- modal-content -->
	</div> <!-- modal-dialog -->
</div>

<script>
	function cambiarTE(tarjeta_equipo, descripcion_te) {
		$("#NumeroSerie").val(tarjeta_equipo);
		$("#Desc_NumeroSerie").val(descripcion_te);
		
		$('#mdTE').modal('hide');
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
				console.log("Line 280", json);

				// Inicio, AJAX
				$.ajax({
					url: 'md_consultar_tarjetas_equipos_ws.php',
					type: 'POST',
					data: formData,
					processData: false,  // tell jQuery not to process the data
					contentType: false,   // tell jQuery not to set contentType
					success: function (response) {
						// console.log("Line 290", response);

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

		$('.chosen-select').chosen({ width: "100%" });
		
		let options = {
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