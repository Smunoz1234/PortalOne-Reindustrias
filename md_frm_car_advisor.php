<?php
require_once("includes/conexion.php");
$id = $_POST["id"] ?? "";

// Llamado AJAX.
$id_formulario_caradvisor = $_POST["id_formulario_caradvisor"] ?? "";
if ($id_formulario_caradvisor != "") {
	$firstname = $_POST["firstname"] ?? "";
	$lastname = $_POST["lastname"] ?? "";
	$email = $_POST["email"] ?? "";
	$zip = $_POST["zip"] ?? "";
	$city = $_POST["city"] ?? "";
	$street = $_POST["street"] ?? "";
	$modelvariant = $_POST["modelvariant"] ?? "";
	$User = $_SESSION['CodUser'] ?? "";

	$param_ca = array(
		"'$id_formulario_caradvisor'",
		"'$firstname'",
		"'$lastname'",
		"'$email'",
		"'$zip'",
		"'$city'",
		"'$street'",
		"'$modelvariant'",
		"'$User'",
	);
	$SQL_Operacion = EjecutarSP("sp_tbl_FormularioCarAdvisor", $param_ca);

	if (!$SQL_Operacion) {
		echo "No se pudo actualizar el registro.";
	} else {
		$row = sqlsrv_fetch_array($SQL_Operacion);

		$error_ca = $row["Error"] ?? "";
		if ($error_ca != "") {
			echo "No se pudo actualizar el registro. ($error_ca)";
		} else {
			echo "OK";
		}
	}

	// Mostrar mensajes AJAX.
	exit();
}

// Consultar registro Car Advisor.
$SQL_ca = Seleccionar("tbl_FormularioCarAdvisor", "*", "id_formulario_caradvisor = '$id'");
$row_ca = sqlsrv_fetch_array($SQL_ca);
?>

<div class="modal-header">
	<h4 class="modal-title">
		Actualizar Car Advisor
		
		<br>
		<small>ID:
			<?php echo $id; ?>
		</small>
	</h4>
</div>
<div class="modal-body">
	<div class="form-group">
		<div class="ibox-content">
			<?php include("includes/spinner.php"); ?>

			<form action="md_frm_car_advisor" id="formCA">
				<div class="form-group row">
					<div class="col-lg-4">
						<label class="control-label">
							ID <span class="text-danger">*</span>
						</label>
						<input required readonly type="text" class="form-control" name="id_formulario_caradvisor" id="id_formulario_caradvisor"
							value="<?php echo $id; ?>">
					</div>

					<div class="col-lg-4">
						<label class="control-label">
							Nombre Contacto <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="firstname"
							id="firstname" value="<?php echo $row_ca["firstname"] ?? ""; ?>">
					</div>

					<div class="col-lg-4">
						<label class="control-label">
							Apellido Contacto <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="lastname"
							id="lastname" value="<?php echo $row_ca["lastname"] ?? ""; ?>">
					</div>
				</div>

				<div class="form-group row">
					<div class="col-lg-4">
						<label class="control-label">
							ZIP <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="zip" id="zip"
							value="<?php echo $row_ca["zip"] ?? ""; ?>">
					</div>

					<div class="col-lg-4">
						<label class="control-label">
							Ciudad <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="city" id="city"
							value="<?php echo $row_ca["city"] ?? ""; ?>">
					</div>

					<div class="col-lg-4">
						<label class="control-label">
							Dirección <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="street" id="street"
							value="<?php echo $row_ca["street"] ?? ""; ?>">
					</div>
				</div>

				<div class="form-group row">
					<div class="col-lg-4">
						<label class="control-label">
							Teléfono <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="phone" id="phone"
							value="<?php echo $row_ca["phone"] ?? ""; ?>">
					</div>

					<div class="col-lg-4">
						<label class="control-label">
							Teléfono Compañía <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="phonecompany"
							id="phonecompany" value="<?php echo $row_ca["phonecompany"] ?? ""; ?>">
					</div>

					<div class="col-lg-4">
						<label class="control-label">
							Teléfono Móvil <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="phonemobile"
							id="phonemobile" value="<?php echo $row_ca["phonemobile"] ?? ""; ?>">
					</div>
				</div>

				<div class="form-group row">
					<div class="col-lg-4">
						<label class="control-label">
							Correo electrónico <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="email" id="email"
							value="<?php echo $row_ca["email"] ?? ""; ?>">
					</div>

					<div class="col-lg-4">
						<label class="control-label">
							Modelo Variante <span class="text-danger">*</span>
						</label>
						<input required autocomplete="off" type="text" class="form-control" name="modelvariant"
							id="modelvariant" value="<?php echo $row_ca["modelvariant"] ?? ""; ?>">
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="modal-footer">
	<button type="submit" class="btn btn-success m-t-md" form="formCA"><i class="fa fa-check"></i> Aceptar</button>
	<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
</div>

<script>
	$(document).ready(function () {
		$("#formCA").validate({
			submitHandler: function (form) {
				$('.ibox-content').toggleClass('sk-loading', true); // Cargando...

				let formData = new FormData(form);

				// Ejemplo de como agregar nuevos campos.
				// formData.append("Dim1", $("#Dim1").val() || "");

				let json = Object.fromEntries(formData);
				console.log("Line 140", json);

				// Inicio, AJAX
				$.ajax({
					url: 'md_frm_car_advisor.php',
					type: 'POST',
					data: formData,
					processData: false,  // tell jQuery not to process the data
					contentType: false,   // tell jQuery not to set contentType
					success: function (response) {
						if (response === "OK") {
							Swal.fire({
								icon: "success",
								title: "¡Listo!",
								text: "Se actualizo el registro correctamente."
							}).then((result) => {
								if (result.isConfirmed) {
									location.reload();
								}
							});
						} else {
							Swal.fire({
								icon: "warning",
								title: "¡Error!",
								text: response
							});
						}

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
	});
</script>