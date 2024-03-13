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

$Filtro = "TipoEquipo <> ''";

$ItemCode = $_POST["ItemCode"] ?? "";
if ($ItemCode != "") {
    $Filtro .= " AND ItemCode='$ItemCode'";
}

$SerialEquipo = $_POST["SerialEquipo"] ?? "";
if ($SerialEquipo != "") {
    $Filtro .= " AND (SerialFabricante LIKE '%$SerialEquipo%' OR SerialInterno LIKE '%$SerialEquipo%')";
}

$BuscarDato = $_POST['BuscarDato'] ?? "";
if ($BuscarDato != "") {
    $Filtro .= " AND (Calle LIKE '%$BuscarDato%' OR CodigoPostal LIKE '%$BuscarDato%' OR Barrio LIKE '%$BuscarDato%' OR Ciudad LIKE '%$BuscarDato%' OR Distrito LIKE '%$BuscarDato%' OR SerialFabricante LIKE '%$BuscarDato%' OR SerialInterno LIKE '%$BuscarDato%' OR IdTarjetaEquipo LIKE '%$BuscarDato%')";
}

$IdJerarquia1 = $_POST["id_jerarquia_1"] ?? "";
if ($IdJerarquia1 != "") {
    $Filtro .= " AND IdJerarquia1 = '$IdJerarquia1'";
}

$IdJerarquia2 = $_POST["id_jerarquia_2"] ?? "";
if ($IdJerarquia2 != "") {
    $Filtro .= " AND IdJerarquia2 = '$IdJerarquia2'";
}

$UbicacionEquipo = $_POST["id_ubicacion_equipo"] ?? "";
if ($UbicacionEquipo != "") {
    $Filtro .= " AND IdUbicacion = '$UbicacionEquipo'";
}

// Realizar consulta con filtros
$Where = "$Filtro ORDER BY IdTarjetaEquipo DESC";
$Cons_TE = "SELECT TOP 1000 * FROM uvw_Sap_tbl_TarjetasEquipos WHERE $Where";
$SQL = sqlsrv_query($conexion, $Cons_TE);

// SMM, 21/11/2023
if (!$SQL) {
    echo $Cons_TE;
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
                    <?php echo $row['jerarquia_1']; ?>
                </td>
                <td>
                    <?php echo $row['jerarquia_2']; ?>
                </td>
                <td>
                    <?php echo $row['ubicacion_hijo']; ?>
                </td>

                <td>
                    <a type="button" class="btn btn-success btn-xs" title="Adicionar o cambiar TE"
                        onclick="cambiarTE_Componente('<?php echo $row['IdTarjetaEquipo']; ?>', '<?php echo 'SN Fabricante: ' . $row['SerialFabricante'] . ' - Núm. Serie: ' . $row['SerialInterno']; ?>', '<?php echo $row['ItemCode']; ?>', '<?php echo $row['ItemName']; ?>')">
                        <b>
                            <?php echo $row['IdTarjetaEquipo']; ?>
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
                    <?php if ($row['CodEstado'] == 'A') { ?>
                        <span class='label label-info'>Activo</span>
                    <?php } elseif ($row['CodEstado'] == 'R') { ?>
                        <span class='label label-danger'>Devuelto</span>
                    <?php } elseif ($row['CodEstado'] == 'T') { ?>
                        <span class='label label-success'>Finalizado</span>
                    <?php } elseif ($row['CodEstado'] == 'L') { ?>
                        <span class='label label-secondary'>Concedido en préstamo</span>
                    <?php } elseif ($row['CodEstado'] == 'I') { ?>
                        <span class='label label-warning'>En laboratorio de reparación</span>
                    <?php } ?>
                </td>
                <td>
                    <a href="tarjeta_equipo.php?id=<?php echo base64_encode($row['IdTarjetaEquipo']); ?>&tl=1"
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