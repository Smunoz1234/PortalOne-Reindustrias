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
				"id": "ROOT",
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
			Seleccionar(data.node);
		});

		// Función para expandir todo el árbol
		$('#btnExpandir').on('click', function() {
			$('#jstree_components').jstree('open_all');
		});

		// Función para contraer todo el árbol manteniendo el primer nivel abierto
		$('#btnContraer').on('click', function() {
			$('#jstree_components').jstree('close_all');

			// Expandir el primer nivel
			$('#jstree_components').jstree('open_node', 'ROOT');
		});
	});

	// SMM, 06/03/2024
	function Seleccionar(node) {
		console.log(`Has seleccionado el nodo "${node.id}"`);
		
		if (node.children.length === 0) {
			console.log(`El nodo "${node.id}" es una hoja.`);
			$("#id_tarjeta_equipo_hijo").text(node.id);

			$.ajax({
				url: "ajx_buscar_datos_json.php",
				data: { type: 55, id: node.id, padre: <?php echo $idPadre; ?> },
				dataType: 'json',
				success: function (data) {
					console.log("Line 115", data);

					document.getElementById('id_articulo_hijo').value = data.id_articulo_hijo;
					document.getElementById('articulo_hijo').value = data.articulo_hijo;
					document.getElementById('unidad_hijo').value = data.unidad_hijo;
					document.getElementById('ubicacion_hijo').value = data.ubicacion_hijo;
					document.getElementById('fecha_operacion_hijo').value = data.fecha_operacion_hijo;
					document.getElementById('contador_hijo').value = data.contador_hijo;
					document.getElementById('proyecto_hijo').value = data.proyecto_hijo;
					document.getElementById('estado_hijo').value = data.estado_hijo;
				},
				error: function (data) {
					console.error("Line 130", data);
				}
			});
		}
	}
</script>