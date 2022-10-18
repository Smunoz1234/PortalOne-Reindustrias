<?php
require_once "includes/conexion.php";

if (isset($_GET['id']) && $_GET['id'] != "") {
    $IdUsuario = base64_decode($_GET['id']);
} else {
    $IdUsuario = "";
}

$SQL_GruposArticulos = Seleccionar("uvw_tbl_UsuariosGruposArticulos", "*", "ID_Usuario='$IdUsuario'");

$SQL_GruposArticulosSAP = Seleccionar("uvw_Sap_tbl_GruposArticulos", "*");
?>

<style>
.select2-dropdown{
    z-index: 9999;
}
</style>

<div class="wrapper wrapper-content">
	<div class="modal inmodal fade" id="modalGruposArticulos" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title"> Adicionar grupo de articulos</h4>
				</div>

				<form id="formGruposArticulos">

					<div class="modal-body">
						<div class="form-group">
							<div class="ibox-content">
								<?php include "includes/spinner.php";?>

								<div class="form-group">
									<div class="col-md-12">
										<label class="control-label">Grupo de Articulo SAP <span class="text-danger">*</span></label>
										<select name="ItmsGrpCod" id="ItmsGrpCod" class="form-control select2" required>
											<option value="" disabled selected>Seleccione...</option>
											<?php while ($row_GruposArticulosSAP = sqlsrv_fetch_array($SQL_GruposArticulosSAP)) {?>
												<option value="<?php echo $row_GruposArticulosSAP['ItmsGrpCod']; ?>">
													<?php echo $row_GruposArticulosSAP['ItmsGrpNam']; ?>
												</option>
											<?php }?>
										</select>
									</div>
								</div>

								<div class="form-group">
									<div class="col-md-12">
										<label class="control-label">Comentarios</label>
										<textarea name="Comentarios" rows="3" maxlength="3000" class="form-control" id="Comentarios" type="text"></textarea>
									</div>
								</div>

							</div>
						</div> <!-- form-group ibox-content -->
					</div> <!-- modal-body -->

					<div class="modal-footer">
						<button type="submit" class="btn btn-success m-t-md"><i class="fa fa-check"></i> Aceptar</button>
						<button type="button" class="btn btn-warning m-t-md" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
					</div>
				</form>
			</div> <!-- modal-content -->
		</div> <!-- modal-dialog -->
	</div> <!-- inmodal -->
</div> <!-- wrapper-content -->

<!-- Inicio, ibox GruposArticulos -->
<div class="ibox" id="GruposArticulos">
	<div class="ibox-title bg-success">
		<h5 class="collapse-link"><i class="fa fa-list"></i> Lista Grupos Articulos</h5>
			<a class="collapse-link pull-right">
			<i class="fa fa-chevron-up"></i>
		</a>
	</div>
	<div class="ibox-content">
		<div class="row m-b-md">
			<div class="col-lg-12">
				<button class="btn btn-primary pull-right" type="button" id="NewMotivo" onClick="$('#modalGruposArticulos').modal('show');"><i class="fa fa-plus-circle"></i> Agregar nuevo</button>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table table-striped table-bordered table-hover dataTables-example">
				<thead>
					<tr>
						<th>Código Grupo Artículo</th>
						<th>Descripción Grupo Artículo</th>
						<th>Comentarios</th>
						<th>Fecha Actualizacion</th>
						<th>Usuario Actualizacion</th>
						<th>Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($row_GrupoArticulo = sqlsrv_fetch_array($SQL_GruposArticulos)) {?>
						<tr>
							<td><?php echo $row_GrupoArticulo['IdGrupoArticulo']; ?></td>
							<td><?php echo $row_GrupoArticulo['GrupoArticulo']; ?></td>
							<td><?php echo $row_GrupoArticulo['Comentarios']; ?></td>
							<td><?php echo isset($row_GrupoArticulo['fecha_actualizacion']) ? date_format($row_GrupoArticulo['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
							<td><?php echo $row_GrupoArticulo['usuario_actualizacion']; ?></td>
							<td>
								<button type="button" id="btnDelete<?php echo $row_GrupoArticulo['ID']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Motivo['ID']; ?>','GruposArticulos');"><i class="fa fa-trash"></i> Eliminar</button>
							</td>
						</tr>
					<?php }?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<!-- Fin, ibox GruposArticulos -->

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