<?php
require_once "includes/conexion.php";

$Title = "Crear nuevo registro";
$Metodo = 1;

$edit = isset($_POST['edit']) ? $_POST['edit'] : 0;
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$id = isset($_POST['id']) ? $_POST['id'] : "";

$SQL_TiposEquiposModal = Seleccionar('tbl_TarjetaEquipo_TiposEquipos', '*');
$SQL_PropiedadesModal = Seleccionar('tbl_TarjetaEquipo_TiposEquipos_Propiedades', '*');

$SQL_TiposEquipos_Campos = Seleccionar('tbl_TarjetaEquipo_TiposEquipos_Campos', '*');

if ($edit == 1 && $id != "") {
    $Title = "Editar registro";
    $Metodo = 2;

    if ($doc == "Tipos") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_TiposEquipos', '*', "ID='$id'");
        $row = sqlsrv_fetch_array($SQL);
    } elseif ($doc == "Propiedades") {
        $SQL = Seleccionar('tbl_TarjetaEquipo_TiposEquipos_Propiedades', '*', "ID='$id'");
        $row = sqlsrv_fetch_array($SQL);
    }

    $ids_perfiles = isset($row['Perfiles']) ? explode(";", $row['Perfiles']) : [];
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

				<!-- Inicio Consulta -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Tipo Equipo <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombreTipoEquipo" id="NombreTipoEquipo" value="<?php if ($edit == 1) {echo $row['NombreTipoEquipo'];}?>">
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
				<!-- Fin Consulta -->

			<?php } elseif ($doc == "Propiedades") {?>

				<!-- Inicio Entrada -->
				<div class="form-group row">
					<div class="col-md-6">
						<label class="control-label">Nombre Propiedad <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="NombrePropiedad" id="NombrePropiedad" value="<?php if ($edit == 1) {echo $row['NombrePropiedad'];}?>">
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
					<div class="col-md-6">
						<label class="control-label">Tipo Propiedad <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="TipoPropiedad" id="TipoPropiedad" value="<?php if ($edit == 1) {echo $row['TipoPropiedad'];}?>">
					</div>

					<div class="col-md-6">
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
						<label class="control-label">Tabla Vinculada <span class="text-danger">*</span></label>
						<input type="text" class="form-control" autocomplete="off" required name="TablaVinculada" id="TablaVinculada" value="<?php if ($edit == 1) {echo $row['TablaVinculada'];}?>">
					</div>

					<div class="col-md-6">
						<label class="control-label">Obligatorio <span class="text-danger">*</span></label>
						<select class="form-control" id="Obligatorio" name="Obligatorio" required>
							<option value="Y" <?php if (($edit == 1) && ($row['Obligatorio'] == "Y")) {echo "selected";}?>>SI</option>
							<option value="N" <?php if (($edit == 1) && ($row['Obligatorio'] == "N")) {echo "selected";}?>>NO</option>
						</select>
					</div>
				</div>
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
$(document).ready(function() {
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
	$(".select2").select2();

	$("#TipoCampo").on("change", function() {
		if($(this).val() == "Sucursal" || $(this).val() == "Lista") {
			$("#Multiple").prop("disabled", false);
		} else {
			$("#Multiple").prop("disabled", true);
		}

		if($(this).val() == "Lista") {
			$("#CamposVista").css("display", "block");
		} else {
			$("#CamposVista").css("display", "none");
		}
	});

	// Cargar lista de campos dependiendo de la vista.
	$("#VistaLista").on("change", function() {
		$.ajax({
			type: "POST",
			url: `ajx_cbo_select.php?type=12&id=${$(this).val()}&obligatorio=1`,
			success: function(response){
				$('#EtiquetaLista').html(response).fadeIn();
				$('#ValorLista').html(response).fadeIn();

				<?php if (($edit == 1) && ($id != "")) {?>
					$('#EtiquetaLista').val("<?php echo $row['EtiquetaLista'] ?? ""; ?>");
					$('#ValorLista').val("<?php echo $row['ValorLista'] ?? ""; ?>");
				<?php }?>

				$('#EtiquetaLista').trigger('change');
				$('#ValorLista').trigger('change');
			}
		});
	});

	// Cargar entradas dependiendo de la consulta.
	$("#ID_Consulta").on("change", function() {
		$.ajax({
			type: "POST",
			url: `ajx_cbo_select.php?type=44&id=${$(this).val()}&input=<?php echo $row['ParametroEntrada'] ?? ""; ?>`,
			success: function(response){
				$('#ParametroEntrada').html(response).fadeIn();
				$('#ParametroEntrada').trigger('change');
			}
		});
	});


	<?php if (($edit == 1) && ($id != "")) {?>
		$('#VistaLista').trigger('change');

		$('#ID_Consulta').trigger('change');
		$('#TipoCampo').trigger('change');
	<?php }?>
 });
</script>
