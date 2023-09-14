<?php
require_once "includes/conexion.php";

// SMM, 13/09/2023
$asincrono = $_POST['asincrono'] ?? "";

// SMM, 25/02/2023
$msg_error_detalle = "";
$parametros_detalle = array();

$coduser = $_SESSION['CodUser'];
$datetime_detalle = FormatoFecha(date('Y-m-d'), date('H:i:s'));

$type_detalle = $_POST['type'] ?? 0;
$id_tarjeta_equipo = $_POST['id_tarjeta_equipo'] ?? "";

$id_llamada_servicio_detalle = $_POST['id_llamada_servicio'] ?? "";
$docentry_llamada_servicio = $_POST['docentry_llamada_servicio'] ?? "";
$id_campana_detalle = $_POST['id_campana'] ?? "";

$id_usuario_creacion_detalle = "'$coduser'";
$fecha_creacion_detalle = "'$datetime_detalle'";
$hora_creacion_detalle = "'$datetime_detalle'";
$id_usuario_actualizacion_detalle = "'$coduser'";
$fecha_actualizacion_detalle = "'$datetime_detalle'";
$hora_actualizacion_detalle = "'$datetime_detalle'";

if ($type_detalle == 1) {
	$msg_error = "No se pudo crear la Campana.";

	$parametros = array(
		$type_detalle,
		$id_llamada_servicio_detalle, // "DocNum"
		$docentry_llamada_servicio,
		"NULL", // @linea
		"'$id_campana_detalle'",
		"NULL", // @ids_campanas
		$id_usuario_actualizacion_detalle,
		$fecha_actualizacion_detalle,
		$hora_actualizacion_detalle,
		$id_usuario_creacion_detalle,
		$fecha_creacion_detalle,
		$hora_creacion_detalle,
	);

} elseif ($type_detalle == 3) {
	$msg_error = "No se pudo eliminar la Campana.";

	$parametros = array(
		$type_detalle,
		$id_llamada_servicio_detalle, // "DocNum"
		$docentry_llamada_servicio,
		"NULL", // @linea
		"'$id_campana_detalle'",
	);
}

if ($type_detalle != 0) {
	$SQL_Operacion = EjecutarSP("sp_tbl_LlamadasServicios_Campanas", $parametros);

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

		// console.log(Check);

		json.forEach(function (element, index) {
			if (json[index] == DocNum) {
				sw = index;
			}

			// console.log(element, index);
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

				<div class="row">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="5" maxlength="3000" class="form-control" id="Comentarios"
							type="text"></textarea>
					</div>
				</div>
				<!-- /.row -->
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
		<?php if ($asincrono == 1) { ?>
			// Obtener las opciones seleccionadas como objetos de opciones
			let opcionesSeleccionadas = $("#Campanas option:selected");

			// Recorrer las opciones seleccionadas con forEach
			opcionesSeleccionadas.each(function () {
				let opcion = $(this).val();

				// Seleccionar el checkbox
    			$(`#chkSelOT${opcion}`).prop("checked", true);
				SeleccionarCampana(opcion);
			});
		<?php } ?>

		$("#frmCampanasDetalle").validate({
			submitHandler: function (form) {
				console.log(json); // json, viene de las funciones "seleccionar".

				Swal.fire({
					title: "¿Está seguro que desea continuar?",
					icon: "question",
					showCancelButton: true,
					confirmButtonText: "Si, confirmo",
					cancelButtonText: "No"
				}).then((result) => {
					if (result.isConfirmed) {
						<?php if ($asincrono == 1) { ?>
							AdicionarCampanasAsincrono();
						<?php } else { ?>
							AdicionarCampanas();
						<?php } ?>
					}
				});
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
			order: [[0, "asc"]]
		});
	});

	function AdicionarCampanas() {
		var validarAjax = true;
		var contadorAjax = 0;

		// Iterar sobre cada ID y realizar una llamada AJAX por separado
		json.forEach(function (id) {
			$.ajax({
				type: "POST",
				url: "md_adicionar_campanas.php",
				data: {
					type: 1,
					id_llamada_servicio: $("#Ticket").val(),
					docentry_llamada_servicio: $("#CallID").val(),
					id_campana: id,  // Usar el ID actual en esta iteración
				},
				success: function (response) {
					console.log(response);

					contadorAjax++;
					if (response !== "OK") {
						validarAjax = false;
					}

					// Verificar si todas las solicitudes AJAX han finalizado
					if (contadorAjax === json.length) {
						Swal.fire({
							icon: (validarAjax) ? "success" : "warning",
							title: (validarAjax) ? "¡Listo!" : "¡Error!",
							text: (validarAjax) ? "Todos las Campanas se insertaron correctamente." : "No se pudieron insertar algunos Campanas, por favor verifique."
						}).then((result) => {
							if (result.isConfirmed) {
								// if(validarAjax) {

								// Obtén la URL actual
								let currentUrl = new URL(window.location.href);

								// Obtén los parámetros del query string
								let searchParams = currentUrl.searchParams;

								// Actualiza el valor del parámetro 'active' o agrega si no existe
								searchParams.set('active', 4);

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
	}

	function AdicionarCampanasAsincrono() {

		// Deseleccionar todas las opciones
		$("#Campanas option").prop("selected", false);

		// Iterar sobre cada ID
		json.forEach(function (id) {

			// Seleccionar opciones específicas
			$(`#Campanas option[value='${id}']`).prop("selected", true);
		});
		// .forEach()

		// Disparar el evento "change"
		$("#Campanas").trigger("change");

		// Ocultar modal
		$('#myModal2').modal("hide");
	}
</script>