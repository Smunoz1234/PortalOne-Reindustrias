<?php
require_once "includes/conexion.php";
PermitirAcceso(312);
//require_once("includes/conexion_hn.php");
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

//Actividades
$SQL_Actividades = Seleccionar('uvw_tbl_Actividades_Rutas', '*', $Where);
$row = sql_fetch_array($SQL_Actividades);

//Asunto actividad
$SQL_AsuntoActividad = Seleccionar('uvw_Sap_tbl_AsuntosActividad', '*', "Id_TipoActividad=2", 'DE_AsuntoActividad');

//Empleados
$SQL_EmpleadoActividad = Seleccionar('uvw_Sap_tbl_Empleados', '*', "IdUsuarioSAP=0", 'NombreEmpleado');

//Turno técnico
$SQL_TurnoTecnicos = Seleccionar('uvw_Sap_tbl_TurnoTecnicos', '*');

//Tipos de Estado actividad
$SQL_TiposEstadoActividad = Seleccionar('uvw_tbl_TipoEstadoServicio', '*');

//Estado actividad
$SQL_EstadoActividad = Seleccionar('uvw_tbl_EstadoActividad', '*');

// Grupos de Empleados, SMM 19/05/2022
$SQL_GruposUsuario = Seleccionar("uvw_tbl_UsuariosGruposEmpleados", "*", "[ID_Usuario]='" . $_SESSION['CodUser'] . "'", 'DeCargo');

$ids_grupos = array();
while ($row_GruposUsuario = sqlsrv_fetch_array($SQL_GruposUsuario)) {
	$ids_grupos[] = $row_GruposUsuario['IdCargo'];
}

$disabled = "";
if (isset($row['ID_EmpleadoActividad']) && (count($ids_grupos) > 0)) {
	$ID_Empleado = "'" . $row['ID_EmpleadoActividad'] . "'";
	$SQL_Empleado = Seleccionar('uvw_Sap_tbl_Empleados', '*', "ID_Empleado = $ID_Empleado");
	$row_Empleado = sql_fetch_array($SQL_Empleado);

	if (isset($row_Empleado['IdCargo']) && (!in_array($row_Empleado['IdCargo'], $ids_grupos))) {
		$disabled = "disabled";
	}
}

// Serie de Llamada. SMM, 07/03/2023
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'191'", // @IdTipoDocumento
	2, // @TipoAccion
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);
?>

<form id="frmActividad" method="post">
	<div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title">
				<?php echo $row['EtiquetaActividad'] ?? "Nueva Solicitud de Llamada de servicio"; ?>
			</h5>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">×</button>
		</div>
		<div class="modal-body">
			<div class="pt-3 pr-3 pl-3 pb-1 mb-2 bg-primary text-white">
				<h5><i class="fas fa-calendar-alt"></i> Datos de programación</h5>
			</div>

			<div class="form-group row">
				<label class="col-lg-2 col-form-label">Serie <span class="text-danger">*</span></label>
				<div class="col-lg-4">
					<select required name="Series" id="Series" class="form-control" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>>
						<option value="">Seleccione...</option>

						<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
							<option value="<?php echo $row_Series['IdSeries']; ?>" <?php if ((isset($row['Series'])) && ($row_Series['IdSeries'] == $row['Series'])) {
								   echo "selected";
							   } ?>>
								<?php echo $row_Series['DeSeries']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<label class="col-lg-2 col-form-label">Cliente</label>
				<div class="col-lg-4">
					<select <?php echo $disabled ?> name="TurnoTecnico" class="form-control" id="TurnoTecnico" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>>
						<option value="">Seleccione...</option>
						<?php while ($row_TurnoTecnicos = sqlsrv_fetch_array($SQL_TurnoTecnicos)) { ?>
							<option value="<?php echo $row_TurnoTecnicos['CodigoTurno']; ?>" <?php if ((isset($row['CDU_IdTurnoTecnico'])) && (strcmp($row_TurnoTecnicos['CodigoTurno'], $row['CDU_IdTurnoTecnico']) == 0)) {
								   echo "selected";
							   } ?>>
								<?php echo $row_TurnoTecnicos['NombreTurno']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<label class="col-lg-2 col-form-label">Sucursal</label>
				<div class="col-lg-4">
					<select <?php echo $disabled ?> name="TurnoTecnico" class="form-control" id="TurnoTecnico" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>>
						<option value="">Seleccione...</option>
						<?php while ($row_TurnoTecnicos = sqlsrv_fetch_array($SQL_TurnoTecnicos)) { ?>
							<option value="<?php echo $row_TurnoTecnicos['CodigoTurno']; ?>" <?php if ((isset($row['CDU_IdTurnoTecnico'])) && (strcmp($row_TurnoTecnicos['CodigoTurno'], $row['CDU_IdTurnoTecnico']) == 0)) {
								   echo "selected";
							   } ?>>
								<?php echo $row_TurnoTecnicos['NombreTurno']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<label class="col-lg-2 col-form-label">Tarjeta Equipo</label>
				<div class="col-lg-4">
					<select <?php echo $disabled ?> name="TurnoTecnico" class="form-control" id="TurnoTecnico" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>>
						<option value="">Seleccione...</option>
						<?php while ($row_TurnoTecnicos = sqlsrv_fetch_array($SQL_TurnoTecnicos)) { ?>
							<option value="<?php echo $row_TurnoTecnicos['CodigoTurno']; ?>" <?php if ((isset($row['CDU_IdTurnoTecnico'])) && (strcmp($row_TurnoTecnicos['CodigoTurno'], $row['CDU_IdTurnoTecnico']) == 0)) {
								   echo "selected";
							   } ?>>
								<?php echo $row_TurnoTecnicos['NombreTurno']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<label class="col-lg-2 col-form-label">Campaña</label>
				<div class="col-lg-4">
					<select <?php echo $disabled ?> name="TurnoTecnico" class="form-control" id="TurnoTecnico" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>>
						<option value="">Seleccione...</option>
						<?php while ($row_TurnoTecnicos = sqlsrv_fetch_array($SQL_TurnoTecnicos)) { ?>
							<option value="<?php echo $row_TurnoTecnicos['CodigoTurno']; ?>" <?php if ((isset($row['CDU_IdTurnoTecnico'])) && (strcmp($row_TurnoTecnicos['CodigoTurno'], $row['CDU_IdTurnoTecnico']) == 0)) {
								   echo "selected";
							   } ?>>
								<?php echo $row_TurnoTecnicos['NombreTurno']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<label class="col-lg-2 col-form-label">Fecha inicio <span class="text-danger">*</span></label>
				<div class="col-lg-2 input-group">
					<input <?php echo $disabled ?> name="FechaInicio" type="text" required
						class="form-control" id="FechaInicio" value="<?php echo $row['FechaInicioActividad'] ?? date("Y-m-d"); ?>" <?php if (($type_act == 1)) {
							   echo "readonly";
						   } ?>>
				</div>
				<div class="col-lg-2 input-group">
					<input <?php echo $disabled ?> name="HoraInicio" id="HoraInicio" type="text" class="form-control"
						value="<?php echo $row['HoraInicioActividad'] ?? date("H:i"); ?>" required
						onChange="ValidarHoras();" <?php if (($type_act == 1)) {
							echo "readonly";
						} ?>>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-lg-2 col-form-label">Fecha fin <span class="text-danger">*</span></label>
				<div class="col-lg-2 input-group">
					<input <?php echo $disabled ?> name="FechaFin" type="text" required class="form-control"
						id="FechaFin" value="<?php echo $row['FechaFinActividad'] ?? date("Y-m-d"); ?>" <?php if (($type_act == 1)) {
							   echo "readonly";
						   } ?>>
				</div>
				<div class="col-lg-2 input-group">
					<input <?php echo $disabled ?> name="HoraFin" id="HoraFin" type="text" class="form-control"
						value="<?php echo $row['HoraFinActividad'] ?? date("H:i"); ?>" required onChange="ValidarHoras();"
						<?php if (($type_act == 1)) {
							echo "readonly";
						} ?>>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-lg-2 col-form-label">Asignado a <span class="text-danger">*</span></label>
				<div class="col-lg-4">
					<select <?php echo $disabled ?> name="EmpleadoActividad" class="form-control select2"
						style="width: 100%" required id="EmpleadoActividad" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>>
						<option value="">(Sin asignar)</option>
						<?php while ($row_EmpleadoActividad = sqlsrv_fetch_array($SQL_EmpleadoActividad)) { ?>
							<option value="<?php echo $row_EmpleadoActividad['ID_Empleado']; ?>" <?php if ((isset($row['ID_EmpleadoActividad'])) && (strcmp($row_EmpleadoActividad['ID_Empleado'], $row['ID_EmpleadoActividad']) == 0)) {
								   echo "selected";
							   } ?>>
								<?php echo $row_EmpleadoActividad['NombreEmpleado']; ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-lg-2 col-form-label">Comentario</label>
				<div class="col-lg-4">
					<select <?php echo $disabled ?> name="TurnoTecnico" class="form-control" id="TurnoTecnico" <?php if (($type_act == 1)) {
							echo "disabled";
						} ?>>
						<option value="">Seleccione...</option>
						<?php while ($row_TurnoTecnicos = sqlsrv_fetch_array($SQL_TurnoTecnicos)) { ?>
							<option value="<?php echo $row_TurnoTecnicos['CodigoTurno']; ?>" <?php if ((isset($row['CDU_IdTurnoTecnico'])) && (strcmp($row_TurnoTecnicos['CodigoTurno'], $row['CDU_IdTurnoTecnico']) == 0)) {
								   echo "selected";
							   } ?>>
								<?php echo $row_TurnoTecnicos['NombreTurno']; ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
		</div>
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
			$('#FechaInicio').flatpickr({
				dateFormat: "Y-m-d",
				static: true,
				allowInput: true
			});
			$('#HoraInicio').flatpickr({
				enableTime: true,
				noCalendar: true,
				dateFormat: "H:i",
				time_24hr: true,
				static: true,
				allowInput: true
			});

			$('#FechaFin').flatpickr({
				dateFormat: "Y-m-d",
				static: true,
				allowInput: true
			});
			$('#HoraFin').flatpickr({
				enableTime: true,
				noCalendar: true,
				dateFormat: "H:i",
				time_24hr: true,
				static: true,
				allowInput: true
			});

		<?php } ?>
		$('#EmpleadoActividad').select2({
			dropdownParent: $('#ModalAct')
		});
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
</script>
