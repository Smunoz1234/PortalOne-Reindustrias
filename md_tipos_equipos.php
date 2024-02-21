<?php
require_once "includes/conexion.php";

$Title = "Crear nuevo registro";
$Metodo = 1;

$edit = isset($_POST['edit']) ? $_POST['edit'] : 0;
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$id = isset($_POST['id']) ? $_POST['id'] : "";

$SQL_TiposEquiposModal = Seleccionar('tbl_TarjetaEquipo_TiposEquipos', '*');
$SQL_TiposEquipos_Campos = Seleccionar('tbl_TarjetaEquipo_TiposEquipos_Campos', '*');

if ($edit == 1 && $id != "") {
    $Title = "Editar registro";
    $Metodo = 2;

    if ($doc == "Tipos") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_TiposEquipos', '*', "[id_tipo_equipo]='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Propiedades") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_TiposEquipos_Propiedades', '*', "[id_propiedad]='$id'");
        $row = sqlsrv_fetch_array($SQL);
    }
}

$Cons_Lista = "EXEC sp_tables @table_owner = 'dbo', @table_type = \"'VIEW'\"";
$SQL_Lista = sqlsrv_query($conexion, $Cons_Lista);
?>

<style>
	.select2-container {
		z-index: 10000;
	}
	.select2-search--inline {
    display: contents;
	}
	.select2-search__field:placeholder-shown {
		width: 100% !important;
	}
</style>

<form id="frm_NewParam" method="post" action="tipos_equipos.php" enctype="multipart/form-data">

<div class="modal-header">
	<h4 class="modal-title">
		<?php echo "Crear Nuevo Registro de $doc"; ?>
	</h4>
</div>

<div class="modal-body">
	<div class="form-group">
		<div class="ibox-content">
			<?php include "includes/spinner.php";?>

			<?php if ($doc == "Tipos") {?>

				<!-- Inicio Tipos -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Tipo Equipo <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreTipoEquipo" id="NombreTipoEquipo" value="<?php if ($edit == 1) {echo $row['tipo_equipo'];}?>">
					</div>

					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['estado_tipo_equipo'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['estado_tipo_equipo'] == "N")) {echo "selected";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php if ($edit == 1) {echo $row['Comentarios'];}?></textarea>
					</div>
				</div>
				<!-- Fin Tipos -->

			<?php } elseif ($doc == "Propiedades") {?>

				<!-- Inicio Propiedades -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Propiedad <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombrePropiedad" id="NombrePropiedad" value="<?php if ($edit == 1) {echo $row['propiedad'];}?>">
					</div>

					<div class="col-md-6">
						<label class="control-label">Tipo Equipo <span class="text-danger">*</span></label>
						<select name="ID_TipoEquipo" class="form-control select2" id="ID_TipoEquipo" required>
							<option value="" disabled selected>Seleccione...</option>
							<?php while ($row_TE_Modal = sqlsrv_fetch_array($SQL_TiposEquiposModal)) {?>
								<option value="<?php echo $row_TE_Modal['id_tipo_equipo']; ?>" <?php if ((isset($row['id_tipo_equipo'])) && (strcmp($row_TE_Modal['id_tipo_equipo'], $row['id_tipo_equipo']) == 0)) {echo "selected";}?>><?php echo $row_TE_Modal['tipo_equipo']; ?></option>
							<?php }?>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">Tipo de campo <span class="text-danger">*</span></label>
						<select class="form-control" name="ID_TipoEquipo_Campo" id="ID_TipoEquipo_Campo" required>
							<?php while ($row_Campo = sqlsrv_fetch_array($SQL_TiposEquipos_Campos)) {?>
								<option value="<?php echo $row_Campo['id_tipo_equipo_campo']; ?>" <?php if ((isset($row['id_tipo_equipo_campo'])) && (strcmp($row_Campo['id_tipo_equipo_campo'], $row['id_tipo_equipo_campo']) == 0)) {echo "selected";}?>><?php echo $row_Campo['tipo_equipo_campo']; ?></option>
							<?php }?>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Obligatorio <span class="text-danger">*</span></label>
						<select class="form-control" id="Obligatorio" name="Obligatorio" required>
							<option value="Y" <?php if (($edit == 1) && ($row['obligatorio'] == "Y")) {echo "selected";}?>>SI</option>
							<option value="N" <?php if (($edit == 1) && ($row['obligatorio'] == "N")) {echo "selected";}?>>NO</option>
						</select>
					</div>

					<div class="col-md-6">
						<label class="control-label">Multiple <span class="text-danger">*</span></label>
						<select class="form-control" id="Multiple" name="Multiple" disabled>
							<option value="N" <?php if (($edit == 1) && ($row['multiple'] == "N")) {echo "selected";}?>>NO</option>
							<option value="Y" <?php if (($edit == 1) && ($row['multiple'] == "Y")) {echo "selected";}?>>SI</option>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">Tabla Vinculada <span class="text-danger">*</span></label>
						<select name="TablaVinculada" class="form-control select2" id="TablaVinculada" disabled>
							<option value="" disabled selected>Seleccione...</option>
							<?php while ($row_Lista = sqlsrv_fetch_array($SQL_Lista)) {?>
								<option value="<?php echo $row_Lista['TABLE_NAME']; ?>" <?php if ((isset($row['tabla_vinculada'])) && ($row_Lista['TABLE_NAME'] == $row['tabla_vinculada'])) {echo "selected";}?>><?php echo $row_Lista['TABLE_NAME']; ?></option>
							<?php }?>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Valor <span class="text-danger">*</span></label>
						<select name="ValorLista" class="form-control" id="ValorLista" disabled>
							<option value="">Seleccione...</option>
							<!-- Generado por JS -->
						</select>
					</div>

					<div class="col-md-6">
						<label class="control-label">Etiqueta <span class="text-danger">*</span></label>
						<select name="EtiquetaLista" class="form-control" id="EtiquetaLista" disabled>
							<option value="">Seleccione...</option>
							<!-- Generado por JS -->
						</select>
					</div>
				</div>

				<!-- Fin Propiedades -->

			<?php }?>
		</div> <!-- ibox-content -->
	</div> <!-- form-group -->
</div> <!-- modal-body -->

<div class="modal-footer">
	<button type="submit" class="btn btn-success m-t-md"><i class="fa fa-check"></i> Aceptar</button>
	<button type="button" class="btn btn-warning m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
</div>

	<input type="hidden" id="TipoDoc" name="TipoDoc" value="<?php echo $doc; ?>" />
	<input type="hidden" id="ID_Actual" name="ID_Actual" value="<?php echo $id; ?>" />
	<input type="hidden" id="Metodo" name="Metodo" value="<?php echo $Metodo; ?>" />
	<input type="hidden" id="frmType" name="frmType" value="1" />

</form>

<script>
$(document).ready(function() {
	$("#frm_NewParam").validate({
		submitHandler: function(form) {
			let Metodo = document.getElementById("Metodo").value;
			
			if(Metodo!="3") {
				Swal.fire({
					title: "¿Está seguro que desea guardar los datos?",
					icon: "question",
					showCancelButton: true,
					confirmButtonText: "Si, confirmo",
					cancelButtonText: "No"
				}).then((result) => {
					if (result.isConfirmed) {
						$('.ibox-content').toggleClass('sk-loading', true);
						form.submit();
					}
				});
			} else {
				$('.ibox-content').toggleClass('sk-loading', true);
				form.submit();
			}
		}
	});

	$('.chosen-select').chosen({width: "100%"});
	$(".select2").select2();

	// SMM, 21/02/2024
	$("#ID_TipoEquipo_Campo").on("change", function() {
		if($(this).val() == 5) {
			$("#Multiple").prop("disabled", false);
			$("#TablaVinculada").prop("disabled", false);
			$("#EtiquetaLista").prop("disabled", false);
			$("#ValorLista").prop("disabled", false);
		} else {
			$("#Multiple").prop("disabled", true);
			$("#TablaVinculada").prop("disabled", true);
			$("#EtiquetaLista").prop("disabled", true);
			$("#ValorLista").prop("disabled", true);
		}
	});

	// Cargar lista de campos dependiendo de la vista.
	$("#TablaVinculada").on("change", function() {
		$.ajax({
			type: "POST",
			url: `ajx_cbo_select.php?type=12&id=${$(this).val()}&obligatorio=1`,
			success: function(response){
				$('#EtiquetaLista').html(response).fadeIn();
				$('#ValorLista').html(response).fadeIn();

				<?php if (($edit == 1) && ($id != "")) {?>
					$('#EtiquetaLista').val("<?php echo $row['etiqueta_lista'] ?? ""; ?>");
					$('#ValorLista').val("<?php echo $row['valor_lista'] ?? ""; ?>");
				<?php }?>

				$('#EtiquetaLista').trigger('change');
				$('#ValorLista').trigger('change');
			}
		});
	});

	<?php if (($edit == 1) && ($id != "")) {?>
		$('#TablaVinculada').trigger('change');
		$('#ID_TipoEquipo_Campo').trigger('change');
	<?php }?>
 });
</script>
