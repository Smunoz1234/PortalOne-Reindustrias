<?php
require_once("includes/conexion.php");

$id = 0;
$esArray = false;
$count = 0;

if (isset($_POST['id'])) {
	if (is_array($_POST['id'])) {
		$esArray = true;
		$count = count($_POST['id']);
		$id = implode(',', $_POST['id']);
	} else {
		$id = $_POST['id'];
	}
}

// Consultar registro Car Advisor
$SQL_ca = Seleccionar("tbl_FormularioCarAdvisor", "*", "id_formulario_caradvisor = '$id'");
$row_ca =  sqlsrv_fetch_array($SQL_ca);
?>

<div class="modal-header">
	<h4 class="modal-title">
		Actualizar
		<?php if ($esArray) {
			echo "en lote";
		} ?><br>
		<?php if ($esArray) { ?>
			<small>Cantidad:
				<?php echo $count; ?>
			</small>
		<?php } else { ?>
			<small>ID:
				<?php echo $id; ?>
			</small>
		<?php } ?>
	</h4>
</div>
<div class="modal-body">
	<div class="form-group">
		<div class="ibox-content">
			<?php include("includes/spinner.php"); ?>

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">
						Nombre Contacto <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="firstname" id="firstname" value="<?php echo $row_ca["firstname"] ?? ""; ?>">
				</div>
				
				<div class="col-lg-4">
					<label class="control-label">
						Apellido Contacto <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="lastname" id="lastname" value="<?php echo $row_ca["lastname"] ?? ""; ?>">
				</div>

				<div class="col-lg-4">
					<label class="control-label">
						Correo electrónico <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="email" id="email" value="<?php echo $row_ca["email"] ?? ""; ?>">
				</div>
			</div>

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">
						ZIP <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="zip" id="zip" value="<?php echo $row_ca["zip"] ?? ""; ?>">
				</div>
				
				<div class="col-lg-4">
					<label class="control-label">
						Ciudad <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="city" id="city" value="<?php echo $row_ca["city"] ?? ""; ?>">
				</div>

				<div class="col-lg-4">
					<label class="control-label">
						Dirección <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="street" id="street" value="<?php echo $row_ca["street"] ?? ""; ?>">
				</div>
			</div>

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">
						Teléfono <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="phone" id="phone" value="<?php echo $row_ca["phone"] ?? ""; ?>">
				</div>
				
				<div class="col-lg-4">
					<label class="control-label">
						Teléfono Compañía <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="phonecompany" id="phonecompany" value="<?php echo $row_ca["phonecompany"] ?? ""; ?>">
				</div>

				<div class="col-lg-4">
					<label class="control-label">
						Teléfono Móvil <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="phonemobile" id="phonemobile" value="<?php echo $row_ca["phonemobile"] ?? ""; ?>">
				</div>
			</div>

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">
						Modelo Variante <span class="text-danger">*</span>
					</label>
					<input required autocomplete="off" type="text" class="form-control" name="modelvariant" id="modelvariant" value="<?php echo $row_ca["modelvariant"] ?? ""; ?>">
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-success m-t-md" onclick="GuardarDatos('<?php echo $id; ?>');"><i
			class="fa fa-check"></i> Aceptar</button>
	<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
</div>

<script>
	function GuardarDatos(id) {
		Swal.fire({
			title: "¿Está seguro que desea ejecutar el proceso?",
			text: "Se modificarán los estados de los registros",
			icon: "info",
			showCancelButton: true,
			confirmButtonText: "Si, confirmo",
			cancelButtonText: "No"
		}).then((result) => {
			if (result.isConfirmed) {
				EjecutarProceso(id);
			}
		});
	}

	function EjecutarProceso(id) {
		$('.ibox-content').toggleClass('sk-loading', true);

		var estado = document.getElementById("Estado").value;
		var comentarios = document.getElementById("Comentarios").value;

		var esArray = <?php echo ($esArray) ? 'true' : 'false'; ?>;

		if (estado == "" || comentarios == "") {
			$('.ibox-content').toggleClass('sk-loading', false);
			Swal.fire({
				title: '¡Advertencia!',
				text: 'Debe llenar todos los campos',
				icon: 'warning'
			});
		} else {
			$.ajax({
				url: "ajx_ejecutar_json.php",
				data: {
					type: 7,
					id: id,
					estado: estado,
					comentarios: comentarios,
					esArray: esArray
				},
				dataType: 'json',
				success: function (data) {
					$('.ibox-content').toggleClass('sk-loading', false);
					Swal.fire({
						title: data.Title,
						text: data.Mensaje,
						icon: data.Icon
					});
					if (data.Estado == 1) {
						$('#myModal').modal("hide");
						if (esArray) {
							var arrayID = id.split(",");
							//					console.log(arrayID);
							arrayID.forEach(function (value) {
								$('#btnEstado' + value).hide();
								$('#dvChkSel' + value).remove();
								$('#comentCierre' + value).html(comentarios);
								if (estado == "C") {
									$('#lblEstado' + value).removeClass()
									$('#lblEstado' + value).addClass("label label-primary");
									$('#lblEstado' + value).html("Cerrado");
								} else if (estado == "A") {
									$('#lblEstado' + value).removeClass()
									$('#lblEstado' + value).addClass("label label-danger");
									$('#lblEstado' + value).html("Anulado");
								}
							});
							$(".chkSelOT").prop("checked", false);
							$("#chkAll").prop("checked", false);
							$("#btnCambiarLote").attr("disabled", "disabled");
							json = [];
							cant = 0;
						} else {
							$('#btnEstado' + id).hide();
							$('#dvChkSel' + id).remove();
							$('#comentCierre' + id).html(comentarios);
							if (estado == "C") {
								$('#lblEstado' + id).removeClass()
								$('#lblEstado' + id).addClass("label label-primary");
								$('#lblEstado' + id).html("Cerrado");
							} else if (estado == "A") {
								$('#lblEstado' + id).removeClass()
								$('#lblEstado' + id).addClass("label label-danger");
								$('#lblEstado' + id).html("Anulado");
							}
						}
					}
				},
				error: function (data) {
					console.log('Error:', data)
					$('.ibox-content').toggleClass('sk-loading', false);
				}
			});
		}


	}
</script>