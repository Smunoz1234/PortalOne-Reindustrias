<?php  
require_once("includes/conexion.php");
PermitirAcceso(1801);

$Total=0;

$SQL=Seleccionar('uvw_Sap_tbl_AsistentePagosDetalle','*',"IdEntry='".$_POST['id']."' and CardCode='".$_POST['CardCode']."'");
?>

<br>
 	<div class="row">
	   <div class="col-lg-12">
			<div class="ibox-content">
				 <?php include("includes/spinner.php"); ?>
				 <div class="row">
					<label class="col-xs-12"><h3 class="bg-success p-xs b-r-sm"><i class="fa fa-list"></i> Detalle del cliente</h3></label>
				</div>
				<div class="table-responsive">
					<table class="table table-striped table-bordered table-hover dataTables-DetailsFact" >
					<thead>
					<tr>
						<th>Tipo de documento</th>
						<th>Número del documento</th>
						<th>Nombre del SN</th>
						<th>Total del documento</th>
					</tr>
					</thead>
					<tbody>
					<?php 
						while($row=sqlsrv_fetch_array($SQL)){ ?>
							<tr>
								<td><?php echo $row['NombreObjeto'];?></td>
								<td><?php echo $row['DocNum'];?></td>
								<td><?php echo $row['CardName'];?></td>
								<td><?php echo number_format($row['TotalLoc'],2);?></td>
							</tr>
					<?php 
						$Total+=$row['TotalLoc'];
					}?>
					</tbody>
					</table>
				</div>
				<div class="row m-t-md">
					<div class="col-lg-12">
						<div class="col-lg-3 pull-right">
							<div class="ibox border-left-right border-top-bottom">
								<div class="ibox-title">
									<h2 class="font-bold">Total a pagar</h2>
								</div>
								<div class="ibox-content">
									<h1 class="no-margins"><span class="font-bold text-success"><?php echo "$".number_format($Total,0);?></span></h1>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div> 
	</div>
<script>
 $(document).ready(function(){
	 
	var table = $('.dataTables-DetailsFact').DataTable({
		pageLength: 10,
		dom: '<"html5buttons"B>lTfgitp',
		orderCellsTop: true,
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