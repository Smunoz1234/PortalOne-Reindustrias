<?php
require_once "includes/conexion.php";

$Title = "Crear nuevo registro";
$Metodo = 1;

$edit = isset($_POST['edit']) ? $_POST['edit'] : 0;
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$id = isset($_POST['id']) ? $_POST['id'] : "";

$SQL_CategoriasModal = Seleccionar('tbl_ConsultasSAPB1_Categorias', '*');
$SQL_Perfiles = Seleccionar('uvw_tbl_PerfilesUsuarios', '*');

$ids_perfiles = array();
if ($edit == 1 && $id != "") {
    $Title = "Editar registro";
    $Metodo = 2;

    if ($doc == "Categoria") {
        $SQL = Seleccionar('tbl_ConsultasSAPB1_Categorias', '*', "ID='" . $id . "'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Procesos") {
        $SQL = Seleccionar('tbl_Autorizaciones_Procesos', '*', "IdInterno='" . $id . "'");
        $row = sqlsrv_fetch_array($SQL);

        // SMM 27/07/2022

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

	/**
	* Iconos del panel de información.
	* SMM, 18/08/2022
	*/
	.panel-heading  a:before {
		font-family: 'Glyphicons Halflings';
		content: "\e114";
		float: right;
		transition: all 0.5s;
	}
	.panel-heading.active a:before {
		-webkit-transform: rotate(180deg);
		-moz-transform: rotate(180deg);
		transform: rotate(180deg);
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
							<?php while ($row_Perfil = sqlsrv_fetch_array($SQL_Perfiles)) {?>
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

			<?php } elseif ($doc == "Motivos") {?>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php if ($edit == 1) {echo $row['Comentarios'];}?></textarea>
					</div>
				</div>

				<br><br><br><br><br><br>
				<div class="form-group">
					<div class="col-md-6">
						<label class="control-label">Tipo de documento <span class="text-danger">*</span></label>
						<select name="IdTipoDocumento" class="form-control" id="IdTipoDocumento" required>
								<option value="" selected disabled>Seleccione...</option>
								<?php $CatActual = "";?>
								<?php while ($row_TipoDoc = sqlsrv_fetch_array($SQL_TipoDoc)) {?>
									<?php if ($CatActual != $row_TipoDoc['CategoriaObjeto']) {?>
										<?php echo "<optgroup label='" . $row_TipoDoc['CategoriaObjeto'] . "'></optgroup>"; ?>
										<?php $CatActual = $row_TipoDoc['CategoriaObjeto'];?>
									<?php }?>
									<option value="<?php echo $row_TipoDoc['IdTipoDocumento']; ?>"
									<?php if ((($edit == 1) && (isset($row['IdTipoDocumento'])) && (strcmp($row_TipoDoc['IdTipoDocumento'], $row['IdTipoDocumento']) == 0))) {echo "selected=\"selected\"";}?>>
										<?php echo $row_TipoDoc['DeTipoDocumento']; ?>
									</option>
							<?php }?>
							<optgroup label='Otros'></optgroup>
							<option value="OTRO" <?php if (($edit == 1) && ($swOtro == 1 && $row['IdTipoDocumento'] != "")) {echo "selected=\"selected\"";}?>>OTRO</option>
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
					<div class="col-md-6">
						<label class="control-label">Id Motivo <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="IdMotivoAutorizacion" id="IdMotivoAutorizacion" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['IdMotivoAutorizacion'];}?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Motivo <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="MotivoAutorizacion" id="MotivoAutorizacion" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['MotivoAutorizacion'];}?>">
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Modelo autorización SAP B1 <span class="text-danger">*</span></label>
						<select name="IdModeloAutorizacionSAPB1" class="form-control select2" id="IdModeloAutorizacionSAPB1" required>
							<option value="">Seleccione...</option>
							<?php while ($row_ModeloAutorizacion = sqlsrv_fetch_array($SQL_ModeloAutorizacion)) {?>
								<option value="<?php echo $row_ModeloAutorizacion['IdModeloAutorizacion']; ?>" <?php if ((isset($row['IdModeloAutorizacionSAPB1'])) && (strcmp($row_ModeloAutorizacion['IdModeloAutorizacion'], $row['IdModeloAutorizacionSAPB1']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_ModeloAutorizacion['ModeloAutorizacion']; ?></option>
							<?php }?>
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-6">
						<label class="control-label">Usuario autorización SAP B1 <span class="text-danger">*</span></label>
						<select name="IdUsuarioAutorizacion" class="form-control select2" id="IdUsuarioAutorizacion" required>
							<option value="">Seleccione...</option>
							<?php while ($row_UsuarioSAP = sqlsrv_fetch_array($SQL_UsuariosSAP)) {?>
								<option value="<?php echo $row_UsuarioSAP['USERID']; ?>" <?php if ((isset($row['IdUsuarioAutorizacionSAPB1'])) && (strcmp($row_UsuarioSAP['USERID'], $row['IdUsuarioAutorizacionSAPB1']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_UsuarioSAP['USER_CODE']; ?></option>
							<?php }?>
						</select>
					</div>
					<div class="col-md-6">
						<label class="control-label">Password usuario SAP B1 <span class="text-danger">*</span></label>
						<input type="password" class="form-control" name="PassUsuarioAutorizacion" id="PassUsuarioAutorizacion" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['PassUsuarioAutorizacionSAPB1'];}?>">
						<a href="#" id="aVerPass" onClick="javascript:MostrarPassword();" title="Mostrar contrase&ntilde;a" class="btn btn-default btn-xs"><span id="VerPass" class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a>
					</div>
				</div>
			<!-- Fin Motivos -->
			<?php } elseif ($doc == "Parametros") {?>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Comentarios</label>
						<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php if ($edit == 1) {echo $row['Comentarios'];}?></textarea>
					</div>
				</div>

				<br><br><br><br><br><br>
				<div class="form-group">
					<div class="col-md-6">
						<label class="control-label">Tipo de documento <span class="text-danger">*</span></label>
						<select name="IdTipoDocumento" class="form-control" id="IdTipoDocumento" required>
								<option value="" selected disabled>Seleccione...</option>
								<?php $CatActual = "";?>
								<?php while ($row_TipoDoc = sqlsrv_fetch_array($SQL_TipoDoc)) {?>
									<?php if ($CatActual != $row_TipoDoc['CategoriaObjeto']) {?>
										<?php echo "<optgroup label='" . $row_TipoDoc['CategoriaObjeto'] . "'></optgroup>"; ?>
										<?php $CatActual = $row_TipoDoc['CategoriaObjeto'];?>
									<?php }?>
									<option value="<?php echo $row_TipoDoc['IdTipoDocumento']; ?>"
									<?php if ((($edit == 1) && (isset($row['IdTipoDocumento'])) && (strcmp($row_TipoDoc['IdTipoDocumento'], $row['IdTipoDocumento']) == 0))) {echo "selected=\"selected\"";}?>>
										<?php echo $row_TipoDoc['DeTipoDocumento']; ?>
									</option>
							<?php }?>
							<optgroup label='Otros'></optgroup>
							<option value="OTRO" <?php if (($edit == 1) && ($swOtro == 1 && $row['IdTipoDocumento'] != "")) {echo "selected=\"selected\"";}?>>OTRO</option>
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
					<div class="col-md-6">
						<label class="control-label">Id Motivo <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="IdMotivoAutorizacion" id="IdMotivoAutorizacion" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['IdMotivoAutorizacion'];}?>">
					</div>
					<div class="col-md-6">
						<label class="control-label">Motivo <span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="MotivoAutorizacion" id="MotivoAutorizacion" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['MotivoAutorizacion'];}?>">
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-12">
						<label class="control-label">Modelo autorización SAP B1 <span class="text-danger">*</span></label>
						<select name="IdModeloAutorizacionSAPB1" class="form-control select2" id="IdModeloAutorizacionSAPB1" required>
							<option value="">Seleccione...</option>
							<?php while ($row_ModeloAutorizacion = sqlsrv_fetch_array($SQL_ModeloAutorizacion)) {?>
								<option value="<?php echo $row_ModeloAutorizacion['IdModeloAutorizacion']; ?>" <?php if ((isset($row['IdModeloAutorizacionSAPB1'])) && (strcmp($row_ModeloAutorizacion['IdModeloAutorizacion'], $row['IdModeloAutorizacionSAPB1']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_ModeloAutorizacion['ModeloAutorizacion']; ?></option>
							<?php }?>
						</select>
					</div>
				</div>

				<br><br><br><br>
				<div class="form-group">
					<div class="col-md-6">
						<label class="control-label">Usuario autorización SAP B1 <span class="text-danger">*</span></label>
						<select name="IdUsuarioAutorizacion" class="form-control select2" id="IdUsuarioAutorizacion" required>
							<option value="">Seleccione...</option>
							<?php while ($row_UsuarioSAP = sqlsrv_fetch_array($SQL_UsuariosSAP)) {?>
								<option value="<?php echo $row_UsuarioSAP['USERID']; ?>" <?php if ((isset($row['IdUsuarioAutorizacionSAPB1'])) && (strcmp($row_UsuarioSAP['USERID'], $row['IdUsuarioAutorizacionSAPB1']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_UsuarioSAP['USER_CODE']; ?></option>
							<?php }?>
						</select>
					</div>
					<div class="col-md-6">
						<label class="control-label">Password usuario SAP B1 <span class="text-danger">*</span></label>
						<input type="password" class="form-control" name="PassUsuarioAutorizacion" id="PassUsuarioAutorizacion" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['PassUsuarioAutorizacionSAPB1'];}?>">
						<a href="#" id="aVerPass" onClick="javascript:MostrarPassword();" title="Mostrar contrase&ntilde;a" class="btn btn-default btn-xs"><span id="VerPass" class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a>
					</div>
				</div>
			<?php }?>
		</div>
	</div>
</div>
<div class="modal-footer">
	<button type="submit" class="btn btn-success m-t-md"><i class="fa fa-check"></i> Aceptar</button>

	<?php if ($doc == "Procesos") {?>
		<button type="button" class="btn btn-info m-t-md pull-left" onClick="Validar('<?php echo $doc; ?>','<?php echo $id; ?>');"><i class="fa fa-database"></i> Validar Condiciones</button>
	<?php }?>

	<!-- Desactivado
	<?php if ($edit == 1) {?><button type="button" class="btn btn-danger m-t-md pull-left" onClick="Eliminar('<?php echo $doc; ?>','<?php echo $id; ?>');"><i class="fa fa-trash"></i> Eliminar</button><?php }?>
	Hasta aquí -->

	<button type="button" class="btn btn-warning m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>

	<div class="form-group" id="CondicionesContainer" style="display: none;">
		<br><br><br>

		<label class="control-label pull-left text-muted">Validación de Condiciones</label>
		<a class="btn btn-info btn-xs" id="Raw" target="_blank"><i class="fa fa-eye"></i> Ver respuesta en texto plano</a>

		<textarea rows="3" type="text" name="Validacion" id="Validacion" class="form-control text-muted" readonly>Resultado de la Validación</textarea>
	</div>
</div>
	<input type="hidden" id="TipoDoc" name="TipoDoc" value="<?php echo $doc; ?>" />
	<input type="hidden" id="ID_Actual" name="ID_Actual" value="<?php echo $id; ?>" />
	<input type="hidden" id="Metodo" name="Metodo" value="<?php echo $Metodo; ?>" />
	<input type="hidden" id="frmType" name="frmType" value="1" />
</form>
<script>
$(document).ready(function(){
	// SMM, 19/08/2022
	$('.panel-collapse').on('show.bs.collapse', function () {
    $(this).siblings('.panel-heading').addClass('active');
  });

  $('.panel-collapse').on('hide.bs.collapse', function () {
    $(this).siblings('.panel-heading').removeClass('active');
  });
  // Hasta aquí, 19/08/2022

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
<script>
function Eliminar(doc,id){
	var result=true;

	Swal.fire({
		title: "¿Está seguro que desea eliminar este registro?",
		icon: "question",
		showCancelButton: true,
		confirmButtonText: "Si, confirmo",
		cancelButtonText: "No"
	}).then((result) => {
		if (result.isConfirmed) {
			$('.ibox-content').toggleClass('sk-loading',true);
			$.ajax({
				url:"ajx_buscar_datos_json.php",
				data:{type:34,
					  item:doc,
					  id:id
					 },
				dataType:'json',
				async: false,
				success: function(data){
					if(data.Estado=='0'){
						result=false;
						Swal.fire({
							title: data.Title,
							text: data.Mensaje,
							icon: data.Icon,
						});
						$('.ibox-content').toggleClass('sk-loading',false);
					}else{
						document.getElementById("Metodo").value="3";
						$("#frm_NewParam").submit();
					}
				}
			});
		}
	});

	return result;
}
