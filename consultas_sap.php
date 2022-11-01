<?php
require_once "includes/conexion.php";

$id = base64_decode($_GET['id']);
// echo $id;

// Consulta
$SQL = Seleccionar("uvw_tbl_ConsultasSAPB1_Consultas", "*", "ID = '$id'");
$row = sqlsrv_fetch_array($SQL);

// Entradas
$SQL_Entradas = Seleccionar("tbl_ConsultasSAPB1_Entradas", "*", "ID_Consulta = '$id'");

// DenegarAcceso();
// $Num_Campos = sqlsrv_num_rows($SQL_Campos);

if (isset($_GET['type'])) {
    $ProcedimientoConsulta = $row['ProcedimientoConsulta'];

    $SQL_ProcedimientoEntradas = Seleccionar("tbl_ConsultasSAPB1_Entradas", "*", "ID_Consulta = '$id'");

    $ProcedimientoEntradas = array();
    while ($row_ProcedimientoEntrada = sqlsrv_fetch_array($SQL_ProcedimientoEntradas)) {
        $ParametroEntrada = $row_ProcedimientoEntrada['ParametroEntrada'];
        $ParametroEntrada = "'" . $_GET[$ParametroEntrada] . "'";

        array_push($ProcedimientoEntradas, $ParametroEntrada);
    }

    $SQL_TablaConsulta = EjecutarSP($ProcedimientoConsulta, $ProcedimientoEntradas);

    $Consultas = array();
    while ($row_Consulta = sqlsrv_fetch_array($SQL_TablaConsulta)) {
        array_push($Consultas, $row_Consulta);
    }

    $TitulosConsulta = array();
    foreach ($Consultas[0] as $key => $value) {
        (is_numeric($key)) ? null : array_push($TitulosConsulta, $key);
    }
}
?>

<!DOCTYPE html>
<html>
<!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include_once "includes/cabecera.php";?>
	<!-- InstanceBeginEditable name="doctitle" -->

	<title>
		<?php echo $row['EtiquetaConsulta']; ?>
	</title>
	<!-- InstanceEndEditable -->

	<style>
		.form-group {
			margin-left: 0 !important;
			margin-right: 0 !important;
		}
	</style>

	<!-- InstanceBeginEditable name="head" -->
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
					<h2><?php echo $row['EtiquetaConsulta']; ?></h2>

					<ol class="breadcrumb">
						<li>
							<a href="#">Inicio</a>
						</li>
						<li>
							<a href="#"><?php echo $row['Categoria']; ?></a>
						</li>
						<li class="active">
							<strong><?php echo $row['EtiquetaConsulta']; ?></strong>
						</li>
					</ol>
				</div> <!-- col-sm-8 -->
			</div><!-- page-heading -->

			<div class="wrapper wrapper-content">
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox-content">
							<?php include "includes/spinner.php";?>

							<form action="consultas_sap.php" method="get" id="formInforme" class="form-horizontal">
								<div class="form-group">
									<label class="col-lg-12"><h3 class="bg-success p-xs b-r-sm"><i class="fa fa-check-square-o"></i> Críterios de selección</h3></label>
								</div>

								<?php $filas = 0;?>
								<?php while ($row_Entrada = sqlsrv_fetch_array($SQL_Entradas)) {?>
									<?php if ($filas == 0) {echo '<div class="form-group">';}?>
									<?php $filas++;?>

									<?php if ($row_Entrada['TipoCampo'] == "Texto") {?>
										<div class="col-lg-4">
											<label class="control-label"><?php echo $row_Entrada['EtiquetaEntrada']; ?> <?php if ($row_Entrada['Obligatorio'] == "Y") {?><span class="text-danger">*</span><?php }?></label>

											<input name="<?php echo $row_Entrada['ParametroEntrada']; ?>" id="<?php echo $row_Entrada['ParametroEntrada']; ?>" type="text" class="form-control" <?php if ($row_Entrada['Obligatorio'] == "Y") {?>required="required"<?php }?>>
										</div>
									<?php } elseif ($row_Entrada['TipoCampo'] == "Comentario") {?>
										<div class="col-lg-4">
											<label class="control-label"><?php echo $row_Entrada['EtiquetaEntrada']; ?> <?php if ($row_Entrada['Obligatorio'] == "Y") {?><span class="text-danger">*</span><?php }?></label>

											<textarea class="form-control"  type="text" rows="5" name="<?php echo $row_Entrada['ParametroEntrada']; ?>" id="<?php echo $row_Entrada['ParametroEntrada']; ?>" <?php if ($row_Entrada['Obligatorio'] == "Y") {?>required="required"<?php }?>></textarea>
										</div>
									<?php } elseif ($row_Entrada['TipoCampo'] == "Fecha") {?>
										<div class="col-lg-4 input-group date">
											<label class="control-label"><?php echo $row_Entrada['EtiquetaEntrada']; ?> <?php if ($row_Entrada['Obligatorio'] == "Y") {?><span class="text-danger">*</span><?php }?></label>

											<div class="input-group date">
												<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input type="text" class="form-control" id="<?php echo $row_Entrada['ParametroEntrada']; ?>" name="<?php echo $row_Entrada['ParametroEntrada']; ?>" <?php if ($row_Entrada['Obligatorio'] == "Y") {?>required="required"<?php }?> value="<?php echo isset($_GET[$row_Entrada['ParametroEntrada']]) ? $_GET[$row_Entrada['ParametroEntrada']] : date('Y-m-d'); ?>">
											</div>
										</div>
									<?php } elseif ($row_Entrada['TipoCampo'] == "Cliente") {?>
										<div class="col-lg-4">
											<label class="control-label"><?php echo $row_Entrada['EtiquetaEntrada']; ?> <?php if ($row_Entrada['Obligatorio'] == "Y") {?><span class="text-danger">*</span><?php }?></label>

											<input type="hidden" name="<?php echo $row_Entrada['ParametroEntrada']; ?>" id="<?php echo $row_Entrada['ParametroEntrada']; ?>" value="">
											<input type="text" class="form-control" name="srcNombreCliente" id="srcNombreCliente" placeholder="<?php if ($row_Entrada['Obligatorio'] == "Y") {?>Digite para buscar...<?php } else {?>Digite para buscar... (Para TODOS, dejar vacio)<?php }?>" <?php if ($row_Entrada['Obligatorio'] == "Y") {?>required="required"<?php }?>>
										</div>
									<?php } elseif ($row_Entrada['TipoCampo'] == "Sucursal") {?>
										<?php $row_Entrada['Multiple'] = 0;?> <!-- Faltante -->

										<div class="col-lg-4">
											<label class="control-label"><?php echo $row_Entrada['EtiquetaEntrada']; ?> <?php if ($row_Entrada['Obligatorio'] == "Y") {?><span class="text-danger">*</span><?php }?></label>

											<select class="form-control select2" id="<?php echo $row_Entrada['ParametroEntrada']; ?>" name="<?php echo $row_Entrada['ParametroEntrada'] . (($row_Entrada['Multiple'] == 1) ? "[]" : ""); ?>" <?php if ($row_Entrada['Multiple'] == 1) {?>multiple="multiple" data-placeholder="Seleccione..."<?php }?> <?php if ($row_Entrada['Obligatorio'] == "Y") {?>required="required"<?php }?>>
												<?php if ($row_Entrada['Multiple'] == 0) {?><option value="">(Todos)</option><?php }?>
											</select>
										</div>
									<?php } elseif ($row_Entrada['TipoCampo'] == "Seleccion") {?>
										<div class="col-lg-4">
											<label class="control-label"><?php echo $row_Entrada['EtiquetaEntrada']; ?> <?php if ($row_Entrada['Obligatorio'] == "Y") {?><span class="text-danger">*</span><?php }?></label>

											<select class="form-control" id="<?php echo $row_Entrada['ParametroEntrada']; ?>" name="<?php echo $row_Entrada['ParametroEntrada']; ?>" <?php if ($row_Entrada['Obligatorio'] == "Y") {?>required="required"<?php }?>>
												<option value="" selected disabled>Seleccione...</option>
												<option value="Y">SI</option>
												<option value="N">NO</option>
											</select>
										</div>
									<?php } elseif ($row_Entrada['TipoCampo'] == "Lista") {?>
										<?php $Cmp_Lista = ($row_Entrada['EtiquetaList'] ?? "") . ", " . ($row_Entrada['ValorList'] ?? "");?>
										<?php $SQL_Lista = Seleccionar(($row_Entrada['VistaLista'] ?? ""), $Cmp_Lista, '');?>

										<div class="form-group">
											<div class="col-lg-4">
												<label class="control-label"><?php echo $row_Entrada['EtiquetaEntrada']; ?> <?php if ($row_Entrada['Obligatorio'] == "Y") {?><span class="text-danger">*</span><?php }?></label>

												<select class="form-control select2" <?php if ($row_Entrada['Multiple'] == 1) {?>data-placeholder="Seleccione..."<?php }?> id="<?php echo $row_Entrada['NombreCampo']; ?>" name="<?php if ($row_Entrada['Multiple'] == 1) {echo $row_Entrada['NombreCampo'] . "[]";} else {echo $row_Entrada['NombreCampo'];}?>" <?php if ($row_Entrada['Obligatorio'] == "Y") {?>required="required"<?php }?> <?php if ($row_Entrada['Multiple'] == 1) {?>multiple="multiple"<?php }?>>
													<?php if ($row_Entrada['Multiple'] == 0) {?>
														<option value="" selected disabled>Seleccione...</option>
													<?php }?>

													<?php while ($row_Lista = sqlsrv_fetch_array($SQL_Lista)) {?>
														<option value="<?php echo $row_Lista[$row_Entrada['ValorLista']]; ?>"><?php echo $row_Lista[$row_Entrada['EtiquetaLista']]; ?></option>
													<?php }?>
												</select>
											</div>
										</div>
									<?php } elseif ($row_Entrada['TipoCampo'] == "Usuario") {?>
										<div class="col-lg-4">
											<label class="control-label"><?php echo $row_Entrada['EtiquetaEntrada']; ?> <?php if ($row_Entrada['Obligatorio'] == "Y") {?><span class="text-danger">*</span><?php }?></label>

											<input name="<?php echo $row_Entrada['ParametroEntrada']; ?>" id="<?php echo $row_Entrada['ParametroEntrada']; ?>" type="text" class="form-control" value="<?php echo strtolower($_SESSION['User']); ?>" readonly>
										</div>
									<?php }?>

									<?php if ($filas >= 3) {?>
										</div>
									<?php $filas = 0;}?>

								<?php }?> <!-- while -->

								<div class="form-group">
									<div class="col-lg-4">
										<br>
										<button type="submit" name="submit" id="submit" class="btn btn-success btn-outline pull-right"><i class="fa fa-search"></i> Buscar</button>
									</div>
								</div>

								<input type="hidden" name="id" id="id" value="<?php echo $_GET['id']; ?>">
								<input type="hidden" name="type" id="type" value="<?php echo base64_encode('1'); ?>">
							</form>

							<?php if (isset($_GET['type'])) {?>
								<div class="form-group">
									<div class="col-lg-12">
										<a href="exportar_excel.php?exp=10&Cons=<?php echo base64_encode(implode(",", $ProcedimientoEntradas)); ?>&sp=<?php echo base64_encode($ProcedimientoConsulta); ?>">
											<img src="css/exp_excel.png" width="50" height="30" alt="Exportar a Excel" title="Exportar a Excel"/>
										</a>
									</div>
								</div>
							<?php }?>
						</div> <!-- ibox-content -->
					</div> <!-- col-lg-12 -->
				</div> <!-- row -->

				<?php if (isset($_GET['type'])) {?>

					<div class="row">
						<div class="col-lg-12">
							<div class="ibox-content">
								<?php include "includes/spinner.php";?>

								<div class="table-responsive">
									<table class="table table-striped table-bordered table-hover dataTables-example" id="example">
										<thead>
											<tr>
												<?php foreach ($TitulosConsulta as &$TituloConsulta) {?>
													<?php echo "<th>" . $TituloConsulta . "</th>"; ?>
												<?php }?>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($Consultas as &$Consulta) {?>
												<tr class="gradeX tooltip-demo">
													<?php foreach ($TitulosConsulta as &$TituloConsulta) {?>
														<td>
															<?php echo is_a($Consulta[$TituloConsulta], 'DateTime') ? $Consulta[$TituloConsulta]->format('Y-m-d H:i:s') : $Consulta[$TituloConsulta]; ?>
														</td>
													<?php }?>
												</tr>
											<?php }?>
										</tbody>
									</table>
								</div> <!-- table -->
							</div> <!-- ibox-content -->
						</div> <!-- col-lg-12 -->
					</div> <!-- row -->
				<?php }?>
			</div> <!-- wrapper-content -->

			<!-- InstanceEndEditable -->
			<?php include_once "includes/footer.php";?>

		</div> <!-- page-wrapper -->
	</div> <!-- wrapper -->

	<?php include_once "includes/pie.php";?>
	<!-- InstanceBeginEditable name="EditRegion4" -->

	<script>
		$(document).ready(function(){
			$("#formInforme").validate({
				submitHandler: function(form){
					form.submit();
				}
			});

			$('.date').datepicker({
				todayBtn: "linked",
				keyboardNavigation: false,
				forceParse: false,
				calendarWeeks: true,
				autoclose: true,
				todayHighlight: true,
				format: 'yyyy-mm-dd'
			});

			$(".select2").select2();

			$('.i-checks').iCheck({
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green',
			});

			$('.dataTables-example').DataTable({
                pageLength: 25,
                dom: '<"html5buttons"B>lTfgitp',
				order: [[ 0, "desc" ]],
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

			/*
			// Inicio, parametrización de clientes y sucursales.
			$("#srcNombreCliente").change(function(){
				var NomCliente=document.getElementById("srcNombreCliente");
				var Cliente=document.getElementById("row_Entrada[NombreCampo]");

				if(NomCliente.value==""){
					Cliente.value="";
					CargarSucursales('row_Entrada[NombreCampo]');
				}
			});

			$("#row_Entrada[NombreCampo]").change(function(){
				CargarSucursales('row_Entrada[NombreCampo]');
			});

			var options = {
				url: function(phrase) {
					return "ajx_buscar_datos_json.php?type=7&id="+phrase;
				},

				getValue: "NombreBuscarCliente",
				requestDelay: 400,
				list: {
					match: {
						enabled: true
					},
					onClickEvent: function() {
						var value = $("#srcNombreCliente").getSelectedItemData().CodigoCliente;

						$("#row_Entrada[NombreCampo]").val(value).trigger("change");
					}
				}
			}

			$("#srcNombreCliente").easyAutocomplete(options);
			// Fin, parametrización de clientes y sucursales.
			*/
		});

		/*
		function CargarSucursales(cmpCliente){
			var Clt=document.getElementById(''+cmpCliente);

			$.ajax({
				type: "POST",
				url: "ajx_cbo_sucursales_clientes_simple.php?CardCode="+Clt.value+"<?php //if ($row_Entrada['Multiple'] == 1) {echo "&todos=0";}?>",
				success: function(response){
					$('#row_Entrada[NombreCampo]').html(response).fadeIn();
					$('#row_Entrada[NombreCampo]').val(null).trigger('change');
				}
			});
		}
		*/
	</script>

<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->
</html>
<?php sqlsrv_close($conexion);?>