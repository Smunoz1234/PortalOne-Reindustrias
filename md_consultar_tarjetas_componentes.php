<?php
$Cons_TarjetasEquipos = "SELECT TOP 100 * FROM uvw_Sap_tbl_TarjetasEquipos WHERE TipoEquipo <> '' ORDER BY IdTarjetaEquipo DESC";
$SQL_TE = sqlsrv_query($conexion, $Cons_TarjetasEquipos);

// SMM, 13/03/2024
$SQL_Ubicacion = Seleccionar("uvw_tbl_TarjetaEquipo_Ubicaciones", "*");
?>

<style>
	.select2-dropdown {
		z-index: 9009;
	}

	.ibox-title {
		border-radius: 5px;
		margin-bottom: 10px;
	}

	.ibox-title a {
		color: inherit !important;
	}

	.collapse-link:hover {
		cursor: pointer;
	}

	.swal2-container {
    	z-index: 9000;
	}
</style>

<div class="modal inmodal fade" id="mdTE_Componente" tabindex="1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" style="width: 60% !important;">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">
					Consultar componentes
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
										<label class="control-label">Jerarquia 1 (Sistema)</label>

										<select name="id_jerarquia_1" id="id_jerarquia_1" class="form-control select2">
											<option value="">Seleccione...</option>

											<?php $SQL_DimJ = Seleccionar("tbl_TarjetaEquipo_Jerarquias", "*", "id_dimension_jerarquia = 1"); ?>
											<?php while ($row_DimJ = sqlsrv_fetch_array($SQL_DimJ)) { ?>
												<option value="<?php echo $row_DimJ['id_jerarquia']; ?>">
													<?php echo $row_DimJ['jerarquia']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="form-group row">
									<div class="col-lg-6">
										<label class="control-label">Serial</label>
									
										<input name="SerialEquipo" type="text" class="form-control" id="SerialEquipo"
											maxlength="100" placeholder="Serial fabricante o interno">
									</div>

									<div class="col-lg-6">
										<label class="control-label">Jerarquia 2 (SubSistema)</label>

										<select name="id_jerarquia_2" id="id_jerarquia_2" class="form-control select2">
											<option value="">Seleccione...</option>

											<?php $SQL_DimJ = Seleccionar("tbl_TarjetaEquipo_Jerarquias", "*", "id_dimension_jerarquia = 2"); ?>
											<?php while ($row_DimJ = sqlsrv_fetch_array($SQL_DimJ)) { ?>
												<option value="<?php echo $row_DimJ['id_jerarquia']; ?>">
													<?php echo $row_DimJ['jerarquia']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="form-group row">
									<div class="col-lg-6">
										<label class="control-label">Buscar dato</label>
									
										<input name="BuscarDato" type="text" class="form-control" id="BuscarDato"
											placeholder="Digite un dato completo, o una parte del mismo...">
									</div>

									<div class="col-lg-6">
										<label class="control-label">Ubicación</label>

										<select name="id_ubicacion_equipo" id="id_ubicacion_equipo" class="form-control select2">
											<option value="">Seleccione...</option>

											<?php while ($row_Ubicacion = sqlsrv_fetch_array($SQL_Ubicacion)) { ?>
												<option value="<?php echo $row_Ubicacion['id_ubicacion_equipo']; ?>">
													<?php echo $row_Ubicacion['ubicacion_equipo']; ?>
												</option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="form-group">
									<div class="col-lg-12">
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
								<div class="table-responsive" id="tableContainer_Componente">
									<table id="footable_Componente" class="table" data-paging="true" data-sorting="true">
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
															onclick="cambiarTE_Componente('<?php echo $row_TE['IdTarjetaEquipo']; ?>', '<?php echo 'SN Fabricante: ' . $row_TE['SerialFabricante'] . ' - Núm. Serie: ' . $row_TE['SerialInterno']; ?>', '<?php echo $row_TE['ItemCode']; ?>', '<?php echo $row_TE['ItemName']; ?>')">
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
	function cambiarTE_Componente(tarjeta_equipo, descripcion_te, id_articulo, de_articulo) {
		console.log("Ejecutando, cambiarTE_Componente()");

		$("#IdTarjetaEquipoComponente").val(tarjeta_equipo);
		$("#DeTarjetaEquipoComponente").val(descripcion_te);

		$("#IdTarjetaEquipoComponente").change();
		$("#DeTarjetaEquipoComponente").change();

		$("#IdArticuloComponente").val(id_articulo);
		$("#ArticuloComponente").val(de_articulo);

		$("#IdArticuloComponente").change();
		$("#ArticuloComponente").change();

		$('#mdTE_Componente').modal('hide');
	}

	$(document).ready(function () {
		$('#footable_Componente').footable();

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
					url: 'md_consultar_tarjetas_componentes_ws.php',
					type: 'POST',
					data: formData,
					processData: false,  // tell jQuery not to process the data
					contentType: false,   // tell jQuery not to set contentType
					success: function (response) {
						// console.log("Line 290", response);

						$("#tableContainer_Componente").html(response);
						$('#footable_Componente').footable();

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

		$("#mdTE_Componente").on("show.bs.modal", function (e) {
			console.log('El modal mdTE_Componente se está mostrando');
			
			let ClienteLlamada = $("#ClienteLlamada").val() || "";
			let NombreClienteLlamada = $("#NombreClienteLlamada").val() || "";

			let IdArticuloLlamada = $("#IdArticuloLlamada").val() || "";
			
			$("#mdTE_Componente #Cliente").val(ClienteLlamada);
			$("#mdTE_Componente #NombreCliente").val(NombreClienteLlamada);

			$("#mdTE_Componente #ItemCode").val(IdArticuloLlamada);

			if((ClienteLlamada =! "") || (IdArticuloLlamada != "")) {
				$('#formBuscar').submit();
			}
		});
	});
</script>