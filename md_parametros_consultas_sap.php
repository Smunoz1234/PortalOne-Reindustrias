<?php
require_once "includes/conexion.php";

$Title = "Crear nuevo registro";
$Metodo = 1;

$edit = isset($_POST['edit']) ? $_POST['edit'] : 0;
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$id = isset($_POST['id']) ? $_POST['id'] : "";

$SQL_CategoriasModal = Seleccionar('tbl_ConsultasSAPB1_Categorias', '*');
$SQL_ConsultasModal = Seleccionar('tbl_ConsultasSAPB1_Consultas', '*');

$ids_perfiles = array();
$SQL_PerfilesUsuarios = Seleccionar('uvw_tbl_PerfilesUsuarios', '*');

if ($edit == 1 && $id != "") {
    $Title = "Editar registro";
    $Metodo = 2;

    if ($doc == "Categoria") {
        $SQL = Seleccionar('tbl_ConsultasSAPB1_Categorias', '*', "ID='" . $id . "'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Consulta") {
        $SQL = Seleccionar('tbl_ConsultasSAPB1_Consultas', '*', "ID='" . $id . "'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Entrada") {
        $SQL = Seleccionar('tbl_ConsultasSAPB1_Entradas', '*', "ID='" . $id . "'");
        $row = sqlsrv_fetch_array($SQL);
    }

    $ids_perfiles = isset($row['Perfiles']) ? explode(";", $row['Perfiles']) : [];
}
?>

<style>
	/**
	* Estilos para el uso del componente select2-multiple en un modal.
	*
	* @author Stiven Muñoz Murillo
	* @version 26/07/2022
	*/

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

<form id="frm_NewParam" method="post" action="parametros_consultas_sap.php" enctype="multipart/form-data">

<div class="modal-header">
	<h4 class="modal-title">
		<?php echo "Crear Nueva $doc"; ?>
	</h4>
</div>

<div class="modal-body">
	<div class="form-group">
		<div class="ibox-content">
			<?php include "includes/spinner.php";?>

			<?php if ($doc == "Categoria") {?>

				<!-- Inicio Categoria -->
				<div class="form-group">
					<div class="col-md-6">
						<label class="control-label">Nombre Categoria <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreCategoria" id="NombreCategoria" value="<?php if ($edit == 1) {echo $row['NombreCategoria'];}?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['Estado'] == "Y")) {echo "selected=\"selected\"";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['Estado'] == "N")) {echo "selected=\"selected\"";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Categoria Padre</label>
						<select name="ID_CategoriaPadre" class="form-control select2" id="ID_CategoriaPadre">
							<option value="">Seleccione...</option>
							<?php while ($row_CategoriaModal = sqlsrv_fetch_array($SQL_CategoriasModal)) {?>
								<option value="<?php echo $row_CategoriaModal['ID']; ?>" <?php if ((isset($row['ID_CategoriaPadre'])) && (strcmp($row_CategoriaModal['ID'], $row['ID_CategoriaPadre']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_CategoriaModal['NombreCategoria']; ?></option>
							<?php }?>
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Perfiles Usuarios</label>
						<select data-placeholder="Digite para buscar..." name="Perfiles[]" class="form-control select2" id="Perfiles" multiple>
							<?php while ($row_Perfil = sqlsrv_fetch_array($SQL_PerfilesUsuarios)) {?>
								<option value="<?php echo $row_Perfil['ID_PerfilUsuario']; ?>"
								<?php if (in_array($row_Perfil['ID_PerfilUsuario'], $ids_perfiles)) {echo "selected";}?>>
									<?php echo $row_Perfil['PerfilUsuario']; ?>
								</option>
							<?php }?>
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php if ($edit == 1) {echo $row['Comentarios'];}?></textarea>
					</div>
				</div>
				<br><br>
				<!-- Fin Categoria -->

			<?php } elseif ($doc == "Consulta") {?>

				<!-- Inicio Consulta -->
				<div class="form-group">
					<div class="col-md-6">
						<label class="control-label">Categoria</label>
						<select name="ID_Categoria" class="form-control select2" id="ID_Categoria">
							<option value="">Seleccione...</option>
							<?php while ($row_CategoriaModal = sqlsrv_fetch_array($SQL_CategoriasModal)) {?>
								<option value="<?php echo $row_CategoriaModal['ID']; ?>" <?php if ((isset($row['ID_Categoria'])) && (strcmp($row_CategoriaModal['ID'], $row['ID_Categoria']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_CategoriaModal['NombreCategoria']; ?></option>
							<?php }?>
						</select>
					</div>
					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['Estado'] == "Y")) {echo "selected=\"selected\"";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['Estado'] == "N")) {echo "selected=\"selected\"";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Procedimiento <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="ProcedimientoConsulta" id="ProcedimientoConsulta" value="<?php if ($edit == 1) {echo $row['ProcedimientoConsulta'];}?>">
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Parámetros</label>
						<input type="text" class="form-control" autocomplete="off" data-role="tagsinput" name="ParametrosEntrada" id="ParametroEntrada" value="<?php if ($edit == 1) {echo $row['ParametroEntrada'];}?>" placeholder= "Ingrese una entrada y utilice la tecla [ESP] para agregar">
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Perfiles Usuarios</label>
						<select data-placeholder="Digite para buscar..." name="Perfiles[]" class="form-control select2" id="Perfiles" multiple>
							<?php while ($row_Perfil = sqlsrv_fetch_array($SQL_PerfilesUsuarios)) {?>
								<option value="<?php echo $row_Perfil['ID_PerfilUsuario']; ?>"
								<?php if (in_array($row_Perfil['ID_PerfilUsuario'], $ids_perfiles)) {echo "selected";}?>>
									<?php echo $row_Perfil['PerfilUsuario']; ?>
								</option>
							<?php }?>
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php if ($edit == 1) {echo $row['Comentarios'];}?></textarea>
					</div>
				</div>
				<br><br>
				<!-- Fin Consulta -->

			<?php } elseif ($doc == "Entrada") {?>

				<!-- Inicio Entrada -->
				<div class="form-group">
					<div class="col-md-6">
						<label class="control-label">Consulta <span class="text-danger">*</span></label>
						<select name="ID_Consulta" class="form-control select2" id="ID_Consulta" required>
							<option value="">Seleccione...</option>
							<?php while ($row_ConsultaModal = sqlsrv_fetch_array($SQL_ConsultasModal)) {?>
								<option value="<?php echo $row_ConsultaModal['ID']; ?>" <?php if ((isset($row['ID_Consulta'])) && (strcmp($row_ConsultaModal['ID'], $row['ID_Consulta']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_ConsultaModal['ProcedimientoConsulta']; ?></option>
							<?php }?>
						</select>
					</div>

					<div class="col-md-6">
						<label class="control-label">Estado <span class="text-danger">*</span></label>
						<select class="form-control" id="Estado" name="Estado">
							<option value="Y" <?php if (($edit == 1) && ($row['Estado'] == "Y")) {echo "selected=\"selected\"";}?>>ACTIVO</option>
							<option value="N" <?php if (($edit == 1) && ($row['Estado'] == "N")) {echo "selected=\"selected\"";}?>>INACTIVO</option>
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Parámetro</label>
						<select name="ID_Consulta" class="form-control select2" id="ID_Consulta" required>
							<option value="">Seleccione...</option>
							<!-- Las demás opciones dependen de la consulta -->
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Procedimiento <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="ProcedimientoConsulta" id="ProcedimientoConsulta" value="<?php if ($edit == 1) {echo $row['ProcedimientoConsulta'];}?>">
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Perfiles Usuarios</label>
						<select data-placeholder="Digite para buscar..." name="Perfiles[]" class="form-control select2" id="Perfiles" multiple>
							<?php while ($row_Perfil = sqlsrv_fetch_array($SQL_PerfilesUsuarios)) {?>
								<option value="<?php echo $row_Perfil['ID_PerfilUsuario']; ?>"
								<?php if (in_array($row_Perfil['ID_PerfilUsuario'], $ids_perfiles)) {echo "selected";}?>>
									<?php echo $row_Perfil['PerfilUsuario']; ?>
								</option>
							<?php }?>
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php if ($edit == 1) {echo $row['Comentarios'];}?></textarea>
					</div>
				</div>
				<br><br>
				<!-- Fin Entrada -->

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
$(document).ready(function(){
	// Activación del componente "tagsinput"
	$('input[data-role=tagsinput]').tagsinput({
		confirmKeys: [32, 44] // Espacio y coma.
	});

	// Ajusto el ancho del componente "tagsinput"
	$('.bootstrap-tagsinput').css("display", "block");
	$('.bootstrap-tagsinput > input').css("width", "100%");

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

	// SMM, 26/07/2022
	$(".select2").select2();

	// SMM, 17/08/2022
	$("#IdTipoDocumento").on("change", function() {
		var doctype = $(this).val();

		$.ajax({
			type: "POST",
			url: `ajx_cbo_select.php?type=43&doctype=${doctype}`,
			success: function(response){
				$('#IdModeloAutorizacionSAPB1').html(response).fadeIn();
				$('#IdModeloAutorizacionSAPB1').trigger('change');
			}
		});
	});
 });
</script>
