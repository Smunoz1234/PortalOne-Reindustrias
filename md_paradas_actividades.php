<?php
require_once "includes/conexion.php";

$IdActividad = $_POST['IdActividad'] ?? "";
$SQL = Seleccionar("[uvw_Sap_tbl_Actividades_Paradas]", "*", "[id_actividad] = $IdActividad");
?>

<div class="form-group">
	<div class="ibox-content">
		<div class="table-responsive">
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th>Inicio Parada</th>
						<th>Fin Parada</th>
						<th>Motivo Parada</th>
						<th>Comentario</th>
						<th>Estado</th>
						<th>Fecha Actualización</th>
						<th>Usuario Actualización</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
						<tr>
							<td>
								<?php echo isset($row["inicio_parada"]) ? $row["inicio_parada"]->format('Y-m-d H:i') : ''; ?>
							</td>
							<td>
								<?php echo isset($row["fin_parada"]) ? $row["fin_parada"]->format('Y-m-d H:i') : ''; ?>
							</td>
							
							<td>
								<?php echo $row["motivo_parada"] ?? ""; ?>
							</td>
							<td>
								<?php echo $row["comentario"] ?? ""; ?>
							</td>

							<td>
								<span class="label" style="color: white; background-color: <?php echo $row['color_estado_parada'] ?? ""; ?>;">
									<?php echo $row['estado_parada'] ?? ""; ?>
								</span>
							</td>

							<td>
								<?php echo isset($row["fecha_actualizacion"]) ? $row["fecha_actualizacion"]->format('Y-m-d H:i') : ''; ?>
							</td>
							<td>
								<?php echo $row["usuario_actualizacion"] ?? ""; ?>
							</td>
						</tr>
					<?php } ?>
					<!-- /while() -->
				</tbody>
			</table>
		</div>
	</div>
</div>