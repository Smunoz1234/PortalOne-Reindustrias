<?php require_once "includes/conexion.php";
// print_r($_POST);
// exit();

// Dimensiones. SMM, 29/05/2023
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimActive='Y'");

$array_Dimensiones = [];
while ($row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones)) {
    array_push($array_Dimensiones, $row_Dimension);
}
// Hasta aquí, SMM 29/05/2023

// SMM, 13/03/2024
$id_tarjeta_equipo_padre = $_POST["id_tarjeta_equipo_padre"] ?? "";
$Filtro = "[id_tarjeta_equipo_padre] = '$id_tarjeta_equipo_padre'";

$ItemCode = $_POST["ItemCode"] ?? "";
if ($ItemCode != "") {
    $Filtro .= " AND [id_articulo_hijo] = '$ItemCode'";
}

$SerialEquipo = $_POST["SerialEquipo"] ?? "";
if ($SerialEquipo != "") {
    $Filtro .= " AND ([serial_fabricante_hijo] LIKE '%$SerialEquipo%' OR [serial_interno_hijo] LIKE '%$SerialEquipo%')";
}

$BuscarDato = $_POST['BuscarDato'] ?? "";
if ($BuscarDato != "") {
    $Filtro .= " AND ([serial_fabricante_hijo] LIKE '%$BuscarDato%' OR [serial_interno_hijo] LIKE '%$BuscarDato%' OR [id_tarjeta_equipo_hijo] LIKE '%$BuscarDato%')";
}

$IdJerarquia1 = $_POST["id_jerarquia_1"] ?? "";
if ($IdJerarquia1 != "") {
    $Filtro .= " AND [id_jerarquia_1_hijo] = '$IdJerarquia1'";
}

$IdJerarquia2 = $_POST["id_jerarquia_2"] ?? "";
if ($IdJerarquia2 != "") {
    $Filtro .= " AND [id_jerarquia_2_hijo] = '$IdJerarquia2'";
}

$UbicacionEquipo = $_POST["id_ubicacion_equipo"] ?? "";
if ($UbicacionEquipo != "") {
    $Filtro .= " AND [id_ubicacion_hijo] = '$UbicacionEquipo'";
}

// Realizar consulta con filtros
$Where = "$Filtro ORDER BY [id_tarjeta_equipo_hijo] DESC";
$Cons_TE_Componentes = "SELECT TOP 1000 * FROM [uvw_tbl_TarjetaEquipo_Componentes] WHERE $Where";
$SQL = sqlsrv_query($conexion, $Cons_TE_Componentes);

// SMM, 21/11/2023
if (!$SQL) {
    echo $Cons_TE_Componentes;
}

// Devolver respuesta en formato JSON
/*
$dataString = "";
if ($SQL === false) {
$dataString = json_encode(sqlsrv_errors(), JSON_PRETTY_PRINT);
} else {
$records = array();
while ($obj = sqlsrv_fetch_object($SQL)) {
array_push($records, $obj);
}
$dataString = json_encode($records, JSON_PRETTY_PRINT);
}
echo $dataString;
 */
?>

<!--?php /* -->
<!-- Devolver respuesta como tabla -->
<table id="footable_Componente" class="table" data-paging="true" data-sorting="true">
    <thead>
        <tr>
            <th>Código Artículo</th>
            <th>Artículo</th>

            <th>Unidad Medida</th>
            <th>Jerarquía 1</th>
            <th>Jerarquía 2</th>
            <th>Ubicación</th>
            
            <th>Núm.</th>
            
            <th data-breakpoints="all">Fecha Operación</th>
            <th data-breakpoints="all">Contador/Horómetro</th>

            <?php foreach ($array_Dimensiones as &$dim) { ?>
                <th data-breakpoints="all">
                    <?php echo $dim['IdPortalOne']; ?>
                </th>
            <?php } ?>

            <th data-breakpoints="all">Proyecto</th>

            <th data-breakpoints="all">Estado</th>
            <th data-breakpoints="all">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
            <tr>
                <td>
					<?php echo $row['id_articulo_hijo']; ?>
                </td>
                <td>
                    <?php echo $row['articulo_hijo']; ?>
                </td>
                
                <td>
                    <?php echo $row['unidad_hijo']; ?>
                </td>
                <td>
                    <?php echo $row['jerarquia_1_hijo']; ?>
                </td>
                <td>
                    <?php echo $row['jerarquia_2_hijo']; ?>
                </td>
                <td>
                    <?php echo $row['ubicacion_hijo']; ?>
                </td>

                <td>
                    <?php $descripcion_te = 'SN Fabricante: ' . $row['serial_fabricante_hijo'] . ' - Núm. Serie: ' . $row['serial_interno_hijo']; ?>
                    <?php $de_articulo = $row['id_articulo_hijo'] . ' - ' . $row['articulo_hijo'] . ' (' . $row['jerarquia_1_hijo'] . ') (' . $row['jerarquia_2_hijo'] . ')'; ?>
                    <a type="button" class="btn btn-success btn-xs" title="Adicionar o cambiar TE"
                        onclick="cambiarTE_Componente('<?php echo $row['id_tarjeta_equipo_hijo']; ?>', '<?php echo $descripcion_te; ?>', '<?php echo $row['id_articulo_hijo']; ?>', '<?php echo $de_articulo; ?>')">
                        <b>
                            <?php echo $row['id_tarjeta_equipo_hijo']; ?>
                        </b>
                    </a>
                </td>

                <td>
                    <?php echo $row['fecha_operacion_hijo']; ?>
                </td>
                <td>
                    <?php echo $row['contador_hijo']; ?>
                </td>
                
                <?php foreach ($array_Dimensiones as &$dim) { ?>
                    <td>
                        <?php $DimCode = intval($dim['DimCode'] ?? 0); ?>
                        <?php echo $row["dimension_$DimCode"]; ?>
                    </td>
                <?php } ?>

                <td>
                    <?php echo $row['proyecto_hijo']; ?>
                </td>

                <td>
                    <?php if ($row['id_estado_hijo'] == 'A') { ?>
                        <span class='label label-info'>Activo</span>
                    <?php } elseif ($row['id_estado_hijo'] == 'R') { ?>
                        <span class='label label-danger'>Devuelto</span>
                    <?php } elseif ($row['id_estado_hijo'] == 'T') { ?>
                        <span class='label label-success'>Finalizado</span>
                    <?php } elseif ($row['id_estado_hijo'] == 'L') { ?>
                        <span class='label label-secondary'>Concedido en préstamo</span>
                    <?php } elseif ($row['id_estado_hijo'] == 'I') { ?>
                        <span class='label label-warning'>En laboratorio de reparación</span>
                    <?php } ?>
                </td>
                <td>
                    <a href="tarjeta_equipo.php?id=<?php echo base64_encode($row['id_tarjeta_equipo_hijo']); ?>&tl=1"
                        class="btn btn-success btn-xs" target="_blank">
                        <i class="fa fa-folder-open-o"></i> Abrir
                    </a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<!-- */ ?-->

<?php // Cerrar conexión de servicio asíncrono ?>
<?php sqlsrv_close($conexion); ?>