<?php require_once "includes/conexion.php";

$VIN = $_POST['VIN'] ?? 0;
$DatoBuscar = $_POST['BuscarItem'] ?? 0;

$Param = array(
    "'$DatoBuscar'"
);
$SQL = EjecutarSP('sp_ConsultarArticulosTodos', $Param);
?>

<table id="footableOne" class="table" data-paging="true" data-sorting="true">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Stock</th>
            <th>Precio Con IVA</th>
            <th>Acciones</th>
            <th data-breakpoints="all">Cod. Lista Precios</th>
            <th data-breakpoints="all">Lista Precios</th>
            <th data-breakpoints="all">Cod. Proveedor</th>
            <th data-breakpoints="all">Unidad Medida</th>
            <th data-breakpoints="all">Precio Sin IVA</th> 
            <th data-breakpoints="all">Cod. Almacen</th>
            <th data-breakpoints="all">Nombre Almacen</th>
            <th data-breakpoints="all">Maneja Serial</th>
            <th data-breakpoints="all">Maneja Lote</th>
            <th data-breakpoints="all">Stock General</th>
            <th data-breakpoints="all">Grupo Artículos</th>
            <th data-breakpoints="all">VIN</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = sql_fetch_array($SQL)) { ?>
            <tr id="<?php echo $row['IdArticulo']; ?>">
                <td>
                    <?php echo $row['NombreBuscarArticulo']; ?>
                </td>
                <td>
                    <?php echo $row['StockAlmacen']; ?>
                </td>
                <td>
                    <?php echo $row['PrecioConIVA']; ?>
                </td>
                <td>
                    <button class="btn btn-success btn-xs"
                        onclick="AgregarArticulo('<?php echo $row['IdArticulo']; ?>');"><i class="fa fa-plus"></i>
                        Agregar</a>
                </td>
                <td class="PriceList">
                    <?php echo $row['PriceList']; ?>
                </td>
                <td>
                    <?php echo $row['ListaPrecio']; ?>
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
                <td class="WhsCode">
                    <?php echo $row['CodAlmacen']; ?>
                </td>
                <td>
                    <?php echo $row['Almacen']; ?>
                </td>
                <td>
                    <?php echo $row['ManejaSerial']; ?>
                </td>
                <td>
                    <?php echo $row['ManejaLote']; ?>
                </td>
                <td>
                    <?php echo $row['StockGeneral']; ?>
                </td>
                <td>
                    <?php echo $row['ItmsGrpCod']; ?>
                </td>
                <td class="VIN">
                    <?php echo $VIN; ?>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php // Cerrar conexión de servicio asíncrono ?>
<?php sqlsrv_close($conexion); ?>