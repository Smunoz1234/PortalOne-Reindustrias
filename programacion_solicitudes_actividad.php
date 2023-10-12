<?php
require_once "includes/conexion.php";
// PermitirAcceso(312);

$Where = "";
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
	"'191'",
	// @IdTipoDocumento
	2,
	// @TipoAccion
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Llamar a SP de forma asincrona. SMM, 10/10/2023
$msg_error = "";
$parametros = array();

$coduser = $_SESSION['CodUser'];
$datetime = FormatoFecha(date('Y-m-d'), date('H:i:s'));

$Cliente = $_POST["Cliente"] ?? "";
$Comentario = $_POST["Comentario"] ?? "";
$FechaHoraFin = isset($_POST["FechaFin"]) ? FormatoFecha($_POST["FechaFin"], $_POST["HoraFin"]) : "";
$FechaHoraInicio = isset($_POST["FechaInicio"]) ? FormatoFecha($_POST["FechaInicio"], $_POST["HoraInicio"]) : "";
$NumeroSerie = $_POST["NumeroSerie"] ?? ""; // TE
$Series = $_POST["Series"] ?? "NULL";
$SucursalCliente = $_POST["SucursalCliente"] ?? "NULL"; // NumeroLinea
$Type = $_POST["Type"] ?? 0;
$Tecnico = $_POST["Tecnico"] ?? "NULL"; // AsignadoA? -> EmpleadoLlamada
$Usuario = "'$coduser'";


if ($Type == 1) {
	$msg_error = "No se pudo crear la Agenda.";

	$parametros = array(
		$Type,
		"NULL", // ID_SolicitudLlamadaServicio
		$Series,
		$Tecnico,
		"'$Cliente'",
		"'$NumeroSerie'",
		$SucursalCliente,
		"'$Comentario'",
		"'$FechaHoraInicio'",
		$Usuario,
		"'$FechaHoraFin'",
		"'$FechaHoraFin'",
		"''" // CampanasAsociadas
	);
} 

if ($Type != 0) {
	$SQL_Operacion = EjecutarSP("sp_tbl_SolicitudLlamadaServicios_Calendario", $parametros);

	if (!$SQL_Operacion) {
		echo $msg_error;
		exit();
	} else {
		$row_Operacion = sqlsrv_fetch_array($SQL_Operacion);

		if (isset($row_Operacion['Error']) && ($row_Operacion['Error'] != "")) {
			$msg_error .= " (" . $row_Operacion['Error'] . ")";

			echo $msg_error;
			exit();
		} else {
			$IdSolicitud = ($row_Operacion[0] ?? "NULL");

			// Corregir Campos. Type = 2, ID_SolicitudLlamadaServicio = $IdSolicitud.
			EjecutarSP("sp_tbl_SolicitudLlamadaServicios_Calendario", [2, $IdSolicitud]); 

			echo "OK";
			exit();
		}
	}
}
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
			<h4 class="modal-title">Nueva Solicitud de Llamada de servicio</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
		</div>
		<!-- /.modal-header -->

		<div class="modal-body">
			<div class="form-group row">
				<div class="col-lg-6">
					<label for="FechaInicio" class="control-label">Fecha inicio <span
							class="text-danger">*</span></label>

					<div class="row">
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-calendar"></i></span>
								<input required type="text" name="FechaInicio" id="FechaInicio"
									class="form-control fecha"
									value="<?php echo $row['FechaInicio'] ?? date("Y-m-d"); ?>">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-clock"></i></span>
								<input required type="text" name="HoraInicio" id="HoraInicio" class="form-control hora"
									value="<?php echo $row['HoraInicio'] ?? date("H:i"); ?>"
									onchange="ValidarHoras();">
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
								<input required type="text" name="FechaFin" id="FechaFin" class="form-control fecha"
									value="<?php echo $row['FechaFin'] ?? date("Y-m-d"); ?>">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-clock"></i></span>
								<input required type="text" name="HoraFin" id="HoraFin" class="form-control hora"
									value="<?php echo $row['HoraFin'] ?? date("H:i"); ?>"
									onchange="ValidarHoras();">
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

					<select required name="Series" id="Series" class="form-control select2">
						<option value="" disabled selected>Seleccione...</option>

						<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
							<option value="<?php echo $row_Series['IdSeries']; ?>">
								<?php echo $row_Series['DeSeries']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="col-lg-2"></div>

				<div class="col-lg-4">
					<label class="control-label">
						<i onclick="ConsultarCliente();" title="Consultar cliente" style="cursor: pointer"
							class="btn-xs btn-success fa fa-search"></i> Cliente <span class="text-danger">*</span>
					</label>

					<input type="hidden" name="Cliente" id="Cliente"
						value="<?php echo $row['ID_CodigoCliente'] ?? ""; ?>">
					<input required type="text" name="NombreCliente" id="NombreCliente" class="form-control"
						placeholder="Digite para buscar..." value="<?php echo $row['NombreClienteLlamada'] ?? ""; ?>">
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">Técnico/Asesor <span class="text-danger">*</span></label>

					<select required name="Tecnico" id="Tecnico" class="form-control select2">
						<option value="" disabled selected>Seleccione...</option>

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

				<div class="col-lg-2"></div>
								
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
						<i onclick="ConsultarEquipo();" title="Consultar equipo" style="cursor: pointer"
							class="btn-xs btn-success fa fa-search"></i> Tarjeta de equipo <span class="text-danger">*</span>
					</label>

					<select name="NumeroSerie" id="NumeroSerie" class="form-control select2" required>
						<option value="">Seleccione...</option>

						<!-- La TE depende del cliente. -->
					</select>
				</div>

				<div class="col-lg-2">
					<br>
					<div class="btn-group">
						<button type="button" id="AddEquipo" class="btn btn-primary" title="Adicionar Equipo"><i
								class="fa fa-plus"></i></button>
						<button type="button" id="AddCampana" class="btn btn-info" title="Adicionar Campaña" disabled><i
								class="fa fa-bell"></i></button>
					</div>
				</div>

				<div class="col-lg-4">
					<label class="control-label">Campañas</label>

					<select multiple name="Campanas[]" id="Campanas" class="form-control select2"
						data-placeholder="Debe seleccionar las campañas que desea asociar.">
						<!-- Las campañas dependen de la TE. -->
					</select>
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-12">
					<label class="control-label">Comentario <span class="text-danger">*</span></label>

					<textarea required name="Comentario" rows="3" maxlength="3000" type="text" class="form-control"><?php echo $row['ComentarioSolicitud'] ?? ""; ?></textarea>
				</div>
			</div>
			<!-- /.form-group -->
		</div>
		<!-- /.modal-body -->

		<div class="modal-footer">
			<button type="button" class="btn btn-secondary md-btn-flat" data-dismiss="modal">Cerrar</button>
			<button type="submit" class="btn btn-primary md-btn-flat"><i class="fas fa-save"></i> Guardar</button>
		</div>
		<!-- /modal-footer -->
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

		$("#Cliente").on("change", function () {
			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=3&id=${$(this).val()}&sucline=1`,
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
				event.preventDefault(); // Prevenir redirrección.
				blockUI(); // Carga iniciada.

				let formData = new FormData(form);
				let jsonForm = Object.fromEntries(formData);
				console.log("Line 366", jsonForm);
				
				$.ajax({
					type: "POST",
					data: {
						Cliente: jsonForm.Cliente,
						Comentario: jsonForm.Comentario,
						FechaFin: jsonForm.FechaFin,
						FechaInicio: jsonForm.FechaInicio,
						HoraFin: jsonForm.HoraFin,
						HoraInicio: jsonForm.HoraInicio,
						NumeroSerie: jsonForm.NumeroSerie,
						Series: jsonForm.Series,
						SucursalCliente: jsonForm.SucursalCliente,
						Tecnico: jsonForm.Tecnico,
						Type: 1
					},
					url: "programacion_solicitudes_actividad.php",
					success: function (response) {
						if (response == "OK") {
							Swal.fire({
								title: "¡Listo!",
								text: "La solicitud se creo correctamente.",
								icon: 'success',
							});

							$('#ModalAct').modal("hide");
						} else {
							Swal.fire({
								title: "¡Advertencia!",
								text: response,
								icon: "warning",
							});
						}

						blockUI(false); // Carga terminada.
					},
					error: function (error) {
						Swal.fire({
							title: "¡Advertencia!",
							text: "Ocurrio un error inesperado.",
							icon: "warning",
						});

						console.log("Error:", error.responseText);
						blockUI(false); // Carga terminada.
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

		// SMM, 05/10/2023
		$("#NumeroSerie").on("change", function () {
			if ($(this).val() != "") {
				$('#AddCampana').prop('disabled', false);
			} else {
				$('#AddCampana').prop('disabled', true);
			}

			let id_tarjeta_equipo = $(this).find(':selected').data('id');

			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=49&id=${id_tarjeta_equipo}`,
				success: function (response) {
					$("#Campanas").html(response).fadeIn();
					$("#Campanas").trigger('change');
				},
				error: function (error) {
					console.log("error (410), ", error);
				}
			});
		});

		$("#AddCampana").on("click", function () {
			AdicionarCampanaAsincrono();
		});

		// Función para oscurecer el primer modal cuando se abre el segundo
		$('#myModal2').on('show.bs.modal', function () {
			$('#ModalAct').addClass('modal-backdrop');
		});

		// Función para eliminar el oscurecimiento cuando se cierra el segundo modal
		$('#myModal2').on('hidden.bs.modal', function () {
			$('#ModalAct').removeClass('modal-backdrop');
		});

		$("#AddEquipo").on("click", function () {
			AdicionarEquipo();
		});
	});

	function AdicionarCampanaAsincrono() {
		let IdInterno_TarjetaEquipo = $("#NumeroSerie").find(':selected').data('id');

		$.ajax({
			type: "POST",
			data: {
				id_tarjeta_equipo: IdInterno_TarjetaEquipo,
				asincrono: 1, // Asincrono - En la creación.
				solicitud: "Solicitud"
			},
			url: "md_adicionar_campanas.php",
			success: function (response) {
				$('#ContenidoModal2').html(response);
				$('#myModal2').modal("show");
			},
			error: function (error) {
				console.log("error (435), ", error);
			}
		});
	}

	function AdicionarEquipo() {
		window.open(`tarjeta_equipo.php`, "_blank");
	}

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