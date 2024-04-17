<?php
require_once "includes/conexion.php";

PermitirAcceso(351);
$sw_error = 0;

if (isset($_POST['Metodo']) && ($_POST['Metodo'] == 3)) {
    try {
        $Param = array(
            $_POST['Metodo'], // 3 - Eliminar
            isset($_POST['ID']) ? $_POST['ID'] : "NULL",
        );

        if ($_POST['TipoDoc'] == "Unidades") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_UnidadMedidas', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la Unidad de Medida.";
            }
        }

    } catch (Exception $e) {
        $sw_error = 1;
        $msg_error = $e->getMessage();
    }
}

//Insertar datos o actualizar datos
if ((isset($_POST['frmType']) && ($_POST['frmType'] != "")) || (isset($_POST['Metodo']) && ($_POST['Metodo'] == 2))) {
    try {

        if ($_POST['TipoDoc'] == "Unidades") {
            $FechaHora = "'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'";
            $Usuario = "'" . $_SESSION['CodUser'] . "'";

            $ID = (isset($_POST['ID_Actual']) && ($_POST['ID_Actual'] != "")) ? $_POST['ID_Actual'] : "NULL";

            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombreUnidadMedida'] . "'",
                "'" . $_POST['Estado'] . "'",
				"'" . $_POST['Comentarios'] . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_UnidadMedidas', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar la Unidad de Medida.";
            }
        }

        // OK
        if ($sw_error == 0) {
            $TipoDoc = $_POST['TipoDoc'];
            header("Location:parametros_te.php?doc=$TipoDoc&a=" . base64_encode("OK_PRUpd") . "#$TipoDoc");
        }

    } catch (Exception $e) {
        $sw_error = 1;
        $msg_error = $e->getMessage();
    }

}

// SMM, 17/04/2024
$SQL_Unidades = Seleccionar("uvw_tbl_TarjetaEquipo_UnidadMedidas", "*");
$SQL_Marcas = Seleccionar("uvw_tbl_TarjetaEquipo_Marcas", "*");
$SQL_Lineas = Seleccionar("uvw_tbl_TarjetaEquipo_Lineas", "*");
$SQL_Fabricantes = Seleccionar("uvw_tbl_TarjetaEquipo_Fabricantes", "*");
$SQL_Annios = Seleccionar("uvw_tbl_TarjetaEquipo_Annios", "*");
$SQL_Ubicaciones = Seleccionar("uvw_tbl_TarjetaEquipo_Ubicaciones", "*");
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include_once "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Parámetros de Tarjeta de Equipo | <?php echo NOMBRE_PORTAL; ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->

<style>
	.ibox-title a{
		color: inherit !important;
	}
	.collapse-link:hover{
		cursor: pointer;
	}
	.swal2-container {
	  	z-index: 9000;
	}
	.easy-autocomplete {
		 width: 100% !important
	}
</style>

<?php
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_PRUpd"))) {
    echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'Datos actualizados exitosamente.',
                icon: 'success'
            });
		});
		</script>";
}
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_PRDel"))) {
    echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'Datos eliminados exitosamente.',
                icon: 'success'
            });
		});
		</script>";
}
if (isset($sw_error) && ($sw_error == 1)) {
    echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Ha ocurrido un error!',
                text: '" . LSiqmlObs($msg_error) . "',
                icon: 'warning'
            });
		});
		</script>";
}
?>
<script>

</script>
<!-- InstanceEndEditable -->
</head>

<body>

<div id="wrapper">

    <?php include_once "includes/menu.php";?>

    <div id="page-wrapper" class="gray-bg">
        <?php include_once "includes/menu_superior.php";?>
        <!-- InstanceBeginEditable name="Contenido" -->
        <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-8">
                    <h2>Parámetros de Tarjeta de Equipo</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index1.php">Inicio</a>
                        </li>
						<li>
                            <a href="#">Mantenimiento</a>
                        </li>
						<li>
                            <a href="#">Equipos</a>
                        </li>
                        <li class="active">
                            <strong>Parámetros de Tarjeta de Equipo</strong>
                        </li>
                    </ol>
                </div>
            </div>
            <?php //echo $Cons;?>
         <div class="wrapper wrapper-content">
			 <div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content" id="ContenidoModal">
						<!-- Contenido generado por JS -->
					</div>
				</div>
			</div>
			 <div class="row">
			 	<div class="col-lg-12">
					<div class="ibox-content">
						<?php include "includes/spinner.php";?>

						<div class="tabs-container">

						 	<ul class="nav nav-tabs">
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Unidades") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-1"><i class="fa fa-list"></i> Unidades</a>
								</li>
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Marcas")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-2"><i class="fa fa-list"></i> Marcas</a>
								</li>
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Lineas")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-3"><i class="fa fa-list"></i> Lineas</a>
								</li>
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Fabricantes")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-4"><i class="fa fa-list"></i> Fabricantes</a>
								</li>
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Annios")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-5"><i class="fa fa-list"></i> Años</a>
								</li>
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Ubicaciones")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-6"><i class="fa fa-list"></i> Ubicaciones</a>
								</li>
							</ul>

							<div class="tab-content">
								
								<!-- Inicio, lista Unidades -->
								<div id="tab-1" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Unidades") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Unidades">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Unidades de Medida</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Unidades');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Unidad Medida Equipo</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Unidades = sqlsrv_fetch_array($SQL_Unidades)) {?>
															<tr>
																<td><?php echo $row_Unidades['unidad_medida_equipo']; ?></td>
																<td><?php echo $row_Unidades['comentarios']; ?></td>
																<td><?php echo isset($row_Unidades['fecha_actualizacion']) ? date_format($row_Unidades['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Unidades['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_Unidades['estado_unidad_medida_equipo'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Unidades['estado_unidad_medida_equipo'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Unidades['id_unidad_medida_equipo']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Unidades['id_unidad_medida_equipo']; ?>','Unidades');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Unidades['id_unidad_medida_equipo']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Unidades['id_unidad_medida_equipo']; ?>','Unidades');"><i class="fa fa-trash"></i> Eliminar</button>
																</td>
															</tr>
															 <?php }?>
														</tbody>
													</table>
												</div>
											</div> <!-- ibox-content -->
										</div> <!-- ibox -->
									</form>
								</div>
								<!-- Fin, lista Unidades -->

								<!-- Inicio, lista Marcas -->
								<div id="tab-2" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Marcas")) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Marcas">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Marcas de Equipo</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Marcas');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Marca Equipo</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Marcas = sqlsrv_fetch_array($SQL_Marcas)) {?>
															<tr>
																<td><?php echo $row_Marcas['unidad_medida_equipo']; ?></td>
																<td><?php echo $row_Marcas['comentarios']; ?></td>
																<td><?php echo isset($row_Marcas['fecha_actualizacion']) ? date_format($row_Marcas['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Marcas['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_Marcas['estado_unidad_medida_equipo'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Marcas['estado_unidad_medida_equipo'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Marcas['id_unidad_medida_equipo']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Marcas['id_unidad_medida_equipo']; ?>','Marcas');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Marcas['id_unidad_medida_equipo']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Marcas['id_unidad_medida_equipo']; ?>','Marcas');"><i class="fa fa-trash"></i> Eliminar</button>
																</td>
															</tr>
															 <?php }?>
														</tbody>
													</table>
												</div>
											</div> <!-- ibox-content -->
										</div> <!-- ibox -->
									</form>
								</div>
								<!-- Fin, lista Marcas -->

								<!-- Inicio, lista Lineas -->
								<div id="tab-3" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Lineas")) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Lineas">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Lineas de Equipo</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Lineas');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Linea Equipo</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Lineas = sqlsrv_fetch_array($SQL_Lineas)) {?>
															<tr>
																<td><?php echo $row_Lineas['unidad_medida_equipo']; ?></td>
																<td><?php echo $row_Lineas['comentarios']; ?></td>
																<td><?php echo isset($row_Lineas['fecha_actualizacion']) ? date_format($row_Lineas['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Lineas['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_Lineas['estado_unidad_medida_equipo'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Lineas['estado_unidad_medida_equipo'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Lineas['id_unidad_medida_equipo']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Lineas['id_unidad_medida_equipo']; ?>','Lineas');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Lineas['id_unidad_medida_equipo']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Lineas['id_unidad_medida_equipo']; ?>','Lineas');"><i class="fa fa-trash"></i> Eliminar</button>
																</td>
															</tr>
															 <?php }?>
														</tbody>
													</table>
												</div>
											</div> <!-- ibox-content -->
										</div> <!-- ibox -->
									</form>
								</div>
								<!-- Fin, lista Lineas -->

								<!-- Inicio, lista Fabricantes -->
								<div id="tab-4" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Fabricantes")) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Fabricantes">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Fabricantes de Equipo</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Fabricantes');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Fabricante Equipo</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Fabricantes = sqlsrv_fetch_array($SQL_Fabricantes)) {?>
															<tr>
																<td><?php echo $row_Fabricantes['unidad_medida_equipo']; ?></td>
																<td><?php echo $row_Fabricantes['comentarios']; ?></td>
																<td><?php echo isset($row_Fabricantes['fecha_actualizacion']) ? date_format($row_Fabricantes['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Fabricantes['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_Fabricantes['estado_unidad_medida_equipo'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Fabricantes['estado_unidad_medida_equipo'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Fabricantes['id_unidad_medida_equipo']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Fabricantes['id_unidad_medida_equipo']; ?>','Fabricantes');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Fabricantes['id_unidad_medida_equipo']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Fabricantes['id_unidad_medida_equipo']; ?>','Fabricantes');"><i class="fa fa-trash"></i> Eliminar</button>
																</td>
															</tr>
															 <?php }?>
														</tbody>
													</table>
												</div>
											</div> <!-- ibox-content -->
										</div> <!-- ibox -->
									</form>
								</div>
								<!-- Fin, lista Fabricantes -->

								<!-- Inicio, lista Annios -->
								<div id="tab-5" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Annios")) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Annios">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Años de Equipo</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Annios');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Año Equipo</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Annios = sqlsrv_fetch_array($SQL_Annios)) {?>
															<tr>
																<td><?php echo $row_Annios['unidad_medida_equipo']; ?></td>
																<td><?php echo $row_Annios['comentarios']; ?></td>
																<td><?php echo isset($row_Annios['fecha_actualizacion']) ? date_format($row_Annios['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Annios['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_Annios['estado_unidad_medida_equipo'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Annios['estado_unidad_medida_equipo'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Annios['id_unidad_medida_equipo']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Annios['id_unidad_medida_equipo']; ?>','Annios');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Annios['id_unidad_medida_equipo']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Annios['id_unidad_medida_equipo']; ?>','Annios');"><i class="fa fa-trash"></i> Eliminar</button>
																</td>
															</tr>
															 <?php }?>
														</tbody>
													</table>
												</div>
											</div> <!-- ibox-content -->
										</div> <!-- ibox -->
									</form>
								</div>
								<!-- Fin, lista Annios -->

								<!-- Inicio, lista Ubicaciones -->
								<div id="tab-6" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Ubicaciones")) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Ubicaciones">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Ubicaciones</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Ubicaciones');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Ubicación</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Ubicaciones = sqlsrv_fetch_array($SQL_Ubicaciones)) {?>
															<tr>
																<td><?php echo $row_Ubicaciones['unidad_medida_equipo']; ?></td>
																<td><?php echo $row_Ubicaciones['comentarios']; ?></td>
																<td><?php echo isset($row_Ubicaciones['fecha_actualizacion']) ? date_format($row_Ubicaciones['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Ubicaciones['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_Ubicaciones['estado_unidad_medida_equipo'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Ubicaciones['estado_unidad_medida_equipo'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Ubicaciones['id_unidad_medida_equipo']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Ubicaciones['id_unidad_medida_equipo']; ?>','Ubicaciones');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Ubicaciones['id_unidad_medida_equipo']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Ubicaciones['id_unidad_medida_equipo']; ?>','Ubicaciones');"><i class="fa fa-trash"></i> Eliminar</button>
																</td>
															</tr>
															 <?php }?>
														</tbody>
													</table>
												</div>
											</div> <!-- ibox-content -->
										</div> <!-- ibox -->
									</form>
								</div>
								<!-- Fin, lista Ubicaciones -->

							</div> <!-- tab-content -->
						</div> <!-- tabs-container -->
					</div>
          		</div>
			 </div>

        </div>
        <!-- InstanceEndEditable -->
        <?php include_once "includes/footer.php";?>

    </div>
</div>
<?php include_once "includes/pie.php";?>
<!-- InstanceBeginEditable name="EditRegion4" -->

<script>
	$(document).ready(function(){
		$(".select2").select2();
		$('.i-checks').iCheck({
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green',
			});

		$('.dataTables-example').DataTable({
			pageLength: 10,
			dom: '<"html5buttons"B>lTfgitp',
			language: {
				"decimal":        "",
				"emptyTable":     "No se encontraron resultados.",
				"info":           "Mostrando _START_ - _END_ de _TOTAL_ registros",
				"infoEmpty":      "Mostrando 0 - 0 de 0 registros",
				"infoFiltered":   "(filtrando de _MAX_ registros)",
				"infoPostFix":    "",
				"thousands":      ",",
				"lengthMenu":     "Mostrar _MENU_ registros",
				"loadingRecords": "Cargando...",
				"processing":     "Procesando...",
				"search":         "Filtrar:",
				"zeroRecords":    "Ningún registro encontrado",
				"paginate": {
					"first":      "Primero",
					"last":       "Último",
					"next":       "Siguiente",
					"previous":   "Anterior"
				},
				"aria": {
					"sortAscending":  ": Activar para ordenar la columna ascendente",
					"sortDescending": ": Activar para ordenar la columna descendente"
				}
			},
			buttons: []
		});
	});
</script>

<script>
function CrearCampo(doc){
	$('.ibox-content').toggleClass('sk-loading',true);

	$.ajax({
		type: "POST",
		url: "md_parametros_te.php",
		data:{
			doc:doc
		},
		success: function(response){
			$('.ibox-content').toggleClass('sk-loading',false);
			$('#ContenidoModal').html(response);
			$('#myModal').modal("show");
		}
	});
}

function EditarCampo(id, doc){
	$('.ibox-content').toggleClass('sk-loading',true);

	$.ajax({
		type: "POST",
		url: "md_parametros_te.php",
		data:{
			doc:doc,
			id:id,
			edit:1
		},
		success: function(response){
			$('.ibox-content').toggleClass('sk-loading',false);
			$('#ContenidoModal').html(response);
			$('#myModal').modal("show");
		}
	});
}

function EliminarCampo(id, doc){
	Swal.fire({
		title: "¿Está seguro que desea eliminar este registro?",
		icon: "question",
		showCancelButton: true,
		confirmButtonText: "Si, confirmo",
		cancelButtonText: "No"
	}).then((result) => {
		if (result.isConfirmed) {
			// $('.ibox-content').toggleClass('sk-loading',true);

			$.ajax({
				type: "post",
				url: "parametros_te.php",
				data: { TipoDoc: doc, ID: id, Metodo: 3 },
				async: false,
				success: function(data){
					// console.log(data);
					location.href = `parametros_te.php?doc=${doc}&a=<?php echo base64_encode("OK_PRDel"); ?>`;
				},
				error: function(error) {
					console.error("consulta erronea");
				}
			});
		}
	});

	return result;
}
</script>
<!-- InstanceEndEditable -->

</body>

<!-- InstanceEnd -->
</html>
<?php sqlsrv_close($conexion);?>