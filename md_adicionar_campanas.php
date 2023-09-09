<?php
require_once "includes/conexion.php";

// SMM, 25/02/2023
$msg_error_detalle = "";
$parametros_detalle = array();

$coduser = $_SESSION['CodUser'];
$datetime_detalle = FormatoFecha(date('Y-m-d'), date('H:i:s'));

$type_detalle = $_POST['type'] ?? 0;
$ID_detalle = $_POST['ID'] ?? "";

$id_tarjeta_equipo = $_POST['id_tarjeta_equipo'] ?? "";
$VIN_detalle = $_POST['VIN'] ?? "";

$id_usuario_creacion_detalle = "'$coduser'";
$fecha_creacion_detalle = "'$datetime_detalle'";
$hora_creacion_detalle = "'$datetime_detalle'";
$id_usuario_actualizacion_detalle = "'$coduser'";
$fecha_actualizacion_detalle = "'$datetime_detalle'";
$hora_actualizacion_detalle = "'$datetime_detalle'";

if ($type_detalle == 1) {
	$msg_error = "No se pudo crear el VIN.";

	$parametros = array(
		$type_detalle,
		"'$ID_detalle'",
		$id_campana_detalle,
		"'$VIN_detalle'",
		$id_usuario_actualizacion_detalle,
		$fecha_actualizacion_detalle,
		$hora_actualizacion_detalle,
		$id_usuario_creacion_detalle,
		$fecha_creacion_detalle,
		$hora_creacion_detalle,
	);

} elseif ($type_detalle == 2) {
	$msg_error = "No se pudo actualizar el VIN.";

	$parametros = array(
		$type_detalle,
		"'$ID_detalle'",
		$id_campana_detalle,
		"'$VIN_detalle'",
		$id_usuario_actualizacion_detalle,
		$fecha_actualizacion_detalle,
		$hora_actualizacion_detalle,
	);

} elseif ($type_detalle == 3) {
	$msg_error = "No se pudo eliminar el VIN.";

	$parametros = array(
		$type_detalle,
		"'$ID_detalle'",
		$id_campana_detalle,
	);
}

if ($type_detalle != 0) {
	$SQL_Operacion = EjecutarSP('sp_tbl_CampanaVehiculosDetalle', $parametros);

	if (!$SQL_Operacion) {
		echo $msg_error_detalle;
	} else {
		$row = sqlsrv_fetch_array($SQL_Operacion);

		if (isset($row['Error']) && ($row['Error'] != "")) {
			echo "$msg_error_detalle";
			echo "(" . $row['Error'] . ")";
		} else {
			echo "OK";
		}
	}

	// Mostrar mensajes AJAX.
	exit();
}

// SMM, 08/09/2023
$SQL_Campanas_Modal = Seleccionar("uvw_tbl_LlamadasServicios_Campanas_Asignacion", "*", "id_tarjeta_equipo='$id_tarjeta_equipo'");
$hasRowsCampanas_Modal = ($SQL_Campanas_Modal) ? sqlsrv_has_rows($SQL_Campanas_Modal) : false;
?>

<script>
	var json = [];
	var cant = 0;
	function SeleccionarCampana(DocNum) {
		var btnAdicionar = document.getElementById('btnAdicionar');
		var Check = document.getElementById(`chkSelOT${DocNum}`).checked;
		var sw = -1;

		json.forEach(function (element, index) {
			if (json[index] == DocNum) {
				sw = index;
			}

			// console.log(element,index);
		});

		if (sw >= 0) {
			json.splice(sw, 1);
			cant--;
		} else if (Check) {
			json.push(DocNum);
			cant++;
		}

		if (cant > 0) {
			$("#btnAdicionar").removeAttr("disabled");
		} else {
			$("#chkAll").prop("checked", false);
			$("#btnAdicionar").attr("disabled", "disabled");
		}

		// console.log(json);
	}

	function SeleccionarTodos() {
		var Check = document.getElementById('chkAll').checked;
		if (Check == false) {
			json = [];
			cant = 0;
			$("#btnAdicionar").attr("disabled", "disabled");
		}
		$(".chkSelOT").prop("checked", Check);
		if (Check) {
			$(".chkSelOT").trigger('change');
		}
	}
</script>

<style>
	.swal2-container {
		z-index: 9000;
	}

	.easy-autocomplete {
		width: 100% !important
	}
</style>

<form id="frmCampanasDetalle" method="post" enctype="multipart/form-data">
	<div class="modal-header">
		<h4 class="modal-title">Adicionar Campañas</h4>
	</div>
	<!-- /.modal-title -->

	<div class="modal-body">
		<div class="form-group">
			<div class="ibox-content">
				<?php include "includes/spinner.php"; ?>

				<!-- Table Campanas -->
				<div class="row">
					<div class="col-12 text-center">
						<div class="ibox-content">
							<?php if ($hasRowsCampanas_Modal) { ?>
								<div class="table" style="max-height: 230px; overflow-y: auto;">
									<table class="table table-striped table-bordered table-hover" id="dataTable_Campana">
										<thead>
											<tr>
												<th>ID Campaña</th>
												<th>Campaña</th>
												<th>Descripción</th>
												<th>Estado Campaña</th>
												<th>Fecha Vigencia</th>

												<th class="text-center">
													<div class="checkbox checkbox-success">
														<input type="checkbox" id="chkAll" value=""
															onchange="SeleccionarTodos();"
															title="Seleccionar todos"><label></label>
													</div>
												</th>
											</tr>
										</thead>
										<tbody>
											<?php while ($row_Campana_Modal = sqlsrv_fetch_array($SQL_Campanas_Modal)) { ?>
												<tr class="gradeX">
													<td>
														<a href="campanas_vehiculo.php?id=<?php echo $row_Campana_Modal['id_campana']; ?>&edit=1"
															class="btn btn-success btn-xs" target="_blank">
															<i class="fa fa-folder-open-o"></i>
															<?php echo $row_Campana_Modal['id_campana']; ?>
														</a>
													</td>
													<td>
														<?php echo $row_Campana_Modal['campana'] ?? ""; ?>
													</td>
													<td>
														<?php echo $row_Campana_Modal['descripcion_campana'] ?? ""; ?>
													</td>
													<td>
														<?php if ($row_Campana_Modal['estado'] == 'Y') { ?>
															<span class='label label-info'>Activa</span>
														<?php } else { ?>
															<span class='label label-danger'>Inactiva</span>
														<?php } ?>
													</td>
													<td>
														<?php echo (isset($row_Campana_Modal["fecha_limite_vigencia"]) && $row_Campana_Modal["fecha_limite_vigencia"] != "") ? $row_Campana_Modal['fecha_limite_vigencia']->format("Y-m-d") : ""; ?>
													</td>

													<td class="text-center">
														<?php if ($row_Campana_Modal['estado'] == 'Y') { ?>
															<div class="checkbox checkbox-success"
																id="dvChkSel<?php echo $row_Campana_Modal['id_recepcion_vehiculo']; ?>">
																<input type="checkbox" class="chkSelOT"
																	id="chkSelOT<?php echo $row_Campana_Modal['id_recepcion_vehiculo']; ?>"
																	value=""
																	onchange="SeleccionarCampana('<?php echo $row_Campana_Modal['id_campana']; ?>');"
																	aria-label="Single checkbox One"><label></label>
															</div>
														<?php } ?>
													</td>
												</tr>
											<?php } ?>
										</tbody>
									</table>
								</div>
							<?php } else { ?>
								<i class="fa fa-search" style="font-size: 18px; color: lightgray;"></i>
								<span style="font-size: 13px; color: lightgray;">No hay registros de Campañas de
									Vehículo</span>
							<?php } ?>
						</div>
					</div>
				</div>
				<!-- End Table Campanas -->
			</div>
			<!-- /.ibox-content -->
		</div>
		<!-- /.form-group -->
	</div>
	<!-- /.modal-body -->

	<div class="modal-footer">
		<button type="submit" class="btn btn-success m-t-md" id="btnAdicionar" disabled><i class="fa fa-check"></i>
			Aceptar</button>

		<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal" id="btnCerrar"><i
				class="fa fa-times"></i>
			Cerrar</button>
	</div>
	<!-- /modal-footer -->
</form>

<script>
	$(document).ready(function () {
		$("#frmCampanasDetalle").validate({
			submitHandler: function (form) {
				/*
				$('.ibox-content').toggleClass('sk-loading', true);

				if (lote) {
					id = json
				}

				$.ajax({
					type: "POST",
					url: "md_frm_cambiar_estados.php",
					data: {
						id: id,
						frm: 'RecepcionVehiculos',
						nomID: 'id_recepcion_vehiculo'
					},
					success: function (response) {
						$('.ibox-content').toggleClass('sk-loading', false);
						$('#ContenidoModal').html(response);
						$('#myModal').modal("show");
					}
				});

				// Obtén el valor del campo de entrada
				let listaVINs = $("#ListaVIN").val();

				// Divide la lista en un arreglo usando el separador ";"
				let arregloVINs = listaVINs.split(";");

				// Limpia los espacios en blanco y elementos vacíos del arreglo
				arregloVINs = arregloVINs.map(function (vin) {
					return vin.trim();
				}).filter(function (vin) {
					return vin !== "";
				});

				// Validación del ciclo
				var validarAjax = true;
				var contadorAjax = 0;

				// Iterar sobre cada VIN y realizar una llamada AJAX por separado
				arregloVINs.forEach(function (vin) {
					$.ajax({
						type: "POST",
						url: "md_campanas_vehiculo.php",
						data: {
							type: 1,
							ID: $("#id_campana").val(),
							VIN: vin,  // Usar el VIN actual en esta iteración
						},
						success: function (response) {
							console.log(response);

							contadorAjax++;
							if (response !== "OK") {
								validarAjax = false;
							}

							// Verificar si todas las solicitudes AJAX han finalizado
							if (contadorAjax === arregloVINs.length) {
								Swal.fire({
									icon: (validarAjax) ? "success" : "warning",
									title: (validarAjax) ? "¡Listo!" : "¡Error!",
									text: (validarAjax) ? "Todos los VINs se insertaron correctamente." : "No se pudieron insertar algunos VINs, por favor verifique."
								}).then((result) => {
									if (result.isConfirmed) {
										// if(validarAjax) {

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

										// }
									}
								});
								// Swal.fire
							}
						},
						error: function (error) {
							console.error("240->", error.responseText);

							validarAjax = false;
						}
					});
				});
				// .forEach()
				*/
			}
			// submitHandler
		});

		$("#dataTable_Campana").DataTable({
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