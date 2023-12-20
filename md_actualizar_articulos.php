<?php require_once "includes/conexion.php";
// Dimensiones. SMM, 24/05/2023
$DimSeries = intval(ObtenerVariable("DimensionSeries"));
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimActive='Y'");

// Pruebas, SMM 29/05/2023
// $SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', 'DimCode IN (1,2,3,4,5)');

$array_Dimensiones = [];
while ($row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones)) {
	array_push($array_Dimensiones, $row_Dimension);
}

$encode_Dimensiones = json_encode($array_Dimensiones);
$cadena_Dimensiones = "JSON.parse('$encode_Dimensiones'.replace(/\\n|\\r/g, ''))";
// Hasta aquí, SMM 24/05/2023

$Edit = $_POST['Edit'];
$DocType = $_POST['DocType'];
$DocId = $_POST['DocId'];
$DocEvent = $_POST['DocEvent'];
$CardCode = $_POST['CardCode'];
$IdSeries = $_POST['IdSeries'];
$Proyecto = $_POST['IdProyecto'];
$IdEmpleado = $_POST['IdEmpleado'];
$ListaPrecio = $_POST['ListaPrecio'];

// SMM, 20/12/2023
$Inventario = $_POST['Inventario'] ?? "";

// SMM, 21/06/2023
$DefaultSelection = false;

// Proyectos. SMM, 24/05/2023
$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', '', 'DeProyecto');

// Almacenes. SMM, 24/05/2023
$SQL_Almacen = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'WhsCode, WhsName', "IdSeries='$IdSeries'", "WhsCode, WhsName", 'WhsName');
$SQL_AlmacenDestino = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'ToWhsCode, ToWhsName', "IdSeries='$IdSeries'", "ToWhsCode, ToWhsName", 'ToWhsName');

// Sucursales. SMM, 26/05/2023
$SQL_Sucursales = SeleccionarGroupBy('uvw_tbl_SeriesSucursalesAlmacenes', 'IdSucursal "OcrCode", DeSucursal "OcrName"', "IdSeries='$IdSeries'", "IdSucursal, DeSucursal", 'DeSucursal');

// Lista de precios, 29/05/2023
$SQL_ListaPrecios = Seleccionar('uvw_Sap_tbl_ListaPrecios', '*');
$SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*', "Estado = 'Y'", 'DE_EmpVentas');

// SMM, 05/06/2023
$SQL_OT_ORIGEN = Seleccionar('uvw_Sap_tbl_OT_Origen', 'IdOT_Origen "IdTipoOT", OT_Origen "TipoOT"', '', 'IdOT_Origen');
$SQL_OT_SEDE_EMPRESA = Seleccionar('uvw_Sap_tbl_OT_SedeEmpresa', 'IdOT_SedeEmpresa "IdSedeEmpresa", OT_SedeEmpresa "SedeEmpresa"', '', 'IdOT_SedeEmpresa');
$SQL_OT_TIPOCARGO = Seleccionar('uvw_Sap_tbl_OT_TipoLlamada', 'IdOT_TipoLlamada "IdTipoCargo", OT_TipoLlamada "TipoCargo"', '', 'IdOT_TipoLlamada');
$SQL_OT_TIPOPROBLEMA = Seleccionar('uvw_Sap_tbl_OT_TipoProblema', 'IdOT_TipoProblema "IdTipoProblema", OT_TipoProblema "TipoProblema"', '', 'IdOT_TipoProblema');
$SQL_OT_TIPOPREVENTI = Seleccionar('uvw_Sap_tbl_OT_TipoPreventivo', 'IdOT_TipoPreventivo "IdTipoPreventivo", OT_TipoPreventivo "TipoPreventivo"', '', 'IdOT_TipoPreventivo');

// Datos de dimensiones del usuario actual, 31/05/2023
$SQL_DatosEmpleados = Seleccionar("uvw_tbl_Usuarios", "*", "ID_Usuario='" . $_SESSION['CodUser'] . "'");
$row_DatosEmpleados = sqlsrv_fetch_array($SQL_DatosEmpleados);

// Filtrar conceptos de salida.
$Where_Conceptos = "ID_Usuario='" . $_SESSION['CodUser'] . "'";
$SQL_Conceptos = Seleccionar('uvw_tbl_UsuariosConceptos', '*', $Where_Conceptos);

$Conceptos = array();
while ($Concepto = sqlsrv_fetch_array($SQL_Conceptos)) {
	$Conceptos[] = ("'" . $Concepto['IdConcepto'] . "'");
}

$Filtro_Conceptos = "Estado = 'Y'";
if (count($Conceptos) > 0 && ($edit == 0)) {
	$Filtro_Conceptos .= " AND id_concepto_salida IN (";
	$Filtro_Conceptos .= implode(",", $Conceptos);
	$Filtro_Conceptos .= ")";
}

$SQL_ConceptoSalida = Seleccionar('tbl_SalidaInventario_Conceptos', '*', $Filtro_Conceptos, 'id_concepto_salida');
// Hasta aquí, 20/12/2023
?>

<style>
	.select2-dropdown {
		z-index: 9000;
	}

	.ibox-title {
		border-radius: 5px;
		margin-bottom: 10px;
	}

	.ibox-title a {
		color: inherit !important;
	}
</style>

<div class="modal-dialog modal-lg" style="width: 75% !important;">
	<div class="modal-content">
		<div class="modal-body">
			<!-- Inicio, filtros -->
			<form id="formActualizar" class="form-horizontal">
				<div class="row">
					<!-- data-toggle="collapse" data-target="#filtros" -->
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-filter"></i> Datos para filtrar</h5>
					</div>

					<div class="collapse in" id="filtros">
						<div class="col-lg-4">
							<div class="form-group">
								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Tipo OT (Origen Llamada)</label>

									<select name="CDU_IdTipoOTUpd" id="CDU_IdTipoOTUpd" class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_ORIGEN = sqlsrv_fetch_array($SQL_OT_ORIGEN)) { ?>
											<option value="<?php echo $row_ORIGEN['IdTipoOT']; ?>"><?php echo $row_ORIGEN['IdTipoOT'] . " - " . $row_ORIGEN['TipoOT']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Tipo Problema</label>

									<select name="CDU_IdTipoProblemaUpd" id="CDU_IdTipoProblemaUpd"
										class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_TIPOPROBLEMA = sqlsrv_fetch_array($SQL_OT_TIPOPROBLEMA)) { ?>
											<option value="<?php echo $row_TIPOPROBLEMA['IdTipoProblema']; ?>"><?php echo $row_TIPOPROBLEMA['IdTipoProblema'] . " - " . $row_TIPOPROBLEMA['TipoProblema']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Sede Empresa</label>

									<select name="CDU_IdSedeEmpresaUpd" id="CDU_IdSedeEmpresaUpd"
										class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_SEDE_EMPRESA = sqlsrv_fetch_array($SQL_OT_SEDE_EMPRESA)) { ?>
											<option value="<?php echo $row_SEDE_EMPRESA['IdSedeEmpresa']; ?>"><?php echo $row_SEDE_EMPRESA['IdSedeEmpresa'] . " - " . $row_SEDE_EMPRESA['SedeEmpresa']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Tipo Cargo (Tipo Llamada)</label>

									<select name="CDU_IdTipoCargoUpd" id="CDU_IdTipoCargoUpd"
										class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_TIPOCARGO = sqlsrv_fetch_array($SQL_OT_TIPOCARGO)) { ?>
											<option value="<?php echo $row_TIPOCARGO['IdTipoCargo']; ?>"><?php echo $row_TIPOCARGO['IdTipoCargo'] . " - " . $row_TIPOCARGO['TipoCargo']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Tipo Preventivo</label>

									<select name="CDU_IdTipoPreventivoUpd" id="CDU_IdTipoPreventivoUpd"
										class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_TIPOPREVENTI = sqlsrv_fetch_array($SQL_OT_TIPOPREVENTI)) { ?>
											<option value="<?php echo $row_TIPOPREVENTI['IdTipoPreventivo']; ?>"><?php echo $row_TIPOPREVENTI['IdTipoPreventivo'] . " - " . $row_TIPOPREVENTI['TipoPreventivo']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->
							</div> <!-- form-group -->
						</div> <!-- col-lg-4 -->

						<div class="col-lg-4">
							<div class="form-group">
								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Almacén origen</label>

									<select name="WhsCodeUpd" id="WhsCodeUpd" class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_Almacen = sqlsrv_fetch_array($SQL_Almacen)) { ?>
											<option <?php if ($DefaultSelection && ($row_DatosEmpleados["AlmacenOrigen"] == $row_Almacen['WhsCode'])) {
												echo "selected";
											} ?> value="<?php echo $row_Almacen['WhsCode']; ?>"><?php echo $row_Almacen['WhsCode'] . " - " . $row_Almacen['WhsName']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Almacén destino</label>

									<select name="ToWhsCodeUpd" id="ToWhsCodeUpd" class="form-control select2" <?php if($Inventario == "") {
										echo "disabled";
									} ?>>
										<option value="">Seleccione...</option>

										<?php while ($row_AlmacenDestino = sqlsrv_fetch_array($SQL_AlmacenDestino)) { ?>
											<option value="<?php echo $row_AlmacenDestino['ToWhsCode']; ?>"><?php echo $row_AlmacenDestino['ToWhsCode'] . " - " . $row_AlmacenDestino['ToWhsName']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Proyecto</label>

									<select id="PrjCodeUpd" name="PrjCodeUpd" class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
											<option <?php if ($DefaultSelection && ($Proyecto == $row_Proyecto['IdProyecto'])) {
												echo "selected";
											} ?> value="<?php echo $row_Proyecto['IdProyecto']; ?>">
												<?php echo $row_Proyecto['IdProyecto'] . " - " . $row_Proyecto['DeProyecto']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Empleado de ventas</label>

									<select name="EmpVentasUpd" id="EmpVentasUpd" class="form-control select2">
										<option value="">Seleccione...</option>

										<?php while ($row_EmpleadosVentas = sqlsrv_fetch_array($SQL_EmpleadosVentas)) { ?>
											<option <?php if ($DefaultSelection && ($IdEmpleado == $row_EmpleadosVentas['ID_EmpVentas'])) {
												echo "selected";
											} ?> value="<?php echo $row_EmpleadosVentas['ID_EmpVentas']; ?>"><?php echo $row_EmpleadosVentas['ID_EmpVentas'] . " - " . $row_EmpleadosVentas['DE_EmpVentas']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Concepto Salida</label>

									<select name="ConceptoSalidaUpd" id="ConceptoSalidaUpd" class="form-control select2" <?php if($Inventario == "") {
										echo "disabled";
									} ?>>
										<option value="">Seleccione...</option>

										<?php while ($row_ConceptoSalida = sqlsrv_fetch_array($SQL_ConceptoSalida)) { ?>
											<option value="<?php echo $row_ConceptoSalida['id_concepto_salida']; ?>">
												<?php echo $row_ConceptoSalida['id_concepto_salida'] . "-" . $row_ConceptoSalida['concepto_salida']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->
							</div> <!-- form-group -->
						</div> <!-- col-lg-4 -->

						<div class="col-lg-4">
							<div class="form-group">
								<?php foreach ($array_Dimensiones as &$dim) { ?>
									<div class="col-xs-12" style="margin-bottom: 10px;">
										<label class="control-label">
											<?php echo $dim['DescPortalOne']; ?>
										</label>

										<select name="<?php echo "OcrCode" . $dim['DimCode'] . "Upd"; ?>"
											id="<?php echo "OcrCode" . $dim['DimCode'] . "Upd"; ?>"
											class="form-control select2">
											<option value="">Seleccione...</option>

											<?php $SQL_Dim = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', 'DimCode=' . $dim['DimCode']); ?>

											<?php if ($dim['DimCode'] == $DimSeries) { ?>
												<?php $SQL_Dim = $SQL_Sucursales; ?>
											<?php } ?>

											<?php while ($row_Dim = sqlsrv_fetch_array($SQL_Dim)) { ?>
												<?php $DimCode = intval($dim['DimCode']); ?>
												<?php $OcrId = ($DimCode == 1) ? "" : $DimCode; ?>

												<option <?php if ($DefaultSelection && ($row_DatosEmpleados["CentroCosto$DimCode"] == $row_Dim['OcrCode'])) {
													echo "selected";
												} ?> value="<?php echo $row_Dim['OcrCode']; ?>">
													<?php echo $row_Dim['OcrCode'] . " - " . $row_Dim['OcrName']; ?>
												</option>
											<?php } ?>
										</select>
									</div> <!-- col-xs-12 -->
								<?php } ?>
							</div> <!-- form-group -->
						</div> <!-- col-lg-4 -->
					</div> <!-- ibox-content -->
				</div> <!-- row -->
			</form>
			<br><br><br>
			<!-- Fin, filtros -->
		</div> <!-- modal-body -->
		<div class="modal-footer">
			<button type="button" class="btn btn-success m-t-md" id="btnAceptarUpd"><i class="fa fa-check"></i>
				Aceptar</button>
			<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i>
				Cerrar</button>
		</div>
	</div> <!-- modal-content -->
</div> <!-- modal-dialog -->

<script>
	function actualizarLineas(json) {
		let dt = <?php echo $DocType; ?>;
		let did = <?php echo $DocId; ?>;
		let dev = <?php echo $DocEvent; ?>;
		let cc = "<?php echo $CardCode; ?>";
		let edit = <?php echo $Edit; ?>;

		// Obtén el elemento con el ID 'DataGrid'
		let dataGrid = document.getElementById('DataGrid');

		// Crea un objeto URL a partir del atributo 'src'
		let url = new URL(dataGrid.src);

		if (edit == 1) {
			// Elimina todos los parámetros existentes
			url.search = '';

			// ?id&evento&type=2
			url.searchParams.set('id', '<?php echo base64_encode($DocId); ?>');
			url.searchParams.set('evento', '<?php echo base64_encode($DocEvent); ?>');
			url.searchParams.set('type', '2');
		} else {
			// ?id=0&type=1&usr&cardcode
			console.log(url.search);
		}

		jQuery.each(json, function (key, value) {
			if (value != "") {
				console.log(key, value);
				let name = key.replace(/Upd$/, "");

				// Renombro los campos que sean necesarios.
				if (name == "OcrCode1") name = "OcrCode";

				// URL del Ajax que consulta al registro número 36, actualización en detalle.
				let urlAjax = `registro.php?P=36&actodos=1&line=0&doctype=${dt}&name=${name}&value=${Base64.encode(value)}`;

				if (edit == 0) {
					urlAjax += `&cardcode=${cc}&whscode=0&type=1`;
				} else {
					urlAjax += `&id=${did}&evento=${dev}&type=2`;
				}

				$.ajax({
					type: "GET",
					url: urlAjax,
					success: function (response) {
						// Asigna la nueva URL al atributo 'src' del elemento
						dataGrid.src = url.href;
					},
					error: function (error) {
						// Manejar el error de la petición AJAX
						console.log("Error actualización:", error);
						
						// alert("Ocurrio un error al actualizar los articulos, se recomienda repetir el procedimiento o consultar al administrador");
					}
				});
			}
		});

		// Cerrar el modal al finalizar la lógica
		$("#mdLoteArticulos").modal("hide");
	}

	$(document).ready(function () {
		$(".select2").select2();

		$('#formActualizar').on('submit', function (event) {
			event.preventDefault();
		});

		$("#btnAceptarUpd").on("click", function () {
			$("#formActualizar").submit();
		});

		$("#formActualizar").validate({
			submitHandler: function (form) {
				Swal.fire({
					title: "¿Desea actualizar las lineas?",
					icon: "question",
					showCancelButton: true,
					confirmButtonText: "Si, confirmo",
					cancelButtonText: "No"
				}).then((result) => {
					if (result.isConfirmed) {
						let formData = new FormData(form);
						let jsonData = Object.fromEntries(formData);

						actualizarLineas(jsonData);
					} else {
						console.log("Se cancelo la actualización");
					}
				});
			} // submitHandler
		});

		$('.chosen-select').chosen({ width: "100%" });

		$('.i-checks').iCheck({
			checkboxClass: 'icheckbox_square-green',
			radioClass: 'iradio_square-green',
		});

		// SMM, 15/06/2023
		$("#IdTipoOT").change(function () {
			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=45&id=${$(this).val()}`,
				success: function (response) {
					$('#IdTipoProblema').html(response).fadeIn();
					$('#IdTipoProblema').trigger('change');
				}
			});
		});

		// SMM, 24/07/2023
		$("#IdTipoProblema").change(function () {
			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=48&id=${$(this).val()}`,
				success: function (response) {
					$('#IdTipoCargo').html(response).fadeIn();
					$('#IdTipoCargo').trigger('change');
				}
			});
		});
	});
</script>