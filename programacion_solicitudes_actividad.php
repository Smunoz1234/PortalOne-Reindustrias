<?php
require_once "includes/conexion.php";
PermitirAcceso(336);

$ID = $_GET["id"] ?? "";
$edit = ($ID != "");

// SMM, 09/11/2023
$CrearSolicitud = PermitirFuncion(335);
$ActualizarSolicitud = PermitirFuncion(343);

// SMM, 30/10/2023
$recurso = $_GET["recurso"] ?? "";
$fecha = $_GET["fecha"] ?? "";
$hora = $_GET["hora"] ?? "";
$hora_final = "";

 // SMM, 02/11/2023
$MostrarTodosRecursos = true;

// SMM, 27/10/2023
if($edit) {
	$Where = "ID_SolicitudLlamadaServicio = $ID";
	$SQL_Actividad = Seleccionar("uvw_tbl_SolicitudLlamadasServicios_Calendario", "*", $Where);
	$row = sqlsrv_fetch_array($SQL_Actividad);
} elseif($hora != "") {
	$hora_final = DateTime::createFromFormat('H:i', $hora)->modify('+1 hour')->format('H:i');
}

// SMM, 30/10/2023
if ($edit && isset($row["ID_CodigoCliente"])) {
	$ID_CodigoCliente = $row["ID_CodigoCliente"];
	$SQL_SucursalCliente = Seleccionar("uvw_Sap_tbl_Clientes_Sucursales", "*", "CodigoCliente='$ID_CodigoCliente' AND TipoDireccion='S'", "NombreSucursal");
	$SQL_Campanas = Seleccionar("uvw_tbl_SolicitudLlamadasServicios_Campanas", "*", "[id_solicitud_llamada_servicio]='$ID'");
	$SQL_CampanasClone = Seleccionar("uvw_tbl_SolicitudLlamadasServicios_Campanas", "*", "[id_solicitud_llamada_servicio]='$ID'");
}

// Fechas. SMM, 27/10/2023
$ValorFechaCreacion = (isset($row["FechaCreacion"]) && ($row["FechaCreacion"] instanceof DateTime)) ? $row["FechaCreacion"]->format("Y-m-d") : date("Y-m-d");
$ValorFechaFinCreacion = (isset($row["FechaFinCreacion"]) && ($row["FechaCreacion"] instanceof DateTime)) ? $row["FechaCreacion"]->format("Y-m-d") : date("Y-m-d");
$ValorFechaAgenda = (isset($row["FechaAgenda"]) && ($row["FechaAgenda"] instanceof DateTime)) ? $row["FechaAgenda"]->format("Y-m-d") : date("Y-m-d");
$ValorFechaFinAgenda = (isset($row["FechaFinAgenda"]) && ($row["FechaFinAgenda"] instanceof DateTime)) ? $row["FechaFinAgenda"]->format("Y-m-d") : date("Y-m-d");
$ValorHoraCreacion = (isset($row["HoraCreacion"]) && ($row["HoraCreacion"] instanceof DateTime)) ? $row["HoraCreacion"]->format("H:i") : date("H:i");
$ValorHoraFinCreacion = (isset($row["HoraFinCreacion"]) && ($row["HoraFinCreacion"] instanceof DateTime)) ? $row["HoraFinCreacion"]->format("H:i") : date("H:i");
$ValorHoraAgenda = (isset($row["HoraAgenda"]) && ($row["HoraAgenda"] instanceof DateTime)) ? $row["HoraAgenda"]->format("H:i") : date("H:i");
$ValorHoraFinAgenda = (isset($row["HoraFinAgenda"]) && ($row["HoraFinAgenda"] instanceof DateTime)) ? $row["HoraFinAgenda"]->format("H:i") : date("H:i");

// Empleados. SMM, 25/10/2023
$SQL_Tecnicos = Seleccionar('uvw_Sap_tbl_Recursos', '*', '', 'NombreEmpleado');
$SQL_TecnicosAdicionales = Seleccionar('uvw_Sap_tbl_Recursos', '*', '', 'NombreEmpleado');

// Grupos de Empleados.
$SQL_GruposUsuario = Seleccionar("uvw_tbl_UsuariosGruposEmpleados", "*", "[ID_Usuario]='" . $_SESSION['CodUser'] . "'", 'DeCargo');

$ids_grupos = array();
while ($row_GruposUsuario = sqlsrv_fetch_array($SQL_GruposUsuario)) {
	$ids_grupos[] = $row_GruposUsuario['IdCargo'];
}

// Serie de Llamada.
$ParamSerie = array(
	"'" . $_SESSION['CodUser'] . "'",
	"'191'", // @IdTipoDocumento
	($edit) ? 1 : 2, // @TipoAccion
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Estado servicio de la Solicitud de Llamada de servicio. SMM, 24/10/2023
$SQL_EstServLlamada = Seleccionar('tbl_SolicitudLlamadasServiciosEstadoServicios', '*');

// Tipos preventivos en la llamada de servicio. SMM, 24/10/2023
$SQL_TipoPreventivo = Seleccionar('uvw_Sap_tbl_LlamadasServicios_TipoPreventivo', '*');

// Contratos en la llamada de servicio. SMM, 08/11/2023
$SQL_ContratosLlamada = Seleccionar('uvw_Sap_tbl_LlamadasServicios_Contratos_TBUsuario', '*');

// SMM, 25/10/2023
$SQL_OrigenLlamada = Seleccionar('uvw_Sap_tbl_LlamadasServiciosOrigen', '*', "Activo = 'Y'", 'DeOrigenLlamada');
$SQL_TipoLlamadas = Seleccionar('uvw_Sap_tbl_TipoLlamadas', '*', "Activo = 'Y'", 'DeTipoLlamada');
$SQL_TipoProblema = Seleccionar('uvw_Sap_tbl_TipoProblemasLlamadas', '*', "Activo = 'Y'", 'DeTipoProblemaLlamada');
$SQL_SubTipoProblema = Seleccionar('uvw_Sap_tbl_SubTipoProblemasLlamadas', '*', "Activo = 'Y'", 'DeSubTipoProblemaLlamada');

$OrigenLlamada = ObtenerValorDefecto(191, "IdOrigenLlamada", false);
$SubtipoProblema = ObtenerValorDefecto(191, "IdSubtipoProblema", false);
$TipoLlamada = ObtenerValorDefecto(191, "IdTipoLlamada", false);
$TipoProblema = ObtenerValorDefecto(191, "IdTipoProblema", false);
// Hasta Aquí, 25/10/2023

// Llamar a SP de forma asincrona. SMM, 10/10/2023
$msg_error = "";
$parametros = array();
$coduser = $_SESSION['CodUser'];
$datetime = FormatoFecha(date('Y-m-d'), date('H:i:s'));

// SMM, 27/10/2023
$Type = $_POST["Type"] ?? 0;
$ID_SolicitudLlamadaServicio = $_POST["ID_SolicitudLlamadaServicio"] ?? "NULL";

$Tecnico = (isset($_POST["Tecnico"]) && ($_POST["Tecnico"] != "")) ? $_POST["Tecnico"] : "NULL";
$TecnicoAdicional = (isset($_POST["TecnicoAdicional"]) && ($_POST["TecnicoAdicional"] != "")) ? $_POST["TecnicoAdicional"] : "NULL";

$Comentario = $_POST["Comentario"] ?? "";

$FechaAgenda = isset($_POST["FechaAgenda"]) ? FormatoFecha($_POST["FechaAgenda"], $_POST["HoraAgenda"]) : "";
$FechaFinAgenda = isset($_POST["FechaFinAgenda"]) ? FormatoFecha($_POST["FechaFinAgenda"], $_POST["HoraFinAgenda"]) : "";
$FechaCreacion = isset($_POST["FechaCreacion"]) ? FormatoFecha($_POST["FechaCreacion"], $_POST["HoraCreacion"]) : "";
$FechaFinCreacion = isset($_POST["FechaFinCreacion"]) ? FormatoFecha($_POST["FechaFinCreacion"], $_POST["HoraFinCreacion"]) : "";
$UsuarioCreacion = "'$coduser'";

$CDU_Kilometros = $_POST["CDU_Kilometros"] ?? "NULL";
$CDU_TipoPreventivo = $_POST["CDU_TipoPreventivo"] ?? "";

// SMM, 04/12/2023
$IdTarjetaEquipo = $_POST["IdTarjetaEquipo"] ?? "NULL";
$NumeroSerie = $_POST["NumeroSerie"] ?? "";

$Series = $_POST["Series"] ?? "NULL";
$OrigenLlamada = $_POST["OrigenLlamada"] ?? "NULL";
$TipoLlamada = $_POST["TipoLlamada"] ?? "NULL";
$TipoProblema = $_POST["TipoProblema"] ?? "NULL";
$SubTipoProblema = $_POST["SubTipoProblema"] ?? "NULL";

$Cliente = $_POST["Cliente"] ?? "";
$CDU_Contrato = $_POST["CDU_Contrato"] ?? "";

$SucursalCliente = $_POST["SucursalCliente"] ?? "NULL"; // NumeroLinea

$CampanasAsociadas = $_POST["Campanas"] ?? "";

if ($Type == 1) {
	$msg_error = "No se pudo crear la Agenda.";

	$parametros = array(
		$Type,
		"NULL", // ID_SolicitudLlamadaServicio
		$Tecnico,
		$TecnicoAdicional,
		"'$Comentario'",
		"'$FechaAgenda'",
		"'$FechaFinAgenda'",
		"'$FechaCreacion'",
		"'$FechaFinCreacion'",
		$UsuarioCreacion,
		"'$datetime'", // FechaCreacion
		$CDU_Kilometros,
		"'$CDU_TipoPreventivo'",
		$Series,
		$OrigenLlamada,
		$TipoLlamada,
		$TipoProblema,
		$SubTipoProblema,
		"'$CDU_Contrato'",
		"'$Cliente'",
		$SucursalCliente,
		"'$NumeroSerie'",
		$IdTarjetaEquipo,
		"'$CampanasAsociadas'", 
	);
} elseif ($Type == 3) {
	$msg_error = "No se pudo actualizar la Agenda.";

	$parametros = array(
		$Type,
		$ID_SolicitudLlamadaServicio,
		$Tecnico,
		$TecnicoAdicional,
		"'$Comentario'",
		"'$FechaAgenda'",
		"'$FechaFinAgenda'",
		"'$FechaCreacion'",
		"'$FechaFinCreacion'",
		$UsuarioCreacion, // UsuarioActualizacion
		"'$datetime'", // FechaActualizacion
		$CDU_Kilometros,
		"'$CDU_TipoPreventivo'",
		$Series,
		$OrigenLlamada,
		$TipoLlamada,
		$TipoProblema,
		$SubTipoProblema,
		"'$CDU_Contrato'",
		"'$Cliente'",
		$SucursalCliente,
		"'$NumeroSerie'",
		$IdTarjetaEquipo,
		"'$CampanasAsociadas'", 
	);
}

if ($Type != 0) {
	$SQL_Operacion = EjecutarSP("sp_tbl_SolicitudLlamadaServicios_Calendario", $parametros);

	if (!$SQL_Operacion) {
		echo $msg_error;
		exit();
	} else {
		$row_Operacion = sqlsrv_fetch_array($SQL_Operacion);

		if (isset($row_Operacion['Error']) && ($row_Operacion['Error'] != "")) {
			$msg_error .= " (" . $row_Operacion['Error'] . ")";

			echo $msg_error;
			exit();
		} else {
			$IdSolicitud = ($row_Operacion[0] ?? "NULL");

			// Corregir Campos. Type = 2, ID_SolicitudLlamadaServicio = $IdSolicitud.
			// if($Type != 3) {
			
			EjecutarSP("sp_tbl_SolicitudLlamadaServicios_Calendario", [2, $IdSolicitud]); 
			
			// }

			echo "OK";
			exit();
		}
	}
}

// SMM, 09/11/2023
$SolicitudCerrada = (isset($row['IdEstadoLlamada']) && ($row['IdEstadoLlamada'] == '-1'));

// SMM, 28/11/2023
$SQL_NumeroSerie = Seleccionar("uvw_Sap_tbl_TarjetasEquipos", "*", "IdTarjetaEquipo='" . ($row['IdTarjetaEquipo'] ?? "") . "'");
$row_NumeroSerie = sqlsrv_fetch_array($SQL_NumeroSerie);
?>

<style>
	.select2-container {
		/** 
		Permite visualizar correctamente el "select2-multiple"
		SMM, 05/10/2023
		*/
		display: block !important;
		width: 100% !important;

		/** 
		Se reemplaza con "dropdownParent" 
		SMM, 01/11/2023
		z-index: 10000 !important;
		*/
	}

	.easy-autocomplete {
		display: block !important;
		width: 100% !important;
	}
</style>

<form id="frmActividad" method="post">
	<div class="modal-content">
		<div class="modal-header">
			<h4 class="modal-title">Solicitud de Llamada de servicio (Agenda)</h4>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">X</button>
		</div>
		<!-- /.modal-header -->

		<div class="modal-body">
			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">
						<i onclick="ConsultarAgenda();" title="Consultar Agenda" style="cursor: pointer"
							class="btn-xs btn-success fa fa-search"></i> ID Solicitud (Agenda)
					</label>

					<input type="text" name="ID_SolicitudLlamadaServicio" id="ID_SolicitudLlamadaServicio"
						class="form-control" value="<?php echo $row["ID_SolicitudLlamadaServicio"] ?? ""; ?>" readonly>
				</div>

				<div class="col-lg-2"></div>

				<div class="col-lg-4">
					<label class="control-label">Estado servicio</label>

					<select disabled name="CDU_EstadoServicio" id="CDU_EstadoServicio" class="form-control">
						<?php while ($row_EstServLlamada = sqlsrv_fetch_array($SQL_EstServLlamada)) { ?>
							<option value="<?php echo $row_EstServLlamada['id_tipo_estado_servicio_sol_llamada']; ?>" <?php if (isset($row["CDU_EstadoServicio"]) && ($row_EstServLlamada["id_tipo_estado_servicio_sol_llamada"] == $row["CDU_EstadoServicio"])) {
								   echo "selected";
							   } ?>>
								<?php echo $row_EstServLlamada['tipo_estado_servicio_sol_llamada']; ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-6">
					<label for="FechaCreacion" class="control-label">
						Fecha Inicio Solicitud <span class="text-danger">*</span>
					</label>

					<div class="row">
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-calendar"></i></span>
								<input required type="text" name="FechaCreacion" id="FechaCreacion"
									class="form-control fecha"
									value="<?php echo $edit ? $ValorFechaCreacion : $fecha; ?>">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-clock"></i></span>
								<input required type="text" name="HoraCreacion" id="HoraCreacion"
									class="form-control hora"
									value="<?php echo $edit ? $ValorHoraCreacion : $hora; ?>">
							</div>
						</div>
					</div>
				</div>
				<!-- /.col-lg-6 -->

				<div class="col-lg-6">
					<label for="FechaAgenda" class="control-label">
						Fecha Inicio Actividad <span class="text-danger">*</span>
					</label>

					<div class="row">
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-calendar"></i></span>
								<input required type="text" name="FechaAgenda" id="FechaAgenda"
									class="form-control fechaAgenda"
									value="<?php echo $edit ? $ValorFechaAgenda : $fecha; ?>">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-clock"></i></span>
								<input required type="text" name="HoraAgenda" id="HoraAgenda" class="form-control horaAgenda"
									value="<?php echo $edit ? $ValorHoraAgenda : $hora; ?>">
							</div>
						</div>
					</div>
				</div>
				<!-- /.col-lg-6 -->
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-6">
					<label for="FechaFinCreacion" class="control-label">
						Fecha Fin Solicitud <span class="text-danger">*</span>
					</label>

					<div class="row">
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-calendar"></i></span>
								<input required type="text" name="FechaFinCreacion" id="FechaFinCreacion"
									class="form-control fecha"
									value="<?php echo $edit ? $ValorFechaFinCreacion : $fecha; ?>">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-clock"></i></span>
								<input required type="text" name="HoraFinCreacion" id="HoraFinCreacion"
									class="form-control hora"
									value="<?php echo $edit ? $ValorHoraFinCreacion : $hora_final; ?>">
							</div>
						</div>
					</div>
				</div>
				<!-- /.col-lg-6 -->

				<div class="col-lg-6">
					<label for="FechaFinAgenda" class="control-label">
						Fecha Fin Actividad <span class="text-danger">*</span>
					</label>

					<div class="row">
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-calendar"></i></span>
								<input required type="text" name="FechaFinAgenda" id="FechaFinAgenda"
									class="form-control fechaAgenda"
									value="<?php echo $edit ? $ValorFechaFinAgenda : $fecha; ?>">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="input-group">
								<span class="input-group-text"><i class="fa fa-clock"></i></span>
								<input required type="text" name="HoraFinAgenda" id="HoraFinAgenda"
									class="form-control horaAgenda"
									value="<?php echo $edit ? $ValorHoraFinAgenda : $hora_final; ?>">
							</div>
						</div>
					</div>
				</div>
				<!-- /.col-lg-6 -->
			</div>
			<!-- /.form-group -->
			
			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">Serie <span class="text-danger">*</span></label>

					<select required name="Series" id="Series" class="form-control TecnicoSugerido">
						<option value="">Seleccione...</option>

						<?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
							<option value="<?php echo $row_Series['IdSeries']; ?>" <?php if (isset($row["IdSeries"]) && ($row_Series["IdSeries"] == $row["IdSeries"])) {
								   echo "selected";
							   } ?>>
								<?php echo $row_Series['DeSeries']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="col-lg-2"></div>

				<div class="col-lg-4">
					<label class="control-label">
						<i onclick="ConsultarCliente();" title="Consultar cliente" style="cursor: pointer"
							class="btn-xs btn-success fa fa-search"></i> Cliente <span class="text-danger">*</span>
					</label>

					<input type="hidden" name="ClienteLlamada" id="ClienteLlamada"
						value="<?php echo $row['ID_CodigoCliente'] ?? ""; ?>">
					<input required type="text" name="NombreClienteLlamada" id="NombreClienteLlamada" class="form-control"
						placeholder="Digite para buscar..." value="<?php echo $row['NombreClienteLlamada'] ?? ""; ?>">
				</div>

				<div class="col-lg-2">
					<br>
					<div class="btn-group">
						<button type="button" id="AddCliente" class="btn btn-primary" title="Adicionar Cliente">
							<i class="fa fa-plus"></i>
						</button>
						<!-- espacio para más botones -->
					</div>
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">Origen <span class="text-danger">*</span></label>

					<select required id="OrigenLlamada" name="OrigenLlamada" class="form-control TecnicoSugerido">
						<option value="">Seleccione...</option>

						<?php while ($row_OrigenLlamada = sqlsrv_fetch_array($SQL_OrigenLlamada)) { ?>
							<option value="<?php echo $row_OrigenLlamada['IdOrigenLlamada']; ?>" <?php if (isset($row['IdOrigenLlamada']) && ($row_OrigenLlamada['IdOrigenLlamada'] == $row['IdOrigenLlamada'])) {
								   echo "selected";
							   } elseif ((!isset($row['IdOrigenLlamada'])) && ($OrigenLlamada == $row_OrigenLlamada['IdOrigenLlamada'])) {
								   echo "selected";
							   } ?>>
								<?php echo $row_OrigenLlamada['DeOrigenLlamada']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="col-lg-2"></div>

				<div class="col-lg-4">
					<label class="control-label">Sucursal <span class="text-danger">*</span></label>

					<select required name="SucursalCliente" id="SucursalCliente" class="form-control">
						<option value="">Seleccione...</option>

						<?php while ($row_SucursalCliente = sqlsrv_fetch_array($SQL_SucursalCliente)) { ?>
							<option value="<?php echo $row_SucursalCliente["NumeroLinea"]; ?>" <?php if (isset($row["IdNombreSucursal"]) && ($row_SucursalCliente["NumeroLinea"] == $row["IdNombreSucursal"])) {
									echo "selected";
							   	} elseif (isset($row["NombreSucursal"]) && ($row_SucursalCliente["NobreSucursal"] == $row["NombreSucursal"])) {
									echo "selected";
							   	} ?>>
								<?php echo $row_SucursalCliente["NombreSucursal"]; ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">Tipo problema (Tipo Servicio) <span
							class="text-danger">*</span></label>

					<select required name="TipoProblema" id="TipoProblema" class="form-control TecnicoSugerido">
						<option value="">Seleccione...</option>

						<?php while ($row_TipoProblema = sqlsrv_fetch_array($SQL_TipoProblema)) { ?>
							<option value="<?php echo $row_TipoProblema['IdTipoProblemaLlamada']; ?>" <?php if (isset($row['IdTipoProblemaLlamada']) && ($row_TipoProblema['IdTipoProblemaLlamada'] == $row['IdTipoProblemaLlamada'])) {
								   echo "selected";
							   } elseif ((!isset($row['IdTipoProblemaLlamada'])) && ($TipoProblema == $row_TipoProblema['IdTipoProblemaLlamada'])) {
								   echo "selected";
							   } ?>>
								<?php echo $row_TipoProblema['DeTipoProblemaLlamada']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="col-lg-2"></div>

				<div class="col-lg-4">
					<label class="control-label">
						<i onclick="ConsultarEquipo();" title="Consultar equipo" style="cursor: pointer"
							class="btn-xs btn-success fa fa-search"></i> Tarjeta de equipo <span
							class="text-danger">*</span>
					</label>

					<!-- SMM, 31/10/2023 -->
					<input type="hidden" name="CDU_Marca" id="CDU_Marca" class="TecnicoSugerido"
						value="<?php echo $row["CDU_Marca"] ?? ""; ?>">

					<!-- Se necesita el SerialInterno para el llamado al WebService. SMM, 27/11/2023 -->
					<input type="hidden" class="form-control" name="SerialInterno" id="SerialInterno"
						value="<?php echo $row_NumeroSerie['SerialInterno'] ?? ""; ?>">

					<input type="hidden" class="form-control" name="NumeroSerie" id="NumeroSerie"
						value="<?php if (isset($row_NumeroSerie['IdTarjetaEquipo']) && ($row_NumeroSerie['IdTarjetaEquipo'] != 0)) {
							echo $row_NumeroSerie['IdTarjetaEquipo'];
						} ?>">
					<input required readonly type="text" class="form-control"
						name="Desc_NumeroSerie" id="Desc_NumeroSerie"
						placeholder="Haga clic en el botón"
						value="<?php if (isset($row_NumeroSerie['IdTarjetaEquipo']) && ($row_NumeroSerie['IdTarjetaEquipo'] != 0)) {
							echo "SN Fabricante: " . ($row_NumeroSerie['SerialFabricante'] ?? "") . " - Núm. Serie: " . ($row_NumeroSerie['SerialInterno'] ?? "") . " - Marca: " . ($row_NumeroSerie["CDU_Marca"] ?? "");
						} ?>">
				</div>
				<!-- /#NumeroSerie -->

				<div class="col-lg-2">
					<br>
					<div class="btn-group">
						<button type="button" class="btn btn-success" title="Cambiar Equipo"  id="btnTE"
							onclick="$('#mdTE').modal('show');">
							<i class="fas fa-sync-alt"></i>
						</button>
						<button disabled type="button" id="AddCampana" class="btn btn-info" title="Adicionar Campaña">
							<i class="fa fa-bell"></i>
						</button>
						<button type="button" id="AddEquipo" class="btn btn-primary" title="Adicionar Equipo">
							<i class="fa fa-plus"></i>
						</button>
					</div>
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">Tipo llamada (Tipo Cliente) <span class="text-danger">*</span></label>

					<select required id="TipoLlamada" name="TipoLlamada" class="form-control">
						<option value="">Seleccione...</option>

						<?php while ($row_TipoLlamadas = sqlsrv_fetch_array($SQL_TipoLlamadas)) { ?>
							<option value="<?php echo $row_TipoLlamadas['IdTipoLlamada']; ?>" <?php if (isset($row['IdTipoLlamada']) && ($row_TipoLlamadas['IdTipoLlamada'] == $row['IdTipoLlamada'])) {
								   echo "selected";
							   } elseif ((!isset($row['IdTipoLlamada'])) && ($TipoLlamada == $row_TipoLlamadas['IdTipoLlamada'])) {
								   echo "selected";
							   } ?>>
								<?php echo $row_TipoLlamadas['DeTipoLlamada']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="col-lg-2"></div>

				<div class="col-lg-4">
					<label class="control-label">
						Kilometros <span class="text-danger">*</span>
					</label>

					<input required type="number" name="CDU_Kilometros" id="CDU_Kilometros" class="form-control"
						value="<?php echo $row["CDU_Kilometros"] ?? ""; ?>">
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">
						SubTipo problema (Subtipo Servicio) <span class="text-danger">*</span>
					</label>

					<select id="SubTipoProblema" name="SubTipoProblema" class="form-control" required>
						<option value="">Seleccione...</option>

						<?php while ($row_SubTipoProblema = sqlsrv_fetch_array($SQL_SubTipoProblema)) { ?>
							<option value="<?php echo $row_SubTipoProblema['IdSubTipoProblemaLlamada']; ?>" <?php if (isset($row['IdSubTipoProblemaLlamada']) && ($row_SubTipoProblema['IdSubTipoProblemaLlamada'] == $row['IdSubTipoProblemaLlamada'])) {
								   echo "selected";
							   } elseif ((!isset($row['IdSubTipoProblemaLlamada'])) && ($SubtipoProblema == $row_SubTipoProblema['IdSubTipoProblemaLlamada'])) {
								   echo "selected";
							   } ?>>
								<?php echo $row_SubTipoProblema['DeSubTipoProblemaLlamada']; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="col-lg-2"></div>

				<div class="col-lg-4">
					<label class="control-label">Campañas</label>

					<select multiple name="Campanas[]" id="Campanas" class="form-control select2"
						data-placeholder="Debe seleccionar las campañas que desea asociar.">

						<!-- Las campañas dependen de la TE. -->
						<?php while ($row_Campanas = sqlsrv_fetch_array($SQL_Campanas)) { ?>
							<option value="<?php echo $row_Campanas["id_campana"]; ?>" selected>
								<?php echo $row_Campanas["id_campana"] . "-" . $row_Campanas["campana"]; ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">
						<?php echo (ObtenerVariable("LabelTecnicoResponsableSolicitudLlamada") == "") ? "Técnico/Asesor" : ObtenerVariable("LabelTecnicoResponsableSolicitudLlamada"); ?> 
						<span class="text-danger">*</span>
					</label>

					<!-- required -->
					<select name="Tecnico" id="Tecnico" class="form-control select2">
						<option value="">Seleccione...</option>

						<?php while ($row_Tecnicos = sqlsrv_fetch_array($SQL_Tecnicos)) { ?>
							<?php if (in_array($row_Tecnicos['IdCargo'], $ids_grupos) || ($MostrarTodosRecursos || (count($ids_grupos) == 0))) { ?>
								<option value="<?php echo $row_Tecnicos['ID_Empleado']; ?>" <?php if (isset($row['IdTecnico']) && ($row_Tecnicos['ID_Empleado'] == $row['IdTecnico'])) {
									   echo "selected";
								   	} elseif ($recurso == $row_Tecnicos['ID_Empleado']) {
										echo "selected";
									} ?> 
									<?php if ((count($ids_grupos) > 0) && (!in_array($row_Tecnicos['IdCargo'], $ids_grupos))) {
										echo "disabled";
									} ?>>
									<?php echo $row_Tecnicos['NombreEmpleado'] . " (" . $row_Tecnicos['NombreCentroCosto2'] . " - " . $row_Tecnicos['DeCargo'] . ")"; ?>
								</option>
							<?php } ?>
						<?php } ?>
					</select>
				</div>

				<div class="col-lg-2"></div>

				<div class="col-lg-4">
					<label class="control-label">
						Tipo preventivo <span class="text-danger">*</span>
					</label>

					<select required name="CDU_TipoPreventivo" id="CDU_TipoPreventivo" class="form-control">
						<?php while ($row_TipoPreventivo = sqlsrv_fetch_array($SQL_TipoPreventivo)) { ?>
							<option value="<?php echo $row_TipoPreventivo['CodigoTipoPreventivo']; ?>" <?php if (isset($row['CDU_TipoPreventivo']) && ($row_TipoPreventivo['CodigoTipoPreventivo'] == $row['CDU_TipoPreventivo'])) {
								   echo "selected";
							   } ?>>
								<?php echo $row_TipoPreventivo['TipoPreventivo']; ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-4">
					<label class="control-label">
						<?php echo (ObtenerVariable("LabelTecnicoAdicionalSolicitudLlamada") == "") ? "Técnico/Asesor Adicional" : ObtenerVariable("LabelTecnicoAdicionalSolicitudLlamada"); ?> 
					</label>

					<select name="TecnicoAdicional" id="TecnicoAdicional" class="form-control select2">
						<option value="">Seleccione...</option>

						<?php while ($row_Tecnicos = sqlsrv_fetch_array($SQL_TecnicosAdicionales)) { ?>
							<?php if (in_array($row_Tecnicos['IdCargo'], $ids_grupos) || ($MostrarTodosRecursos || (count($ids_grupos) == 0))) { ?>
								<option value="<?php echo $row_Tecnicos['ID_Empleado']; ?>" <?php if (isset($row['CDU_IdTecnicoAdicional']) && ($row_Tecnicos['ID_Empleado'] == $row['CDU_IdTecnicoAdicional'])) {
									   echo "selected";
								   	} ?> 
									<?php if ((count($ids_grupos) > 0) && (!in_array($row_Tecnicos['IdCargo'], $ids_grupos))) {
										echo "disabled";
									} ?>>
									<?php echo $row_Tecnicos['NombreEmpleado'] . " (" . $row_Tecnicos['NombreCentroCosto2'] . " - " . $row_Tecnicos['DeCargo'] . ")"; ?>
								</option>
							<?php } ?>
						<?php } ?>
					</select>
				</div>

				<div class="col-lg-2"></div>

				<div class="col-lg-4">
					<label class="control-label">
						Contrato/Campaña <span class="text-danger">*</span>
					</label>
					
					<select name="CDU_Contrato" id="CDU_Contrato" class="form-control" required>
						<option value="" disabled selected>Seleccione...</option>
						
						<?php while ($row_Contrato = sqlsrv_fetch_array($SQL_ContratosLlamada)) { ?>
							<option value="<?php echo $row_Contrato['NombreContrato']; ?>" <?php if (isset($row['CDU_Contrato']) && ($row_Contrato['NombreContrato'] == $row['CDU_Contrato'])) {
								echo "selected";
							} ?>>
								<?php echo $row_Contrato['NombreContrato']; ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
			<!-- /.form-group -->

			<div class="form-group row">
				<div class="col-lg-12">
					<label class="control-label">
						Comentario <span class="text-danger">*</span>
					</label>

					<textarea required type="text" name="Comentario" id="Comentario" rows="3" maxlength="3000"
						class="form-control"><?php echo $row['ComentarioLlamada'] ?? ""; ?></textarea>
				</div>
			</div>
			<!-- /.form-group -->
		</div>
		<!-- /.modal-body -->

		<div class="modal-footer">
			<button type="button" class="btn btn-secondary md-btn-flat" data-dismiss="modal">Cerrar</button>

			<?php if(!$SolicitudCerrada && $edit && $ActualizarSolicitud) { ?>
				<button type="submit" class="btn btn-primary md-btn-flat"><i class="fas fa-save"></i> Guardar/Actualizar</button>			
			<?php } elseif(!$SolicitudCerrada && !$edit && $CrearSolicitud) { ?>
				<button type="submit" class="btn btn-primary md-btn-flat"><i class="fas fa-save"></i> Guardar/Crear</button>
			<?php } ?>
		</div>
		<!-- /modal-footer -->
	</div>
</form>

<script>
	$(document).ready(function () {
		// Al cerrar el modal ModalAct
		$('#ModalAct').on('hidden.bs.modal', function () {
			// SMM, 02/11/2023
			$(".select2").select2();
		});

		let options = {
			url: function (phrase) {
				return `ajx_buscar_datos_json.php?type=7&id=${phrase}`;
			},
			getValue: "NombreBuscarCliente",
			requestDelay: 400,
			list: {
				match: {
					enabled: true
				},
				onClickEvent: function () {
					let value = $("#NombreClienteLlamada").getSelectedItemData().CodigoCliente;
					$("#ClienteLlamada").val(value).trigger("change");
				}
			}
		};
		$("#NombreClienteLlamada").easyAutocomplete(options);

		$("#ClienteLlamada").on("change", function () {
			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=3&id=${$(this).val()}&sucline=1`,
				success: function (response) {
					$('#SucursalCliente').html(response).fadeIn();
					$('#SucursalCliente').trigger('change');
				}
			});
		});

		// SMM, 25/10/2023
		$("#Series").change(function () {
			let Series = document.getElementById('Series').value;
			if (Series !== "") {
				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: {
						type: 30,
						id: Series
					},
					dataType: 'json',
					success: function (data) {
						console.log(data);

						$('#OrigenLlamada').val(data.OrigenLlamada || '""');
						$('#OrigenLlamada').trigger('change');
						$('#TipoProblema').val(data.TipoProblemaLlamada || '""');
						$('#TipoProblema').trigger('change');
						$('#TipoLlamada').val(data.TipoLlamada || '""');
						$('#TipoLlamada').trigger('change');
					},
					error: function (error) {
						console.log("AJAX error:", error);
					}
				});
			} else {
				$('.ibox-content').toggleClass('sk-loading', false);
			}
		});

		// SMM, 25/10/2023
		$("#OrigenLlamada").change(function () {
			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=46&id=${$(this).val()}&serie=${$("#Series").val()}`,
				success: function (response) {
					$('#TipoProblema').html(response).fadeIn();
					$('#TipoProblema').trigger('change');
				}
			});
		});

		// SMM, 25/10/2023
		$("#TipoProblema").change(function () {
			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=47&id=${$(this).val()}&serie=${$("#Series").val()}`,
				success: function (response) {
					$('#TipoLlamada').html(response).fadeIn();
					$('#TipoLlamada').trigger('change');
				}
			});
		});

		// Genera conflictos con los "select2" del principal.
		$(".select2").select2({
			dropdownParent: $('#ModalAct')
		});
		// Pero se resuelven "onsubmit". SMM, 02/11/2023

		$("#frmActividad").validate({
			submitHandler: function (form, event) {
				event.preventDefault(); // Prevenir redirrección.
				blockUI(); // Carga iniciada.

				let formData = new FormData(form);
				let jsonForm = Object.fromEntries(formData);
				console.log("Line 765", jsonForm);

				let campanas = $("#Campanas").val();
				let CampanasAsociadas = campanas.join(";");
				
				console.log(CampanasAsociadas);
				let sp_type = <?php echo $edit ? 3 : 1; ?>

				let jsonActividad = {
					Type: sp_type,
					ID_SolicitudLlamadaServicio: jsonForm.ID_SolicitudLlamadaServicio,
					Tecnico: jsonForm.Tecnico,
					TecnicoAdicional: jsonForm.TecnicoAdicional,
					Comentario: jsonForm.Comentario,
					FechaCreacion: jsonForm.FechaCreacion,
					FechaFinCreacion: jsonForm.FechaFinCreacion,
					FechaAgenda: jsonForm.FechaAgenda,
					FechaFinAgenda: jsonForm.FechaFinAgenda,
					HoraCreacion: jsonForm.HoraCreacion,
					HoraFinCreacion: jsonForm.HoraFinCreacion,
					HoraAgenda: jsonForm.HoraAgenda,
					HoraFinAgenda: jsonForm.HoraFinAgenda,
					CDU_Kilometros: jsonForm.CDU_Kilometros,
					CDU_TipoPreventivo: jsonForm.CDU_TipoPreventivo,
					Series: jsonForm.Series,
					OrigenLlamada: jsonForm.OrigenLlamada,
					TipoLlamada: jsonForm.TipoLlamada,
					TipoProblema: jsonForm.TipoProblema,
					SubTipoProblema: jsonForm.SubTipoProblema,
					CDU_Contrato: jsonForm.CDU_Contrato,
					Cliente: jsonForm.ClienteLlamada,
					SucursalCliente: jsonForm.SucursalCliente,
					NumeroSerie: jsonForm.SerialInterno,
					IdTarjetaEquipo: jsonForm.NumeroSerie,
					Campanas: CampanasAsociadas
				};

				// SMM, 02/11/2023
				if(ValidarFechas() && ValidarTecnicos()) {
					$.ajax({
						type: "POST",
						data: jsonActividad,
						url: "programacion_solicitudes_actividad.php",
						success: function (response) {
							if (response == "OK") {
								Swal.fire({
									title: "¡Listo!",
									text: "La solicitud se <?php echo ($edit) ? "actualizo": "creo"; ?> correctamente.",
									icon: 'success',
								});

								// Refrescar desde el documento principal.
								RefrescarCalendario();

								// Cerrar modal.
								$('#ModalAct').modal("hide");
							} else {
								Swal.fire({
									title: "¡Advertencia!",
									text: response,
									icon: "warning",
								});
							}

							// Carga terminada.
							blockUI(false);
						},
						error: function (error) {
							Swal.fire({
								title: "¡Advertencia!",
								text: "Ocurrio un error inesperado.",
								icon: "warning",
							});

							console.log("Error:", error.responseText);
							blockUI(false); // Carga terminada.
						}
					});
					// Fin del AJAX
				} else {
					blockUI(false); // Carga terminada.
				}
			}
			// submitHandler()
		});

		<?php if (PermitirFuncion(341) || !$edit) { ?>
			$(".fecha").flatpickr({
				dateFormat: "Y-m-d",
				static: true,
				allowInput: true
			});

			$(".hora").flatpickr({
				enableTime: true,
				noCalendar: true,
				dateFormat: "H:i",
				time_24hr: true,
				static: true,
				allowInput: true
			});
		<?php } else { ?>
			$(".fecha, .hora").prop("readonly", true);
		<?php } ?>

		// SMM, 10/11/2023
		<?php if ($edit && PermitirFuncion(344)) { ?>
			// Deshabilitar todo.
			$("#frmActividad input").prop("disabled", true);
			$("#frmActividad select").prop("disabled", true);
			$("#frmActividad textarea").prop("disabled", true);
			$("#frmActividad #btnTE").prop("disabled", true);
			
			// Habilitar ID, requerido para la actualización.
			$("#ID_SolicitudLlamadaServicio").prop("disabled", false);

			// Habilitar fecha solicitud.
			$(".fechaAgenda, .horaAgenda").prop("disabled", false);

			$(".fechaAgenda").flatpickr({
				dateFormat: "Y-m-d",
				static: true,
				allowInput: true
			});
			$(".horaAgenda").flatpickr({
				enableTime: true,
				noCalendar: true,
				dateFormat: "H:i",
				time_24hr: true,
				static: true,
				allowInput: true
			});

			// Habilitar técnico adicional.
			$("#TecnicoAdicional").prop("disabled", false);
		<?php } elseif($edit) { ?>
			// Deben estar activos en la edición. SMM, 14/11/2023
			$(".fechaAgenda").flatpickr({
				dateFormat: "Y-m-d",
				static: true,
				allowInput: true
			});
			$(".horaAgenda").flatpickr({
				enableTime: true,
				noCalendar: true,
				dateFormat: "H:i",
				time_24hr: true,
				static: true,
				allowInput: true
			});
		<?php } ?>

		// SMM, 07/11/2023
		<?php if(PermitirFuncion(342)) { ?>
			$(".fecha, .hora").on("change", function() {
				let fechaCreacion = $("#FechaCreacion").val();
				let horaCreacion = $("#HoraCreacion").val();
				let fechaFinCreacion = $("#FechaFinCreacion").val();
				let horaFinCreacion = $("#HoraFinCreacion").val();

				// Igualar la fecha de solicitud a la de actividad.
				$("#FechaAgenda").val(fechaCreacion);
				$("#HoraAgenda").val(horaCreacion);
				$("#FechaFinAgenda").val(fechaFinCreacion);
				$("#HoraFinAgenda").val(horaFinCreacion);
			});
		<?php } ?>
		
		// maxLength("Comentario");

		// SMM, 05/10/2023
		$("#NumeroSerie").on("change", function () {
			if ($(this).val() != "") {
				$('#AddCampana').prop('disabled', false);
			} else {
				$('#AddCampana').prop('disabled', true);
			}

			// SMM, 22/11/2023
			let id_tarjeta_equipo = $(this).val();

			$.ajax({
				type: "POST",
				url: `ajx_cbo_select.php?type=49&id=${id_tarjeta_equipo}`,
				success: function (response) {
					$("#Campanas").html(response).fadeIn();
					$("#Campanas").trigger('change');

					// Iterar sobre cada ID y seleccionar opciones específicas. SMM, 22/11/2023
					if(id_tarjeta_equipo === "<?php echo $row["IdTarjetaEquipo"] ?? ""; ?>") {
						<?php while (isset($SQL_CampanasClone) && $row_Clone = sqlsrv_fetch_array($SQL_CampanasClone)) { ?>
							$("#Campanas option[value='<?php echo $row_Clone["id_campana"] ?? ""; ?>']").prop("selected", true);
						<?php } ?>

						// Cargar de nuevo con los ids seleccionados.
						$("#Campanas").trigger('change');
					}
					// Eso lo hice con un .forEach(), en la solicitud directa.
				},
				error: function (error) {
					console.log("error (410), ", error);
				}
			});

			// Verifico si la TE no corresponde a la de la solicitud. SMM, 22/11/2023
			if(id_tarjeta_equipo !== "<?php echo $row["IdTarjetaEquipo"] ?? ""; ?>") {
				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: {
						type: 44,
						id: id_tarjeta_equipo,
						clt: $("#ClienteLlamada").val()	
					},
					dataType: 'json',
					success: function (data) {
						// Si no corresponde modifico la marca en base a la TE.
						$("#CDU_Marca").val(data.CDU_IdMarca);
						$("#CDU_Marca").change();
					},
					error: function (error) {
						console.log("Error CDU_Marca", error.responseText);
					}
				});
			}
		});

		$("#AddCampana").on("click", function () {
			AdicionarCampanaAsincrono();
		});

		// Función para oscurecer el primer modal cuando se abre el segundo.
		$('#myModal2, #mdTE').on('show.bs.modal', function () {
			$('#ModalAct').addClass('modal-backdrop');
		});

		// Función para eliminar el oscurecimiento cuando se cierra el segundo modal.
		$('#myModal2, #mdTE').on('hidden.bs.modal', function () {
			$('#ModalAct').removeClass('modal-backdrop');
			
			// Indicarle al Scroll que aún esta abierto el modal principal.
			$('body').addClass('modal-open');
		});

		$("#AddEquipo").on("click", function () {
			AdicionarEquipo();
		});

		$("#AddCliente").on("click", function () {
			AdicionarCliente();
		});

		// SMM, 16/11/2023
		<?php if($edit) { ?>
			console.log("entrando a la validación de la edición y campañas");
			
			// $("#ClienteLlamada").change();
			$("#NumeroSerie").change();
			
			// $("#NombreClienteLlamada").prop("readonly", true);
			// $("#SucursalCliente").prop("disabled", true);
			
			// $("#NumeroSerie").prop("disabled", true);
			// $("#Campanas").prop("disabled", true);
		<?php } ?>

		// SMM, 31/10/2023
		$(".TecnicoSugerido").change(function () {
			let IdSerieLlamada = $("#Series").val() || "";
			let IdOrigen = $("#OrigenLlamada").val() || "";
			let IdTipoProblema = $("#TipoProblema").val() || "";
			let IdMarca = $("#CDU_Marca").val() || "";

			if ((IdSerieLlamada != "") && (IdOrigen != "") && (IdTipoProblema != "") && (IdMarca != "")) {
				$.ajax({
					url: "ajx_buscar_datos_json.php",
					data: {
						type: 50,
						IdSerieLlamada: IdSerieLlamada,
						IdOrigen: IdOrigen,
						IdTipoProblema: IdTipoProblema,
						IdMarca: IdMarca
					},
					dataType: 'json',
					success: function (data) {
						$("#TecnicoAdicional").val(data.IdTecnico);
						$("#TecnicoAdicional").change();

						Swal.fire({
							title: 'Técnico Adicional Sugerido',
							text: data.DeTecnico,
							icon: 'success',
						});
					},
					error: function (error) {
						console.log("Error TecnicoSugerido:", error.responseText);
					}
				});
			}
		});
		// Hasta aquí, 31/10/2023

		// SMM, 09/11/2023
		<?php if($SolicitudCerrada || (!$edit && !$CrearSolicitud) || ($edit && !$ActualizarSolicitud)) { ?>
			$("#frmActividad input").prop("disabled", true);
			$("#frmActividad select").prop("disabled", true);
			$("#frmActividad textarea").prop("disabled", true);
			$("#frmActividad #btnTE").prop("disabled", true);
		<?php } ?>
	});

	// SMM, 28/11/2023
	function AdicionarCampanaAsincrono() {
		let id_tarjeta_equipo = $("#NumeroSerie").val();

		$.ajax({
			type: "POST",
			data: {
				id_tarjeta_equipo: id_tarjeta_equipo,
				asincrono: 1, // Asincrono - En la creación.
				solicitud: "Solicitud"
			},
			url: "md_adicionar_campanas.php",
			success: function (response) {
				$('#ContenidoModal2').html(response);
				$('#myModal2').modal("show");
			},
			error: function (error) {
				console.log("error (435), ", error);
			}
		});
	}

	function AdicionarEquipo() {
		window.open(`tarjeta_equipo.php`, "_blank");
	}

	function AdicionarCliente() {
		window.open(`socios_negocios.php`, "_blank");
	}

	// SMM, 07/11/2023
	function ValidarFechas() {
		let fechaCreacion = new Date(`${$("#FechaCreacion").val()}T${$("#HoraCreacion").val()}`);
		let fechaFinCreacion = new Date(`${$("#FechaFinCreacion").val()}T${$("#HoraFinCreacion").val()}`);
		let fechaAgenda = new Date(`${$("#FechaAgenda").val()}T${$("#HoraAgenda").val()}`);
		let fechaFinAgenda = new Date(`${$("#FechaFinAgenda").val()}T${$("#HoraFinAgenda").val()}`);

		if ((fechaAgenda >= fechaFinAgenda) || (fechaCreacion >= fechaFinCreacion)) {
			Swal.fire({
				title: '¡Advertencia!',
				text: 'Tiempo no válido. Ingrese una duración positiva.',
				icon: 'warning',
			});
			return false;
		} 
		// SMM, 03/11/2023
		else if(fechaCreacion > fechaAgenda) {
			Swal.fire({
				title: '¡Advertencia!',
				text: 'La fecha de inicio de la solicitud debe ser menor o igual a la fecha de inicio de la actividad.',
				icon: 'warning',
			});
			return false;
		}
		return true;
	}

	// SMM, 02/11/2023
	function ValidarTecnicos() {
		let Tecnico = $('#Tecnico').val() || "";
		let TecnicoAdicional = $('#TecnicoAdicional').val() || "";

		// Verifica si la opción seleccionada está deshabilitada.
		let disabledTecnico = $(`#Tecnico option[value='${Tecnico}']`).is(':disabled');
		let disabledTecnicoAdicional = $(`#TecnicoAdicional option[value='${TecnicoAdicional}']`).is(':disabled');
		

		// Validación "required"
		if (Tecnico === "") {
			Swal.fire({
				title: '¡Técnico Obligatorio!',
				text: 'No se ha seleccionado un técnico o se selecciono un técnico bloqueado.',
				icon: 'warning',
			});
			return false;
		}

		// La opción seleccionada está deshabilitada.
		if (disabledTecnico || disabledTecnicoAdicional) {
			Swal.fire({
				title: '¡Advertencia!',
				text: 'El técnico esta deshabilitado.',
				icon: 'warning',
			});
			return false;
		}
		return true;
	}

	function ConsultarAgenda() {
		let Agenda = document.getElementById("ID_SolicitudLlamadaServicio");

		if (Agenda.value != "") {
			window.open(`solicitud_llamada.php?id=${Base64.encode(Agenda.value)}&tl=1`, "_blank");
		}
	}

	function ConsultarCliente() {
		let Cliente = document.getElementById("ClienteLlamada");

		if (Cliente.value != "") {
			window.open(`socios_negocios.php?id=${Base64.encode(Cliente.value)}&tl=1`, "_blank");
		}
	}

	// SMM, 28/11/2023
	function ConsultarEquipo() {
		let Equipo = document.getElementById("NumeroSerie");

		if (Equipo.value != "") {
			window.open(`tarjeta_equipo.php?id=${Base64.encode(Equipo.value)}&tl=1`, "_blank");
		}
	}
</script>