<?php
require_once "includes/conexion.php";

$Title = "Crear nuevo registro";
$Metodo = 1;

$edit = isset($_POST['edit']) ? $_POST['edit'] : 0;
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$id = isset($_POST['id']) ? $_POST['id'] : "";

// SMM, 19/04/2024
$SQL_DimensionesModal = Seleccionar('tbl_TarjetaEquipo_DimensionJerarquias', '*');

if ($edit == 1 && $id != "") {
    $Title = "Editar registro";
    $Metodo = 2;

    if ($doc == "Jerarquias") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_Jerarquias', '*', "[id_jerarquia]='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Dimensiones") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_DimensionJerarquias', '*', "[id_dimension_jerarquia]='$id'");
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

<form id="frm_NewParam" method="post" action="jerarquias_te.php" enctype="multipart/form-data">

<div class="modal-header">
	<h4 class="modal-title">
		<?php echo "Crear Nuevo Registro de $doc"; ?>
	</h4>
</div>

<div class="modal-body">
	<div class="form-group">
		<div class="ibox-content">
			<?php include "includes/spinner.php";?>

			<?php if ($doc == "Jerarquias") {?>

				<!-- Inicio, Unidad Medida -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Jerarquía <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreJerarquia" id="NombreJerarquia" value="<?php echo $row['jerarquia'] ?? ""; ?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['estado_jerarquia'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['estado_jerarquia'] == "N")) {echo "selected";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">
							Dimensión Jerarquía <span class="text-danger">*</span>
						</label>
						
						<select name="ID_Dimension" class="form-control select2" id="ID_Dimension" required>
							<option value="" disabled selected>Seleccione...</option>
							
							<?php while ($row_DimensionesModal = sqlsrv_fetch_array($SQL_DimensionesModal)) {?>
								<option value="<?php echo $row_DimensionesModal['id_dimension_jerarquia']; ?>" 
									<?php if (isset($row['id_dimension_jerarquia']) && ($row['id_dimension_jerarquia'] == $row_DimensionesModal['id_dimension_jerarquia'])) {
										echo "selected";
									} ?>>
									<?php echo $row_DimensionesModal['id_dimension_jerarquia'] . " - " . $row_DimensionesModal['dimension_jerarquia']; ?>
								</option>
							<?php }?>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php echo $row['comentarios'] ?? ""; ?></textarea>
					</div>
				</div>
				<!-- Fin, Unidad Medida -->

			<?php } elseif ($doc == "Dimensiones") {?>

				<!-- Inicio, Marca -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Dimensión <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreDimension" id="NombreDimension" value="<?php echo $row['dimension_jerarquia'] ?? ""; ?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['estado_dimension_jerarquia'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['estado_dimension_jerarquia'] == "N")) {echo "selected";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php echo $row['comentarios'] ?? ""; ?></textarea>
					</div>
				</div>
				<!-- Fin, Marca -->

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
