<?php
require_once "includes/conexion.php";

if (isset($_GET['id']) && $_GET['id'] != "") {
    $IdUsuario = base64_decode($_GET['id']);
} else {
    $IdUsuario = "";
}

$SQL_GruposArticulos = Seleccionar("uvw_tbl_UsuariosGruposArticulos", "*", "ID_Usuario='$IdUsuario'");
?>

<!-- Inicio, ibox GruposArticulos -->
<div class="ibox" id="GruposArticulos">
	<div class="ibox-title bg-success">
		<h5 class="collapse-link"><i class="fa fa-list"></i> Lista GruposArticulos de autorizaciones</h5>
			<a class="collapse-link pull-right">
			<i class="fa fa-chevron-up"></i>
		</a>
	</div>
	<div class="ibox-content">
		<div class="row m-b-md">
			<div class="col-lg-12">
				<button class="btn btn-primary pull-right" type="button" id="NewMotivo" onClick="CrearCampo('GruposArticulos');"><i class="fa fa-plus-circle"></i> Agregar nuevo</button>
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
					<?php while ($row_Motivo = sqlsrv_fetch_array($SQL_GruposArticulos)) {?>
						<tr>
							<td><?php echo $row_Motivo['IdGrupoArticulo']; ?></td>
							<td><?php echo $row_Motivo['GrupoArticulo']; ?></td>
							<td><?php echo $row_Motivo['Comentarios']; ?></td>
							<td><?php echo isset($row_Motivo['fecha_actualizacion']) ? date_format($row_Motivo['fecha_actualizacion'], 'Y-m-d H:i:s') : ""; ?></td>
							<td><?php echo $row_Motivo['usuario_actualizacion']; ?></td>
							<td>
								<button type="button" id="btnDelete<?php echo $row_Motivo['ID']; ?>" class="btn btn-danger btn-xs" onClick="EliminarCampo('<?php echo $row_Motivo['ID']; ?>','GruposArticulos');"><i class="fa fa-trash"></i> Eliminar</button>
							</td>
						</tr>
					<?php }?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<!-- Fin, ibox GruposArticulos -->