<?php require_once "includes/conexion.php";
// Dimensiones. SMM, 29/05/2023
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimActive='Y'");

$array_Dimensiones = [];
while ($row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones)) {
    array_push($array_Dimensiones, $row_Dimension);
}
// Hasta aquí, SMM 29/05/2023

// Realizar consulta con filtros. SMM, 24/05/2023
$DatoBuscar = $_POST['BuscarItem'] ?? 0;
$WhsCode = $_POST['Almacen'] ?? 0;

$TipoDoc = $_POST['tipodoc'] ?? 1;
// @TipoDoc: 1 COMPRA, 2 VENTA, 3 INVENTARIO

$TodosArticulos = $_POST['todosart'] ?? 0;

$SoloStock = $_POST['chkStock'] ?? 2;
$IdListaPrecio = $_POST['ListaPrecio'] ?? "";

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
    "'$IdListaPrecio'",
    $Usuario,
);

$SQL = EjecutarSP('sp_ConsultarArticulos_ListaPrecios', $Param);
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

            <th data-breakpoints="all">Almacen Destino</th>
            <th data-breakpoints="all">Concepto Salida</th>
            
            <th data-breakpoints="all">Maneja Serial</th>
            <th data-breakpoints="all">Maneja Lote</th>
            <th data-breakpoints="all">Stock General</th>
            <th data-breakpoints="all">Grupo Artículos</th>
            <?php foreach ($array_Dimensiones as &$dim) { ?>
                <th data-breakpoints="all">
                    <?php echo $dim['IdPortalOne']; ?>
                </th>
            <?php } ?>
            <th data-breakpoints="all">EmpVentas</th>
            <th data-breakpoints="all">PrjCode</th>

            <th data-breakpoints="all">Tipo OT</th>
            <th data-breakpoints="all">Sede Empresa</th>
            <th data-breakpoints="all">Tipo Cargo</th>
            <th data-breakpoints="all">Tipo Problema</th>
            <th data-breakpoints="all">Tipo Preventivo</th>
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
                <td class="AlmacenDestino">
                    <?php echo $_POST['AlmacenDestino'] ?? ""; ?>
                </td>
                <td class="ConceptoSalida">
                    <?php echo $_POST['ConceptoSalida'] ?? ""; ?>
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
                <?php foreach ($array_Dimensiones as &$dim) { ?>
                    <td class="<?php echo $dim['IdPortalOne']; ?>">
                        <?php echo $_POST[$dim['IdPortalOne']] ?? ""; ?>
                    </td>
                <?php } ?>
                <td class="EmpVentas">
                    <?php echo $_POST['EmpVentas'] ?? ""; ?>
                </td>
                <td class="PrjCode">
                    <?php echo $_POST['Proyecto'] ?? ""; ?>
                </td>

                <td class="IdTipoOT">
                    <?php echo $_POST['IdTipoOT'] ?? ""; ?>
                </td>
                <td class="IdSedeEmpresa">
                    <?php echo $_POST['IdSedeEmpresa'] ?? ""; ?>
                </td>
                <td class="IdTipoCargo">
                    <?php echo $_POST['IdTipoCargo'] ?? ""; ?>
                </td>
                <td class="IdTipoProblema">
                    <?php echo $_POST['IdTipoProblema'] ?? ""; ?>
                </td>
                <td class="IdTipoPreventivo">
                    <?php echo $_POST['IdTipoPreventivo'] ?? ""; ?>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php // Cerrar conexión de servicio asíncrono ?>
<?php sqlsrv_close($conexion); ?>