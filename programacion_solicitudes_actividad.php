<?php
require_once "includes/conexion.php";
// PermitirAcceso(312);

// Actividades.
if (isset($_GET['id']) && $_GET['id'] != "") {
	$id = base64_decode($_GET['id']);
	$idEvento = base64_decode($_GET['idEvento']);
} else {
	$id = "";
	$idEvento = "";
}

$type_act = isset($_GET['tl']) ? $_GET['tl'] : 0;

if ($type_act == 1) {
	$Where = "DocEntry='" . $id . "' and IdEvento='" . $idEvento . "'";
} else {
	$Where = "ID_Actividad='" . $id . "' and IdEvento='" . $idEvento . "'";
}

$SQL_Actividades = Seleccionar('uvw_tbl_Actividades_Rutas', '*', $Where);
$row = sql_fetch_array($SQL_Actividades);

// Empleados.
$SQL_Tecnicos = Seleccionar('uvw_Sap_tbl_Recursos', '*', '', 'NombreEmpleado');

// Grupos de Empleados.
$SQL_GruposUsuario = Seleccionar("uvw_tbl_UsuariosGruposEmpleados", "*", "[ID_Usuario]='" . $_SESSION['CodUser'] . "'", 'DeCargo');

$ids_grupos = array();
while ($row_GruposUsuario = sqlsrv_fetch_array($SQL_GruposUsuario)) {
	$ids_grupos[] = $row_GruposUsuario['IdCargo'];
}

// Serie de Llamada.
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'191'", // @IdTipoDocumento
	2, // @TipoAccion
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);
?>

<style>
	.select2-container {
		/**
		Se reemplaza con "dropdownParent"
		z-index: 10000 !important;
		*/

		/** 
		Permite visualizar correctamente el "select2-multiple"
		SMM, 05/10/2023
		*/
		display: block !important;
		width: 100% !important;
	}

	.easy-autocomplete {
		display: block !important;
		width: 100% !important;
	}
</style>

<form id="frmActividad" method="post">
	<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title">
				<?php echo $row['EtiquetaActividad'] ?? "Nueva Solicitud de Llamada de servicio"; ?>
			</h4>
			
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
		</div>
		<!-- /.modal-header -->

		<div class="modal-body">
			<div class="form-group row">
				<div class="col-lg-6">
					<label for="FechaInicio" class="control-label">Fecha inicio <span class="text-danger">*</span></label>
					
					<div class="row">
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-calendar"></i></span>
								<input required type="text" name="FechaInicio" id="FechaInicio" class="form-control fecha" value="<?php echo $row['FechaInicioActividad'] ?? date("Y-m-d"); ?>" <?php if ($type_act == 1) { echo "readonly"; } ?>>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-clock"></i></span>
								<input required type="text" name="HoraInicio" id="HoraInicio" class="form-control hora" value="<?php echo $row['HoraInicioActividad'] ?? date("H:i"); ?>" onchange="ValidarHoras();" <?php if ($type_act == 1) { echo "readonly"; } ?>>
							</div>
						</div>
					</div>
				</div>
				<!-- /.col-lg-6 -->

				<div class="col-lg-6">
					<label for="FechaFin" class="control-label">Fecha fin <span class="text-danger">*</span></label>
					
					<div class="row">
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-calendar"></i></span>
								<input required type="text" name="FechaFin" id="FechaFin" class="form-control fecha" value="<?php echo $row['FechaFinActividad'] ?? date("Y-m-d"); ?>" <?php if ($type_act == 1) { echo "readonly"; } ?>>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-clock"></i></span>
								<input required type="text" name="HoraFin" id="HoraFin" class="form-control hora" value="<?php echo $row['HoraFinActividad'] ?? date("H:i"); ?>" onchange="ValidarHoras();" <?php if ($type_act == 1) { echo "readonly"; } ?>>
							</div>
						</div>
					</div>
				</div>
				<!-- /.col-lg-6 -->
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">Serie <span class="text-danger">*</span></label>
				
					<select required name="Series" id="Series" class="form-control select2" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>>
						<option value="" disabled <?php if (($type_act == 0)) {
							echo "selected";
						} ?>>Seleccione...</option>

						<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
							<option value="<?php echo $row_Series['IdSeries']; ?>" <?php if (isset($row['Series']) && ($row_Series['IdSeries'] == $row['Series'])) {
								   echo "selected";
							   } ?>>
								<?php echo $row_Series['DeSeries']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="col-lg-4">
					<label class="control-label">
						<i onclick="ConsultarCliente();" title="Consultar cliente" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Cliente <span class="text-danger">*</span>
					</label>
					
					<input type="hidden" name="Cliente" id="Cliente" value="<?php echo $row['ID_CodigoCliente'] ?? ""; ?>">
					<input required type="text" name="NombreCliente" id="NombreCliente" class="form-control" placeholder="Digite para buscar..." <?php if (($type_act == 1)) {
							echo "disabled";
						} ?> value="<?php echo $row['NombreClienteLlamada'] ?? ""; ?>">
				</div>

				<div class="col-lg-4">
					<label class="control-label">Sucursal <span class="text-danger">*</span></label>
				
					<select required name="SucursalCliente" id="SucursalCliente" class="form-control select2">
						<option value="">Seleccione...</option>

						<!-- La sucursal depende del cliente. -->
					</select>
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">
						<i onclick="ConsultarEquipo();" title="Consultar equipo" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Tarjeta de equipo
					</label>
				
					<select name="NumeroSerie" id="NumeroSerie" class="form-control select2">
						<option value="">Seleccione...</option>

						<!-- La TE depende del cliente. -->
					</select>
				</div>

				<div class="col-lg-2">
					<br>
					<div class="btn-group">
						<button type="button" id="AddEquipo" class="btn btn-primary" title="Adicionar Equipo"><i class="fa fa-plus"></i></button>
						<button type="button" id="AddCampana" class="btn btn-info" title="Adicionar Campaña" disabled><i class="fa fa-bell"></i></button>
					</div>
				</div>

				<div class="col-lg-6">
					<label class="control-label">Campañas</label>

					<select multiple name="Campanas[]" id="Campanas" class="form-control select2" data-placeholder="Debe seleccionar las campañas que desea asociar con la Solicitud de Llamada de servicio.">
						<!-- Las campañas dependen de la TE. -->
					</select>
				</div>
			</div>
			<!-- /.form-group -->
			
			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">Asignado a <span class="text-danger">*</span></label>

					<select required name="Tecnico" id="Tecnico" class="form-control select2" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>>
						<option value="" disabled <?php if (($type_act == 0)) {
							echo "selected";
						} ?>>Seleccione...</option>
							
						<?php while ($row_Tecnicos = sqlsrv_fetch_array($SQL_Tecnicos)) { ?>
							<?php if (in_array($row_Tecnicos['IdCargo'], $ids_grupos) || ($MostrarTodosRecursos || (count($ids_grupos) == 0))) { ?>
								<option value="<?php echo $row_Tecnicos['ID_Empleado']; ?>" <?php if (isset($row['IdTecnico']) && ($row_Tecnicos['ID_Empleado'] == $row['IdTecnico'])) {
									echo "selected";
								} ?> 
								<?php if ((count($ids_grupos) > 0) && (!in_array($row_Tecnicos['IdCargo'], $ids_grupos))) {
									echo "disabled";
								} ?>>
									<?php echo $row_Tecnicos['NombreEmpleado'] . " (" . $row_Tecnicos['NombreCentroCosto2'] . " - " . $row_Tecnicos['DeCargo'] . ")"; ?>
								</option>
							<?php } ?>
						<?php } ?>
					</select>
				</div>
				<!-- /.col-lg-4 -->
				
				<div class="col-lg-2">

				</div>

				<div class="col-lg-6">
					<label class="control-label">Comentario <span class="text-danger">*</span></label>

					<textarea required name="Comentario" rows="2" maxlength="3000" type="text" class="form-control" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>><?php echo $row['ComentarioLlamada'] ?? ""; ?></textarea>
				</div>
			</div>
			<!-- /.form-group -->
		</div>
		<!-- /.modal-body -->

		<div class="modal-footer">
			<button type="button" class="btn btn-secondary md-btn-flat" data-dismiss="modal">Cerrar</button>
			<?php if (true) { ?><button type="submit" class="btn btn-primary md-btn-flat"><i
						class="fas fa-save"></i> Guardar</button>
			<?php } ?>
		</div>
	</div>
</form>
<script>
	$(document).ready(function () {
		let options = {
			url: function (phrase) {
				return `ajx_buscar_datos_json.php?type=7&id=${phrase}`;
			},
			getValue: "NombreBuscarCliente",
			requestDelay: 400,
			list: {
				match: {
					enabled: true
				},
				onClickEvent: function () {
					let value = $("#NombreCliente").getSelectedItemData().CodigoCliente;
					$("#Cliente").val(value).trigger("change");
				}
			}
		};
		$("#NombreCliente").easyAutocomplete(options);

		$("#Cliente").on("change", function() {
			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=3&id=${$(this).val()}`,
				success: function (response) {
					$('#SucursalCliente').html(response).fadeIn();
					$('#SucursalCliente').trigger('change');
				}
			});

			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=28&id=&clt=${$(this).val()}`,
				success: function (response) {
					$('#NumeroSerie').html(response).fadeIn();
					$('#NumeroSerie').trigger('change');
				}
			});
		});

		$(".select2").select2({
			dropdownParent: $('#ModalAct')
		});

		$("#frmActividad").validate({
			submitHandler: function (form, event) {
				event.preventDefault()
				blockUI();
				$.ajax({
					type: "GET",
					url: "includes/procedimientos.php?type=31&id_actividad=<?php echo $row['ID_Actividad'] ?? ""; ?>&id_evento=<?php echo $row['IdEvento'] ?? ""; ?>&docentry=<?php echo $row['DocEntry'] ?? ""; ?>&id_asuntoactividad=" + $("#AsuntoActividad").val() + "&titulo_actividad=" + $("#TituloActividad").val() + "&id_empleadoactividad=" + $("#EmpleadoActividad").val() + "&fechainicio=" + $("#FechaInicio").val() + "&horainicio=" + $("#HoraInicio").val() + "&fechafin=" + $("#FechaFin").val() + "&horafin=" + $("#HoraFin").val() + "&comentarios_actividad=" + $("#Comentarios").val() + "&estado=" + $("#EstadoActividad").val() + "&id_tipoestadoact=" + $("#TipoEstadoActividad").val() + "&llamada_servicio=<?php echo $row['ID_LlamadaServicio'] ?? ""; ?>&metodo=2&fechainicio_ejecucion=" + $("#FechaInicioEjecucion").val() + "&horainicio_ejecucion=" + $("#HoraInicioEjecucion").val() + "&fechafin_ejecucion=" + $("#FechaFinEjecucion").val() + "&horafin_ejecucion=" + $("#HoraFinEjecucion").val() + "&turno_tecnico=" + $("#TurnoTecnico").val() + "&sptype=2",
					success: function (response) {
						if (response == "OK") {
							$("#btnGuardar").prop('disabled', false);
							$("#btnPendientes").prop('disabled', false);
							var event = calendar.getEventById('<?php echo $id; ?>')
							event.setExtendedProp('manualChange', '1')
							event.setProp('backgroundColor', $("#TipoEstadoActividad").find(':selected').data('color'))
							event.setProp('borderColor', $("#TipoEstadoActividad").find(':selected').data('color'))
							event.setDates($("#FechaInicio").val() + ' ' + $("#HoraInicio").val(), $("#FechaFin").val() + ' ' + $("#HoraFin").val())
							event.setResources([$("#EmpleadoActividad").val()])
							if ($("#EstadoActividad").val() == 'Y') {
								event.setProp('classNames', ['event-striped'])
							}
							$('#ModalAct').modal("hide");
							event.setExtendedProp('manualChange', '0')
							blockUI(false);
							mostrarNotify('Se ha editado una actividad')
						} else {
							Swal.fire({
								title: '¡Advertencia!',
								text: 'No se pudo insertar la actividad en la ruta',
								icon: 'warning',
							});
							console.log("Error:", response)
						}
					}
				});
			}
		});

		<?php if (true) { ?>
			$(".fecha").flatpickr({
				dateFormat: "Y-m-d",
				static: true,
				allowInput: true
			});

			$(".hora").flatpickr({
				enableTime: true,
				noCalendar: true,
				dateFormat: "H:i",
				time_24hr: true,
				static: true,
				allowInput: true
			});
		<?php } ?>

		// maxLength("Comentario");
		
		
	});

	function ValidarHoras() {
		var HInicio = document.getElementById("HoraInicio").value;
		var HFin = document.getElementById("HoraFin").value;

		if (!validarRangoHoras(HInicio, HFin)) {
			Swal.fire({
				title: '¡Advertencia!',
				text: 'Tiempo no válido. Ingrese una duración positiva.',
				icon: 'warning',
			});
			return false;
		}
	}

	function ConsultarCliente() {
		let Cliente = document.getElementById("Cliente");
		
		if (Cliente.value != "") {
			window.open(`socios_negocios.php?id=${Base64.encode(Cliente.value)}&tl=1`, "_blank");
		}
	}

	function ConsultarEquipo() {
		let Equipo = document.getElementById("NumeroSerie");
		
		if (Equipo.value != "") {
			window.open(`tarjeta_equipo.php?id=${Base64.encode(Equipo.value)}&tl=1&te=1`, "_blank");
		}
	}
</script>
