<?php
// Componentes, Tarjeta de Equipo. SMM, 06/03/2024
$SQL_Componentes_TE = Seleccionar("uvw_tbl_TarjetaEquipo_Componentes", "*", "id_tarjeta_equipo_padre = $IdTarjetaEquipo");

$array_Componentes = [];
while ($row_Componente = sqlsrv_fetch_array($SQL_Componentes_TE)) {
	array_push($array_Componentes, $row_Componente);
}

// print_r($array_Componentes);
// exit();
// Hasta aquí, SMM 06/03/2024

// SMM, 06/03/2024
$SQL_Padre = Seleccionar("uvw_Sap_tbl_TarjetasEquipos", "*", "IdTarjetaEquipo = $IdTarjetaEquipo");
$row_Padre = sqlsrv_fetch_array($SQL_Padre);
$textPadre = ($row_Padre["ItemCode"] ?? "") . " - " . ($row_Padre["ItemName"] ?? "");
?>

<div class="row">
	<div class="col-lg-12">
		<div class="ibox-content">
			<?php include("includes/spinner.php"); ?>
			<div id="jstree_components"></div>
		</div>
	</div>
</div>

<script>
	$(document).ready(function () {
		$(".alkin").on("click", function () {
			$(".ibox-content").toggleClass("sk-loading");
		});

		$("#jstree_components").jstree({
			"core": {
				"strings": {
					"Loading ...": "Cargando..."
				},
				"multiple": false,
				"data": [
					{
						"text": "<?php echo $textPadre; ?>",
						"icon": "fa fa-sitemap",
						"children": [
							<?php foreach ($array_Componentes as &$component) { ?>
								{
									"text": "<?php echo $component["id_articulo_hijo"] . " - " . $component["articulo_hijo"]; ?>",
									"icon": "fa fa-cubes"
								
									<?php /* ?>
										, "children": [
											{
												"text": "Nieto",
												"icon": "fa fa-cube"
											}
										]
									<?php */ ?>
								},
							<?php } ?>
						]
					}
				]
			},
			'get_selected': true
			, "plugins": ["themes", "icons"]
		})
		.bind('select_node.jstree', function (e, data) {
			Seleccionar(data.node.id);
		});
	});

	// SMM, 06/03/2024
	function Seleccionar(id) {
		alert(`Has seleccionado el nodo "${id}"`);
	}
</script>
