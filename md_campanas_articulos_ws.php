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
            <th>Acciones</th>
            <th data-breakpoints="all">ID</th>
            <th data-breakpoints="all">Descripción</th>
            <th data-breakpoints="all">VIN</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = sql_fetch_array($SQL)) { ?>
            <tr id="<?php echo $row['IdArticulo']; ?>">
                <td>
                    <?php echo $row['NombreBuscarArticulo'] ?? ""; ?>
                </td>
                <td>
                    <button type="button" class="btn btn-success btn-xs"
                        onclick="AgregarArticulo('<?php echo $row['IdArticulo']; ?>');"><i class="fa fa-plus"></i>
                        Agregar</a>
                </td>
                <td>
                    <?php echo $row['IdArticulo']; ?>
                </td>
                <td class="descripcion">
                    <?php echo $row['DescripcionArticulo'] ?? ""; ?>
                </td>
                <td class="vin">
                    <?php echo $VIN; ?>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php // Cerrar conexión de servicio asíncrono ?>
<?php sqlsrv_close($conexion); ?>