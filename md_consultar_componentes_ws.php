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

// SMM, 05/03/2024
$IdDoc = $_POST["id_doc"] ?? "";
$Filtro = "TipoEquipo <> '' AND PadreComponente='No Asignado' AND IdTarjetaEquipo<>'$IdDoc'";

$ItemCode = $_POST["item_code"] ?? "";
if ($ItemCode != "") {
    $Filtro .= " AND ItemCode='$ItemCode'";
}

$SerialEquipo = $_POST["serial_equipo"] ?? "";
if ($SerialEquipo != "") {
    $Filtro .= " AND (SerialFabricante LIKE '%$SerialEquipo%' OR SerialInterno LIKE '%$SerialEquipo%')";
}

$BuscarDato = $_POST['buscar_dato'] ?? "";
if ($BuscarDato != "") {
    $Filtro .= " AND (Calle LIKE '%$BuscarDato%' OR CodigoPostal LIKE '%$BuscarDato%' OR Barrio LIKE '%$BuscarDato%' OR Ciudad LIKE '%$BuscarDato%' OR Distrito LIKE '%$BuscarDato%' OR SerialFabricante LIKE '%$BuscarDato%' OR SerialInterno LIKE '%$BuscarDato%' OR IdTarjetaEquipo LIKE '%$BuscarDato%')";
}

$TipoEquipo = $_POST["id_tipo_equipo"] ?? "";
if ($TipoEquipo != "") {
    $Filtro .= " AND IdTipoEquipoPropiedad='$TipoEquipo'";
}

$UbicacionEquipo = $_POST["id_ubicacion_equipo"] ?? "";
if ($UbicacionEquipo != "") {
    $Filtro .= " AND IdUbicacion = '$UbicacionEquipo'";
}

$IdJerarquia1 = $_POST["id_jerarquia_1"] ?? "";
if ($IdJerarquia1 != "") {
    $Filtro .= " AND IdJerarquia1 = '$IdJerarquia1'";
}

$IdJerarquia2 = $_POST["id_jerarquia_2"] ?? "";
if ($IdJerarquia2 != "") {
    $Filtro .= " AND IdJerarquia2 = '$IdJerarquia2'";
}

$IdProyecto = $_POST["id_proyecto"] ?? "";
if ($IdProyecto != "") {
    $Filtro .= " AND IdProyecto = '$IdProyecto'";
}

$IdDimension1 = $_POST["id_dimension_1"] ?? "";
if ($IdDimension1 != "") {
    $Filtro .= " AND IdDimension1 = '$IdDimension1'";
}

$IdDimension2 = $_POST["id_dimension_2"] ?? "";
if ($IdDimension2 != "") {
    $Filtro .= " AND IdDimension2 = '$IdDimension2'";
}

$IdDimension3 = $_POST["id_dimension_3"] ?? "";
if ($IdDimension3 != "") {
    $Filtro .= " AND IdDimension3 = '$IdDimension3'";
}

$IdDimension4 = $_POST["id_dimension_4"] ?? "";
if ($IdDimension4 != "") {
    $Filtro .= " AND IdDimension4 = '$IdDimension4'";
}

$IdDimension5 = $_POST["id_dimension_5"] ?? "";
if ($IdDimension5 != "") {
    $Filtro .= " AND IdDimension5 = '$IdDimension5'";
}

// Realizar consulta con filtros
$Where = "$Filtro ORDER BY IdTarjetaEquipo DESC";
$Cons_TE = "SELECT TOP 1000 * FROM uvw_Sap_tbl_TarjetasEquipos WHERE $Where";
$SQL = sqlsrv_query($conexion, $Cons_TE);

// SMM, 04/03/2024
if (!$SQL) {
    echo $Cons_TE;
}
?>

<table id="footableOne" class="table" data-paging="true" data-sorting="true">
    <thead>
        <tr>
            <th>Código Artículo</th>
            <th>Artículo</th>
            
            <th>Jerarquia 1</th>
            <th>Jerarquia 2</th>
            
            <th>Ubicación</th>
            
            <th>Acciones</th>

            <th data-breakpoints="all">ID Tarjeta Equipo</th>
            <th data-breakpoints="all">Tipo Equipo Propiedad</th>
            
            <th data-breakpoints="all">Serial Interno</th>
            <th data-breakpoints="all">Serial Fabricante</th>

            <th data-breakpoints="all">Proyecto</th>
            
            <?php foreach ($array_Dimensiones as &$dim) { ?>
                <th data-breakpoints="all">
                    <?php echo $dim['IdPortalOne']; ?>
                </th>
            <?php } ?>
            
            <th data-breakpoints="all">Código Cliente</th>
            <th data-breakpoints="all">Cliente</th>
            <th data-breakpoints="all">Tipo Proceso</th>
            <th data-breakpoints="all">Estado</th>
            <th data-breakpoints="all">Otras Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
            <tr id="<?php echo $row['IdTarjetaEquipo']; ?>">
                <td>
                    <?php echo $row['ItemCode']; ?>
                </td>
                <td>
                    <?php echo $row['ItemName']; ?>
                </td>

                <td>
                    <?php echo $row["Jerarquia1"] ?? ""; ?>
                </td>
                <td>
                    <?php echo $row["Jerarquia2"] ?? ""; ?>
                </td>

                <td>
                    <?php echo $row['Ubicacion']; ?>
                </td>

                <td>
                    <button class="btn btn-success btn-xs"
                        onclick="AgregarArticulo('<?php echo $row['IdTarjetaEquipo']; ?>');"><i class="fa fa-plus"></i>
                        Agregar</a>
                </td>

                <td class="IdTarjetaEquipo">
                    <?php echo $row['IdTarjetaEquipo']; ?>
                </td>
                <td>
                    <?php echo $row['TipoEquipoPropiedad']; ?>
                </td>

                <td>
                    <?php echo $row['SerialInterno']; ?>
                </td>
                <td>
                    <?php echo $row['SerialFabricante']; ?>
                </td>

                <td>
                    <?php echo $row['Proyecto']; ?>
                </td>

                <?php foreach ($array_Dimensiones as &$dim) { ?>
                    <td class="<?php echo $dim['IdPortalOne']; ?>">
                        <?php echo $_POST[$dim['IdPortalOne']] ?? ""; ?>
                    </td>
                <?php } ?>

                <td>
                    <?php echo $row['CardCode']; ?>
                </td>
                <td>
                    <?php echo $row['CardName']; ?>
                </td>
                <td>
                    <?php if ($row['TipoEquipo'] === 'P') {
                        echo 'Compras';
                    } elseif ($row['TipoEquipo'] === 'R') {
                        echo 'Ventas';
                    } ?>
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

<?php // Cerrar conexión de servicio asíncrono ?>
<?php sqlsrv_close($conexion); ?>