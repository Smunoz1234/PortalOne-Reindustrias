<?php require_once "includes/conexion.php";
// Realizar consulta con filtros. SMM, 24/05/2023
$DatoBuscar = $_POST['BuscarItem'] ?? 0;
$WhsCode = $_POST['Almacen'] ?? 0;

$TipoDoc = $_POST['tipodoc'] ?? 1;
$TodosArticulos = $_POST['todosart'] ?? 0;
$SoloStock = $_POST['solostock'] ?? 1;

$Usuario = "'" . $_SESSION['CodUser'] . "'";
$SQL_GruposArticulos = Seleccionar("uvw_tbl_UsuariosGruposArticulos", "ID_Usuario", "ID_Usuario=$Usuario");

if (!sqlsrv_has_rows($SQL_GruposArticulos)) {
    $Usuario = "NULL";
}

$Param = array(
    "'$DatoBuscar'",
    "'$WhsCode'",
    "'$TipoDoc'",
    "'$SoloStock'",
    "'$TodosArticulos'",
    $Usuario,
);

$SQL = EjecutarSP('sp_ConsultarArticulos', $Param);
?>

<table id="footable" class="table" data-paging="true" data-sorting="true">
    <thead>
        <tr>
            <th>Nombre</th>
            <th data-breakpoints="all">Cod. Proveedor</th>
            <th data-breakpoints="all">Unidad Medida</th>
            <th data-breakpoints="all">Precio Sin IVA</th>
            <th data-breakpoints="all">Precio Con IVA</th>
            <th data-breakpoints="all">Almacen</th>
            <th data-breakpoints="all">Stock</th>
            <th data-breakpoints="all">Maneja Serial</th>
            <th data-breakpoints="all">Maneja Lote</th>
            <th data-breakpoints="all">Grupo Artículos</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = sql_fetch_array($SQL)) { ?>
            <tr>
                <td>
                    <?php echo $row['NombreBuscarArticulo']; ?>
                </td>
                <td>
                    <?php echo $row['CodArticuloProveedor'] ?? "--"; ?>
                </td>
                <td>
                    <?php echo $row['UndMedida']; ?>
                </td>
                <td>
                    <?php echo $row['PrecioSinIVA']; ?>
                </td>
                <td>
                    <?php echo $row['PrecioConIVA']; ?>
                </td>
                <td>
                    <?php echo $row['CodAlmacen'] . " - " . $row['Almacen']; ?>
                </td>
                <td>
                    <?php echo $row['StockAlmacen']; ?>
                </td>
                <td>
                    <?php echo $row['ManejaSerial']; ?>
                </td>
                <td>
                    <?php echo $row['ManejaLote']; ?>
                </td>
                <td>
                    <?php echo $row['ItmsGrpCod']; ?>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php // Cerrar conexión de servicio asíncrono ?>
<?php sqlsrv_close($conexion); ?>