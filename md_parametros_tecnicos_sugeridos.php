<?php
require_once "includes/conexion.php";

$Title = "Crear nuevo registro";
$Metodo = 1;

$edit = isset($_POST['edit']) ? $_POST['edit'] : 0;
$doc = isset($_POST['doc']) ? $_POST['doc'] : "";
$id = isset($_POST['id']) ? $_POST['id'] : "";

// Subconsultas.
$SQL_Serie = Seleccionar("uvw_Sap_tbl_SeriesLlamadas", "*");
$SQL_Origen = Seleccionar("uvw_Sap_tbl_LlamadasServiciosOrigen", "*");
$SQL_TipoProblema = Seleccionar("uvw_Sap_tbl_TipoProblemasLlamadas", "*");
$SQL_Marca = Seleccionar("uvw_Sap_tbl_LlamadasServicios_MarcaVehiculo", "*");
$SQL_Tecnico = Seleccionar("uvw_Sap_tbl_Recursos", "*");

if ($edit == 1 && $id != "") {
	$Title = "Editar registro";
	$Metodo = 2;

	if ($doc == "Tecnico") {
		$SQL = Seleccionar('uvw_tbl_SolicitudLlamadasServicios_TecnicosSugeridos', '*', "[ID]='$id'");
		$row = sqlsrv_fetch_array($SQL);
	}
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
	.panel-heading a:before {
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

<form id="frm_NewParam" method="post" action="parametros_tecnicos_sugeridos.php" enctype="multipart/form-data">
	<div class="modal-header">
		<h4 class="modal-title">
			<?php echo $Title; ?>
		</h4>
	</div>
	<div class="modal-body">
		<div class="form-group">
			<div class="ibox-content">
				<?php include "includes/spinner.php"; ?>
				<?php if ($doc == "Tecnico") { ?>
					<div class="form-group row">
						<div class="col-md-4">
							<label class="control-label">ID</label>
							<input type="text" class="form-control" name="id_interno" id="id_interno" readonly
								autocomplete="off" value="<?php if ($edit == 1) {
									echo $row['ID'];
								} ?>">
						</div>

						<div class="col-md-4">
							<label class="control-label">
								Serie <span class="text-danger">*</span>
							</label>
							
							<select name="IdSerieLlamada" class="form-control select2" id="IdSerieLlamada" required>
								<option value="">Seleccione...</option>
								
								<?php while ($row_Serie = sqlsrv_fetch_array($SQL_Serie)) { ?>
									<option value="<?php echo $row_Serie["IdSeries"]; ?>" <?php if (isset($row["IdSerieLlamada"]) && ($row_Serie["IdSeries"] == $row["IdSerieLlamada"])) {
											echo "selected";
										} ?>>
										<?php echo $row_Serie["IdSeries"] . " - " . $row_Serie["DeSeries"]; ?>
									</option>
								<?php } ?>
							</select>
						</div>

						<div class="col-md-4">
							<label class="control-label">
								Origen <span class="text-danger">*</span>
							</label>
							
							<select name="IdOrigen" class="form-control select2" id="IdOrigen" required>
								<option value="">Seleccione...</option>
								
								<?php while ($row_Origen = sqlsrv_fetch_array($SQL_Origen)) { ?>
									<option value="<?php echo $row_Origen["IdOrigenLlamada"]; ?>" <?php if (isset($row["IdOrigen"]) && ($row_Origen["IdOrigenLlamada"] == $row["IdOrigen"])) {
											echo "selected";
										} ?>>
										<?php echo $row_Origen["IdOrigenLlamada"] . " - " . $row_Origen["DeOrigenLlamada"]; ?>
									</option>
								<?php } ?>
							</select>
						</div>
					</div>

					<div class="form-group row">
						<div class="col-md-4">
							<label class="control-label">
								Tipo Problema <span class="text-danger">*</span>
							</label>
							
							<select name="IdTipoProblema" class="form-control select2" id="IdTipoProblema" required>
								<option value="">Seleccione...</option>
								
								<?php while ($row_TipoProblema = sqlsrv_fetch_array($SQL_TipoProblema)) { ?>
									<option value="<?php echo $row_TipoProblema["IdTipoProblemaLlamada"]; ?>" <?php if (isset($row["IdTipoProblema"]) && ($row_TipoProblema["IdTipoProblemaLlamada"] == $row["IdTipoProblema"])) {
											echo "selected";
										} ?>>
										<?php echo $row_TipoProblema["IdTipoProblemaLlamada"] . " - " . $row_TipoProblema["DeTipoProblemaLlamada"]; ?>
									</option>
								<?php } ?>
							</select>
						</div>
						
						<div class="col-md-4">
							<label class="control-label">
								Marca Vehiculo <span class="text-danger">*</span>
							</label>
							
							<select name="IdMarca" class="form-control select2" id="IdMarca" required>
								<option value="">Seleccione...</option>
								
								<?php while ($row_Marca = sqlsrv_fetch_array($SQL_Marca)) { ?>
									<option value="<?php echo $row_Marca["IdMarcaVehiculo"]; ?>" <?php if (isset($row["IdMarca"]) && ($row_Marca["IdMarcaVehiculo"] == $row["IdMarca"])) {
											echo "selected";
										} ?>>
										<?php echo $row_Marca["IdMarcaVehiculo"] . " - " . $row_Marca["DeMarcaVehiculo"]; ?>
									</option>
								<?php } ?>
							</select>
						</div>

						<div class="col-md-4">
							<label class="control-label">
								Técnico Sugerido <span class="text-danger">*</span>
							</label>
							
							<select name="IdTecnico" class="form-control select2" id="IdTecnico" required>
								<option value="">Seleccione...</option>
								
								<?php while ($row_Tecnico = sqlsrv_fetch_array($SQL_Tecnico)) { ?>
									<option value="<?php echo $row_Tecnico["ID_Empleado"]; ?>" <?php if (isset($row["IdTecnico"]) && ($row_Tecnico["ID_Empleado"] == $row["IdTecnico"])) {
											echo "selected";
										} ?>>
										<?php echo $row_Tecnico["ID_Empleado"] . " - " . $row_Tecnico["NombreEmpleado"]; ?>
									</option>
								<?php } ?>
							</select>
						</div>
					</div>

					<div class="form-group row">
						<div class="col-md-4">
							<label class="control-label">Estado <span class="text-danger">*</span></label>
							<select class="form-control" id="estado" name="estado">
								<option value="Y" <?php if (($edit == 1) && ($row['estado'] == "Y")) {
									echo "selected";
								} ?>>ACTIVO
								</option>
								<option value="N" <?php if (($edit == 1) && ($row['estado'] == "N")) {
									echo "selected";
								} ?>>
									INACTIVO</option>
							</select>
						</div>
					</div>
				<?php } ?>
				<!-- Fin Tecnico -->
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="submit" class="btn btn-success m-t-md"><i class="fa fa-check"></i> Aceptar</button>
		<button type="button" class="btn btn-warning m-t-md" data-dismiss="modal"><i class="fa fa-times"></i>
			Cerrar</button>
	</div>
	<input type="hidden" id="TipoDoc" name="TipoDoc" value="<?php echo $doc; ?>">
	<input type="hidden" id="ID_Actual" name="ID_Actual" value="<?php echo $id; ?>">
	<input type="hidden" id="Metodo" name="Metodo" value="<?php echo $Metodo; ?>">
</form>

<script>
	$(document).ready(function () {
		$('.panel-collapse').on('show.bs.collapse', function () {
			$(this).siblings('.panel-heading').addClass('active');
		});
		$('.panel-collapse').on('hide.bs.collapse', function () {
			$(this).siblings('.panel-heading').removeClass('active');
		});

		$("#frm_NewParam").validate({
			submitHandler: function (form) {
				let Metodo = document.getElementById("Metodo").value;
				if (Metodo != "3") {
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
		$('.chosen-select').chosen({ width: "100%" });

		// SMM, 26/07/2022
		$(".select2").select2();
	});
</script>