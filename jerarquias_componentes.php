<?php
// SMM, 06/03/2024
$SQL_Padre = Seleccionar("uvw_Sap_tbl_TarjetasEquipos", "*", "IdTarjetaEquipo = $IdTarjetaEquipo");
$row_Padre = sqlsrv_fetch_array($SQL_Padre);
$idPadre = "$IdTarjetaEquipo";
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

		// SMM, 07/03/2024
		var dataTree = [
			{
				"id": "N0_<?php echo $idPadre; ?>",
				"text": "<?php echo $textPadre; ?>",
				"icon": "fa fa-sitemap",
				"state": {
					"opened": true
				},
				"children": [
					<?php $SQL_Nivel_1 = Seleccionar("uvw_tbl_TarjetaEquipo_Componentes_Nivel_1", "*", "id_tarjeta_equipo_padre = $idPadre"); ?>
					<?php while ($row_N1 = sqlsrv_fetch_array($SQL_Nivel_1)) { ?>
						{
							"id": "N1_<?php echo $row_N1["id_jerarquia_1_hijo"]; ?>",
							"text": "<?php echo $row_N1["jerarquia_1_hijo"]; ?>",
							"icon": "fa fa-cubes"
						
							, "children": [
								<?php $idHijo = $row_N1["id_jerarquia_1_hijo"]; ?>
								<?php $SQL_Nivel_2 = Seleccionar("uvw_tbl_TarjetaEquipo_Componentes_Nivel_2", "*", "id_tarjeta_equipo_padre = $idPadre AND id_jerarquia_1_hijo = $idHijo"); ?>
								<?php while ($row_N2 = sqlsrv_fetch_array($SQL_Nivel_2)) { ?>
									{
										"id": "N2_<?php echo $row_N2["id_jerarquia_2_hijo"]; ?>",
										"text": "<?php echo $row_N2["jerarquia_2_hijo"]; ?>",
										"icon": "fa fa-cube"

										, "children": [
											<?php $idNieto = $row_N2["id_jerarquia_2_hijo"]; ?>
											<?php $SQL_Nivel_3 = Seleccionar("uvw_tbl_TarjetaEquipo_Componentes_Nivel_3", "*", "id_tarjeta_equipo_padre = $idPadre AND id_jerarquia_1_hijo = $idHijo AND id_jerarquia_2_hijo = $idNieto"); ?>
											<?php while ($row_N3 = sqlsrv_fetch_array($SQL_Nivel_3)) { ?>
												{
													"id": "<?php echo $row_N3["id_tarjeta_equipo_hijo"]; ?>",
													"text": "<?php echo $row_N3["id_articulo_hijo"] . " - " . $row_N3["articulo_hijo"]; ?>",
													"icon": "fa fa-rocket"
												},
											<?php } ?>
										]
									},
								<?php } ?>
							]
						},
					<?php } ?>
				]
			}
		];

		// Imprimiendo JSON del arbol.
		console.log(dataTree);

		// Armando y mostrando el arbol.
		$("#jstree_components").jstree({
			"get_selected": true
			, "plugins": ["themes", "icons"]
			, "core": {
				"strings": {
					"Loading ...": "Cargando..."
				},
				"multiple": false,
				"data": dataTree
			}
		}).bind("select_node.jstree", function (event, data) {
			Seleccionar(data.node.id);
		});

		// Función para expandir todo el árbol
		$('#btnExpandir').on('click', function() {
			$('#jstree_components').jstree('open_all');
		});

		// Función para contraer todo el árbol
		$('#btnContraer').on('click', function() {
			$('#jstree_components').jstree('close_all');
		});
	});

	// SMM, 06/03/2024
	function Seleccionar(id) {
		console.log(`Has seleccionado el nodo "${id}"`);
		
		// Resaltar con la clase personalizada "highlighted".
		// $("#footableComponents tbody tr").removeClass("highlighted");
		// $(`#component${id}`).addClass("highlighted");

		// Expandir componente seleccionado.
		$("#footableComponents tbody tr").data('expanded', false);
		$(`#component${id}`).data('expanded', true);

		// Re-renderizar Footable.
		$('#footableComponents').footable();
	}
</script>