<?php
require_once "includes/conexion.php";
$DocEntry = $_POST['DocEntry'] ?? "";

$SQL = Seleccionar("[tbl_LlamadasServicios_SeguimientoTipoEstadoServicio]", "*", "[docentry_llamada_servicio] = $DocEntry");
?>

<div class="form-group">
	<div class="ibox-content">
		<div class="table-responsive">
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th>Línea</th>
						<th>Fecha Actualización</th>
						<th>Usuario Actualización</th>
						<th>DocEntry</th>
						<th>Tipo</th>
						<th>Tipo Estado Servicio Actual</th>
						<th>Tipo Estado Servicio Previo</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
						<tr>
							<td>
								<?php echo $row["linea"] ?? ""; ?>
							</td>

							<td>
								<?php echo $row["fecha_actualizacion"]->format('Y-m-d H:i') ?? ""; ?>
							</td>
							
							<td>
								<?php echo $row["id_usuario_actualizacion"] ?? ""; ?>
							</td>
							
							<td>
								<?php echo $row["docentry_objeto"] ?? ""; ?>
							</td>
							
							<td>
								<?php echo $row["tipo_objeto"] ?? ""; ?>
							</td>
							
							<td>
								<?php echo $row["tipo_estado_servicio_llamada_actual"] ?? ""; ?>
							</td>
							
							<td>
								<?php echo $row["tipo_estado_servicio_llamada_previo"] ?? ""; ?>
							</td>
						</tr>
					<?php } ?>
					<!-- /while() -->
				</tbody>
			</table>
		</div>
	</div>
</div>