<?php require_once "includes/conexion.php";
PermitirAcceso(1605);

$sw = 0;
if (isset($_GET['Marca']) && $_GET['Marca'] != "") {
    $sw = 1;
}

// Clientes
if (PermitirFuncion(205)) {
    $SQL_Cliente = Seleccionar("uvw_Sap_tbl_Clientes", "CodigoCliente, NombreCliente", "", 'NombreCliente');
} else {
    $Where = "ID_Usuario = " . $_SESSION['CodUser'];
    $SQL_Cliente = Seleccionar("uvw_tbl_ClienteUsuario", "CodigoCliente, NombreCliente", $Where);
}

// Marcas de vehiculo
$SQL_Marca = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_MarcaVehiculo', '*');

// Concesionarios en la tarjeta de equipo
$SQL_Concesionario = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_Concesionario', '*');

// Filtros
$Cliente = $_GET['ClienteEquipo'] ?? "";
$IdMarca = $_GET['Marca'] ?? "";
$Ciudad = $_GET['Ciudad'] ?? "";
$IdConcesionario = $_GET['Concesionario'] ?? "";
$FechaMatriculo = (isset($_GET['CDU_FechaMatricula']) && strtotime($_GET['CDU_FechaMatricula'])) ? ("'" . FormatoFecha($_GET['CDU_FechaMatricula']) . "'") : "NULL";
$FechaUltMnto = (isset($_GET['CDU_FechaUlt_Mant']) && strtotime($_GET['CDU_FechaUlt_Mant'])) ? ("'" . FormatoFecha($_GET['CDU_FechaUlt_Mant']) . "'") : "NULL";
$FechaProxMnto = (isset($_GET['CDU_FechaProx_Mant']) && strtotime($_GET['CDU_FechaProx_Mant'])) ? ("'" . FormatoFecha($_GET['CDU_FechaProx_Mant']) . "'") : "NULL";

if ($sw == 1) {
    $Param = array(
        "'" . $Cliente . "'",
        "'" . $IdMarca . "'",
        "'" . $Ciudad . "'",
        "'" . $IdConcesionario . "'",
        $FechaMatriculo,
        $FechaUltMnto,
        $FechaProxMnto,
    );
    $SQL = EjecutarSP('usp_inf_GestionTarjetaEquipos', $Param);
}
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Gestión de tarjetas de equipo | <?php echo NOMBRE_PORTAL; ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
<script type="text/javascript">
	$(document).ready(function() {
		$("#NombreClienteEquipo").change(function(){
			var NomCliente=document.getElementById("NombreClienteEquipo");
			var Cliente=document.getElementById("ClienteEquipo");
			if(NomCliente.value==""){
				Cliente.value="";
			}
		});
	});
</script>
<!-- InstanceEndEditable -->
</head>

<body>

<div id="wrapper">

    <?php include "includes/menu.php";?>

    <div id="page-wrapper" class="gray-bg">
        <?php include "includes/menu_superior.php";?>
        <!-- InstanceBeginEditable name="Contenido" -->
        <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-8">
                    <h2>Gestión de tarjetas de equipo</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="#">Mantenimiento</a>
                        </li>
						<li>
                            <a href="#">Informes</a>
                        </li>
                        <li class="active">
                            <strong>Gestión de tarjetas de equipo</strong>
                        </li>
                    </ol>
                </div>
			<?php if (PermitirFuncion(1602)) {?>
                <div class="col-sm-4">
                    <div class="title-action">
                        <a href="tarjeta_equipo.php" class="alkin btn btn-primary"><i class="fa fa-plus-circle"></i> Crear nueva tarjeta de equipo</a>
                    </div>
                </div>
			<?php }?>
               <?php //echo $Cons;?>
            </div>
         <div class="wrapper wrapper-content">
             <div class="row">
				<div class="col-lg-12">
			    <div class="ibox-content">
					 <?php include "includes/spinner.php";?>
				  <form action="informe_tarjeta_equipo.php" method="get" id="formBuscar" class="form-horizontal">
					    <div class="form-group">
							<label class="col-xs-12"><h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para filtrar</h3></label>
						</div>
						<div class="form-group">
							<label class="col-lg-1 control-label">Marca <span class="text-danger">*</span></label>
							<div class="col-lg-3">
								<select name="Marca" class="form-control" id="Marca" required>
										<option value="" disabled selected>Seleccione...</option>
								  <?php while ($row_Marca = sqlsrv_fetch_array($SQL_Marca)) {?>
										<option value="<?php echo $row_Marca['IdMarcaVehiculo']; ?>" <?php if ((isset($_GET['Marca'])) && (strcmp($row_Marca['IdMarcaVehiculo'], $_GET['Marca']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Marca['DeMarcaVehiculo']; ?></option>
								  <?php }?>
								</select>
							</div>

							<label class="col-lg-1 control-label">Concesionario</label>
							<div class="col-lg-3">
								<select name="Concesionario" class="form-control" id="Concesionario">
										<option value="">(Todos)</option>
								  <?php while ($row_Concesionario = sqlsrv_fetch_array($SQL_Concesionario)) {?>
										<option value="<?php echo $row_Concesionario['CodigoConcesionario']; ?>" <?php if ((isset($_GET['Concesionario'])) && (strcmp($row_Concesionario['CodigoConcesionario'], $_GET['Concesionario']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Concesionario['NombreConcesionario']; ?></option>
								  <?php }?>
								</select>
							</div>

							<label class="col-lg-1 control-label">Ciudad</label>
							<div class="col-lg-3">
								<input name="Ciudad" type="text" class="form-control" id="Ciudad" maxlength="100" value="<?php if (isset($_GET['Ciudad']) && ($_GET['Ciudad'] != "")) {echo $_GET['Ciudad'];}?>">
							</div>
						</div>

					  	<div class="form-group">
						  	<label class="col-lg-1 control-label">Cliente</label>
							<div class="col-lg-3">
								<input name="ClienteEquipo" type="hidden" id="ClienteEquipo" value="<?php if (isset($_GET['ClienteEquipo']) && ($_GET['ClienteEquipo'] != "")) {echo $_GET['ClienteEquipo'];}?>">
								<input name="NombreClienteEquipo" type="text" class="form-control" id="NombreClienteEquipo" placeholder="Para TODOS, dejar vacio..." value="<?php if (isset($_GET['NombreClienteEquipo']) && ($_GET['NombreClienteEquipo'] != "")) {echo $_GET['NombreClienteEquipo'];}?>">
							</div>

							<label class="col-lg-1 control-label">Fecha Ult. Mantenimiento</label>
								<div class="col-lg-3 input-group date">
									 <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="CDU_FechaUlt_Mant" id="CDU_FechaUlt_Mant" type="text" class="form-control"
									 placeholder="YYYY-MM-DD" value="<?php if (isset($_GET['CDU_FechaUlt_Mant']) && strtotime($_GET['CDU_FechaUlt_Mant'])) {echo date('Y-m-d', strtotime($_GET['CDU_FechaUlt_Mant']));}?>">
								</div>

							<label class="col-lg-1 control-label">Fecha Prox. Mantenimiento</label>
							<div class="col-lg-3 input-group date">
									<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="CDU_FechaProx_Mant" id="CDU_FechaProx_Mant" type="text" class="form-control"
									placeholder="YYYY-MM-DD" value="<?php if (isset($_GET['CDU_FechaProx_Mant']) && strtotime($_GET['CDU_FechaProx_Mant'])) {echo date('Y-m-d', strtotime($_GET['CDU_FechaProx_Mant']));}?>">
							</div>
						</div>

						<div class="form-group">
							<label class="col-lg-1 control-label">Fecha Matricula</label>
							<div class="col-lg-3 input-group date">
								<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="CDU_FechaMatricula" id="CDU_FechaMatricula" type="text" class="form-control"
								placeholder="YYYY-MM-DD" value="<?php if (isset($_GET['CDU_FechaMatricula']) && strtotime($_GET['CDU_FechaMatricula'])) {echo date('Y-m-d', strtotime($_GET['CDU_FechaMatricula']));}?>">
							</div>

							<div class="col-lg-4">
								<button type="submit" class="btn btn-outline btn-success pull-right"><i class="fa fa-search"></i> Buscar</button>
							</div>
						</div>

						<?php if ($sw == 1) {?>
					  	<div class="form-group">
							<div class="col-lg-10">
								<a href="exportar_excel.php?exp=10&Cons=<?php echo base64_encode(implode(",", $Param)); ?>&sp=<?php echo base64_encode("usp_inf_GestionTarjetaEquipos"); ?>">
									<img src="css/exp_excel.png" width="50" height="30" alt="Exportar a Excel" title="Exportar a Excel"/>
								</a>
							</div>
						</div>
					   <?php }?>
				 </form>
			</div>
			</div>
		  </div>
         <br>

		<?php if ($sw == 1) {?>
          <div class="row">
           <div class="col-lg-12">
			    <div class="ibox-content">
					 <?php include "includes/spinner.php";?>
			<div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover dataTables-example" >
                    <thead>
                    <tr>
						<th>Núm.</th>
						<th>Código cliente</th>
						<th>Serial interno</th>
                        <th>Marca vehículo</th>
						<th>Ciudad</th>
						<th>Concesionario</th>
						<th>Fecha Matricula</th>
                        <th>Fecha Ult. Mant.</th>
						<th>Fecha Prox. Mant.</th>
						<th>Estado</th>
						<th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = sqlsrv_fetch_array($SQL)) {?>
						 <tr class="gradeX tooltip-demo">
							<td><?php echo $row['IdTarjetaEquipo']; ?></td>
							<td><?php echo $row['CardCode']; ?></td>
							<td><?php echo $row['SerialInterno']; ?></td>
							<td><?php echo $row['CDU_Marca']; ?></td>
							<td><?php echo $row['Ciudad']; ?></td>
							<td><?php echo $row['CDU_Concesionario']; ?></td>
							<td><?php echo ($row['CDU_FechaMatricula'] != "") ? $row['CDU_FechaMatricula']->format('Y-m-d') : ""; ?></td>
							<td><?php echo ($row['CDU_FechaUlt_Mant'] != "") ? $row['CDU_FechaUlt_Mant']->format('Y-m-d') : ""; ?></td>
							<td><?php echo ($row['CDU_FechaProx_Mant'] != "") ? $row['CDU_FechaProx_Mant']->format('Y-m-d') : ""; ?></td>
							<td>
								<?php if ($row['CodEstado'] == 'A') {?>
									<span  class='label label-info'>Activo</span>
								<?php } elseif ($row['CodEstado'] == 'R') {?>
									<span  class='label label-danger'>Devuelto</span>
								<?php } elseif ($row['CodEstado'] == 'T') {?>
									<span  class='label label-success'>Finalizado</span>
								<?php } elseif ($row['CodEstado'] == 'L') {?>
									<span  class='label label-secondary'>Concedido en préstamo</span>
								<?php } elseif ($row['CodEstado'] == 'I') {?>
									<span  class='label label-warning'>En laboratorio de reparación</span>
								<?php }?>
							</td>
							<td><a href="tarjeta_equipo.php?id=<?php echo base64_encode($row['IdTarjetaEquipo']); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('consultar_tarjeta_equipo.php'); ?>&tl=1" class="alkin btn btn-success btn-xs"><i class="fa fa-folder-open-o"></i> Abrir</a></td>
						</tr>
					<?php }?>
                    </tbody>
                    </table>
              </div>
			</div>
			 </div>
          </div>
		<?php }?>

		</div>
        <!-- InstanceEndEditable -->
        <?php include "includes/footer.php";?>

    </div>
</div>
<?php include "includes/pie.php";?>
<!-- InstanceBeginEditable name="EditRegion4" -->
 <script>
        $(document).ready(function(){
			$("#formBuscar").validate({
			 submitHandler: function(form){
				 $('.ibox-content').toggleClass('sk-loading');
				 form.submit();
				}
			});
			 $(".alkin").on('click', function(){
					$('.ibox-content').toggleClass('sk-loading');
				});
			 $('#FechaInicial').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				todayHighlight: true,
				format: 'yyyy-mm-dd'
            });
			 $('#FechaFinal').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				todayHighlight: true,
				format: 'yyyy-mm-dd'
            });

			$('.chosen-select').chosen({width: "100%"});

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
						var value = $("#NombreClienteEquipo").getSelectedItemData().CodigoCliente;
						$("#ClienteEquipo").val(value).trigger("change");
					},
					onKeyEnterEvent: function() {
						var value = $("#NombreClienteEquipo").getSelectedItemData().CodigoCliente;
						$("#ClienteEquipo").val(value).trigger("change");
					}
				}
			};

			$("#NombreClienteEquipo").easyAutocomplete(options);

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

        });

    </script>
<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion);?>