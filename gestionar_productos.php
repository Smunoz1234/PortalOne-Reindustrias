<?php require_once ("includes/conexion.php");
PermitirAcceso(209);

$Filtro = "";//Filtro
$sw_and = 0;


//Categorias de productos
$SQL_CatProductos = Seleccionar("uvw_tbl_CategoriasProductos", "ID_CategoriaProductos, NombreCategoriaProductos", "");

if (isset($_GET['Categoria']) && $_GET['Categoria'] != "") {
	$Filtro .= " and ID_CategoriaProductos='" . $_GET['Categoria'] . "'";
}

//Lista de productos
$SQL_Productos = Seleccionar("uvw_Sap_tbl_ListaProductos", "ItemCode, ItemName", "");

if (isset($_GET['Producto']) && $_GET['Producto'] != "") {
	$Filtro .= " and ItemCode='" . $_GET['Producto'] . "'";
}


//Fechas
if (isset($_GET['FechaInicial']) && $_GET['FechaInicial'] != "") {
	$FechaInicial = $_GET['FechaInicial'];
} else {
	//Restar 15 dias a la fecha actual
	$fecha = date('Y-m-d');
	$nuevafecha = strtotime('-' . ObtenerVariable("DiasRangoFechasGestionar") . ' day');
	$nuevafecha = date('Y-m-d', $nuevafecha);
	$FechaInicial = $nuevafecha;
}
if (isset($_GET['FechaFinal']) && $_GET['FechaFinal'] != "") {
	$FechaFinal = $_GET['FechaFinal'];
} else {
	$FechaFinal = date('Y-m-d');
}

if (isset($_GET['Categoria'])) {
	if (($_GET['Categoria']) != "") {
		$Filtro .= " and ID_CategoriaProductos='" . $_GET['Categoria'] . "'";
	}
}

$Cons = "Select * From uvw_tbl_Productos Where (Fecha Between '$FechaInicial' and '$FechaFinal') $Filtro Order by Fecha DESC";
//echo $Cons;
$SQL = sqlsrv_query($conexion, $Cons);


?>
<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include_once ("includes/cabecera.php"); ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Gestionar productos | <?php echo NOMBRE_PORTAL; ?></title>
	<!-- InstanceEndEditable -->
	<!-- InstanceBeginEditable name="head" -->
	<?php
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_UpdFile"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'Los archivos han sido cargados exitosamente.',
                icon: 'success'
            });
		});		
		</script>";
	}
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_File_delete"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'El registro ha sido eliminado exitosamente.',
                icon: 'success'
            });
		});		
		</script>";
	}
	?>
	<script type="text/javascript">
		$(document).ready(function () {//Cargar los almacenes dependiendo del proyecto
			$("#Cliente").change(function () {
				$.ajax({
					type: "POST",
					url: "ajx_cbo_sucursales_clientes_simple.php?CardCode=" + document.getElementById('Cliente').value,
					success: function (response) {
						$('#Sucursal').html(response).fadeIn();
					}
				});
			});
		});
	</script>
	<script>
		function EliminarRegistro(id) {
			if (id != "") {
				Swal.fire({
					title: "¿Estás seguro?",
					text: "Se eliminará la información de este registro. Este proceso no tiene reversión.",
					icon: "warning",
					showCancelButton: true,
					confirmButtonText: "Si, estoy seguro",
					cancelButtonText: "Cancelar"
				}).then((result) => {
					if (result.isConfirmed) {
						location.href = 'registro.php?P=18&id=' + id;
					}
				});
			}
		}
	</script>
	<!-- InstanceEndEditable -->
</head>

<body>

	<div id="wrapper">

		<?php include_once ("includes/menu.php"); ?>

		<div id="page-wrapper" class="gray-bg">
			<?php include_once ("includes/menu_superior.php"); ?>
			<!-- InstanceBeginEditable name="Contenido" -->
			<div class="row wrapper border-bottom white-bg page-heading">
				<div class="col-sm-8">
					<h2>Gestionar productos</h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">Gestión de archivos</a>
						</li>
						<li class="active">
							<strong>Gestionar productos</strong>
						</li>
					</ol>
				</div>
				<div class="col-sm-4">
					<div class="title-action">
						<a href="productos_add.php" class="btn btn-primary"><i class="fa fa-upload"></i> Cargar
							archivos</a>
					</div>
				</div>
				<?php  //echo $Cons; ?>
			</div>
			<div class="wrapper wrapper-content">
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<form action="gestionar_productos.php" method="get" id="formBuscar" class="form-horizontal">
								<div class="form-group">
									<label class="col-lg-1 control-label">Fechas</label>
									<div class="col-lg-3">
										<div class="input-daterange input-group" id="datepicker">
											<input name="FechaInicial" type="text" class="input-sm form-control"
												id="FechaInicial" placeholder="Fecha inicial"
												value="<?php echo $FechaInicial; ?>" />
											<span class="input-group-addon">hasta</span>
											<input name="FechaFinal" type="text" class="input-sm form-control"
												id="FechaFinal" placeholder="Fecha final"
												value="<?php echo $FechaFinal; ?>" />
										</div>
									</div>
									<label class="col-lg-1 control-label">Producto</label>
									<div class="col-lg-3">
										<select name="Producto" class="form-control m-b chosen-select" id="Producto">
											<option value="">(Todos)</option>
											<?php while ($row_Productos = sqlsrv_fetch_array($SQL_Productos)) { ?>
												<option value="<?php echo $row_Productos['ItemCode']; ?>" <?php if ((isset($_GET['Producto'])) && (strcmp($row_Productos['ItemCode'], $_GET['Producto']) == 0)) {
													  echo "selected=\"selected\"";
												  } ?>>
													<?php echo $row_Productos['ItemName']; ?></option>
											<?php } ?>
										</select>
									</div>
									<label class="col-lg-1 control-label">Categoría</label>
									<div class="col-lg-2">
										<select name="Categoria" class="form-control m-b" id="Categoria">
											<option value="">(Todos)</option>
											<?php while ($row_CatProductos = sqlsrv_fetch_array($SQL_CatProductos)) { ?>
												<option value="<?php echo $row_CatProductos['ID_CategoriaProductos']; ?>"
													<?php if (isset($_GET['Categoria']) && (strcmp($row_CatProductos['ID_CategoriaProductos'], $_GET['Categoria']) == 0)) {
														echo "selected=\"selected\"";
													} ?>>
													<?php echo $row_CatProductos['NombreCategoriaProductos']; ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="col-lg-1">
										<button type="submit" class="btn btn-outline btn-success"><i
												class="fa fa-search"></i> Buscar</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
				<br>
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<div class="table-responsive">
								<table class="table table-striped table-bordered table-hover dataTables-example">
									<thead>
										<tr>
											<th>Código</th>
											<th>Nombre producto</th>
											<th>Fecha del archivo</th>
											<th>Categoría</th>
											<th>Nombre archivo</th>
											<th>Fecha de cargue</th>
											<th>Usuario</th>
											<th>Acciones</th>
										</tr>
									</thead>
									<tbody>
										<?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
											<tr class="gradeX">
												<td><?php echo $row['ItemCode']; ?></td>
												<td><?php echo $row['ItemName']; ?></td>
												<td><?php if ($row['Fecha'] != "") {
													echo $row['Fecha']->format('Y-m-d');
												} else { ?>
														<p class="text-muted">--</p><?php } ?>
												</td>
												<td><?php echo $row['NombreCategoriaProductos']; ?></td>
												<td><?php echo FormatoNombreArchivo($row['Archivo']); ?></td>
												<td><?php echo $row['FechaRegistro']->format('Y-m-d H:s'); ?></td>
												<td><?php echo $row['NombreUsuario']; ?></td>
												<td><?php if ($row['Archivo'] != "") { ?><a
															href="productdownload.php?file=<?php echo base64_encode($row['ID_Producto']); ?>"
															target="_blank" class="btn btn-success btn-xs"><i
																class="fa fa-download"></i>
															Descargar</a><?php if (PermitirFuncion(205) || ConsultarUsuarioCargueProd($row['ID_Producto'])) { ?>
															<a href="#"
																onClick="EliminarRegistro(<?php echo $row['ID_Producto']; ?>);"
																class="btn btn-danger btn-xs"><i class="fa fa-eraser"></i>
																Eliminar</a><?php }
												} else { ?>
														<p class="text-muted">Ninguno</p><?php } ?>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- InstanceEndEditable -->
			<?php include_once ("includes/footer.php"); ?>

		</div>
	</div>
	<?php include_once ("includes/pie.php"); ?>
	<!-- InstanceBeginEditable name="EditRegion4" -->
	<script>
		$(document).ready(function () {
			$('#FechaInicial').datepicker({
				todayBtn: "linked",
				keyboardNavigation: false,
				forceParse: false,
				calendarWeeks: true,
				autoclose: true,
				format: 'yyyy-mm-dd'
			});
			$('#FechaFinal').datepicker({
				todayBtn: "linked",
				keyboardNavigation: false,
				forceParse: false,
				calendarWeeks: true,
				autoclose: true,
				format: 'yyyy-mm-dd'
			});

			$('.chosen-select').chosen({ width: "100%" });

			$('.dataTables-example').DataTable({
				pageLength: 25,
				responsive: true,
				dom: '<"html5buttons"B>lTfgitp',
				language: {
					"decimal": "",
					"emptyTable": "No se encontraron resultados.",
					"info": "Mostrando _START_ - _END_ de _TOTAL_ registros",
					"infoEmpty": "Mostrando 0 - 0 de 0 registros",
					"infoFiltered": "(filtrando de _MAX_ registros)",
					"infoPostFix": "",
					"thousands": ",",
					"lengthMenu": "Mostrar _MENU_ registros",
					"loadingRecords": "Cargando...",
					"processing": "Procesando...",
					"search": "Filtrar:",
					"zeroRecords": "Ningún registro encontrado",
					"paginate": {
						"first": "Primero",
						"last": "Último",
						"next": "Siguiente",
						"previous": "Anterior"
					},
					"aria": {
						"sortAscending": ": Activar para ordenar la columna ascendente",
						"sortDescending": ": Activar para ordenar la columna descendente"
					}
				},
				buttons: []

			});

		});

	</script>
	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>
<?php sqlsrv_close($conexion); ?>