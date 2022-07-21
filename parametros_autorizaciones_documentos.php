<?php
require_once "includes/conexion.php";
PermitirAcceso(216);

$sw_error = 0;
$dir_new = CrearObtenerDirAnx("formularios/monitoreos_temperaturas/planos");
//Insertar datos
if (isset($_POST['frmType']) && ($_POST['frmType'] != "")) {
    try {

        if ($_POST['TipoDoc'] = "Motivos") {
            $Param = array(
                "'" . $_POST['IdMotivoAutorizacion'] . "'",
                "'" . $_POST['MotivoAutorizacion'] . "'",
                "'" . $_POST['IdTipoDocumento'] . "'",
                "'" . $_POST['Comentarios'] . "'",
                "'" . $_POST['Estado'] . "'",
                "'" . $_POST['Metodo'] . "'",
                "'" . $_SESSION['CodUser'] . "'",
                "'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'",
                "'" . FormatoFecha(date('Y-m-d'), date('H:i:s')) . "'",
                1,
            );
            $SQL = EjecutarSP('sp_tbl_Autorizaciones_Motivos', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar los datos";
            }
        } elseif ($_POST['TipoDoc'] == "Productos") {
            $Param = array(
                "'" . $_POST['TipoDoc'] . "'",
                "'" . $_POST['CodigoProducto'] . "'",
                "'" . $_POST['ID_Actual'] . "'",
                "'" . $_POST['NombreProducto'] . "'",
                "'" . $_POST['ComentariosProducto'] . "'",
                "'" . $_POST['EstadoProducto'] . "'",
                "'" . $_POST['Metodo'] . "'",
                "'" . $_SESSION['CodUser'] . "'",
            );
            $SQL = EjecutarSP('sp_tbl_FrmPuerto', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar los datos";
            }
        } elseif ($_POST['TipoDoc'] == "Transportes") {
            //Transportes
            $Param = array(
                "'" . $_POST['TipoDoc'] . "'",
                "'" . $_POST['CodigoTransporte'] . "'",
                "'" . $_POST['ID_Actual'] . "'",
                "'" . $_POST['NombreTransporte'] . "'",
                "'" . $_POST['ComentariosTransporte'] . "'",
                "'" . $_POST['EstadoTransporte'] . "'",
                "'" . $_POST['Metodo'] . "'",
                "'" . $_SESSION['CodUser'] . "'",
                "'" . $_POST['RegistroCap'] . "'",
            );
            $SQL = EjecutarSP('sp_tbl_FrmPuerto', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar los datos";
            }
        } else {
            $Param = array(
                "'" . $_POST['TipoDoc'] . "'",
                "'" . $_POST['Codigo'] . "'",
                "'" . $_POST['ID_Actual'] . "'",
                "'" . $_POST['Nombre'] . "'",
                "'" . $_POST['Comentarios'] . "'",
                "'" . $_POST['Estado'] . "'",
                "'" . $_POST['Metodo'] . "'",
                "'" . $_SESSION['CodUser'] . "'",
            );
            $SQL = EjecutarSP('sp_tbl_FrmPuerto', $Param);
            if (!$SQL) {
                $sw_error = 1;
                $msg_error = "No se pudo insertar los datos";
            }
        }

        if ($sw_error == 0) {
            header('Location:parametros_autorizaciones_documentos.php?a=' . base64_encode("OK_PRUpd") . '#' . $_POST['TipoDoc']);
        }
    } catch (Exception $e) {
        $sw_error = 1;
        $msg_error = $e->getMessage();
    }

}

// SMM, 21/07/2022
$SQL_Motivo = Seleccionar("tbl_Autorizaciones_Motivos", "*");

$SQL_Productos = Seleccionar("tbl_ProductosPuerto", "*");

$SQL_Transporte = Seleccionar("tbl_TransportesPuerto", "*");

$SQL_TipoInfectacion = Seleccionar("tbl_TipoInfectacionProductos", "*");

$SQL_GradoInfectacion = Seleccionar("tbl_GradoInfectacion", "*");

$SQL_Muelles = Seleccionar("tbl_MuellesPuerto", "*");

$SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', 'CodigoCliente, NombreCliente', '', 'NombreCliente');

?>
<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include_once "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Parámetros autorizaciones documentos | <?php echo NOMBRE_PORTAL; ?></title>
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
                    <h2>Parámetros autorizaciones documentos</h2>
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
                            <strong>Parámetros autorizaciones documentos</strong>
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
								<li class="active"><a data-toggle="tab" href="#tab-1"><i class="fa fa-list"></i> Lista de autorización</a></li>
								<li><a data-toggle="tab" href="#tab-2"><i class="fa fa-list"></i> Lista motivo autorización</a></li>
							</ul>
							<div class="tab-content">
								<!-- Inicio, Lista de autorización -->
								<div id="tab-1" class="tab-pane active">
									<form class="form-horizontal">
										<div class="ibox" id="TipoInfectacion">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Tipo infestación productos</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" id="NewAlgo" onClick="CrearCampo('TipoInfectacion');"><i class="fa fa-plus-circle"></i> Agregar nuevo</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Código infestación</th>
																<th>Nombre infestación</th>
																<th>Comentarios</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
														  <?php
while ($row_TipoInfectacion = sqlsrv_fetch_array($SQL_TipoInfectacion)) {
    ?>
															<tr>
																 <td><?php echo $row_TipoInfectacion['id_tipo_infectacion_producto']; ?></td>
																 <td><?php echo $row_TipoInfectacion['tipo_infectacion_producto']; ?></td>
																 <td><?php echo $row_TipoInfectacion['comentarios']; ?></td>
																 <td><?php if ($row_TipoInfectacion['estado'] == 'Y') {echo "ACTIVO";} else {echo "INACTIVO";}?></td>
																 <td>
																	<button type="button" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_TipoInfectacion['id_tipo_infectacion_producto']; ?>','TipoInfectacion');"><i class="fa fa-pencil"></i> Editar</button>
																 </td>
															</tr>
														 <?php }?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
										<div class="ibox" id="GradoInfectacion">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Grado infestación productos</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" id="NewAlgo" onClick="CrearCampo('GradoInfectacion');"><i class="fa fa-plus-circle"></i> Agregar nuevo</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Código grado infestación</th>
																<th>Nombre grado infestación</th>
																<th>Comentarios</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
														  <?php
while ($row_GradoInfectacion = sqlsrv_fetch_array($SQL_GradoInfectacion)) {
    ?>
															<tr>
																 <td><?php echo $row_GradoInfectacion['id_grado_infectacion']; ?></td>
																 <td><?php echo $row_GradoInfectacion['grado_infectacion']; ?></td>
																 <td><?php echo $row_GradoInfectacion['comentarios']; ?></td>
																 <td><?php if ($row_GradoInfectacion['estado'] == 'Y') {echo "ACTIVO";} else {echo "INACTIVO";}?></td>
																 <td>
																	<button type="button" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_GradoInfectacion['id_grado_infectacion']; ?>','GradoInfectacion');"><i class="fa fa-pencil"></i> Editar</button>
																 </td>
															</tr>
														 <?php }?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
										<div class="ibox" id="Muelles">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Muelles</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" onClick="CrearCampo('Muelles');"><i class="fa fa-plus-circle"></i> Agregar nuevo</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Código muelle</th>
																<th>Nombre muelle</th>
																<th>Comentarios</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
														  <?php
while ($row_Muelles = sqlsrv_fetch_array($SQL_Muelles)) {
    ?>
															<tr>
																 <td><?php echo $row_Muelles['id_muelle_puerto']; ?></td>
																 <td><?php echo $row_Muelles['muelle_puerto']; ?></td>
																 <td><?php echo $row_Muelles['comentarios']; ?></td>
																 <td><?php if ($row_Muelles['estado'] == 'Y') {echo "ACTIVO";} else {echo "INACTIVO";}?></td>
																 <td>
																	<button type="button" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Muelles['id_muelle_puerto']; ?>','Muelles');"><i class="fa fa-pencil"></i> Editar</button>
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
								<!-- Fin, lista de autorización -->
								<!-- Inicio, lista motivo autorización -->
								<div id="tab-2" class="tab-pane active">
									<form class="form-horizontal">
										<div class="ibox" id="Motivos">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Lista de autorizaciones</h5>
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
																<th>ID Tipo Documento</th>
																<th>Tipo Documento</th>
																<th>ID Formato</th>
																<th>Id Motivo Autorizacion (SAP B1)</th>
																<th>Motivo Autorizacion</th>
																<th>Comentarios</th>
																<th>Fecha Actualizacion</th>
																<th>Usuario Actualizacion</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
															 <?php while ($row_Motivo = sqlsrv_fetch_array($SQL_Motivo)) {?>
															<tr>
																<td><?php echo $row_Motivo['IdTipoDocumento']; ?></td>
																<td><?php echo $row_Motivo['TipoDocumento']; ?></td>
																<td><?php echo $row_Motivo['IdFormato']; ?></td>
																<td><?php echo $row_Motivo['IdMotivoAutorizacion']; ?></td>
																<td><?php echo $row_Motivo['MotivoAutorizacion']; ?></td>
																<td><?php echo $row_Motivo['Comentarios']; ?></td>
																<td><?php echo $row_Motivo['fecha_actualizacion']; ?></td>
																<td><?php echo $row_Motivo['id_usuario_actualizacion']; ?></td>
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
										<div class="ibox" id="Productos">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Productos</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" id="NewAlgo" onClick="CrearCampo('Productos');"><i class="fa fa-plus-circle"></i> Agregar nuevo</button>
													</div>
												</div>
												<div class="table-responsive">
													<table class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Código producto</th>
																<th>Nombre producto</th>
																<th>Comentarios</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
														  <?php
while ($row_Productos = sqlsrv_fetch_array($SQL_Productos)) {
    ?>
															<tr>
																 <td><?php echo $row_Productos['id_producto_puerto']; ?></td>
																 <td><?php echo $row_Productos['producto_puerto']; ?></td>
																 <td><?php echo $row_Productos['comentarios']; ?></td>
																 <td><?php if ($row_Productos['estado'] == 'Y') {echo "ACTIVO";} else {echo "INACTIVO";}?></td>
																 <td>
																	<button type="button" id="btnEditProd<?php echo $row_Productos['id_producto_puerto']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Productos['id_producto_puerto']; ?>','Productos');"><i class="fa fa-pencil"></i> Editar</button>
																 </td>
															</tr>
														 <?php }?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
										<div class="ibox" id="Transportes">
											<div class="ibox-title bg-success">
												<h5 class="collapse-link"><i class="fa fa-list"></i> Motonave</h5>
												 <a class="collapse-link pull-right">
													<i class="fa fa-chevron-up"></i>
												</a>
											</div>
											<div class="ibox-content">
												<div class="row m-b-md">
													<div class="col-lg-12">
														<button class="btn btn-primary pull-right" type="button" id="NewAlgo" onClick="CrearCampo('Transportes');"><i class="fa fa-plus-circle"></i> Agregar nuevo</button>
													</div>
												</div>
												<div class="table-responsive">
													<table width="100%" class="table table-striped table-bordered table-hover dataTables-example">
														<thead>
															<tr>
																<th>Código motonave</th>
																<th>Nombre motonave</th>
																<th>REG (Registro capitanía)</th>
																<th>Comentarios</th>
																<th>Estado</th>
																<th>Acciones</th>
															</tr>
														</thead>
														<tbody>
														  <?php
while ($row_Transporte = sqlsrv_fetch_array($SQL_Transporte)) {?>
															<tr>
																 <td><?php echo $row_Transporte['id_transporte_puerto']; ?></td>
																 <td><?php echo $row_Transporte['transporte_puerto']; ?></td>
																 <td><?php echo $row_Transporte['registro_capitania']; ?></td>
																 <td><?php echo $row_Transporte['comentarios']; ?></td>
																 <td><?php if ($row_Transporte['estado'] == 'Y') {echo "ACTIVO";} else {echo "INACTIVO";}?></td>
																 <td>
																	<button type="button" id="btnEditTrans<?php echo $row_Transporte['id_transporte_puerto']; ?>" class="btn btn-success btn-xs" onClick="EditarCampo('<?php echo $row_Transporte['id_transporte_puerto']; ?>','Transportes');"><i class="fa fa-pencil"></i> Editar</button>
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
		url: "md_autorizaciones_documentos.php",
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
		url: "md_frm_param_personalizados.php",
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
</script>
<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion);?>