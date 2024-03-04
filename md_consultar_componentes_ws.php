<?php require_once "includes/conexion.php";

print_r($_POST);
exit();

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

$EstadoEquipo = $_POST["EstadoEquipo"] ?? "";
if ($EstadoEquipo != "") {
    $Filtro .= " AND CodEstado='$EstadoEquipo'";
}

$Cliente = $_POST["Cliente"] ?? "";
if ($Cliente != "") {
    $Filtro .= " AND CardCode = '$Cliente'";
}

$BuscarDato = $_POST['BuscarDato'] ?? "";
if ($BuscarDato != "") {
    $Filtro .= " AND (Calle LIKE '%$BuscarDato%' OR CodigoPostal LIKE '%$BuscarDato%' OR Barrio LIKE '%$BuscarDato%' OR Ciudad LIKE '%$BuscarDato%' OR Distrito LIKE '%$BuscarDato%' OR SerialFabricante LIKE '%$BuscarDato%' OR SerialInterno LIKE '%$BuscarDato%' OR IdTarjetaEquipo LIKE '%$BuscarDato%')";
}

// Realizar consulta con filtros
$Where = "$Filtro ORDER BY IdTarjetaEquipo DESC";
$Cons_TE = "SELECT TOP 1000 * FROM uvw_Sap_tbl_TarjetasEquipos WHERE $Where";
$SQL = sqlsrv_query($conexion, $Cons_TE);

// SMM, 21/11/2023
// if (!$SQL) {
    echo $Cons_TE;
// }
?>

<table id="footableOne" class="table" data-paging="true" data-sorting="true">
    <thead>
        <tr>
            <th>Código cliente</th>
            <th>Cliente</th>
            <th>Serial fabricante</th>

            <th>Acciones</th>

            <th>Serial interno</th>
            <th>Núm.</th>
            <th data-breakpoints="all">Código de artículo</th>
            <th data-breakpoints="all">Artículo</th>
            
            <?php foreach ($array_Dimensiones as &$dim) { ?>
                <th data-breakpoints="all">
                    <?php echo $dim['IdPortalOne']; ?>
                </th>
            <?php } ?>
            
            <th data-breakpoints="all">Tipo de equipo</th>
            <th data-breakpoints="all">Estado</th>
            <th data-breakpoints="all">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
            <tr id="<?php echo $row['IdArticulo']; ?>">
                <td>
                    <?php echo $row['CardCode']; ?>
                </td>
                <td>
                    <?php echo $row['CardName']; ?>
                </td>
                <td>
                    <?php echo $row['SerialFabricante']; ?>
                </td>

                <td>
                    <button class="btn btn-success btn-xs"
                        onclick="AgregarArticulo('<?php echo $row['IdArticulo']; ?>');"><i class="fa fa-plus"></i>
                        Agregar</a>
                </td>

                <td>
                    <?php echo $row['SerialInterno']; ?>
                </td>
                <td>
                    <a type="button" class="btn btn-success btn-xs" title="Adicionar o cambiar TE"
                        onclick="cambiarTE('<?php echo $row['IdTarjetaEquipo']; ?>', '<?php echo 'SN Fabricante: ' . $row['SerialFabricante'] . ' - Núm. Serie: ' . $row['SerialInterno']; ?>', '<?php echo $row['SerialInterno']; ?>')">
                        <b>
                            <?php echo $row['IdTarjetaEquipo']; ?>
                        </b>
                    </a>
                </td>
                <td>
                    <?php echo $row['ItemCode']; ?>
                </td>
                <td>
                    <?php echo $row['ItemName']; ?>
                </td>

                <?php foreach ($array_Dimensiones as &$dim) { ?>
                    <td class="<?php echo $dim['IdPortalOne']; ?>">
                        <?php echo $_POST[$dim['IdPortalOne']] ?? ""; ?>
                    </td>
                <?php } ?>

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