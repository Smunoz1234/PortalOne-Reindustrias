<style>
	/* SMM, 14/05/2022 */
	.fc-highlight {
		background-color: lightblue !important;
	}
</style>

<?php require_once "includes/conexion.php";

$sw = isset($_GET['FechaInicial']) ? 1 : 0; // Sincrono (Filtrar datos)

$Filtro = "";
$Usuario = $_SESSION['CodUser'] ?? "";

$FechaInicial = $_GET['FechaInicial'] ?? date('Y-m-d');
$FechaFinal = $_GET['FechaFinal'] ?? date('Y-m-d');

$Cliente = $_GET['Cliente'] ?? "";
$Filtro .= ($Cliente == "") ? "" : " AND ID_CodigoCliente='$Cliente'";

$Sucursal = $_GET['Sucursal'] ?? ""; // Nombre Sucursal
$Filtro .= ($Sucursal == "") ? "" : " AND NombreSucursal='$Sucursal'";

$Grupo = $_GET['Grupo'] ?? "";
$Filtro .= ($Grupo == "") ? "" : " AND IdCargo='$Grupo'";

$Sede = $_GET['Sede'] ?? "";
$DimSeries = intval(ObtenerVariable("DimensionSeries"));
$Filtro .= ($Sede == "") ? "" : " AND CentroCosto$DimSeries='$Sede'";

// SMM, 17/10/2023
$Recursos = isset($_GET['Recursos']) ? implode(',', $_GET['Recursos']) : "";
// echo "<script> console.log('programacion_solicitudes_calendario.php 33', '$Recursos'); </script>";

// Filtrar técnicos principal y secundario. SMM, 03/11/2023 
$Filtro .= ($Recursos == "") ? "" : " AND ([IdTecnico] IN ($Recursos) OR [CDU_IdTecnicoAdicional] IN ($Recursos))";

if ($sw == 1) { // Si estoy refrescando datos ya cargados

	// Tecnicos para seleccionar
	$ParamRec = array(
		"'$Usuario'",
		"'$Sede'",
		"'$Grupo'",
		"'$Recursos'",
	);
	$SQL_Recursos = EjecutarSP("sp_ConsultarTecnicos", $ParamRec);

	// SMM, 17/10/2023
	$Cons = "SELECT * FROM [uvw_tbl_SolicitudLlamadasServicios_Calendario] WHERE (FechaCreacionLLamada BETWEEN '$FechaInicial' AND '$FechaFinal') $Filtro";
    $SQL_Actividades = sqlsrv_query($conexion, $Cons);

	// SMM, 18/10/2023 
	// echo $Cons;
	// echo $Recursos;

}

// Grupos de Empleados, SMM 16/05/2022
$SQL_GruposUsuario = Seleccionar("uvw_tbl_UsuariosGruposEmpleados", "*", "[ID_Usuario]='$Usuario'", 'DeCargo');

$ids_grupos = array();
while ($row_GruposUsuario = sqlsrv_fetch_array($SQL_GruposUsuario)) {
	$ids_grupos[] = $row_GruposUsuario['IdCargo'];
}

$ids_recursos = array();
?>

<style>
	.fc-view-harness > div:not(.fc-view) {
    	/* 
		Estilos para el hijo que no tiene la clase "fc-view" dentro del contenedor con la clase "fc-view-harness" 
		Nota: También conocido como el botón que no tengo idea de donde salió.
		*/
		display: none;
	}
</style>

<div id="calendario"></div>

<script>
	$(document).ready(function () {

		/* initialize the calendar
		 -----------------------------------------------------------------*/
		var CalendarJS = FullCalendar.Calendar;
		var Draggable = FullCalendar.Draggable;

		var containerEl = document.getElementById('dvOT');
		var calendarEl = document.getElementById('calendario');

		var fechaActual = "<?php echo $FechaInicial; ?>";
		// isset($_GET['reload']) -> fechaActual = window.sessionStorage.getItem('CurrentDateCalendar');

		var vistaActual = window.sessionStorage.getItem('CurrentViewCalendar');
		if (!vistaActual) {
			vistaActual = "dayGridMonth"
		} else {
			console.log(vistaActual);
		}

		var visualizarFechasActual = true;
		if (window.sessionStorage.getItem('DateAboveResources') === "false") {
			visualizarFechasActual = false
		}

		// initialize the external events
		// -----------------------------------------------------------------

		new Draggable(containerEl, {
			itemSelector: '.item-drag',
			eventData: function (eventEl) {
				// console.log("eventData.dataset", eventEl.dataset);
				// console.log("eventData.dataset.comentario", eventEl.dataset.comentario);

				// Stiven Muñoz Murillo, 07/02/2022
				let minutos = eventEl.dataset.tiempo;
				var m = minutos % 60;
				var h = (minutos - m) / 60;
				var tiempo = h.toString() + ":" + (m < 10 ? "0" : "") + m.toString();
				// console.log(tiempo);

				var new_id = 0;
				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: { type: 26 },
					dataType: 'json',
					async: false,
					success: function (data) {
						new_id = data.NewID;
					}
				});
				return {
					id: new_id,
					title: eventEl.dataset.title,
					comentario: eventEl.dataset.comentario, // SMM, 03/05/2022
					duration: (minutos == "") ? '02:00' : tiempo // SMM, 07/02/2022
				};
			}
		});

		//Identificar si un evento fue copiado
		var copiado = false;

		calendar = new CalendarJS(calendarEl, {
			locale: 'es',
			themeSystem: 'bootstrap',
			headerToolbar: {
				left: 'prev,next today',
				center: 'title',
				right: 'dayGridMonth,resourceTimeGridWeek,resourceTimeGridDay'
			},
			buttonText: {
				today: 'Hoy',
				month: 'Mes',
				week: 'Semana',
				day: 'Día'
			},
			initialView: vistaActual,
			initialDate: fechaActual,
			datesSet: function (dateInfo) {
				//					console.log(dateInfo)
				window.sessionStorage.setItem('CurrentViewCalendar', dateInfo.view.type)
				window.sessionStorage.setItem('CurrentDateCalendar', dateInfo.startStr.substring(0, 10))
			},
			datesAboveResources: visualizarFechasActual,
			editable: true,
			//				selectable: true,
			droppable: true,
			//				drop: function(info){
			//					//console.log("Drop",info.resource.id)
			//					//console.log(info)
			//					//console.log(info.draggedEl.parentNode)
			//					debugger;
			//					if(info.draggedEl.parentNode){
			//						info.draggedEl.parentNode.removeChild(info.draggedEl)
			//					}else{
			//						$(info.draggedEl).remove()
			//					}
			//
			//					debugger;
			//				},
			//				eventDragStop:function(info) {
			//					console.log(info)
			//				},
			views: {
				dayGridMonth: {
					selectable: true
				}
			},
			resources: [
				<?php while ($row_Recursos = sqlsrv_fetch_array($SQL_Recursos)) { ?>
					<?php if ((count($ids_grupos) == 0) || in_array($row_Recursos['IdCargo'], $ids_grupos)) { ?>
						<?php $ids_recursos[] = $row_Recursos['ID_Empleado']; ?>
						
						{
							id: '<?php echo $row_Recursos['ID_Empleado']; ?>',
							title: '<?php echo $row_Recursos['NombreEmpleado'] . ' (' . $row_Recursos['DeCargo'] . ')'; ?>'
						},
					<?php } elseif (PermitirFuncion(321)) { ?>
						{
							id: '<?php echo $row_Recursos['ID_Empleado']; ?>',
							title: '<?php echo $row_Recursos['NombreEmpleado'] . ' [BLOQUEADO]'; ?>'
						},
					<?php } ?>
				<?php } ?>	
			],
			// SMM, 17/05/2022
			eventConstraint: {
				resourceIds: [<?php echo implode(",", $ids_recursos); ?>]
			},
			resourceOrder: 'title',
			// SMM, 30/10/2023
			dateClick: function (info) {
				console.log("Se ejecuto el evento dateClick");

				// Vista mes.
				if (info.view.type === "dayGridMonth") {
					// Ir a vista día.
					calendar.changeView("resourceTimeGridDay", info.dateStr);
				} else if (info.view.type === "resourceTimeGridDay") {
					// Cargando.
					blockUI();
					
					// Obtener la fecha y hora en objetos separados
					let recurso = info.resource.id;
					let fechaSeleccionada = new Date(info.dateStr);
					// let fecha = fechaSeleccionada.toLocaleDateString(); // Formato de fecha
    				// let hora = fechaSeleccionada.toLocaleTimeString(); // Formato de hora

					let fecha = fechaSeleccionada.toISOString().split('T')[0]; // Formato Y-m-d
					let hora = `${fechaSeleccionada.getHours().toString().padStart(2, '0')}:${fechaSeleccionada.getMinutes().toString().padStart(2, '0')}`; // Formato H:i

					// Imprimir la fecha, hora y recurso seleccionado por el usuario
					console.log("Fecha seleccionada (Y-m-d): " + fecha);
					console.log("Hora seleccionada (H:i): " + hora);
    				console.log("Recurso seleccionado: " + recurso);

					// Agregar nueva Solicitud.
					$.ajax({
						type: "GET",
						url: `programacion_solicitudes_actividad.php?recurso=${recurso}&fecha=${fecha}&hora=${hora}`,
						success: function (response) {
							$('#ContenidoModal').html(response);
							$('#ModalAct').modal("show");
							
							// Quitar cargando.
							blockUI(false);
						},
						error: function (error) {
							console.log("error (230), ", error);

							// Quitar cargando.
							blockUI(false);
						}
					});
				}
			},
			// Seleccionar solamente un día del mes. SMM, 14/05/2022
			selectAllow: function (e) {
				console.log("selectAllow");
				if (e.end.getTime() / 1000 - e.start.getTime() / 1000 <= 86400) {
					return true;
				}
			},
			eventWillUnmount: function (info) {
				console.log('Se ejecuto eventWillUnmount en el calendario');
				$('.tooltip').remove();
			},
			eventDidMount: function (info) {
				// console.log("info.event", info.event)
				console.log('Se ejecuto eventDidMount en el calendario');

				// SMM, 10/03/2022
				$(info.el).tooltip({
					title: `${info.event.title} "${info.event.extendedProps.comentario}"` // SMM, 03/05/2022
					, animation: false
					, placement: "right"
				});

				if (info.view.type != 'dayGridMonth') {
					let cont = info.el.getElementsByClassName('fc-event-time')//fc-event-title-container

					// console.log(info.event.extendedProps.estadoLlamadaServ);
					// console.log(cont[0]);

					if (cont[0] !== undefined) {

						//-3 Abierto, -2 Pendiente, -1 Cerrado
						if (info.event.extendedProps.estadoLlamadaServ === undefined) {//Cuando se agrega por primera vez haciendo drop
							cont[0].insertAdjacentHTML('beforeend', '<i class="fas fa-door-open pull-right" title="Llamada de servicio abierta"></i>')
						} else if (info.event.extendedProps.estadoLlamadaServ == '-3') {
							cont[0].insertAdjacentHTML('beforeend', '<i class="fas fa-door-open pull-right" title="Llamada de servicio abierta"></i>')
						} else if (info.event.extendedProps.estadoLlamadaServ == '-2') {
							cont[0].insertAdjacentHTML('beforeend', '<i class="fas fa-clock pull-right" title="Llamada de servicio pendiente"></i>')
						} else if (info.event.extendedProps.estadoLlamadaServ == '-1') {
							cont[0].insertAdjacentHTML('beforeend', '<i class="fas fa-door-closed pull-right" title="Llamada de servicio cerrada"></i>')
						} else if (info.event.extendedProps.estadoLlamadaServ == '') {//No tiene llamada de servicio
							cont[0].insertAdjacentHTML('beforeend', '<i class="fas fa-unlink pull-right" title="Actividad sin llamada de servicio asociada"></i>')
						}

						//Si tiene llamada de servicio
						if (info.event.extendedProps.llamadaServicio === undefined || info.event.extendedProps.llamadaServicio !== '0') {//Cuando se agrega por primera vez haciendo drop
							cont[0].insertAdjacentHTML('beforeend', '<i class="fas fa-phone-square-alt mr-1 pull-right" title="Tiene asociada una llamada de servicio"></i>')
						}

					} else {
						console.error("info.el.getElementsByClassName('fc-event-time') === undefined");
					}
				}
			},
			events: [
				<?php if ($sw == 1) { ?>
					<?php while ($row_Actividad = sqlsrv_fetch_array($SQL_Actividades)) {
						$classAdd = "";
						if (isset($row_Actividad['CDU_IdTecnicoAdicional']) && ($row_Actividad['CDU_IdTecnicoAdicional'] != "")) {
							$classAdd = "'event-striped'";
						}

						/*
						if ($row_Actividad['IdEstadoLlamada'] == '-2') { //Llamada pendiente
							$classAdd .= ",'event-pend'";
						}
						*/

						$Cliente = $row_Actividad["NombreClienteLlamada"] ?? "";
						$Sucursal = $row_Actividad["NombreSucursal"] ?? "";
						$SI_TE = $row_Actividad["IdNumeroSerie"] ?? "";
						$Marca = $row_Actividad["DeMarcaVehiculo"] ?? "";
						$Linea = $row_Actividad["DeLineaModeloVehiculo"] ?? "";
						$ID_Agenda = $row_Actividad["ID_SolicitudLlamadaServicio"] ?? "";

						$TecnicoAsesor = $row_Actividad["NombreTecnicoAsesor"] ?? "";
						$TecnicoAdicional = $row_Actividad["NombreTecnicoAdicional"] ?? "";

						$EtiquetaActividad = "#$ID_Agenda ($Cliente - $Sucursal) ($SI_TE - $Marca, $Linea) [$TecnicoAsesor - $TecnicoAdicional]";

						// Bloqueado -> "red". SMM, 02/11/2023
						$ColorBorde = in_array($row_Actividad["IdTecnico"], $ids_recursos) ? $row_Actividad['ColorEstadoServicioLlamada'] : "red";
						$ColorBordeAdicional = in_array($row_Actividad["CDU_IdTecnicoAdicional"], $ids_recursos) ? $row_Actividad['ColorEstadoServicioLlamada'] : "red";
						?>
						
						{
							id: '<?php echo $row_Actividad['ID_SolicitudLlamadaServicio']; ?>',
							title: '<?php echo $EtiquetaActividad; ?>',
							comentario: '<?php echo preg_replace('([^A-Za-z0-9 ])', '', $row_Actividad['ComentarioLlamada']); ?>',
							start: '<?php echo $row_Actividad["FechaCreacion"]->format("Y-m-d H:i"); ?>',
							end: '<?php echo $row_Actividad["FechaFinCreacion"]->format("Y-m-d H:i"); ?>',
							resourceId: '<?php echo $row_Actividad['IdTecnico']; ?>',
							textColor: '#FFF',
							backgroundColor: '<?php echo $row_Actividad['ColorEstadoServicioLlamada']; ?>',
							borderColor: '<?php echo $ColorBorde; ?>',
							<?php if (($ColorBorde == "red") || true) { ?>
								startEditable: false,
								durationEditable: false,
								resourceEditable: false,
							<?php } ?>
							classNames: [<?php echo $classAdd; ?>]

							/*
							tl: '<?php echo ($row_Actividad['IdActividadPortal'] == 0) ? 1 : 0; ?>',
							estado: '<?php echo $row_Actividad['IdEstadoActividad']; ?>',
							tipoEstado: '<?php echo $row_Actividad['DeTipoEstadoActividad'] ?? ""; ?>',
							llamadaServicio: '<?php echo $row_Actividad['ID_LlamadaServicio']; ?>',
							estadoLlamadaServ: '<?php echo $row_Actividad['IdEstadoLlamada']; ?>',
							informacionAdicional: '<?php echo $row_Actividad['InformacionAdicional']; ?>',
							manualChange: '0',
							*/
						},

						// SMM, 23/10/2023
						<?php if(isset($row_Actividad['CDU_IdTecnicoAdicional']) && ($row_Actividad['CDU_IdTecnicoAdicional'] != "")) { ?>
							{
								id: '<?php echo $row_Actividad['ID_SolicitudLlamadaServicio']; ?>',
								title: '<?php echo $EtiquetaActividad; ?>',
								comentario: '<?php echo preg_replace('([^A-Za-z0-9 ])', '', $row_Actividad['ComentarioLlamada']); ?>',
								start: '<?php echo $row_Actividad["FechaAgenda"]->format('Y-m-d H:i'); ?>',
								end: '<?php echo $row_Actividad["FechaFinAgenda"]->format('Y-m-d H:i'); ?>',
								resourceId: '<?php echo $row_Actividad['CDU_IdTecnicoAdicional']; ?>',
								textColor: '#FFF',
								backgroundColor: '<?php echo $row_Actividad['ColorEstadoServicioLlamada']; ?>',
								borderColor: '<?php echo $ColorBordeAdicional; ?>',
								<?php if (($ColorBordeAdicional == "red") || true) { ?>
									startEditable: false,
									durationEditable: false,
									resourceEditable: false,
								<?php } ?>	
								classNames: ['event-striped']
							},
						<?php } ?>
					<?php } ?>
				<?php } ?>
			],
			eventDrop: function (info) {
				console.log('Se ejecuto eventDrop en el calendario');
				// console.log("eventDrop [CTRL]", info);

				//Cuando se va a duplicar con la tecla CTRL
				if (info.jsEvent.ctrlKey) {
					copiado = true;
					var new_id = 0;
					$.ajax({
						url: "ajx_buscar_datos_json.php",
						data: { type: 26 },
						dataType: 'json',
						async: false,
						success: function (data) {
							new_id = data.NewID;
						}
					});
					var data = {
						id: new_id,
						title: info.event.title,
						start: info.event.start,
						end: info.event.end,
						resourceId: info.event.getResources()[0].id,
						textColor: '#fff',
						backgroundColor: info.event.backgroundColor,
						borderColor: info.event.borderColor,
						extendedProps: {}
					}
					$.ajax({
						type: "GET",
						url: "includes/procedimientos.php?type=31&id_actividad=" + new_id + "&id_evento=" + $("#IdEvento").val() + "&llamada_servicio=" + info.event.extendedProps.llamadaServicio + "&id_empleadoactividad=" + info.event.getResources()[0].id + "&fechainicio=" + info.event.startStr.substring(0, 10) + "&horainicio=" + info.event.startStr.substring(11, 16) + "&fechafin=" + info.event.endStr.substring(0, 10) + "&horafin=" + info.event.endStr.substring(11, 16) + "&sptype=1&metodo=1&docentry=&comentarios_actividad=&estado=&id_tipoestadoact=&fechainicio_ejecucion=&horainicio_ejecucion=&fechafin_ejecucion=&horafin_ejecucion=&turno_tecnico=&id_asuntoactividad=&titulo_actividad=",
						async: false,
						success: function (response) {
							if (isNaN(response)) {
								Swal.fire({
									title: '¡Advertencia!',
									text: 'No se pudo insertar la actividad en la ruta',
									icon: 'warning',
								});
							} else {
								$("#btnGuardar").prop('disabled', false);
								$("#btnPendientes").prop('disabled', false);
								//									data.extendedProps.id = response;
								data.estado = 'N';
								data.llamadaServicio = info.event.extendedProps.llamadaServicio;
								data.manualChange = '0'
								calendar.addEvent(data);
								//									var dev = calendar.addEvent(data);
								//									console.log("Dev: ",dev)
								info.revert()
								//									console.log("newEvent: ",info.event)
								console.log("Se ejecuto eventDrop duplicando.")
								mostrarNotify('Se ha duplicado una actividad')
							}
							copiado = false;
							//							console.log(response)
						}
					});
				} else {//Cuando se mueve el evento a otro lado sin duplicarlo

					copiado = false;
					var ID;
					var docentry;
					var metodo;
					var estado = 'Y'; //Cerrado
					var manual;

					// console.log("evenDrop (copiado=false)", info.event)
					if ((!info.event.extendedProps.tl) || (info.event.extendedProps.tl == 0)) {
						ID = info.event.id //info.event.extendedProps.id
						estado = info.event.extendedProps.estado
						docentry = 0
						metodo = 1
						manual = info.event.extendedProps.manualChange
					} else {
						ID = info.event.id
						estado = info.event.extendedProps.estado
						docentry = ID
						metodo = 2
						manual = info.event.extendedProps.manualChange
					}

					let tipoEstado = info.event.extendedProps.tipoEstado;

					// console.log(estado)
					console.log("tipoEstado", tipoEstado);

					if (tipoEstado === 'INICIADA' && copiado === false) { // SMM, 07/03/2023
						info.revert()
						Swal.fire({
							title: '¡Advertencia!',
							text: 'La actividad se encuentra INICIADA. No puede ejecutar esta acción.',
							icon: 'warning',
						});
					} else if (estado === 'Y' && copiado === false) {
						info.revert()
						Swal.fire({
							title: '¡Advertencia!',
							text: 'La actividad se encuentra cerrada. No puede ejecutar esta acción.',
							icon: 'warning',
						});
					} else if (copiado === true) {
						//info.revert()
					} else {
						//Validar si la información se está cambiando en la ventana modal de la actividad. Si es así no modifico nada por este callback.
						//0-> Se está modificando en el calendario
						//1-> Se está modificando en el modal
						if (manual == '0') {
							$.ajax({
								type: "GET",
								url: "includes/procedimientos.php?type=31&id_actividad=" + ID + "&id_evento=" + $("#IdEvento").val() + "&llamada_servicio=&id_empleadoactividad=" + info.event.getResources()[0].id + "&fechainicio=" + info.event.startStr.substring(0, 10) + "&horainicio=" + info.event.startStr.substring(11, 16) + "&fechafin=" + info.event.endStr.substring(0, 10) + "&horafin=" + info.event.endStr.substring(11, 16) + "&sptype=2&metodo=" + metodo + "&docentry=" + docentry + "&comentarios_actividad=&estado=&id_tipoestadoact=&fechainicio_ejecucion=&horainicio_ejecucion=&fechafin_ejecucion=&horafin_ejecucion=&turno_tecnico=&id_asuntoactividad=&titulo_actividad=",
								success: function (response) {
									//							console.log(response)
									if (response != "OK") {
										info.revert()
										Swal.fire({
											title: '¡Advertencia!',
											text: 'No se pudo actualizar la actividad en la ruta',
											icon: 'warning',
										});
									} else {
										$("#btnGuardar").prop('disabled', false);
										$("#btnPendientes").prop('disabled', false);
									}
									console.log("Se ejecuto eventDrop.")
									mostrarNotify('Se ha editado una actividad')
								}
							});
						}
					}
				}
			},
			eventResize: function (info) {
				console.log('Se ejecuto eventResize en el calendario');

				var ID;
				var docentry;
				var metodo;
				var estado = 'Y'; //Cerrado
				var manual;

				// console.log("eventResize", info.event)
				//					console.log("Copiado",copiado)
				//					console.log("tl",info.event.extendedProps.tl)
				if ((!info.event.extendedProps.tl) || (info.event.extendedProps.tl == 0)) {
					ID = info.event.id //info.event.extendedProps.id
					estado = info.event.extendedProps.estado
					docentry = 0
					metodo = 1
					manual = info.event.extendedProps.manualChange
					//						console.log("Entro en 1")
					//						console.log("ID",ID)
				} else {
					ID = info.event.id
					estado = info.event.extendedProps.estado
					docentry = ID
					metodo = 2
					manual = info.event.extendedProps.manualChange
					//						console.log("Entro en 2")
					//						console.log("ID",ID)
				}
				//					console.log(estado)
				if (estado === 'Y' && copiado === false) {
					info.revert()
					Swal.fire({
						title: '¡Advertencia!',
						text: 'La actividad se encuentra cerrada. No puede ejecutar esta acción.',
						icon: 'warning',
					});
				} else {
					//Validar si la información se está cambiando en la ventana modal de la actividad. Si es así no modifico nada por este callback.
					//0-> Se está modificando en el calendario
					//1-> Se está modificando en el modal
					//						console.log("Manual",manual)
					if (manual == '0') {
						$.ajax({
							type: "GET",
							url: "includes/procedimientos.php?type=31&id_actividad=" + ID + "&id_evento=" + $("#IdEvento").val() + "&llamada_servicio=&id_empleadoactividad=" + info.event.getResources()[0].id + "&fechainicio=" + info.event.startStr.substring(0, 10) + "&horainicio=" + info.event.startStr.substring(11, 16) + "&fechafin=" + info.event.endStr.substring(0, 10) + "&horafin=" + info.event.endStr.substring(11, 16) + "&sptype=2&metodo=" + metodo + "&docentry=" + docentry + "&comentarios_actividad=&estado=&id_tipoestadoact=&fechainicio_ejecucion=&horainicio_ejecucion=&fechafin_ejecucion=&horafin_ejecucion=&turno_tecnico=&id_asuntoactividad=&titulo_actividad=",
							success: function (response) {
								//							console.log(response)
								if (response != "OK") {
									info.revert()
									Swal.fire({
										title: '¡Advertencia!',
										text: 'No se pudo actualizar la actividad en la ruta',
										icon: 'warning',
									});
								} else {
									$("#btnGuardar").prop('disabled', false);
									$("#btnPendientes").prop('disabled', false);
									mostrarNotify('Se ha editado una actividad')
								}
								console.log("Se ejecuto eventResize.")
							}
						});
					}
				}
			},
			//eventChange: function(info){},
			eventReceive: function (info) {
				console.log('Se ejecuto eventReceive en el calendario');

				/*
				console.log(info)
				console.log(info.event)
				console.log(info.event.id)
				console.log(info.event.startStr)
				console.log(info.event.endStr)
				console.log(info.draggedEl.dataset.docnum)
				console.log($("#IdEvento").val())
				console.log(info.event.getResources()[0].id)
				*/

				if (info.draggedEl.parentNode) {
					info.draggedEl.parentNode.removeChild(info.draggedEl)
					$.ajax({
						type: "GET",
						url: "includes/procedimientos.php?type=31&id_actividad=" + info.event.id + "&id_evento=" + $("#IdEvento").val() + "&llamada_servicio=" + info.draggedEl.dataset.docnum + "&id_empleadoactividad=" + info.event.getResources()[0].id + "&fechainicio=" + info.event.startStr.substring(0, 10) + "&horainicio=" + info.event.startStr.substring(11, 16) + "&fechafin=" + info.event.endStr.substring(0, 10) + "&horafin=" + info.event.endStr.substring(11, 16) + "&sptype=1&metodo=1&docentry=&comentarios_actividad=&estado=&id_tipoestadoact=&fechainicio_ejecucion=&horainicio_ejecucion=&fechafin_ejecucion=&horafin_ejecucion=&turno_tecnico=&id_asuntoactividad=&titulo_actividad=",
						success: function (response) {
							if (isNaN(response)) {
								Swal.fire({
									title: '¡Advertencia!',
									text: 'No se pudo insertar la actividad en la ruta. Respuesta: ' + response,
									icon: 'warning',
								});
							} else {
								$("#btnGuardar").prop('disabled', false);
								$("#btnPendientes").prop('disabled', false);

								// info.event.setExtendedProp('id',response)
								info.event.setExtendedProp('estado', 'N')
								info.event.setExtendedProp('llamadaServicio', info.draggedEl.dataset.docnum)
								info.event.setExtendedProp('estadoLlamadaServ', info.draggedEl.dataset.estado)
								info.event.setExtendedProp('informacionAdicional', info.draggedEl.dataset.info)
								info.event.setExtendedProp('manualChange', '0')

								mostrarNotify('Se ha agregado una nueva actividad')
							}
							//							console.log(response)
						}
					});
				} else {
					//						console.log(info)
					info.event.remove()
				}
			},
			eventClick: function (info) {
				console.log('Se ejecuto eventClick en el calendario');
				
				// console.log(info.event.title)
				// console.log('ID',btoa(info.event.id));
				// console.log('info',info);
				// console.log('eP:',info.event.extendedProps);

				// window.open(`solicitud_llamada.php?id=${btoa(info.event.id)}&tl=1`, "_blank");

				// Cargando.
				blockUI();

				// Editar Solicitud.
				$.ajax({
					type: "GET",
					url: `programacion_solicitudes_actividad.php?id=${info.event.id}`,
					success: function (response) {
						$('#ContenidoModal').html(response);
						$('#ModalAct').modal("show");
						
						// Quitar cargando.
						blockUI(false);
					},
					error: function (error) {
						console.log("error (230), ", error);

						// Quitar cargando.
						blockUI(false);
					}
				});
			},
			height: 'auto', // will activate stickyHeaderDates automatically!
			contentHeight: 'auto',
			dayMinWidth: 150, // will cause horizontal scrollbars
		});
		//			console.log(calendar)
		calendar.render();
	});

</script>