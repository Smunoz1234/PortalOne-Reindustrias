<?php
require_once "includes/conexion.php";

PermitirAcceso(349);
$sw_error = 0;

if (isset($_POST['Metodo']) && ($_POST['Metodo'] == 3)) {
    try {
        $Param = array(
            $_POST['Metodo'], // 3 - Eliminar
            isset($_POST['ID']) ? $_POST['ID'] : "NULL",
        );

        if ($_POST['TipoDoc'] == "Tipos") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_TiposEquipos', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar el Tipo de Equipo.";
            }
        } elseif ($_POST['TipoDoc'] == "Propiedades") {
            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_TiposEquipos_Propiedades', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la Propiedad.";
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

        if ($_POST['TipoDoc'] == "Tipos") {
            $FechaHora = "'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'";
            $Usuario = "'" . $_SESSION['CodUser'] . "'";

            $ID = (isset($_POST['ID_Actual']) && ($_POST['ID_Actual'] != "")) ? $_POST['ID_Actual'] : "NULL";

            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombreTipoEquipo'] . "'",
                "'" . $_POST['Estado'] . "'",
				"'" . $_POST['Comentarios'] . "'",
                $Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_TiposEquipos', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar el Tipo de Equipo.";
            }
        } elseif ($_POST['TipoDoc'] == "Propiedades") {
            $FechaHora = "'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'";
            $Usuario = "'" . $_SESSION['CodUser'] . "'";

            $ID = (isset($_POST['ID_Actual']) && ($_POST['ID_Actual'] != "")) ? $_POST['ID_Actual'] : "NULL";

            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['NombrePropiedad'] . "'",
                "'" . $_POST['ID_TipoEquipo'] . "'",
                "'" . $_POST['ID_TipoEquipo_Campo'] . "'",
				"'" . ($_POST['TablaVinculada'] ?? "") . "'",
                "'" . $_POST['Obligatorio'] . "'",
				"'" . ($_POST['Multiple'] ?? "") . "'",
				"'" . ($_POST['EtiquetaLista'] ?? "") . "'",
				"'" . ($_POST['ValorLista'] ?? "") . "'",
				$Usuario, // Usuario de actualización y creación
            );

            $SQL = EjecutarSP('sp_tbl_TarjetaEquipo_TiposEquipos_Propiedades', $Param);
            $row = sqlsrv_fetch_array($SQL);

            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar la Propiedad.";
            } elseif (isset($row['Error'])) {
                $sw_error = 1;
                $msg_error = $row['Error'];
            }
        }

        // OK
        if ($sw_error == 0) {
            $TipoDoc = $_POST['TipoDoc'];
            header("Location:tipos_equipos.php?doc=$TipoDoc&a=" . base64_encode("OK_PRUpd") . "#$TipoDoc");
        }

    } catch (Exception $e) {
        $sw_error = 1;
        $msg_error = $e->getMessage();
    }

}

$SQL_TipoEquipo = Seleccionar("uvw_tbl_TarjetaEquipo_TiposEquipos", "*");
$SQL_Propiedades = Seleccionar("uvw_tbl_TarjetaEquipo_TiposEquipos_Propiedades", "*");
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include_once "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Parámetros Tipos de Equipos | <?php echo NOMBRE_PORTAL; ?></title>
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
                    <h2>Tipos de Equipos</h2>
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
                            <strong>Tipos de Equipos</strong>
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
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Tipos") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-1"><i class="fa fa-list"></i> Tipos</a>
								</li>
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Propiedades")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-2"><i class="fa fa-list"></i> Propiedades</a>
								</li>
							</ul>

							<div class="tab-content">
								
								<!-- Inicio, lista Tipos -->
								<div id="tab-1" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Tipos") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Tipos">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Tipos</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Tipos');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Tipo Equipo</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_TipoEquipo = sqlsrv_fetch_array($SQL_TipoEquipo)) {?>
															<tr>
																<td><?php echo $row_TipoEquipo['tipo_equipo']; ?></td>
																<td><?php echo $row_TipoEquipo['Comentarios']; ?></td>
																<td><?php echo isset($row_TipoEquipo['fecha_actualizacion']) ? date_format($row_TipoEquipo['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_TipoEquipo['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<span class="label <?php echo ($row_TipoEquipo['estado_tipo_equipo'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_TipoEquipo['estado_tipo_equipo'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_TipoEquipo['id_tipo_equipo']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_TipoEquipo['id_tipo_equipo']; ?>','Tipos');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_TipoEquipo['id_tipo_equipo']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_TipoEquipo['id_tipo_equipo']; ?>','Tipos');"><i class="fa fa-trash"></i> Eliminar</button>
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
								<!-- Fin, lista Tipos -->

								<!-- Inicio, lista Propiedades -->
								<div id="tab-2" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Propiedades")) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<div class="ibox" id="Propiedades">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Propiedades</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Propiedades');"><i class="fa fa-plus-circle"></i> Agregar nueva</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Propiedad</th>
																<th>Tipo Equipo</th>
																<th>Tipo Campo</th>
																<th>Tabla Vinculada</th>
																<th>Obligatorio</th>
																<th>Multiple</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Propiedades = sqlsrv_fetch_array($SQL_Propiedades)) {?>
															<tr>
																<td><?php echo $row_Propiedades['propiedad']; ?></td>
																<td><?php echo $row_Propiedades['tipo_equipo_padre'] ?? ""; ?></td>
																<td><?php echo $row_Propiedades['tipo_campo'] ?? ""; ?></td>
																<td><?php echo $row_Propiedades['tabla_vinculada'] ?? ""; ?></td>
																<td><?php echo ($row_Propiedades['obligatorio'] == "Y") ? "SI" : "NO"; ?></td>
																<td><?php echo ($row_Propiedades['multiple'] == "Y") ? "SI" : "NO"; ?></td>
																<td><?php echo isset($row_Propiedades['fecha_actualizacion']) ? date_format($row_Propiedades['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Propiedades['usuario_actualizacion'] ?? ""; ?></td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Propiedades['id_propiedad']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Propiedades['id_propiedad']; ?>','Propiedades');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Propiedades['id_propiedad']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Propiedades['id_propiedad']; ?>','Propiedades');"><i class="fa fa-trash"></i> Eliminar</button>
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
								<!-- Fin, lista Propiedades -->

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
		url: "md_tipos_equipos.php",
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
		url: "md_tipos_equipos.php",
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
				url: "tipos_equipos.php",
				data: { TipoDoc: doc, ID: id, Metodo: 3 },
				async: false,
				success: function(data){
					// console.log(data);
					location.href = `tipos_equipos.php?doc=${doc}&a=<?php echo base64_encode("OK_PRDel"); ?>`;
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