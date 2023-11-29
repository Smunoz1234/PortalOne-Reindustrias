<?php
$Cons_TarjetasEquipos = "SELECT TOP 100 * FROM uvw_Sap_tbl_TarjetasEquipos WHERE TipoEquipo <> '' ORDER BY IdTarjetaEquipo DESC";
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
								<div class="form-group row">
									<div class="col-lg-6">	
										<label class="control-label">ID servicio (IdArticulo)</label>
									
										<input name="ItemCode" type="text" class="form-control" id="ItemCode"
											maxlength="100" placeholder="ID del articulo o servicio">
									</div>

									<div class="col-lg-6">
										<label class="control-label">Serial</label>
									
										<input name="SerialEquipo" type="text" class="form-control" id="SerialEquipo"
											maxlength="100" placeholder="Serial fabricante o interno">
									</div>
								</div>

								<div class="form-group row">
									<div class="col-lg-6">
										<label class="control-label">Estado de equipo</label>
									
										<select name="EstadoEquipo" class="form-control" id="EstadoEquipo">
											<option value="">(Todos)</option>
											<option value="A">Activo</option>
											<option value="R">Devuelto</option>
											<option value="T">Finalizado</option>
											<option value="L">Concedido en prestamo</option>
											<option value="I">En laboratorio de reparación</option>
										</select>
									</div>

									<div class="col-lg-6">
										<label class="control-label">Cliente</label>
									
										<input name="Cliente" type="hidden" id="Cliente">
										<input name="NombreCliente" type="text" class="form-control" id="NombreCliente"
											placeholder="Para TODOS, dejar vacio...">
									</div>
								</div>

								<div class="form-group row">
									<div class="col-lg-6">
										<label class="control-label">Buscar dato</label>
									
										<input name="BuscarDato" type="text" class="form-control" id="BuscarDato"
											placeholder="Digite un dato completo, o una parte del mismo...">
									</div>

									<div class="col-lg-6">
										<br>
										<button type="submit" class="btn btn-outline btn-success pull-right">
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
					<br>

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
															onclick="cambiarTE('<?php echo $row_TE['IdTarjetaEquipo']; ?>', '<?php echo 'SN Fabricante: ' . $row_TE['SerialFabricante'] . ' - Núm. Serie: ' . $row_TE['SerialInterno']; ?>', '<?php echo $row['SerialInterno']; ?>')">
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
														<?php if ($row_TE['TipoEquipo'] === 'P') {
															echo 'Compras';
														} elseif ($row_TE['TipoEquipo'] === 'R') {
															echo 'Ventas';
														} ?>
													</td>
													<td>
														<?php if ($row_TE['CodEstado'] == 'A') { ?>
															<span class='label label-info'>Activo</span>
														<?php } elseif ($row_TE['CodEstado'] == 'R') { ?>
															<span class='label label-danger'>Devuelto</span>
														<?php } elseif ($row_TE['CodEstado'] == 'T') { ?>
															<span class='label label-success'>Finalizado</span>
														<?php } elseif ($row_TE['CodEstado'] == 'L') { ?>
															<span class='label label-secondary'>Concedido en préstamo</span>
														<?php } elseif ($row_TE['CodEstado'] == 'I') { ?>
															<span class='label label-warning'>En laboratorio de reparación</span>
														<?php } ?>
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
	function cambiarTE(tarjeta_equipo, descripcion_te, serial_interno) {
		$("#NumeroSerie").val(tarjeta_equipo);
		$("#Desc_NumeroSerie").val(descripcion_te);
		$("#SerialInterno").val(serial_interno);

		$("#NumeroSerie").change();
		$("#Desc_NumeroSerie").change();
		$("#SerialInterno").change();

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

		$("#formBuscar").on("submit", function (event) {
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

		// Error con cabecera_new
		// $('.chosen-select').chosen({ width: "100%" });

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

		$("#mdTE").on("show.bs.modal", function (e) {
			console.log('El modal mdTE se está mostrando');
			
			let ClienteLlamada = $("#ClienteLlamada").val() || "";
			let NombreClienteLlamada = $("#NombreClienteLlamada").val() || "";

			let IdArticuloLlamada = $("#IdArticuloLlamada").val() || "";
			
			$("#mdTE #Cliente").val(ClienteLlamada);
			$("#mdTE #NombreCliente").val(NombreClienteLlamada);

			$("#mdTE #ItemCode").val(IdArticuloLlamada);

			if((ClienteLlamada =! "") || (IdArticuloLlamada != "")) {
				$('#formBuscar').submit();
			}
		});
	});
</script>