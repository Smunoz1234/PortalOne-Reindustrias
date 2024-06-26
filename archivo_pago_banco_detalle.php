<?php  
require_once("includes/conexion.php");
PermitirAcceso(1801);

$Total=0;

$SQL=Seleccionar('uvw_Sap_tbl_AsistentePagosDetalle','*',"IdEntry='".$_POST['id']."' and PayAmount > 0");
?>

<br>
 	<div class="row">
	   <div class="col-lg-12">
			<div class="ibox-content">
				 <?php include("includes/spinner.php"); ?>
				 <div class="row">
					<label class="col-xs-12"><h3 class="bg-success p-xs b-r-sm"><i class="fa fa-list"></i> Detalle de la ejecución</h3></label>
				</div>
				<div class="row m-b-lg">
					<div class="col-lg-12 form-horizontal">
						<label class="col-lg-1 control-label">Archivo de banco</label>
						<div class="col-lg-3">
							<select name="Banco" class="form-control" id="Banco">
								<option value="Bancolombia">Bancolombia</option>
								<option value="BBVA">BBVA</option>
							</select>
						</div>
						
						<div class="col-lg-2">
							<!-- SMM, 02/05/2024 -->
							<div class="btn-group">
								<button data-toggle="dropdown"
									class="btn btn-outline btn-primary dropdown-toggle">
									<i class="fa fa-download"></i>
									Descargar Archivo 
									<i class="fa fa-caret-down"></i>
								</button>
								<ul class="dropdown-menu">
									<li>
										<a class="dropdown-item" target="_blank" id="excelBanco"
											href="exportar_excel.php?exp=16&Cons=<?php echo base64_encode($_POST['id']);?>&sp=<?php echo base64_encode("sp_AsistentePagosDetalle_Bancolombia");?>">
											Descargar Excel
										</a>
									</li>
									<li>
										<a class="dropdown-item" target="_blank" id="bancoTXT"
											href="exportar_excel.php?exp=24&Cons=<?php echo base64_encode($_POST['id']);?>&sp=<?php echo base64_encode("sp_AsistentePagosDetalle_Bancolombia");?>">
											Descargar TXT
										</a>
									</li>
								</ul>
							</div>
							<!-- Hasta aquí, 02/05/2024 -->
						</div>			
					</div>
				</div>
				<div class="table-responsive">
					<table class="table table-striped table-bordered table-hover dataTables-Details" >
					<thead>
					<tr>
						<th>Código de SN</th>
						<th>Nombre de SN</th>
						<th>Cantidad de documentos</th>
						<th>Total a pagar</th>							
						<th>Acciones</th>
					</tr>
					</thead>
					<tbody>
					<?php 
						while($row=sqlsrv_fetch_array($SQL)){ ?>
							<tr id="tr_Det<?php echo $row['CardCode'];?>" class="trDetalle">
								<td><?php echo $row['CardCode'];?></td>
								<td><?php echo $row['CardName'];?></td>
								<td><?php echo $row['Cant'];?></td>
								<td><?php echo number_format($row['PayAmount'],2);?></td>
								<td><a href="#" onClick="VerDetalleCliente('<?php echo $row['IdEntry'];?>','<?php echo $row['CardCode'];?>');" class="btn btn-warning btn-xs"><i class="fa fa-folder-open-o"></i> Ver detalles</a></td>
							</tr>
					<?php 
						$Total+=$row['PayAmount'];
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
	$("#Banco").on("change", function() {
		let banco = $("#Banco").val();
		
		let excelB1 = "exportar_excel.php?exp=16&Cons=<?php echo base64_encode($_POST['id']);?>&sp=<?php echo base64_encode("sp_AsistentePagosDetalle_Bancolombia");?>";
		let excelB2 = "exportar_excel.php?exp=22&Cons=<?php echo base64_encode($_POST['id']);?>&sp=<?php echo base64_encode("sp_AsistentePagosDetalle_BBVA");?>";

		let hrefExcel = (banco === "Bancolombia") ? excelB1 : excelB2;
		$("#excelBanco").attr("href", hrefExcel);

		// SMM, 02/05/2024
		// exp = 23
		let txtB1 = "exportar_excel.php?exp=24&Cons=<?php echo base64_encode($_POST['id']);?>&sp=<?php echo base64_encode("sp_AsistentePagosDetalle_Bancolombia");?>";
		let txtB2 = "exportar_excel.php?exp=24&Cons=<?php echo base64_encode($_POST['id']);?>&sp=<?php echo base64_encode("sp_AsistentePagosDetalle_BBVA");?>";
		

		let hrefTXT = (banco === "Bancolombia") ? txtB1 : txtB2;
		$("#bancoTXT").attr("href", hrefTXT);
	});
	 
	var table = $('.dataTables-Details').DataTable({
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