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

// Filtrar fechas
if (isset($_POST['FechaInicialSLS']) && $_POST['FechaInicialSLS'] != "") {
    $FechaInicial = $_POST['FechaInicialSLS'];
} else {
    $FechaInicial = date('Y-m-d');
}

if (isset($_POST['FechaFinalSLS']) && $_POST['FechaFinalSLS'] != "") {
    $FechaFinal = $_POST['FechaFinalSLS'];
} else {
    $FechaFinal = date('Y-m-d');
}

// Filtrar estados
$Filtro = "";
$Filtro .= " AND [IdEstadoLlamada] <> -1";

// Obtener series de llamada
$ParamSerie = array(
    "'" . $_SESSION['CodUser'] . "'",
    "'191'",
    1,
);
$SQL_SeriesLlamada = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Filtrar series
$FilSerie = "";
$i = 0;
while ($row_Series = sqlsrv_fetch_array($SQL_SeriesLlamada)) {
    if ($i == 0) {
        $FilSerie .= "'" . $row_Series['IdSeries'] . "'";
    } else {
        $FilSerie .= ",'" . $row_Series['IdSeries'] . "'";
    }
    $i++;
}
$Filtro .= " AND [IdSeries] IN (" . $FilSerie . ")";
$SQL_SeriesLlamada = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Filtrar serie seleccionada
if (isset($_POST['SeriesSLS']) && $_POST['SeriesSLS'] != "") {
    $Filtro .= " AND [IdSeries]='" . $_POST['SeriesSLS'] . "'";
}

// Filtrar cliente
if (isset($_POST['ClienteSLS']) && ($_POST['ClienteSLS'] != "")) {
    $Filtro .= " AND ID_CodigoCliente='" . $_POST['ClienteSLS'] . "'";
}

// Filtrar sucursal
if (isset($_POST['Sucursal']) && ($_POST['Sucursal'] != "")) {
    $Filtro .= " AND NombreSucursal='" . $_POST['Sucursal'] . "'";
}

// Clausula Where para la consulta con filtros
$Where = "([FechaCreacionLLamada] BETWEEN '$FechaInicial' AND '$FechaFinal') $Filtro";

// Filtrar ticket, elimina los otros filtros
if (isset($_POST['IDTicket']) && $_POST['IDTicket'] != "") {
    $Where = "DocNum LIKE '%" . trim($_POST['IDTicket']) . "%'";
}

// Realizar consulta con filtros
$SQL = Seleccionar('uvw_tbl_SolicitudLlamadasServicios', 'TOP 100 *', $Where);
// echo "<script> console.log($Where); </script>";

if (!$SQL) {
    echo "Consulta WS:<br>";
    echo "SELECT TOP 100 * FROM uvw_tbl_SolicitudLlamadasServicios WHERE $Where";
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
<table id="footableSLS" class="table" data-paging="true" data-sorting="true">
    <thead>
        <tr>
            <th>Fecha creación</th>
            <th>Sucursal</th>
            <th>Cliente</th>

            <th>Estado</th>
            
            <th>Tipo llamada</th>
            <th>Asunto</th>
            <th>Ticket</th>
            <th data-breakpoints="all">Serial Interno</th>
            <th data-breakpoints="all">Tipo problema</th>
            <th data-breakpoints="all">Estado servicio</th>
            <th data-breakpoints="all">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
            <tr>
                <td>
                    <?php echo $row['FechaHoraCreacionLLamada']->format('Y-m-d H:i'); ?>
                </td>
                <td>
                    <?php echo $row['NombreSucursal']; ?>
                </td>
                <td>
                    <?php echo $row['NombreClienteLlamada']; ?>
                </td>

                <td>
                    <span <?php if ($row['IdEstadoLlamada'] == '-3') {
                        echo "class='label label-info'";
                    } else {
                        echo "class='label label-danger'";
                    } ?>>
                        <?php echo $row['NombreEstado']; ?>
                    </span>
                </td>

                <td>
                    <?php echo $row['DeTipoLlamada']; ?>
                </td>
                <td>
                    <?php echo $row['AsuntoLlamada']; ?>
                </td>
                <td>
                    <a type="button" class="btn btn-success btn-xs"
                        title="Cambiar Agenda"
                        onclick="cambiarSLS('<?php echo $row['ID_SolicitudLlamadaServicio']; ?>', '<?php echo $row['ID_SolicitudLlamadaServicio'] . ' - ' . $row['AsuntoLlamada'] . ' (' . $row['DeTipoLlamada'] . ')'; ?>')">
                        <i class="fa fa-refresh"></i> <b><?php echo $row['ID_SolicitudLlamadaServicio']; ?></b>
                    </a>
                </td>
                <td>
                    <?php echo $row['IdNumeroSerie']; ?>
                </td>
                <td>
                    <?php echo $row['DeTipoProblemaLlamada']; ?>
                </td>
                <td>
                    <span <?php if ($row['CDU_EstadoServicio'] == '0') {
                        echo "class='label label-warning'";
                    } elseif ($row['CDU_EstadoServicio'] == '1') {
                        echo "class='label label-primary'";
                    } else {
                        echo "class='label label-danger'";
                    } ?>>
                        <?php echo $row['DeEstadoServicio']; ?>
                    </span>
                </td>
                <td>
                    <a target="_blank"
                        href="llamada_servicio.php?id=<?php echo base64_encode($row['ID_LlamadaServicio']); ?>&tl=1"
                        class="btn btn-success btn-xs"><i class="fa fa-folder-open-o"></i> Abrir</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<!-- */ ?-->

<?php // Cerrar conexión de servicio asíncrono ?>
<?php sqlsrv_close($conexion); ?>