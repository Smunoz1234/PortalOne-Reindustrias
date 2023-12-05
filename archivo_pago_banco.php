<?php require_once("includes/conexion.php");
PermitirAcceso(1801);
$sw=0;

$Filtro="";
$Estado="";

//Fechas
if(isset($_GET['FechaInicial'])&&$_GET['FechaInicial']!=""){
	$FechaInicial=$_GET['FechaInicial'];
	$sw=1;
}else{
	//Restar 7 dias a la fecha actual
	$FechaInicial = date('Y-m-d');
//	$nuevafecha = strtotime ('-'.ObtenerVariable("DiasRangoFechasDocSAP").' day');
//	$nuevafecha = date ( 'Y-m-d' , $nuevafecha);
//	$FechaInicial=$nuevafecha;
}
if(isset($_GET['FechaFinal'])&&$_GET['FechaFinal']!=""){
	$FechaFinal=$_GET['FechaFinal'];
}else{
	$FechaFinal=date('Y-m-d');
}

//Filtros
if($sw==1){
	$Filtro="(FechaEjecucion Between '".FormatoFecha($FechaInicial)."' and '".FormatoFecha($FechaFinal)."')";	
}


if(isset($_GET['Estado'])&&$_GET['Estado']!=""){
	$Estado=$_GET['Estado'];
	$Filtro.=" and CodEstadoEjecucion='".$Estado."'";
	$sw=1;
}

if($sw==1){
	$SQL=Seleccionar('uvw_Sap_tbl_AsistentePagos','*',$Filtro);
}
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include_once("includes/cabecera.php"); ?>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Archivo para pagos | <?php echo NOMBRE_PORTAL;?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
<style>
	.ibox-title a{
		color: inherit !important;
	}
	.collapse-link:hover{
		cursor: pointer;
	}
	.modal-dialog{
		width: 70% !important;
	}
	.modal-footer{
		border: 0px !important;
	}
	.bg-success, .bg-success > td{
		background-color: #1c84c6 !important;
		color: #ffffff !important;
	}
</style>
<script type="text/javascript">
	$(document).ready(function() {
		$("#NombreCliente").change(function(){
			var NomCliente=document.getElementById("NombreCliente");
			var Cliente=document.getElementById("Cliente");
			if(NomCliente.value==""){
				Cliente.value="";
			}	
		});
		$("#NombreArticulo").change(function(){
			var NomArticulo=document.getElementById("NombreArticulo");
			var Articulo=document.getElementById("Articulo");
			if(NomArticulo.value==""){
				Articulo.value="";
			}	
		});
	});
</script>
<!-- InstanceEndEditable -->
</head>

<body>

<div id="wrapper">

    <?php include_once("includes/menu.php"); ?>

    <div id="page-wrapper" class="gray-bg">
        <?php include_once("includes/menu_superior.php"); ?>
        <!-- InstanceBeginEditable name="Contenido" -->
        <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-8">
                    <h2>Archivo para pagos</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index1.php">Inicio</a>
                        </li>
                        <li>
                            <a href="#">Gestión de bancos</a>
                        </li>
                        <li class="active">
                            <strong>Archivo para pagos</strong>
                        </li>
                    </ol>
                </div>
            </div>
         <div class="wrapper wrapper-content">
			<div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title" id="TituloModal"></h4>
						</div>
						<div class="modal-body" id="ContenidoModal"></div>
						<div class="modal-footer">
							<button type="button" class="btn btn-success m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
						</div>
					</div>
				</div>
			</div>
             <div class="row">
				<div class="col-lg-12">
			    <div class="ibox-content">
					 <?php include("includes/spinner.php"); ?>
				  <form action="archivo_pago_banco.php" method="get" id="formBuscar" class="form-horizontal">
					   <div class="form-group">
							<label class="col-xs-12"><h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para filtrar</h3></label>
						</div>
						<div class="form-group">
							<label class="col-lg-1 control-label">Fechas</label>
							<div class="col-lg-3">
								<div class="input-daterange input-group" id="datepicker">
									<input name="FechaInicial" type="text" class="input-sm form-control" id="FechaInicial" placeholder="Fecha inicial" value="<?php echo $FechaInicial;?>" autocomplete="off" />
									<span class="input-group-addon">hasta</span>
									<input name="FechaFinal" type="text" class="input-sm form-control" id="FechaFinal" placeholder="Fecha final" value="<?php echo $FechaFinal;?>" autocomplete="off" />
								</div>
							</div>
							<label class="col-lg-1 control-label">Estado</label>
							<div class="col-lg-3">
								<select name="Estado" class="form-control" id="Estado">
									<option value="">(Todos)</option>
									<option value="E" <?php if(isset($_GET['Estado'])&&($_GET['Estado']=='E')){echo "selected=\"selected\"";}?>>Ejecutado</option>
									<option value="R" <?php if(isset($_GET['Estado'])&&($_GET['Estado']=='R')){echo "selected=\"selected\"";}?>>Recomendado</option>
									<option value="S" <?php if(isset($_GET['Estado'])&&($_GET['Estado']=='S')){echo "selected=\"selected\"";}?>>Guardado</option>
								</select>
							</div>
							<div class="col-lg-4">
								<button type="submit" class="btn btn-outline btn-success pull-right"><i class="fa fa-search"></i> Buscar</button>
							</div>
						</div>
				 </form>
			</div>
			</div>
		  </div>
         <br>
		 <?php if($sw==1){?>
          <div class="row">
           <div class="col-lg-12">
			    <div class="ibox-content">
					<?php include("includes/spinner.php"); ?>
					<div class="table-responsive">
						<table class="table table-striped table-bordered table-hover dataTables-example" >
						<thead>
						<tr>
							<th>Nombre de ejecución de pago</th>
							<th>Fecha</th>
							<th>Total</th>
							<th>Cantidad de pagos</th>						
							<th>Estado</th>
							<th>Acciones</th>
						</tr>
						</thead>
						<tbody>
						<?php
							if($sw==1){
							while($row=sqlsrv_fetch_array($SQL)){ ?>
								<tr id="tr_Resum<?php echo $row['IdEjecucion'];?>" class="trResum">
									<td><?php echo $row['NombreEjecucion'];?></td>
									<td><?php echo $row['FechaEjecucion']->format('Y-m-d');?></td>
									<td><?php echo number_format($row['TotalEjecucion'],2);?></td>
									<td><?php echo $row['CantidadEjecucion'];?></td>
									<td><span <?php if($row['CodEstadoEjecucion']=='E'){echo "class='label label-info'";}elseif($row['CodEstadoEjecucion']=='R'){echo "class='label label-warning'";}else{echo "class='label label-success'";}?>><?php echo $row['NombreEstadoEjecucion'];?></span></td>	
									<td><a href="#" onClick="VerDetalle('<?php echo $row['IdEjecucion'];?>');" class="btn btn-success btn-xs"><i class="fa fa-folder-open-o"></i> Ver detalles</a></td>
								</tr>
						<?php }
						}?>
						</tbody>
						</table>
					</div>					
				</div>
			 </div> 
          </div>
		 <?php }?>
		<div id="dv_Detalle"></div>
		<div id="dv_DetalleCliente"></div>
        </div>
        <!-- InstanceEndEditable -->
        <?php include_once("includes/footer.php"); ?>

    </div>
</div>
<?php include_once("includes/pie.php"); ?>
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
			
			$(".select2").select2();
			
			$('#FechaInicial').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true,
            });
			 $('#FechaFinal').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true,
            }); 
			
            $('.dataTables-example').DataTable({
                pageLength: 10,
                responsive: false,
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
function VerDetalle(cod){
	
	PonerQuitarClase(cod);
	
	$('.ibox-content').toggleClass('sk-loading',true);
	$.ajax({
		type: "POST",
		url: "archivo_pago_banco_detalle.php",
		data:{
			id:cod
		},
		success: function(response){
			$('#dv_Detalle').html(response).fadeIn();
			$('#dv_DetalleCliente').html('');
			$('.ibox-content').toggleClass('sk-loading',false);
		}
	});
}

function VerDetalleCliente(id, cardcode){
	PonerQuitarClase(cardcode,2);
	$('.ibox-content').toggleClass('sk-loading',true);
	$.ajax({
		type: "POST",
		url: "archivo_pago_banco_detalle_fact.php",
		data:{
			id:id,
			CardCode:cardcode
		},
		success: function(response){
			$('#dv_DetalleCliente').html(response).fadeIn();
			$('.ibox-content').toggleClass('sk-loading',false);
		}
	});
}
	
function PonerQuitarClase(ID,detalle=1){
	if(detalle==2){
		$(".trDetalle").removeClass('bg-success');
		$("#tr_Det"+ID).addClass('bg-success');
	}else{
		$(".trResum").removeClass('bg-success');
		$("#tr_Resum"+ID).addClass('bg-success');
	}
	
}
</script>
<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion);?>