<?php require_once "includes/conexion.php";
// PermitirAcceso(312);
$sw = 0;

$ParamSucursal = array(
	"'" . $_SESSION['CodUser'] . "'",
);
$SQL_Suc = EjecutarSP('sp_ConsultarSucursalesUsuario', $ParamSucursal);

$SQL_EstadoServicios = Seleccionar("tbl_SolicitudLlamadasServiciosEstadoServicios", "*");

//$i=0;
$FilRec = "";
//$Filtro="";

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
	$sw = 1;
} else {
	//sumar 7 dias a la fecha actual
	$fecha = date('Y-m-d');
	$nuevafecha = strtotime('+' . ObtenerVariable("DiasRangoFechasDocSAP") . ' day');
	$nuevafecha = date('Y-m-d', $nuevafecha);
	$FechaFinal = $nuevafecha;
}

$Sede = isset($_GET['Sede']) ? $_GET['Sede'] : "";
$Grupo = isset($_GET['Grupo']) ? $_GET['Grupo'] : "";
$Recurso = isset($_GET['Recursos']) ? implode(',', $_GET['Recursos']) : ""; // SMM

$Cliente = isset($_GET['Cliente']) ? $_GET['Cliente'] : "";
$NomSucursal = isset($_GET['Sucursal']) ? $_GET['Sucursal'] : "";

//Lista de cargos de recursos (Tecnicos)
$SQL_CargosRecursos = Seleccionar('uvw_Sap_tbl_Recursos', 'DISTINCT IdCargo, DeCargo', "CentroCosto2='" . $Sede . "'");

//Lista de recursos (Tecnicos)
$ParamRec = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'" . $Sede . "'",
	"'" . $Grupo . "'",
);

$SQL_Recursos = EjecutarSP("sp_ConsultarTecnicos", $ParamRec);

if (isset($_GET['Recursos']) && $_GET['Recursos'] != "") {
	$FilRec = implode(',', $_GET['Recursos']);
	$sw = 1;
}

//Consultar actividades
if ($sw == 1) {
	// Recursos, 22/01/2022
	$all_resources = array();
	$sql_resources = EjecutarSP("sp_ConsultarTecnicos", $ParamRec);
	while ($row_resources = sqlsrv_fetch_array($sql_resources)) {
		array_push($all_resources, $row_resources['ID_Empleado']);
	}
	$resource = implode(',', $all_resources);
	// Sin uso actualmente, contiene todos los técnicos.
	// Stiven Muñoz Murillo
}
?>

<!DOCTYPE html>
<html class="light-style">

<head>
	<?php include "includes/cabecera_new.php"; ?>
	<title>Programación de solicitudes</title>
	<style>
		body,
		html {
			font-size: 13px;
			background: #f5f5f5;
		}

		.ps__thumb-y {
			height: 15px !important;
		}

		.event-striped {
			background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
			background-size: .75rem .75rem;
		}

		.event-pend {
			border-color: orange !important;
			border-width: 3px !important;
			border-style: solid !important;
		}

		.swal2-container {
			z-index: 9000;
		}

		.custom-card {
            color: white;
			text-align: center;
        }

        .custom-card-header {
            font-weight: bold;
			border-bottom: 2px solid white;
        }

		.collapse-icon {
			border: 1px solid gray;
			padding: 10px;
			border-radius: 5px;
		}

		.datepicker-switch {
			background-color: lightgray;
			text-align: center;
		}

		#small-calendar .table-condensed {
			width: 100%;
			border: 1px solid black;
		}

		#small-calendar .day,
		#small-calendar .prev,
		#small-calendar .next  {
			cursor: pointer;
		}

		#small-calendar .today,
		#small-calendar .prev,
		#small-calendar .next {
			background-color: #007bff !important;
			color: #fff !important;
		}

		#small-calendar .prev:hover,
		#small-calendar .next:hover {
			background-color: #0056b3 !important;
		}
	</style>

	<script type="text/javascript">
		$(document).ready(function () {//Cargar los almacenes dependiendo del proyecto
			$("#NombreCliente").change(function () {
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
					url: "ajx_cbo_sucursales_clientes_simple.php?CardCode=" + Cliente.value + "&tdir=S",
					success: function (response) {
						$('#Sucursal').html(response).fadeIn();
					}
				});
			});

			$("#Sede").change(function () {
				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=37&id=" + document.getElementById('Sede').value,
					success: function (response) {
						$('#Grupo').html(response);
					}
				});

				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=27&bloquear=<?php echo PermitirFuncion(321) ? 0 : 1; ?>&id=" + document.getElementById('Sede').value,
					success: function (response) {
						$('#Recursos').html(response);
						$("#Recursos").trigger("change");
					}
				});
			});

			$("#Grupo").change(function () {
				var grupo = document.getElementById('Grupo').value;
				if (grupo != "") {
					$.ajax({
						type: "POST",
						url: "ajx_cbo_select.php?type=38&id=" + document.getElementById('Sede').value + "&grupo=" + document.getElementById('Grupo').value,
						success: function (response) {
							$('#Recursos').html(response);
							$("#Recursos").trigger("change");
						}
					});
				} else {
					$.ajax({
						type: "POST",
						url: "ajx_cbo_select.php?type=27&bloquear=<?php echo PermitirFuncion(321) ? 0 : 1; ?>&id=" + document.getElementById('Sede').value,
						success: function (response) {
							$('#Recursos').html(response);
							$("#Recursos").trigger("change");
						}
					});
				}
			});
		});
	</script>

<script>
// Configura el idioma de Bootstrap Datepicker a español
$.fn.datepicker.dates['es'] = {
	days: ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"],
	daysShort: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"],
	daysMin: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sá"],
	months: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
	monthsShort: ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"],
	today: "Hoy",
	clear: "Borrar",
	format: "yyyy-mm-dd",
	titleFormat: "MM yyyy"
};

// Inicializa el DatePicker
$(function () {
	$("#small-calendar").datepicker({
		language: "es",
		todayHighlight: true,
		keyboardNavigation: false
	});

	$(".datepicker-switch").on("click", function() { 
		return false; 
	});

	$("#small-calendar").datepicker("update", "<?php echo $_GET['FechaInicial'] ?? ""; ?>");

	$("#small-calendar .prev").on("click", function() {
		$(".fc-dayGridMonth-button").trigger("click");

		setTimeout(() => {
			$(".fc-prev-button").trigger("click");	
		}, 250);
	});

	$("#small-calendar .next").on("click", function() {
		$(".fc-dayGridMonth-button").trigger("click");

		setTimeout(() => {
			$(".fc-next-button").trigger("click");
		}, 250);
	});
});
</script>
					

</head>

<body>
	<div class="container-fluid">
		<!-- Event modal -->
		<div class="modal inmodal fade" id="ModalAct" data-backdrop="static" data-keyboard="false">
			<div id="ContenidoModal" class="modal-dialog modal-xl">
				<!-- Contenido generado por JS -->
			</div>
		</div>
		<!-- / Event modal -->

		<div class="modal inmodal fade" id="myModal2" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-lg" style="width: 70% !important;">
				<div class="modal-content" id="ContenidoModal2">
					<!-- Contenido generado por JS -->
				</div>
			</div>
		</div>
		<!-- /#MyModal2 -->

		<div id="dvHead" class="row mb-md-3 mt-md-4">
			<div id="accordionTitle" class="card col-lg-12 p-md-4">
				<div class="pt-3 pr-3 pl-3 pb-0 mb-2 bg-primary text-white">
					<a class="d-flex justify-content-between text-white" data-toggle="collapse" aria-expanded="true"
						href="#accordionTitle-1">
						<h4 class="pr-2"><i class="fas fa-filter"></i> Agregue los filtros necesarios</h4>
						<div class="collapse-icon"></div>
					</a>
				</div>
				<div id="accordionTitle-1" class="collapse show" data-parent="#accordionTitle">
					<!-- Inicio del Formulario -->
					<form action="programacion_solicitudes.php" method="get" class="form-horizontal"
						id="frmProgramacion">
						<div class="form-row">
							<div class="form-group col-lg-3">
								<label class="form-label">Fechas</label>
								<div class="input-group">
									<input name="FechaInicial" type="text" class="form-control" id="FechaInicial"
										value="<?php echo $FechaInicial; ?>" placeholder="YYYY-MM-DD"
										autocomplete="off">
									<span class="input-group-prepend px-2 bg-light text-center pt-2">hasta</span>
									<input name="FechaFinal" type="text" class="form-control" id="FechaFinal"
										value="<?php echo $FechaFinal; ?>" placeholder="YYYY-MM-DD" autocomplete="off">
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label class="form-label">Sede</label>
								<div class="select2-success">
									<select name="Sede" id="Sede" class="select2 form-control">
										<option value="">(TODOS)</option>
										<?php
										while ($row_Suc = sqlsrv_fetch_array($SQL_Suc)) { ?>
											<option value="<?php echo $row_Suc['IdSucursal']; ?>" <?php if ((isset($_GET['Sede']) && ($_GET['Sede'] != "")) && (strcmp($row_Suc['IdSucursal'], $_GET['Sede']) == 0)) {
												   echo "selected=\"selected\"";
											   } ?>>
												<?php echo $row_Suc['DeSucursal']; ?>
											</option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label class="form-label">Cliente</label>
								<input name="Cliente" type="hidden" id="Cliente"
									value="<?php if (isset($_GET['Cliente']) && ($_GET['Cliente'] != "")) {
										echo $_GET['Cliente'];
									} ?>">
								<input name="NombreCliente" type="text" class="form-control" id="NombreCliente"
									placeholder="Ingrese para buscar..."
									value="<?php if (isset($_GET['NombreCliente']) && ($_GET['NombreCliente'] != "")) {
										echo $_GET['NombreCliente'];
									} ?>">
							</div>
							<div class="form-group col-lg-3">
								<label class="form-label">Sucursal</label>
								<div class="select2-success">
									<select name="Sucursal" id="Sucursal" class="select2 form-control">
										<option value="">(TODOS)</option>
										<?php
										if (isset($_GET['Sucursal'])) { //Cuando se ha seleccionado una opción
											if (PermitirFuncion(205)) {
												$Where = "CodigoCliente='" . $_GET['Cliente'] . "' and TipoDireccion='S'";
												$SQL_Sucursal = Seleccionar("uvw_Sap_tbl_Clientes_Sucursales", "NombreSucursal", $Where);
											} else {
												$Where = "CodigoCliente='" . $_GET['Cliente'] . "' and TipoDireccion='S' and ID_Usuario = " . $_SESSION['CodUser'];
												$SQL_Sucursal = Seleccionar("uvw_tbl_SucursalesClienteUsuario", "NombreSucursal", $Where);
											}
											while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) { ?>
												<option value="<?php echo $row_Sucursal['NombreSucursal']; ?>" <?php if (strcmp($row_Sucursal['NombreSucursal'], $_GET['Sucursal']) == 0) {
													   echo "selected=\"selected\"";
												   } ?>>
													<?php echo $row_Sucursal['NombreSucursal']; ?>
												</option>
											<?php }
										} ?>
									</select>
								</div>
							</div>
						</div>
						<div class="form-row">
							<div class="form-group col-lg-3">
								<label class="form-label">Grupo</label>
								<div class="select2-success">
									<select name="Grupo" id="Grupo" class="select2 form-control">
										<option value="">(TODOS)</option>
										<?php
										if ($Sede != "") {
											while ($row_CargosRecursos = sqlsrv_fetch_array($SQL_CargosRecursos)) { ?>
												<option value="<?php echo $row_CargosRecursos['IdCargo']; ?>" <?php if ((isset($_GET['Grupo']) && ($_GET['Grupo'] != "")) && (strcmp($row_CargosRecursos['IdCargo'], $_GET['Grupo']) == 0)) {
													   echo "selected=\"selected\"";
												   } ?>>
													<?php echo $row_CargosRecursos['DeCargo']; ?>
												</option>
											<?php }
										} ?>
									</select>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label class="form-label">Técnicos/Empleados</label>
								<div class="select2-success">
									<select name="Recursos[]" id="Recursos" class="select2 form-control" multiple
										style="width: 100%" data-placeholder="(TODOS)">
										<?php
										if ($Sede != "") {
											$j = 0;
											while ($row_Recursos = sqlsrv_fetch_array($SQL_Recursos)) { ?>
												<option value="<?php echo $row_Recursos['ID_Empleado']; ?>" <?php if ((isset($_GET['Recursos'][$j]) && ($_GET['Recursos'][$j] != "")) && (strcmp($row_Recursos['ID_Empleado'], $_GET['Recursos'][$j]) == 0)) {
													   echo "selected=\"selected\"";
													   $j++;
												   } ?>>
													<?php echo $row_Recursos['NombreEmpleado']; ?>
												</option>
											<?php }
										} ?>
									</select>
								</div>
							</div>
							<div class="form-group col-lg-3">
								<label class="form-label">&nbsp;</label>
								<button id="btnRefrescar" type="button" onClick="RefresarCalendario();"
									class="btn btn-info mt-4" <?php if ($sw == 0) {
										echo "disabled";
									} ?>><i
										class="fas fa-sync"></i> Refrescar</button>
							</div>
							<div class="form-group col-lg-3">
								<label class="form-label">&nbsp;</label>
								<button id="btnFiltrar" type="submit" class="btn btn-success load mt-4 pull-right"><i
										class="fas fa-filter"></i> Filtrar datos</button>
							</div>
						</div>
					</form>
					<!-- Fin del Formulario -->
				</div>
			</div>
		</div>
		<div class="row">
			<div id="dvOT" class="card col-lg-2" style="max-height: 1110px; min-height: auto;">
				<div class="card mt-lg-3">
					<div class="card-header bg-primary text-white text-center">
						<strong>Visualización</strong>
					</div>
					<div class="card-body">
						<div class="input-group">
							<label class="switcher">
								<input type="checkbox" class="switcher-input"
									id="chkDatesAboveResources" checked="checked">
								<span class="switcher-indicator">
									<span class="switcher-yes"></span>
									<span class="switcher-no"></span>
								</span>
								<span class="switcher-label">Mostrar fechas arriba de los técnicos</span>
							</label>
						</div>
					</div>
				</div>

				<div class="card mt-lg-4">
					<div class="card-header bg-primary text-white text-center">
						<strong>Calendario</strong>
					</div>
					<div id="small-calendar"></div>
				</div>
				
				<div id="accordion1" class="sticky-top mt-lg-4">
					<div class="card mb-2">
						<div class="card-header bg-primary text-white">
							<a class="d-flex justify-content-between text-white" data-toggle="collapse"
								aria-expanded="true" href="#accordion1-1">
								<b class='pr-2'><i class="fas fa-tint"></i> Referencia de colores</b>
								<div class="collapse-icon"></div>
							</a>
						</div>
						<div id="accordion1-1" class="collapse show" data-parent="#accordion1">
							<div class="card-body">
								<?php while ($row_EstadoServicio = sqlsrv_fetch_array($SQL_EstadoServicios)) { ?>
									<div class="card custom-card" style="background-color: <?php echo $row_EstadoServicio["color_estado_servicio_llamada"] ?? ""; ?>;">
										<div class="card-header custom-card-header"><?php echo $row_EstadoServicio["tipo_estado_servicio_sol_llamada"] ?? ""; ?></div>
										<div class="card-body"><?php echo $row_EstadoServicio["descripcion_estado_servicio_llamada"] ?? ""; ?></div>
									</div>
									<br>
								<?php } ?>
							</div>
						</div>
						<!-- /#accordion1-1 -->
					</div>
				</div>
				<!-- /#accordion1 -->
			</div>

			<div id="dvCal" class="card card-body col-lg-10">
				<div class="row">
					<div class="form-group col-lg-12">
						<button type="button" class="btn icon-btn btn-sm btn-success"
							title="Mostrar/ocultar lista de OTs" onClick="ExpandirPanelLateral();"><span
								class="fa fa-bars"></span></button>
						<button id="btnExpandir" type="button" class="btn icon-btn btn-sm btn-success fa-pull-right"
							title="Expandir calendario" onClick="Expandir();"><span id="iconBtnExpandir"
								class="fas fa-expand-arrows-alt"></span></button>
					</div>
				</div>
				<div id="dv_calendar">
					<?php require_once "programacion_solicitudes_calendario.php"; ?>
				</div>
			</div>
		</div>
	</div>
	<?php require 'includes/pie.php'; ?>
	<script>
		var calendar;

		$(document).ready(function () {
			$("#frmProgramacion").validate({
				submitHandler: function (form) {
					$.ajax({
						url: "ajx_buscar_datos_json.php",
						data: {
							type: 29,
							idEvento: document.getElementById("IdEvento").value
						},
						dataType: 'json',
						async: false,
						success: function (data) {
							if (data.Estado == 1) {
								Swal.fire({
									title: data.Mensaje,
									text: "¿Está seguro que desea continuar?",
									icon: "warning",
									showCancelButton: true,
									confirmButtonText: "Si, confirmo",
									cancelButtonText: "No"
								}).then((result) => {
									if (result.isConfirmed) {
										blockUI();
										window.sessionStorage.removeItem('ResourceList')
										form.submit();
									} else {
										blockUI(false);
									}
								});
							} else {
								blockUI();
								window.sessionStorage.removeItem('ResourceList')
								form.submit();
							}
						}
					});
				}
			});
			$(".select2").select2();

			$(".select2OT").select2({
				dropdownParent: $('#accordion1-1')
			});

			$('#FechaInicial').flatpickr({
				dateFormat: "Y-m-d",
				allowInput: true
			});

			$('#FechaFinal').flatpickr({
				dateFormat: "Y-m-d",
				allowInput: true
			});

			$('#FechaInicioOT').flatpickr({
				dateFormat: "Y-m-d",
				allowInput: true
			});

			$('#FechaFinalOT').flatpickr({
				dateFormat: "Y-m-d",
				allowInput: true
			});

			$(function () {
				new PerfectScrollbar(document.getElementById('dvOT'));
			});


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
						$("#Cliente").val(value).trigger("change");
					}
				}
			};

			$("#NombreCliente").easyAutocomplete(options);

			if (window.sessionStorage.getItem('DateAboveResources') === "false") {
				$("#chkDatesAboveResources").prop("checked", false);
			} else {
				$("#chkDatesAboveResources").prop("checked", true);
			}

			$("#chkDatesAboveResources").change(function () {
				if ($('#chkDatesAboveResources').prop('checked')) {
					window.sessionStorage.setItem('DateAboveResources', true)
					RefresarCalendario()
				} else {
					window.sessionStorage.setItem('DateAboveResources', false)
					RefresarCalendario()
				}
			});
		});
	</script>

	<script>
		function RefresarCalendario() {
			blockUI();
			var Tecnicos = $("#Recursos").val();
			var Grupo = $("#Grupo").val();
			//	console.log(Tecnicos.length);
			if (Tecnicos.length == 0) {
				window.sessionStorage.removeItem('ResourceList')
				//		console.log('Borro')
			} else {
				window.sessionStorage.setItem('ResourceList', Tecnicos)
				//		console.log('Agrego')
			}

			$.ajax({
				type: "POST",
				url: `programacion_solicitudes_calendario.php?type=1&pSede=<?php echo $Sede; ?>&pGrupo=${Grupo}&pTecnicos=${Tecnicos}&sw=<?php echo $sw; ?>&fchinicial=<?php echo $FechaInicial; ?>`,
				success: function (response) {
					$('#dv_calendar').html(response);
					blockUI(false);
				}
			});
		}

		function Expandir(show = false) {
			if (show) {
				$('#dvCal').removeClass("col-lg-12").addClass("col-lg-10");
				$('#dvHead').show();
				$('#dvOT').show();
				$("#btnExpandir").attr("title", "Expandir calendario");
				$("#iconBtnExpandir").removeClass("fas fa-compress-arrows-alt").addClass("fas fa-expand-arrows-alt");
				$("#btnExpandir").attr("onClick", "Expandir();");
			} else {
				$('#dvHead').hide();
				$('#dvOT').hide();
				$('#dvCal').removeClass("col-lg-10").addClass("col-lg-12");
				$("#btnExpandir").attr("title", "Contraer calendario");
				$("#iconBtnExpandir").removeClass("fas fa-expand-arrows-alt").addClass("fas fa-compress-arrows-alt");
				$("#btnExpandir").attr("onClick", "Expandir(true);");

			}
		}

		// SMM, 21/09/2022
		function ExpandirPanelLateral() {
			$('#dvOT').toggle();
			$('#dvCal').toggleClass('col-lg-10 col-lg-12');
		}
	</script>

</html>

<?php sqlsrv_close($conexion); ?>