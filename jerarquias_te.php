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

        if ($_POST['TipoDoc'] == "Jerarquias") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Jerarquias', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la Unidad de Medida.";
            }
        } elseif ($_POST['TipoDoc'] == "Dimensiones") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_DimensionJerarquias', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la Marca.";
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

        if ($TipoDoc == "Jerarquias") {
            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
				$_POST['ID_Dimension'],
                "'" . $_POST['NombreJerarquia'] . "'",
                "'" . $_POST['Estado'] . "'",
				"'" . $_POST['Comentarios'] . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_Jerarquias', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar la Unidad de Medida.";
            }
        } elseif ($TipoDoc == "Dimensiones") {
            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombreDimension'] . "'",
                "'" . $_POST['Estado'] . "'",
				"'" . $_POST['Comentarios'] . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_DimensionJerarquias', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar la Marca.";
            }
        }

        // OK. SMM, 18/04/2024
        if ($sw_error == 0) {
            header("Location:jerarquias_te.php?doc=$TipoDoc&a=" . base64_encode("OK_PRUpd") . "#$TipoDoc");
        }

    } catch (Exception $e) {
        $sw_error = 1;
        $msg_error = $e->getMessage();
    }
}

// SMM, 17/04/2024
$SQL_Jerarquias = Seleccionar("uvw_tbl_TarjetaEquipo_Jerarquias", "*");
$SQL_Dimensiones = Seleccionar("uvw_tbl_TarjetaEquipo_DimensionJerarquias", "*");
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include_once "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Parámetros de Jerarquías de Equipo | <?php echo NOMBRE_PORTAL; ?></title>
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
                    <h2>Parámetros de Jerarquías de Equipo</h2>
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
                            <strong>Parámetros de Jerarquías de Equipo</strong>
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
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Jerarquias") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-1"><i class="fa fa-list"></i> Jerarquias</a>
								</li>
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Dimensiones")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-2"><i class="fa fa-list"></i> Dimensiones</a>
								</li>
							</ul>

							<div class="tab-content">
								
								<!-- Inicio, lista Jerarquias -->
								<div id="tab-1" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Jerarquias") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Jerarquias">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Jerarquías de Equipo</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Jerarquias');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Dimensión</th>
																<th>Jerarquía Equipo</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Jerarquias = sqlsrv_fetch_array($SQL_Jerarquias)) {?>
															<tr>
																<td><?php echo $row_Jerarquias['dimension_jerarquia'] ?? ""; ?></td>		
																<td><?php echo $row_Jerarquias['jerarquia']; ?></td>
																<td><?php echo $row_Jerarquias['comentarios']; ?></td>
																<td><?php echo isset($row_Jerarquias['fecha_actualizacion']) ? date_format($row_Jerarquias['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Jerarquias['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_Jerarquias['estado_jerarquia'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Jerarquias['estado_jerarquia'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>	
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Jerarquias['id_jerarquia']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Jerarquias['id_jerarquia']; ?>','Jerarquias');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Jerarquias['id_jerarquia']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Jerarquias['id_jerarquia']; ?>','Jerarquias');"><i class="fa fa-trash"></i> Eliminar</button>
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
								<!-- Fin, lista Jerarquias -->

								<!-- Inicio, lista Dimensiones -->
								<div id="tab-2" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Dimensiones")) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Dimensiones">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Dimensiones de Jerarquía</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Dimensiones');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Dimensión Jerarquía</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Dimensiones = sqlsrv_fetch_array($SQL_Dimensiones)) {?>
															<tr>
																<td><?php echo $row_Dimensiones['dimension_jerarquia']; ?></td>
																<td><?php echo $row_Dimensiones['comentarios']; ?></td>
																<td><?php echo isset($row_Dimensiones['fecha_actualizacion']) ? date_format($row_Dimensiones['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Dimensiones['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_Dimensiones['estado_dimension_jerarquia'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Dimensiones['estado_dimension_jerarquia'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Dimensiones['id_dimension_jerarquia']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Dimensiones['id_dimension_jerarquia']; ?>','Dimensiones');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Dimensiones['id_dimension_jerarquia']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Dimensiones['id_dimension_jerarquia']; ?>','Dimensiones');"><i class="fa fa-trash"></i> Eliminar</button>
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
								<!-- Fin, lista Dimensiones -->

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
		url: "md_jerarquias_te.php",
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
		url: "md_jerarquias_te.php",
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
				url: "jerarquias_te.php",
				data: { TipoDoc: doc, ID: id, Metodo: 3 },
				async: false,
				success: function(data){
					// console.log(data);
					location.href = `jerarquias_te.php?doc=${doc}&a=<?php echo base64_encode("OK_PRDel"); ?>`;
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