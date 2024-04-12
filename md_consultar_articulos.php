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

$OT = $_POST['OT'] ?? "";
$ObjType = $_POST['ObjType'];
$Edit = $_POST['Edit'];
$Borrador = $_POST['Borrador'] ?? "0";
$DocType = $_POST['DocType'];
$DocId = $_POST['DocId'];
$DocEvent = $_POST['DocEvent'];
$CardCode = $_POST['CardCode'];
$IdSeries = $_POST['IdSeries'];
$Proyecto = $_POST['IdProyecto'];
$IdEmpleado = $_POST['IdEmpleado'];
$ListaPrecio = $_POST['ListaPrecio'];

// Solicitado para. SMM, 12/04/2024
$CodEmpleado = $_POST['CodEmpleado'] ?? "";

// SMM, 14/10/2023
$Solicitud_OT = $_POST['Solicitud'] ?? "";

// SMM, 20/12/2023
$Inventario = $_POST['Inventario'] ?? "";

// Valores predeterminados en los campos de documentos del usuario según el tipo.
$OrigenLlamada = ObtenerValorDefecto($ObjType, "OrigenLlamada", false);
$SedeEmpresa = ObtenerValorDefecto($ObjType, "SedeEmpresa", false);
$TipoPreventivo = ObtenerValorDefecto($ObjType, "TipoPreventivo", false);
$TipoProblemaLlamada = ObtenerValorDefecto($ObjType, "TipoProblemaLlamada", false);
$TipoLlamada = ObtenerValorDefecto($ObjType, "TipoLlamada", false);

// Orden de trabajo (Llamada de servicio). SMM, 28/06/2023
$SQL_OT = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "[ID_LlamadaServicio]='$OT'");

$row_OT = array();
if(sqlsrv_has_rows($SQL_OT)) {
	$row_OT = sqlsrv_fetch_array($SQL_OT);
} else {
	// Buscar parámetros en la solicitud. SMM, 14/10/2023
	$SQL_OT = Seleccionar('uvw_tbl_SolicitudLlamadasServicios', '*', "[ID_SolicitudLlamadaServicio]='$Solicitud_OT'");
	$row_OT = sqlsrv_fetch_array($SQL_OT);
}

if (isset($row_OT["IdOrigenLlamada"]) && ($row_OT["IdOrigenLlamada"] != "")) {
	$IdOrigenLlamada = $row_OT["IdOrigenLlamada"];

	$SQL_Origen = Seleccionar("uvw_Sap_tbl_LlamadasServiciosOrigen", '*', "IdOrigenLlamada='$IdOrigenLlamada'");
	$row_Origen = sqlsrv_fetch_array($SQL_Origen);
	
	$OrigenLlamada = $row_Origen["IdRelacionMarketing"] ?? "";
}

if (isset($row_OT["CDU_TipoPreventivo"]) && ($row_OT["CDU_TipoPreventivo"] != "")) {
	$CDU_TipoPreventivo = $row_OT["CDU_TipoPreventivo"];

	$SQL_TipoPreventivo = Seleccionar("uvw_Sap_tbl_LlamadasServicios_TipoPreventivo", '*', "CodigoTipoPreventivo='$CDU_TipoPreventivo'");
	$row_TipoPreventivo = sqlsrv_fetch_array($SQL_TipoPreventivo);
	
	$TipoPreventivo = $row_TipoPreventivo["IdRelacionMarketing"] ?? "";
}

if (isset($row_OT["IdTipoProblemaLlamada"]) && ($row_OT["IdTipoProblemaLlamada"] != "")) {
	$IdTipoProblemaLlamada = $row_OT["IdTipoProblemaLlamada"];

	$SQL_TipoProblema = Seleccionar("uvw_Sap_tbl_TipoProblemasLlamadas", '*', "IdTipoProblemaLlamada='$IdTipoProblemaLlamada'");
	$row_TipoProblema = sqlsrv_fetch_array($SQL_TipoProblema);
	
	$TipoProblemaLlamada = $row_TipoProblema["IdRelacionMarketing"] ?? "";
}

if (isset($row_OT["IdTipoLlamada"]) && ($row_OT["IdTipoLlamada"] != "")) {
	$IdTipoLlamada = $row_OT["IdTipoLlamada"];

	$SQL_TipoLlamada = Seleccionar("uvw_Sap_tbl_TipoLlamadas", '*', "IdTipoLlamada='$IdTipoLlamada'");
	$row_TipoLlamada = sqlsrv_fetch_array($SQL_TipoLlamada);
	
	$TipoLlamada = $row_TipoLlamada["IdRelacionMarketing"] ?? "";
}

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

// Solicitado para. SMM, 12/04/2024
$SQL_Empleado = Seleccionar('uvw_Sap_tbl_EmpleadosSN', '*', '', 'NombreEmpleado');
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

	.collapse-link:hover {
		cursor: pointer;
	}
</style>

<div class="modal-dialog modal-lg" style="width: 75% !important;">
	<div class="modal-content">
		<div class="modal-body">
			<!-- Inicio, filtros -->
			<form id="formBuscar" class="form-horizontal">
				<div class="row">
					<!-- data-toggle="collapse" data-target="#filtros" -->
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-filter"></i> Datos para filtrar</h5>
						<a class="collapse-link pull-right">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>

					<div class="collapse in" id="filtros">
						<div class="col-lg-4">
							<div class="form-group">
								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Almacén origen <span
											class="text-danger">*</span></label>

									<select name="Almacen" id="Almacen" class="form-control select2" required>
										<option value="">Seleccione...</option>

										<?php while ($row_Almacen = sqlsrv_fetch_array($SQL_Almacen)) { ?>
											<option <?php if ($row_DatosEmpleados["AlmacenOrigen"] == $row_Almacen['WhsCode']) {
												echo "selected";
											} ?> value="<?php echo $row_Almacen['WhsCode']; ?>"><?php echo $row_Almacen['WhsCode'] . " - " . $row_Almacen['WhsName']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Almacén destino</label>

									<select name="AlmacenDestino" id="AlmacenDestino" class="form-control select2" <?php if(($Inventario == "") || ($Inventario == "Salida")) {
										echo "disabled";
									} ?>>
										<option value="">Seleccione...</option>

										<?php while ($row_AlmacenDestino = sqlsrv_fetch_array($SQL_AlmacenDestino)) { ?>
											<option  <?php if ($row_DatosEmpleados["AlmacenDestino"] == $row_AlmacenDestino['ToWhsCode']) {
												echo "selected";
											} ?> value="<?php echo $row_AlmacenDestino['ToWhsCode']; ?>">
												<?php echo $row_AlmacenDestino['ToWhsCode'] . " - " . $row_AlmacenDestino['ToWhsName']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Proyecto</label>

									<select id="Proyecto" name="Proyecto" class="form-control select2">
										<option value="">(NINGUNO)</option>

										<?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) { ?>
											<option <?php if ($Proyecto == $row_Proyecto['IdProyecto']) {
												echo "selected";
											} ?> value="<?php echo $row_Proyecto['IdProyecto']; ?>">
												<?php echo $row_Proyecto['IdProyecto'] . " - " . $row_Proyecto['DeProyecto']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Lista Precios <span
											class="text-danger">*</span></label>

									<select name="ListaPrecio" id="ListaPrecio" class="form-control select2" required>
										<option value="">Seleccione...</option>

										<?php while ($row_ListaPrecio = sqlsrv_fetch_array($SQL_ListaPrecios)) { ?>
											<option <?php if ($ListaPrecio == $row_ListaPrecio['IdListaPrecio']) {
												echo "selected";
											} ?> value="<?php echo $row_ListaPrecio['IdListaPrecio']; ?>">

												<?php echo $row_ListaPrecio['IdListaPrecio'] . " - " . $row_ListaPrecio['DeListaPrecio']; ?>

											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Empleado de ventas/compras <span
											class="text-danger">*</span></label>

									<select name="EmpVentas" id="EmpVentas" class="form-control select2" required>
										<option value="">Seleccione...</option>

										<?php while ($row_EmpleadosVentas = sqlsrv_fetch_array($SQL_EmpleadosVentas)) { ?>
											<option <?php if ($IdEmpleado == $row_EmpleadosVentas['ID_EmpVentas']) {
												echo "selected";
											} ?> value="<?php echo $row_EmpleadosVentas['ID_EmpVentas']; ?>"><?php echo $row_EmpleadosVentas['ID_EmpVentas'] . " - " . $row_EmpleadosVentas['DE_EmpVentas']; ?></option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">
										Concepto Salida
									</label>
									
									<select name="ConceptoSalida" id="ConceptoSalida" class="form-control select2" <?php if($Inventario == "") {
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
								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Solicitado para</label>

									<select id="Empleado" name="Empleado" class="form-control select2">
										<option value="">Seleccione...</option>
										
										<?php while ($row_Empleado = sqlsrv_fetch_array($SQL_Empleado)) { ?>
											<option <?php if ($CodEmpleado == $row_Empleado['ID_Empleado']) {
												echo "selected";
											} ?> value="<?php echo $row_Empleado['ID_Empleado']; ?>">
												<?php echo $row_Empleado['ID_Empleado'] . " - " . $row_Empleado['NombreEmpleado']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->
							
								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Tipo OT (Origen Llamada) <span
											class="text-danger">*</span></label>

									<select name="IdTipoOT" id="IdTipoOT" class="form-control select2" required>
										<option value="">Seleccione...</option>

										<?php while ($row_ORIGEN = sqlsrv_fetch_array($SQL_OT_ORIGEN)) { ?>
											<option <?php if ($OrigenLlamada == $row_ORIGEN['IdTipoOT']) {
												echo "selected";
											} ?> value="<?php echo $row_ORIGEN['IdTipoOT']; ?>">
												<?php echo $row_ORIGEN['IdTipoOT'] . " - " . $row_ORIGEN['TipoOT']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Tipo Problema <span
											class="text-danger">*</span></label>

									<select name="IdTipoProblema" id="IdTipoProblema" class="form-control select2"
										required>
										<option value="">Seleccione...</option>

										<?php while ($row_TIPOPROBLEMA = sqlsrv_fetch_array($SQL_OT_TIPOPROBLEMA)) { ?>
											<option <?php if ($TipoProblemaLlamada == $row_TIPOPROBLEMA['IdTipoProblema']) {
												echo "selected";
											} ?> value="<?php echo $row_TIPOPROBLEMA['IdTipoProblema']; ?>">
												<?php echo $row_TIPOPROBLEMA['IdTipoProblema'] . " - " . $row_TIPOPROBLEMA['TipoProblema']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Sede Empresa <span class="text-danger">*</span></label>

									<select name="IdSedeEmpresa" id="IdSedeEmpresa" class="form-control select2"
										required>
										<option value="">Seleccione...</option>

										<?php while ($row_SEDE_EMPRESA = sqlsrv_fetch_array($SQL_OT_SEDE_EMPRESA)) { ?>
											<option <?php if ($SedeEmpresa == $row_SEDE_EMPRESA['IdSedeEmpresa']) {
												echo "selected";
											} ?> value="<?php echo $row_SEDE_EMPRESA['IdSedeEmpresa']; ?>">
												<?php echo $row_SEDE_EMPRESA['IdSedeEmpresa'] . " - " . $row_SEDE_EMPRESA['SedeEmpresa']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Tipo Cargo (Tipo Llamada) <span
											class="text-danger">*</span></label>

									<select name="IdTipoCargo" id="IdTipoCargo" class="form-control select2" required>
										<option value="">Seleccione...</option>

										<?php while ($row_TIPOCARGO = sqlsrv_fetch_array($SQL_OT_TIPOCARGO)) { ?>
											<option <?php if ($TipoLlamada == $row_TIPOCARGO['IdTipoCargo']) {
												echo "selected";
											} ?> value="<?php echo $row_TIPOCARGO['IdTipoCargo']; ?>">
												<?php echo $row_TIPOCARGO['IdTipoCargo'] . " - " . $row_TIPOCARGO['TipoCargo']; ?>
											</option>
										<?php } ?>
									</select>
								</div> <!-- col-xs-12 -->

								<div class="col-xs-12" style="margin-bottom: 10px;">
									<label class="control-label">Tipo Preventivo <span
											class="text-danger">*</span></label>

									<select name="IdTipoPreventivo" id="IdTipoPreventivo" class="form-control select2"
										required>
										<option value="">Seleccione...</option>

										<?php while ($row_TIPOPREVENTI = sqlsrv_fetch_array($SQL_OT_TIPOPREVENTI)) { ?>
											<option <?php if ($TipoPreventivo == $row_TIPOPREVENTI['IdTipoPreventivo']) {
												echo "selected";
											} ?> value="<?php echo $row_TIPOPREVENTI['IdTipoPreventivo']; ?>">
												<?php echo $row_TIPOPREVENTI['IdTipoPreventivo'] . " - " . $row_TIPOPREVENTI['TipoPreventivo']; ?>
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
											<?php echo $dim['DescPortalOne']; ?> <span class="text-danger">*</span>
										</label>

										<select name="<?php echo $dim['IdPortalOne'] ?>" required
											id="<?php echo $dim['IdPortalOne'] ?>" class="form-control select2">
											<option value="">Seleccione...</option>

											<?php $SQL_Dim = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', 'DimCode=' . $dim['DimCode']); ?>

											<?php if ($dim['DimCode'] == $DimSeries) { ?>
												<?php $SQL_Dim = $SQL_Sucursales; ?>
											<?php } ?>

											<?php while ($row_Dim = sqlsrv_fetch_array($SQL_Dim)) { ?>
												<?php $DimCode = intval($dim['DimCode']); ?>
												<?php $OcrId = ($DimCode == 1) ? "" : $DimCode; ?>

												<option <?php if ($row_DatosEmpleados["CentroCosto$DimCode"] == $row_Dim['OcrCode']) {
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

				<div class="row">
					<div class="col-lg-6">
						<label class="control-label">Buscar artículo <span class="text-danger">*</span></label>

						<input name="BuscarItem" id="BuscarItem" type="text" class="form-control"
							placeholder="Escriba para buscar..." required>
					</div>

					<div class="col-lg-4" style="margin-top: 20px;">
						<label class="checkbox-inline i-checks"><input name="chkStock" type="checkbox" id="chkStock"
								value="1" checked="checked"> Mostrar solo los artículos con
							stock</label>
					</div>

					<div class="col-lg-2" style="margin-top: 20px;">
						<button type="submit" class="btn btn-outline btn-success pull-right"><i
								class="fa fa-search"></i> Buscar</button>
					</div>
				</div> <!-- row -->
			</form>
			<br><br><br>
			<!-- Fin, filtros -->

			<!-- Inicio, tabla -->
			<div class="row">
				<div class="col-lg-6">
					<div class="ibox-content">
						<div class="table-responsive" id="tableContainerOne">
							<i class="fa fa-search" style="font-size: 20px; color: gray;"></i>
							<span style="font-size: 15px; color: gray;">Debe buscar un artículo.</span>
						</div> <!-- table-responsive -->
					</div> <!-- ibox-content -->
				</div> <!-- col-lg-6 -->
				<div class="col-lg-6">
					<div class="ibox-content">
						<div class="table-responsive" id="tableContainerTwo">
							<i class="fa fa-exclamation-circle" style="font-size: 20px; color: gray;"></i>
							<span style="font-size: 15px; color: gray;">Todavía no se han agregado artículos al
								carrito.</span>
						</div> <!-- table-responsive -->
					</div> <!-- ibox-content -->
				</div> <!-- col-lg-6 -->
			</div>
			<!-- Fin, tabla -->

		</div> <!-- modal-body -->
		<div class="modal-footer">
			<button type="button" class="btn btn-success m-t-md" id="btnAceptar"><i class="fa fa-check"></i>
				Aceptar</button>
			<button type="button" class="btn btn-danger m-t-md" data-dismiss="modal"><i class="fa fa-times"></i>
				Cerrar</button>
		</div>
	</div> <!-- modal-content -->
</div> <!-- modal-dialog -->

<script>
	function VerificarFilas() {
		if ($(".footable-detail-row").length) {
			Swal.fire({
				"title": "¡Advertencia!",
				"text": "Debe contraer todas las filas para poder realizar alguna acción en las tablas.",
				"icon": "warning"
			});

			return false;
		}

		return true;
	}

	function EliminarFilas() {
		let contenido = `<i class="fa fa-search" style="font-size: 20px; color: gray;"></i>
		<span style="font-size: 15px; color: gray;">Debe buscar un artículo.</span>`;

		$("#tableContainerOne").html(contenido);
	}

	function AgregarArticulo(ID) {
		if (VerificarFilas()) {

			if ($("#footableTwo").length) {
				console.log("footableTwo existe.");
			} else {
				console.log("footableTwo no existe.");

				// Clonar la tabla con el ID "footableOne"
				let tableTwo = $('#footableOne').clone();

				// Vaciar el tbody de la tabla clonada
				tableTwo.find('tbody').empty();

				// Asignar el ID "footableTwo" a la tabla clonada
				tableTwo.attr('id', 'footableTwo');

				// Agregar la tabla clonada al DOM
				$('#tableContainerTwo').replaceWith(tableTwo);
			}

			// Obtener la fila correspondiente al artículo seleccionado
			let fila = $(`#${ID}`).clone();

			// Eliminar el botón "fooicon" de la fila clonada
			fila.find('.fooicon').remove();

			// Reemplazar el botón "Agregar" por "Eliminar"
			fila.find(".btn-success")
				.removeClass("btn-success")
				.addClass("btn-danger")
				.html('<i class="fa fa-trash"></i> Eliminar')
				.attr("onclick", `EliminarArticulo(this);`);


			// Agregar la fila al carrito de compras
			$("#footableTwo tbody").append(fila);

			// Re-renderizar.
			$('#footableTwo').footable();

		} // VerificarFilas()
	}

	function EliminarArticulo(btn) {
		if (VerificarFilas()) {

			$(btn).closest("tr").remove(); // Eliminar la fila padre del botón

		} // VerificarFilas()
	}

	$(document).ready(function () {
		console.log("<?php echo $row_OT["IdOrigenLlamada"] ?? ""; ?>");
		console.log("<?php echo $row_OT["CDU_TipoPreventivo"] ?? ""; ?>");
		console.log("<?php echo $row_OT["IdTipoProblemaLlamada"] ?? ""; ?>");
		console.log("<?php echo $row_OT["IdTipoLlamada"] ?? ""; ?>");
		
		$(".select2").select2();
		$('#footableOne').footable();

		$('#formBuscar').on('submit', function (event) {
			event.preventDefault();
		});

		// SMM, 29/05/2023
		$('#filtros').on('show.bs.collapse', function () {
			$('.collapse-link i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
		});

		$('#filtros').on('hide.bs.collapse', function () {
			$('.collapse-link i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
		});

		// SMM, 31/05/2023
		$(".collapse-link").on("click", function () {
			$("#filtros").collapse("toggle");
		});

		$("#formBuscar").validate({
			submitHandler: function (form) {
				$('.ibox-content').toggleClass('sk-loading', true);

				// Comprimir el acordeón
				$("#filtros").collapse("hide");

				let formData = new FormData(form);

				// Ejemplo de como agregar nuevos campos.
				// formData.append("Dim1", $("#Dim1").val() || "");

				formData.append("tipodoc", "<?php echo $_POST["TipoDoc"] ?? 2; ?>");

				let json = Object.fromEntries(formData);
				console.log("Line 340", json);

				// Inicio, AJAX
				$.ajax({
					url: 'md_consultar_articulos_ws.php',
					type: 'POST',
					data: formData,
					processData: false,  // tell jQuery not to process the data
					contentType: false,   // tell jQuery not to set contentType
					success: function (response) {
						// console.log("Line 260", response);

						$("#tableContainerOne").html(response);
						$('#footableOne').footable();

						$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
					},
					error: function (error) {
						console.error(error.responseText);

						$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
					}
				});
				// Fin, AJAX
			}
		});

		$('.chosen-select').chosen({ width: "100%" });

		$('.i-checks').iCheck({
			checkboxClass: 'icheckbox_square-green',
			radioClass: 'iradio_square-green',
		});

		$("#btnAceptar").on("click", function () {
			let dt = <?php echo $DocType; ?>;
			let did = <?php echo $DocId; ?>;
			let dev = <?php echo $DocEvent; ?>;
			let cc = "<?php echo $CardCode; ?>";
			let db = <?php echo $Borrador; ?>; // SMM, 03/02/2024

			var totalArticulos = $("#footableTwo tbody tr").length; // Obtener el total de artículos
			var contadorArticulos = 0; // Inicializar el contador de artículos

			$("#footableTwo tbody tr").each(function () {
				let idArticulo = $(this).attr("id");
				let whsCode = $(this).find('.WhsCode').text();

				// SMM, 21/03/2024
				let almacenDestino = $(this).find('.AlmacenDestino').length ? $(this).find('.AlmacenDestino').text() : "";
				let conceptoSalida = $(this).find('.ConceptoSalida').length ? $(this).find('.ConceptoSalida').text() : "";

				let dim1 = $(this).find('.Dim1').length ? $(this).find('.Dim1').text() : "";
				let dim2 = $(this).find('.Dim2').length ? $(this).find('.Dim2').text() : "";
				let dim3 = $(this).find('.Dim3').length ? $(this).find('.Dim3').text() : "";
				let dim4 = $(this).find('.Dim4').length ? $(this).find('.Dim4').text() : "";
				let dim5 = $(this).find('.Dim5').length ? $(this).find('.Dim5').text() : "";

				let codEmpleado = $(this).find('.CodEmpleado').text();
				let prjCode = $(this).find('.PrjCode').text();
				let priceList = $(this).find('.PriceList').text();
				let empVentas = $(this).find('.EmpVentas').text();

				let IdTipoOT = $(this).find('.IdTipoOT').text();
				let IdSedeEmpresa = $(this).find('.IdSedeEmpresa').text();
				let IdTipoCargo = $(this).find('.IdTipoCargo').text();
				let IdTipoProblema = $(this).find('.IdTipoProblema').text();
				let IdTipoPreventivo = $(this).find('.IdTipoPreventivo').text();

				let articulo = {
					P: 35,
					doctype: dt,
					borrador: db,
					id: did,
					evento: dev,
					cardcode: cc,
					item: idArticulo,
					whscode: whsCode.trim(),
					towhscode: almacenDestino.trim(),
					concepto: conceptoSalida.trim(),
					dim1: dim1.trim(),
					dim2: dim2.trim(),
					dim3: dim3.trim(),
					dim4: dim4.trim(),
					dim5: dim5.trim(),
					empleado: codEmpleado.trim(),
					prjcode: prjCode.trim(),
					pricelist: priceList.trim(),
					empventas: empVentas.trim(),
					IdTipoOT: IdTipoOT.trim(),
					IdSedeEmpresa: IdSedeEmpresa.trim(),
					IdTipoCargo: IdTipoCargo.trim(),
					IdTipoProblema: IdTipoProblema.trim(),
					IdTipoPreventivo: IdTipoPreventivo.trim()
				};

				// Articulo que se esta enviando a registro.
				console.log(articulo);

				// Envio AJAX del Articulo.
				$.ajax({
					url: "registro.php",
					type: "POST",
					data: articulo,
					success: function (response) {
						// Manejar la respuesta del servidor
						// console.log("Respuesta:", response);
						contadorArticulos++; // Incrementar el contador de artículos

						// Verificar si todas las solicitudes AJAX han finalizado
						if (contadorArticulos === totalArticulos) {
							// Obtén el elemento con el ID 'DataGrid'
							let dataGrid = document.getElementById('DataGrid');

							// Crea un objeto URL a partir del atributo 'src'
							let url = new URL(dataGrid.src);

							// SMM, 23/06/2023
							let edit = <?php echo $Edit; ?>;

							if (edit == 1) {
								// Elimina todos los parámetros existentes
								url.search = '';

								// ?id&evento&type=2
								url.searchParams.set('id', '<?php echo base64_encode($DocId); ?>');
								url.searchParams.set('evento', '<?php echo base64_encode($DocEvent); ?>');
								url.searchParams.set('type', '2');
							} else {
								// ?id=0&type=1&usr&cardcode
								console.log("url.search", url.search);
							}

							// Asigna la nueva URL al atributo 'src' del elemento
							dataGrid.src = url.href;

							// Cerrar el modal al finalizar la lógica
							$("#mdArticulos").modal("hide");
						}
					},
					error: function (error) {
						// Manejar el error de la petición AJAX
						console.log("Error inserción:", error);

						// alert("Ocurrio un error al insertar los articulos, se recomienda repetir el procedimiento o consultar al administrador");
					}
				});
				// Fin AJAX
			}); // Fin Loop Articulos
		}); // Fin Evento CLICK

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

		// SMM, 30/08/2023
		$(".select2").on("change", function() {
			EliminarFilas();
		});
	});
</script>