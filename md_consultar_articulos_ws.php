<?php require_once "includes/conexion.php";
// Realizar consulta con filtros. SMM, 24/05/2023
$DatoBuscar = $_POST['BuscarItem'] ?? 0;
$WhsCode = $_POST['Almacen'] ?? 0;

$TipoDoc = $_POST['tipodoc'] ?? 1;
$TodosArticulos = $_POST['todosart'] ?? 0;
$SoloStock = $_POST['solostock'] ?? 1;

$Usuario = "'" . $_SESSION['CodUser'] . "'";

$Param = array(
	"'$DatoBuscar'",
	"'$WhsCode'",
	"'$TipoDoc'",
	"'$SoloStock'",
	"'$TodosArticulos'",
	$Usuario,
);

$SQL_Articulos = EjecutarSP('sp_ConsultarArticulos', $Param);
$hasRows = sqlsrv_has_rows($SQL_Articulos);
?>

<table id="footable" class="table" data-paging="true" data-sorting="true">
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
            <th data-breakpoints="all">Asignado por</th>
            <th data-breakpoints="all">Tipo problema</th>
            <th data-breakpoints="all">Estado servicio</th>
            <th data-breakpoints="all">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = sql_fetch_array($SQL)) { ?>
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
                    } elseif ($row['IdEstadoLlamada'] == '-2') {
                        echo "class='label label-warning'";
                    } else {
                        echo "class='label label-danger'";
                    } ?>>
                        <?php echo $row['DeEstadoLlamada']; ?>
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
                        onclick="cambiarOT('<?php echo $row['ID_LlamadaServicio']; ?>', '<?php echo $row['DocNum'] . ' - ' . $row['AsuntoLlamada'] . ' (' . $row['DeTipoLlamada'] . ')'; ?>')"><b>
                            <?php echo $row['DocNum']; ?>
                        </b></a>
                </td>
                <td>
                    <?php echo $row['IdNumeroSerie']; ?>
                </td>
                <td>
                    <?php echo $row['DeAsignadoPor']; ?>
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

<?php // Cerrar conexión de servicio asíncrono ?>
<?php sqlsrv_close($conexion); ?>