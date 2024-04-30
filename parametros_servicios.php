<?php
require_once "includes/conexion.php";

PermitirAcceso(352);
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
        } elseif ($_POST['TipoDoc'] == "Marcas") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Marcas', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la Marca.";
            }
        } elseif ($_POST['TipoDoc'] == "Lineas") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Lineas', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la Linea.";
            }
        } elseif ($_POST['TipoDoc'] == "Fabricantes") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Fabricantes', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar el Fabricante.";
            }
        } elseif ($_POST['TipoDoc'] == "Annios") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Annios', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar el Año.";
            }
        } elseif ($_POST['TipoDoc'] == "Ubicaciones") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Ubicaciones', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la Ubicación.";
            }
        } elseif ($_POST['TipoDoc'] == "Motivos") {
            $SQL = EjecutarSP('sp_tbl_Actividades_ParadaMotivo', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar el Motivo.";
            }
        }

    } catch (Exception $e) {
        $sw_error = 1;
        $msg_error = $e->getMessage();
    }
}

//Insertar datos o actualizar datos. SMM, 18/04/2024
if ((isset($_POST['frmType']) && ($_POST['frmType'] != "")) || (isset($_POST['Metodo']) && ($_POST['Metodo'] == 2))) {
    
	try {
		$ID = (isset($_POST['ID_Actual']) && ($_POST['ID_Actual'] != "")) ? $_POST['ID_Actual'] : "NULL";
		$FechaHora = "'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'";
        $Usuario = "'" . ($_SESSION['CodUser'] ?? "") . "'";
		$TipoDoc = $_POST['TipoDoc'] ?? "";

        if ($TipoDoc == "Unidades") {
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
        } elseif ($TipoDoc == "Marcas") {
            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombreMarca'] . "'",
                "'" . $_POST['Estado'] . "'",
				"'" . $_POST['Comentarios'] . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Marcas', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar la Marca.";
            }
        } elseif ($TipoDoc == "Lineas") {
            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombreLinea'] . "'",
                "'" . $_POST['Estado'] . "'",
				"'" . $_POST['Comentarios'] . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Lineas', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar la Linea.";
            }
        } elseif ($TipoDoc == "Fabricantes") {
            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombreFabricante'] . "'",
                "'" . $_POST['Estado'] . "'",
				"'" . $_POST['Comentarios'] . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Fabricantes', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar el Fabricante.";
            }
        } elseif ($TipoDoc == "Annios") {
            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombreAnnio'] . "'",
                "'" . $_POST['Estado'] . "'",
				"'" . $_POST['Comentarios'] . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Annios', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar el Año.";
            }
        } elseif ($TipoDoc == "Ubicaciones") {
            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombreUbicacion'] . "'",
                "'" . ($_POST['Estado'] ?? "") . "'",
				"'" . ($_POST['Comentarios'] ?? "") . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Ubicaciones', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar la Ubicación.";
            }
        } elseif ($TipoDoc == "Motivos") {
            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombreMotivo'] . "'",
                "'" . ($_POST['Estado'] ?? "") . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_Actividades_ParadaMotivo', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar el Motivo.";
            }
        }

        // OK. SMM, 18/04/2024
        if ($sw_error == 0) {
            header("Location:parametros_servicios.php?doc=$TipoDoc&a=" . base64_encode("OK_PRUpd") . "#$TipoDoc");
        }

    } catch (Exception $e) {
        $sw_error = 1;
        $msg_error = $e->getMessage();
    }
}

// SMM, 29/04/2024
$SQL_Motivos = Seleccionar("uvw_tbl_Actividades_ParadaMotivo", "*");
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
<title>Parámetros de Servicios | <?php echo NOMBRE_PORTAL; ?></title>
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
                    <h2>Parámetros de servicio/mantenimiento</h2>
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
                            <strong>Parámetros de servicio/mantenimiento</strong>
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
							 	<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Motivos") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-1"><i class="fa fa-list"></i> Motivos Parada</a>
								</li>
								<!-- li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Unidades")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-2"><i class="fa fa-list"></i> Unidades</a>
								</li -->
							</ul>

							<div class="tab-content">
								
								<!-- Inicio, lista Motivos -->
								<div id="tab-1" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Motivos") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Motivos">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Motivos de Parada</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Motivos');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Motivo Parada</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Motivos = sqlsrv_fetch_array($SQL_Motivos)) {?>
															<tr>
																<td><?php echo $row_Motivos['motivo_parada']; ?></td>
																<td><?php echo isset($row_Motivos['fecha_actualizacion']) ? date_format($row_Motivos['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Motivos['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_Motivos['estado'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Motivos['estado'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Motivos['id_motivo_parada']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Motivos['id_motivo_parada']; ?>','Motivos');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Motivos['id_motivo_parada']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Motivos['id_motivo_parada']; ?>','Motivos');"><i class="fa fa-trash"></i> Eliminar</button>
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
								<!-- Fin, lista Motivos -->

								<!-- Inicio, lista Unidades ->
								<div id="tab-2" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Unidades")) ? "active" : ""; ?>">
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
											</div>
										</div>
									</form>
								</div>
								<!- Fin, lista Unidades -->

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
		url: "md_parametros_servicios.php",
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
		url: "md_parametros_servicios.php",
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
				url: "parametros_servicios.php",
				data: { TipoDoc: doc, ID: id, Metodo: 3 },
				async: false,
				success: function(data){
					// console.log(data);
					location.href = `parametros_servicios.php?doc=${doc}&a=<?php echo base64_encode("OK_PRDel"); ?>`;
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