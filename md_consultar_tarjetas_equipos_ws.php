<?php require_once "includes/conexion.php";
/* JSON de ejemplo (también pueden llegar vacios ""):
{
Cliente: "CL-1054994729",
FechaFinal: "2022-08-04",
FechaInicial: "2022-07-20",
IDTicket: "111000047",
NombreCliente: "Stiven Muñoz Murillo",
Series: "142",
Sucursal: "CHINCHINA"
}
 */

// SMM, 18/03/2024
$Filtro = "[TipoEquipo] <> '' AND [PadreComponente] <> 'Activo Componente'";

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
<table id="footable" class="table" data-paging="true" data-sorting="true">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Articulo</th>
            <th>Serial fabricante</th>
            <th>Serial interno</th>
            <th>Núm.</th>
            <th data-breakpoints="all">Unidad de medida</th>
			<th data-breakpoints="all">Ubicación</th>
            <th data-breakpoints="all">Tipo de equipo</th>
            <th data-breakpoints="all">Estado</th>
            <th data-breakpoints="all">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
            <tr>
                <td>
                    <?php echo $row['CardCode'] . " (" . $row['CardName'] . ")"; ?>
                </td>
                <td>
                    <?php echo $row['ItemCode'] . " (" . $row['ItemName'] . ")"; ?>
                </td>
                <td>
                    <?php echo $row['SerialFabricante']; ?>
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
                    <?php echo $row['UnidadMedidaEquipo']; ?>
                </td>
                <td>
                    <?php echo $row['Ubicacion']; ?>
                </td>
                <td>
                    <?php echo $row['TipoEquipoPropiedad']; ?>
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