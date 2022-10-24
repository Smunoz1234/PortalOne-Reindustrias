<?php
require_once "includes/conexion.php";
PermitirAcceso(216);

$sw_error = 0;

// SMM, 21/07/2022
if (isset($_POST['Metodo']) && ($_POST['Metodo'] == 3)) {
    try {
        $Param = array(
            $_POST['Metodo'], // 3 - Eliminar
            isset($_POST['ID']) ? $_POST['ID'] : "NULL",
        );

        if ($_POST['TipoDoc'] == "Categoria") {
            $SQL = EjecutarSP('sp_tbl_ConsultasSAPB1_Categorias', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la categoría.";
            }
        } elseif ($_POST['TipoDoc'] == "Consulta") {
            $SQL = EjecutarSP('sp_tbl_ConsultasSAPB1_Consultas', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la consulta.";
            }
        } elseif ($_POST['TipoDoc'] == "Entrada") {
            $SQL = EjecutarSP('sp_tbl_ConsultasSAPB1_Consultas', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo eliminar la consulta.";
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

        if ($_POST['TipoDoc'] == "Categoria") {
            $FechaHora = "'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'";
            $Usuario = "'" . $_SESSION['CodUser'] . "'";

            $Perfiles = implode(";", $_POST['Perfiles']);
            $Perfiles = count($_POST['Perfiles']) > 0 ? "'$Perfiles'" : "''";

            $ID = (isset($_POST['ID_Actual']) && ($_POST['ID_Actual'] != "")) ? $_POST['ID_Actual'] : "NULL";

            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $ID,
                "'" . $_POST['ID_CategoriaPadre'] . "'",
                "'" . $_POST['NombreCategoria'] . "'",
                $Perfiles,
                "'" . $_POST['Comentarios'] . "'",
                "'" . $_POST['Estado'] . "'",
                $Usuario, // @id_usuario_actualizacion
                $FechaHora, // @fecha_actualizacion
                $FechaHora, // @hora_actualizacion
                ($_POST['Metodo'] == 1) ? $Usuario : "NULL",
                ($_POST['Metodo'] == 1) ? $FechaHora : "NULL",
                ($_POST['Metodo'] == 1) ? $FechaHora : "NULL",
            );

            $SQL = EjecutarSP('sp_tbl_ConsultasSAPB1_Categorias', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar la nueva categoría";
            }
        } elseif ($_POST['TipoDoc'] == "Motivos") {
            $FechaHora = "'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'";
            $Usuario = "'" . $_SESSION['CodUser'] . "'";

            $Perfiles = implode(";", $_POST['Perfiles']);
            $Perfiles = count($_POST['Perfiles']) > 0 ? "'$Perfiles'" : "''";

            $IdInterno = (isset($_POST['ID_Actual']) && ($_POST['ID_Actual'] != "")) ? $_POST['ID_Actual'] : "NULL";

            $Param = array(
                $_POST['Metodo'] ?? 1, // 1 - Crear, 2 - Actualizar
                $IdInterno,
                "'" . $_POST['IdMotivoAutorizacion'] . "'",
                "'" . $_POST['MotivoAutorizacion'] . "'",
                "'" . $_POST['IdTipoDocumento'] . "'",
                "'" . $_POST['IdModeloAutorizacionSAPB1'] . "'",
                "'" . $_POST['IdUsuarioAutorizacion'] . "'", // SMM, 26/08/2022
                "'" . $_POST['PassUsuarioAutorizacion'] . "'", // Se quito la función md5(...)
                "'" . $_POST['Comentarios'] . "'",
                "'" . $_POST['Estado'] . "'",
                $Usuario, // @id_usuario_actualizacion
                $FechaHora, // @fecha_actualizacion
                $FechaHora, // @hora_actualizacion
                ($_POST['Metodo'] == 1) ? $Usuario : "NULL",
                ($_POST['Metodo'] == 1) ? $FechaHora : "NULL",
                ($_POST['Metodo'] == 1) ? $FechaHora : "NULL",
            );
            $SQL = EjecutarSP('sp_tbl_Autorizaciones_Motivos', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar los datos";
            }
        }

        if ($sw_error == 0) {
            $TipoDoc = $_POST['TipoDoc'];
            header("Location:parametros_consultas_sap.php?doc=$TipoDoc&a=" . base64_encode("OK_PRUpd") . "#$TipoDoc");
        }

    } catch (Exception $e) {
        $sw_error = 1;
        $msg_error = $e->getMessage();
    }

}

$SQL_Categorias = Seleccionar("uvw_tbl_ConsultasSAPB1_Categorias", "*");

// SMM, 21/07/2022
$SQL_Motivos = Seleccionar("uvw_tbl_Autorizaciones_Motivos", "*");

// Perfiles Usuarios, SMM 26/07/2022
$SQL_Perfiles = Seleccionar('uvw_tbl_PerfilesUsuarios', '*');
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include_once "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Parámetros Consultas SAP B1 | <?php echo NOMBRE_PORTAL; ?></title>
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
                    <h2>Parámetros Consultas SAP B1</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index1.php">Inicio</a>
                        </li>
						<li>
                            <a href="#">Administración</a>
                        </li>
						<li>
                            <a href="#">Parámetros del sistema</a>
                        </li>
                        <li class="active">
                            <strong>Parámetros Consultas SAP B1</strong>
                        </li>
                    </ol>
                </div>
            </div>
            <?php //echo $Cons;?>
         <div class="wrapper wrapper-content">
			 <div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content" id="ContenidoModal">

					</div>
				</div>
			</div>
			 <div class="row">
			 	<div class="col-lg-12">
					<div class="ibox-content">
						<?php include "includes/spinner.php";?>
						 <div class="tabs-container">
							<ul class="nav nav-tabs">
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Categoria") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-1"><i class="fa fa-list"></i> Lista de Categorías</a>
								</li>
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Consulta")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-2"><i class="fa fa-list"></i> Lista de Consultas SAP B1</a>
								</li>
								<li class="<?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Entrada")) ? "active" : ""; ?>">
									<a data-toggle="tab" href="#tab-3"><i class="fa fa-list"></i> Lista de Parámetros</a>
								</li>
							</ul>
							<div class="tab-content">
								<!-- Inicio, lista motivo autorización -->
								<div id="tab-1" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Categoria") || !isset($_GET['doc'])) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<!-- Inicio, ibox motivos -->
										<div class="ibox" id="Categoria">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de Categorías</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" id="NewMotivo" onClick="CrearCampo('Categoria');"><i class="fa fa-plus-circle"></i> Agregar nuevo</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Categoría Padre</th>
																<th>Nombre Categoría</th>
																<th>Perfiles</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Categoria = sqlsrv_fetch_array($SQL_Categorias)) {?>
															<tr>
																<td><?php echo $row_Categoria['CategoriaPadre']; ?></td>
																<td><?php echo $row_Categoria['NombreCategoria']; ?></td>

																<td>
																	<?php sqlsrv_fetch($SQL_Perfiles, SQLSRV_SCROLL_ABSOLUTE, -1);?>
																	<?php $ids_perfiles = explode(";", $row_Categoria['Perfiles']);?>

																	<?php $cadenaPerfiles = "";?>
																	<?php while ($row_Perfil = sqlsrv_fetch_array($SQL_Perfiles)) {?>
																		<?php if (in_array($row_Perfil['ID_PerfilUsuario'], $ids_perfiles)) {?>
																			<span class="label label-secondary"></span>
																			<?php $cadenaPerfiles .= $row_Perfil['PerfilUsuario'] . "; ";?>
																		<?php }?>
																	<?php }?>
																	<?php echo ($cadenaPerfiles == "") ? "(Todos)" : $cadenaPerfiles; ?>
																</td>

																<td><?php echo $row_Categoria['Comentarios']; ?></td>

																<td><?php echo isset($row_Categoria['fecha_actualizacion']) ? date_format($row_Categoria['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Categoria['usuario_actualizacion']; ?></td>
																<td>
																	<span class="label <?php echo ($row_Categoria['Estado'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Categoria['Estado'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Categoria['ID']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Categoria['ID']; ?>','Categoria');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Categoria['ID']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Categoria['ID']; ?>','Categoria');"><i class="fa fa-trash"></i> Eliminar</button>
																</td>
															</tr>
															 <?php }?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
										<!-- Fin, ibox motivos -->
									</form>
								</div>
								<!-- Fin, lista motivo autorización -->

								<!-- Inicio, lista motivo autorización -->
								<div id="tab-2" class="tab-pane <?php echo (isset($_GET['doc']) && ($_GET['doc'] == "Motivos")) ? "active" : ""; ?>">
									<form class="form-horizontal">
										<!-- Inicio, ibox motivos -->
										<div class="ibox" id="Motivos">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista motivos de autorizaciones</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" id="NewMotivo" onClick="CrearCampo('Motivos');"><i class="fa fa-plus-circle"></i> Agregar nuevo</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<!-- th>ID Tipo Documento</th -->
																<th>Tipo Documento</th>
																<th>Modelo autorización SAP B1</th>
																<th>Id Motivo Autorizacion</th>
																<th>Motivo Autorizacion</th>

																<th>Usuario Autorización SAP B1</th> <!-- SMM, 26/08/2022 -->

																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Motivo = sqlsrv_fetch_array($SQL_Motivos)) {?>
															<tr>
																<!-- td><?php echo $row_Motivo['IdTipoDocumento']; ?></td -->
																<td><?php echo $row_Motivo['TipoDocumento']; ?></td>
																<td><?php echo $row_Motivo['ModeloAutorizacion']; ?></td>
																<td><?php echo $row_Motivo['IdMotivoAutorizacion']; ?></td>
																<td><?php echo $row_Motivo['MotivoAutorizacion']; ?></td>

																<td>
																	<?php echo $row_Motivo['UsuarioAutorizacionSAPB1']; ?>
																</td>

																<td><?php echo $row_Motivo['Comentarios']; ?></td>
																<td><?php echo isset($row_Motivo['fecha_actualizacion']) ? date_format($row_Motivo['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
																<td><?php echo $row_Motivo['usuario_actualizacion']; ?></td>
																<td>
																	<span class="label <?php echo ($row_Motivo['Estado'] == "Y") ? "label-info" : "label-danger"; ?>">
																		<?php echo ($row_Motivo['Estado'] == "Y") ? "Activo" : "Inactivo"; ?>
																	</span>
																</td>
																<td>
																	<button type="button" id="btnEdit<?php echo $row_Motivo['IdInterno']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Motivo['IdInterno']; ?>','Motivos');"><i class="fa fa-pencil"></i> Editar</button>
																	<button type="button" id="btnDelete<?php echo $row_Motivo['IdInterno']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Motivo['IdInterno']; ?>','Motivos');"><i class="fa fa-trash"></i> Eliminar</button>
																</td>
															</tr>
															 <?php }?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
										<!-- Fin, ibox motivos -->
									</form>
								</div>
								<!-- Fin, lista motivo autorización -->
							</div>
						 </div>
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
		url: "md_parametros_consultas_sap.php",
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
		url: "md_autorizaciones_documentos.php",
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
				url: "parametros_consultas_sap.php",
				data: { TipoDoc: doc, ID: id, Metodo: 3 },
				async: false,
				success: function(data){
					// console.log(data);
					location.href = "parametros_consultas_sap.php?a=<?php echo base64_encode("OK_PRDel"); ?>";
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

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion);?>