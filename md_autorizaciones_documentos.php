<?php
require_once "includes/conexion.php";

$Title = "Crear nuevo registro";
$Metodo = 1;

$edit = isset($_POST['edit']) ? $_POST['edit'] : 0;
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$id = isset($_POST['id']) ? $_POST['id'] : "";

$dir_new = CrearObtenerDirAnx("formularios/monitoreos_temperaturas/planos");

// SMM, 18/07/2022
$SQL_TipoDoc = Seleccionar("uvw_tbl_ObjetosSAP", "*", "CategoriaObjeto = 'Documentos de ventas'", 'CategoriaObjeto, DeTipoDocumento');
$SQL_ModeloAutorizacion = Seleccionar("uvw_Sap_tbl_ModelosAutorizaciones", "*");

// Perfiles Usuarios, SMM 14/05/2022
$SQL_Perfiles = Seleccionar('uvw_tbl_PerfilesUsuarios', '*');

if ($edit == 1 && $id != "") {
    $Title = "Editar registro";
    $Metodo = 2;
    if ($doc == "Motivos") {
        $SQL = Seleccionar('tbl_Autorizaciones_Motivos', '*', "IdInterno='" . $id . "'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Productos") {
        $SQL = Seleccionar('tbl_ProductosPuerto', '*', "id_producto_puerto='" . $id . "'");
        $row = sqlsrv_fetch_array($SQL);
    }
}
?>

<style>
	/*
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

<form id="frm_NewParam" method="post" action="parametros_autorizaciones_documentos.php" enctype="multipart/form-data">
<div class="modal-header">
	<h4 class="modal-title">
		<?php echo "Crear nuevo motivo autorización"; ?>
	</h4>
</div>
<div class="modal-body">
	<div class="form-group">
		<div class="ibox-content">
			<?php include "includes/spinner.php";?>
			<?php if ($doc == "Motivos") {?>
				<div class="form-group">
					<label class="control-label">Id Motivo <span class="text-danger">*</span></label>
					<input type="text" class="form-control" name="IdMotivoAutorizacion" id="IdMotivoAutorizacion" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['IdMotivoAutorizacion'];}?>">
				</div>
				<div class="form-group">
					<label class="control-label">Motivo <span class="text-danger">*</span></label>
					<input type="text" class="form-control" name="MotivoAutorizacion" id="MotivoAutorizacion" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['MotivoAutorizacion'];}?>">
				</div>
				<div class="form-group">
					<label class="control-label">Comentarios</label>
					<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"><?php if ($edit == 1) {echo $row['Comentarios'];}?></textarea>
				</div>
				<div class="form-group">
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
				<div class="form-group">
					<label class="control-label">Modelo autorización SAP B1 <span class="text-danger">*</span></label>
					<select name="IdFormato" class="form-control select2" id="IdFormato" required>
						<option value="">Seleccione...</option>
						<?php while ($row_ModeloAutorizacion = sqlsrv_fetch_array($SQL_ModeloAutorizacion)) {?>
							<option value="<?php echo $row_ModeloAutorizacion['IdModeloAutorizacion']; ?>" <?php if ((isset($row['IdFormato'])) && (strcmp($row_ModeloAutorizacion['IdModeloAutorizacion'], $row['IdFormato']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_ModeloAutorizacion['ModeloAutorizacion']; ?></option>
						<?php }?>
					</select>
				</div>
				<div class="form-group">
					<label class="control-label">Estado <span class="text-danger">*</span></label>
					<select class="form-control" id="Estado" name="Estado">
						<option value="Y" <?php if (($edit == 1) && ($row['Estado'] == "Y")) {echo "selected=\"selected\"";}?>>ACTIVO</option>
						<option value="N" <?php if (($edit == 1) && ($row['Estado'] == "N")) {echo "selected=\"selected\"";}?>>INACTIVO</option>
					</select>
				</div>
				<div class="form-group">
					<label class="control-label">Perfiles Usuarios</label>
					<select data-placeholder="Digite para buscar..." name="Perfiles[]" class="form-control select2" id="Perfiles" multiple>
						<?php while ($row_Perfil = sqlsrv_fetch_array($SQL_Perfiles)) {?>
							<option value="<?php echo $row_Perfil['ID_PerfilUsuario']; ?>"
							<?php //if (in_array($row_Perfil['ID_PerfilUsuario'], $ids_perfiles)) {echo "selected";} ?>>
								<?php echo $row_Perfil['PerfilUsuario']; ?>
							</option>
						<?php }?>
					</select>
				</div>
				<div class="form-group">
					<label class="control-label">Condiciones</label>
					<textarea name="Condiciones" rows="3" maxlength="3000" class="form-control" id="Condiciones" type="text"><?php if ($edit == 1) {echo $row['Condiciones'];}?></textarea>
				</div>
			<?php } elseif ($doc == "Transportes") {?>
			<div class="form-group">
				<label class="control-label">Código de motonave <span class="text-danger">*</span></label>
				<input type="text" class="form-control" name="CodigoTransporte" id="CodigoTransporte" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['id_transporte_puerto'];}?>">
			</div>
			<div class="form-group">
				<label class="control-label">Nombre de motonave <span class="text-danger">*</span></label>
				<input type="text" class="form-control" name="NombreTransporte" id="NombreTransporte" required autocomplete="off" value="<?php if ($edit == 1) {echo $row['transporte_puerto'];}?>">
			</div>
			<div class="form-group">
				<label class="control-label">REG (Registro capitanía)</label>
				<input type="text" class="form-control" name="RegistroCap" id="RegistroCap" autocomplete="off" value="<?php if ($edit == 1) {echo $row['registro_capitania'];}?>">
			</div>
			<div class="form-group">
				<label class="control-label">Comentarios</label>
				<textarea name="ComentariosTransporte" rows="3" maxlength="3000" class="form-control" id="ComentariosTransporte" type="text"><?php if ($edit == 1) {echo $row['comentarios'];}?></textarea>
			</div>
			<div class="form-group">
				<label class="control-label">Estado <span class="text-danger">*</span></label>
				<select class="form-control" id="EstadoTransporte" name="EstadoTransporte">
					 <option value="Y" <?php if (($edit == 1) && ($row['estado'] == "Y")) {echo "selected=\"selected\"";}?>>ACTIVO</option>
					 <option value="N" <?php if (($edit == 1) && ($row['estado'] == "N")) {echo "selected=\"selected\"";}?>>INACTIVO</option>
				 </select>
			</div>
			<?php }?>
		</div>
	</div>
</div>
<div class="modal-footer">
	<button type="submit" class="btn btn-success m-t-md"><i class="fa fa-check"></i> Aceptar</button>

	<!-- Desactivado
	<?php if ($edit == 1) {?><button type="button" class="btn btn-danger m-t-md pull-left" onClick="Eliminar('<?php echo $doc; ?>','<?php echo $id; ?>');"><i class="fa fa-trash"></i> Eliminar</button><?php }?>
	Hasta aquí -->

	<button type="button" class="btn btn-warning m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
</div>
	<input type="hidden" id="TipoDoc" name="TipoDoc" value="<?php echo $doc; ?>" />
	<input type="hidden" id="ID_Actual" name="ID_Actual" value="<?php echo $id; ?>" />
	<input type="hidden" id="Metodo" name="Metodo" value="<?php echo $Metodo; ?>" />
	<input type="hidden" id="frmType" name="frmType" value="1" />
</form>
<script>
$(document).ready(function(){
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

	<?php if ($doc == "Bodegas") {?>

		 $("#NombreClienteBodega").change(function(){
			var NomCliente=document.getElementById("NombreClienteBodega");
			var Cliente=document.getElementById("ClienteBodega");
			if(NomCliente.value==""){
				Cliente.value="";
				$("#ClienteBodega").trigger("change");
			}
		});
		$("#ClienteBodega").change(function(){
			var Cliente=document.getElementById("ClienteBodega");
			$.ajax({
				type: "POST",
				url: "ajx_cbo_sucursales_clientes_simple.php?CardCode="+Cliente.value+"&sucline=1&selec=1",
				success: function(response){
					$("#SucursalBodega").chosen("destroy");
					$('#SucursalBodega').html(response);
					$('#SucursalBodega').chosen({width: "100%"});
				}
			});
		});

		  var options = {
			  url: function(phrase) {
				  return "ajx_buscar_datos_json.php?type=7&id="+phrase;
			  },
			  getValue: "NombreBuscarCliente",
			  requestDelay: 400,
			  list: {
				  match: {
					  enabled: true
				  },
				  onClickEvent: function() {
					  var value = $("#NombreClienteBodega").getSelectedItemData().CodigoCliente;
					  $("#ClienteBodega").val(value).trigger("change");
				  }
			  }
		 };
		$("#NombreClienteBodega").easyAutocomplete(options);
 	<?php }?>
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
</script>