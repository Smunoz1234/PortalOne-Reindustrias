<?php
require_once "includes/conexion.php";
$preCostos = (isset($_POST['pre'])) ? $_POST['pre'] : 0;

// Validar el uso, para informe de analisis de ventas.
if ($preCostos == 0) { 
	$ParamCons = array(
		"'" . $_POST['DocType'] . "'",
		"'" . $_POST['DocEntry'] . "'",
		$_POST['Todos'],
	);
	$SQL = EjecutarSP('usp_InformeVentas_DetalleArticulos', $ParamCons);
} 

// Previsualizar costos en asistente de facturacion.
elseif ($preCostos == 1) { 
	$ParamCons = array(
		"'" . $_POST['CardCode'] . "'",
		"'" . $_SESSION['CodUser'] . "'",
	);
	$SQL = EjecutarSP('sp_ConsultarFacturacionOT_Costos', $ParamCons);
} 

// Previsualizar costos de una llamada de servicio.
elseif ($preCostos == 2) { 
	$ParamCons = array(
		"'" . $_POST['DocEntry'] . "'",
	);
	$SQL = EjecutarSP('sp_ConsultarCostosOT', $ParamCons);
} 

// Previsualizar los precios de ventas de una llamada de servicio.
elseif ($preCostos == 3) { 
	$ParamCons = array(
		"'" . $_POST['DocEntry'] . "'",
	);
	$SQL = EjecutarSP('sp_ConsultarEntregasVentasOT', $ParamCons);
}

// Previsualizar los precios de los documentos de marketing por tipo.
elseif ($preCostos == 4) {
	$ParamCons = array(
		"'" . $_POST['DocNum'] . "'",
		"'" . $_POST['IdObjeto'] . "'",
	);
	$SQL = EjecutarSP('sp_ConsultarDocumentosPreciosOT', $ParamCons);
}

// Previsualizar los articulos no aprobados de una llamada de servicio.
elseif ($preCostos == 5) { 
	$ParamCons = array(
		"'" . $_POST['DocEntry'] . "'",
	);
	$SQL = EjecutarSP('sp_ConsultarOfertasVentas_ArticulosNoAprobados', $ParamCons);
}
?>

<div class="form-group">
	<div class="ibox-content">
		<div class="table-responsive">
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th>#</th>
						
						<?php if (($preCostos == 3) || ($preCostos == 5)) { ?>
							<th>Código cliente</th>
							<th>Nombre de cliente</th>
						<?php } ?>
						
						<th>Código artículo</th>
						<th>Nombre de artículo</th>
						<th>Unidad de medida</th>
						<th>Cantidad</th>
						<th>Precio</th>
						<th>Total</th>
						<th>Clase de artículo</th>
						<th>Grupo de artículo</th>

						<?php if ($preCostos == 5) { ?>
							<th>Causal no aprobación</th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php $i = 1;
					$SubGrupo = "";
					$SubTotal = 0;
					$Total = 0;
					$sw_Cambio = 0;

					while ($row = sqlsrv_fetch_array($SQL)) {
						if ($i == 1) {
							$SubGrupo = $row['DE_ItemType'];
						}

						if ((($SubGrupo != $row['DE_ItemType']) && $i > 1) || ($i == 1)) {
							if ($i > 1) { ?>
								<tr>
									<td colspan="<?php echo ($preCostos == 3) ? '8' : ($preCostos==5?'9':'6'); ?>" class="text-success font-bold"><span
											class="pull-right">SubTotal
											<?php echo $SubGrupo; ?>
										</span></td>
									<td class="text-success font-bold">
										<?php echo "$" . number_format($SubTotal, 2); ?>
									</td>
									<td colspan="2" class="text-success font-bold">&nbsp;</td>
								</tr>
							<?php } ?>

							<?php $SubGrupo = $row['DE_ItemType'];
							$SubTotal = 0; ?>

							<tr>
								<td colspan="<?php echo ($preCostos == 3) ? '11' : ($preCostos==5?'12':'9'); ?>"
									class="bg-muted text-success font-bold">
									<?php echo $row['DE_ItemType']; ?>
								</td>
							</tr>
						<?php } ?>
						<!-- /if() -->

						<tr>
							<td>
								<?php echo $i; ?>
							</td>

							<?php if (($preCostos == 3) || ($preCostos == 5)) { ?>
								<td>
									<a href="socios_negocios.php?id=<?php echo base64_encode($row['IdCliente']); ?>&tl=1" target="_blank">
										<?php echo $row['IdCliente']; ?>
									</a>
								</td>
								<td>
									<?php echo $row['NombreCliente']; ?>
								</td>
							<?php } ?>

							<td>
								<a href="articulos.php?id=<?php echo base64_encode($row['ItemCode']); ?>&tl=1" target="_blank">
									<?php echo $row['ItemCode']; ?>
								</a>
							</td>
							<td>
								<?php echo $row['ItemName']; ?>
							</td>
							<td>
								<?php echo $row['Unidad']; ?>
							</td>
							<td>
								<?php echo number_format($row['Cantidad'], 2); ?>
							</td>
							<td>
								<?php echo "$" . number_format($row['Precio'], 2); ?>
							</td>
							
							<td class="<?php if ($row['LineTotal'] < 0) {
								echo "text-danger";
							} else {
								echo "text-navy";
							} ?>">
								<?php echo "$" . number_format($row['LineTotal'], 2); ?>
							</td>
							
							<td>
								<?php echo $row['DE_ItemType']; ?>
							</td>
							<td>
								<?php echo $row['ItmsGrpNam']; ?>
							</td>

							<?php if ($preCostos == 5) { ?>
								<td>
									<?php echo $row['AprobacionArticuloCausal']; ?>
								</td>
							<?php } ?>
						</tr>
							
						<?php $i++;
						$SubTotal += $row['LineTotal'];
						$Total += $row['LineTotal']; ?>
					
					<?php } ?>
					<!-- /while() -->

					<!-- Filas finales de la tabla -->
					<tr>
						<td colspan="<?php echo (($preCostos == 3) || ($preCostos == 5)) ? '8' : '6'; ?>" class="text-success font-bold"><span
								class="pull-right">SubTotal
								<?php echo $SubGrupo; ?>
							</span></td>
						<td class="text-success font-bold">
							<?php echo "$" . number_format($SubTotal, 2); ?>
						</td>
						<td colspan="<?php echo ($preCostos == 5) ? '3' : '2'; ?>" class="text-success font-bold">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="<?php echo (($preCostos == 3) || ($preCostos == 5)) ? '8' : '6'; ?>" class="text-danger font-bold"><span
								class="pull-right">TOTAL</span></td>
						<td class="text-danger font-bold">
							<?php echo "$" . number_format($Total, 2); ?>
						</td>
						<td colspan="<?php echo ($preCostos == 5) ? '3' : '2'; ?>" class="text-danger font-bold">&nbsp;</td>
					</tr>
					<!-- Hasta aqui, filas del total -->
				</tbody>
			</table>
		</div>
	</div>
</div>