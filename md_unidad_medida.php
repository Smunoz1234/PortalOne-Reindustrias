<?php
require_once "includes/conexion.php";

$Title = "Crear nuevo registro";
$Metodo = 1;

$edit = isset($_POST['edit']) ? $_POST['edit'] : 0;
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$id = isset($_POST['id']) ? $_POST['id'] : "";

$SQL_TiposEquiposModal = Seleccionar('tbl_TarjetaEquipo_UnidadMedidas', '*');

if ($edit == 1 && $id != "") {
    $Title = "Editar registro";
    $Metodo = 2;

    if ($doc == "Unidades") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_UnidadMedidas', '*', "ID='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } 
}
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

<form id="frm_NewParam" method="post" action="unidad_medida.php" enctype="multipart/form-data">

<div class="modal-header">
	<h4 class="modal-title">
		<?php echo "Crear Nuevo Registro de $doc"; ?>
	</h4>
</div>

<div class="modal-body">
	<div class="form-group">
		<div class="ibox-content">
			<?php include "includes/spinner.php";?>

			<?php if ($doc == "Unidades") {?>

				<!-- Inicio Unidad Medida -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Unidad Medida <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreUnidadMedida" id="NombreUnidadMedida" value="<?php if ($edit == 1) {echo $row['NombreUnidadMedida'];}?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['Estado'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['Estado'] == "N")) {echo "selected";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php if ($edit == 1) {echo $row['Comentarios'];}?></textarea>
					</div>
				</div>
				<!-- Fin Unidad Medida -->

			<?php } ?>

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
		submitHandler: function(form){
			let Metodo = document.getElementById("Metodo").value;
			if(Metodo!="3"){
				Swal.fire({
				title: "¿Está seguro que desea guardar los datos?",
				icon: "question",
				showCancelButton: true,
				confirmButtonText: "Si, confirmo",
				cancelButtonText: "No"
			}).then((result) => {
				if (result.isConfirmed) {
					$('.ibox-content').toggleClass('sk-loading',true);
					form.submit();
				}
			});
			}else{
			$('.ibox-content').toggleClass('sk-loading',true);
			form.submit();
			}
	}
	 });

	$('.chosen-select').chosen({width: "100%"});
	$(".select2").select2();
 });
</script>
