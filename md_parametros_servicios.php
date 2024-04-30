<?php
require_once "includes/conexion.php";

$Title = "Crear nuevo registro";
$Metodo = 1;

$edit = isset($_POST['edit']) ? $_POST['edit'] : 0;
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$id = isset($_POST['id']) ? $_POST['id'] : "";

if ($edit == 1 && $id != "") {
    $Title = "Editar registro";
    $Metodo = 2;

    if ($doc == "Unidades") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_UnidadMedidas', '*', "[id_unidad_medida_equipo]='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Marcas") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_Marcas', '*', "[id_marca_equipo]='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Lineas") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_Lineas', '*', "[id_linea_equipo]='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Fabricantes") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_Fabricantes', '*', "[id_fabricante_equipo]='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Annios") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_Annio', '*', "[id_annio_equipo]='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Ubicaciones") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_Ubicaciones', '*', "[id_ubicacion_equipo]='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Motivos") {
        $SQL = Seleccionar('tbl_Actividades_ParadaMotivo', '*', "[id_motivo_parada]='$id'");
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

<form id="frm_NewParam" method="post" action="parametros_servicios.php" enctype="multipart/form-data">

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

				<!-- Inicio, Unidad Medida -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Unidad Medida <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreUnidadMedida" id="NombreUnidadMedida" value="<?php echo $row['unidad_medida_equipo'] ?? ""; ?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['estado_unidad_medida_equipo'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['estado_unidad_medida_equipo'] == "N")) {echo "selected";}?>>INACTIVO</option>
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

			<?php } elseif ($doc == "Marcas") {?>

				<!-- Inicio, Marca -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Marca <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreMarca" id="NombreMarca" value="<?php echo $row['marca_equipo'] ?? ""; ?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['estado_marca_equipo'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['estado_marca_equipo'] == "N")) {echo "selected";}?>>INACTIVO</option>
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

			<?php } elseif ($doc == "Lineas") {?>

				<!-- Inicio, Linea -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Linea <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreLinea" id="NombreLinea" value="<?php echo $row['linea_equipo'] ?? ""; ?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['estado_linea_equipo'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['estado_linea_equipo'] == "N")) {echo "selected";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php echo $row['comentarios'] ?? ""; ?></textarea>
					</div>
				</div>
				<!-- Fin, Linea -->

			<?php } elseif ($doc == "Fabricantes") {?>

				<!-- Inicio, Fabricante -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Fabricante <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreFabricante" id="NombreFabricante" value="<?php echo $row['fabricante_equipo'] ?? ""; ?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['estado_fabricante_equipo'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['estado_fabricante_equipo'] == "N")) {echo "selected";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php echo $row['comentarios'] ?? ""; ?></textarea>
					</div>
				</div>
				<!-- Fin, Fabricante -->

			<?php } elseif ($doc == "Annios") {?>

				<!-- Inicio, Año -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Año <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreAnnio" id="NombreAnnio" value="<?php echo $row['annio_equipo'] ?? ""; ?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['estado_annio_equipo'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['estado_annio_equipo'] == "N")) {echo "selected";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<div class="form-group row">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php echo $row['comentarios'] ?? ""; ?></textarea>
					</div>
				</div>
				<!-- Fin, Año -->

			<?php } elseif ($doc == "Ubicaciones") {?>

				<!-- Inicio, Ubicacion -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Ubicación <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreUbicacion" id="NombreUbicacion" value="<?php echo $row['ubicacion_equipo'] ?? ""; ?>">
					</div>
					
					<div class="col-md-6" style="display: none;">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['estado_ubicacion_equipo'] == "Y")) {echo "selected";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['estado_ubicacion_equipo'] == "N")) {echo "selected";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<div class="form-group row" style="display: none;">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php echo $row['comentarios'] ?? ""; ?></textarea>
					</div>
				</div>
				<!-- Fin, Ubicacion -->

				<?php } elseif ($doc == "Motivos") {?>

					<!-- Inicio, Motivo -->
					<div class="form-group row">
						<div class="col-md-6">
							<label class="control-label">Nombre Motivo Parada <span class="text-danger">*</span></label>
							<input type="text" class="form-control" autocomplete="off" required name="NombreMotivo" id="NombreMotivo" value="<?php echo $row['motivo_parada'] ?? ""; ?>">
						</div>
						
						<div class="col-md-6">
							<label class="control-label">Estado <span class="text-danger">*</span></label>
							<select class="form-control" id="Estado" name="Estado">
								<option value="Y" <?php if (($edit == 1) && ($row['estado'] == "Y")) {echo "selected";}?>>ACTIVO</option>
								<option value="N" <?php if (($edit == 1) && ($row['estado'] == "N")) {echo "selected";}?>>INACTIVO</option>
							</select>
						</div>
					</div>

					<div class="form-group row" style="display: none;">
						<div class="col-md-12">
							<label class="control-label">Comentarios</label>
							<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php echo $row['comentarios'] ?? ""; ?></textarea>
						</div>
					</div>
					<!-- Fin, Motivo -->

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
